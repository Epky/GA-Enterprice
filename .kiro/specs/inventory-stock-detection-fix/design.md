# Design Document

## Overview

This design addresses a critical bug in the inventory stock detection system where the `total_stock` attribute incorrectly calculates available stock by only summing `quantity_available`, ignoring `quantity_reserved`. This causes the walk-in transaction system to display "Insufficient stock to reserve. Available: 0" even when stock exists but is reserved.

The fix involves:
1. Correcting the `getTotalStockAttribute()` method in the Product model to properly aggregate all stock
2. Ensuring stock availability checks use `quantity_available` (not total stock)
3. Improving error messages to clearly indicate available vs. reserved stock
4. Maintaining consistency across all stock-related calculations

## Architecture

The inventory system follows a layered architecture:

```
┌─────────────────────────────────────┐
│   WalkInTransactionService          │
│   (Business Logic Layer)            │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   InventoryService                  │
│   (Inventory Management Layer)      │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Product & Inventory Models        │
│   (Data Access Layer)               │
└─────────────────────────────────────┘
```

### Key Components

1. **Product Model**: Provides stock calculation attributes and relationships
2. **Inventory Model**: Manages stock quantities at specific locations
3. **WalkInTransactionService**: Handles transaction logic and stock checks
4. **InventoryService**: Manages stock reservations and movements

## Components and Interfaces

### Product Model Changes

**Current Implementation (Buggy):**
```php
public function getTotalStockAttribute(): int
{
    return $this->inventory->sum('quantity_available');
}
```

**Fixed Implementation:**
```php
public function getTotalStockAttribute(): int
{
    return $this->inventory->sum(function ($inventory) {
        return $inventory->quantity_available + $inventory->quantity_reserved;
    });
}

public function getAvailableStockAttribute(): int
{
    return $this->inventory->sum('quantity_available');
}
```

### WalkInTransactionService Changes

**Stock Check Logic:**
```php
// Before: Uses total_stock (incorrect)
$availableStock = $product->total_stock;

// After: Uses available_stock (correct)
$availableStock = $product->available_stock;
```

**Error Message Improvements:**
```php
// Enhanced error messages that distinguish between total and available stock
throw ValidationException::withMessages([
    'quantity' => "Insufficient stock. Available: {$availableStock}"
]);
```

## Data Models

### Inventory Table Structure
```
inventory
├── id (primary key)
├── product_id (foreign key)
├── variant_id (foreign key, nullable)
├── location (string)
├── quantity_available (integer) - Stock that can be reserved
├── quantity_reserved (integer) - Stock reserved for orders
├── quantity_sold (integer) - Stock that has been sold
├── reorder_level (integer)
├── reorder_quantity (integer)
└── last_restocked_at (timestamp)
```

### Stock Quantity Relationships
- **Total Stock** = quantity_available + quantity_reserved
- **Available Stock** = quantity_available (can be reserved)
- **Reserved Stock** = quantity_reserved (allocated to pending orders)
- **Sold Stock** = quantity_sold (completed transactions)

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Total stock calculation includes all quantities
*For any* product with inventory records, the total_stock attribute should equal the sum of (quantity_available + quantity_reserved) across all inventory locations.
**Validates: Requirements 1.1**

### Property 2: Available stock only counts unreserved quantities
*For any* product with inventory records, the available_stock attribute should equal the sum of quantity_available across all inventory locations.
**Validates: Requirements 1.2, 1.5, 3.2**

### Property 3: Stock aggregation across locations
*For any* product with multiple inventory locations, the total_stock should equal the sum of all location totals.
**Validates: Requirements 1.3, 3.3**

### Property 4: Error messages display available stock
*For any* stock validation error, the error message should contain the quantity_available value, not the total_stock value.
**Validates: Requirements 2.1**

## Error Handling

### Stock Validation Errors

**Insufficient Stock Error:**
```php
throw ValidationException::withMessages([
    'quantity' => "Insufficient stock. Available: {$availableStock}"
]);
```

**Context Information:**
- Display `quantity_available` in error messages
- Optionally show reserved stock for transparency
- Provide clear actionable information

### Edge Cases

1. **No Inventory Records**: Return 0 for both total_stock and available_stock
2. **All Stock Reserved**: total_stock > 0 but available_stock = 0
3. **Multiple Locations**: Aggregate correctly across all locations
4. **Concurrent Reservations**: Use database transactions to prevent race conditions

## Testing Strategy

### Unit Testing

We will write unit tests to verify:
- Product model stock attribute calculations
- Inventory model quantity methods
- Stock validation logic in services
- Error message formatting

**Test Framework**: PHPUnit (Laravel's default)

### Property-Based Testing

We will use property-based testing to verify universal properties across all inputs.

**PBT Library**: We will use **Pest with Faker** for property-based testing in PHP/Laravel. While PHP doesn't have a dedicated PBT library like QuickCheck, we can simulate PBT by generating random test data with Faker and running assertions across many iterations.

**Configuration**: Each property test will run a minimum of 100 iterations with randomly generated data.

**Property Test Tagging**: Each property-based test MUST be tagged with a comment explicitly referencing the correctness property using this format:
```php
// Feature: inventory-stock-detection-fix, Property 1: Total stock calculation includes all quantities
```

**Implementation Approach**:
1. Generate random inventory data (products, locations, quantities)
2. Test that stock calculations hold across all generated scenarios
3. Verify error messages contain correct information
4. Test edge cases (no inventory, all reserved, multiple locations)

### Test Coverage

- **Unit Tests**: Specific examples and edge cases
- **Property Tests**: Universal properties across random inputs
- **Integration Tests**: End-to-end walk-in transaction flows

Both unit and property tests are complementary:
- Unit tests catch specific bugs and verify concrete examples
- Property tests verify general correctness across all possible inputs
