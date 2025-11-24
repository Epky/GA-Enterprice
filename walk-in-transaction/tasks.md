# Implementation Plan

- [x] 1. Set up walk-in transaction service and models





  - Create WalkInTransactionService class with core business logic
  - Add walk-in specific scopes and methods to Order model
  - Set up service provider registration
  - _Requirements: 1.2, 1.3, 6.1_

- [x] 1.1 Write property test for transaction creation


  - **Property 1: Transaction creation with pending status**
  - **Validates: Requirements 1.2**

- [x] 1.2 Write property test for staff association


  - **Property 2: Staff association**
  - **Validates: Requirements 1.3**

- [x] 1.3 Write property test for whitespace name rejection


  - **Property 3: Whitespace name rejection**
  - **Validates: Requirements 1.4**

- [x] 2. Implement transaction creation and customer name handling





  - Implement createTransaction method in service
  - Add customer name validation (reject whitespace-only)
  - Generate unique transaction reference numbers (format: WI-YYYYMMDD-####)
  - Associate transaction with authenticated staff member
  - _Requirements: 1.2, 1.3, 1.4, 6.5_

- [x] 2.1 Write property test for unique reference numbers


  - **Property 16: Unique reference numbers**
  - **Validates: Requirements 6.5**

- [x] 3. Implement product selection and search functionality





  - Create product search method with name/SKU filtering
  - Add stock availability checking
  - Implement addItem method to add products to transaction
  - Handle out-of-stock and insufficient stock scenarios
  - _Requirements: 2.2, 2.3, 2.4, 2.5_

- [x] 3.1 Write property test for product search filtering


  - **Property 4: Product search filtering**
  - **Validates: Requirements 2.2**

- [x] 3.2 Write property test for product addition with default quantity


  - **Property 5: Product addition with default quantity**
  - **Validates: Requirements 2.3**

- [x] 4. Implement quantity management and validation





  - Add quantity validation (positive integers only)
  - Implement updateItemQuantity method
  - Add stock availability validation for quantities
  - Calculate and update item subtotals
  - _Requirements: 3.2, 3.3, 3.4, 3.5_

- [x] 4.1 Write property test for positive integer quantity validation


  - **Property 6: Positive integer quantity validation**
  - **Validates: Requirements 3.2**

- [x] 4.2 Write property test for subtotal calculation


  - **Property 7: Subtotal calculation**
  - **Validates: Requirements 3.4**

- [x] 5. Implement transaction items management





  - Implement removeItem method
  - Add item list display logic
  - Ensure item display includes all required fields
  - Handle multiple items in transaction
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [x] 5.1 Write property test for item list growth


  - **Property 8: Item list growth**
  - **Validates: Requirements 4.2**

- [x] 5.2 Write property test for item display completeness


  - **Property 9: Item display completeness**
  - **Validates: Requirements 4.3**

- [x] 5.3 Write property test for item removal and recalculation


  - **Property 10: Item removal and recalculation**
  - **Validates: Requirements 4.4**

- [x] 5.4 Write property test for quantity modification updates


  - **Property 11: Quantity modification updates**
  - **Validates: Requirements 4.5**

- [x] 6. Implement total calculation and formatting





  - Implement calculateTotal method
  - Ensure total equals sum of all item subtotals
  - Add currency formatting (two decimal places)
  - Handle empty transaction (zero total)
  - Recalculate on item changes
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 6.1 Write property test for total equals sum of subtotals


  - **Property 12: Total equals sum of subtotals**
  - **Validates: Requirements 5.1**

- [x] 6.2 Write property test for currency formatting

  - **Property 13: Currency formatting**
  - **Validates: Requirements 5.5**

- [x] 7. Implement transaction completion with inventory updates




  - Implement completeTransaction method with database transaction
  - Integrate with InventoryService for stock reduction
  - Create inventory movement records for each item
  - Record transaction timestamp
  - Validate transaction has items before completion
  - Implement rollback on inventory update failure
  - _Requirements: 6.1, 6.2, 6.3, 7.1, 7.2, 7.3, 7.4, 7.5_

- [x] 7.1 Write property test for completion status


  - **Property 14: Completion status**
  - **Validates: Requirements 6.1**

- [x] 7.2 Write property test for timestamp recording

  - **Property 15: Timestamp recording**
  - **Validates: Requirements 6.2**

- [x] 7.3 Write property test for stock reduction

  - **Property 17: Stock reduction**
  - **Validates: Requirements 7.1**

- [x] 7.4 Write property test for movement record creation

  - **Property 18: Movement record creation**
  - **Validates: Requirements 7.2, 7.4**

- [x] 7.5 Write property test for transaction atomicity


  - **Property 19: Transaction atomicity**
  - **Validates: Requirements 7.3**

- [x] 7.6 Write property test for inventory verification before completion

  - **Property 20: Inventory verification before completion**
  - **Validates: Requirements 7.5**

- [x] 8. Implement receipt generation





  - Implement generateReceipt method
  - Include customer name, date, staff name, reference number
  - Include all item details (name, quantity, price, subtotal)
  - Include transaction total
  - Format receipt for printing/display
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [x] 8.1 Write property test for receipt header completeness


  - **Property 21: Receipt header completeness**
  - **Validates: Requirements 8.1**

- [x] 8.2 Write property test for receipt item details


  - **Property 22: Receipt item details**
  - **Validates: Requirements 8.2**

- [x] 8.3 Write property test for receipt total display


  - **Property 23: Receipt total display**
  - **Validates: Requirements 8.3**

- [x] 9. Implement transaction cancellation





  - Implement cancelTransaction method
  - Validate transaction is pending before cancellation
  - Mark transaction as cancelled without inventory changes
  - Prevent cancellation of completed transactions
  - _Requirements: 9.1, 9.2, 9.5_

- [x] 9.1 Write property test for pending transaction cancellation


  - **Property 24: Pending transaction cancellation**
  - **Validates: Requirements 9.1**

- [x] 9.2 Write property test for inventory preservation on cancellation


  - **Property 25: Inventory preservation on cancellation**
  - **Validates: Requirements 9.2**

- [x] 10. Implement transaction history and search





  - Implement getTransactionHistory method with pagination
  - Order transactions by date descending
  - Include all required display fields
  - Implement search/filter by customer name, date range, reference number
  - Add transaction detail view
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [x] 10.1 Write property test for history ordering


  - **Property 26: History ordering**
  - **Validates: Requirements 10.1**

- [x] 10.2 Write property test for history display fields


  - **Property 27: History display fields**
  - **Validates: Requirements 10.2**

- [x] 10.3 Write property test for transaction detail completeness


  - **Property 28: Transaction detail completeness**
  - **Validates: Requirements 10.3**

- [x] 10.4 Write property test for history search filtering


  - **Property 29: History search filtering**
  - **Validates: Requirements 10.4**

- [x] 11. Create controller and request validation





  - Create WalkInTransactionController
  - Implement index, create, store, show, cancel, receipt, history actions
  - Create WalkInTransactionStoreRequest for validation
  - Add staff middleware protection
  - Handle validation errors with user-friendly messages
  - _Requirements: 1.1, 1.4, 1.5, 2.1, 3.1, 4.1, 6.4, 9.3, 9.4_

- [x] 11.1 Write unit tests for controller actions


  - Test all controller methods
  - Test middleware protection
  - Test validation error handling
  - Test response formats

- [x] 12. Create user interface views





  - Create transaction creation form (customer name input)
  - Create product selection interface with search
  - Create transaction items display with quantity controls
  - Create transaction summary with total
  - Create receipt view with print/download options
  - Create transaction history list view
  - Create transaction detail view
  - Add confirmation prompts for cancellation
  - _Requirements: 1.1, 1.5, 2.1, 3.1, 4.1, 6.4, 8.4, 9.3, 9.4_

- [x] 12.1 Write feature tests for complete transaction workflow


  - Test end-to-end transaction creation to completion
  - Test product search and selection
  - Test quantity modifications
  - Test transaction cancellation
  - Test receipt generation

- [x] 13. Add routes and navigation





  - Add routes for all walk-in transaction actions
  - Add navigation link in staff dashboard
  - Protect routes with staff middleware
  - _Requirements: 1.1_



- [x] 14. Checkpoint - Ensure all tests pass



  - Ensure all tests pass, ask the user if questions arise.

- [x] 15. Enhance walk-in transaction form with contact number and fix product selection








  - Add contact number field to customer information section
  - Update validation to include optional customer_phone field
  - Fix product search functionality to properly filter products
  - Add quantity input controls for each product in transaction
  - Implement "Add Another Product" functionality for multiple products
  - Update UI to show quantity controls with increment/decrement buttons
  - Ensure transaction items display with editable quantities
  - _Requirements: 1.5, 2.2, 3.1, 4.1, 4.2_


- [x] 15.1 Update customer information form



  - Add contact number input field below customer name
  - Update form validation to accept optional phone number
  - Update transaction creation to store customer_phone
  - Update summary display to show contact number



- [ ] 15.2 Fix product search functionality


  - Verify search endpoint is properly configured
  - Ensure search filters products by name and SKU
  - Test search with various queries
  - Display search results with proper formatting


- [ ] 15.3 Add quantity controls to product selection


  - Add quantity input field when adding products
  - Implement increment/decrement buttons for quantities
  - Add quantity validation (min: 1, max: available stock)

  - Update subtotal calculation when quantity changes

- [ ] 15.4 Implement multiple product addition


  - Ensure products can be added multiple times to transaction
  - Display all added products in transaction items list
  - Allow quantity editing for each item
  - Allow removal of individual items
  - Update total calculation when items change

