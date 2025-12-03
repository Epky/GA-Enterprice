<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'image_url',
        'alt_text',
        'display_order',
        'is_primary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the product that owns the image.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope a query to only include primary images.
     */
    public function scopePrimary(Builder $query): void
    {
        $query->where('is_primary', true);
    }

    /**
     * Scope a query to order images by display order.
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('display_order')->orderBy('id');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // When setting an image as primary, ensure no other images for the same product are primary
        static::saving(function ($image) {
            if ($image->is_primary && $image->product_id) {
                static::where('product_id', $image->product_id)
                      ->where('id', '!=', $image->id)
                      ->update(['is_primary' => false]);
            }
        });

        // If this is the first image for a product, make it primary
        static::created(function ($image) {
            if (!$image->is_primary && $image->product_id) {
                $hasOtherPrimary = static::where('product_id', $image->product_id)
                                        ->where('is_primary', true)
                                        ->exists();
                
                if (!$hasOtherPrimary) {
                    $image->update(['is_primary' => true]);
                }
            }
        });

        // When deleting a primary image, set another image as primary if available
        static::deleting(function ($image) {
            if ($image->is_primary && $image->product_id) {
                $nextImage = static::where('product_id', $image->product_id)
                                  ->where('id', '!=', $image->id)
                                  ->orderBy('display_order')
                                  ->first();
                
                if ($nextImage) {
                    $nextImage->update(['is_primary' => true]);
                }
            }
        });
    }

    /**
     * Get the full URL for the image.
     */
    public function getFullUrlAttribute(): string
    {
        // If the image_url is already a full URL, return it as is
        if (filter_var($this->image_url, FILTER_VALIDATE_URL)) {
            return $this->image_url;
        }

        // Check if file exists, if not return placeholder image
        if (!Storage::disk('public')->exists($this->image_url)) {
            // Return a placeholder image URL or empty string
            return asset('images/placeholder-product.png');
        }

        // Generate the correct URL using asset() helper which respects current domain
        return asset('storage/' . $this->image_url);
    }
    
    /**
     * Check if the image file exists on disk.
     */
    public function fileExists(): bool
    {
        if (filter_var($this->image_url, FILTER_VALIDATE_URL)) {
            return true; // Assume external URLs are valid
        }
        
        return Storage::disk('public')->exists($this->image_url);
    }

    /**
     * Get the thumbnail URL for the image.
     */
    public function getThumbnailUrlAttribute(): string
    {
        // This would typically generate a thumbnail version
        // For now, return the same URL - can be enhanced later
        return $this->full_url;
    }
}