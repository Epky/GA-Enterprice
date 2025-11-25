# Implementation Plan

- [ ] 1. Update InventoryService to add direct stock deduction method



  - Create `deductStock()` method that directly reduces available stock
  - Method should create a single "sale" movement record
  - Include proper validation and error handling
  - _Requirements: 2.2, 2.3_

- [ ] 2. Refactor WalkInTransactionService to remove reservation logic
  - [ ] 2.1 Update `addItem()` method to remove reservation calls
    - Keep stock availability check
    - Remove `reserveStock()` call
    - Create order item without inventory changes
    - _Requirements: 2.1, 2.4_

  - [ ] 2.2 Write property test for no reservation movements
    - **Property 2: No reservation movements for walk-in**
    - **Validates: Requirements 1.2, 2.1, 3.1**

  - [ ] 2.3 Update `updateItemQuantity()` method to remove reservation adjustments
    - Keep stock availability check
    - Remove `reserveStock()` and `releaseReservedStock()` calls
    - Update order item without inventory changes
    - _Requirements: 2.1, 2.4_

  - [ ] 2.4 Update `removeItem()` method to remove release logic
    - Remove `releaseReservedStock()` call
    - Delete order item without inventory changes
    - _Requirements: 2.1, 2.4_

  - [ ] 2.5 Write property test for no inventory changes during transaction
    - **Property 6: No inventory changes during transaction**
    - **Validates: Requirements 2.4**

  - [ ] 2.6 Update `completeTransaction()` method to use direct deduction
    - Replace reservation fulfillment with direct stock deduction
    - Use new `deductStock()` method from InventoryService
    - Create single "sale" movement per item
    - _Requirements: 1.1, 2.2, 2.3_

  - [ ] 2.7 Write property test for single sale movement on completion
    - **Property 1: Single sale movement on completion**
    - **Validates: Requirements 1.1, 2.3**

  - [ ] 2.8 Write property test for direct stock deduction
    - **Property 5: Direct stock deduction on completion**
    - **Validates: Requirements 2.2**

  - [ ] 2.9 Update `cancelTransaction()` method to remove release logic
    - Remove all `releaseReservedStock()` calls
    - Simply update order status
    - _Requirements: 1.3, 2.5_

  - [ ] 2.10 Write property test for no movements on cancellation
    - **Property 3: No movements on cancellation**
    - **Validates: Requirements 1.3, 2.5**

- [ ] 3. Update InventoryMovement model and views
  - [ ] 3.1 Update movement type label method
    - Simplify labels: purchase→"Restock", sale→"Sale", etc.
    - Remove reservation/release from labels
    - _Requirements: 1.4_

  - [ ] 3.2 Write property test for movement type labels
    - **Property 4: Movement type labels**
    - **Validates: Requirements 1.4**

  - [ ] 3.3 Update movement history view filters
    - Remove "reservation" and "release" from filter options
    - Keep only: restock, sale, return, adjustment, damage, transfer
    - _Requirements: 1.5_

  - [ ] 3.4 Add transaction type indication to movement display
    - Show "Walk-In" or "Online" badge for movements linked to orders
    - Display order number and customer name for walk-in movements
    - _Requirements: 3.3, 4.2_

  - [ ] 3.5 Write property test for transaction type indication
    - **Property 7: Transaction type indication**
    - **Validates: Requirements 3.3**

  - [ ] 3.6 Write property test for walk-in movement context
    - **Property 10: Walk-in movement context**
    - **Validates: Requirements 4.2**

- [ ] 4. Add Order model helper methods
  - Add `isWalkIn()` and `isOnline()` helper methods
  - Use these methods throughout the codebase for clarity
  - _Requirements: 3.1, 3.3_

- [ ] 5. Update tests and validation
  - [ ] 5.1 Write property test for movement type consistency
    - **Property 8: Movement type consistency**
    - **Validates: Requirements 3.5**

  - [ ] 5.2 Write property test for required movement display fields
    - **Property 9: Required movement display fields**
    - **Validates: Requirements 4.1**

  - [ ] 5.3 Write property test for movement filtering
    - **Property 11: Movement filtering**
    - **Validates: Requirements 4.3**

  - [ ] 5.4 Update existing unit tests for WalkInTransactionService
    - Remove tests for reservation logic
    - Add tests for direct deduction
    - Update test expectations

  - [ ] 5.5 Update existing feature tests for walk-in transaction flow
    - Update assertions to expect no reservation movements
    - Verify direct stock deduction
    - Test complete flow from creation to completion

- [ ] 6. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
