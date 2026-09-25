<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Modules\Support\Concerns\HasUuid;
use Modules\Support\Concerns\HasSlug;
use Modules\Product\Enums\InventoryMovementTypes;
use Modules\Product\Exceptions\InsufficientStockException;
use Modules\Product\Exceptions\StockTrackingDisabledException;

class Product extends Model
{
    use HasUuid, HasSlug;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'track_inventory' => 'boolean',
        'current_stock' => 'decimal:2',
        'low_stock_threshold' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    protected $appends = [
        'category_name',
        'thumbnail_url',
    ];

    protected static function booted()
    {
        static::updating(function ($product) {
            if ($product->isDirty('name')) {
                $old_name = $product->getOriginal('name');
                $new_name = $product->name;
                
                // Update slug
                $product->slug = Str::slug($new_name);
                
                // Get images directly from database
                $images = $product->images()->get();
                
                $new_slug = Str::slug($new_name);
                
                // Rename all images
                foreach ($images as $image) {
                    $old_filename = $image->name;
                    
                    // Extract the current slug from the filename (first part before first underscore)
                    $parts = explode('_', $old_filename);
                    $old_slug = $parts[0];
                    
                    // Replace old slug with new slug
                    $new_filename = str_replace($old_slug, $new_slug, $old_filename);
                    
                    if ($old_filename !== $new_filename) {
                        $old_path = 'products/' . $old_filename;
                        $new_path = 'products/' . $new_filename;
                        
                        // Rename the actual file
                        if (Storage::disk('public')->exists($old_path)) {
                            Storage::disk('public')->move($old_path, $new_path);
                        }
                        
                        // Update database record
                        $image->name = $new_filename;
                        $image->save();
                    }
                }
            }
        });

        static::deleting(function ($product) {
            $images = $product->images()->get();
            
            foreach ($images as $image) {
                $path = "products/{$image->name}";
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                $image->delete();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function getCategoryNameAttribute(): string
    {
        return $this->category?->name ?? 'Uncategorized';
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order');
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->images->first()?->image_url ?? asset('assets/images/general/default-image.webp');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'product_id');
    }

    public function scopeSearch(Builder $query, $search): Builder
    {
        if (!$search) {
            return $query;
        }

        $term = trim($search);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
            ->orWhere('sku', 'like', "%{$term}%")
            ->orWhere('barcode', 'like', "{$term}%");
        });
    }

    public function tracksInventory(): bool
    {
        return (bool) $this->track_inventory;
    }

    public function hasStockFor(int|float $quantity): bool
    {
        if (!$this->tracksInventory()) {
            return true;
        }

        return (float) $this->current_stock >= (float) $quantity;
    }

    public function decrementStock(float $quantity): bool
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        $affected = static::query()
            ->where('id', $this->id)
            ->where('current_stock', '>=', $quantity)
            ->decrement('current_stock', $quantity);

        return $affected > 0;
    }

    private function ensureInventoryIsTracked(): void
    {
        if (!$this->tracksInventory()) {
            throw StockTrackingDisabledException::forProduct($this);
        }
    }

    public function addStock(
        float $quantity, 
        InventoryMovementTypes $type, 
        ?string $notes = null, 
        ?array $metadata = null
    ): InventoryMovement
    {
        $this->ensureInventoryIsTracked();

        return $this->updateStock(
            newQuantity: (float) $this->current_stock + $quantity,
            type: $type,
            notes: $notes,
            metadata: $metadata,
        );
    }

    public function updateStock(float $newQuantity, InventoryMovementTypes $type, ?string $notes = null, ?array $metadata = null): InventoryMovement
    {
        $this->ensureInventoryIsTracked();
        
        $oldQuantity = (float) $this->current_stock;
        $quantityChange = $newQuantity - $oldQuantity;
        
        return DB::transaction(function () use ($newQuantity, $oldQuantity, $quantityChange, $type, $notes, $metadata) {
            $movement = InventoryMovement::create([
                'product_id' => $this->id,
                'type' => $type,
                'quantity' => $quantityChange,
                'quantity_before' => $oldQuantity,
                'quantity_after' => $newQuantity,
                'notes' => $notes,
                'metadata' => $metadata,
            ]);

            $this->current_stock = $newQuantity;
            $this->save();

            return $movement;
        });
    }

    public function removeStock(float $quantity, InventoryMovementTypes $type, ?string $notes = null, ?array $metadata = null): InventoryMovement
    {
        $this->ensureInventoryIsTracked();

        if (!$this->hasStockFor($quantity)) {
            throw new InsufficientStockException(
                "Only {$this->current_stock} units available for '{$this->name}'"
            );
        }

        return $this->updateStock(
            newQuantity: (float) $this->current_stock - $quantity,
            type: $type,
            notes: $notes,
            metadata: $metadata,
        );
    }
}