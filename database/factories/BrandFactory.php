<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brand>
 */
class BrandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional()->paragraph(),
            'logo_url' => fake()->optional()->imageUrl(200, 200, 'business'),
            'website_url' => fake()->optional()->url(),
            'is_active' => fake()->boolean(95), // 95% chance of being active
        ];
    }

    /**
     * Indicate that the brand is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the brand is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a beauty-specific brand.
     */
    public function beauty(): static
    {
        $beautyBrands = [
            'L\'Oréal', 'Maybelline', 'Revlon', 'MAC', 'Clinique',
            'Estée Lauder', 'Lancôme', 'Shiseido', 'Neutrogena', 'Olay',
            'Dove', 'Nivea', 'Garnier', 'The Body Shop', 'Sephora',
            'Urban Decay', 'Too Faced', 'Benefit', 'Tarte', 'NARS',
            'Fenty Beauty', 'Rare Beauty', 'Glossier', 'Charlotte Tilbury'
        ];

        $name = fake()->randomElement($beautyBrands);
        
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
            'website_url' => 'https://www.' . Str::slug($name) . '.com',
        ]);
    }
}