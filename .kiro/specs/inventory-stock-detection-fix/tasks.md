# Implementation Plan

- [x] 1. Fix Product model stock calculation attributes





  - Add new `available_stock` attribute that sums only `quantity_available`
  - Update `total_stock` attribute to include both `quantity_available` and `quantity_reserved`
  - Ensure both attributes handle empty inventory collections correctly
  - _Requirements: 1.1, 1.3, 1.4_

- [x] 1.1 Write property test for total stock calculation


  - **Property 1: Total stock calculation includes all quantities**
  - **Validates: Requirements 1.1**

- [x] 1.2 Write property test for available stock calculation


  - **Property 2: Available stock only counts unreserved quantities**
  - **Validates: Requirements 1.2, 1.5, 3.2**

- [x] 1.3 Write property test for stock aggregation across locations


  - **Property 3: Stock aggregation across locations**
  - **Validates: Requirements 1.3, 3.3**

- [x] 2. Update WalkInTransactionService to use available_stock





  - Replace `$product->total_stock` with `$product->available_stock` in `addItem()` method
  - Replace `$product->total_stock` with `$product->available_stock` in `updateItemQuantity()` method
  - Ensure error messages display the correct available stock quantity
  - _Requirements: 1.5, 2.1_

- [x] 2.1 Write property test for error message content


  - **Property 4: Error messages display available stock**
  - **Validates: Requirements 2.1**

- [x] 2.2 Write unit tests for WalkInTransactionService stock checks


  - Test adding items with sufficient stock
  - Test adding items with insufficient available stock
  - Test adding items when stock is reserved
  - Test updating item quantities
  - _Requirements: 1.5, 2.1_

- [x] 3. Verify and test edge cases






  - Test products with no inventory records return 0 for both attributes
  - Test products with all stock reserved (available = 0, total > 0)
  - Test products with inventory across multiple locations
  - Test concurrent stock reservations don't cause race conditions
  - _Requirements: 1.4, 1.3_

- [x] 3.1 Write integration tests for walk-in transaction flow


  - Test complete transaction flow with stock checks
  - Test transaction cancellation releases reserved stock
  - Test transaction completion converts reserved to sold
  - _Requirements: 1.5, 2.1_
-



- [x] 4. Checkpoint - Ensure all tests pass







  - Ensure all tests pass, ask the user if questions arise.
