<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
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
            'type_label' => $this->type->label(),
            'quantity' => $this->quantity,
            'formatted_quantity' => $this->quantity > 0 ? "+{$this->quantity}" : "{$this->quantity}",
            'quantity_before' => $this->quantity_before,
            'quantity_after' => $this->quantity_after,
            'notes' => $this->notes,
            'created_at' => $this->created_at->format('d-m-y H:i:s'),
            'user' => $this->user ? [
                'name' => $this->user->name
            ] : null,
        ];
    }
}
