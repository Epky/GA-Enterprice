<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $variantTypes = ['color', 'size', 'shade', 'scent', 'volume'];
        $variantType = fake()->randomElement($variantTypes);
        
        $variantValue = $this->getVariantValue($variantType);
        
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('??###??-VAR')),
            'name' => $variantValue,
            'variant_type' => $variantType,
            'variant_value' => $variantValue,
            'price_adjustment' => fake()->randomFloat(2, -10, 20),
            'image_url' => fake()->optional()->imageUrl(400, 400, 'beauty'),
            'is_active' => fake()->boolean(90), // 90% chance of being active
        ];
    }

    /**
     * Get a variant value based on the type.
     */
    private function getVariantValue(string $type): string
    {
        return match ($type) {
            'color' => fake()->randomElement([
                'Red', 'Blue', 'Green', 'Black', 'White', 'Pink', 'Purple',
                'Orange', 'Yellow', 'Brown', 'Gray', 'Navy', 'Burgundy'
            ]),
            'size' => fake()->randomElement([
                'XS', 'S', 'M', 'L', 'XL', 'XXL', 'Mini', 'Travel Size', 'Full Size'
            ]),
            'shade' => fake()->randomElement([
                'Fair', 'Light', 'Medium', 'Tan', 'Deep', 'Dark',
                'Ivory', 'Beige', 'Honey', 'Caramel', 'Espresso',
                'Porcelain', 'Sand', 'Golden', 'Warm', 'Cool'
            ]),
            'scent' => fake()->randomElement([
                'Vanilla', 'Lavender', 'Rose', 'Citrus', 'Coconut',
                'Jasmine', 'Mint', 'Eucalyptus', 'Unscented', 'Fresh'
            ]),
            'volume' => fake()->randomElement([
                '15ml', '30ml', '50ml', '100ml', '150ml', '200ml',
                '0.5oz', '1oz', '1.7oz', '3.4oz', '6.8oz'
            ]),
            default => fake()->word(),
        };
    }

    /**
     * Create a color variant.
     */
    public function color(): static
    {
        $colors = [
            'Red', 'Blue', 'Green', 'Black', 'White', 'Pink', 'Purple',
            'Orange', 'Yellow', 'Brown', 'Gray', 'Navy', 'Burgundy',
            'Coral', 'Teal', 'Magenta', 'Lime', 'Maroon', 'Olive'
        ];

        $color = fake()->randomElement($colors);

        return $this->state(fn (array $attributes) => [
            'variant_type' => 'color',
            'variant_value' => $color,
            'name' => $color,
        ]);
    }

    /**
     * Create a size variant.
     */
    public function size(): static
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Mini', 'Travel Size', 'Full Size'];
        $size = fake()->randomElement($sizes);

        return $this->state(fn (array $attributes) => [
            'variant_type' => 'size',
            'variant_value' => $size,
            'name' => $size,
        ]);
    }

    /**
     * Create a shade variant (for makeup products).
     */
    public function shade(): static
    {
        $shades = [
            'Fair', 'Light', 'Medium', 'Tan', 'Deep', 'Dark',
            'Ivory', 'Beige', 'Honey', 'Caramel', 'Espresso',
            'Porcelain', 'Sand', 'Golden', 'Warm Undertone', 'Cool Undertone'
        ];

        $shade = fake()->randomElement($shades);

        return $this->state(fn (array $attributes) => [
            'variant_type' => 'shade',
            'variant_value' => $shade,
            'name' => $shade,
        ]);
    }

    /**
     * Indicate that the variant is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the variant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a variant with no price adjustment.
     */
    public function noPriceAdjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'price_adjustment' => 0,
        ]);
    }

    /**
     * Create a variant with a premium price.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'price_adjustment' => fake()->randomFloat(2, 5, 25),
        ]);
    }
}