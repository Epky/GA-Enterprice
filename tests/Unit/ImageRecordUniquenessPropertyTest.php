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

/**
 * Feature: product-image-upload-duplication, Property 3: Image record uniqueness
 * 
 * For any uploaded file, exactly one database record should be created in the product_images table
 * Validates: Requirements 1.2
 */
class ImageRecordUniquenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    private ImageUploadService $imageUploadService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imageUploadService = new ImageUploadService();
        Storage::fake('public');
    }

    /**
     * Property: For any set of files (including duplicates), each unique file should create exactly one database record
     * 
     * This test generates random sets of files, including intentional duplicates,
     * and verifies that duplicate files are detected and only one record is created per unique file.
     */
    public function test_duplicate_files_create_single_database_record()
    {
        // Run the property test multiple times with different random inputs
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Create a fresh product for each iteration
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
            ]);

            // Generate random number of unique files (1-5)
            $uniqueFileCount = rand(1, 5);
            $uniqueFiles = [];
            
            for ($i = 0; $i < $uniqueFileCount; $i++) {
                $uniqueFiles[] = UploadedFile::fake()->image("unique_{$i}.jpg", 800, 600);
            }

            // Generate random number of duplicate copies (0-3 duplicates per file)
            $allFiles = [];
            foreach ($uniqueFiles as $file) {
                $duplicateCount = rand(0, 3);
                
                // Add the original file
                $allFiles[] = $file;
                
                // Add duplicate copies (same file object multiple times)
                for ($d = 0; $d < $duplicateCount; $d++) {
                    $allFiles[] = $file;
                }
            }

            // Shuffle the array to randomize order
            shuffle($allFiles);

            // Get initial count
            $initialCount = ProductImage::where('product_id', $product->id)->count();

            // Upload all files (including duplicates)
            $uploadedImages = $this->imageUploadService->uploadProductImages($product, $allFiles);

            // Get final count
            $finalCount = ProductImage::where('product_id', $product->id)->count();

            // Property: The number of created records should equal the number of unique files
            $createdRecords = $finalCount - $initialCount;
            
            $this->assertEquals(
                $uniqueFileCount,
                $createdRecords,
                "Expected {$uniqueFileCount} unique records but got {$createdRecords} " .
                "(iteration {$iteration}, total files: " . count($allFiles) . ")"
            );

            // Property: The number of returned images should equal the number of unique files
            $this->assertEquals(
                $uniqueFileCount,
                count($uploadedImages),
                "Expected {$uniqueFileCount} uploaded images but got " . count($uploadedImages) .
                " (iteration {$iteration})"
            );

            // Clean up for next iteration
            ProductImage::where('product_id', $product->id)->delete();
            $product->delete();
            $category->delete();
            $brand->delete();
        }
    }

    /**
     * Property: Uploading the same file multiple times in a single batch should create only one record
     * 
     * This is a specific edge case test for the most common duplication scenario.
     */
    public function test_same_file_uploaded_three_times_creates_one_record()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        // Create a single file
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        // Upload the same file three times (simulating the original bug)
        $files = [$file, $file, $file];

        $uploadedImages = $this->imageUploadService->uploadProductImages($product, $files);

        // Property: Only one record should be created
        $this->assertEquals(1, count($uploadedImages), 'Expected exactly 1 uploaded image');
        
        $recordCount = ProductImage::where('product_id', $product->id)->count();
        $this->assertEquals(1, $recordCount, 'Expected exactly 1 database record');
    }

    /**
     * Property: Different files should all be uploaded (no false positives in duplicate detection)
     * 
     * This ensures our duplicate detection doesn't incorrectly flag different files as duplicates.
     */
    public function test_different_files_all_create_records()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 50; $iteration++) {
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
            ]);

            // Generate random number of different files (1-10)
            $fileCount = rand(1, 10);
            $files = [];
            
            for ($i = 0; $i < $fileCount; $i++) {
                // Create files with different dimensions to ensure they're different
                $width = rand(200, 1000);
                $height = rand(200, 1000);
                $files[] = UploadedFile::fake()->image("file_{$i}.jpg", $width, $height);
            }

            $uploadedImages = $this->imageUploadService->uploadProductImages($product, $files);

            // Property: All different files should be uploaded
            $this->assertEquals(
                $fileCount,
                count($uploadedImages),
                "Expected {$fileCount} uploaded images but got " . count($uploadedImages) .
                " (iteration {$iteration})"
            );

            $recordCount = ProductImage::where('product_id', $product->id)->count();
            $this->assertEquals(
                $fileCount,
                $recordCount,
                "Expected {$fileCount} database records but got {$recordCount} (iteration {$iteration})"
            );

            // Clean up
            ProductImage::where('product_id', $product->id)->delete();
            $product->delete();
            $category->delete();
            $brand->delete();
        }
    }

    /**
     * Property: Display order should be sequential without gaps even when duplicates are skipped
     * 
     * This validates Requirement 1.4 - proper ordering when files are skipped.
     */
    public function test_display_order_sequential_when_duplicates_skipped()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        // Create files: unique, duplicate, unique, duplicate, unique
        $file1 = UploadedFile::fake()->image('file1.jpg', 800, 600);
        $file2 = UploadedFile::fake()->image('file2.jpg', 800, 600);
        $file3 = UploadedFile::fake()->image('file3.jpg', 800, 600);

        $files = [$file1, $file1, $file2, $file2, $file3];

        $uploadedImages = $this->imageUploadService->uploadProductImages($product, $files);

        // Should have 3 unique images
        $this->assertEquals(3, count($uploadedImages));

        // Get all images ordered by display_order
        $images = ProductImage::where('product_id', $product->id)
            ->orderBy('display_order')
            ->get();

        // Property: Display orders should be sequential (1, 2, 3) with no gaps
        $expectedOrders = [1, 2, 3];
        $actualOrders = $images->pluck('display_order')->toArray();

        $this->assertEquals(
            $expectedOrders,
            $actualOrders,
            'Display orders should be sequential without gaps'
        );
    }
}
