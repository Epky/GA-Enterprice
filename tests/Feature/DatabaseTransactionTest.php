<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class DatabaseTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;
    private Category $category;
    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffUser = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);

        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);
    }

    public function test_product_creation_rolls_back_on_error()
    {
        $initialProductCount = Product::count();

        try {
            DB::transaction(function () {
                // Create product
                $product = Product::factory()->create([
                    'category_id' => $this->category->id,
                    'brand_id' => $this->brand->id,
                ]);

                // Create inventory
                Inventory::factory()->create([
                    'product_id' => $product->id,
                ]);

                // Simulate error
                throw new \Exception('Simulated error');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Verify rollback - product count should be unchanged
        $this->assertEquals($initialProductCount, Product::count());
    }

    public function test_inventory_update_maintains_consistency()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 100,
        ]);

        $initialQuantity = $inventory->quantity_available;

        // Perform update in transaction
        DB::transaction(function () use ($product, $inventory) {
            $inventory->update(['quantity_available' => 80]);
            
            // Record movement
            $product->inventoryMovements()->create([
                'movement_type' => 'sale',
                'quantity' => -20,
                'quantity_before' => 100,
                'quantity_after' => 80,
                'performed_by' => $this->staffUser->id,
                'notes' => 'Test sale',
            ]);
        });

        // Verify both updates succeeded
        $inventory->refresh();
        $this->assertEquals(80, $inventory->quantity_available);
        $this->assertEquals(1, $product->inventoryMovements()->count());
    }

    public function test_bulk_update_is_atomic()
    {
        $products = Product::factory()->count(5)->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        $productIds = $products->pluck('id')->toArray();

        try {
            DB::transaction(function () use ($productIds) {
                // Update first 3 products
                Product::whereIn('id', array_slice($productIds, 0, 3))
                    ->update(['status' => 'inactive']);

                // Simulate error before updating remaining products
                throw new \Exception('Simulated error');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Verify rollback - all products should still be active
        foreach ($products as $product) {
            $product->refresh();
            $this->assertEquals('active', $product->status);
        }
    }

    public function test_product_deletion_with_related_data()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $images = ProductImage::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
        ]);

        $imageCount = ProductImage::count();
        $inventoryCount = Inventory::count();

        // Soft delete product
        $product->delete();

        // Verify product is soft deleted
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        // Verify related data is preserved
        $this->assertEquals($imageCount, ProductImage::count());
        $this->assertEquals($inventoryCount, Inventory::count());
    }

    public function test_concurrent_inventory_updates_maintain_integrity()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 100,
        ]);

        // Simulate two concurrent updates
        DB::transaction(function () use ($inventory) {
            $currentQty = $inventory->quantity_available;
            $inventory->update(['quantity_available' => $currentQty - 10]);
        });

        DB::transaction(function () use ($inventory) {
            $inventory->refresh();
            $currentQty = $inventory->quantity_available;
            $inventory->update(['quantity_available' => $currentQty - 5]);
        });

        // Verify final quantity is correct
        $inventory->refresh();
        $this->assertEquals(85, $inventory->quantity_available);
    }

    public function test_category_deletion_prevents_orphaned_products()
    {
        $category = Category::factory()->create(['is_active' => true]);
        
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $this->brand->id,
        ]);

        // Attempt to delete category with products
        $category->delete();

        // Verify category is soft deleted
        $this->assertSoftDeleted('categories', ['id' => $category->id]);

        // Verify product still exists and references the category
        $product->refresh();
        $this->assertEquals($category->id, $product->category_id);
    }

    public function test_inventory_movement_tracking_consistency()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 100,
        ]);

        // Perform multiple inventory movements
        $movements = [
            ['type' => 'sale', 'quantity' => -10, 'expected' => 90],
            ['type' => 'restock', 'quantity' => 50, 'expected' => 140],
            ['type' => 'adjustment', 'quantity' => -15, 'expected' => 125],
        ];

        foreach ($movements as $movement) {
            DB::transaction(function () use ($product, $inventory, $movement) {
                $quantityBefore = $inventory->quantity_available;
                $quantityAfter = $quantityBefore + $movement['quantity'];
                
                $inventory->update(['quantity_available' => $quantityAfter]);
                
                $product->inventoryMovements()->create([
                    'movement_type' => $movement['type'],
                    'quantity' => $movement['quantity'],
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'performed_by' => $this->staffUser->id,
                ]);
            });

            $inventory->refresh();
            $this->assertEquals($movement['expected'], $inventory->quantity_available);
        }

        // Verify all movements were recorded
        $this->assertEquals(3, $product->inventoryMovements()->count());
    }
}
