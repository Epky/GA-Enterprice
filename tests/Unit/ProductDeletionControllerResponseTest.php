<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductDeletionControllerResponseTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Brand $brand;
    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);
        
        // Create a staff user for authentication
        $this->staffUser = User::factory()->create([
            'role' => 'staff',
            'email' => 'staff@test.com',
        ]);
    }

    public function test_successful_deletion_returns_redirect()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
        ]);

        $response = $this->actingAs($this->staffUser)
            ->delete(route('staff.products.destroy', $product));

        // Verify redirect to product list
        $response->assertRedirect(route('staff.products.index'));
    }

    public function test_successful_deletion_includes_success_message()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
        ]);

        $response = $this->actingAs($this->staffUser)
            ->delete(route('staff.products.destroy', $product));

        // Verify success message is in session
        $response->assertSessionHas('success', 'Product deleted successfully.');
    }

    public function test_deletion_of_nonexistent_product_returns_404()
    {
        // Try to delete a product that doesn't exist
        $nonExistentId = 99999;

        $response = $this->actingAs($this->staffUser)
            ->delete(route('staff.products.destroy', $nonExistentId));

        // Verify 404 response
        $response->assertNotFound();
    }

    public function test_successful_deletion_removes_product_from_database()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
        ]);

        $productId = $product->id;

        $this->actingAs($this->staffUser)
            ->delete(route('staff.products.destroy', $product));

        // Verify product is soft deleted
        $this->assertSoftDeleted('products', [
            'id' => $productId,
        ]);
    }

    public function test_deletion_requires_authentication()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
        ]);

        // Try to delete without authentication
        $response = $this->delete(route('staff.products.destroy', $product));

        // Verify redirect to login
        $response->assertRedirect(route('login'));
    }

    public function test_deletion_with_stock_still_succeeds()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
        ]);

        // Add inventory
        $product->inventory()->create([
            'quantity_available' => 100,
            'quantity_reserved' => 20,
            'reorder_level' => 10,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->delete(route('staff.products.destroy', $product));

        // Verify successful deletion despite having stock
        $response->assertRedirect(route('staff.products.index'));
        $response->assertSessionHas('success', 'Product deleted successfully.');
    }

    public function test_deletion_response_has_correct_status_code()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
        ]);

        $response = $this->actingAs($this->staffUser)
            ->delete(route('staff.products.destroy', $product));

        // Verify redirect status code (302)
        $response->assertStatus(302);
    }
}
