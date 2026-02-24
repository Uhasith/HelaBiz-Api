<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 500);
        $tax = $subtotal * 0.10;
        $discount = fake()->randomFloat(2, 0, 50);
        $total = $subtotal + $tax - $discount;

        return [
            'order_number' => fake()->unique()->bothify('ORD-######'),
            'order_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'cancelled']),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'warranty_period' => fake()->numberBetween(1, 12),
            'warranty_unit' => fake()->randomElement(['days', 'weeks', 'months', 'years']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
