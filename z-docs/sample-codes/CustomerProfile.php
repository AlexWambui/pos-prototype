<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Concerns\HasUserCodeGeneration;
use App\Concerns\Users\HasCreatorAuditTrail;

class CustomerProfile extends Model
{
    use HasUserCodeGeneration, HasCreatorAuditTrail;
    
    protected $codeColumn = 'customer_code';
    protected $codePrefix = 'CUST';

    protected $guarded = [];

    protected $casts = [
        'loyalty_points' => 'integer',
        'credit_limit' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
