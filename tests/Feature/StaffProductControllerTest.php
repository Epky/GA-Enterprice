<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StaffProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;
    private User $customerUser;
    private Category $category;
    private Brand $brand;

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
    }

    public function test_staff_can_view_product_list()
    {
        Product::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.products.index'));

        $response->assertStatus(200);
        $response->assertViewIs('staff.products.index');
        $response->assertViewHas('products');
    }

    public function test_customer_cannot_access_product_list()
    {
        $response = $this->actingAs($this->customerUser)
            ->get(route('staff.products.index'));

        $response->assertStatus(403);
    }

    public function test_staff_can_view_create_product_form()
    {
        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.products.create'));

        $response->assertStatus(200);
        $response->assertViewIs('staff.products.create');
    }

    public function test_staff_can_create_product()
    {
        $productData = [
            'name' => 'New Test Product',
            'sku' => 'TEST-NEW-001',
            'description' => 'Test product description',
            'price' => 49.99,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.products.store'), $productData);

        $response->assertRedirect(route('staff.products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-NEW-001',
            'name' => 'New Test Product',
        ]);
    }

    public function test_staff_cannot_create_product_with_invalid_data()
    {
        $productData = [
            'name' => '',
            'sku' => '',
            'price' => -10,
        ];

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.products.store'), $productData);

        $response->assertSessionHasErrors(['name', 'sku', 'price']);
    }

    public function test_staff_can_view_product_details()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.products.show', $product));

        $response->assertStatus(200);
        $response->assertViewIs('staff.products.show');
        $response->assertViewHas('product');
    }

    public function test_staff_can_view_edit_product_form()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.products.edit', $product));

        $response->assertStatus(200);
        $response->assertViewIs('staff.products.edit');
        $response->assertViewHas('product');
    }

    public function test_staff_can_update_product()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $updateData = [
            'name' => 'Updated Product Name',
            'sku' => $product->sku,
            'description' => 'Updated description',
            'price' => 59.99,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->staffUser)
            ->put(route('staff.products.update', $product), $updateData);

        $response->assertRedirect(route('staff.products.show', $product));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'price' => 59.99,
        ]);
    }

    public function test_staff_can_delete_product()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->delete(route('staff.products.destroy', $product));

        $response->assertRedirect(route('staff.products.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_staff_can_search_products()
    {
        Product::factory()->create([
            'name' => 'Lipstick Red',
            'sku' => 'LIP-001',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        Product::factory()->create([
            'name' => 'Foundation Beige',
            'sku' => 'FND-001',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.products.index', ['search' => 'Lipstick']));

        $response->assertStatus(200);
        $response->assertSee('Lipstick Red');
        $response->assertDontSee('Foundation Beige');
    }

    public function test_staff_can_filter_products_by_status()
    {
        Product::factory()->create([
            'name' => 'Active Product',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        Product::factory()->create([
            'name' => 'Inactive Product',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.products.index', ['status' => 'active']));

        $response->assertStatus(200);
        $response->assertSee('Active Product');
    }

    public function test_staff_can_bulk_update_product_status()
    {
        $products = Product::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        $productIds = $products->pluck('id')->toArray();

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.products.bulk-status'), [
                'product_ids' => $productIds,
                'status' => 'inactive',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        foreach ($productIds as $id) {
            $this->assertDatabaseHas('products', [
                'id' => $id,
                'status' => 'inactive',
            ]);
        }
    }
}
