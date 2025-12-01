<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: landing-page-product-display, Property 8: Filter application correctness
 * Validates: Requirements 3.2, 3.3, 3.4
 */
class FilterApplicationCorrectnessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 8: Filter application correctness
     * For any combination of category, brand, and price range filters, 
     * all products returned should match all applied filter criteria
     * 
     * @test
     */
    public function property_all_filters_applied_correctly()
    {
        // Run the test multiple times with different random data (100 iterations)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create multiple categories and brands
            $categories = Category::factory()->count(3)->create(['is_active' => true]);
            $brands = Brand::factory()->count(3)->create(['is_active' => true]);
            
            // Randomly select filter criteria
            $applyCategory = (bool) rand(0, 1);
            $applyBrand = (bool) rand(0, 1);
            $applyPriceRange = (bool) rand(0, 1);
            
            $selectedCategory = $applyCategory ? $categories->random() : null;
            $selectedBrand = $applyBrand ? $brands->random() : null;
            $minPrice = $applyPriceRange ? rand(100, 500) : null;
            $maxPrice = $applyPriceRange ? rand(600, 1000) : null;
            
            // Create products that match the filters
            $matchingCount = rand(2, 5);
            $matchingProducts = [];
            
            for ($i = 0; $i < $matchingCount; $i++) {
                $product = Product::factory()->create([
                    'category_id' => $applyCategory ? $selectedCategory->id : $categories->random()->id,
                    'brand_id' => $applyBrand ? $selectedBrand->id : $brands->random()->id,
                    'base_price' => $applyPriceRange ? rand($minPrice, $maxPrice) : rand(100, 1000),
                    'status' => 'active',
                ]);
                
                $matchingProducts[] = $product;
            }
            
            // Create products that don't match at least one filter
            $nonMatchingCount = rand(2, 5);
            $nonMatchingProducts = [];
            
            for ($i = 0; $i < $nonMatchingCount; $i++) {
                // Intentionally violate at least one filter
                $violationType = rand(1, 3);
                
                if ($violationType === 1 && $applyCategory) {
                    // Wrong category
                    $wrongCategory = $categories->where('id', '!=', $selectedCategory->id)->first();
                    $product = Product::factory()->create([
                        'category_id' => $wrongCategory->id,
                        'brand_id' => $applyBrand ? $selectedBrand->id : $brands->random()->id,
                        'base_price' => $applyPriceRange ? rand($minPrice, $maxPrice) : rand(100, 1000),
                        'status' => 'active',
                    ]);
                } elseif ($violationType === 2 && $applyBrand) {
                    // Wrong brand
                    $wrongBrand = $brands->where('id', '!=', $selectedBrand->id)->first();
                    $product = Product::factory()->create([
                        'category_id' => $applyCategory ? $selectedCategory->id : $categories->random()->id,
                        'brand_id' => $wrongBrand->id,
                        'base_price' => $applyPriceRange ? rand($minPrice, $maxPrice) : rand(100, 1000),
                        'status' => 'active',
                    ]);
                } elseif ($violationType === 3 && $applyPriceRange) {
                    // Price out of range
                    $outOfRangePrice = rand(0, 1) ? rand(1, $minPrice - 1) : rand($maxPrice + 1, 2000);
                    $product = Product::factory()->create([
                        'category_id' => $applyCategory ? $selectedCategory->id : $categories->random()->id,
                        'brand_id' => $applyBrand ? $selectedBrand->id : $brands->random()->id,
                        'base_price' => $outOfRangePrice,
                        'status' => 'active',
                    ]);
                } else {
                    // If no filters applied or random violation, create a random product
                    $product = Product::factory()->create([
                        'category_id' => $categories->random()->id,
                        'brand_id' => $brands->random()->id,
                        'base_price' => rand(1, 2000),
                        'status' => 'active',
                    ]);
                }
                
                $nonMatchingProducts[] = $product;
            }
            
            // Build filter parameters
            $filters = [];
            if ($applyCategory) {
                $filters['category'] = $selectedCategory->id;
            }
            if ($applyBrand) {
                $filters['brand'] = $selectedBrand->id;
            }
            if ($applyPriceRange) {
                $filters['min_price'] = $minPrice;
                $filters['max_price'] = $maxPrice;
            }
            
            // Skip if no filters applied
            if (empty($filters)) {
                continue;
            }
            
            // Act: Apply filters
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', $filters));
            
            // Assert: All matching products should be present
            $response->assertStatus(200);
            
            foreach ($matchingProducts as $product) {
                $response->assertSee($product->name, false);
            }
            
            // Assert: Non-matching products should NOT be present
            // Only check products that actually violate the applied filters
            foreach ($nonMatchingProducts as $product) {
                $shouldBeFiltered = false;
                
                // Check if product violates any applied filter
                if ($applyCategory && $product->category_id != $selectedCategory->id) {
                    $shouldBeFiltered = true;
                }
                if ($applyBrand && $product->brand_id != $selectedBrand->id) {
                    $shouldBeFiltered = true;
                }
                if ($applyPriceRange && ($product->base_price < $minPrice || $product->base_price > $maxPrice)) {
                    $shouldBeFiltered = true;
                }
                
                if ($shouldBeFiltered) {
                    $response->assertDontSee($product->name, false);
                }
            }
        }
    }

    /**
     * Property: Category filter returns only products from that category
     * 
     * @test
     */
    public function property_category_filter_returns_only_matching_products()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create categories and products
            $targetCategory = Category::factory()->create(['is_active' => true]);
            $otherCategory = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            // Products in target category
            $targetProducts = Product::factory()->count(rand(2, 4))->create([
                'category_id' => $targetCategory->id,
                'brand_id' => $brand->id,
                'status' => 'active',
            ]);
            
            // Products in other category
            $otherProducts = Product::factory()->count(rand(2, 4))->create([
                'category_id' => $otherCategory->id,
                'brand_id' => $brand->id,
                'status' => 'active',
            ]);
            
            // Act: Filter by target category
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', [
                'category' => $targetCategory->id
            ]));
            
            // Assert
            $response->assertStatus(200);
            
            foreach ($targetProducts as $product) {
                $response->assertSee($product->name, false);
            }
            
            foreach ($otherProducts as $product) {
                $response->assertDontSee($product->name, false);
            }
        }
    }

    /**
     * Property: Brand filter returns only products from that brand
     * 
     * @test
     */
    public function property_brand_filter_returns_only_matching_products()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create brands and products
            $category = Category::factory()->create(['is_active' => true]);
            $targetBrand = Brand::factory()->create(['is_active' => true]);
            $otherBrand = Brand::factory()->create(['is_active' => true]);
            
            // Products with target brand
            $targetProducts = Product::factory()->count(rand(2, 4))->create([
                'category_id' => $category->id,
                'brand_id' => $targetBrand->id,
                'status' => 'active',
            ]);
            
            // Products with other brand
            $otherProducts = Product::factory()->count(rand(2, 4))->create([
                'category_id' => $category->id,
                'brand_id' => $otherBrand->id,
                'status' => 'active',
            ]);
            
            // Act: Filter by target brand
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', [
                'brand' => $targetBrand->id
            ]));
            
            // Assert
            $response->assertStatus(200);
            
            foreach ($targetProducts as $product) {
                $response->assertSee($product->name, false);
            }
            
            foreach ($otherProducts as $product) {
                $response->assertDontSee($product->name, false);
            }
        }
    }

    /**
     * Property: Price range filter returns only products within range
     * 
     * @test
     */
    public function property_price_range_filter_returns_only_products_in_range()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create products with different prices
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $minPrice = 200;
            $maxPrice = 500;
            
            // Products within range
            $inRangeProducts = [];
            for ($i = 0; $i < rand(2, 4); $i++) {
                $inRangeProducts[] = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'base_price' => rand($minPrice, $maxPrice),
                    'status' => 'active',
                ]);
            }
            
            // Products below range
            $belowRangeProducts = [];
            for ($i = 0; $i < rand(1, 3); $i++) {
                $belowRangeProducts[] = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'base_price' => rand(1, $minPrice - 1),
                    'status' => 'active',
                ]);
            }
            
            // Products above range
            $aboveRangeProducts = [];
            for ($i = 0; $i < rand(1, 3); $i++) {
                $aboveRangeProducts[] = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'base_price' => rand($maxPrice + 1, 1000),
                    'status' => 'active',
                ]);
            }
            
            // Act: Filter by price range
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', [
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
            ]));
            
            // Assert
            $response->assertStatus(200);
            
            foreach ($inRangeProducts as $product) {
                $response->assertSee($product->name, false);
            }
            
            foreach ($belowRangeProducts as $product) {
                $response->assertDontSee($product->name, false);
            }
            
            foreach ($aboveRangeProducts as $product) {
                $response->assertDontSee($product->name, false);
            }
        }
    }
}
