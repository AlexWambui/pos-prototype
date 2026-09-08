<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Branch;
use App\Concerns\HasUserCodeGeneration;
use App\Concerns\Users\HasCreatorAuditTrail;

class StaffProfile extends Model
{
    use HasUserCodeGeneration, HasCreatorAuditTrail;

    protected $codeColumn = 'staff_code';
    protected $codePrefix = 'STF';

    protected $guarded = [];

    protected $casts = [
        'salary' => 'decimal:2',
        'hired_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
