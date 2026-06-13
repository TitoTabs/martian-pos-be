<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'image' => $this->image,
            'price' => (float) $this->price,
            'is_active' => $this->is_active,
            'addons' => AddonResource::collection($this->whenLoaded('addons')),
            'ingredients' => $this->whenLoaded('ingredients', fn () => $this->ingredients->map(fn ($item) => [
                'inventory_item_id' => $item->id,
                'name' => $item->name,
                'unit' => $item->unit,
                'quantity' => (float) $item->pivot->quantity,
            ])),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
