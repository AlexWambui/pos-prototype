<?php

namespace Modules\StoreBranch\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
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
            'code' => $this->code,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'city' => $this->city,
            'address' => $this->address,
            'is_active' => (bool) $this->is_active,
        ];
    }
}