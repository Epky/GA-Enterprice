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
 * Feature: landing-page-product-display, Property 11: Out of stock badge display
 * Validates: Requirements 5.1
 */
class ProductOutOfStockBadgeDisplayPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 11: Out of stock badge display
     * For any product with zero total available stock, the rendered product card 
     * should contain an "OUT OF STOCK" badge overlay
     * 
     * @test
     */
    public function property_out_of_stock_badge_displayed_for_zero_available_stock()
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
            
            // Arrange: Create product with zero available stock
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Test different scenarios of zero available stock
            $scenario = rand(0, 3);
            
            if ($scenario === 0) {
                // No inventory records at all
                // Product has no inventory
            } elseif ($scenario === 1) {
                // Single inventory location with 0 available
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => 0,
                    'quantity_reserved' => rand(0, 20),
                ]);
            } elseif ($scenario === 2) {
                // Multiple inventory locations, all with 0 available
                $locationCount = rand(2, 5);
                for ($i = 0; $i < $locationCount; $i++) {
                    Inventory::factory()->create([
                        'product_id' => $product->id,
                        'location' => 'Location ' . $i,
                        'quantity_available' => 0,
                        'quantity_reserved' => rand(0, 10),
                    ]);
                }
            } else {
                // Mix of locations with 0 available
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'location' => 'Warehouse A',
                    'quantity_available' => 0,
                    'quantity_reserved' => rand(5, 15),
                ]);
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'location' => 'Warehouse B',
                    'quantity_available' => 0,
                    'quantity_reserved' => 0,
                ]);
            }
            
            // Act: Render the landing page
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: OUT OF STOCK badge should be present
            $response->assertStatus(200);
            $response->assertSee('OUT OF STOCK', false);
            
            // Verify the product is still displayed
            $response->assertSee($product->name, false);
        }
    }

    /**
     * Property: Products with available stock should NOT show out of stock badge
     * 
     * @test
     */
    public function property_no_out_of_stock_badge_when_stock_available()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('inventory')->delete();
            \DB::table('product_images')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product with available stock (at least 1)
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Create inventory with available stock
            $availableStock = rand(1, 100);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $availableStock,
                'quantity_reserved' => rand(0, 20),
            ]);
            
            // Act: Render the landing page
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: OUT OF STOCK badge should NOT be present
            $response->assertStatus(200);
            
            // The product should be visible
            $response->assertSee($product->name, false);
            
            // Count OUT OF STOCK badges - should be 0
            $content = $response->getContent();
            $outOfStockCount = substr_count($content, 'OUT OF STOCK');
            
            $this->assertEquals(0, $outOfStockCount,
                "Iteration {$iteration}: Expected no OUT OF STOCK badges when product has {$availableStock} available stock, but found {$outOfStockCount}");
        }
    }

    /**
     * Property: Reserved stock does not affect out of stock badge display
     * 
     * @test
     */
    public function property_reserved_stock_does_not_prevent_out_of_stock_badge()
    {
        // Run the test multiple times
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('inventory')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product with reserved stock but no available stock
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            $reservedQuantity = rand(1, 50);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 0,
                'quantity_reserved' => $reservedQuantity,
            ]);
            
            // Act
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: OUT OF STOCK badge should still be displayed
            $response->assertStatus(200);
            $response->assertSee('OUT OF STOCK', false);
            $response->assertSee($product->name, false);
        }
    }
}
