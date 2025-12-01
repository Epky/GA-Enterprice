# Design Document

## Overview

This feature enhances the product creation and editing forms by adding inline deletion and search capabilities to the category and brand dropdowns. Instead of using standard HTML select elements, we'll implement custom dropdown components that support:
- Delete buttons next to each item
- Real-time search filtering
- AJAX-based deletion with confirmation
- Automatic dropdown refresh after operations

The solution will use a custom Blade component with JavaScript for interactivity, maintaining consistency with the existing inline creation functionality.

## Architecture

### Component Structure

```
resources/
├── views/
│   └── components/
│       ├── searchable-select.blade.php (new custom dropdown component)
│       └── add-inline-button.blade.php (existing)
├── js/
│   └── searchable-select.js (new JavaScript module)
└── css/
    └── app.css (add custom dropdown styles)
```

### Backend Structure

```
app/
├── Http/
│   └── Controllers/
│       └── Staff/
│           ├── StaffCategoryController.php (add deleteInline method)
│           └── StaffBrandController.php (add deleteInline method)
routes/
└── staff.php (add new delete routes)
```

## Components and Interfaces

### 1. Searchable Select Component

**File:** `resources/views/components/searchable-select.blade.php`

**Props:**
- `name` (string, required): Form field name
- `label` (string, required): Display label
- `items` (Collection, required): Items to display
- `selected` (int|null): Currently selected item ID
- `required` (bool, default: false): Whether field is required
- `deleteRoute` (string, required): Route name for deletion
- `refreshRoute` (string, required): Route name to fetch updated list
- `placeholder` (string, default: "Select..."): Placeholder text

**Structure:**
```html
<div class="searchable-select-wrapper">
    <label>{{ $label }}</label>
    <div class="relative">
        <!-- Hidden input for form submission -->
        <input type="hidden" name="{{ $name }}" value="{{ $selected }}">
        
        <!-- Display button -->
        <button type="button" class="searchable-select-trigger">
            <span class="selected-text">{{ $placeholder }}</span>
            <svg><!-- dropdown icon --></svg>
        </button>
        
        <!-- Dropdown panel -->
        <div class="searchable-select-dropdown hidden">
            <!-- Search input -->
            <div class="search-box">
                <input type="text" placeholder="Search...">
            </div>
            
            <!-- Items list -->
            <ul class="items-list">
                @foreach($items as $item)
                <li data-id="{{ $item->id }}" data-name="{{ $item->name }}">
                    <span class="item-name">{{ $item->name }}</span>
                    <button type="button" class="delete-btn" data-id="{{ $item->id }}">
                        <svg><!-- trash icon --></svg>
                    </button>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
```

### 2. JavaScript Module

**File:** `resources/js/searchable-select.js`

**Class:** `SearchableSelect`

**Methods:**
- `constructor(element, options)`: Initialize component
- `open()`: Show dropdown
- `close()`: Hide dropdown
- `search(query)`: Filter items by search query
- `selectItem(id, name)`: Select an item
- `deleteItem(id)`: Delete an item with confirmation
- `refreshItems()`: Fetch updated items from server
- `showLoading()`: Display loading state
- `hideLoading()`: Hide loading state
- `showMessage(type, text)`: Display success/error message

**Event Handlers:**
- Click outside to close
- Search input keyup
- Item click to select
- Delete button click
- Escape key to close

### 3. Backend API Endpoints

#### Category Delete Endpoint

**Route:** `DELETE /staff/categories/{category}/inline`  
**Controller:** `StaffCategoryController@deleteInline`

**Request:**
```json
{
    "confirm": true
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Category deleted successfully."
}
```

**Response (Has Products):**
```json
{
    "success": false,
    "message": "Cannot delete category with 5 associated products.",
    "product_count": 5
}
```

#### Brand Delete Endpoint

**Route:** `DELETE /staff/brands/{brand}/inline`  
**Controller:** `StaffBrandController@deleteInline`

**Request/Response:** Same structure as category

#### Refresh Endpoints

Both controllers already have `getActive()` methods that return active items:
- `GET /staff/categories/active`
- `GET /staff/brands/active`

## Data Models

No changes to existing models required. We'll use existing:
- `Category` model with `products` relationship
- `Brand` model with `products` relationship

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Deletion validation consistency
*For any* category or brand with associated products, deletion attempts should always fail with an appropriate error message indicating the number of associated products.
**Validates: Requirements 1.5, 2.5**

### Property 2: Search filter correctness
*For any* search query string, the filtered dropdown items should only include items whose names contain the search query (case-insensitive).
**Validates: Requirements 5.3**

### Property 3: Dropdown refresh completeness
*For any* successful deletion operation, the refreshed dropdown list should not contain the deleted item and should contain all other active items.
**Validates: Requirements 3.1, 3.2**

### Property 4: Form state preservation
*For any* deletion or search operation, all other form field values should remain unchanged.
**Validates: Requirements 3.2**

### Property 5: Empty search results handling
*For any* search query that matches no items, the dropdown should display a "No results found" message and no selectable items.
**Validates: Requirements 5.4**

### Property 6: Confirmation dialog accuracy
*For any* delete button click, the confirmation dialog should display the exact name of the item being deleted.
**Validates: Requirements 4.4**

## Error Handling

### Client-Side Errors

1. **Network Failure**
   - Display: "Network error. Please check your connection."
   - Action: Keep dropdown open, allow retry

2. **Server Error (500)**
   - Display: "Server error. Please try again later."
   - Action: Keep dropdown open, log error

3. **Validation Error (422)**
   - Display specific validation message from server
   - Action: Keep dropdown open

### Server-Side Errors

1. **Item Has Products**
   - Return 422 with product count
   - Message: "Cannot delete {type} with {count} associated products."

2. **Item Not Found**
   - Return 404
   - Message: "{Type} not found."

3. **Database Error**
   - Log error details
   - Return 500
   - Message: "Failed to delete {type}. Please try again."

### Error Response Format

```json
{
    "success": false,
    "message": "Human-readable error message",
    "errors": {
        "field": ["Validation error details"]
    },
    "product_count": 5  // Optional, for deletion prevention
}
```

## Testing Strategy

### Unit Tests

1. **Controller Tests**
   - Test `deleteInline` method with valid category/brand
   - Test deletion prevention when products exist
   - Test deletion of non-existent items
   - Test unauthorized access

2. **Component Tests**
   - Test search filtering logic
   - Test item selection
   - Test dropdown open/close

### Property-Based Tests

Property-based tests will use Laravel's built-in testing framework with custom generators for random data.

**Test Configuration:**
- Minimum 100 iterations per property test
- Use database transactions for isolation
- Generate random categories/brands with varying product counts

**Test Files:**
- `tests/Unit/SearchableSelectDeletionValidationPropertyTest.php`
- `tests/Unit/SearchableSelectSearchFilterPropertyTest.php`
- `tests/Unit/SearchableSelectRefreshCompletenessPropertyTest.php`
- `tests/Unit/SearchableSelectFormStatePreservationPropertyTest.php`
- `tests/Unit/SearchableSelectEmptySearchPropertyTest.php`
- `tests/Unit/SearchableSelectConfirmationDialogPropertyTest.php`

### Integration Tests

1. **Full Workflow Test**
   - Create product form
   - Open category dropdown
   - Search for category
   - Attempt to delete category with products
   - Verify error message
   - Delete category without products
   - Verify dropdown refresh

2. **Concurrent Operations Test**
   - Multiple users deleting items simultaneously
   - Verify data consistency

## Security Considerations

1. **Authorization**
   - Only staff users can delete categories/brands
   - Use Laravel's authorization policies

2. **CSRF Protection**
   - Include CSRF token in all AJAX requests
   - Verify token on server

3. **Input Validation**
   - Validate item IDs
   - Sanitize search queries
   - Prevent SQL injection

4. **Rate Limiting**
   - Limit deletion requests to prevent abuse
   - Use Laravel's throttle middleware

## Performance Considerations

1. **Caching**
   - Cache active categories/brands list (already implemented)
   - Clear cache after deletion
   - Cache duration: 1 hour

2. **Database Queries**
   - Use existing `withCount('products')` for product count
   - Index on `is_active` column (already exists)

3. **Frontend Optimization**
   - Debounce search input (300ms)
   - Lazy load items if list is very large (>100 items)
   - Use event delegation for delete buttons

## Implementation Notes

1. **Backward Compatibility**
   - Keep existing select elements as fallback
   - Progressive enhancement approach
   - Graceful degradation if JavaScript disabled

2. **Accessibility**
   - ARIA labels for screen readers
   - Keyboard navigation support
   - Focus management

3. **Mobile Responsiveness**
   - Touch-friendly delete buttons
   - Responsive dropdown sizing
   - Virtual keyboard handling

## Migration Path

1. Create new component and JavaScript module
2. Add new controller methods and routes
3. Update product create/edit forms to use new component
4. Test thoroughly in staging
5. Deploy to production
6. Monitor for errors and user feedback
