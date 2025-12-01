<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: landing-page-product-display, Property 12: Out of stock product inclusion
 * Validates: Requirements 5.2
 */
class ProductOutOfStockInclusionPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 12: Out of stock product inclusion
     * For any product query, products with zero stock should still be included 
     * in the results and not filtered out
     * 
     * @test
     */
    public function property_out_of_stock_products_are_included_in_results()
    {
        // Run the test multiple times with different random data (100 iterations as per design)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('inventory')->delete();
            \DB::table('product_images')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create mix of in-stock and out-of-stock products
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $outOfStockCount = rand(1, 5);
            $inStockCount = rand(1, 5);
            $totalProducts = $outOfStockCount + $inStockCount;
            
            $outOfStockProducts = [];
            $inStockProducts = [];
            
            // Create out-of-stock products
            for ($i = 0; $i < $outOfStockCount; $i++) {
                $product = Product::factory()->create([
                    'name' => 'Out of Stock Product ' . $iteration . '-' . $i,
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => false,
                ]);
                
                // Randomly choose: no inventory or zero available
                if (rand(0, 1)) {
                    Inventory::factory()->create([
                        'product_id' => $product->id,
                        'quantity_available' => 0,
                        'quantity_reserved' => rand(0, 10),
                    ]);
                }
                // else: no inventory records
                
                $outOfStockProducts[] = $product;
            }
            
            // Create in-stock products
            for ($i = 0; $i < $inStockCount; $i++) {
                $product = Product::factory()->create([
                    'name' => 'In Stock Product ' . $iteration . '-' . $i,
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => false,
                ]);
                
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => rand(1, 50),
                    'quantity_reserved' => rand(0, 10),
                ]);
                
                $inStockProducts[] = $product;
            }
            
            // Act: Render the landing page
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: All products should be visible (both in-stock and out-of-stock)
            $response->assertStatus(200);
            
            // Check that all out-of-stock products are displayed
            foreach ($outOfStockProducts as $product) {
                $response->assertSee($product->name, false);
            }
            
            // Check that all in-stock products are displayed
            foreach ($inStockProducts as $product) {
                $response->assertSee($product->name, false);
            }
            
            // Verify the count of "View Details" buttons matches total products
            $content = $response->getContent();
            $viewDetailsCount = substr_count($content, 'View Details');
            
            $this->assertGreaterThanOrEqual($totalProducts, $viewDetailsCount,
                "Iteration {$iteration}: Expected at least {$totalProducts} products displayed, but found {$viewDetailsCount} 'View Details' buttons");
        }
    }

    /**
     * Property: Out of stock products appear in search results
     * 
     * @test
     */
    public function property_out_of_stock_products_appear_in_search_results()
    {
        // Run the test multiple times
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('inventory')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create out-of-stock product with unique searchable name
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $uniqueSearchTerm = 'UniqueProduct' . $iteration . rand(1000, 9999);
            
            $product = Product::factory()->create([
                'name' => $uniqueSearchTerm,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Make it out of stock
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 0,
                'quantity_reserved' => rand(0, 10),
            ]);
            
            // Act: Search for the product
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', ['search' => $uniqueSearchTerm]));
            
            // Assert: Out-of-stock product should appear in search results
            $response->assertStatus(200);
            $response->assertSee($uniqueSearchTerm, false);
            $response->assertSee('OUT OF STOCK', false);
        }
    }

    /**
     * Property: Out of stock products appear in filtered results
     * 
     * @test
     */
    public function property_out_of_stock_products_appear_in_filtered_results()
    {
        // Run the test multiple times
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('inventory')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create category with out-of-stock product
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Make it out of stock
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 0,
                'quantity_reserved' => rand(0, 10),
            ]);
            
            // Act: Filter by category
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard', ['category' => $category->id]));
            
            // Assert: Out-of-stock product should appear in filtered results
            $response->assertStatus(200);
            $response->assertSee($product->name, false);
            $response->assertSee('OUT OF STOCK', false);
        }
    }

    /**
     * Property: Out of stock products appear across all pages in pagination
     * 
     * @test
     */
    public function property_out_of_stock_products_appear_in_pagination()
    {
        // Clean up
        \DB::table('inventory')->delete();
        \DB::table('products')->delete();
        \DB::table('categories')->delete();
        \DB::table('brands')->delete();
        \DB::table('users')->delete();
        
        // Arrange: Create enough products to trigger pagination (more than 12)
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $totalProducts = 15;
        $outOfStockProducts = [];
        
        for ($i = 0; $i < $totalProducts; $i++) {
            $product = Product::factory()->create([
                'name' => 'Product ' . $i,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Make some products out of stock
            if ($i % 3 === 0) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => 0,
                ]);
                $outOfStockProducts[] = $product;
            } else {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => rand(1, 50),
                ]);
            }
        }
        
        // Act: Get first page
        $user = User::factory()->create(['role' => 'customer']);
        $response1 = $this->actingAs($user)->get(route('customer.dashboard'));
        
        // Assert: First page should include some products
        $response1->assertStatus(200);
        
        // Get second page
        $response2 = $this->actingAs($user)->get(route('customer.dashboard', ['page' => 2]));
        
        // Assert: Second page should also include products
        $response2->assertStatus(200);
        
        // Verify that out-of-stock products appear across pages
        $page1Content = $response1->getContent();
        $page2Content = $response2->getContent();
        
        $outOfStockFoundCount = 0;
        foreach ($outOfStockProducts as $product) {
            if (str_contains($page1Content, $product->name) || str_contains($page2Content, $product->name)) {
                $outOfStockFoundCount++;
            }
        }
        
        $this->assertGreaterThan(0, $outOfStockFoundCount,
            "Expected to find out-of-stock products across paginated results");
    }
}
