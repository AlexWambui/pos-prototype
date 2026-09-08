<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use App\Models\Users\Shift;
use App\Models\Payments\Payment;

class Sale extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
