<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Dashboard\Support\PeriodStats;
use Modules\Dashboard\Support\SalesBucketer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Order\Enums\OrderStatusEnum;
use Modules\Order\Models\Order;
use Modules\Payment\Models\Payment;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCategory;
use Modules\User\Enums\UserRoles;
use Modules\User\Models\User;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (in_array($user->role, [UserRoles::SUPER_ADMIN, UserRoles::ADMIN], true)) {
            $view = $user->role === UserRoles::SUPER_ADMIN
                ? 'app/dashboards/SuperAdmin'
                : 'app/dashboards/Admin';

            return inertia($view, [
                'user'  => $user,
                'stats' => $this->adminStats($request->query('period', 'month')),
            ]);
        }

        if ($user->role === UserRoles::CASHIER) {
            return inertia('app/dashboards/Cashier', [
                'user'  => $user,
                'stats' => $this->cashierStats($user->id),
            ]);
        }

        if ($user->role === UserRoles::CUSTOMER) {
            return inertia('app/dashboards/Customer', [
                'user'  => $user,
                'stats' => [],
            ]);
        }

        return inertia('app/dashboards/Dashboard');
    }

    /**
     * Shared payload for SUPER_ADMIN + ADMIN.
     */
    private function adminStats(string $period): array
    {
        $period = in_array($period, ['today', 'week', 'month', 'year'], true) ? $period : 'month';

        // ── Period ranges ────────────────────────────────────────────────
        $ranges = [
            'today'      => [now()->startOfDay(),  now()->endOfDay()],
            'yesterday'  => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'week'       => [now()->startOfWeek(),  now()->endOfWeek()],
            'last_week'  => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'month'      => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'year'       => [now()->startOfYear(),  now()->endOfYear()],
            'last_year'  => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
        ];

        // ── Aggregate once per period ────────────────────────────────────
        $p = [];
        foreach ($ranges as $key => [$from, $to]) {
            $p[$key] = PeriodStats::for($from, $to);
        }

        // ── Deltas (current vs matching previous period) ─────────────────
        $deltas = [
            'today' => [
                'revenue' => PeriodStats::delta($p['today']['revenue'], $p['yesterday']['revenue']),
                'orders'  => PeriodStats::delta($p['today']['orders'],  $p['yesterday']['orders']),
            ],
            'week' => [
                'revenue' => PeriodStats::delta($p['week']['revenue'], $p['last_week']['revenue']),
                'orders'  => PeriodStats::delta($p['week']['orders'],  $p['last_week']['orders']),
            ],
            'month' => [
                'revenue' => PeriodStats::delta($p['month']['revenue'], $p['last_month']['revenue']),
                'orders'  => PeriodStats::delta($p['month']['orders'],  $p['last_month']['orders']),
            ],
            'year' => [
                'revenue' => PeriodStats::delta($p['year']['revenue'], $p['last_year']['revenue']),
                'orders'  => PeriodStats::delta($p['year']['orders'],  $p['last_year']['orders']),
            ],
        ];

        // ── Trend chart for the selected period ──────────────────────────
        [$chartFrom, $chartTo] = $ranges[$period];
        $trend = SalesBucketer::for($period, $chartFrom, $chartTo);

        // ── Payment split scoped to the selected period ──────────────────
        $paymentSplit = Payment::query()
            ->whereIn('payment_method', ['mpesa', 'cash'])
            ->whereIn('order_id', function ($q) use ($chartFrom, $chartTo) {
                $q->select('id')
                    ->from('orders')
                    ->where('order_status', OrderStatusEnum::COMPLETED->value)
                    ->whereBetween('sold_at', [$chartFrom, $chartTo]);
            })
            ->selectRaw('payment_method, COALESCE(SUM(amount), 0) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        // ── Order status mix scoped to the selected period ───────────────
        $statusMix = Order::query()
            ->whereBetween('sold_at', [$chartFrom, $chartTo])
            ->selectRaw('order_status, COUNT(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status');

        // ── Top products scoped to the selected period ───────────────────
        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.order_status', OrderStatusEnum::COMPLETED->value)
            ->whereBetween('orders.sold_at', [$chartFrom, $chartTo])
            ->selectRaw('
                products.id,
                products.name,
                SUM(order_items.quantity) as qty,
                SUM(order_items.selling_price) as revenue
            ')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'id'      => $row->id,
                'name'    => $row->name,
                'qty'     => (int) $row->qty,
                'revenue' => (float) $row->revenue,
            ]);

        // ── Counts (cheap, single query each) ────────────────────────────
        $counts = [
            'total_users'             => User::where('role', '!=', UserRoles::SUPER_ADMIN)->count(),
            'total_admins'            => User::where('role', '=', UserRoles::ADMIN)->count(),
            'total_cashiers'          => User::where('role', '=', UserRoles::CASHIER)->count(),
            'total_products'          => Product::count(),
            'total_product_categories'=> ProductCategory::count(),
            'total_orders'            => Order::count(),
            'orders_need_attention'   => Order::needsAttention()->count(),
        ];

        return [
            'period'  => $period,
            'periods' => ['today', 'week', 'month', 'year'],

            // Tier 1 — glance zone
            'glance' => [
                'today' => [
                    'revenue' => $p['today']['revenue'],
                    'orders'  => $p['today']['orders'],
                    'delta'   => $deltas['today'],
                ],
                'needs_attention' => $counts['orders_need_attention'],
                'low_stock_count' => Product::query()
                    ->where('is_active', true)
                    ->where('track_inventory', true)
                    ->whereColumn('current_stock', '<=', 'low_stock_threshold')
                    ->count(),
            ],

            // Tier 2 — trend chart
            'trend' => [
                'labels' => $trend['labels'],
                'values' => $trend['values'],
            ],

            // Tier 3 — breakdowns
            'payment_breakdown' => [
                'mpesa' => (float) ($paymentSplit['mpesa'] ?? 0),
                'cash'  => (float) ($paymentSplit['cash']  ?? 0),
            ],
            'status_mix' => $statusMix->map(fn ($v) => (int) $v),
            'top_products' => $topProducts,

            // Tier 4 — profitability for the selected period
            'fiscal' => $p[$period],

            // Comparison deltas for all periods (used by the toggle labels)
            'deltas' => $deltas,

            // Misc counts
            'counts' => $counts,
        ];
    }

    /**
     * Cashier payload — unchanged logic, just extracted.
     */
    private function cashierStats(int $userId): array
    {
        $today = now()->startOfDay();

        $order_summary = Order::query()
            ->where('created_by', $userId)
            ->where('sold_at', '>=', $today)
            ->selectRaw('
                COUNT(*) as orders_count,
                COALESCE(SUM(CASE WHEN order_status != ? THEN total_selling_price ELSE 0 END), 0) as sales_total,
                SUM(CASE WHEN order_status IN (?, ?) AND amount_paid < total_selling_price THEN 1 ELSE 0 END) as pending_payment,
                SUM(CASE WHEN order_status = ? THEN 1 ELSE 0 END) as ready_for_pickup
            ', [
                OrderStatusEnum::CANCELLED->value,
                OrderStatusEnum::PENDING->value,
                OrderStatusEnum::PROCESSING->value,
                OrderStatusEnum::READY_FOR_PICKUP->value,
            ])
            ->first();

        $payment_breakdown = Payment::query()
            ->whereIn('order_id', function ($q) use ($userId, $today) {
                $q->select('id')
                    ->from('orders')
                    ->where('created_by', $userId)
                    ->where('sold_at', '>=', $today);
            })
            ->where('payment_status', 'paid')
            ->whereIn('payment_method', ['mpesa', 'cash'])
            ->selectRaw('payment_method, COALESCE(SUM(amount), 0) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        $lowStock = Product::query()
            ->where('is_active', true)
            ->where('track_inventory', true)
            ->whereColumn('current_stock', '<=', 'low_stock_threshold')
            ->orderBy('current_stock')
            ->limit(5)
            ->get(['id', 'name', 'current_stock'])
            ->map(fn ($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'current_stock' => (float) $p->current_stock,
            ]);

        return [
            'today' => [
                'orders_count'    => (int) $order_summary->orders_count,
                'sales_total'     => (float) $order_summary->sales_total,
                'cash_collected'  => (float) ($payment_breakdown['cash']  ?? 0),
                'mpesa_collected' => (float) ($payment_breakdown['mpesa'] ?? 0),
            ],
            'needs_attention' => [
                'pending_payment'  => (int) $order_summary->pending_payment,
                'ready_for_pickup' => (int) $order_summary->ready_for_pickup,
            ],
            'low_stock' => $lowStock,
        ];
    }
}