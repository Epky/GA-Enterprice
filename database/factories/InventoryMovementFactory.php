<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $movementType = fake()->randomElement(['purchase', 'sale', 'return', 'adjustment', 'damage', 'transfer']);
        $quantity = $this->getQuantityForMovementType($movementType);
        
        return [
            'product_id' => Product::factory(),
            'variant_id' => null,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'location_from' => $movementType === 'transfer' ? fake()->randomElement(['main_warehouse', 'store_front', 'storage_room']) : null,
            'location_to' => fake()->randomElement(['main_warehouse', 'store_front', 'storage_room', 'online_only']),
            'reference_type' => fake()->optional()->randomElement(['order', 'purchase_order', 'return']),
            'reference_id' => fake()->optional()->numberBetween(1, 1000),
            'notes' => fake()->optional()->sentence(),
            'performed_by' => User::factory(),
            'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }

    /**
     * Get appropriate quantity based on movement type.
     */
    private function getQuantityForMovementType(string $movementType): int
    {
        $baseQuantity = fake()->numberBetween(1, 100);
        
        return match ($movementType) {
            'sale', 'damage' => -$baseQuantity,
            'purchase', 'return' => $baseQuantity,
            'adjustment' => fake()->randomElement([-$baseQuantity, $baseQuantity]),
            'transfer' => $baseQuantity,
            default => $baseQuantity,
        };
    }

    /**
     * Create movement for a specific variant.
     */
    public function forVariant(): static
    {
        return $this->state(fn (array $attributes) => [
            'variant_id' => ProductVariant::factory(),
        ]);
    }

    /**
     * Create inbound movement (purchase, return, positive adjustment).
     */
    public function inbound(): static
    {
        return $this->state(function (array $attributes) {
            $movementType = fake()->randomElement(['purchase', 'return', 'adjustment']);
            return [
                'movement_type' => $movementType,
                'quantity' => fake()->numberBetween(1, 100),
            ];
        });
    }

    /**
     * Create outbound movement (sale, damage, negative adjustment).
     */
    public function outbound(): static
    {
        return $this->state(function (array $attributes) {
            $movementType = fake()->randomElement(['sale', 'damage', 'adjustment']);
            $quantity = fake()->numberBetween(1, 100);
            return [
                'movement_type' => $movementType,
                'quantity' => $movementType === 'adjustment' ? -$quantity : -$quantity,
            ];
        });
    }

    /**
     * Create purchase movement.
     */
    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'purchase',
            'quantity' => fake()->numberBetween(10, 500),
            'reference_type' => 'purchase_order',
            'reference_id' => fake()->numberBetween(1000, 9999),
            'notes' => 'Stock purchase from supplier',
        ]);
    }

    /**
     * Create sale movement.
     */
    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'sale',
            'quantity' => -fake()->numberBetween(1, 20),
            'reference_type' => 'order',
            'reference_id' => fake()->numberBetween(1, 1000),
            'notes' => 'Product sold to customer',
        ]);
    }

    /**
     * Create adjustment movement.
     */
    public function adjustment(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = fake()->numberBetween(1, 50);
            $isPositive = fake()->boolean();
            
            return [
                'movement_type' => 'adjustment',
                'quantity' => $isPositive ? $quantity : -$quantity,
                'notes' => $isPositive ? 'Stock count adjustment - increase' : 'Stock count adjustment - decrease',
            ];
        });
    }

    /**
     * Create transfer movement.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'transfer',
            'quantity' => fake()->numberBetween(1, 50),
            'location_from' => 'main_warehouse',
            'location_to' => 'store_front',
            'notes' => 'Stock transfer between locations',
        ]);
    }

    /**
     * Create return movement.
     */
    public function return(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'return',
            'quantity' => fake()->numberBetween(1, 10),
            'reference_type' => 'return',
            'reference_id' => fake()->numberBetween(1, 500),
            'notes' => 'Customer return processed',
        ]);
    }

    /**
     * Create damage/loss movement.
     */
    public function damage(): static
    {
        return $this->state(fn (array $attributes) => [
            'movement_type' => 'damage',
            'quantity' => -fake()->numberBetween(1, 20),
            'notes' => fake()->randomElement([
                'Product damaged during handling',
                'Expired product disposal',
                'Quality control rejection',
                'Accidental damage'
            ]),
        ]);
    }

    /**
     * Create recent movement (within last week).
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Create movement with reference.
     */
    public function withReference(string $type, int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'reference_type' => $type,
            'reference_id' => $id,
        ]);
    }

    /**
     * Create movement performed by specific user.
     */
    public function performedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'performed_by' => $user->id,
        ]);
    }
}