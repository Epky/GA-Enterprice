<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductStockCalculationPropertyTest extends TestCase
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
     * Feature: inventory-stock-detection-fix, Property 1: Total stock calculation includes all quantities
     * 
     * @test
     */
    public function property_total_stock_includes_available_and_reserved_quantities()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Generate random number of inventory locations (1-5)
            $locationCount = fake()->numberBetween(1, 5);
            $expectedTotal = 0;

            for ($j = 0; $j < $locationCount; $j++) {
                $quantityAvailable = fake()->numberBetween(0, 100);
                $quantityReserved = fake()->numberBetween(0, 50);
                
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $quantityAvailable,
                    'quantity_reserved' => $quantityReserved,
                ]);

                $expectedTotal += ($quantityAvailable + $quantityReserved);
            }

            // Refresh to load inventory relationship
            $product->refresh();

            // Property: total_stock should equal sum of (quantity_available + quantity_reserved)
            $this->assertEquals(
                $expectedTotal,
                $product->total_stock,
                "Iteration {$i}: Total stock should equal sum of available and reserved quantities"
            );

            // Clean up for next iteration
            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Feature: inventory-stock-detection-fix, Property 1: Total stock calculation includes all quantities
     * Edge case: Product with no inventory records
     * 
     * @test
     */
    public function property_total_stock_returns_zero_for_product_with_no_inventory()
    {
        // Run 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Property: total_stock should be 0 when no inventory records exist
            $this->assertEquals(
                0,
                $product->total_stock,
                "Iteration {$i}: Total stock should be 0 for product with no inventory"
            );

            $product->delete();
        }
    }

    /**
     * Feature: inventory-stock-detection-fix, Property 2: Available stock only counts unreserved quantities
     * 
     * @test
     */
    public function property_available_stock_only_counts_quantity_available()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Generate random number of inventory locations (1-5)
            $locationCount = fake()->numberBetween(1, 5);
            $expectedAvailable = 0;

            for ($j = 0; $j < $locationCount; $j++) {
                $quantityAvailable = fake()->numberBetween(0, 100);
                $quantityReserved = fake()->numberBetween(0, 50);
                
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $quantityAvailable,
                    'quantity_reserved' => $quantityReserved,
                ]);

                $expectedAvailable += $quantityAvailable;
            }

            // Refresh to load inventory relationship
            $product->refresh();

            // Property: available_stock should equal sum of quantity_available only
            $this->assertEquals(
                $expectedAvailable,
                $product->available_stock,
                "Iteration {$i}: Available stock should equal sum of quantity_available only"
            );

            // Property: available_stock should NOT include reserved quantities
            $totalReserved = $product->inventory->sum('quantity_reserved');
            $this->assertEquals(
                $product->total_stock - $totalReserved,
                $product->available_stock,
                "Iteration {$i}: Available stock should be total stock minus reserved"
            );

            // Clean up for next iteration
            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Feature: inventory-stock-detection-fix, Property 2: Available stock only counts unreserved quantities
     * Edge case: Product with all stock reserved
     * 
     * @test
     */
    public function property_available_stock_is_zero_when_all_stock_reserved()
    {
        // Run 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Create inventory with all stock reserved
            $quantityReserved = fake()->numberBetween(10, 100);
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 0,
                'quantity_reserved' => $quantityReserved,
            ]);

            // Refresh to load inventory relationship
            $product->refresh();

            // Property: available_stock should be 0 when all stock is reserved
            $this->assertEquals(
                0,
                $product->available_stock,
                "Iteration {$i}: Available stock should be 0 when all stock is reserved"
            );

            // Property: total_stock should still be greater than 0
            $this->assertEquals(
                $quantityReserved,
                $product->total_stock,
                "Iteration {$i}: Total stock should equal reserved quantity"
            );

            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Feature: inventory-stock-detection-fix, Property 2: Available stock only counts unreserved quantities
     * Edge case: Product with no inventory records
     * 
     * @test
     */
    public function property_available_stock_returns_zero_for_product_with_no_inventory()
    {
        // Run 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Property: available_stock should be 0 when no inventory records exist
            $this->assertEquals(
                0,
                $product->available_stock,
                "Iteration {$i}: Available stock should be 0 for product with no inventory"
            );

            $product->delete();
        }
    }

    /**
     * Feature: inventory-stock-detection-fix, Property 3: Stock aggregation across locations
     * 
     * @test
     */
    public function property_stock_aggregates_correctly_across_multiple_locations()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Generate random number of inventory locations (2-10)
            $locationCount = fake()->numberBetween(2, 10);
            $expectedTotal = 0;
            $expectedAvailable = 0;
            $locationTotals = [];

            for ($j = 0; $j < $locationCount; $j++) {
                $quantityAvailable = fake()->numberBetween(0, 100);
                $quantityReserved = fake()->numberBetween(0, 50);
                
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'location' => "location_{$j}",
                    'quantity_available' => $quantityAvailable,
                    'quantity_reserved' => $quantityReserved,
                ]);

                $locationTotal = $quantityAvailable + $quantityReserved;
                $locationTotals[] = $locationTotal;
                $expectedTotal += $locationTotal;
                $expectedAvailable += $quantityAvailable;
            }

            // Refresh to load inventory relationship
            $product->refresh();

            // Property: total_stock should equal sum of all location totals
            $this->assertEquals(
                $expectedTotal,
                $product->total_stock,
                "Iteration {$i}: Total stock should equal sum of all location totals"
            );

            // Property: available_stock should equal sum of all location available quantities
            $this->assertEquals(
                $expectedAvailable,
                $product->available_stock,
                "Iteration {$i}: Available stock should equal sum of all location available quantities"
            );

            // Property: sum of location totals should equal product total_stock
            $sumOfLocationTotals = array_sum($locationTotals);
            $this->assertEquals(
                $sumOfLocationTotals,
                $product->total_stock,
                "Iteration {$i}: Sum of location totals should equal product total stock"
            );

            // Clean up for next iteration
            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Feature: inventory-stock-detection-fix, Property 3: Stock aggregation across locations
     * Edge case: Single location
     * 
     * @test
     */
    public function property_stock_aggregates_correctly_for_single_location()
    {
        // Run 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            $quantityAvailable = fake()->numberBetween(0, 100);
            $quantityReserved = fake()->numberBetween(0, 50);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'location' => 'main_warehouse',
                'quantity_available' => $quantityAvailable,
                'quantity_reserved' => $quantityReserved,
            ]);

            // Refresh to load inventory relationship
            $product->refresh();

            // Property: total_stock should equal the single location's total
            $this->assertEquals(
                $quantityAvailable + $quantityReserved,
                $product->total_stock,
                "Iteration {$i}: Total stock should equal single location total"
            );

            // Property: available_stock should equal the single location's available
            $this->assertEquals(
                $quantityAvailable,
                $product->available_stock,
                "Iteration {$i}: Available stock should equal single location available"
            );

            $product->inventory()->delete();
            $product->delete();
        }
    }
}
