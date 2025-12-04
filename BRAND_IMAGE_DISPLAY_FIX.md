# Brand Image Display Fix

## Problem
Brand images were not displaying properly in the staff/brands index page, while category images were working correctly.

## Root Cause Analysis

### Issue 1: Missing Image Relationship Loading
The `StaffBrandController::index()` method was not loading product images:
```php
// Before - No image relationships loaded
$query = Brand::withCount(['products', 'activeProducts']);
```

The `StaffCategoryController::index()` method was correctly loading images:
```php
// Working example from categories
$query = Category::with([
    'products.primaryImage:id,product_id,image_url,is_primary'
]);
```

### Issue 2: Missing Display Accessor
The `Brand` model lacked a `getDisplayLogoAttribute()` accessor to provide fallback images from products when the brand doesn't have its own logo.

The `Category` model had this functionality:
```php
public function getDisplayImageAttribute(): ?string
{
    if ($this->image_url) {
        return $this->image_url;
    }
    
    // Fallback to product image
    $product = $this->products()
        ->where('status', 'active')
        ->whereHas('images')
        ->with('primaryImage')
        ->first();
        
    return $product?->primaryImage?->image_url;
}
```

### Issue 3: View Not Using Accessor
The view was directly checking `$brand->logo_url` instead of using a display accessor that could provide fallback images.

## Solution Implemented

### 1. Added Display Logo Accessor to Brand Model
**File:** `app/Models/Brand.php`

Added `getDisplayLogoAttribute()` method that:
- Returns the brand's own logo if available
- Falls back to the first product image from active products
- Returns null if no images are available

```php
public function getDisplayLogoAttribute(): ?string
{
    // First check if brand has its own logo
    if ($this->logo_url) {
        return $this->logo_url;
    }

    // Otherwise, get the first product image from active products
    $product = $this->products()
        ->where('status', 'active')
        ->whereHas('images')
        ->with('primaryImage')
        ->first();

    return $product?->primaryImage?->image_url ?? $product?->images?->first()?->image_url;
}
```

### 2. Updated Controller to Load Product Images
**File:** `app/Http/Controllers/Staff/StaffBrandController.php`

Modified the `index()` method to eager load product images:

```php
$query = Brand::withCount(['products', 'activeProducts'])
    ->with([
        'products' => function($q) {
            $q->where('status', 'active')
              ->whereHas('images')
              ->with('primaryImage:id,product_id,image_url,is_primary')
              ->limit(1);
        }
    ]);
```

This ensures:
- Only active products with images are loaded
- Only the primary image is loaded for performance
- Only one product per brand is loaded (sufficient for display)
- Selective column loading reduces memory usage

### 3. Updated View to Use Display Accessor
**File:** `resources/views/staff/brands/index.blade.php`

Changed from:
```blade
@if($brand->logo_url)
    <img src="{{ asset('storage/' . $brand->logo_url) }}" ...>
```

To:
```blade
@if($brand->display_logo)
    <img src="{{ asset('storage/' . $brand->display_logo) }}" ...>
```

## Benefits

1. **Consistent Behavior**: Brands now display images the same way categories do
2. **Better UX**: Brands without logos show product images instead of placeholder icons
3. **Performance Optimized**: Selective eager loading prevents N+1 queries
4. **Maintainable**: Uses Laravel's accessor pattern for clean, reusable code

## Testing

To verify the fix:

1. Navigate to `/staff/brands`
2. Brands with logos should display their logo
3. Brands without logos but with products should display the first product image
4. Brands without logos or products should display the placeholder icon
5. No N+1 query issues should occur (check query count in debug bar)

## Files Modified

1. `app/Models/Brand.php` - Added `getDisplayLogoAttribute()` accessor
2. `app/Http/Controllers/Staff/StaffBrandController.php` - Added eager loading of product images
3. `resources/views/staff/brands/index.blade.php` - Updated to use `display_logo` accessor

## Related Spec

This fix aligns with the spec at `.kiro/specs/brand-image-display-fix/`
