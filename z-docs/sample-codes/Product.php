<?php

namespace App\Models\Products;

use Illuminate\Database\Eloquent\Model;
use Modules\Support\Concerns\HasUuid;
use Modules\Support\Concerns\HasSlug;
use Illuminate\Support\Facades\DB;
use App\Enums\WeightUnits;

class Product extends Model
{
    use HasUuid, HasSlug;

    protected $guarded = [];

    protected $casts = [
        'buying_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
        'current_stock' => 'integer',
        'weight_value' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->sort_order)) {
                $model->sort_order = static::max('sort_order') + 1;
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('current_stock', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('current_stock', '<=', 0);
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('sku', 'LIKE', "%{$searchTerm}%")
              ->orWhere('barcode', $searchTerm);
        });
    }

    public function calculateProfitMargin(): ?float
    {
        if (!$this->buying_price) {
            return null;
        }

        if ($this->buying_price == 0) {
            return 100; // Infinite profit if buying price is 0
        }

        $profit = $this->selling_price - $this->buying_price;
        return round(($profit / $this->buying_price) * 100, 2);
    }

    public function calculateProfitPerUnit(): ?float
    {
        if (!$this->buying_price) {
            return null;
        }

        return round($this->selling_price - $this->buying_price, 2);
    }

    public function getStockValue(): ?float
    {
        if (!$this->buying_price) {
            return null;
        }

        return round($this->current_stock * $this->buying_price, 2);
    }

    public function updateStock(int $quantity): bool
    {
        if ($this->current_stock + $quantity < 0) {
            return false; // Prevent negative stock if not allowed
        }

        $this->current_stock += $quantity;
        return $this->save();
    }

    public function isInStock(): bool
    {
        return $this->current_stock > 0;
    }

    public static function generateBarcode(): string
    {
        do {
            $barcode = 'PROD' . str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        } while (self::where('barcode', $barcode)->exists());

        return $barcode;
    }

    // Bulk operations
    public static function updateStockBulk(array $productUpdates): void
    {
        foreach ($productUpdates as $update) {
            DB::table('products')
                ->where('id', $update['product_id'])
                ->update([
                    'current_stock' => DB::raw("current_stock + {$update['quantity']}"),
                    'updated_at' => now(),
                ]);
        }
    }

    public function getWeightUnitLabelAttribute(): ?string
    {
        if (!$this->weight_unit) {
            return null;
        }
        
        return WeightUnits::tryFrom($this->weight_unit)?->label() ?? $this->weight_unit;
    }
}
