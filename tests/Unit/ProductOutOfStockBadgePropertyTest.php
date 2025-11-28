<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 9: Out of stock badge display
 * Validates: Requirements 4.2
 */
class ProductOutOfStockBadgePropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 9: Out of stock badge display
     * For any product where the sum of inventory.quantity_available equals zero, 
     * the product card should display an "OUT OF STOCK" badge overlay
     * 
     * @test
     */
    public function property_out_of_stock_badge_displayed_when_no_available_stock()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            \DB::table('inventory')->delete();
            
            // Arrange: Create product with zero available stock
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Create inventory with zero available stock
            // Test different scenarios: no inventory, or inventory with 0 quantity
            $scenario = rand(0, 2);
            
            if ($scenario === 1) {
                // Single inventory location with 0 available
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => 0,
                    'quantity_reserved' => rand(0, 10),
                ]);
            } elseif ($scenario === 2) {
                // Multiple inventory locations, all with 0 available
                $locationCount = rand(2, 4);
                for ($i = 0; $i < $locationCount; $i++) {
                    Inventory::factory()->create([
                        'product_id' => $product->id,
                        'quantity_available' => 0,
                        'quantity_reserved' => rand(0, 5),
                    ]);
                }
            }
            // scenario 0: no inventory at all
            
            // Act: Render the dashboard view
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert: OUT OF STOCK badge should be present
            $response->assertStatus(200);
            $response->assertSee('OUT OF STOCK', false);
            
            // Verify the badge has the correct styling
            $response->assertSee('bg-red-500', false);
        }
    }

    /**
     * Property: Products with available stock should not show out of stock badge
     * 
     * @test
     */
    public function property_no_out_of_stock_badge_when_stock_available()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            \DB::table('inventory')->delete();
            
            // Arrange: Create product with available stock
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Create inventory with available stock (at least 1)
            $availableStock = rand(1, 100);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $availableStock,
                'quantity_reserved' => rand(0, 10),
            ]);
            
            // Act: Render the dashboard view
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert: OUT OF STOCK badge should NOT be present for this product
            $response->assertStatus(200);
            
            // The product should be visible
            $response->assertSee($product->name, false);
            
            // Count OUT OF STOCK badges - should be 0
            $content = $response->getContent();
            $outOfStockCount = substr_count($content, 'OUT OF STOCK');
            
            $this->assertEquals(0, $outOfStockCount,
                "Expected no OUT OF STOCK badges when product has {$availableStock} available stock, but found {$outOfStockCount}");
        }
    }

    /**
     * Property: Out of stock badge should overlay the product image
     * 
     * @test
     */
    public function property_out_of_stock_badge_overlays_product_image()
    {
        // Arrange: Create product with zero stock
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
        ]);
        
        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 0,
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Badge should be in an overlay div with proper styling
        $response->assertStatus(200);
        $response->assertSee('absolute inset-0', false);
        $response->assertSee('bg-black bg-opacity-50', false);
        $response->assertSee('OUT OF STOCK', false);
    }

    /**
     * Property: Multiple products with mixed stock levels show correct badges
     * 
     * @test
     */
    public function property_mixed_stock_products_show_correct_badges()
    {
        // Arrange: Create multiple products with different stock levels
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $outOfStockCount = rand(2, 4);
        $inStockCount = rand(2, 4);
        
        // Create out of stock products
        for ($i = 0; $i < $outOfStockCount; $i++) {
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 0,
            ]);
        }
        
        // Create in-stock products
        for ($i = 0; $i < $inStockCount; $i++) {
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => rand(1, 50),
            ]);
        }
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Number of OUT OF STOCK badges should match out of stock products
        $response->assertStatus(200);
        
        $content = $response->getContent();
        $badgeCount = substr_count($content, 'OUT OF STOCK');
        
        $this->assertEquals($outOfStockCount, $badgeCount,
            "Expected {$outOfStockCount} OUT OF STOCK badges, but found {$badgeCount}");
    }

    /**
     * Property: Reserved stock does not affect out of stock badge display
     * 
     * @test
     */
    public function property_reserved_stock_does_not_prevent_out_of_stock_badge()
    {
        // Arrange: Create product with reserved stock but no available stock
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
        ]);
        
        $reservedQuantity = rand(5, 50);
        
        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 0,
            'quantity_reserved' => $reservedQuantity,
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: OUT OF STOCK badge should still be displayed
        $response->assertStatus(200);
        $response->assertSee('OUT OF STOCK', false);
    }
}
