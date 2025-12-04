<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AdminProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $staffUser;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->staffUser = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);

        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);

        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
    }

    public function test_admin_can_upload_product_images()
    {
        $file = UploadedFile::fake()->image('product.jpg', 600, 600);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.products.images.upload', $this->product), [
                'images' => [$file],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Images uploaded successfully',
        ]);

        $this->assertDatabaseHas('product_images', [
            'product_id' => $this->product->id,
        ]);
    }

    public function test_admin_can_delete_product_image()
    {
        $image = ProductImage::factory()->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('admin.products.images.delete', $image->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Image deleted successfully',
        ]);

        $this->assertDatabaseMissing('product_images', [
            'id' => $image->id,
        ]);
    }

    public function test_admin_can_set_primary_image()
    {
        $image1 = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'is_primary' => true,
        ]);

        $image2 = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'is_primary' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.products.images.set-primary', $image2->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Primary image updated successfully',
        ]);

        $this->assertDatabaseHas('product_images', [
            'id' => $image2->id,
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('product_images', [
            'id' => $image1->id,
            'is_primary' => false,
        ]);
    }

    public function test_staff_cannot_access_admin_image_upload()
    {
        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->actingAs($this->staffUser)
            ->postJson(route('admin.products.images.upload', $this->product), [
                'images' => [$file],
            ]);

        $response->assertStatus(403);
    }

    public function test_upload_validates_image_files()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.products.images.upload', $this->product), [
                'images' => [$file],
            ]);

        $response->assertStatus(422);
    }

    public function test_upload_validates_file_size()
    {
        $file = UploadedFile::fake()->image('large.jpg')->size(6000); // 6MB

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.products.images.upload', $this->product), [
                'images' => [$file],
            ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_toggle_product_featured_status()
    {
        // Initially not featured
        $this->product->is_featured = false;
        $this->product->save();

        $response = $this->actingAs($this->adminUser)
            ->patch(route('admin.products.toggle-featured', $this->product));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'is_featured' => true,
        ]);

        // Toggle back to unfeatured
        $response = $this->actingAs($this->adminUser)
            ->patch(route('admin.products.toggle-featured', $this->product));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'is_featured' => false,
        ]);
    }

    public function test_staff_cannot_toggle_product_featured_status()
    {
        $response = $this->actingAs($this->staffUser)
            ->patch(route('admin.products.toggle-featured', $this->product));

        // Staff users are redirected by the role.redirect middleware
        $response->assertRedirect();
    }
}
