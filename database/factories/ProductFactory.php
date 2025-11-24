<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $name = fake()->words(3, true);
        $basePrice = fake()->randomFloat(2, 10, 500);
        $salePrice = fake()->optional(0.3)->randomFloat(2, $basePrice * 0.7, $basePrice * 0.95);
        
        return [
            'sku' => strtoupper(fake()->unique()->bothify('??###??')),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->optional()->paragraph(),
            'short_description' => fake()->optional()->sentence(),
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'base_price' => $basePrice,
            'sale_price' => $salePrice,
            'cost_price' => fake()->optional()->randomFloat(2, $basePrice * 0.3, $basePrice * 0.7),
            'is_featured' => fake()->boolean(20), // 20% chance of being featured
            'is_new_arrival' => fake()->boolean(15), // 15% chance of being new arrival
            'is_best_seller' => fake()->boolean(10), // 10% chance of being best seller
            'status' => fake()->randomElement(['active', 'inactive', 'discontinued', 'out_of_stock']),
            'average_rating' => fake()->randomFloat(2, 0, 5),
            'review_count' => fake()->numberBetween(0, 100),
            'meta_title' => fake()->optional()->sentence(),
            'meta_description' => fake()->optional()->text(160),
            'meta_keywords' => fake()->optional()->words(5, true),
        ];
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the product is a new arrival.
     */
    public function newArrival(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_new_arrival' => true,
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the product is a best seller.
     */
    public function bestSeller(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_best_seller' => true,
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the product is on sale.
     */
    public function onSale(): static
    {
        return $this->state(function (array $attributes) {
            $basePrice = $attributes['base_price'] ?? fake()->randomFloat(2, 10, 500);
            return [
                'base_price' => $basePrice,
                'sale_price' => fake()->randomFloat(2, $basePrice * 0.6, $basePrice * 0.9),
                'status' => 'active',
            ];
        });
    }

    /**
     * Create a beauty-specific product.
     */
    public function beauty(): static
    {
        $beautyProducts = [
            'Hydrating Face Serum', 'Matte Foundation', 'Volumizing Mascara',
            'Moisturizing Cream', 'Lip Gloss', 'Eye Shadow Palette',
            'Cleansing Oil', 'Sunscreen SPF 50', 'Anti-Aging Cream',
            'Liquid Lipstick', 'Concealer', 'Blush Palette',
            'Face Primer', 'Setting Spray', 'Eyebrow Pencil',
            'Micellar Water', 'Night Cream', 'Vitamin C Serum',
            'Highlighter', 'Bronzer', 'Lip Balm'
        ];

        $name = fake()->randomElement($beautyProducts);
        
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
            'category_id' => Category::factory()->beauty(),
            'brand_id' => Brand::factory()->beauty(),
        ]);
    }

    /**
     * Create a product with high ratings.
     */
    public function highRated(): static
    {
        return $this->state(fn (array $attributes) => [
            'average_rating' => fake()->randomFloat(2, 4.0, 5.0),
            'review_count' => fake()->numberBetween(50, 200),
        ]);
    }

    /**
     * Create a product with no ratings.
     */
    public function unrated(): static
    {
        return $this->state(fn (array $attributes) => [
            'average_rating' => 0,
            'review_count' => 0,
        ]);
    }
}