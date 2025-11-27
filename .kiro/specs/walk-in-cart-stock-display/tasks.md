# Implementation Plan

- [-] 1. Enhance WalkInTransactionService with stock calculation methods



  - Add `calculateAvailableStock()` method to compute available stock for a product considering current cart
  - Add `getCartQuantity()` method to get total quantity of a product in cart
  - Ensure methods handle edge cases (empty cart, multiple items of same product)
  - _Requirements: 1.1, 2.4, 3.2_

- [ ] 1.1 Write property test for stock calculation


  - **Property 7: Stock calculation with multiple cart items**
  - **Validates: Requirements 2.4**

- [ ] 2. Update WalkInTransactionController to include stock in responses
  - Modify `searchProducts()` to include `available_stock` in product response
  - Calculate available stock for each product considering current cart state
  - Ensure response format matches ProductSearchResult interface
  - _Requirements: 3.1, 3.2_

- [ ] 2.1 Write property test for search stock calculation
  - **Property 10: Search stock calculation considers cart**
  - **Validates: Requirements 3.2**

- [ ] 3. Update show.blade.php to display stock in cart items
  - Calculate available stock for each cart item in the view
  - Add stock display HTML next to product information
  - Include data attributes for JavaScript manipulation
  - Apply initial color coding based on stock level
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [ ] 3.1 Write property tests for stock display format and color coding
  - **Property 1: Stock display format consistency**
  - **Property 2: Low stock color coding**
  - **Property 3: Adequate stock color coding**
  - **Property 4: Zero stock color coding**
  - **Validates: Requirements 1.2, 1.3, 1.4, 1.5**

- [ ] 4. Implement JavaScript StockManager class
  - Create StockManager class to handle client-side stock updates
  - Implement `updateStockDisplay()` method for quantity changes
  - Implement `updateStockColor()` method for color coding
  - Implement `updateQuantityControls()` method for button states
  - Add stock cache to minimize recalculations
  - _Requirements: 2.1, 2.2, 2.3, 2.5, 4.2, 4.3, 4.5_

- [ ] 4.1 Write property tests for quantity-stock relationship
  - **Property 5: Quantity increase decreases available stock**
  - **Property 6: Quantity decrease increases available stock**
  - **Property 8: Color updates after quantity change**
  - **Validates: Requirements 2.1, 2.2, 2.5**

- [ ] 5. Integrate StockManager with existing quantity update functions
  - Modify `updateQuantityInstant()` to call StockManager methods
  - Update stock display when quantity changes
  - Update color coding after stock changes
  - Update button states based on available stock
  - _Requirements: 2.1, 2.2, 2.3, 2.5_

- [ ] 5.1 Write property test for button state management
  - **Property 13: Plus button disabled at stock limit**
  - **Property 14: Button re-enables when stock increases**
  - **Validates: Requirements 4.2, 4.3, 4.5**

- [ ] 6. Add stock display to product search results
  - Update `displaySearchResults()` function to include stock display
  - Show available stock next to price in search results
  - Apply color coding to search result stock displays
  - Update stock display when product is added to cart
  - _Requirements: 3.1, 3.3, 3.4_

- [ ] 6.1 Write property test for search-cart color consistency
  - **Property 11: Consistent color coding across contexts**
  - **Validates: Requirements 3.3**

- [ ] 7. Implement stock validation in addProduct function
  - Check available stock before allowing product addition
  - Show error message if insufficient stock
  - Prevent addition when stock is zero or negative
  - _Requirements: 4.1, 4.4_

- [ ] 7.1 Write unit test for insufficient stock error handling
  - Test error message display when attempting to add product with insufficient stock
  - **Validates: Requirements 4.4**

- [ ] 8. Add stock limit validation to quantity controls
  - Disable plus button when quantity equals available stock
  - Add visual indicator (opacity, cursor) for disabled state
  - Prevent quantity increase beyond available stock
  - Re-enable button when stock becomes available
  - _Requirements: 4.1, 4.2, 4.3, 4.5_

- [ ] 8.1 Write property test for quantity increase prevention
  - **Property 12: Quantity increase prevention at stock limit**
  - **Validates: Requirements 4.1**

- [ ] 9. Add accessibility attributes to stock displays
  - Add `aria-label` to stock indicators
  - Add `aria-live="polite"` for dynamic updates
  - Ensure keyboard navigation works with quantity controls
  - Test with screen readers
  - _Requirements: 5.1, 5.5_

- [ ] 9.1 Write unit tests for accessibility attributes
  - Test aria-label presence and format
  - Test aria-live attribute on stock displays
  - _Requirements: 5.1_

- [ ] 10. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 11. Add CSS styling for stock displays
  - Add color classes for stock levels (green, orange, red)
  - Ensure consistent typography with existing elements
  - Test responsive layout on mobile devices
  - Verify color contrast meets WCAG AA standards
  - _Requirements: 1.3, 1.4, 1.5, 5.2, 5.3, 5.4_

- [ ] 11.1 Write property test for consistent styling
  - **Property 15: Consistent stock display across all cart items**
  - **Validates: Requirements 5.5**

- [ ] 12. Add error handling for stock calculation failures
  - Handle network errors during stock updates
  - Revert UI changes on server errors
  - Show user-friendly error messages
  - Log errors for monitoring
  - _Requirements: 2.3_

- [ ] 12.1 Write unit tests for error handling scenarios
  - Test network error handling
  - Test stock calculation error handling
  - Test UI revert on errors

- [ ] 13. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
