<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer?->name,
            'customer_phone' => $this->customer?->phone,
            'customer_email' => $this->customer?->email,
            'address' => $this->customer?->address,
            'order_date' => $this->order_date?->format('Y-m-d'),
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'total' => $this->total,
            'warranty_period' => $this->warranty_period,
            'warranty_unit' => $this->warranty_unit,
            'notes' => $this->notes,
            'items_count' => $this->when(
                ! $this->relationLoaded('items'),
                fn () => $this->items()->count()
            ),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'phone' => $this->customer->phone,
                    'email' => $this->customer->email,
                    'address' => $this->customer->address,
                    'orders_count' => $this->when(
                        $request->routeIs('orders.show'),
                        fn () => $this->customer->orders()->count()
                    ),
                    'total_spent' => $this->when(
                        $request->routeIs('orders.show'),
                        fn () => $this->customer->orders()->sum('total')
                    ),
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
