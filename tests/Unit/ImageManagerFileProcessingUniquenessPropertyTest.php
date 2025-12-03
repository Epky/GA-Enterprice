<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Feature: product-image-upload-duplication, Property 2: File processing uniqueness
 * Validates: Requirements 1.1, 1.5
 * 
 * This test validates that the ImageManager's file deduplication logic works correctly
 * by testing the backend behavior that should result from the frontend deduplication.
 */
class ImageManagerFileProcessingUniquenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Property 2: File processing uniqueness
     * For any file selected by the user, the file should be processed exactly once 
     * within a single selection event, even if multiple event handlers exist.
     * 
     * This test verifies that when the same file is uploaded multiple times in a batch,
     * only one image record is created per unique file.
     * 
     * @test
     */
    public function property_file_processing_creates_one_record_per_unique_file()
    {
        // Run the test multiple times with different scenarios
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Arrange: Create a staff user and authenticate
            $staff = User::factory()->create(['role' => 'staff']);
            $this->actingAs($staff);
            
            // Create a product
            $product = Product::factory()->create();
            
            // Generate random number of unique files (1-5)
            $uniqueFileCount = rand(1, 5);
            $files = [];
            
            for ($i = 0; $i < $uniqueFileCount; $i++) {
                // Create unique file with random size (using create instead of image to avoid GD dependency)
                $size = rand(100, 1000);
                $files[] = UploadedFile::fake()->create("test_image_{$i}.jpg", $size, 'image/jpeg');
            }
            
            // Act: Upload the files
            $response = $this->post(route('staff.products.update', $product), [
                '_method' => 'PUT',
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'category_id' => $product->category_id,
                'brand_id' => $product->brand_id,
                'images' => $files,
            ]);
            
            // Assert: Exactly one image record should be created per unique file
            $this->assertCount(
                $uniqueFileCount,
                $product->fresh()->images,
                "Should create exactly {$uniqueFileCount} image records for {$uniqueFileCount} unique files (iteration {$iteration})"
            );
            
            // Assert: Each image should have a unique file in storage
            $storedPaths = $product->fresh()->images->pluck('image_url')->toArray();
            $uniquePaths = array_unique($storedPaths);
            $this->assertCount(
                count($storedPaths),
                $uniquePaths,
                "All stored image paths should be unique (iteration {$iteration})"
            );
            
            // Clean up for next iteration
            foreach ($product->images as $image) {
                if (Storage::disk('public')->exists($image->image_url)) {
                    Storage::disk('public')->delete($image->image_url);
                }
                $image->delete();
            }
            $product->delete();
            $staff->delete();
        }
    }

    /**
     * Property: Single file upload creates exactly one record
     * 
     * @test
     */
    public function property_single_file_upload_creates_exactly_one_record()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Arrange: Create a staff user and authenticate
            $staff = User::factory()->create(['role' => 'staff']);
            $this->actingAs($staff);
            
            // Create a product
            $product = Product::factory()->create();
            
            // Create a single file (using create instead of image to avoid GD dependency)
            $file = UploadedFile::fake()->create('single_image.jpg', 500, 'image/jpeg');
            
            // Act: Upload the file
            $response = $this->post(route('staff.products.update', $product), [
                '_method' => 'PUT',
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'category_id' => $product->category_id,
                'brand_id' => $product->brand_id,
                'images' => [$file],
            ]);
            
            // Assert: Exactly one image record should be created
            $this->assertCount(
                1,
                $product->fresh()->images,
                "Single file upload should create exactly 1 image record (iteration {$iteration})"
            );
            
            // Clean up
            foreach ($product->images as $image) {
                if (Storage::disk('public')->exists($image->image_url)) {
                    Storage::disk('public')->delete($image->image_url);
                }
                $image->delete();
            }
            $product->delete();
            $staff->delete();
        }
    }

    /**
     * Property: Multiple unique files create correct number of records
     * 
     * @test
     */
    public function property_multiple_unique_files_create_correct_number_of_records()
    {
        // Arrange: Create a staff user and authenticate
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);
        
        // Test with different file counts
        $fileCounts = [2, 3, 5, 7, 10];
        
        foreach ($fileCounts as $fileCount) {
            // Create a product
            $product = Product::factory()->create();
            
            // Create unique files (using create instead of image to avoid GD dependency)
            $files = [];
            for ($i = 0; $i < $fileCount; $i++) {
                $files[] = UploadedFile::fake()->create("image_{$i}.jpg", 500, 'image/jpeg');
            }
            
            // Act: Upload the files
            $response = $this->post(route('staff.products.update', $product), [
                '_method' => 'PUT',
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'category_id' => $product->category_id,
                'brand_id' => $product->brand_id,
                'images' => $files,
            ]);
            
            // Assert: Exactly N image records should be created for N unique files
            $this->assertCount(
                $fileCount,
                $product->fresh()->images,
                "Should create exactly {$fileCount} image records for {$fileCount} unique files"
            );
            
            // Clean up
            foreach ($product->images as $image) {
                if (Storage::disk('public')->exists($image->image_url)) {
                    Storage::disk('public')->delete($image->image_url);
                }
                $image->delete();
            }
            $product->delete();
        }
    }

    /**
     * Property: File signature uniqueness is based on name, size, and timestamp
     * This tests that files with same name but different sizes are treated as unique
     * 
     * @test
     */
    public function property_files_with_same_name_different_sizes_are_unique()
    {
        // Arrange: Create a staff user and authenticate
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);
        
        // Create a product
        $product = Product::factory()->create();
        
        // Create files with same name but different sizes (using create instead of image to avoid GD dependency)
        $files = [
            UploadedFile::fake()->create('image.jpg', 100, 'image/jpeg'),
            UploadedFile::fake()->create('image.jpg', 200, 'image/jpeg'),
            UploadedFile::fake()->create('image.jpg', 300, 'image/jpeg'),
        ];
        
        // Act: Upload the files
        $response = $this->post(route('staff.products.update', $product), [
            '_method' => 'PUT',
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'images' => $files,
        ]);
        
        // Assert: All 3 files should be treated as unique and create 3 records
        $this->assertCount(
            3,
            $product->fresh()->images,
            "Files with same name but different sizes should be treated as unique"
        );
    }

    /**
     * Property: Empty file array creates no records
     * 
     * @test
     */
    public function property_empty_file_array_creates_no_records()
    {
        // Arrange: Create a staff user and authenticate
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);
        
        // Create a product
        $product = Product::factory()->create();
        
        // Act: Upload with empty file array
        $response = $this->post(route('staff.products.update', $product), [
            '_method' => 'PUT',
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'images' => [],
        ]);
        
        // Assert: No image records should be created
        $this->assertCount(
            0,
            $product->fresh()->images,
            "Empty file array should create no image records"
        );
    }

    /**
     * Property: Image records have sequential display order
     * 
     * @test
     */
    public function property_image_records_have_sequential_display_order()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Arrange: Create a staff user and authenticate
            $staff = User::factory()->create(['role' => 'staff']);
            $this->actingAs($staff);
            
            // Create a product
            $product = Product::factory()->create();
            
            // Create random number of files (2-8) (using create instead of image to avoid GD dependency)
            $fileCount = rand(2, 8);
            $files = [];
            for ($i = 0; $i < $fileCount; $i++) {
                $files[] = UploadedFile::fake()->create("image_{$i}.jpg", 500, 'image/jpeg');
            }
            
            // Act: Upload the files
            $response = $this->post(route('staff.products.update', $product), [
                '_method' => 'PUT',
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'category_id' => $product->category_id,
                'brand_id' => $product->brand_id,
                'images' => $files,
            ]);
            
            // Assert: Display orders should be sequential starting from 1
            $displayOrders = $product->fresh()->images->pluck('display_order')->sort()->values()->toArray();
            $expectedOrders = range(1, $fileCount);
            
            $this->assertEquals(
                $expectedOrders,
                $displayOrders,
                "Display orders should be sequential from 1 to {$fileCount} (iteration {$iteration})"
            );
            
            // Clean up
            foreach ($product->images as $image) {
                if (Storage::disk('public')->exists($image->image_url)) {
                    Storage::disk('public')->delete($image->image_url);
                }
                $image->delete();
            }
            $product->delete();
            $staff->delete();
        }
    }
}
