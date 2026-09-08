<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Support\Concerns\HasUuid;
use App\Concerns\Users\HasCreatorAuditTrail;
use App\Models\Users\StaffProfile;

class Branch extends Model
{
    use HasUuid, HasCreatorAuditTrail;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function staffProfiles()
    {
        return $this->hasMany(StaffProfile::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%");
        }
        
        return $query;
    }
}
