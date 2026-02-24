<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'barcode' => fake()->unique()->ean13(),
            'description' => fake()->sentence(),
            'cost_price' => fake()->randomFloat(2, 10, 100),
            'selling_price' => fake()->randomFloat(2, 50, 200),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'low_stock_alert' => fake()->numberBetween(5, 20),
        ];
    }
}
