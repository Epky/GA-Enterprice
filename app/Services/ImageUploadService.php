<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageUploadService
{
    /**
     * Allowed image MIME types.
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    /**
     * Maximum file size in bytes (5MB).
     */
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;

    /**
     * Image dimensions for different sizes.
     */
    private const IMAGE_SIZES = [
        'original' => null,
        'large' => ['width' => 800, 'height' => 800],
        'medium' => ['width' => 400, 'height' => 400],
        'thumbnail' => ['width' => 150, 'height' => 150],
    ];

    /**
     * Upload multiple images for a product.
     * Optimized to process images in batches for better performance.
     */
    public function uploadProductImages(Product $product, array $files, array $options = []): array
    {
        $uploadedImages = [];
        $startOrder = $this->getNextDisplayOrder($product);

        DB::transaction(function () use ($product, $files, $options, &$uploadedImages, $startOrder) {
            foreach ($files as $index => $file) {
                if ($file instanceof UploadedFile) {
                    $uploadedImages[] = $this->uploadSingleProductImage(
                        $product,
                        $file,
                        array_merge($options, [
                            'alt_text' => $options['alt_texts'][$index] ?? null,
                            'display_order' => $startOrder + $index,
                        ])
                    );
                }
            }
        });

        return $uploadedImages;
    }

    /**
     * Upload a single image for a product.
     */
    public function uploadSingleProductImage(Product $product, UploadedFile $file, array $options = []): ProductImage
    {
        // Validate the uploaded file
        $this->validateImageFile($file);

        return DB::transaction(function () use ($product, $file, $options) {
            // Generate unique filename
            $filename = $this->generateUniqueFilename($file);
            
            // Create directory path
            $directory = "products/{$product->id}";
            
            // Store the original image
            $originalPath = $this->storeImage($file, $directory, $filename);

            // Generate different sizes (if image processing is available)
            $imagePaths = $this->generateImageSizes($file, $directory, $filename);

            // Create database record
            $productImage = ProductImage::create([
                'product_id' => $product->id,
                'image_url' => $originalPath,
                'alt_text' => $options['alt_text'] ?? $product->name,
                'display_order' => $options['display_order'] ?? $this->getNextDisplayOrder($product),
                'is_primary' => $options['is_primary'] ?? false,
            ]);

            return $productImage;
        });
    }

    /**
     * Update image order for a product.
     * Optimized to use bulk update with case statement.
     */
    public function updateImageOrder(Product $product, array $imageOrder): bool
    {
        return DB::transaction(function () use ($product, $imageOrder) {
            // Build case statement for bulk update
            $cases = [];
            $ids = [];
            
            foreach ($imageOrder as $order => $imageId) {
                $cases[] = "WHEN {$imageId} THEN " . ($order + 1);
                $ids[] = $imageId;
            }
            
            if (empty($cases)) {
                return true;
            }
            
            $idsString = implode(',', $ids);
            $caseString = implode(' ', $cases);
            
            // Execute bulk update
            DB::update(
                "UPDATE product_images 
                SET display_order = CASE id {$caseString} END 
                WHERE product_id = ? AND id IN ({$idsString})",
                [$product->id]
            );
            
            return true;
        });
    }

    /**
     * Set primary image for a product.
     */
    public function setPrimaryImage(Product $product, ProductImage $image): ProductImage
    {
        if ($image->product_id !== $product->id) {
            throw ValidationException::withMessages([
                'image' => 'Image does not belong to this product.'
            ]);
        }

        return DB::transaction(function () use ($product, $image) {
            // Remove primary flag from all other images
            ProductImage::where('product_id', $product->id)
                ->where('id', '!=', $image->id)
                ->update(['is_primary' => false]);

            // Set this image as primary
            $image->update(['is_primary' => true]);

            return $image;
        });
    }

    /**
     * Delete a product image.
     */
    public function deleteProductImage(ProductImage $image): bool
    {
        return DB::transaction(function () use ($image) {
            // Delete files from storage
            $this->deleteImageFiles($image->image_url);

            // Delete database record
            return $image->delete();
        });
    }

    /**
     * Delete all images for a product.
     * Optimized to batch delete files and database records.
     */
    public function deleteAllProductImages(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            // Get all image paths first
            $imagePaths = ProductImage::where('product_id', $product->id)
                ->pluck('image_url')
                ->toArray();

            // Delete all files
            $disk = Storage::disk('public');
            foreach ($imagePaths as $path) {
                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
            }

            // Delete all database records in one query
            return ProductImage::where('product_id', $product->id)->delete();
        });
    }

    /**
     * Get optimized image URL for different sizes.
     */
    public function getOptimizedImageUrl(ProductImage $image, string $size = 'medium'): string
    {
        // For now, return the original image URL
        // This can be enhanced when image processing is implemented
        return $image->image_url;
    }

    /**
     * Validate uploaded image file.
     */
    private function validateImageFile(UploadedFile $file): void
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw ValidationException::withMessages([
                'image' => 'The uploaded file is not valid.'
            ]);
        }

        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw ValidationException::withMessages([
                'image' => 'The image file size must not exceed 5MB.'
            ]);
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw ValidationException::withMessages([
                'image' => 'The image must be a file of type: jpeg, jpg, png, webp.'
            ]);
        }

        // Check if it's actually an image
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw ValidationException::withMessages([
                'image' => 'The uploaded file is not a valid image.'
            ]);
        }

        // Check image dimensions (optional minimum requirements)
        if ($imageInfo[0] < 100 || $imageInfo[1] < 100) {
            throw ValidationException::withMessages([
                'image' => 'The image dimensions must be at least 100x100 pixels.'
            ]);
        }
    }

    /**
     * Generate unique filename for the image.
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);
        
        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Store image file to storage.
     */
    private function storeImage(UploadedFile $file, string $directory, string $filename): string
    {
        $path = $file->storeAs($directory, $filename, 'public');
        
        if (!$path) {
            throw ValidationException::withMessages([
                'image' => 'Failed to store the image file.'
            ]);
        }

        return $path;
    }

    /**
     * Generate different image sizes (placeholder for future enhancement).
     */
    private function generateImageSizes(UploadedFile $file, string $directory, string $filename): array
    {
        // This is a placeholder for when Intervention Image is installed
        // For now, we'll just store the original image
        
        $imagePaths = [];
        
        // Store original
        $imagePaths['original'] = $this->storeImage($file, $directory, $filename);
        
        // TODO: Implement image resizing when Intervention Image is available
        // foreach (self::IMAGE_SIZES as $size => $dimensions) {
        //     if ($dimensions) {
        //         $resizedFilename = $this->getResizedFilename($filename, $size);
        //         $imagePaths[$size] = $this->resizeAndStore($file, $directory, $resizedFilename, $dimensions);
        //     }
        // }

        return $imagePaths;
    }

    /**
     * Get the next display order for a product's images.
     * Cached to reduce database queries during bulk uploads.
     */
    private function getNextDisplayOrder(Product $product): int
    {
        static $orderCache = [];
        
        if (!isset($orderCache[$product->id])) {
            $orderCache[$product->id] = ProductImage::where('product_id', $product->id)
                ->max('display_order') ?? 0;
        }

        return $orderCache[$product->id] + 1;
    }

    /**
     * Delete image files from storage.
     * Optimized to check existence before deletion.
     */
    private function deleteImageFiles(string $imagePath): void
    {
        $disk = Storage::disk('public');
        
        // Delete the main image only if it exists
        if ($disk->exists($imagePath)) {
            $disk->delete($imagePath);
        }

        // TODO: Delete different sizes when implemented
        // $directory = dirname($imagePath);
        // $filename = basename($imagePath);
        // 
        // foreach (array_keys(self::IMAGE_SIZES) as $size) {
        //     if ($size !== 'original') {
        //         $sizedPath = $directory . '/' . $this->getResizedFilename($filename, $size);
        //         if ($disk->exists($sizedPath)) {
        //             $disk->delete($sizedPath);
        //         }
        //     }
        // }
    }

    /**
     * Get filename for resized image (placeholder for future use).
     */
    private function getResizedFilename(string $originalFilename, string $size): string
    {
        $pathInfo = pathinfo($originalFilename);
        return $pathInfo['filename'] . "_{$size}." . $pathInfo['extension'];
    }

    /**
     * Resize and store image (placeholder for future implementation).
     */
    private function resizeAndStore(UploadedFile $file, string $directory, string $filename, array $dimensions): string
    {
        // This will be implemented when Intervention Image is available
        // For now, just return the original path
        return $this->storeImage($file, $directory, $filename);
    }

    /**
     * Get image statistics for a product.
     */
    public function getImageStats(Product $product): array
    {
        $images = $product->images;
        
        return [
            'total_images' => $images->count(),
            'primary_image' => $images->where('is_primary', true)->first(),
            'total_size' => $this->calculateTotalImageSize($images),
        ];
    }

    /**
     * Calculate total size of all images for a product.
     * Optimized to use Storage facade for better performance.
     */
    private function calculateTotalImageSize($images): int
    {
        $totalSize = 0;
        $disk = Storage::disk('public');
        
        foreach ($images as $image) {
            if ($disk->exists($image->image_url)) {
                $totalSize += $disk->size($image->image_url);
            }
        }
        
        return $totalSize;
    }
}