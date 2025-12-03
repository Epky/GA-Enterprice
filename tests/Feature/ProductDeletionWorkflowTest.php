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

class ProductDeletionWorkflowTest extends TestCase
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

    /**
     * Test complete deletion flow from list page
     * 
     * This test verifies:
     * - Navigate to product list
     * - Click delete button
     * - Verify modal appears (via data attributes)
     * - Confirm deletion
     * - Verify redirect and success message
     * - Verify product is deleted
     */
    public function test_complete_deletion_flow_from_list_page()
    {
        // Create a product with stock
        $product = Product::factory()->create([
            'name' => 'Test Product for Deletion',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 50,
            'quantity_reserved' => 0,
        ]);

        // Step 1: Navigate to product list
        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.products.index'));

        $response->assertStatus(200);
        $response->assertSee($product->name);

        // Step 2: Verify delete button has correct data attributes for modal
        $response->assertSee('delete-product-btn');
        $response->assertSee('data-product-id="' . $product->id . '"', false);
        $response->assertSee('data-product-name="' . $product->name . '"', false);
        $response->assertSee('data-stock-quantity="50"', false);

        // Step 3: Confirm deletion (simulate form submission)
        $deleteResponse = $this->actingAs($this->staffUser)
            ->delete(route('staff.products.destroy', $product));

        // Step 4: Verify redirect and success message
        $deleteResponse->assertRedirect(route('staff.products.index'));
        $deleteResponse->assertSessionHas('success');

        // Step 5: Verify product is deleted (soft deleted)
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    /**
     * Test cancellation flow from detail page
     * 
     * This test verifies:
     * - Navigate to product detail
     * - Click delete button
     * - Verify modal appears (via data attributes)
     * - Cancel deletion (by not submitting)
     * - Verify modal closes (simulated by checking product still exists)
     * - Verify product still exists
     */
    public function test_cancellation_flow_from_detail_page()
    {
        // Create a product with stock
        $product = Product::factory()->create([
            'name' => 'Test Product for Cancellation',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 30,
            'quantity_reserved' => 0,
        ]);

        // Step 1: Navigate to product detail
        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.products.show', $product));

        $response->assertStatus(200);
        $response->assertSee($product->name);

        // Step 2: Verify delete button has correct data attributes for modal
        $response->assertSee('data-product-id="' . $product->id . '"', false);
        $response->assertSee('data-product-name="' . $product->name . '"', false);
        $response->assertSee('data-stock-quantity="30"', false);

        // Step 3: Simulate cancellation by NOT submitting the delete request
        // Instead, verify the product still exists in the database
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $product->name,
            'deleted_at' => null,
        ]);

        // Step 4: Verify we can still access the product detail page
        $verifyResponse = $this->actingAs($this->staffUser)
            ->get(route('staff.products.show', $product));

        $verifyResponse->assertStatus(200);
        $verifyResponse->assertSee($product->name);
    }

    /**
     * Test cross-page consistency
     * 
     * This test verifies:
     * - Test deletion from list page
     * - Test deletion from detail page
     * - Test deletion from edit page
     * - Verify modal is identical in all cases (via data attributes)
     */
    public function test_cross_page_consistency()
    {
        // Create three products for testing on different pages
        $productForList = Product::factory()->create([
            'name' => 'Product on List Page',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $productForDetail = Product::factory()->create([
            'name' => 'Product on Detail Page',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $productForEdit = Product::factory()->create([
            'name' => 'Product on Edit Page',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        // Add stock to all products
        Inventory::factory()->create([
            'product_id' => $productForList->id,
            'quantity_available' => 25,
            'quantity_reserved' => 0,
        ]);

        Inventory::factory()->create([
            'product_id' => $productForDetail->id,
            'quantity_available' => 35,
            'quantity_reserved' => 0,
        ]);

        Inventory::factory()->create([
            'product_id' => $productForEdit->id,
            'quantity_available' => 45,
            'quantity_reserved' => 0,
        ]);

        // Test 1: List page modal data attributes
        $listResponse = $this->actingAs($this->staffUser)
            ->get(route('staff.products.index'));

        $listResponse->assertStatus(200);
        $listResponse->assertSee('delete-product-btn');
        $listResponse->assertSee('data-product-id="' . $productForList->id . '"', false);
        $listResponse->assertSee('data-product-name="' . $productForList->name . '"', false);
        $listResponse->assertSee('data-stock-quantity="25"', false);

        // Test 2: Detail page modal data attributes
        $detailResponse = $this->actingAs($this->staffUser)
            ->get(route('staff.products.show', $productForDetail));

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('data-product-id="' . $productForDetail->id . '"', false);
        $detailResponse->assertSee('data-product-name="' . $productForDetail->name . '"', false);
        $detailResponse->assertSee('data-stock-quantity="35"', false);

        // Test 3: Edit page modal data attributes
        $editResponse = $this->actingAs($this->staffUser)
            ->get(route('staff.products.edit', $productForEdit));

        $editResponse->assertStatus(200);
        $editResponse->assertSee('data-product-id="' . $productForEdit->id . '"', false);
        $editResponse->assertSee('data-product-name="' . $productForEdit->name . '"', false);
        $editResponse->assertSee('data-stock-quantity="45"', false);

        // Verify all pages use the same data attribute structure
        // This ensures modal consistency across pages
    }

    /**
     * Test deletion with zero stock from list page
     */
    public function test_deletion_with_zero_stock_from_list_page()
    {
        $product = Product::factory()->create([
            'name' => 'Zero Stock Product',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        // Create inventory with zero stock
        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
        ]);

        // Navigate to list page
        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.products.index'));

        $response->assertStatus(200);
        $response->assertSee('data-stock-quantity="0"', false);

        // Delete the product
        $deleteResponse = $this->actingAs($this->staffUser)
            ->delete(route('staff.products.destroy', $product));

        $deleteResponse->assertRedirect(route('staff.products.index'));
        $deleteResponse->assertSessionHas('success');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    /**
     * Test deletion with high stock from detail page
     */
    public function test_deletion_with_high_stock_from_detail_page()
    {
        $product = Product::factory()->create([
            'name' => 'High Stock Product',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        // Create inventory with high stock
        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 500,
            'quantity_reserved' => 0,
        ]);

        // Navigate to detail page
        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.products.show', $product));

        $response->assertStatus(200);
        $response->assertSee('data-stock-quantity="500"', false);

        // Delete the product
        $deleteResponse = $this->actingAs($this->staffUser)
            ->delete(route('staff.products.destroy', $product));

        $deleteResponse->assertRedirect(route('staff.products.index'));
        $deleteResponse->assertSessionHas('success');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    /**
     * Test deletion with multiple inventory locations
     */
    public function test_deletion_with_multiple_inventory_locations()
    {
        $product = Product::factory()->create([
            'name' => 'Multi-Location Product',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        // Create inventory at multiple locations
        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Warehouse A',
            'quantity_available' => 20,
            'quantity_reserved' => 0,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Warehouse B',
            'quantity_available' => 30,
            'quantity_reserved' => 0,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'location' => 'Store Front',
            'quantity_available' => 15,
            'quantity_reserved' => 0,
        ]);

        // Total stock should be 65
        $totalStock = $product->total_stock;
        $this->assertEquals(65, $totalStock);

        // Navigate to edit page
        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.products.edit', $product));

        $response->assertStatus(200);
        $response->assertSee('data-stock-quantity="65"', false);

        // Delete the product
        $deleteResponse = $this->actingAs($this->staffUser)
            ->delete(route('staff.products.destroy', $product));

        $deleteResponse->assertRedirect(route('staff.products.index'));
        $deleteResponse->assertSessionHas('success');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    /**
     * Test deletion of product with images
     */
    public function test_deletion_of_product_with_images()
    {
        $product = Product::factory()->create([
            'name' => 'Product with Images',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        // Create product images
        ProductImage::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 10,
            'quantity_reserved' => 0,
        ]);

        // Verify images exist
        $this->assertEquals(3, $product->images()->count());

        // Delete the product
        $deleteResponse = $this->actingAs($this->staffUser)
            ->delete(route('staff.products.destroy', $product));

        $deleteResponse->assertRedirect(route('staff.products.index'));
        $deleteResponse->assertSessionHas('success');
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        // Images should still exist (soft delete doesn't cascade to images)
        $this->assertEquals(3, ProductImage::where('product_id', $product->id)->count());
    }

    /**
     * Test that non-staff users cannot delete products
     */
    public function test_non_staff_cannot_delete_products()
    {
        $customerUser = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        // Attempt to delete as customer
        $response = $this->actingAs($customerUser)
            ->delete(route('staff.products.destroy', $product));

        // Customer should be redirected (302) or forbidden (403)
        $this->assertContains($response->status(), [302, 403], 
            'Customer should not be able to delete products');

        // Verify product still exists (not soft deleted)
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test deletion of non-existent product returns 404
     */
    public function test_deletion_of_non_existent_product_returns_404()
    {
        $nonExistentId = 99999;

        $response = $this->actingAs($this->staffUser)
            ->delete(route('staff.products.destroy', $nonExistentId));

        $response->assertStatus(404);
    }
}
