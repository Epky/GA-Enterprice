<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\WalkInTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

class WalkInTransactionFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;
    private Category $category;
    private Brand $brand;
    private WalkInTransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffUser = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);

        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);

        $this->actingAs($this->staffUser);
        $this->service = app(WalkInTransactionService::class);
    }

    /** @test */
    public function complete_transaction_flow_with_stock_checks()
    {
        // Create products with inventory
        $product1 = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'base_price' => 100.00,
            'sale_price' => null, // Ensure no sale price for predictable testing
        ]);

        $product2 = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'base_price' => 50.00,
            'sale_price' => null, // Ensure no sale price for predictable testing
        ]);

        Inventory::factory()->create([
            'product_id' => $product1->id,
            'location' => 'main_warehouse',
            'quantity_available' => 20,
            'quantity_reserved' => 0,
            'quantity_sold' => 0,
        ]);

        Inventory::factory()->create([
            'product_id' => $product2->id,
            'location' => 'main_warehouse',
            'quantity_available' => 15,
            'quantity_reserved' => 5,
            'quantity_sold' => 10,
        ]);

        // Step 1: Create transaction
        $order = $this->service->createTransaction([
            'customer_name' => 'John Doe',
            'customer_phone' => '1234567890',
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('pending', $order->order_status);
        $this->assertEquals('walk_in', $order->order_type);

        // Step 2: Add first item
        $orderItem1 = $this->service->addItem($order, [
            'product_id' => $product1->id,
            'quantity' => 5,
        ]);

        $this->assertEquals(5, $orderItem1->quantity);
        $this->assertEquals(500.00, $orderItem1->total_price);

        // Verify stock was reserved
        $product1->refresh();
        $inventory1 = $product1->inventory->first();
        $this->assertEquals(15, $inventory1->quantity_available);
        $this->assertEquals(5, $inventory1->quantity_reserved);

        // Step 3: Add second item
        $orderItem2 = $this->service->addItem($order, [
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);

        $this->assertEquals(3, $orderItem2->quantity);

        // Verify stock was reserved
        $product2->refresh();
        $inventory2 = $product2->inventory->first();
        $this->assertEquals(12, $inventory2->quantity_available);
        $this->assertEquals(8, $inventory2->quantity_reserved);

        // Step 4: Update item quantity
        $updatedItem = $this->service->updateItemQuantity($orderItem1, 8);
        $this->assertEquals(8, $updatedItem->quantity);

        // Verify stock adjustment
        $product1->refresh();
        $inventory1 = $product1->inventory->first();
        $this->assertEquals(12, $inventory1->quantity_available);
        $this->assertEquals(8, $inventory1->quantity_reserved);

        // Step 5: Complete transaction
        $completedOrder = $this->service->completeTransaction($order, [
            'payment_method' => 'cash',
        ]);

        $this->assertEquals('completed', $completedOrder->order_status);
        $this->assertEquals('paid', $completedOrder->payment_status);

        // Verify stock was converted from reserved to sold
        $product1->refresh();
        $inventory1 = $product1->inventory->first();
        $this->assertEquals(12, $inventory1->quantity_available);
        $this->assertEquals(0, $inventory1->quantity_reserved);
        $this->assertEquals(8, $inventory1->quantity_sold);

        $product2->refresh();
        $inventory2 = $product2->inventory->first();
        $this->assertEquals(12, $inventory2->quantity_available);
        $this->assertEquals(5, $inventory2->quantity_reserved); // Original 5 reserved
        $this->assertEquals(13, $inventory2->quantity_sold); // Original 10 + 3 new

        // Verify payment was created
        $this->assertDatabaseHas('payments', [
            'order_id' => $completedOrder->id,
            'payment_status' => 'completed',
        ]);
    }

    /** @test */
    public function transaction_cancellation_releases_reserved_stock()
    {
        // Create product with inventory
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'main_warehouse',
            'quantity_available' => 50,
            'quantity_reserved' => 0,
        ]);

        // Create transaction and add items
        $order = $this->service->createTransaction([
            'customer_name' => 'Jane Smith',
        ]);

        $this->service->addItem($order, [
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        // Verify stock was reserved
        $product->refresh();
        $inventory = $product->inventory->first();
        $this->assertEquals(40, $inventory->quantity_available);
        $this->assertEquals(10, $inventory->quantity_reserved);

        // Cancel transaction
        $result = $this->service->cancelTransaction($order);
        $this->assertTrue($result);

        // Verify order was cancelled
        $order->refresh();
        $this->assertEquals('cancelled', $order->order_status);

        // Verify stock was released
        $product->refresh();
        $inventory = $product->inventory->first();
        $this->assertEquals(50, $inventory->quantity_available);
        $this->assertEquals(0, $inventory->quantity_reserved);
    }

    /** @test */
    public function transaction_completion_converts_reserved_to_sold()
    {
        // Create product with inventory
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'base_price' => 75.00,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'main_warehouse',
            'quantity_available' => 100,
            'quantity_reserved' => 0,
            'quantity_sold' => 0,
        ]);

        // Create and complete transaction
        $order = $this->service->createTransaction([
            'customer_name' => 'Bob Johnson',
        ]);

        $this->service->addItem($order, [
            'product_id' => $product->id,
            'quantity' => 25,
        ]);

        // Verify initial reservation
        $product->refresh();
        $inventory = $product->inventory->first();
        $this->assertEquals(75, $inventory->quantity_available);
        $this->assertEquals(25, $inventory->quantity_reserved);
        $this->assertEquals(0, $inventory->quantity_sold);

        // Complete transaction
        $this->service->completeTransaction($order, [
            'payment_method' => 'credit_card',
        ]);

        // Verify conversion from reserved to sold
        $product->refresh();
        $inventory = $product->inventory->first();
        $this->assertEquals(75, $inventory->quantity_available);
        $this->assertEquals(0, $inventory->quantity_reserved);
        $this->assertEquals(25, $inventory->quantity_sold);
    }

    /** @test */
    public function cannot_add_item_exceeding_available_stock()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'main_warehouse',
            'quantity_available' => 5,
            'quantity_reserved' => 10,
        ]);

        $order = $this->service->createTransaction([
            'customer_name' => 'Test Customer',
        ]);

        // Try to add more than available stock
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Insufficient stock. Available: 5');

        $this->service->addItem($order, [
            'product_id' => $product->id,
            'quantity' => 10,
        ]);
    }

    /** @test */
    public function cannot_complete_transaction_with_no_items()
    {
        $order = $this->service->createTransaction([
            'customer_name' => 'Empty Order',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot complete an order with no items');

        $this->service->completeTransaction($order, [
            'payment_method' => 'cash',
        ]);
    }

    /** @test */
    public function removing_item_releases_reserved_stock()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'main_warehouse',
            'quantity_available' => 30,
            'quantity_reserved' => 0,
        ]);

        $order = $this->service->createTransaction([
            'customer_name' => 'Test Customer',
        ]);

        $orderItem = $this->service->addItem($order, [
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        // Verify stock was reserved
        $product->refresh();
        $inventory = $product->inventory->first();
        $this->assertEquals(20, $inventory->quantity_available);
        $this->assertEquals(10, $inventory->quantity_reserved);

        // Remove item
        $this->service->removeItem($orderItem);

        // Verify stock was released
        $product->refresh();
        $inventory = $product->inventory->first();
        $this->assertEquals(30, $inventory->quantity_available);
        $this->assertEquals(0, $inventory->quantity_reserved);
    }

    /** @test */
    public function multiple_items_transaction_handles_stock_correctly()
    {
        // Create multiple products
        $products = [];
        for ($i = 0; $i < 3; $i++) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'status' => 'active',
                'base_price' => 50.00,
            ]);

            Inventory::factory()->create([
                'product_id' => $product->id,
                'location' => 'main_warehouse',
                'quantity_available' => 20,
                'quantity_reserved' => 0,
                'quantity_sold' => 0,
            ]);

            $products[] = $product;
        }

        // Create transaction with multiple items
        $order = $this->service->createTransaction([
            'customer_name' => 'Multi-Item Customer',
        ]);

        foreach ($products as $product) {
            $this->service->addItem($order, [
                'product_id' => $product->id,
                'quantity' => 5,
            ]);
        }

        // Verify all stock was reserved
        foreach ($products as $product) {
            $product->refresh();
            $inventory = $product->inventory->first();
            $this->assertEquals(15, $inventory->quantity_available);
            $this->assertEquals(5, $inventory->quantity_reserved);
        }

        // Complete transaction
        $this->service->completeTransaction($order, [
            'payment_method' => 'cash',
        ]);

        // Verify all stock was converted to sold
        foreach ($products as $product) {
            $product->refresh();
            $inventory = $product->inventory->first();
            $this->assertEquals(15, $inventory->quantity_available);
            $this->assertEquals(0, $inventory->quantity_reserved);
            $this->assertEquals(5, $inventory->quantity_sold);
        }
    }

    /** @test */
    public function transaction_respects_available_stock_not_total_stock()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        // 10 available, 20 reserved = 30 total
        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'main_warehouse',
            'quantity_available' => 10,
            'quantity_reserved' => 20,
        ]);

        $order = $this->service->createTransaction([
            'customer_name' => 'Test Customer',
        ]);

        // Should succeed with 10 (available stock)
        $orderItem = $this->service->addItem($order, [
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $this->assertEquals(10, $orderItem->quantity);

        // Verify stock
        $product->refresh();
        $inventory = $product->inventory->first();
        $this->assertEquals(0, $inventory->quantity_available);
        $this->assertEquals(30, $inventory->quantity_reserved);

        // Should fail with 1 more (no available stock left)
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Insufficient stock. Available: 0');

        $this->service->addItem($order, [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }
}
