<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsPageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'price' => $this->price,
            'cost_price' => $this->cost_price,
            'has_discount' => $this->has_discount,
            'discount_display' => $this->discount_display,
            'discounted_price' => $this->discounted_price,
            'thumbnail_url' => $this->thumbnail_url,
            'category_name' => $this->category_name,
            'current_stock' => $this->current_stock,
            'track_inventory' => (bool) $this->track_inventory,
            'low_stock_threshold' => $this->low_stock_threshold,
        ];
    }
}
