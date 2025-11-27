# Design Document

## Overview

This feature improves the inventory movements history display by implementing intelligent filtering, grouping, and formatting of movement records. The current implementation shows all movement types equally, creating visual clutter with internal system operations (reservations, releases) that are not meaningful to staff users. This design introduces a layered approach where business movements are shown by default, with system movements available on demand, and related movements grouped together for better context.

## Architecture

The solution follows Laravel's MVC pattern with enhancements to the existing inventory system:

### Component Layers

1. **Data Layer** (InventoryService)
   - Enhanced query methods with movement type filtering
   - Grouping logic for related movements
   - Note parsing utilities

2. **Controller Layer** (StaffInventoryController)
   - Request parameter handling for filters
   - Toggle state management
   - Response formatting

3. **View Layer** (Blade Templates)
   - Conditional rendering based on movement types
   - Grouped movement display components
   - Enhanced formatting helpers

4. **Model Layer** (InventoryMovement)
   - New accessor methods for parsed data
   - Movement classification helpers
   - Grouping relationship methods

## Components and Interfaces

### InventoryService Enhancements

```php
class InventoryService
{
    /**
     * Get inventory movements with enhanced filtering
     * 
     * @param array $filters {
     *     @type bool $include_system_movements Default: false
     *     @type bool $group_related Default: true
     *     @type string $movement_type
     *     @type string $location
     *     @type string $start_date
     *     @type string $end_date
     * }
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInventoryMovements(array $filters = [], int $perPage = 25): LengthAwarePaginator;
    
    /**
     * Group related movements by transaction reference
     * 
     * @param Collection $movements
     * @return Collection Grouped movements with 'primary' and 'related' keys
     */
    public function groupRelatedMovements(Collection $movements): Collection;
    
    /**
     * Check if a movement type is a business movement
     * 
     * @param string $type
     * @return bool
     */
    public function isBusinessMovement(string $type): bool;
    
    /**
     * Check if a movement type is a system movement
     * 
     * @param string $type
     * @return bool
     */
    public function isSystemMovement(string $type): bool;
}
```

### InventoryMovement Model Enhancements

```php
class InventoryMovement extends Model
{
    /**
     * Check if this is a business movement
     * 
     * @return bool
     */
    public function isBusinessMovement(): bool;
    
    /**
     * Check if this is a system movement
     * 
     * @return bool
     */
    public function isSystemMovement(): bool;
    
    /**
     * Extract transaction reference from notes
     * 
     * @return array|null ['type' => 'walk-in', 'id' => 'WI-20251125-0001']
     */
    public function getTransactionReferenceAttribute(): ?array;
    
    /**
     * Parse reason from notes
     * 
     * @return string|null
     */
    public function getReasonAttribute(): ?string;
    
    /**
     * Get notes without structured data
     * 
     * @return string|null
     */
    public function getCleanNotesAttribute(): ?string;
    
    /**
     * Get related movements (same transaction reference)
     * 
     * @return Collection
     */
    public function getRelatedMovements(): Collection;
}
```

### Controller Updates

```php
class StaffInventoryController extends Controller
{
    /**
     * Display inventory movements history with enhanced filtering
     * 
     * @param Request $request
     * @return View
     */
    public function movements(Request $request): View
    {
        $includeSystemMovements = $request->boolean('include_system_movements', false);
        $groupRelated = $request->boolean('group_related', true);
        
        $movements = $this->inventoryService->getInventoryMovements([
            'product_id' => $request->get('product_id'),
            'movement_type' => $request->get('movement_type'),
            'location' => $request->get('location'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'performed_by' => $request->get('performed_by'),
            'include_system_movements' => $includeSystemMovements,
            'group_related' => $groupRelated
        ], 25);
        
        return view('staff.inventory.movements', compact('movements', 'includeSystemMovements'));
    }
}
```

## Data Models

### Movement Classification

Movements are classified into two categories:

**Business Movements** (shown by default):
- `purchase` - Stock purchases/restocks
- `sale` - Sales to customers
- `return` - Customer returns
- `damage` - Damaged/lost items
- `adjustment` - Manual adjustments
- `transfer` - Location transfers

**System Movements** (hidden by default):
- `reservation` - Temporary holds for transactions
- `release` - Released reservations

### Movement Grouping Structure

When grouping is enabled, movements are organized as:

```php
[
    'primary' => InventoryMovement,  // The business movement
    'related' => Collection,          // Related system movements
    'transaction_ref' => string|null  // Transaction reference if any
]
```

### Note Structure

Notes can contain structured data in these formats:

- **Reason**: `"Some text (Reason: reason text)"`
- **Transaction Reference**: `"Reserved for walk-in transaction: WI-20251125-0001"`
- **Transaction Completion**: `"Walk-in transaction completed: WI-20251125-0001"`

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Default business-only filtering
*For any* request to the movements page without the include_system_movements parameter, all returned movements should have movement types in the business movements set (purchase, sale, return, damage, adjustment, transfer)
**Validates: Requirements 1.1**

### Property 2: System movements inclusion when toggled
*For any* request with include_system_movements=true, the returned movements should include both business and system movement types (reservation, release)
**Validates: Requirements 1.5**

### Property 3: Related movements grouping
*For any* sale movement with a transaction reference in its notes, calling groupRelatedMovements should return a structure containing that sale as the primary movement and any reservation/release movements with the same transaction reference as related movements
**Validates: Requirements 2.1**

### Property 4: Transaction reference extraction
*For any* movement with notes containing a walk-in transaction pattern (e.g., "WI-20251125-0001"), the getTransactionReferenceAttribute should extract and return the transaction ID correctly
**Validates: Requirements 2.4**

### Property 5: Reason extraction from notes
*For any* movement with notes containing "(Reason: text)", the getReasonAttribute should extract the reason text correctly
**Validates: Requirements 3.1**

### Property 6: Clean notes separation
*For any* movement with notes containing structured data (reasons or transaction references), the getCleanNotesAttribute should return the notes with structured data removed
**Validates: Requirements 3.2**

### Property 7: Multiple filter combination
*For any* combination of filters (movement_type, location, date_range), the query should return only movements that match all specified criteria
**Validates: Requirements 4.4**

### Property 8: Quantity color coding
*For any* movement, if the quantity is positive it should be assigned green color class, if negative it should be assigned red color class
**Validates: Requirements 5.1**

### Property 9: Consistent type badge colors
*For any* movement type, the same type should always map to the same badge color class across all movements
**Validates: Requirements 5.2**

## Error Handling

### Invalid Filter Parameters

- **Scenario**: User provides invalid movement_type or location values
- **Handling**: Ignore invalid values, apply only valid filters
- **User Feedback**: No error message, silently filter out invalid values

### Missing Transaction References

- **Scenario**: Movement notes reference a transaction that no longer exists
- **Handling**: Display the reference as plain text without link
- **User Feedback**: No special indication

### Grouping Failures

- **Scenario**: Related movements cannot be found or grouped
- **Handling**: Display movement ungrouped
- **User Feedback**: No error, movement appears normally

### Empty Results

- **Scenario**: Filters result in no movements
- **Handling**: Display empty state message
- **User Feedback**: "No inventory movements found matching your filters"

## Testing Strategy

### Unit Tests

Unit tests will cover:

1. **Movement Classification**
   - Test `isBusinessMovement()` returns true for business types
   - Test `isSystemMovement()` returns true for system types
   - Test edge case with unknown movement type

2. **Note Parsing**
   - Test reason extraction with various formats
   - Test transaction reference extraction with various patterns
   - Test clean notes with multiple structured elements
   - Test empty notes handling

3. **Filter Logic**
   - Test business-only filter excludes system movements
   - Test include_system_movements includes all types
   - Test multiple filter combination

4. **Grouping Logic**
   - Test movements with same transaction reference are grouped
   - Test movements without references remain ungrouped
   - Test mixed business and system movements grouping

### Property-Based Tests

Property-based tests will verify universal behaviors using PHPUnit with a property testing approach (generating multiple test cases):

1. **Property 1: Default business-only filtering**
   - Generate random movement collections with mixed types
   - Apply default filter
   - Verify all results are business movements

2. **Property 2: System movements inclusion when toggled**
   - Generate random movement collections
   - Apply filter with include_system_movements=true
   - Verify results include both business and system types

3. **Property 3: Related movements grouping**
   - Generate movements with transaction references
   - Apply grouping
   - Verify each group has correct primary and related movements

4. **Property 4: Transaction reference extraction**
   - Generate notes with various transaction reference formats
   - Extract references
   - Verify correct ID extraction

5. **Property 5: Reason extraction from notes**
   - Generate notes with various reason formats
   - Extract reasons
   - Verify correct text extraction

6. **Property 6: Clean notes separation**
   - Generate notes with mixed structured and free-form content
   - Extract clean notes
   - Verify structured data is removed

7. **Property 7: Multiple filter combination**
   - Generate random filter combinations
   - Apply filters
   - Verify all results match all criteria

8. **Property 8: Quantity color coding**
   - Generate movements with various quantities
   - Get color classes
   - Verify positive=green, negative=red

9. **Property 9: Consistent type badge colors**
   - Generate multiple movements of same type
   - Get badge colors
   - Verify all instances have same color

### Integration Tests

Integration tests will verify:

1. **Full Page Rendering**
   - Test movements page loads with default filters
   - Test toggle changes filter state
   - Test grouped movements render correctly

2. **Filter Persistence**
   - Test filter selections persist in query parameters
   - Test page refresh maintains filter state

3. **Transaction Links**
   - Test transaction reference links navigate correctly
   - Test missing transactions display as plain text

## Implementation Notes

### Performance Considerations

1. **Grouping Performance**: Grouping should be done in PHP after fetching, not in SQL, to avoid complex joins
2. **Pagination**: Apply filters before pagination to ensure accurate page counts
3. **Eager Loading**: Load product, variant, and performedBy relationships to avoid N+1 queries

### Backward Compatibility

- Existing movement records require no migration
- Existing API endpoints remain unchanged
- New filter parameters are optional with sensible defaults

### Future Enhancements

1. **Collapsible Groups**: Allow users to collapse/expand grouped movements
2. **Export Filtering**: Apply same filters to export functionality
3. **Movement Templates**: Save common filter combinations
4. **Real-time Updates**: WebSocket updates for new movements
