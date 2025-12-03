<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductDeletionTotalStockCalculationTest extends TestCase
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

    public function test_total_stock_attribute_returns_zero_for_no_inventory()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        // No inventory records created
        $this->assertEquals(0, $product->total_stock);
    }

    public function test_total_stock_attribute_sums_single_location()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 50,
            'quantity_reserved' => 10,
        ]);

        // Should sum both available and reserved
        $this->assertEquals(60, $product->fresh()->total_stock);
    }

    public function test_total_stock_attribute_sums_multiple_locations()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        // Create inventory at multiple locations
        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Warehouse A',
            'quantity_available' => 30,
            'quantity_reserved' => 5,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Warehouse B',
            'quantity_available' => 20,
            'quantity_reserved' => 3,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Store Front',
            'quantity_available' => 15,
            'quantity_reserved' => 2,
        ]);

        // Should sum all: (30+5) + (20+3) + (15+2) = 75
        $this->assertEquals(75, $product->fresh()->total_stock);
    }

    public function test_total_stock_attribute_handles_zero_quantities()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
        ]);

        $this->assertEquals(0, $product->fresh()->total_stock);
    }

    public function test_total_stock_attribute_includes_reserved_quantity()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 10,
            'quantity_reserved' => 40, // More reserved than available
        ]);

        // Should include both
        $this->assertEquals(50, $product->fresh()->total_stock);
    }

    public function test_total_stock_attribute_with_mixed_quantities()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        // Location 1: Has both available and reserved
        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Location 1',
            'quantity_available' => 100,
            'quantity_reserved' => 25,
        ]);

        // Location 2: Only available
        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Location 2',
            'quantity_available' => 50,
            'quantity_reserved' => 0,
        ]);

        // Location 3: Only reserved
        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Location 3',
            'quantity_available' => 0,
            'quantity_reserved' => 15,
        ]);

        // Should sum all: (100+25) + (50+0) + (0+15) = 190
        $this->assertEquals(190, $product->fresh()->total_stock);
    }

    public function test_available_stock_attribute_excludes_reserved()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 50,
            'quantity_reserved' => 10,
        ]);

        // available_stock should only count quantity_available
        $this->assertEquals(50, $product->fresh()->available_stock);
    }

    public function test_available_stock_attribute_sums_multiple_locations()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Location 1',
            'quantity_available' => 30,
            'quantity_reserved' => 5,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Location 2',
            'quantity_available' => 20,
            'quantity_reserved' => 3,
        ]);

        // Should only sum available: 30 + 20 = 50
        $this->assertEquals(50, $product->fresh()->available_stock);
    }
}
