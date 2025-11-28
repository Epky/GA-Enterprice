# Design Document

## Overview

This design consolidates the product detail page views to ensure a consistent user experience across all entry points. The solution involves standardizing on the existing `shop.show` view template, which already has a modern, feature-rich design with proper handling for both authenticated and guest users.

## Architecture

### Current State

The application currently has two different product detail implementations:

1. **shop.show** - Used by ShopController for public product browsing
   - Route: `/products/{product}`
   - Modern gradient design with comprehensive features
   - Handles both authenticated and guest users
   - Includes image gallery, tabs, and related products

2. **customer.product-detail** - Used by CustomerController for authenticated users
   - Route: `/customer/products/{product}`
   - Simpler design focused on authenticated users
   - Less visual polish compared to shop.show

### Target State

Consolidate to use only the `shop.show` view template for all product detail pages:

- ShopController continues using `shop.show`
- CustomerController redirects to ShopController's route or uses the same view
- Single source of truth for product detail presentation
- Consistent user experience across all entry points

## Components and Interfaces

### Controllers

#### ShopController
```php
public function show(Product $product)
{
    $product->load(['category', 'brand', 'images', 'specifications', 'inventory']);
    
    $relatedProducts = Product::with(['images', 'brand'])
        ->where('category_id', $product->category_id)
        ->where('id', '!=', $product->id)
        ->where('status', 'active')
        ->limit(4)
        ->get();
    
    return view('shop.show', compact('product', 'relatedProducts'));
}
```

#### CustomerController
```php
public function show(Product $product)
{
    // Redirect to the shop route for consistency
    return redirect()->route('products.show', $product);
}
```

### Routes

**Existing Routes:**
- `GET /products/{product}` → ShopController@show (name: 'products.show')
- `GET /customer/products/{product}` → CustomerController@show (name: 'customer.product.show')

**Solution:** Keep both routes but have CustomerController redirect to ShopController's route.

### View Template

Use `resources/views/shop/show.blade.php` as the single template. This template already includes:

1. **Breadcrumb Navigation** - Shows navigation path
2. **Image Gallery** - Main image with thumbnail grid
3. **Product Information** - Name, brand, price, stock status
4. **Authentication Handling** - Different CTAs for guests vs authenticated users
5. **Tabbed Content** - Description and specifications
6. **Related Products** - Category-based recommendations

## Data Models

### Product Model

Required relationships and accessors:
- `images` - Product images collection
- `category` - Product category
- `brand` - Product brand
- `specifications` - Product specifications
- `inventory` - Stock information
- `in_stock` - Accessor for stock availability
- `total_stock` - Accessor for total available quantity

### Stock Calculation

```php
// In Product model
public function getInStockAttribute()
{
    return $this->inventory->sum('quantity_available') > 0;
}

public function getTotalStockAttribute()
{
    return $this->inventory->sum('quantity_available');
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: View Template Consistency
*For any* product, when accessed through ShopController or CustomerController, the system should use the same view template name
**Validates: Requirements 2.2**

### Property 2: Guest User Access
*For any* product detail page request made by a guest user, the system should return a successful response with all product information visible
**Validates: Requirements 3.1**

### Property 3: Guest User Login Prompt
*For any* product detail page rendered for a guest user, the HTML output should contain a "Login to Purchase" button and should not contain an "Add to Cart" button
**Validates: Requirements 3.2**

### Property 4: Authenticated User Cart Access
*For any* product detail page rendered for an authenticated user, the HTML output should contain an "Add to Cart" button with quantity input field
**Validates: Requirements 3.3**

### Property 5: Required Information Presence
*For any* product detail page, the rendered HTML should contain elements for product images, price, stock status, and description
**Validates: Requirements 1.5**

### Property 6: Stock Status Accuracy
*For any* product with inventory data, the displayed stock status should match the calculated total available quantity across all inventory locations
**Validates: Requirements 3.4**

### Property 7: Related Products Display
*For any* product with at least one other product in the same category, the rendered page should include a related products section
**Validates: Requirements 3.5**

### Property 8: Breadcrumb Navigation
*For any* product detail page, the rendered HTML should contain breadcrumb navigation elements
**Validates: Requirements 1.4**

## Error Handling

### Missing Product Data

**Scenario:** Product has no images, specifications, or related products

**Handling:**
- Display placeholder image for products without images
- Hide specifications tab if no specifications exist
- Hide related products section if no related products found
- Ensure page remains functional and visually acceptable

### Stock Calculation Errors

**Scenario:** Inventory data is missing or corrupted

**Handling:**
- Default to "Out of Stock" status if inventory cannot be calculated
- Disable "Add to Cart" button
- Log error for investigation
- Display user-friendly message

### Route Conflicts

**Scenario:** Both customer and shop routes exist for product detail

**Handling:**
- CustomerController redirects to ShopController route
- Preserve any query parameters during redirect
- Use 301 (permanent redirect) to indicate canonical URL

## Testing Strategy

### Unit Tests

1. **Controller Tests**
   - Test ShopController returns correct view with required data
   - Test CustomerController redirects to shop route
   - Test product data loading with all relationships

2. **View Tests**
   - Test authenticated user sees "Add to Cart" button
   - Test guest user sees "Login to Purchase" button
   - Test stock status display for in-stock products
   - Test stock status display for out-of-stock products
   - Test image gallery renders correctly
   - Test specifications tab appears when specifications exist
   - Test related products section appears when related products exist

3. **Model Tests**
   - Test `in_stock` accessor returns correct boolean
   - Test `total_stock` accessor calculates sum correctly
   - Test stock calculation with multiple inventory locations

### Integration Tests

1. **Navigation Flow Tests**
   - Test clicking product from home page shows correct view
   - Test clicking product from products page shows correct view
   - Test clicking product from category page shows correct view
   - Test breadcrumb navigation reflects correct path

2. **Authentication Flow Tests**
   - Test guest user can view product details
   - Test guest user sees login prompt for purchase
   - Test authenticated user can add to cart
   - Test cart functionality works from product detail page

### Manual Testing Checklist

1. Navigate to product from home page - verify design
2. Navigate to same product from /products page - verify identical design
3. Navigate to same product from category page - verify identical design
4. Test as guest user - verify "Login to Purchase" appears
5. Test as authenticated user - verify "Add to Cart" appears
6. Test with product that has no images - verify placeholder
7. Test with product that has no specifications - verify tab hidden
8. Test with product that has no related products - verify section hidden
9. Verify breadcrumb navigation updates correctly
10. Verify image gallery thumbnail switching works
