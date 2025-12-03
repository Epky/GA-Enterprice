# Product Image Display Fix

## Issue
Product images were not displaying on the staff product pages (`/staff/products/{id}` and `/staff/products/{id}/edit`).

## Root Cause
The issue was in the `image-manager.blade.php` component. The component was wrapping the `$image->full_url` attribute with the `asset()` helper function:

```php
<img src="{{ asset($image->full_url) }}" ...>
```

Since `$image->full_url` already returns a complete URL (e.g., `http://127.0.0.1:8000/storage/products/1/image.png`), wrapping it with `asset()` created a malformed double URL like:
```
http://127.0.0.1:8000/http://127.0.0.1:8000/storage/products/1/image.png
```

This caused the browser to fail loading the images, resulting in broken image displays.

## Solution
### 1. Fixed the Image Manager Component
**File:** `resources/views/components/image-manager.blade.php`

Changed line 18 from:
```php
<img src="{{ asset($image->full_url) }}" 
```

To:
```php
<img src="{{ $image->full_url }}" 
```

### 2. Improved ProductImage Model
**File:** `app/Models/ProductImage.php`

Updated the `getFullUrlAttribute()` method to:
- Use `asset()` helper instead of `Storage::url()` for better domain handling
- Return a placeholder image URL when the file doesn't exist (instead of empty string)

```php
public function getFullUrlAttribute(): string
{
    // If the image_url is already a full URL, return it as is
    if (filter_var($this->image_url, FILTER_VALIDATE_URL)) {
        return $this->image_url;
    }

    // Check if file exists, if not return placeholder image
    if (!Storage::disk('public')->exists($this->image_url)) {
        return asset('images/placeholder-product.png');
    }

    // Generate the correct URL using asset() helper
    return asset('storage/' . $this->image_url);
}
```

### 3. Cleared Caches
Ran the following commands to ensure changes take effect:
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Verification
The images are now properly accessible at:
- Storage location: `storage/app/public/products/{product_id}/{image_file}`
- Public URL: `http://127.0.0.1:8000/storage/products/{product_id}/{image_file}`
- Symlink: `public/storage` → `storage/app/public` (already configured)

## Testing
To verify the fix:
1. Visit `/staff/products/1` - Images should now display correctly
2. Visit `/staff/products/1/edit` - Navigate to Step 2 (Images) - Images should display correctly
3. Check browser console - No 404 errors for image files

## Additional Fixes
### Currency Symbol Update
Changed all dollar signs ($) to peso signs (₱) in the product show page:
- **File:** `resources/views/staff/products/show.blade.php`
- Fixed variant price adjustments display
- Fixed revenue display
- Changed from `$0.00` to `₱0.00`
- Changed from `${{ number_format(...) }}` to `₱{{ number_format(...) }}`

## Additional Notes
- The storage symlink was already properly configured (`php artisan storage:link`)
- Image files exist in the correct location (`storage/app/public/products/`)
- The issue was purely a URL generation problem in the Blade template
- Currency symbols have been standardized to Philippine Peso (₱) throughout the staff product pages
