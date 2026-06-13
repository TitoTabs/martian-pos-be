<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
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
            'subtotal' => (float) $this->subtotal,
            'total' => (float) $this->total,
            'payment_method' => $this->payment_method,
            'customer_name' => $this->customer_name,
            'order_type' => $this->order_type,
            'notes' => $this->notes,
            'status' => $this->status,
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
