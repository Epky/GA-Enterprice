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
 * Feature: landing-page-product-display, Property 13: Stock calculation across locations
 * Validates: Requirements 5.3
 */
class ProductStockCalculationAcrossLocationsPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 13: Stock calculation across locations
     * For any product with multiple inventory locations, the total available stock 
     * should equal the sum of quantity_available across all locations
     * 
     * @test
     */
    public function property_available_stock_equals_sum_across_all_locations()
    {
        // Run the test multiple times with different random data (100 iterations as per design)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('inventory')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            
            // Arrange: Create product with multiple inventory locations
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Create random number of inventory locations (1-10)
            $locationCount = rand(1, 10);
            $expectedAvailableStock = 0;
            $inventoryRecords = [];
            
            for ($i = 0; $i < $locationCount; $i++) {
                $quantityAvailable = rand(0, 100);
                $quantityReserved = rand(0, 50);
                
                $inventory = Inventory::factory()->create([
                    'product_id' => $product->id,
                    'location' => 'Location ' . $i . ' Iter ' . $iteration,
                    'quantity_available' => $quantityAvailable,
                    'quantity_reserved' => $quantityReserved,
                ]);
                
                $expectedAvailableStock += $quantityAvailable;
                $inventoryRecords[] = $inventory;
            }
            
            // Refresh product to load inventory relationship
            $product->refresh();
            
            // Assert: available_stock should equal sum of quantity_available across all locations
            $actualAvailableStock = $product->available_stock;
            
            $this->assertEquals($expectedAvailableStock, $actualAvailableStock,
                "Iteration {$iteration}: Expected available stock {$expectedAvailableStock} (sum across {$locationCount} locations), but got {$actualAvailableStock}");
            
            // Verify by manually summing
            $manualSum = $product->inventory->sum('quantity_available');
            $this->assertEquals($expectedAvailableStock, $manualSum,
                "Iteration {$iteration}: Manual sum should match expected sum");
        }
    }

    /**
     * Property: Stock calculation correctly handles zero stock across multiple locations
     * 
     * @test
     */
    public function property_zero_stock_calculated_correctly_across_locations()
    {
        // Run the test multiple times
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('inventory')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            
            // Arrange: Create product with multiple locations, all with zero available
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Create random number of locations (2-8), all with zero available
            $locationCount = rand(2, 8);
            
            for ($i = 0; $i < $locationCount; $i++) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'location' => 'Location ' . $i,
                    'quantity_available' => 0,
                    'quantity_reserved' => rand(0, 20),
                ]);
            }
            
            // Refresh product
            $product->refresh();
            
            // Assert: available_stock should be 0
            $this->assertEquals(0, $product->available_stock,
                "Iteration {$iteration}: Expected 0 available stock across {$locationCount} locations with zero available");
        }
    }

    /**
     * Property: Stock calculation with single location
     * 
     * @test
     */
    public function property_stock_calculation_works_with_single_location()
    {
        // Run the test multiple times
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('inventory')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            
            // Arrange: Create product with single inventory location
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            $quantityAvailable = rand(0, 100);
            $quantityReserved = rand(0, 50);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'location' => 'Main Warehouse',
                'quantity_available' => $quantityAvailable,
                'quantity_reserved' => $quantityReserved,
            ]);
            
            // Refresh product
            $product->refresh();
            
            // Assert: available_stock should equal the single location's quantity_available
            $this->assertEquals($quantityAvailable, $product->available_stock,
                "Iteration {$iteration}: Expected available stock {$quantityAvailable} for single location");
        }
    }

    /**
     * Property: Stock calculation with no inventory records
     * 
     * @test
     */
    public function property_stock_calculation_returns_zero_with_no_inventory()
    {
        // Run the test multiple times
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            \DB::table('inventory')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            
            // Arrange: Create product with no inventory records
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // No inventory records created
            
            // Refresh product
            $product->refresh();
            
            // Assert: available_stock should be 0
            $this->assertEquals(0, $product->available_stock,
                "Iteration {$iteration}: Expected 0 available stock when no inventory records exist");
        }
    }

    /**
     * Property: Reserved stock does not contribute to available stock calculation
     * 
     * @test
     */
    public function property_reserved_stock_not_included_in_available_stock()
    {
        // Run the test multiple times
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up
            \DB::table('inventory')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            
            // Arrange: Create product with multiple locations having both available and reserved
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            $locationCount = rand(2, 6);
            $expectedAvailable = 0;
            $totalReserved = 0;
            
            for ($i = 0; $i < $locationCount; $i++) {
                $quantityAvailable = rand(0, 50);
                $quantityReserved = rand(1, 30); // Always have some reserved
                
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'location' => 'Location ' . $i,
                    'quantity_available' => $quantityAvailable,
                    'quantity_reserved' => $quantityReserved,
                ]);
                
                $expectedAvailable += $quantityAvailable;
                $totalReserved += $quantityReserved;
            }
            
            // Refresh product
            $product->refresh();
            
            // Assert: available_stock should only include quantity_available, not reserved
            $this->assertEquals($expectedAvailable, $product->available_stock,
                "Iteration {$iteration}: Available stock should not include {$totalReserved} reserved units");
            
            // Verify that total_stock includes both
            $expectedTotal = $expectedAvailable + $totalReserved;
            $this->assertEquals($expectedTotal, $product->total_stock,
                "Iteration {$iteration}: Total stock should include both available and reserved");
        }
    }

    /**
     * Property: Stock calculation consistency in view rendering
     * 
     * @test
     */
    public function property_stock_calculation_consistent_in_view()
    {
        // Run the test multiple times
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Clean up
            \DB::table('inventory')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product with known stock across locations
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            $locationCount = rand(2, 5);
            $totalAvailable = 0;
            
            for ($i = 0; $i < $locationCount; $i++) {
                $quantityAvailable = rand(0, 30);
                
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'location' => 'Location ' . $i,
                    'quantity_available' => $quantityAvailable,
                    'quantity_reserved' => rand(0, 10),
                ]);
                
                $totalAvailable += $quantityAvailable;
            }
            
            // Act: Render the landing page
            $user = User::factory()->create(['role' => 'customer']);
            $response = $this->actingAs($user)->get(route('customer.dashboard'));
            
            // Assert: If total available is 0, OUT OF STOCK badge should appear
            // If total available > 0, OUT OF STOCK badge should NOT appear
            $response->assertStatus(200);
            $response->assertSee($product->name, false);
            
            $content = $response->getContent();
            $hasOutOfStockBadge = str_contains($content, 'OUT OF STOCK');
            
            if ($totalAvailable === 0) {
                $this->assertTrue($hasOutOfStockBadge,
                    "Iteration {$iteration}: Expected OUT OF STOCK badge when total available is 0 across {$locationCount} locations");
            } else {
                $this->assertFalse($hasOutOfStockBadge,
                    "Iteration {$iteration}: Expected NO OUT OF STOCK badge when total available is {$totalAvailable} across {$locationCount} locations");
            }
        }
    }
}
