# Design Document

## Overview

Ang landing page product display feature ay mag-transform ng customer dashboard landing page mula sa category-focused layout tungo sa product-focused layout. Ang design na ito ay nakatuon sa pagpapakita ng mga products na may high-quality images, clear pricing, at madaling navigation. Ang existing functionality para sa search, filtering, at sorting ay mapapanatili at mapapahusay.

## Architecture

Ang feature na ito ay gumagamit ng existing Laravel MVC architecture:

- **Controller Layer**: `CustomerController` - handles product retrieval, filtering, sorting, at pagination
- **Model Layer**: `Product`, `Category`, `Brand`, `Inventory` - data models with relationships
- **View Layer**: Blade templates - responsive product grid layout
- **Service Layer**: Walang bagong service needed, existing models ay sufficient

Ang data flow:
1. Customer visits landing page
2. Controller retrieves products with filters/sorting
3. Products are paginated (12 per page)
4. View renders product grid with images
5. Customer interacts with filters/sorting
6. Page reloads with updated product list

## Components and Interfaces

### CustomerController

Existing controller na may `dashboard()` method:
- Handles GET requests to customer dashboard
- Accepts query parameters: search, category, brand, min_price, max_price, sort
- Returns view with products, featuredProducts, categories, brands

Key responsibilities:
- Validate filter inputs
- Build product query with filters
- Apply sorting logic
- Paginate results (12 per page)
- Load featured products (max 4)
- Pass data to view

### Product Model

Existing model with relationships:
- `category()` - belongsTo Category
- `brand()` - belongsTo Brand
- `primaryImage()` - hasOne ProductImage
- `images()` - hasMany ProductImage
- `inventory()` - hasMany Inventory

Key attributes used:
- `name`, `description`, `sku`
- `base_price`
- `status` (active/inactive)
- `is_featured` (boolean)

### View Components

**Main Dashboard View** (`customer.dashboard`):
- Hero section with search bar
- Featured products section (conditional)
- Sidebar filters
- Product grid
- Pagination

**Product Card Component**:
- Product image (or placeholder)
- Category label
- Product name
- Description (truncated)
- Price
- "View Details" button
- Out of stock overlay (conditional)
- Featured badge (conditional)

## Data Models

### Product
```php
- id: integer
- name: string
- description: text
- sku: string
- base_price: decimal
- status: enum (active, inactive)
- is_featured: boolean
- category_id: foreign key
- brand_id: foreign key
- created_at: timestamp
```

### ProductImage
```php
- id: integer
- product_id: foreign key
- image_url: string
- is_primary: boolean
```

### Inventory
```php
- id: integer
- product_id: foreign key
- location: string
- quantity_available: integer
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*


### Property Reflection

After reviewing the prework, several properties can be consolidated:
- Properties 3.2, 3.3, and 3.4 (category, brand, price filters) can be combined into a general filter application property
- Properties 4.1, 4.2, 4.3, 4.4 (sorting options) can be combined into a general sorting property
- Properties 3.5 and 4.5 (filter/sort preservation) can be combined into a single state preservation property

### Product Display Properties

Property 1: Product card completeness
*For any* product displayed in the grid, the rendered HTML should contain the product name, price, category name, and either a primary image URL or placeholder image indicator
**Validates: Requirements 1.2**

Property 2: Placeholder image display
*For any* product without a primary image, the rendered product card should contain a placeholder image element
**Validates: Requirements 1.3**

Property 3: Product card navigation
*For any* product displayed in the grid, the product card should contain a valid link to that product's detail page
**Validates: Requirements 1.5**

### Featured Products Properties

Property 4: Featured products limit
*For any* set of featured products, the landing page should display a maximum of 4 featured products regardless of how many products are marked as featured
**Validates: Requirements 2.2**

Property 5: Featured badge presence
*For any* product marked as featured and displayed in the featured section, the rendered HTML should contain a featured badge indicator
**Validates: Requirements 2.3**

Property 6: Featured product card consistency
*For any* featured product, the product card should contain the same data fields (name, price, category, image) as regular product cards
**Validates: Requirements 2.5**

### Filter and Search Properties

Property 7: Search filter application
*For any* search term, all products returned should have the search term present in either the product name, description, or SKU
**Validates: Requirements 3.1**

Property 8: Filter application correctness
*For any* combination of category, brand, and price range filters, all products returned should match all applied filter criteria
**Validates: Requirements 3.2, 3.3, 3.4**

Property 9: Filter state preservation
*For any* applied filters and sort options, pagination links should preserve all query parameters
**Validates: Requirements 3.5, 4.5**

### Sorting Properties

Property 10: Sort order correctness
*For any* sort option selected (newest, price ascending, price descending, alphabetical), the products should be ordered according to the specified sort criteria
**Validates: Requirements 4.1, 4.2, 4.3, 4.4**

### Stock Status Properties

Property 11: Out of stock badge display
*For any* product with zero total available stock, the rendered product card should contain an "OUT OF STOCK" badge overlay
**Validates: Requirements 5.1**

Property 12: Out of stock product inclusion
*For any* product query, products with zero stock should still be included in the results and not filtered out
**Validates: Requirements 5.2**

Property 13: Stock calculation across locations
*For any* product with multiple inventory locations, the total available stock should equal the sum of quantity_available across all locations
**Validates: Requirements 5.3**

Property 14: Out of stock card consistency
*For any* out-of-stock product, the product card structure should contain the same elements (name, price, category, image, button) as in-stock products
**Validates: Requirements 5.5**

## Error Handling

### Invalid Filter Inputs
- Price range validation: min_price must be <= max_price
- Category/Brand validation: IDs must exist in database
- Invalid inputs redirect with error message

### No Results
- Display "No products found" message
- Provide link to clear filters
- Maintain filter form state

### Missing Images
- Gracefully handle missing product images
- Display SVG placeholder icon
- Maintain card layout consistency

### Database Errors
- Laravel's exception handling
- Log errors for debugging
- Display user-friendly error page

## Testing Strategy

### Unit Testing
Unit tests will verify:
- Controller filter logic
- Query building with multiple filters
- Sort parameter handling
- Pagination configuration
- Stock calculation logic
- Specific edge cases (no products, no featured products, no images)

### Property-Based Testing
Property-based tests will use **Pest PHP** with the **pest-plugin-faker** for data generation. Each test will run a minimum of 100 iterations.

Tests will verify:
- Product card completeness across random products
- Filter application correctness across random filter combinations
- Sort order correctness across random product sets
- Stock calculation across random inventory configurations
- State preservation across pagination

Each property-based test will be tagged with a comment referencing the design document property:
- Format: `// Feature: landing-page-product-display, Property X: [property description]`

### Integration Testing
Integration tests will verify:
- Full page rendering with products
- Filter form submission and results
- Pagination navigation
- Search functionality end-to-end
- Featured products display logic

## Implementation Notes

### Current State
Ang current implementation ay halos complete na. Ang customer dashboard view at controller ay may:
- Product grid display with images
- Featured products section
- Search functionality
- Category and brand filters
- Price range filters
- Sorting options
- Pagination
- Out of stock indicators

### Changes Needed
Based sa requirements, ang current implementation ay **already meets the specifications**. Ang landing page ay:
- ✅ Displays product grid with images
- ✅ Shows featured products section
- ✅ Has search and filter functionality
- ✅ Supports sorting
- ✅ Shows out of stock badges
- ✅ Responsive design

Ang main task ay:
1. **Verify** na lahat ng functionality ay working correctly
2. **Add comprehensive tests** para sa correctness properties
3. **Minor UI refinements** kung kinakailangan

### Performance Considerations
- Eager loading of relationships (category, brand, primaryImage, inventory)
- Pagination to limit query size (12 per page)
- Index on product status, is_featured, category_id, brand_id
- Consider caching for featured products

### Accessibility
- Alt text for product images
- Keyboard navigation support
- ARIA labels for interactive elements
- Sufficient color contrast
- Touch-friendly button sizes (min 44x44px)
