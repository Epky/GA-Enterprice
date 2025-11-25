<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\WalkInTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

class WalkInTransactionServiceTest extends TestCase
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

    /** @test */
    public function it_can_add_item_with_sufficient_stock()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 10,
            'quantity_reserved' => 0,
        ]);

        $order = $this->service->createTransaction([
            'customer_name' => 'Test Customer',
        ]);

        $orderItem = $this->service->addItem($order, [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $this->assertInstanceOf(OrderItem::class, $orderItem);
        $this->assertEquals(5, $orderItem->quantity);
        $this->assertEquals($product->id, $orderItem->product_id);
        
        // Verify stock was reserved
        $product->refresh();
        $inventory = $product->inventory->first();
        $this->assertEquals(5, $inventory->quantity_available);
        $this->assertEquals(5, $inventory->quantity_reserved);
    }

    /** @test */
    public function it_cannot_add_item_with_insufficient_available_stock()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 3,
            'quantity_reserved' => 0,
        ]);

        $order = $this->service->createTransaction([
            'customer_name' => 'Test Customer',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Insufficient stock. Available: 3');

        $this->service->addItem($order, [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    /** @test */
    public function it_cannot_add_item_when_stock_is_reserved()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        // All stock is reserved
        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 0,
            'quantity_reserved' => 10,
        ]);

        $order = $this->service->createTransaction([
            'customer_name' => 'Test Customer',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Insufficient stock. Available: 0');

        $this->service->addItem($order, [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    /** @test */
    public function it_can_update_item_quantity_with_sufficient_stock()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 10,
            'quantity_reserved' => 0,
        ]);

        $order = $this->service->createTransaction([
            'customer_name' => 'Test Customer',
        ]);

        $orderItem = $this->service->addItem($order, [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        // Update quantity from 3 to 7
        $updatedItem = $this->service->updateItemQuantity($orderItem, 7);

        $this->assertEquals(7, $updatedItem->quantity);
        
        // Verify stock was adjusted
        $product->refresh();
        $inventory = $product->inventory->first();
        $this->assertEquals(3, $inventory->quantity_available);
        $this->assertEquals(7, $inventory->quantity_reserved);
    }

    /** @test */
    public function it_cannot_update_item_quantity_with_insufficient_stock()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 10,
            'quantity_reserved' => 0,
        ]);

        $order = $this->service->createTransaction([
            'customer_name' => 'Test Customer',
        ]);

        $orderItem = $this->service->addItem($order, [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        // Try to update quantity from 3 to 15 (more than available)
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Insufficient stock. Available: 7');

        $this->service->updateItemQuantity($orderItem, 15);
    }

    /** @test */
    public function it_can_decrease_item_quantity()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 10,
            'quantity_reserved' => 0,
        ]);

        $order = $this->service->createTransaction([
            'customer_name' => 'Test Customer',
        ]);

        $orderItem = $this->service->addItem($order, [
            'product_id' => $product->id,
            'quantity' => 8,
        ]);

        // Decrease quantity from 8 to 3
        $updatedItem = $this->service->updateItemQuantity($orderItem, 3);

        $this->assertEquals(3, $updatedItem->quantity);
        
        // Verify stock was released
        $product->refresh();
        $inventory = $product->inventory->first();
        $this->assertEquals(7, $inventory->quantity_available);
        $this->assertEquals(3, $inventory->quantity_reserved);
    }

    /** @test */
    public function it_respects_available_stock_not_total_stock()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        // 5 available, 10 reserved = 15 total
        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 5,
            'quantity_reserved' => 10,
        ]);

        $order = $this->service->createTransaction([
            'customer_name' => 'Test Customer',
        ]);

        // Should fail because only 5 available (not 15 total)
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Insufficient stock. Available: 5');

        $this->service->addItem($order, [
            'product_id' => $product->id,
            'quantity' => 10,
        ]);
    }
}
