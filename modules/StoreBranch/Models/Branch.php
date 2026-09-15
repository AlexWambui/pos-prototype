<?php

namespace Modules\StoreBranch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Modules\Support\Concerns\HasUuid;
use Modules\Support\Concerns\HasSlug;
use Modules\Support\Concerns\HasCreatorAuditTrail;

class Branch extends Model
{
    use HasUuid, HasSlug, HasCreatorAuditTrail;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        $searchTerm = strtolower($search);
        
        return $query->where(function (Builder $q) use ($searchTerm) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"])
                ->orWhereRaw('LOWER(code) LIKE ?', ["%{$searchTerm}%"])
                ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchTerm}%"])
                ->orWhereRaw('LOWER(phone_number) LIKE ?', ["%{$searchTerm}%"])
                ->orWhereRaw('LOWER(address) LIKE ?', ["%{$searchTerm}%"])
                ->orWhereRaw('LOWER(city) LIKE ?', ["%{$searchTerm}%"]);
        });
    }
}
