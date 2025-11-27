# Design Document

## Overview

This feature improves the staff dashboard's "Recent Inventory Movements" widget by applying the same filtering, formatting, and display enhancements already implemented in the full movements page. The current dashboard widget uses a raw database query that includes system movements and truncates notes, making it difficult to understand recent inventory activity at a glance. This design leverages existing InventoryService methods and Blade components to provide a consistent, readable experience.

## Architecture

The solution reuses existing components from the inventory movements display improvement:

### Component Layers

1. **Controller Layer** (DashboardController)
   - Replace raw database query with InventoryService call
   - Apply business-only filter by default
   - Pass formatted data to view

2. **Service Layer** (InventoryService)
   - Reuse existing `getInventoryMovements()` method
   - Apply default filters (business movements only, last 7 days)
   - Return paginated/limited results

3. **View Layer** (dashboard.blade.php)
   - Replace inline note rendering with movement-notes component
   - Apply consistent badge colors and quantity formatting
   - Improve table spacing and layout

4. **Component Layer** (Blade Components)
   - Reuse existing `movement-notes` component
   - Maintain consistent styling with full movements page

## Components and Interfaces

### DashboardController Updates

```php
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ... existing dashboard data ...
        
        // Get recent business movements (last 7 days) using InventoryService
        $recentMovements = $this->inventoryService->getInventoryMovements([
            'include_system_movements' => false,  // Business only
            'start_date' => now()->subDays(7)->format('Y-m-d'),
            'group_related' => false  // No grouping needed for dashboard
        ], 10);  // Limit to 10 most recent
        
        return view('staff.dashboard', compact(
            // ... existing variables ...
            'recentMovements'
        ));
    }
}
```

### View Updates

The dashboard view will be updated to:

1. **Use InventoryMovement models** instead of raw database results
2. **Apply movement-notes component** for consistent formatting
3. **Use model methods** for badge colors and quantity formatting
4. **Improve table spacing** with better padding and typography
5. **Show full notes** with word wrapping instead of truncation

## Data Models

### Movement Data Structure

The dashboard will receive a collection of `InventoryMovement` models with:

- All model accessor methods available (`getTypeBadgeColor()`, `getQuantityColorClass()`, etc.)
- Relationships eager-loaded (product, variant, performedBy)
- Filtered to business movements only
- Limited to 10 most recent within 7 days

### Note Formatting

Notes will be formatted using the existing `movement-notes` component which handles:

- Transaction reference extraction and linking
- Reason badge display
- Clean notes separation
- Empty state handling

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Business movements only
*For any* dashboard load, all movements displayed in the widget should have movement types in the business movements set (purchase, sale, return, damage, adjustment, transfer), never system movements (reservation, release)
**Validates: Requirements 1.1**

### Property 2: Recent movements limit
*For any* collection of movements, when displayed on the dashboard, at most 10 movements should be shown
**Validates: Requirements 1.3**

### Property 3: Descending date order
*For any* collection of movements displayed on the dashboard, each movement should have a creation date greater than or equal to the next movement in the list
**Validates: Requirements 1.5**

### Property 4: Transaction reference linking
*For any* movement with notes containing a transaction reference pattern, the rendered output should include a clickable link with the correct href to that transaction
**Validates: Requirements 2.1**

### Property 5: Reason badge display
*For any* movement with notes containing a reason pattern, the rendered output should include a badge element displaying the extracted reason text
**Validates: Requirements 2.2**

### Property 6: Structured data separation
*For any* movement with notes containing structured data (transaction references or reasons), the clean notes should not contain that structured data
**Validates: Requirements 2.3**

### Property 7: No note truncation
*For any* movement with notes of any length, the rendered output should contain the full note text without truncation indicators
**Validates: Requirements 2.5**

### Property 8: Quantity color coding
*For any* movement displayed, if the quantity is positive it should use green color class, if negative it should use red color class
**Validates: Requirements 3.1**

### Property 9: Consistent badge colors
*For any* movement type, the badge color class used in the dashboard widget should match the badge color class used in the full movements page for the same type
**Validates: Requirements 3.2**

### Property 10: Date and time display
*For any* movement displayed, the rendered date should include both date and time components in a readable format
**Validates: Requirements 3.3**

### Property 11: Product information completeness
*For any* movement with an associated product, the rendered output should display both the product name and SKU
**Validates: Requirements 3.4**

### Property 12: Transaction link navigation
*For any* movement with a transaction reference, the link href should correctly point to the transaction details page with the proper transaction ID
**Validates: Requirements 4.2**

### Property 13: Movement count accuracy
*For any* dashboard display, if a count is shown, it should equal the actual number of movements displayed in the widget
**Validates: Requirements 4.4**

## Error Handling

### No Recent Movements

- **Scenario**: No business movements exist in the last 7 days
- **Handling**: Hide the entire widget section
- **User Feedback**: Widget not displayed (no error message needed)

### Service Unavailable

- **Scenario**: InventoryService throws an exception
- **Handling**: Log error, hide widget gracefully
- **User Feedback**: Widget not displayed (silent failure)

### Missing Relationships

- **Scenario**: Product or variant has been deleted
- **Handling**: Display movement with placeholder text
- **User Feedback**: Show "Deleted Product" or similar indicator

## Testing Strategy

### Unit Tests

Unit tests will cover:

1. **Controller Data Preparation**
   - Test dashboard controller calls InventoryService with correct parameters
   - Test business-only filter is applied
   - Test 7-day date range is calculated correctly
   - Test limit of 10 is enforced

2. **View Rendering**
   - Test widget is hidden when no movements exist
   - Test movements are displayed in correct order
   - Test movement-notes component is used
   - Test "View All" link is present

### Property-Based Tests

Property-based tests will verify universal behaviors:

1. **Property 1: Business movements only**
   - Generate random movement collections with mixed types
   - Apply dashboard filter
   - Verify all results are business movements

2. **Property 2: Recent movements limit**
   - Generate movement collections of various sizes
   - Apply dashboard display logic
   - Verify at most 10 movements are shown

3. **Property 3: Date range filtering**
   - Generate movements with various creation dates
   - Apply 7-day filter
   - Verify all results are within range

4. **Property 4: Transaction reference linking**
   - Generate movements with transaction references
   - Render using movement-notes component
   - Verify links are present in output

5. **Property 5: Consistent badge colors**
   - Generate movements of each type
   - Get badge colors from both dashboard and full page
   - Verify colors match for same types

6. **Property 6: Quantity color coding**
   - Generate movements with various quantities
   - Get color classes
   - Verify positive=green, negative=red

### Integration Tests

Integration tests will verify:

1. **Full Dashboard Rendering**
   - Test dashboard page loads successfully
   - Test widget displays when movements exist
   - Test widget hidden when no movements exist

2. **Component Integration**
   - Test movement-notes component renders correctly in dashboard context
   - Test transaction links navigate correctly
   - Test badge colors match full movements page

## Implementation Notes

### Reusing Existing Code

This design maximizes code reuse:

- **InventoryService**: Already has filtering and querying logic
- **movement-notes component**: Already handles note formatting
- **Model methods**: Already provide badge colors and quantity formatting
- **CSS classes**: Already defined for consistent styling

### Performance Considerations

1. **Query Optimization**: InventoryService already eager-loads relationships
2. **Limit Early**: Apply limit of 10 at database level, not in PHP
3. **Cache Consideration**: Dashboard data could be cached for 1-5 minutes if needed

### Backward Compatibility

- Existing dashboard functionality remains unchanged
- Only the movements widget display is updated
- No database schema changes required
- No API changes required

### Future Enhancements

1. **Configurable Time Range**: Allow users to choose 24h, 7d, 30d
2. **Movement Type Filter**: Quick filter buttons for specific types
3. **Real-time Updates**: WebSocket updates for new movements
4. **Export Widget Data**: Quick export of recent movements
