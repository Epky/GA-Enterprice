<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductManagementWorkflowTest extends TestCase
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

        Storage::fake('public');
    }

    public function test_complete_product_creation_workflow()
    {
        // Step 1: Create product
        $productData = [
            'name' => 'Complete Test Product',
            'sku' => 'CTP-001',
            'description' => 'Complete product description',
            'price' => 99.99,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.products.store'), $productData);

        $response->assertRedirect(route('staff.products.index'));
        
        $product = Product::where('sku', 'CTP-001')->first();
        $this->assertNotNull($product);

        // Step 2: Upload images
        $images = [
            UploadedFile::fake()->image('image1.jpg'),
            UploadedFile::fake()->image('image2.jpg'),
        ];

        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.products.images.upload', $product), [
                'images' => $images,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(2, $product->images()->count());

        // Step 3: Set inventory
        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 100,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->put(route('staff.inventory.update', $product), [
                'quantity_available' => 150,
                'movement_type' => 'restock',
                'notes' => 'Initial stock',
            ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'quantity_available' => 150,
        ]);

        // Step 4: Verify product is visible
        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.products.show', $product));

        $response->assertStatus(200);
        $response->assertSee('Complete Test Product');
    }

    public function test_product_update_with_inventory_tracking_workflow()
    {
        // Create product with inventory
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 100,
        ]);

        // Update product details
        $updateData = [
            'name' => 'Updated Product',
            'sku' => $product->sku,
            'description' => 'Updated description',
            'price' => 79.99,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->staffUser)
            ->put(route('staff.products.update', $product), $updateData);

        $response->assertRedirect();

        // Update inventory
        $response = $this->actingAs($this->staffUser)
            ->put(route('staff.inventory.update', $product), [
                'quantity_available' => 80,
                'movement_type' => 'sale',
                'notes' => 'Sold 20 units',
            ]);

        $response->assertRedirect();

        // Verify changes
        $product->refresh();
        $this->assertEquals('Updated Product', $product->name);
        $this->assertEquals(79.99, $product->price);

        $inventory->refresh();
        $this->assertEquals(80, $inventory->quantity_available);

        // Verify movement was recorded
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'movement_type' => 'sale',
        ]);
    }

    public function test_product_deletion_workflow()
    {
        // Create product with related data
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        ProductImage::factory()->count(2)->create([
            'product_id' => $product->id,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
        ]);

        // Delete product
        $response = $this->actingAs($this->staffUser)
            ->delete(route('staff.products.destroy', $product));

        $response->assertRedirect(route('staff.products.index'));

        // Verify soft delete
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        // Verify related data still exists
        $this->assertEquals(2, ProductImage::where('product_id', $product->id)->count());
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id]);
    }

    public function test_bulk_operations_workflow()
    {
        // Create multiple products
        $products = Product::factory()->count(5)->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        foreach ($products as $product) {
            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 50,
            ]);
        }

        $productIds = $products->pluck('id')->toArray();

        // Bulk status update
        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.products.bulk-status'), [
                'product_ids' => $productIds,
                'status' => 'inactive',
            ]);

        $response->assertRedirect();

        // Verify all products updated
        foreach ($productIds as $id) {
            $this->assertDatabaseHas('products', [
                'id' => $id,
                'status' => 'inactive',
            ]);
        }

        // Bulk inventory update
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
                'notes' => 'Bulk adjustment',
            ]);

        $response->assertRedirect();

        // Verify inventory updated
        foreach ($products as $product) {
            $this->assertDatabaseHas('inventories', [
                'product_id' => $product->id,
                'quantity_available' => 100,
            ]);
        }
    }

    public function test_category_and_brand_management_workflow()
    {
        // Create category
        $categoryData = [
            'name' => 'Workflow Category',
            'description' => 'Test category',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.categories.store'), $categoryData);

        $response->assertRedirect();
        
        $category = Category::where('name', 'Workflow Category')->first();
        $this->assertNotNull($category);

        // Create brand
        $brandData = [
            'name' => 'Workflow Brand',
            'description' => 'Test brand',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.brands.store'), $brandData);

        $response->assertRedirect();
        
        $brand = Brand::where('name', 'Workflow Brand')->first();
        $this->assertNotNull($brand);

        // Create product with new category and brand
        $productData = [
            'name' => 'Product with New Category',
            'sku' => 'PWNC-001',
            'description' => 'Test product',
            'price' => 49.99,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.products.store'), $productData);

        $response->assertRedirect();

        // Verify product created with correct relationships
        $product = Product::where('sku', 'PWNC-001')->first();
        $this->assertEquals($category->id, $product->category_id);
        $this->assertEquals($brand->id, $product->brand_id);
    }

    public function test_low_stock_alert_workflow()
    {
        // Create products with varying stock levels
        $lowStockProduct = Product::factory()->create([
            'name' => 'Low Stock Product',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        Inventory::factory()->create([
            'product_id' => $lowStockProduct->id,
            'quantity_available' => 5,
            'reorder_level' => 10,
        ]);

        $normalStockProduct = Product::factory()->create([
            'name' => 'Normal Stock Product',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        Inventory::factory()->create([
            'product_id' => $normalStockProduct->id,
            'quantity_available' => 100,
            'reorder_level' => 10,
        ]);

        // View inventory dashboard
        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.inventory.index'));

        $response->assertStatus(200);
        $response->assertViewHas('lowStockAlerts');

        // Restock low stock product
        $response = $this->actingAs($this->staffUser)
            ->put(route('staff.inventory.update', $lowStockProduct), [
                'quantity_available' => 50,
                'movement_type' => 'restock',
                'notes' => 'Restocking low stock item',
            ]);

        $response->assertRedirect();

        // Verify stock updated
        $this->assertDatabaseHas('inventories', [
            'product_id' => $lowStockProduct->id,
            'quantity_available' => 50,
        ]);

        // Verify movement recorded
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $lowStockProduct->id,
            'movement_type' => 'restock',
            'quantity' => 45,
        ]);
    }
}
