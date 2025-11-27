<?php

namespace Database\Factories;

use App\Models\User;
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
        $subtotal = fake()->randomFloat(2, 10, 1000);
        $taxAmount = $subtotal * 0.12; // 12% tax
        $shippingCost = fake()->randomFloat(2, 0, 50);
        $discountAmount = fake()->optional(0.3, 0)->randomFloat(2, 0, $subtotal * 0.2);
        $totalAmount = $subtotal + $taxAmount + $shippingCost - $discountAmount;

        return [
            'order_number' => 'WI-' . now()->format('Ymd') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'user_id' => User::factory(),
            'order_type' => 'walk_in',
            'order_status' => fake()->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
            'payment_status' => fake()->randomElement(['pending', 'paid', 'refunded']),
            'customer_name' => fake()->optional()->name(),
            'customer_email' => fake()->optional()->safeEmail(),
            'customer_phone' => fake()->optional()->phoneNumber(),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_cost' => $shippingCost,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'shipping_address_id' => null,
            'shipping_method' => null,
            'tracking_number' => null,
            'notes' => fake()->optional()->sentence(),
            'internal_notes' => fake()->optional()->sentence(),
            'ip_address' => fake()->optional()->ipv4(),
            'user_agent' => fake()->optional()->userAgent(),
            'confirmed_at' => null,
            'shipped_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
        ];
    }

    /**
     * Indicate that the order is a walk-in order.
     */
    public function walkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_type' => 'walk_in',
            'order_number' => 'WI-' . now()->format('Ymd') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Indicate that the order is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'confirmed_at' => now()->subDays(rand(1, 30)),
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_status' => 'cancelled',
            'cancelled_at' => now()->subDays(rand(1, 30)),
        ]);
    }
}
