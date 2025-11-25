# Design Document

## Overview

This design simplifies the inventory management for walk-in transactions by removing the unnecessary reservation/release workflow and implementing direct stock deduction upon transaction completion. The system will maintain a clear distinction between walk-in transactions (immediate fulfillment) and online orders (reservation-based fulfillment).

## Architecture

### Current Flow (To Be Removed)
```
Walk-In Transaction:
1. Add Item → Reserve Stock (creates reservation movement)
2. Update Quantity → Adjust Reservation (creates release/reservation movements)
3. Remove Item → Release Stock (creates release movement)
4. Complete → Fulfill Reserved Stock + Create Sale Movement
5. Cancel → Release All Reserved Stock
```

### New Simplified Flow
```
Walk-In Transaction:
1. Add Item → Check Available Stock (no inventory change)
2. Update Quantity → Re-check Available Stock (no inventory change)
3. Remove Item → Remove from Order (no inventory change)
4. Complete → Direct Stock Deduction + Create Sale Movement
5. Cancel → No inventory changes needed
```

### Online Order Flow (Future - For Reference)
```
Online Order:
1. Place Order → Reserve Stock (creates reservation movement)
2. Modify Order → Adjust Reservation
3. Cancel Order → Release Stock (creates release movement)
4. Fulfill Order → Convert Reserved to Sold (creates sale movement)
```

## Components and Interfaces

### 1. WalkInTransactionService

**Modified Methods:**

```php
// Remove reservation logic
public function addItem(Order $order, array $itemData): OrderItem
{
    // Check available stock
    // Create order item (no inventory change)
    // Recalculate totals
}

// Remove reservation adjustment logic
public function updateItemQuantity(OrderItem $item, int $quantity): OrderItem
{
    // Check available stock
    // Update order item (no inventory change)
    // Recalculate totals
}

// Remove release logic
public function removeItem(OrderItem $item): bool
{
    // Delete order item (no inventory change)
    // Recalculate totals
}

// Simplified completion - direct deduction
public function completeTransaction(Order $order, array $paymentData): Order
{
    // Directly deduct from available stock
    // Create single "sale" movement per item
    // Create payment record
    // Update order status
}

// Simplified cancellation
public function cancelTransaction(Order $order): bool
{
    // Update order status only (no inventory changes)
}
```

### 2. InventoryService

**New Method:**

```php
public function deductStock(Product $product, int $quantity, array $options = []): void
{
    // Directly deduct from available stock
    // Create "sale" movement record
    // No reservation/fulfillment logic
}
```

**Methods to Keep (for online orders):**
- `reserveStock()` - for online orders
- `releaseReservedStock()` - for online orders
- `fulfillReservedStock()` - for online orders

### 3. InventoryMovement Model

**Valid Movement Types:**
- `purchase` - Restock/Purchase
- `sale` - Sale (walk-in or online)
- `return` - Customer return
- `adjustment` - Manual adjustment
- `damage` - Damage/Loss
- `transfer` - Transfer between locations
- ~~`reservation`~~ - Removed from walk-in flow
- ~~`release`~~ - Removed from walk-in flow

**Updated Label Method:**

```php
public function getMovementTypeLabelAttribute(): string
{
    return match ($this->movement_type) {
        'purchase' => 'Restock',
        'sale' => 'Sale',
        'return' => 'Return',
        'adjustment' => 'Adjustment',
        'damage' => 'Damage/Loss',
        'transfer' => 'Transfer',
        default => ucfirst($this->movement_type),
    };
}
```

## Data Models

### Order Model
```php
// Add helper method to check transaction type
public function isWalkIn(): bool
{
    return $this->order_type === 'walk_in';
}

public function isOnline(): bool
{
    return $this->order_type === 'online';
}
```

### Inventory Model
```php
// Existing fields remain the same
- quantity_on_hand (total physical stock)
- quantity_available (available for sale)
- quantity_reserved (reserved for pending orders)

// For walk-in: only quantity_available is checked and modified
// For online: all three fields are used
```

### InventoryMovement Model
```php
// Fields remain the same
- movement_type (enum: purchase, sale, return, adjustment, damage, transfer)
- quantity (positive or negative)
- location_from, location_to
- reference_type, reference_id (links to order)
- notes (context about the movement)
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*


### Property Reflection

After reviewing the prework analysis, several properties are redundant:
- Property 2.3 is identical to 1.1 (both test single sale movement on completion)
- Property 2.5 is identical to 1.3 (both test no inventory changes on cancellation)
- Property 3.1 is covered by properties 2.1 and 2.2 (no reservations + direct deduction)

These redundant properties will be consolidated into single comprehensive properties.

### Property 1: Single sale movement on completion
*For any* walk-in transaction with items, when the transaction is completed, the system should create exactly one "sale" inventory movement record per item.
**Validates: Requirements 1.1, 2.3**

### Property 2: No reservation movements for walk-in
*For any* walk-in transaction, adding, updating, or removing items should not create any "reservation" or "release" inventory movement records.
**Validates: Requirements 1.2, 2.1, 3.1**

### Property 3: No movements on cancellation
*For any* walk-in transaction that is cancelled before completion, the system should not create any inventory movement records.
**Validates: Requirements 1.3, 2.5**

### Property 4: Movement type labels
*For any* inventory movement, the displayed label should match the expected label for its movement type (purchase→"Restock", sale→"Sale", return→"Return", adjustment→"Adjustment", damage→"Damage/Loss", transfer→"Transfer").
**Validates: Requirements 1.4**

### Property 5: Direct stock deduction on completion
*For any* walk-in transaction, when completed, the available stock should be reduced by exactly the sum of all item quantities in the transaction.
**Validates: Requirements 2.2**

### Property 6: No inventory changes during transaction
*For any* walk-in transaction in progress, adding, updating, or removing items should not modify the inventory quantities (quantity_available, quantity_reserved, quantity_on_hand) until the transaction is completed.
**Validates: Requirements 2.4**

### Property 7: Transaction type indication
*For any* inventory movement linked to an order, the displayed information should clearly indicate whether the source is a walk-in transaction or online order.
**Validates: Requirements 3.3**

### Property 8: Movement type consistency
*For any* inventory movement record, the movement_type field should be one of the valid types: purchase, sale, return, adjustment, damage, or transfer.
**Validates: Requirements 3.5**

### Property 9: Required movement display fields
*For any* inventory movement displayed to users, the display should include movement type, quantity, date, staff member, and transaction reference.
**Validates: Requirements 4.1**

### Property 10: Walk-in movement context
*For any* inventory movement related to a walk-in transaction, the display should include the order number and customer name.
**Validates: Requirements 4.2**

### Property 11: Movement filtering
*For any* movement history query with filters (date range, movement type, location, or product), the results should only include movements matching all specified filter criteria.
**Validates: Requirements 4.3**

## Error Handling

### Stock Validation Errors
- **Insufficient Stock**: When adding/updating items, if requested quantity exceeds available stock, throw `ValidationException` with clear message showing available quantity
- **Invalid Quantity**: When quantity is not a positive integer, throw `ValidationException`
- **Empty Customer Name**: When customer name is empty or whitespace-only, throw `ValidationException`

### Transaction State Errors
- **Non-Pending Order**: When attempting to modify a non-pending order, throw `ValidationException` with message "Cannot modify a non-pending order"
- **Empty Order**: When attempting to complete an order with no items, throw `ValidationException`
- **Already Completed**: When attempting to complete an already completed order, throw `ValidationException`

### Data Integrity
- All inventory operations must be wrapped in database transactions
- Stock deductions must be atomic (all items succeed or all fail)
- Movement records must be created in the same transaction as stock changes

## Testing Strategy

### Unit Testing

**WalkInTransactionService Tests:**
- Test stock validation when adding items
- Test order total calculations
- Test error handling for invalid states
- Test transaction cancellation
- Test order number generation

**InventoryService Tests:**
- Test direct stock deduction
- Test movement record creation
- Test stock validation
- Test location handling

**Model Tests:**
- Test movement type labels
- Test movement direction calculation
- Test order type helpers (isWalkIn, isOnline)

### Property-Based Testing

We will use **Pest with Pest Property Testing plugin** for PHP property-based tests. Each property test should run a minimum of 100 iterations.

**Property Test Requirements:**
- Each property-based test must be tagged with a comment referencing the correctness property
- Tag format: `// Feature: walk-in-inventory-simplification, Property {number}: {property_text}`
- Each correctness property must be implemented by a SINGLE property-based test
- Tests should use realistic data generators for products, orders, and inventory

**Test Generators Needed:**
- Random walk-in orders with varying item counts
- Random products with varying stock levels
- Random inventory movements of different types
- Random order states (pending, completed, cancelled)

### Integration Testing

**Walk-In Transaction Flow:**
- Test complete flow from creation to completion
- Test cancellation flow
- Test multiple items in single transaction
- Test concurrent transactions on same product

**Movement History Display:**
- Test filtering by various criteria
- Test pagination
- Test movement type labels
- Test transaction context display

## Implementation Notes

### Migration Strategy

1. **No Database Changes Required**: The inventory_movements table already supports all needed movement types
2. **Code Changes Only**: Modify WalkInTransactionService to remove reservation logic
3. **Backward Compatibility**: Existing movement records remain unchanged; only new walk-in transactions use simplified flow
4. **Online Orders**: Keep reservation logic in InventoryService for future online order feature

### Performance Considerations

- Direct stock deduction is faster than reservation + fulfillment
- Fewer movement records created per transaction
- Simpler queries for movement history (no need to filter out reservation/release)

### Future Enhancements

- Add "before" and "after" stock levels to movement records for better audit trail
- Implement movement history export functionality
- Add sales reports separating walk-in vs online
- Implement online order reservation workflow when needed
