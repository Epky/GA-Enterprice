# Design Document

## Overview

The customer dashboard redesign aims to create a more organized, visually appealing, and user-friendly shopping experience for GA Beauty Store customers. The redesign focuses on improving visual hierarchy, adding new sections for better product discovery, enhancing the filter and search experience, and ensuring responsive design across all devices.

The current dashboard combines all functionality in a single view with a hero section, featured products, filters, and product grid. The redesign will maintain this structure while adding new sections (category showcase, quick actions), improving spacing and visual consistency, and enhancing the overall user experience with better animations and interactions.

## Architecture

The customer dashboard follows Laravel's MVC architecture:

- **Controller**: `CustomerController@dashboard` handles all dashboard logic including product queries, filtering, sorting, and pagination
- **View**: `customer/dashboard.blade.php` renders the dashboard UI using Blade components
- **Models**: Product, Category, Brand models provide data access
- **Layout**: `customer-layout` component provides consistent navigation and footer

### Component Structure

```
customer/dashboard.blade.php
├── Hero Section (search, welcome message)
├── Category Showcase (new section)
├── Quick Actions (new section)
├── Featured Products Section
└── Main Content Area
    ├── Filter Sidebar (sticky)
    └── Product Grid
        ├── Sort & Results Info
        ├── Product Cards
        └── Pagination
```

## Components and Interfaces

### CustomerController

**Methods:**
- `dashboard(Request $request)`: Main dashboard method handling all queries and filters
  - Parameters: search, category, brand, min_price, max_price, sort
  - Returns: View with products, featuredProducts, categories, brands

### Dashboard View Sections

**1. Hero Section**
- Gradient background (pink-500 → purple-500 → indigo-500)
- Welcome heading and tagline
- Centered search bar with rounded input and button
- Responsive padding and typography

**2. Category Showcase** (New)
- Grid of category cards (4 columns desktop, 2 tablet, 1 mobile)
- Each card shows: category image, name, product count
- Click navigates to filtered view
- Only shows active categories

**3. Quick Actions** (New)
- 3 action cards: My Orders, Wishlist, Account Settings
- Icon + text layout
- Links to respective pages
- Responsive grid layout

**4. Featured Products**
- Maximum 4 products displayed
- Yellow "FEATURED" badge
- Hover animations (scale-up image)
- Fallback placeholder for missing images

**5. Filter Sidebar**
- Sticky positioning (top-4)
- Category dropdown (auto-submit)
- Brand dropdown (auto-submit)
- Price range inputs with Apply button
- Clear Filters button (conditional)
- Preserves all filters in URL params

**6. Product Grid**
- Results count display
- Sort dropdown (newest, price_low, price_high, name)
- Responsive grid (1/2/3 columns)
- Product cards with:
  - Square aspect ratio images
  - Category label
  - Product name (2-line clamp)
  - Description (60 char limit)
  - Price (₱ format, 2 decimals)
  - Out of stock badge (conditional)
  - View Details button
- Pagination with query preservation
- Empty state with helpful message

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

**Relationships:**
- belongsTo Category
- belongsTo Brand
- hasMany ProductImage
- hasOne primaryImage (first image)
- hasMany Inventory

### Category
```php
- id: integer
- name: string
- slug: string
- image_url: string (nullable)
- is_active: boolean
```

**Computed:**
- products_count: count of active products

### Brand
```php
- id: integer
- name: string
- is_active: boolean
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Featured products limit
*For any* number of products marked as featured, the dashboard should display a maximum of 4 featured products
**Validates: Requirements 2.2**

### Property 2: Featured badge presence
*For any* product displayed in the featured section, the rendered HTML should contain a "FEATURED" badge element
**Validates: Requirements 2.3**

### Property 3: Image placeholder fallback
*For any* product without a primary image, the product card should display a placeholder SVG icon
**Validates: Requirements 2.5**

### Property 4: Category filter application
*For any* category ID selected in the filter, all displayed products should have that category_id
**Validates: Requirements 3.2**

### Property 5: Brand filter application
*For any* brand ID selected in the filter, all displayed products should have that brand_id
**Validates: Requirements 3.3**

### Property 6: Clear filters button visibility
*For any* request with at least one filter parameter (category, brand, min_price, max_price, search), the "Clear Filters" button should be present in the rendered HTML
**Validates: Requirements 3.5**

### Property 7: Filter persistence during pagination
*For any* set of active filters, when navigating to a different page, all filter parameters should remain in the URL query string
**Validates: Requirements 3.6**

### Property 8: Product card completeness
*For any* product displayed in the grid, the product card should contain all required elements: image/placeholder, category name, product name, price, and action button
**Validates: Requirements 4.1**

### Property 9: Out of stock badge display
*For any* product where the sum of inventory.quantity_available equals zero, the product card should display an "OUT OF STOCK" badge overlay
**Validates: Requirements 4.2**

### Property 10: Description truncation
*For any* product description longer than 60 characters, the displayed text should be truncated to 60 characters or less
**Validates: Requirements 4.5**

### Property 11: Price formatting
*For any* product price value, the displayed price should be formatted as "₱X,XXX.XX" with Philippine Peso symbol, thousand separators, and exactly 2 decimal places
**Validates: Requirements 4.6**

### Property 12: Sort by newest
*For any* set of products when sort="newest" is selected, the products should be ordered by created_at in descending order
**Validates: Requirements 5.2**

### Property 13: Sort by price ascending
*For any* set of products when sort="price_low" is selected, the products should be ordered by base_price in ascending order
**Validates: Requirements 5.3**

### Property 14: Sort by price descending
*For any* set of products when sort="price_high" is selected, the products should be ordered by base_price in descending order
**Validates: Requirements 5.4**

### Property 15: Sort alphabetically
*For any* set of products when sort="name" is selected, the products should be ordered alphabetically by name in ascending order
**Validates: Requirements 5.5**

### Property 16: Filter preservation during sort
*For any* combination of active filters and sort parameter, both the filters and sort should be preserved in the URL query string
**Validates: Requirements 5.6**

### Property 17: Pagination info accuracy
*For any* page of results, the "Showing X to Y of Z products" text should accurately reflect firstItem(), lastItem(), and total() values from the paginator
**Validates: Requirements 6.1**

### Property 18: Pagination range calculation
*For any* page number and page size, the displayed range (X to Y) should correctly calculate based on (page-1) * pageSize + 1 to min(page * pageSize, total)
**Validates: Requirements 6.4**

### Property 19: Category card completeness
*For any* category displayed in the showcase, the category card should contain category name, product count, and either an image or default icon
**Validates: Requirements 7.2**

### Property 20: Category filter on click
*For any* category card clicked, the resulting page should have that category's ID in the category filter parameter
**Validates: Requirements 7.3**

### Property 21: Active categories only
*For any* set of categories in the database, only those with is_active=true should be displayed in the category showcase and filter dropdown
**Validates: Requirements 7.4**

### Property 22: Category image fallback
*For any* category without an image_url value, the category card should display a default category icon
**Validates: Requirements 7.5**

### Property 23: Quick action links
*For any* quick action button, the href attribute should point to the correct route (/orders, /wishlist, or /profile)
**Validates: Requirements 8.3**

### Property 24: Quick action structure
*For any* quick action card, it should contain both an icon element and descriptive text
**Validates: Requirements 8.4**

## Error Handling

### No Products Found
- Display friendly empty state with illustration
- Show "No products found" message
- Suggest adjusting filters or search terms
- Provide "View All Products" button to reset

### Missing Images
- Use consistent placeholder SVG icon
- Maintain aspect ratio with gray background
- Ensure placeholder is visually distinct but not jarring

### Filter Errors
- Validate price range inputs (min <= max)
- Handle invalid category/brand IDs gracefully
- Default to showing all products if filters are invalid

### Search Errors
- Trim whitespace from search input
- Handle special characters safely
- Show empty state if no matches

## Testing Strategy

### Unit Tests
- Test CustomerController filter logic with various combinations
- Test price range validation
- Test sort parameter handling
- Test pagination calculations
- Test empty state conditions

### Property-Based Tests
Property-based tests will verify universal properties across all inputs using a PHP property testing library (e.g., Eris or Pest with Faker). Each test should run a minimum of 100 iterations.

Each property-based test must be tagged with a comment explicitly referencing the correctness property:
- Format: `// Feature: customer-dashboard-redesign, Property X: [property text]`
- Each correctness property must be implemented by a SINGLE property-based test

**Test Coverage:**
- Featured products limit (Property 1)
- Filter application correctness (Properties 4, 5)
- Sort order correctness (Properties 12-15)
- Price formatting (Property 11)
- Description truncation (Property 10)
- Pagination calculations (Properties 17, 18)
- Stock badge display logic (Property 9)
- Category filtering (Properties 20, 21)

### Integration Tests
- Test full dashboard rendering with various filter combinations
- Test search + filter + sort combinations
- Test pagination with filters
- Test responsive behavior (viewport testing)

### Visual Regression Tests
- Hero section layout
- Product card styling
- Category showcase layout
- Filter sidebar positioning
- Empty states

## Performance Considerations

### Database Queries
- Eager load relationships (category, brand, primaryImage, inventory)
- Use pagination to limit results (12 per page)
- Index frequently filtered columns (category_id, brand_id, status, is_featured)
- Cache category and brand lists (rarely change)

### Frontend Performance
- Lazy load product images
- Use CSS transforms for animations (GPU accelerated)
- Minimize reflows with fixed aspect ratios
- Debounce search input (if implementing live search)

### Caching Strategy
- Cache featured products query (5 minutes)
- Cache category list with product counts (10 minutes)
- Cache brand list (30 minutes)
- Use query string cache busting for filters

## Accessibility

- Semantic HTML structure (nav, main, section, article)
- ARIA labels for filter controls
- Keyboard navigation support
- Focus indicators on interactive elements
- Alt text for all product images
- Color contrast ratios meet WCAG AA standards
- Touch targets minimum 44x44px
- Screen reader friendly empty states

## Future Enhancements

- Wishlist functionality
- Product comparison feature
- Recently viewed products
- Personalized recommendations
- Advanced filters (color, size, rating)
- Live search with autocomplete
- Infinite scroll option
- Save filter preferences
- Product quick view modal
