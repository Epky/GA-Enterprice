<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a staff user
        $this->staffUser = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);

        // Create necessary related models
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);

        // Create a product
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        // Fake storage
        Storage::fake('public');
    }

    public function test_staff_can_upload_product_images(): void
    {
        $file = UploadedFile::fake()->image('product.jpg', 800, 600);

        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.products.images.upload', $this->product), [
                'images' => [$file],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Images uploaded successfully',
            ]);

        // Verify image was created in database
        $this->assertDatabaseHas('product_images', [
            'product_id' => $this->product->id,
        ]);

        // Verify file was stored
        $image = ProductImage::where('product_id', $this->product->id)->first();
        $this->assertTrue(Storage::disk('public')->exists($image->image_url));
    }

    public function test_staff_can_delete_product_image(): void
    {
        // Create an image
        $image = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'image_url' => 'products/' . $this->product->id . '/test.jpg',
        ]);

        // Create a fake file in storage
        Storage::disk('public')->put($image->image_url, 'fake content');

        $response = $this->actingAs($this->staffUser)
            ->deleteJson(route('staff.products.images.delete', $image));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Image deleted successfully',
            ]);

        // Verify image was deleted from database
        $this->assertDatabaseMissing('product_images', [
            'id' => $image->id,
        ]);

        // Verify file was deleted from storage
        $this->assertFalse(Storage::disk('public')->exists($image->image_url));
    }

    public function test_staff_can_set_primary_image(): void
    {
        // Create multiple images
        $image1 = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'is_primary' => true,
        ]);

        $image2 = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'is_primary' => false,
        ]);

        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.products.images.set-primary', $image2));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Primary image updated successfully',
            ]);

        // Verify image2 is now primary
        $this->assertDatabaseHas('product_images', [
            'id' => $image2->id,
            'is_primary' => true,
        ]);

        // Verify image1 is no longer primary
        $this->assertDatabaseHas('product_images', [
            'id' => $image1->id,
            'is_primary' => false,
        ]);
    }

    public function test_staff_can_reorder_product_images(): void
    {
        // Create multiple images
        $image1 = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'display_order' => 1,
        ]);

        $image2 = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'display_order' => 2,
        ]);

        $image3 = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'display_order' => 3,
        ]);

        // Reorder: swap image1 and image3
        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.products.images.reorder', $this->product), [
                'order' => [
                    ['id' => $image3->id, 'order' => 1],
                    ['id' => $image2->id, 'order' => 2],
                    ['id' => $image1->id, 'order' => 3],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Image order updated successfully',
            ]);

        // Verify new order
        $this->assertDatabaseHas('product_images', [
            'id' => $image3->id,
            'display_order' => 1,
        ]);

        $this->assertDatabaseHas('product_images', [
            'id' => $image1->id,
            'display_order' => 3,
        ]);
    }

    public function test_image_upload_validates_file_type(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.products.images.upload', $this->product), [
                'images' => [$file],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['images.0']);
    }

    public function test_image_upload_validates_file_size(): void
    {
        // Create a file larger than 5MB
        $file = UploadedFile::fake()->image('large.jpg')->size(6000);

        $response = $this->actingAs($this->staffUser)
            ->postJson(route('staff.products.images.upload', $this->product), [
                'images' => [$file],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['images.0']);
    }

    public function test_non_staff_cannot_manage_images(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $image = ProductImage::factory()->create([
            'product_id' => $this->product->id,
        ]);

        // Try to delete image as customer
        $response = $this->actingAs($customer)
            ->deleteJson(route('staff.products.images.delete', $image));

        $response->assertStatus(403);
    }
}
