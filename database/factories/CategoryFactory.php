<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);
        
        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'parent_id' => null,
            'image_url' => fake()->optional()->imageUrl(400, 300, 'beauty'),
            'display_order' => fake()->numberBetween(0, 100),
            'is_active' => fake()->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Indicate that the category is a root category.
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
        ]);
    }

    /**
     * Indicate that the category is a child category.
     */
    public function child(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => Category::factory(),
        ]);
    }

    /**
     * Indicate that the category is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a beauty-specific category.
     */
    public function beauty(): static
    {
        $beautyCategories = [
            'Skincare', 'Makeup', 'Haircare', 'Fragrance', 'Body Care',
            'Face Cleansers', 'Moisturizers', 'Serums', 'Sunscreen',
            'Foundation', 'Concealer', 'Lipstick', 'Eyeshadow', 'Mascara',
            'Shampoo', 'Conditioner', 'Hair Styling', 'Hair Treatment',
            'Perfume', 'Body Lotion', 'Body Wash', 'Deodorant'
        ];

        $name = fake()->randomElement($beautyCategories);
        
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }
}