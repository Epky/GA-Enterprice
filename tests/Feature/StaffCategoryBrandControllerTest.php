<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StaffCategoryBrandControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;
    private User $customerUser;

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

        Storage::fake('public');
    }

    // Category Tests
    public function test_staff_can_view_category_list()
    {
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.categories.index'));

        $response->assertStatus(200);
        $response->assertViewIs('staff.categories.index');
        $response->assertViewHas('categories');
    }

    public function test_customer_cannot_access_category_list()
    {
        $response = $this->actingAs($this->customerUser)
            ->get(route('staff.categories.index'));

        $response->assertStatus(403);
    }

    public function test_staff_can_create_category()
    {
        $categoryData = [
            'name' => 'New Category',
            'description' => 'Category description',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.categories.store'), $categoryData);

        $response->assertRedirect(route('staff.categories.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'New Category',
        ]);
    }

    public function test_staff_can_update_category()
    {
        $category = Category::factory()->create();

        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated description',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->staffUser)
            ->put(route('staff.categories.update', $category), $updateData);

        $response->assertRedirect(route('staff.categories.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category Name',
        ]);
    }

    public function test_staff_can_delete_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->staffUser)
            ->delete(route('staff.categories.destroy', $category));

        $response->assertRedirect(route('staff.categories.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_staff_can_create_subcategory()
    {
        $parentCategory = Category::factory()->create();

        $categoryData = [
            'name' => 'Subcategory',
            'description' => 'Subcategory description',
            'parent_id' => $parentCategory->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.categories.store'), $categoryData);

        $response->assertRedirect(route('staff.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Subcategory',
            'parent_id' => $parentCategory->id,
        ]);
    }

    // Brand Tests
    public function test_staff_can_view_brand_list()
    {
        Brand::factory()->count(3)->create();

        $response = $this->actingAs($this->staffUser)
            ->get(route('staff.brands.index'));

        $response->assertStatus(200);
        $response->assertViewIs('staff.brands.index');
        $response->assertViewHas('brands');
    }

    public function test_customer_cannot_access_brand_list()
    {
        $response = $this->actingAs($this->customerUser)
            ->get(route('staff.brands.index'));

        $response->assertStatus(403);
    }

    public function test_staff_can_create_brand()
    {
        $brandData = [
            'name' => 'New Brand',
            'description' => 'Brand description',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.brands.store'), $brandData);

        $response->assertRedirect(route('staff.brands.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('brands', [
            'name' => 'New Brand',
        ]);
    }

    public function test_staff_can_create_brand_with_logo()
    {
        $logo = UploadedFile::fake()->image('logo.jpg');

        $brandData = [
            'name' => 'Brand With Logo',
            'description' => 'Brand description',
            'logo' => $logo,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.brands.store'), $brandData);

        $response->assertRedirect(route('staff.brands.index'));

        $brand = Brand::where('name', 'Brand With Logo')->first();
        $this->assertNotNull($brand);
        $this->assertNotNull($brand->logo_url);
    }

    public function test_staff_can_update_brand()
    {
        $brand = Brand::factory()->create();

        $updateData = [
            'name' => 'Updated Brand Name',
            'description' => 'Updated description',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->staffUser)
            ->put(route('staff.brands.update', $brand), $updateData);

        $response->assertRedirect(route('staff.brands.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Updated Brand Name',
        ]);
    }

    public function test_staff_can_delete_brand()
    {
        $brand = Brand::factory()->create();

        $response = $this->actingAs($this->staffUser)
            ->delete(route('staff.brands.destroy', $brand));

        $response->assertRedirect(route('staff.brands.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('brands', ['id' => $brand->id]);
    }

    public function test_staff_can_toggle_brand_status()
    {
        $brand = Brand::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.brands.toggle-status', $brand));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'is_active' => false,
        ]);
    }

    public function test_category_name_must_be_unique()
    {
        Category::factory()->create(['name' => 'Existing Category']);

        $categoryData = [
            'name' => 'Existing Category',
            'description' => 'Description',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.categories.store'), $categoryData);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_brand_name_must_be_unique()
    {
        Brand::factory()->create(['name' => 'Existing Brand']);

        $brandData = [
            'name' => 'Existing Brand',
            'description' => 'Description',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->staffUser)
            ->post(route('staff.brands.store'), $brandData);

        $response->assertSessionHasErrors(['name']);
    }
}
