<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Sales\Sale;
use App\Models\CashMovement;

class Shift extends Model
{
    protected $guarded = [];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'shift_id');
    }

    public function cashMovements()
    {
        return $this->hasMany(CashMovement::class);
    }

    // Helper Methods
    public function getTotalSalesAttribute()
    {
        return $this->sales()->sum('total_amount');
    }

    public function getTransactionsCountAttribute()
    {
        return $this->sales()->count();
    }

    public function getCashSalesAttribute()
    {
        return $this->sales()
            ->whereHas('payments', function($query) {
                $query->where('method', 'cash');
            })
            ->sum('total_amount');
    }

    public function getDurationAttribute()
    {
        $end = $this->closed_at ?? now();
        return $end->diffInHours($this->opened_at);
    }

    public function getFormattedDurationAttribute()
    {
        $hours = floor($this->duration);
        $minutes = floor(($this->duration - $hours) * 60);
        return "{$hours}h {$minutes}m";
    }

    public function isOpen()
    {
        return is_null($this->closed_at);
    }
}
