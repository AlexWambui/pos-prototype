<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Concerns\Users\HasCreatorAuditTrail;

class SupplierProfile extends Model
{
    use HasCreatorAuditTrail;
    
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
