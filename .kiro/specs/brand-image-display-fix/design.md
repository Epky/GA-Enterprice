# Design Document

## Overview

This design addresses the brand logo display issue in the staff brand management interface. The fix ensures brand logos display correctly by implementing the same image handling pattern used successfully in the category management system. The solution includes fixing the storage path resolution, ensuring proper fallback to placeholders, and correcting the product count attribute names.

## Architecture

The fix involves three main components:

1. **View Layer**: Update the brand index blade template to use correct image path resolution
2. **Model Layer**: Ensure Brand model provides consistent accessor methods for image URLs
3. **Controller Layer**: Verify the controller loads data with correct attribute names

The architecture follows Laravel's MVC pattern and maintains consistency with the existing category implementation.

## Components and Interfaces

### Brand Model
- **Purpose**: Provide data access and business logic for brands
- **Key Methods**:
  - `logo_url` attribute: Stores the relative path to the logo image
  - Accessor methods for product counts (if needed)
- **Dependencies**: Eloquent Model base class

### StaffBrandController
- **Purpose**: Handle HTTP requests for brand management
- **Key Methods**:
  - `index()`: Load brands with product counts using `withCount()`
- **Data Loading**: Uses `withCount(['products', 'activeProducts'])` which creates `products_count` and `active_products_count` attributes

### Brand Index View
- **Purpose**: Display brand cards with logos and information
- **Key Elements**:
  - Brand logo image display with fallback
  - Product count displays
  - Status badges
  - Action buttons

## Data Models

### Brand Model Attributes
```php
- id: integer
- name: string
- slug: string
- description: text (nullable)
- logo_url: string (nullable) // Relative path from storage/app/public
- website_url: string (nullable)
- is_active: boolean
- created_at: timestamp
- updated_at: timestamp
```

### Computed Attributes (from withCount)
```php
- products_count: integer // Total products
- active_products_count: integer // Active products only
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Image path resolution consistency
*For any* brand with a logo_url value, the displayed image src should resolve to the same storage path pattern used by categories
**Validates: Requirements 1.1, 1.2, 3.1**

### Property 2: Placeholder fallback
*For any* brand without a logo_url or with a missing image file, the system should display a placeholder icon without throwing errors
**Validates: Requirements 1.3, 1.4, 3.2**

### Property 3: Product count attribute accuracy
*For any* brand loaded with withCount, accessing products_count and active_products_count should return the correct integer values
**Validates: Requirements 2.1, 2.2, 2.3**

### Property 4: Zero product count display
*For any* brand with no associated products, both products_count and active_products_count should equal 0
**Validates: Requirements 2.4**

### Property 5: Image styling consistency
*For any* brand logo displayed, the CSS classes and sizing should match the category image display pattern
**Validates: Requirements 1.5, 3.3**

## Error Handling

### Missing Image Files
- **Scenario**: Brand has logo_url but file doesn't exist
- **Handling**: Display placeholder icon, no error thrown
- **Implementation**: Use `@if` blade directive to check for file existence or use CSS fallback

### Invalid Image Paths
- **Scenario**: logo_url contains invalid path
- **Handling**: Display placeholder icon
- **Implementation**: Validate path format in model accessor

### Database Query Errors
- **Scenario**: withCount() fails or returns unexpected data
- **Handling**: Log error, display 0 for counts
- **Implementation**: Use null coalescing operator in view

## Testing Strategy

### Unit Tests
- Test Brand model accessor methods return correct values
- Test image path resolution with various logo_url values
- Test placeholder display when logo_url is null
- Test product count attributes from withCount()

### Property-Based Tests
- **Property 1 Test**: Generate random brands with various logo_url values, verify all resolve to correct storage paths
- **Property 2 Test**: Generate random brands with null/invalid logo_url, verify all display placeholders
- **Property 3 Test**: Generate random brands with varying product counts, verify counts display accurately
- **Property 4 Test**: Generate brands with zero products, verify both counts equal 0
- **Property 5 Test**: Generate random brands, verify all logos use consistent CSS classes

### Integration Tests
- Test brand index page loads without errors
- Test brand logos display correctly for brands with images
- Test placeholder displays for brands without images
- Test product counts display correctly

### Manual Testing Checklist
1. View brand index page with brands that have logos
2. View brand index page with brands without logos
3. Verify logos display at correct size
4. Verify product counts are accurate
5. Compare with category index page for consistency
6. Test with missing image files
7. Test with various image formats (jpg, png, svg)

## Implementation Notes

### Image Path Resolution
The category implementation uses Laravel's `asset()` or `Storage::url()` helper to resolve image paths. The brand implementation should follow the same pattern:

```php
// Category pattern (working)
@if($category->image_url)
    <img src="{{ asset('storage/' . $category->image_url) }}" ...>
@else
    <div class="placeholder">...</div>
@endif
```

### Product Count Attributes
The controller uses `withCount(['products', 'activeProducts'])` which creates:
- `products_count` (not `product_count`)
- `active_products_count` (not `active_product_count`)

The view must use these exact attribute names.

### Storage Configuration
Ensure the `storage/app/public` directory is linked to `public/storage`:
```bash
php artisan storage:link
```

### Image Upload Path
When uploading brand logos, ensure they're stored in a consistent location:
- Recommended: `storage/app/public/brands/`
- Path stored in DB: `brands/filename.ext`
- Full URL: `asset('storage/brands/filename.ext')`
