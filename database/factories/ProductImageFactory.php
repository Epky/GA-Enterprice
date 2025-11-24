<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Use picsum.photos for reliable placeholder images
        $width = 800;
        $height = 800;
        $imageId = fake()->numberBetween(1, 1000);
        
        return [
            'product_id' => Product::factory(),
            'image_url' => "https://picsum.photos/id/{$imageId}/{$width}/{$height}",
            'alt_text' => fake()->optional()->sentence(),
            'display_order' => fake()->numberBetween(0, 10),
            'is_primary' => false,
        ];
    }

    /**
     * Indicate that this is the primary image.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
            'display_order' => 0,
        ]);
    }

    /**
     * Create a beauty-specific product image.
     */
    public function beauty(): static
    {
        $imageId = fake()->numberBetween(1, 1000);
        
        return $this->state(fn (array $attributes) => [
            'image_url' => "https://picsum.photos/id/{$imageId}/800/800",
            'alt_text' => 'Beauty product image',
        ]);
    }

    /**
     * Create a thumbnail image.
     */
    public function thumbnail(): static
    {
        $imageId = fake()->numberBetween(1, 1000);
        
        return $this->state(fn (array $attributes) => [
            'image_url' => "https://picsum.photos/id/{$imageId}/300/300",
        ]);
    }

    /**
     * Create a high-resolution image.
     */
    public function highRes(): static
    {
        $imageId = fake()->numberBetween(1, 1000);
        
        return $this->state(fn (array $attributes) => [
            'image_url' => "https://picsum.photos/id/{$imageId}/1200/1200",
        ]);
    }
}