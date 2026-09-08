<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRoles;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->role === UserRoles::SUPER_ADMIN) {
            return inertia('dashboards/SuperAdmin', [
                'user' => $user,
                'stats' => $this->getSuperAdminStats()
            ]);
        }

        if ($user->role === UserRoles::ADMIN) {
            return inertia('dashboards/Admin', [
                'user' => $user,
                'stats' => $this->getAdminStats()
            ]);
        }

        if ($user->role === UserRoles::CASHIER) {
            if (!$user->hasOpenShift()) {
                return inertia('dashboards/CashierNoShift', [
                    'user' => $user,
                    'lastShift' => $user->shifts()->latest()->first(),
                    'status' => $this->getCashierStats()
                ]);
            }

            $current_shift = $user->openShift()->with('sales')->first();
            $shift_sales = $current_shift->sales()->sum('total_amount');
            $transaction_count = $current_shift->sales()->count();

            return inertia('dashboards/Cashier', [
                'user' => $user,
                'stats' => $this->getCashierStats(),
                'current_shift' => [
                    'id' => $current_shift->id,
                    'opened_at' => $current_shift->opened_at,
                    'opening_cash' => $current_shift->opening_cash,
                    'sales_total' => $shift_sales,
                    'transactions_count' => $transaction_count,
                    'duration_formatted' => $this->formatDuration($current_shift->opened_at)
                ]
            ]);
        }

        return "Unknown User Role";
    }

    private function getSuperAdminStats()
    {
        return [
            'total_branches' => Branch::count(),
            'total_inactive_branches' => Branch::inactive()->count(),

            // 'total_staff' => User::staff()->count(),
            // 'total_customers' => User::customers()->count(),
            'total_staff' => 1000,
            'toal_customers' => 1000,

            // 'total_products' => Product::count(),
            // 'total_product_categories' => ProductCategory::count(),
            // 'total_branches' => Branch::count(),
            'total_products' => 1000,
            'total_product_categories' => 1000,
            'total_product_branches' => 1000,

            'total_sales' => 4578,
            'gross_sales' => 1000000,
            'net_sales' => 850000,
            'cost_of_sales' => 150000,
            'gross_profit' => 500000,

            // 'todaySales' => Transaction::today()->sum('amount'),
        ];
    }

    public function getAdminStats()
    {
        return [
            'total_products' => 1000,
            'total_product_categories' => 1000,
            
            'total_sales' => 4578,
            'gross_sales' => 1000000,
            'net_sales' => 850000,
            'cost_of_sales' => 150000,
            'gross_profit' => 500000,
        ];
    }

    public function getCashierStats()
    {
        return [
            'total_sales_today' => 0,
            'total_transactions' => 0
        ];
    }

    private function formatDuration($opened_at)
    {
        $diff = now()->diff($opened_at);
    
        $hours = $diff->h + ($diff->days * 24);
        $minutes = $diff->i;
        
        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } elseif ($minutes > 0) {
            return "{$minutes}m";
        }
        
        return "Just now";
    }
}
