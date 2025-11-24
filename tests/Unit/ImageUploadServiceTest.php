<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ImageUploadService;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImageUploadService $imageUploadService;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imageUploadService = new ImageUploadService();
        
        Storage::fake('public');
        
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
    }

    public function test_image_upload_service_can_be_instantiated()
    {
        $this->assertInstanceOf(ImageUploadService::class, $this->imageUploadService);
    }

    public function test_can_upload_single_image()
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $result = $this->imageUploadService->uploadProductImage($this->product, $file);

        $this->assertInstanceOf(ProductImage::class, $result);
        $this->assertDatabaseHas('product_images', [
            'product_id' => $this->product->id,
        ]);
    }

    public function test_can_upload_multiple_images()
    {
        $files = [
            UploadedFile::fake()->image('test1.jpg'),
            UploadedFile::fake()->image('test2.jpg'),
            UploadedFile::fake()->image('test3.jpg'),
        ];

        $results = $this->imageUploadService->uploadMultipleImages($this->product, $files);

        $this->assertCount(3, $results);
        $this->assertEquals(3, ProductImage::where('product_id', $this->product->id)->count());
    }

    public function test_can_delete_image()
    {
        $image = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'image_url' => 'products/' . $this->product->id . '/test.jpg',
        ]);

        Storage::disk('public')->put($image->image_url, 'fake content');

        $result = $this->imageUploadService->deleteImage($image);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
    }

    public function test_can_set_primary_image()
    {
        $image1 = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'is_primary' => true,
        ]);

        $image2 = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'is_primary' => false,
        ]);

        $result = $this->imageUploadService->setPrimaryImage($image2);

        $this->assertTrue($result);
        
        $image1->refresh();
        $image2->refresh();
        
        $this->assertFalse($image1->is_primary);
        $this->assertTrue($image2->is_primary);
    }

    public function test_can_reorder_images()
    {
        $images = ProductImage::factory()->count(3)->create([
            'product_id' => $this->product->id,
        ]);

        $order = [
            ['id' => $images[2]->id, 'order' => 1],
            ['id' => $images[0]->id, 'order' => 2],
            ['id' => $images[1]->id, 'order' => 3],
        ];

        $result = $this->imageUploadService->reorderImages($order);

        $this->assertTrue($result);
        
        $this->assertDatabaseHas('product_images', [
            'id' => $images[2]->id,
            'display_order' => 1,
        ]);
    }

    public function test_validates_image_file_type()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $result = $this->imageUploadService->validateImageFile($file);

        $this->assertFalse($result);
    }

    public function test_validates_image_file_size()
    {
        $file = UploadedFile::fake()->image('large.jpg')->size(6000);

        $result = $this->imageUploadService->validateImageFile($file);

        $this->assertFalse($result);
    }

    public function test_accepts_valid_image_formats()
    {
        $jpgFile = UploadedFile::fake()->image('test.jpg');
        $pngFile = UploadedFile::fake()->image('test.png');

        $this->assertTrue($this->imageUploadService->validateImageFile($jpgFile));
        $this->assertTrue($this->imageUploadService->validateImageFile($pngFile));
    }
}
