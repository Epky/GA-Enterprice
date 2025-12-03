# Design Document

## Overview

This feature implements a safe product deletion workflow that allows staff members to delete products regardless of stock status. The design focuses on providing clear user feedback through a confirmation modal that displays relevant product information and appropriate warnings based on stock levels.

The implementation will modify the existing product deletion flow to always show a confirmation modal before deletion, with dynamic content based on the product's stock status. This ensures staff members are fully informed before performing destructive actions.

## Architecture

The solution follows Laravel's MVC architecture with these key components:

1. **Frontend Layer**: Blade templates with JavaScript for modal interactions
2. **Controller Layer**: Existing `StaffProductController` handles deletion requests
3. **Service Layer**: `ProductService` performs the actual deletion logic
4. **Modal Component**: Reusable Blade component for confirmation dialogs

The deletion flow:
1. User clicks delete button → JavaScript intercepts click
2. JavaScript displays modal with product data
3. User confirms → Form submits to controller
4. Controller calls service → Service deletes product
5. Redirect with success message

## Components and Interfaces

### 1. Confirmation Modal Component

**Location**: `resources/views/components/delete-confirmation-modal.blade.php`

**Props**:
- `productId`: The ID of the product to delete
- `productName`: The name of the product
- `stockQuantity`: Total stock across all locations
- `deleteRoute`: The route to submit the deletion form

**Behavior**:
- Displays product name and stock information
- Shows warning message if stock > 0
- Shows standard confirmation if stock = 0
- Provides "Cancel" and "Delete" buttons
- Uses red/destructive styling for delete action

### 2. JavaScript Modal Controller

**Location**: `resources/js/product-deletion.js` or inline in view

**Functions**:
- `showDeleteModal(productId, productName, stockQuantity)`: Opens the modal with product data
- `hideDeleteModal()`: Closes the modal
- `confirmDeletion()`: Submits the deletion form

**Event Handlers**:
- Delete button click → Show modal
- Cancel button click → Hide modal
- Confirm button click → Submit form
- Escape key → Hide modal
- Click outside modal → Hide modal

### 3. Controller Method

**Location**: `app/Http/Controllers/Staff/StaffProductController.php`

**Method**: `destroy(Product $product)`

**Current Behavior**: Calls `ProductService::deleteProduct()`

**No Changes Needed**: The controller already handles deletion correctly. The modal is purely a frontend enhancement.

### 4. Service Method

**Location**: `app/Services/ProductService.php`

**Method**: `deleteProduct(Product $product)`

**Current Behavior**: 
- Wraps deletion in transaction
- Deletes related images
- Deletes product record
- Clears caches

**No Changes Needed**: The service already handles deletion regardless of stock status. No stock checking logic needs to be removed.

## Data Models

### Product Model

**Relevant Attributes**:
- `id`: Primary key
- `name`: Product name for display in modal
- `sku`: Product SKU for reference

**Relevant Relationships**:
- `inventory`: HasMany relationship to get stock quantities

**Computed Property Needed**:
```php
public function getTotalStockAttribute(): int
{
    return $this->inventory->sum('quantity_available');
}
```

This provides easy access to total stock across all locations for modal display.

### Inventory Model

**Relevant Attributes**:
- `quantity_available`: Stock quantity at a location
- `product_id`: Foreign key to product

**Usage**: Summed to calculate total stock for display in modal.

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Modal Display Universality
*For any* product in the system, clicking the delete button should trigger the confirmation modal to appear, regardless of the product's stock status.
**Validates: Requirements 1.1**

### Property 2: Modal Content Completeness
*For any* product, when the confirmation modal is displayed, it should contain both the product name and the current total stock quantity.
**Validates: Requirements 1.2**

### Property 3: Deletion Confirmation Effect
*For any* product, when deletion is confirmed through the modal, the product should no longer exist in the database after the operation completes.
**Validates: Requirements 1.3**

### Property 4: Cancellation Preservation
*For any* product, when deletion is canceled through the modal, the product should remain in the database with all its original data unchanged.
**Validates: Requirements 1.4**

### Property 5: Post-Deletion Redirect
*For any* product that is successfully deleted, the system should redirect to the product list page and display a success message.
**Validates: Requirements 1.5**

### Property 6: Stock Warning Display
*For any* product where total stock is greater than zero, the confirmation modal should display a warning message about stock loss.
**Validates: Requirements 2.1**

### Property 7: No Warning for Zero Stock
*For any* product where total stock equals zero, the confirmation modal should display a standard confirmation message without stock warnings.
**Validates: Requirements 2.2**

### Property 8: Stock Quantity Accuracy
*For any* product with inventory across multiple locations, the stock quantity displayed in the warning message should equal the sum of quantity_available across all inventory records.
**Validates: Requirements 2.3**

### Property 9: Modal Consistency
*For any* page where product deletion can be initiated (list, detail, or edit page), the confirmation modal should have the same structure, content format, and styling.
**Validates: Requirements 3.4**

## Error Handling

### Frontend Errors

1. **Modal Display Failure**
   - Scenario: JavaScript fails to load or execute
   - Handling: Fallback to browser's native confirm dialog
   - User Experience: Less polished but functional

2. **Network Error During Deletion**
   - Scenario: Form submission fails due to network issues
   - Handling: Display error message, keep modal open
   - User Experience: User can retry deletion

### Backend Errors

1. **Product Not Found**
   - Scenario: Product deleted by another user before confirmation
   - Handling: Return 404 with appropriate message
   - User Experience: Redirect to product list with error message

2. **Database Transaction Failure**
   - Scenario: Database error during deletion
   - Handling: Rollback transaction, log error
   - User Experience: Redirect back with error message

3. **Permission Denied**
   - Scenario: User loses staff permissions during operation
   - Handling: Return 403 Forbidden
   - User Experience: Redirect to dashboard with error message

### Validation Errors

1. **Invalid Product ID**
   - Scenario: Malformed or non-existent product ID
   - Handling: Laravel route model binding handles this
   - User Experience: 404 error page

2. **CSRF Token Mismatch**
   - Scenario: Session expired or CSRF attack
   - Handling: Laravel's CSRF middleware handles this
   - User Experience: 419 error page with option to retry

## Testing Strategy

### Unit Tests

Unit tests will verify specific behaviors and edge cases:

1. **Modal Component Rendering**
   - Test that modal renders with correct product data
   - Test that warning message appears when stock > 0
   - Test that warning message is absent when stock = 0
   - Test that stock quantity is correctly calculated

2. **Controller Deletion**
   - Test successful deletion returns redirect
   - Test deletion with success message
   - Test deletion of non-existent product returns 404

3. **Service Deletion Logic**
   - Test product is removed from database
   - Test related records are handled correctly
   - Test caches are cleared after deletion

### Property-Based Tests

Property-based tests will verify universal properties across many inputs using a PHP property-based testing library (e.g., Eris or Pest with Faker):

**Configuration**: Each property test should run a minimum of 100 iterations to ensure thorough coverage.

**Tagging**: Each property-based test must include a comment tag in this exact format:
```php
// **Feature: product-deletion-with-stock, Property {number}: {property_text}**
```

**Implementation**: Each correctness property from the design document must be implemented as a single property-based test.

1. **Property 1: Modal Display Universality**
   - Generate: Random products with varying stock levels
   - Test: Modal appears for all products when delete is clicked
   - Tag: `// **Feature: product-deletion-with-stock, Property 1: Modal Display Universality**`

2. **Property 2: Modal Content Completeness**
   - Generate: Random products with random names and stock
   - Test: Modal contains correct product name and stock quantity
   - Tag: `// **Feature: product-deletion-with-stock, Property 2: Modal Content Completeness**`

3. **Property 3: Deletion Confirmation Effect**
   - Generate: Random products
   - Test: Product doesn't exist after confirmed deletion
   - Tag: `// **Feature: product-deletion-with-stock, Property 3: Deletion Confirmation Effect**`

4. **Property 4: Cancellation Preservation**
   - Generate: Random products with random attributes
   - Test: Product remains unchanged after cancellation
   - Tag: `// **Feature: product-deletion-with-stock, Property 4: Cancellation Preservation**`

5. **Property 5: Post-Deletion Redirect**
   - Generate: Random products
   - Test: Deletion results in redirect to product list with success message
   - Tag: `// **Feature: product-deletion-with-stock, Property 5: Post-Deletion Redirect**`

6. **Property 6: Stock Warning Display**
   - Generate: Random products with stock > 0
   - Test: Modal contains warning message
   - Tag: `// **Feature: product-deletion-with-stock, Property 6: Stock Warning Display**`

7. **Property 7: No Warning for Zero Stock**
   - Generate: Random products with stock = 0
   - Test: Modal does not contain warning message
   - Tag: `// **Feature: product-deletion-with-stock, Property 7: No Warning for Zero Stock**`

8. **Property 8: Stock Quantity Accuracy**
   - Generate: Random products with inventory across multiple locations
   - Test: Displayed stock equals sum of all inventory quantities
   - Tag: `// **Feature: product-deletion-with-stock, Property 8: Stock Quantity Accuracy**`

9. **Property 9: Modal Consistency**
   - Generate: Random products and random page contexts
   - Test: Modal structure and content are identical across all pages
   - Tag: `// **Feature: product-deletion-with-stock, Property 9: Modal Consistency**`

### Integration Tests

Integration tests will verify the complete deletion workflow:

1. **Complete Deletion Flow**
   - Navigate to product list
   - Click delete button
   - Verify modal appears
   - Confirm deletion
   - Verify redirect and success message
   - Verify product is deleted

2. **Cancellation Flow**
   - Navigate to product detail
   - Click delete button
   - Verify modal appears
   - Cancel deletion
   - Verify modal closes
   - Verify product still exists

3. **Cross-Page Consistency**
   - Test deletion from list page
   - Test deletion from detail page
   - Test deletion from edit page
   - Verify modal is identical in all cases

### Manual Testing Checklist

1. Visual verification of modal styling
2. Accessibility testing (keyboard navigation, screen readers)
3. Mobile responsiveness of modal
4. Browser compatibility (Chrome, Firefox, Safari, Edge)
5. Error message display and clarity
6. Success message display and clarity

## Implementation Notes

### Styling Considerations

- Use Tailwind CSS classes consistent with existing UI
- Modal should use red/destructive colors (red-600, red-700)
- Warning icon should be prominent for products with stock
- Ensure sufficient contrast for accessibility

### Accessibility Requirements

- Modal should trap focus when open
- Escape key should close modal
- Focus should return to delete button after modal closes
- Screen readers should announce modal content
- Buttons should have clear aria-labels

### Performance Considerations

- Stock calculation should use existing eager-loaded inventory data
- No additional database queries needed for modal display
- Modal HTML can be rendered server-side or client-side
- Consider caching product data if using AJAX approach

### Browser Compatibility

- Target modern browsers (last 2 versions)
- Provide fallback for browsers without JavaScript
- Test modal backdrop and positioning across browsers
- Ensure form submission works without JavaScript (progressive enhancement)

## Alternative Approaches Considered

### 1. Inline Confirmation (Not Chosen)
Using browser's native `confirm()` dialog instead of custom modal.

**Pros**: Simpler implementation, no additional code needed
**Cons**: Cannot show detailed product information, poor UX, not customizable

**Decision**: Rejected due to poor user experience and inability to show stock warnings.

### 2. Soft Delete (Not Chosen)
Implementing soft deletes where products are marked as deleted but not removed.

**Pros**: Allows recovery of accidentally deleted products
**Cons**: Adds complexity, requires additional database columns, not requested in requirements

**Decision**: Rejected as it's beyond the scope of current requirements. Can be added later if needed.

### 3. AJAX-Based Modal (Not Chosen)
Loading modal content via AJAX request after delete button click.

**Pros**: Reduces initial page load size
**Cons**: Adds network latency, more complex error handling, requires additional endpoint

**Decision**: Rejected as product data is already available on the page. Server-side rendering is simpler and faster.

### 4. Separate Confirmation Page (Not Chosen)
Redirecting to a dedicated confirmation page instead of using a modal.

**Pros**: Works without JavaScript, simpler state management
**Cons**: Disrupts user flow, requires additional navigation, slower UX

**Decision**: Rejected due to poor user experience. Modal provides better interaction flow.

## Security Considerations

1. **CSRF Protection**: All deletion forms must include CSRF tokens (Laravel handles this automatically)
2. **Authorization**: Verify user has staff permissions before allowing deletion (existing middleware handles this)
3. **Input Validation**: Product ID is validated through route model binding
4. **SQL Injection**: Using Eloquent ORM prevents SQL injection
5. **XSS Prevention**: Blade templates automatically escape output

## Future Enhancements

1. **Bulk Deletion**: Allow selecting multiple products for deletion with a single confirmation
2. **Deletion History**: Log all deletions for audit purposes
3. **Soft Delete Option**: Add ability to archive products instead of permanent deletion
4. **Undo Functionality**: Allow undoing recent deletions within a time window
5. **Stock Transfer**: Offer to transfer stock to another product before deletion
6. **Dependency Checking**: Warn if product is referenced in pending orders or other records
