# Implementation Plan

- [ ] 1. Update Product model with stock accessors
  - Add `in_stock` accessor that returns boolean based on inventory sum
  - Add `total_stock` accessor that calculates total available quantity
  - Ensure inventory relationship is properly loaded
  - _Requirements: 3.4_

- [ ] 2. Update CustomerController to redirect to shop route
  - Modify `show` method to redirect to `products.show` route
  - Preserve any query parameters during redirect
  - Use 301 permanent redirect status
  - _Requirements: 1.1, 1.2, 1.3, 2.2_

- [ ] 3. Verify shop.show view handles all requirements
  - Confirm breadcrumb navigation is present
  - Confirm authentication-based button rendering (guest vs authenticated)
  - Confirm all required product information is displayed
  - Confirm related products section exists
  - _Requirements: 1.4, 1.5, 3.1, 3.2, 3.3, 3.5_

- [ ] 4. Update any links that use customer.product.show route
  - Search codebase for `customer.product.show` route references
  - Replace with `products.show` route
  - Verify no broken links remain
  - _Requirements: 2.1, 2.2_

- [ ] 5. Write property test for view template consistency
  - **Property 1: View Template Consistency**
  - **Validates: Requirements 2.2**
  - Test that ShopController and CustomerController use same view template
  - Generate random products and verify template name matches

- [ ] 6. Write property test for guest user access
  - **Property 2: Guest User Access**
  - **Validates: Requirements 3.1**
  - Test that guest users can access product detail pages
  - Generate random products and verify successful response without authentication

- [ ] 7. Write property test for authentication-based rendering
  - **Property 3: Guest User Login Prompt**
  - **Property 4: Authenticated User Cart Access**
  - **Validates: Requirements 3.2, 3.3**
  - Test guest users see "Login to Purchase" button
  - Test authenticated users see "Add to Cart" button
  - Generate random products and verify correct button appears based on auth state

- [ ] 8. Write property test for required information display
  - **Property 5: Required Information Presence**
  - **Validates: Requirements 1.5**
  - Test that all required elements are present in rendered HTML
  - Generate random products and verify images, price, stock, description elements exist

- [ ] 9. Write property test for stock status accuracy
  - **Property 6: Stock Status Accuracy**
  - **Validates: Requirements 3.4**
  - Test that displayed stock matches calculated inventory
  - Generate products with various inventory levels and verify display accuracy

- [ ] 10. Write property test for related products
  - **Property 7: Related Products Display**
  - **Validates: Requirements 3.5**
  - Test that related products section appears when applicable
  - Generate products with categories and verify related products display

- [ ] 11. Write property test for breadcrumb navigation
  - **Property 8: Breadcrumb Navigation**
  - **Validates: Requirements 1.4**
  - Test that breadcrumb elements are present in rendered HTML
  - Generate random products and verify breadcrumb structure exists

- [ ] 12. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 13. Manual testing verification
  - Test navigation from home page to product detail
  - Test navigation from products page to product detail
  - Test navigation from category page to product detail
  - Verify identical design across all entry points
  - Test as guest user - verify login prompt
  - Test as authenticated user - verify cart functionality
  - _Requirements: 1.1, 1.2, 1.3, 3.2, 3.3_
