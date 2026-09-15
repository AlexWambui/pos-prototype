<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\User\Enums\UserRoles;
use Modules\User\Models\User;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCategory;
use Modules\Order\Enums\OrderStatusEnum;
use Modules\Order\Models\Order;
use Modules\Payment\Models\Payment;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->role === UserRoles::SUPER_ADMIN) {
            return inertia('app/dashboards/SuperAdmin', [
                'user' => $user,
                'stats' => [
                    'total_users' => User::where('role', '!=', UserRoles::SUPER_ADMIN)->count(),
                    'total_admins' => User::where('role', '=', UserRoles::ADMIN)->count(),
                    'total_cashiers' => User::where('role', '=', UserRoles::CASHIER)->count(),

                    'total_products' => Product::count(),
                    'total_product_categories' => ProductCategory::count(),

                    'total_orders' => Order::count(),
                    'orders_need_attention' => Order::where('order_status', OrderStatusEnum::PENDING->value)->whereColumn('amount_paid', '>=', 'total_selling_price')->count(),
                ]
            ]);
        }

        if ($user->role === UserRoles::ADMIN) {
            $current_year = now()->year;

            $completed_orders_summary = Order::completed()
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_selling_price), 0) as revenue, COALESCE(SUM(total_cost_price), 0) as cogs')->first();
            $completed_orders_count = (int) $completed_orders_summary->count();
            $total_revenue = (float) $completed_orders_summary->revenue;
            $total_cogs = (float) $completed_orders_summary->cogs;
            $total_gross_profit = $total_revenue - $total_cogs;
            $gross_profit_margin = $total_revenue > 0 ? ($total_gross_profit / $total_revenue) * 100 : 0;
            $aov = $completed_orders_count > 0 ? $total_revenue / $completed_orders_count : 0;

            $driver = DB::connection()->getDriverName();
            $monthlySalesQuery = Order::completed()->whereYear('sold_at', $current_year);
            if ($driver === 'sqlite') {
                $monthlySalesQuery
                    ->selectRaw("CAST(strftime('%m', sold_at) AS INTEGER) as month, SUM(total_selling_price) as total")
                    ->groupBy('month');
            } else {
                $monthlySalesQuery
                    ->selectRaw("EXTRACT(MONTH FROM sold_at)::int as month, SUM(total_selling_price) as total")
                    ->groupBy('month');
            }
            $raw = $monthlySalesQuery->pluck('total', 'month');
            $monthly_sales = collect(range(1, 12))
                ->map(fn ($m) => (float) ($raw[(string) $m] ?? $raw[$m] ?? 0))
                ->values()
                ->all();

            $mpesa_total = Payment::query()
                ->where('payment_method', 'mpesa')
                ->whereIn('order_id', Order::completed()->select('id'))
                ->sum('amount');
            $cash_total = Payment::query()
                ->where('payment_method', 'cash')
                ->whereIn('order_id', Order::completed()->select('id'))
                ->sum('amount');

            return inertia('app/dashboards/Admin', [
                'user' => $user,
                'stats' => [
                    'total_users' => User::where('role', '!=', UserRoles::SUPER_ADMIN)->count(),
                    'total_admins' => User::where('role', '=', UserRoles::ADMIN)->count(),
                    'total_cashiers' => User::where('role', '=', UserRoles::CASHIER)->count(),

                    'total_products' => Product::count(),
                    'total_product_categories' => ProductCategory::count(),

                    'total_orders' => Order::count(),
                    'orders_need_attention' => Order::needsAttention()->count(),

                    'monthly_sales' => $monthly_sales,
                    'payment_breakdown' => [
                        'mpesa' => (float) $mpesa_total,
                        'cash' => (float) $cash_total,
                    ],
                    'total_revenue' => (float) $total_revenue,
                    'total_cogs' => (float) $total_cogs,
                    'total_gross_profit' => (float) $total_gross_profit,
                    'gross_profit_margin' => (float) $gross_profit_margin,
                    'aov' => (float) $aov
                ]
            ]);
        }

        if ($user->role === UserRoles::CASHIER) {
            return inertia('app/dashboards/Cashier', [
                'user' => $user,
                'stats' => [
                    'total_products' => Product::where('is_active', true)->count(),
                    'total_product_categories' => ProductCategory::where('is_active', true)->count(),
                ]
            ]);
        }

        if ($user->role === UserRoles::CUSTOMER) {
            $ordersQuery = $user->orders();

            $stats = [
                'total_orders' => $ordersQuery->count(),
                'pending_orders' => (clone $ordersQuery)->pending()->count(),
                'processing_orders' => (clone $ordersQuery)->processing()->count(),
                'shipped_orders' => (clone $ordersQuery)->shipped()->count(),
                'delivered_orders' => (clone $ordersQuery)->delivered()->count(),
                'cancelled_orders' => (clone $ordersQuery)->cancelled()->count(),
                'active_orders' => (clone $ordersQuery)->active()->count(), // Using the new scope
                'total_spent' => (clone $ordersQuery)->paid()->sum('total_amount'),
                // 'recent_orders' => OrderResource::collection($ordersQuery->latest()->paginate(20)),
            ];

            return inertia('app/dashboards/Customer', [
                'user' => $user,
                'stats' => $stats
            ]);
        }
        return inertia('app/dashboards/Dashboard');
    }
}
