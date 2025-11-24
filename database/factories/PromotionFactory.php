<?php

namespace Database\Factories;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        $promotionTypes = ['percentage', 'fixed_amount', 'bogo', 'free_shipping'];
        $applicableTo = ['all', 'category', 'product', 'brand'];
        
        $type = $this->faker->randomElement($promotionTypes);
        $applicable = $this->faker->randomElement($applicableTo);

        return [
            'name' => $this->faker->words(3, true) . ' Promotion',
            'description' => $this->faker->sentence(),
            'promotion_type' => $type,
            'discount_value' => $type === 'percentage' ? $this->faker->numberBetween(5, 50) : $this->faker->numberBetween(5, 100),
            'min_purchase_amount' => $this->faker->optional(0.3)->randomFloat(2, 20, 100),
            'max_discount_amount' => $this->faker->optional(0.3)->randomFloat(2, 10, 50),
            'applicable_to' => $applicable,
            'applicable_ids' => $applicable !== 'all' ? [$this->faker->numberBetween(1, 10)] : null,
            'start_date' => now(),
            'end_date' => now()->addDays($this->faker->numberBetween(7, 90)),
            'usage_limit' => $this->faker->optional(0.5)->numberBetween(10, 1000),
            'usage_count' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->subDays(30),
            'end_date' => now()->subDays(1),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(30),
        ]);
    }
}
