# Design Document

## Overview

This feature extends the admin interface to include full product management capabilities by reusing the existing staff product management infrastructure. The design leverages Laravel's existing controllers, services, and views to provide administrators with the same product management functionality available to staff members, ensuring consistency and reducing code duplication.

## Architecture

### Component Reuse Strategy

The design follows a **shared infrastructure approach** where admin product management reuses the existing staff components:

1. **Controller Layer**: Create `AdminProductController` that extends or delegates to `StaffProductController`
2. **Service Layer**: Reuse existing `ProductService` without modification
3. **View Layer**: Create admin-specific views that include the admin layout but reuse product form components
4. **Route Layer**: Add admin product routes that mirror staff routes with admin middleware

### Request Flow

```
Admin User → Admin Routes → AdminProductController → ProductService → Product Model → Database
                ↓                                           ↓
          Admin Middleware                         ImageUploadService
                ↓
          Admin Layout Views
```

## Components and Interfaces

### 1. AdminProductController

**Location**: `app/Http/Controllers/Admin/AdminProductController.php`

**Purpose**: Handle admin product management requests by delegating to ProductService

**Key Methods**:
- `index(Request $request)`: Display paginated product list with filters
- `create()`: Show product creation form
- `store(ProductStoreRequest $request)`: Create new product
- `show(Product $product)`: Display product details
- `edit(Product $product)`: Show product edit form
- `update(ProductUpdateRequest $request, Product $product)`: Update product
- `destroy(Product $product)`: Soft delete product

**Dependencies**:
- `ProductService`: For business logic
- `ImageUploadService`: For image handling (via ProductService)
- `Category`, `Brand`, `Product` models

### 2. Route Configuration

**Location**: `routes/admin.php`

**New Routes**:
```php
// Product management routes
Route::resource('products', AdminProductController::class);

// Image management
Route::post('products/{product}/images/upload', [AdminProductController::class, 'uploadImages'])
    ->name('products.images.upload');
Route::delete('products/images/{image}', [AdminProductController::class, 'deleteImage'])
    ->name('products.images.delete');
Route::post('products/images/{image}/set-primary', [AdminProductController::class, 'setPrimaryImage'])
    ->name('products.images.set-primary');

// Quick actions
Route::patch('products/{product}/toggle-featured', [AdminProductController::class, 'toggleFeatured'])
    ->name('products.toggle-featured');
```

### 3. View Components

**Admin Product Views**:
- `resources/views/admin/products/index.blade.php`: Product list
- `resources/views/admin/products/create.blade.php`: Product creation form
- `resources/views/admin/products/edit.blade.php`: Product edit form
- `resources/views/admin/products/show.blade.php`: Product detail view

**Shared Components** (reused from staff):
- `resources/views/components/searchable-select.blade.php`: Category/brand selection
- `resources/views/components/image-manager.blade.php`: Image upload and management
- `resources/views/components/inline-add-modal.blade.php`: Quick category/brand creation
- `resources/views/components/delete-confirmation-modal.blade.php`: Product deletion

### 4. Navigation Integration

**Location**: `resources/views/layouts/admin.blade.php`

Add products menu item to admin navigation:
```blade
<x-nav-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
    Products
</x-nav-link>
```

## Data Models

### Existing Models (No Changes Required)

**Product Model** (`app/Models/Product.php`):
- Already supports all required functionality
- Soft deletes enabled
- Relationships: category, brand, images, inventory, variants, specifications

**Category Model** (`app/Models/Category.php`):
- Used for product categorization
- Cached for performance

**Brand Model** (`app/Models/Brand.php`):
- Used for product branding
- Cached for performance

**ProductImage Model** (`app/Models/ProductImage.php`):
- Handles product images
- Supports primary image designation
- Display order management

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Admin product list completeness
*For any* set of products in the database, the admin product list should display all products with the same data completeness as the staff product list (name, category, brand, price, stock status, images)
**Validates: Requirements 1.2**

### Property 2: Product creation equivalence
*For any* valid product data submitted by an admin, the product creation process should produce the same result as when submitted by staff (same validation, same database record, same image handling)
**Validates: Requirements 2.3, 2.4**

### Property 3: Product update equivalence
*For any* valid product update submitted by an admin, the product update process should produce the same result as when submitted by staff (same validation, same database changes, same image handling)
**Validates: Requirements 3.3, 3.4**

### Property 4: Image management consistency
*For any* product image operation (upload, delete, set primary) performed by an admin, the result should be identical to the same operation performed by staff
**Validates: Requirements 2.4, 3.4**

### Property 5: Deletion warning accuracy
*For any* product with positive stock quantity, when an admin attempts deletion, the system should display a warning with the exact total stock quantity across all locations
**Validates: Requirements 4.2**

### Property 6: Filter and search parity
*For any* search query or filter combination applied by an admin, the results should match what staff would see with the same query and filters
**Validates: Requirements 1.3**

### Property 7: Navigation accessibility
*For any* admin user viewing the admin dashboard, the products menu item should be visible and functional
**Validates: Requirements 5.1, 5.2**

## Error Handling

### Validation Errors

**Product Creation/Update**:
- Required field validation (name, description, price, category, brand)
- Price validation (numeric, positive)
- Image validation (type, size, format)
- Display errors inline with form fields
- Preserve form data on validation failure

### Image Upload Errors

**Scenarios**:
- Invalid file type: Display "Please upload a valid image file (JPEG, PNG, WebP)"
- File too large: Display "Image size must be under 5MB"
- Upload failure: Display "Failed to upload image. Please try again"
- Storage error: Log error and display generic message

### Database Errors

**Scenarios**:
- Duplicate SKU: Display "Product SKU already exists"
- Foreign key constraint: Display "Invalid category or brand selected"
- Connection error: Display "Database connection error. Please try again"
- Transaction rollback: Ensure no partial data is saved

### Authorization Errors

**Scenarios**:
- Non-admin access attempt: Redirect to appropriate dashboard
- Session expiration: Redirect to login with return URL
- CSRF token mismatch: Display "Session expired. Please refresh and try again"

## Testing Strategy

### Unit Tests

**Controller Tests**:
- Test each controller method with valid inputs
- Test validation error handling
- Test authorization checks
- Test redirect behavior

**Integration Tests**:
- Test complete product creation workflow
- Test complete product update workflow
- Test image upload and management workflow
- Test product deletion workflow

### Property-Based Tests

**Testing Framework**: Use Pest PHP with property-based testing capabilities

**Test Configuration**: Each property test should run a minimum of 100 iterations

**Property Test Implementation**:
- Each property-based test must reference its corresponding correctness property using the format: `**Feature: admin-product-management, Property {number}: {property_text}**`
- Generate random product data (names, descriptions, prices, categories, brands)
- Generate random image sets
- Generate random filter combinations
- Verify properties hold across all generated inputs

### Manual Testing Checklist

**Product List**:
- [ ] Verify all products display correctly
- [ ] Test search functionality
- [ ] Test category filter
- [ ] Test brand filter
- [ ] Test status filter
- [ ] Test pagination

**Product Creation**:
- [ ] Create product with all fields
- [ ] Create product with images
- [ ] Test validation errors
- [ ] Test inline category creation
- [ ] Test inline brand creation

**Product Editing**:
- [ ] Edit product information
- [ ] Add new images
- [ ] Remove images
- [ ] Set primary image
- [ ] Test validation errors

**Product Deletion**:
- [ ] Delete product with zero stock
- [ ] Delete product with positive stock (verify warning)
- [ ] Cancel deletion
- [ ] Confirm deletion

## Implementation Notes

### Code Reuse Strategy

1. **Controller Delegation**: AdminProductController can extend StaffProductController or use composition to delegate to ProductService
2. **View Inheritance**: Admin views extend admin layout but include shared form components
3. **Route Naming**: Use `admin.products.*` naming convention to distinguish from staff routes
4. **Middleware**: Apply `admin` middleware to all admin product routes

### Performance Considerations

1. **Caching**: Reuse existing category and brand caching from staff implementation
2. **Eager Loading**: Use same eager loading strategy as staff controller to prevent N+1 queries
3. **Pagination**: Use same pagination settings (20 items per page)
4. **Image Optimization**: Reuse existing image upload service with optimization

### Security Considerations

1. **Authorization**: Verify admin role on all routes via middleware
2. **CSRF Protection**: Ensure all forms include CSRF tokens
3. **Input Validation**: Use existing ProductStoreRequest and ProductUpdateRequest
4. **File Upload Security**: Reuse existing ImageUploadService validation

### Accessibility Considerations

1. **Keyboard Navigation**: Ensure all interactive elements are keyboard accessible
2. **Screen Reader Support**: Use proper ARIA labels and semantic HTML
3. **Focus Management**: Maintain logical focus order in forms
4. **Error Announcements**: Ensure validation errors are announced to screen readers
