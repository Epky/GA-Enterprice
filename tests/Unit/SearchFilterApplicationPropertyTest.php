<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: landing-page-product-display, Property 7: Search filter application
 * Validates: Requirements 3.1
 */
class SearchFilterApplicationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 7: Search filter application
     * For any search term, all products returned should have the search term 
     * present in either the product name, description, or SKU
     * 
     * @test
     */
    public function property_search_returns_only_matching_products()
    {
        // Run the test multiple times with different random data (100 iterations)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create products with specific searchable terms
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            // Create a random search term
            $searchTerms = ['Lipstick', 'Serum', 'Cream', 'Powder', 'Lotion', 'Mask', 'Oil', 'Gel'];
            $searchTerm = $searchTerms[array_rand($searchTerms)];
            
            // Create products that match the search term
            $matchingCount = rand(1, 5);
            $matchingProducts = [];
            
            for ($i = 0; $i < $matchingCount; $i++) {
                $matchType = rand(1, 3);
                
                if ($matchType === 1) {
                    // Match in name
                    $product = Product::factory()->create([
                        'name' => $searchTerm . ' Product ' . $i,
                        'description' => 'Random description ' . rand(1000, 9999),
                        'sku' => 'SKU-' . rand(1000, 9999),
                        'category_id' => $category->id,
                        'brand_id' => $brand->id,
                        'status' => 'active',
                    ]);
                } elseif ($matchType === 2) {
                    // Match in description
                    $product = Product::factory()->create([
                        'name' => 'Product ' . $i . ' ' . rand(1000, 9999),
                        'description' => 'This is a ' . $searchTerm . ' product description',
                        'sku' => 'SKU-' . rand(1000, 9999),
                        'category_id' => $category->id,
                        'brand_id' => $brand->id,
                        'status' => 'active',
                    ]);
                } else {
                    // Match in SKU
                    $product = Product::factory()->create([
                        'name' => 'Product ' . $i . ' ' . rand(1000, 9999),
                        'description' => 'Random description ' . rand(1000, 9999),
                        'sku' => $searchTerm . '-' . rand(1000, 9999),
                        'category_id' => $category->id,
                        'brand_id' => $brand->id,
                        'status' => 'active',
                    ]);
                }
                
                $matchingProducts[] = $product;
            }
            
            // Create products that don't match the search term
            $nonMatchingCount = rand(1, 5);
            $nonMatchingProducts = [];
            
            for ($i = 0; $i < $nonMatchingCount; $i++) {
                $product = Product::factory()->create([
                    'name' => 'Different Product ' . $i . ' ' . rand(1000, 9999),
                    'description' => 'Different description ' . rand(1000, 9999),
                    'sku' => 'DIFF-' . rand(1000, 9999),
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                ]);
                
                $nonMatchingProducts[] = $product;
            }
            
            // Act: Search with the term
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', [
                'search' => $searchTerm
            ]));
            
            // Assert: All matching products should be present
            $response->assertStatus(200);
            
            foreach ($matchingProducts as $product) {
                $response->assertSee($product->name, false);
            }
            
            // Assert: Non-matching products should NOT be present
            foreach ($nonMatchingProducts as $product) {
                $response->assertDontSee($product->name, false);
            }
        }
    }

    /**
     * Property: Search is case-insensitive
     * 
     * @test
     */
    public function property_search_is_case_insensitive()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product with mixed case name
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'name' => 'MixedCase Product',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
            ]);
            
            // Test with different case variations
            $searchVariations = ['mixedcase', 'MIXEDCASE', 'MiXeDcAsE'];
            $searchTerm = $searchVariations[array_rand($searchVariations)];
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', [
                'search' => $searchTerm
            ]));
            
            // Assert: Product should be found regardless of case
            $response->assertStatus(200);
            $response->assertSee($product->name, false);
        }
    }

    /**
     * Property: Search with partial match works
     * 
     * @test
     */
    public function property_search_with_partial_match_works()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $fullName = 'Beautiful Lipstick Red';
            $product = Product::factory()->create([
                'name' => $fullName,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
            ]);
            
            // Search with partial term
            $partialTerms = ['Lip', 'stick', 'Red', 'Beautiful'];
            $searchTerm = $partialTerms[array_rand($partialTerms)];
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', [
                'search' => $searchTerm
            ]));
            
            // Assert: Product should be found with partial match
            $response->assertStatus(200);
            $response->assertSee($product->name, false);
        }
    }

    /**
     * Property: Empty search returns all products
     * 
     * @test
     */
    public function property_empty_search_returns_all_products()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create multiple products
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $productCount = rand(3, 8);
            $products = [];
            
            for ($i = 0; $i < $productCount; $i++) {
                $products[] = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                ]);
            }
            
            // Act: Search with empty string
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', [
                'search' => ''
            ]));
            
            // Assert: All products should be visible
            $response->assertStatus(200);
            
            foreach ($products as $product) {
                $response->assertSee($product->name, false);
            }
        }
    }
}
