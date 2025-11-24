<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductSpecification>
 */
class ProductSpecificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $specKeys = [
            'ingredients', 'skin_type', 'hair_type', 'coverage', 'finish',
            'spf', 'volume', 'weight', 'dimensions', 'color_family',
            'cruelty_free', 'vegan', 'organic', 'paraben_free', 'sulfate_free',
            'fragrance_free', 'hypoallergenic', 'dermatologist_tested',
            'application_method', 'recommended_use', 'shelf_life'
        ];

        $specKey = fake()->randomElement($specKeys);
        $specValue = $this->getSpecValue($specKey);

        return [
            'product_id' => Product::factory(),
            'spec_key' => $specKey,
            'spec_value' => $specValue,
            'display_order' => fake()->numberBetween(0, 20),
        ];
    }

    /**
     * Get a spec value based on the key.
     */
    private function getSpecValue(string $key): string
    {
        return match ($key) {
            'ingredients' => fake()->words(8, true),
            'skin_type' => fake()->randomElement(['All Skin Types', 'Dry', 'Oily', 'Combination', 'Sensitive', 'Normal']),
            'hair_type' => fake()->randomElement(['All Hair Types', 'Dry', 'Oily', 'Normal', 'Curly', 'Straight', 'Wavy']),
            'coverage' => fake()->randomElement(['Light', 'Medium', 'Full', 'Buildable']),
            'finish' => fake()->randomElement(['Matte', 'Satin', 'Dewy', 'Natural', 'Glossy', 'Shimmer']),
            'spf' => fake()->randomElement(['SPF 15', 'SPF 30', 'SPF 50', 'No SPF']),
            'volume' => fake()->randomElement(['15ml', '30ml', '50ml', '100ml', '150ml', '200ml']),
            'weight' => fake()->randomElement(['10g', '25g', '50g', '100g', '150g']),
            'dimensions' => fake()->randomElement(['5x5x10cm', '8x3x15cm', '10x10x5cm']),
            'color_family' => fake()->randomElement(['Warm', 'Cool', 'Neutral', 'Bold', 'Natural']),
            'cruelty_free' => fake()->randomElement(['Yes', 'No']),
            'vegan' => fake()->randomElement(['Yes', 'No']),
            'organic' => fake()->randomElement(['Yes', 'No', 'Partially']),
            'paraben_free' => fake()->randomElement(['Yes', 'No']),
            'sulfate_free' => fake()->randomElement(['Yes', 'No']),
            'fragrance_free' => fake()->randomElement(['Yes', 'No']),
            'hypoallergenic' => fake()->randomElement(['Yes', 'No']),
            'dermatologist_tested' => fake()->randomElement(['Yes', 'No']),
            'application_method' => fake()->randomElement(['Apply with fingers', 'Use brush', 'Use sponge', 'Spray evenly']),
            'recommended_use' => fake()->randomElement(['Daily', 'As needed', 'Morning only', 'Evening only', '2-3 times per week']),
            'shelf_life' => fake()->randomElement(['12 months', '18 months', '24 months', '36 months']),
            default => fake()->sentence(),
        };
    }

    /**
     * Create a beauty-specific specification.
     */
    public function beauty(): static
    {
        $beautySpecs = [
            'skin_type' => ['All Skin Types', 'Dry', 'Oily', 'Combination', 'Sensitive'],
            'coverage' => ['Light', 'Medium', 'Full', 'Buildable'],
            'finish' => ['Matte', 'Satin', 'Dewy', 'Natural'],
            'spf' => ['SPF 15', 'SPF 30', 'SPF 50'],
            'cruelty_free' => ['Yes'],
            'paraben_free' => ['Yes'],
        ];

        $specKey = fake()->randomElement(array_keys($beautySpecs));
        $specValue = fake()->randomElement($beautySpecs[$specKey]);

        return $this->state(fn (array $attributes) => [
            'spec_key' => $specKey,
            'spec_value' => $specValue,
        ]);
    }

    /**
     * Create an ingredients specification.
     */
    public function ingredients(): static
    {
        $ingredients = [
            'Water, Glycerin, Hyaluronic Acid, Vitamin E',
            'Aqua, Dimethicone, Titanium Dioxide, Iron Oxides',
            'Aloe Vera, Jojoba Oil, Shea Butter, Vitamin C',
            'Retinol, Peptides, Niacinamide, Ceramides',
            'Salicylic Acid, Tea Tree Oil, Zinc Oxide'
        ];

        return $this->state(fn (array $attributes) => [
            'spec_key' => 'ingredients',
            'spec_value' => fake()->randomElement($ingredients),
        ]);
    }

    /**
     * Create a boolean specification.
     */
    public function boolean(): static
    {
        $booleanSpecs = [
            'cruelty_free', 'vegan', 'organic', 'paraben_free',
            'sulfate_free', 'fragrance_free', 'hypoallergenic'
        ];

        $specKey = fake()->randomElement($booleanSpecs);

        return $this->state(fn (array $attributes) => [
            'spec_key' => $specKey,
            'spec_value' => fake()->randomElement(['Yes', 'No']),
        ]);
    }

    /**
     * Create a key specification (commonly displayed).
     */
    public function keySpec(): static
    {
        $keySpecs = [
            'skin_type' => ['All Skin Types', 'Dry', 'Oily', 'Combination', 'Sensitive'],
            'coverage' => ['Light', 'Medium', 'Full'],
            'finish' => ['Matte', 'Satin', 'Dewy'],
            'spf' => ['SPF 15', 'SPF 30', 'SPF 50'],
            'cruelty_free' => ['Yes'],
        ];

        $specKey = fake()->randomElement(array_keys($keySpecs));
        $specValue = fake()->randomElement($keySpecs[$specKey]);

        return $this->state(fn (array $attributes) => [
            'spec_key' => $specKey,
            'spec_value' => $specValue,
            'display_order' => fake()->numberBetween(0, 5), // Higher priority for key specs
        ]);
    }
}