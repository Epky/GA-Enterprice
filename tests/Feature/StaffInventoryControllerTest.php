<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StaffInventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;
    private User $customerUser;
    private Category $category;
    private Brand $brand;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffUser = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);

        $this->customerUser = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);
        
        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);
    }

    public function test_staff_can_view_inventory_dashboard()
    {
        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.inventory.index'));

        $response->assertStatus(200);
        $response->assertViewIs('staff.inventory.index');
    }

    public function test_customer_cannot_access_inventory_dashboard()
    {
        $response = $this->actingAs($this->customerUser)
            ->get(route('staff.inventory.index'));

        $response->assertStatus(403);
    }

    public function test_staff_can_view_product_inventory()
    {
        Inventory::factory()->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.inventory.edit', $this->product));

        $response->assertStatus(200);
        $response->assertViewIs('staff.inventory.edit');
        $response->assertViewHas('product');
    }

    public function test_staff_can_update_inventory()
    {
        $inventory = Inventory::factory()->create([
            'product_id' => $this->product->id,
            'quantity_available' => 100,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->put(route('staff.inventory.update', $this->product), [
                'quantity_available' => 150,
                'movement_type' => 'restock',
                'notes' => 'Restocking inventory',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('inventories', [
            'product_id' => $this->product->id,
            'quantity_available' => 150,
        ]);
    }

    public function test_staff_can_view_bulk_update_form()
    {
        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.inventory.bulk-update'));

        $response->assertStatus(200);
        $response->assertViewIs('staff.inventory.bulk-update');
    }

    public function test_staff_can_perform_bulk_inventory_update()
    {
        $products = Product::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        foreach ($products as $product) {
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 50,
            ]);
        }

        $updates = [];
        foreach ($products as $product) {
            $updates[] = [
                'product_id' => $product->id,
                'quantity_available' => 100,
            ];
        }

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.inventory.bulk-update.store'), [
                'updates' => $updates,
                'movement_type' => 'adjustment',
                'notes' => 'Bulk inventory adjustment',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        foreach ($products as $product) {
            $this->assertDatabaseHas('inventories', [
                'product_id' => $product->id,
                'quantity_available' => 100,
            ]);
        }
    }

    public function test_staff_can_view_inventory_movements()
    {
        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.inventory.movements'));

        $response->assertStatus(200);
        $response->assertViewIs('staff.inventory.movements');
    }

    public function test_inventory_update_creates_movement_record()
    {
        $inventory = Inventory::factory()->create([
            'product_id' => $this->product->id,
            'quantity_available' => 100,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->put(route('staff.inventory.update', $this->product), [
                'quantity_available' => 120,
                'movement_type' => 'restock',
                'notes' => 'Adding stock',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'movement_type' => 'restock',
            'quantity' => 20,
        ]);
    }

    public function test_staff_cannot_set_negative_inventory()
    {
        $inventory = Inventory::factory()->create([
            'product_id' => $this->product->id,
            'quantity_available' => 100,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->put(route('staff.inventory.update', $this->product), [
                'quantity_available' => -10,
                'movement_type' => 'adjustment',
            ]);

        $response->assertSessionHasErrors(['quantity_available']);
    }

    public function test_low_stock_alerts_are_displayed()
    {
        Inventory::factory()->create([
            'product_id' => $this->product->id,
            'quantity_available' => 5,
            'reorder_level' => 10,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.inventory.index'));

        $response->assertStatus(200);
        $response->assertViewHas('lowStockAlerts');
    }
}
