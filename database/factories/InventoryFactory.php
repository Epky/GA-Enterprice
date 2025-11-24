<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantityAvailable = fake()->numberBetween(0, 500);
        $reorderLevel = fake()->numberBetween(5, 50);
        
        return [
            'product_id' => Product::factory(),
            'variant_id' => null,
            'location' => fake()->randomElement(['main_warehouse', 'store_front', 'storage_room', 'online_only']),
            'quantity_available' => $quantityAvailable,
            'quantity_reserved' => fake()->numberBetween(0, min(10, $quantityAvailable)),
            'quantity_sold' => fake()->numberBetween(0, 1000),
            'reorder_level' => $reorderLevel,
            'reorder_quantity' => fake()->numberBetween($reorderLevel * 2, $reorderLevel * 10),
            'last_restocked_at' => fake()->optional()->dateTimeBetween('-3 months', 'now'),
        ];
    }

    /**
     * Create inventory for a specific variant.
     */
    public function forVariant(): static
    {
        return $this->state(fn (array $attributes) => [
            'variant_id' => ProductVariant::factory(),
        ]);
    }

    /**
     * Create inventory with low stock.
     */
    public function lowStock(): static
    {
        return $this->state(function (array $attributes) {
            $reorderLevel = fake()->numberBetween(10, 30);
            return [
                'quantity_available' => fake()->numberBetween(0, $reorderLevel),
                'reorder_level' => $reorderLevel,
                'reorder_quantity' => fake()->numberBetween($reorderLevel * 2, $reorderLevel * 5),
            ];
        });
    }

    /**
     * Create inventory that's out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_available' => 0,
            'quantity_reserved' => 0,
        ]);
    }

    /**
     * Create inventory with high stock.
     */
    public function highStock(): static
    {
        return $this->state(function (array $attributes) {
            $reorderLevel = fake()->numberBetween(10, 30);
            return [
                'quantity_available' => fake()->numberBetween($reorderLevel * 3, 1000),
                'reorder_level' => $reorderLevel,
                'quantity_reserved' => fake()->numberBetween(0, 20),
            ];
        });
    }

    /**
     * Create inventory for main warehouse.
     */
    public function mainWarehouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => 'main_warehouse',
        ]);
    }

    /**
     * Create inventory for store front.
     */
    public function storeFront(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => 'store_front',
            'quantity_available' => fake()->numberBetween(0, 50), // Smaller quantities for store front
        ]);
    }

    /**
     * Create recently restocked inventory.
     */
    public function recentlyRestocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_restocked_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'quantity_available' => fake()->numberBetween(50, 500),
        ]);
    }

    /**
     * Create inventory that needs restocking.
     */
    public function needsRestocking(): static
    {
        return $this->state(function (array $attributes) {
            $reorderLevel = fake()->numberBetween(10, 30);
            return [
                'quantity_available' => fake()->numberBetween(0, $reorderLevel - 1),
                'reorder_level' => $reorderLevel,
                'last_restocked_at' => fake()->dateTimeBetween('-3 months', '-1 month'),
            ];
        });
    }

    /**
     * Create inventory with reserved quantities.
     */
    public function withReserved(): static
    {
        return $this->state(function (array $attributes) {
            $available = $attributes['quantity_available'] ?? fake()->numberBetween(20, 200);
            return [
                'quantity_available' => $available,
                'quantity_reserved' => fake()->numberBetween(1, min(20, $available)),
            ];
        });
    }
}