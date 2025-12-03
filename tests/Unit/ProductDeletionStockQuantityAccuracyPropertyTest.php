<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: product-deletion-with-stock, Property 8: Stock Quantity Accuracy
 * Validates: Requirements 2.3
 */
class ProductDeletionStockQuantityAccuracyPropertyTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);
    }

    /**
     * Property 8: Stock Quantity Accuracy
     * For any product with inventory across multiple locations, the stock quantity 
     * displayed in the warning message should equal the sum of quantity_available 
     * and quantity_reserved across all inventory records.
     * 
     * @test
     */
    public function property_displayed_stock_equals_sum_of_all_inventory_quantities()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up any existing data first
            Product::query()->delete();
            Inventory::query()->delete();
            
            // Arrange: Create a random product
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => 'MultiLocationProduct ' . fake()->unique()->word() . ' ' . rand(1000, 9999),
            ]);

            // Generate random number of locations (1 to 5)
            $numLocations = rand(1, 5);
            $locations = ['main_warehouse', 'store_front', 'storage_room', 'online_warehouse', 'backup_storage'];
            
            // Track expected total stock
            $expectedTotalStock = 0;
            $inventoryRecords = [];

            // Create inventory at multiple random locations
            for ($i = 0; $i < $numLocations; $i++) {
                $location = $locations[$i];
                $quantityAvailable = rand(0, 200);
                $quantityReserved = rand(0, 50);
                
                $expectedTotalStock += $quantityAvailable + $quantityReserved;
                
                $inventoryRecords[] = Inventory::factory()->create([
                    'product_id' => $product->id,
                    'location' => $location,
                    'quantity_available' => $quantityAvailable,
                    'quantity_reserved' => $quantityReserved,
                ]);
            }

            // Refresh product and eager load inventory relationship to ensure fresh data
            $product->refresh();
            $product->load('inventory');
            
            // Act: Get the total stock from the product model
            $actualTotalStock = $product->total_stock;

            // Assert: The total_stock attribute should equal the sum of all inventory quantities
            $this->assertEquals(
                $expectedTotalStock,
                $actualTotalStock,
                "Product total_stock should equal sum of (quantity_available + quantity_reserved) across all locations. " .
                "Expected: {$expectedTotalStock}, Got: {$actualTotalStock}, " .
                "Locations: {$numLocations}, Iteration: {$iteration}"
            );

            // Also verify that the stock displayed on the page matches the model's calculation
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.index'));

            $response->assertStatus(200);
            
            // Re-fetch the product to ensure we're checking against the same data the view sees
            $productFromView = Product::with('inventory')->find($product->id);
            $stockInView = $productFromView->total_stock;
            
            // Verify the view's stock matches what we calculated
            $this->assertEquals(
                $expectedTotalStock,
                $stockInView,
                "Stock displayed in view should match expected total. " .
                "Expected: {$expectedTotalStock}, View shows: {$stockInView}"
            );
            
            // The delete button should have the correct stock quantity in data attribute
            $content = $response->getContent();
            
            // Check that the data-stock-quantity attribute with the correct value exists
            $this->assertStringContainsString(
                'data-stock-quantity="' . $stockInView . '"',
                $content,
                "Page should contain data-stock-quantity=\"{$stockInView}\" for product '{$product->name}'"
            );
        }
    }

    /**
     * Property: Stock quantity accuracy on detail page
     * 
     * @test
     */
    public function property_detail_page_displays_accurate_multi_location_stock()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            Product::query()->delete();
            Inventory::query()->delete();
            
            // Arrange: Create product with inventory at multiple locations
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => 'DetailMultiLoc ' . fake()->unique()->word() . ' ' . rand(1000, 9999),
            ]);

            $locations = ['main_warehouse', 'store_front', 'storage_room'];
            $expectedTotal = 0;

            foreach ($locations as $location) {
                $available = rand(10, 100);
                $reserved = rand(0, 30);
                $expectedTotal += $available + $reserved;
                
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'location' => $location,
                    'quantity_available' => $available,
                    'quantity_reserved' => $reserved,
                ]);
            }

            $product = Product::with('inventory')->find($product->id);
            $actualTotal = $product->total_stock;

            // Assert: Model calculation is correct
            $this->assertEquals($expectedTotal, $actualTotal);

            // Act: Load detail page
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.show', $product));

            // Assert: Page displays correct stock
            $response->assertStatus(200);
            $content = $response->getContent();
            $this->assertStringContainsString(
                'data-stock-quantity="' . $actualTotal . '"',
                $content,
                "Detail page should display correct total stock: {$actualTotal}"
            );
        }
    }

    /**
     * Property: Stock quantity accuracy on edit page
     * 
     * @test
     */
    public function property_edit_page_displays_accurate_multi_location_stock()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            Product::query()->delete();
            Inventory::query()->delete();
            
            // Arrange: Create product with inventory at multiple locations
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => 'EditMultiLoc ' . fake()->unique()->word() . ' ' . rand(1000, 9999),
            ]);

            $locations = ['main_warehouse', 'online_warehouse'];
            $expectedTotal = 0;

            foreach ($locations as $location) {
                $available = rand(5, 150);
                $reserved = rand(0, 20);
                $expectedTotal += $available + $reserved;
                
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'location' => $location,
                    'quantity_available' => $available,
                    'quantity_reserved' => $reserved,
                ]);
            }

            $product = Product::with('inventory')->find($product->id);
            $actualTotal = $product->total_stock;

            // Assert: Model calculation is correct
            $this->assertEquals($expectedTotal, $actualTotal);

            // Act: Load edit page
            $response = $this->actingAs($this->createStaffUser())
                ->get(route('staff.products.edit', $product));

            // Assert: Page displays correct stock
            $response->assertStatus(200);
            $content = $response->getContent();
            $this->assertStringContainsString(
                'data-stock-quantity="' . $actualTotal . '"',
                $content,
                "Edit page should display correct total stock: {$actualTotal}"
            );
        }
    }

    /**
     * Helper method to create a staff user
     */
    private function createStaffUser()
    {
        return \App\Models\User::factory()->create([
            'role' => 'staff',
        ]);
    }
}
