<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\User;
use App\Services\WalkInTransactionService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

class WalkInTransactionServicePropertyTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Brand $brand;
    private User $user;
    private WalkInTransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);
        $this->user = User::factory()->create(['role' => 'staff']);
        $this->actingAs($this->user);
        
        $this->service = app(WalkInTransactionService::class);
    }

    /**
     * Feature: inventory-stock-detection-fix, Property 4: Error messages display available stock
     * 
     * @test
     */
    public function property_error_messages_display_available_stock_not_total_stock()
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'status' => 'active',
            ]);

            // Generate random quantities where available < requested
            $quantityAvailable = fake()->numberBetween(0, 50);
            $quantityReserved = fake()->numberBetween(10, 100);
            $requestedQuantity = $quantityAvailable + fake()->numberBetween(1, 20);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => $quantityAvailable,
                'quantity_reserved' => $quantityReserved,
            ]);

            // Refresh to load inventory relationship
            $product->refresh();

            // Create an order
            $order = $this->service->createTransaction([
                'customer_name' => fake()->name(),
            ]);

            // Property: Error message should contain the available_stock value
            try {
                $this->service->addItem($order, [
                    'product_id' => $product->id,
                    'quantity' => $requestedQuantity,
                ]);
                
                $this->fail("Iteration {$i}: Expected ValidationException to be thrown");
            } catch (ValidationException $e) {
                $errorMessage = $e->errors()['quantity'][0] ?? '';
                
                // Property: Error message must contain the available stock quantity
                $this->assertStringContainsString(
                    (string)$quantityAvailable,
                    $errorMessage,
                    "Iteration {$i}: Error message should contain available stock ({$quantityAvailable})"
                );
                
                // Property: Error message should NOT contain total stock if different from available
                $totalStock = $quantityAvailable + $quantityReserved;
                if ($totalStock !== $quantityAvailable) {
                    $this->assertStringNotContainsString(
                        (string)$totalStock,
                        $errorMessage,
                        "Iteration {$i}: Error message should not contain total stock ({$totalStock}) when different from available"
                    );
                }
            }

            // Clean up for next iteration
            $order->delete();
            $product->inventory()->delete();
            $product->delete();
        }
    }

    /**
     * Feature: inventory-stock-detection-fix, Property 4: Error messages display available stock
     * Edge case: Zero available stock with reserved stock
     * 
     * @test
     */
    public function property_error_messages_show_zero_when_all_stock_is_reserved()
    {
        // Run 20 iterations
        for ($i = 0; $i < 20; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'status' => 'active',
            ]);

            // All stock is reserved
            $quantityReserved = fake()->numberBetween(10, 100);
            
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 0,
                'quantity_reserved' => $quantityReserved,
            ]);

            // Refresh to load inventory relationship
            $product->refresh();

            // Create an order
            $order = $this->service->createTransaction([
                'customer_name' => fake()->name(),
            ]);

            // Property: Error message should show "Available: 0"
            try {
                $this->service->addItem($order, [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]);
                
                $this->fail("Iteration {$i}: Expected ValidationException to be thrown");
            } catch (ValidationException $e) {
                $errorMessage = $e->errors()['quantity'][0] ?? '';
                
                // Property: Error message must show available stock as 0
                $this->assertStringContainsString(
                    'Available: 0',
                    $errorMessage,
                    "Iteration {$i}: Error message should show 'Available: 0' when all stock is reserved"
                );
            }

            // Clean up for next iteration
            $order->delete();
            $product->inventory()->delete();
            $product->delete();
        }
    }
}
