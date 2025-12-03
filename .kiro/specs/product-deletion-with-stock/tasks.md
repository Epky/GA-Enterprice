# Implementation Plan

- [x] 1. Add total stock computed property to Product model




  - Add `getTotalStockAttribute()` method to Product model
  - Method should sum `quantity_available` from all related inventory records
  - _Requirements: 1.2, 2.3_

- [x] 2. Create delete confirmation modal component





  - [x] 2.1 Create Blade component at `resources/views/components/delete-confirmation-modal.blade.php`


    - Accept props: productId, productName, stockQuantity, deleteRoute
    - Render modal with backdrop and centered dialog
    - Display product name and stock information
    - Include Cancel and Delete buttons with appropriate styling
    - _Requirements: 1.1, 1.2, 2.4_

  - [x] 2.2 Implement conditional warning message logic

    - Show warning message when stockQuantity > 0
    - Show standard confirmation when stockQuantity = 0
    - Include stock quantity in warning message
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 2.3 Add destructive action styling

    - Use red/destructive colors for delete button
    - Add warning icon for products with stock
    - Ensure consistent styling with existing UI
    - _Requirements: 2.4_

- [x] 3. Create JavaScript modal controller





  - [x] 3.1 Create JavaScript file for modal interactions


    - Create `resources/js/product-deletion.js` or add inline script
    - Implement `showDeleteModal()` function
    - Implement `hideDeleteModal()` function
    - Implement `confirmDeletion()` function
    - _Requirements: 1.1, 1.4_

  - [x] 3.2 Add event handlers for modal interactions


    - Handle delete button click to show modal
    - Handle cancel button click to hide modal
    - Handle confirm button click to submit form
    - Handle Escape key to close modal
    - Handle click outside modal to close
    - _Requirements: 1.1, 1.4_

  - [x] 3.3 Implement accessibility features


    - Trap focus within modal when open
    - Return focus to delete button after close
    - Add aria-labels to buttons
    - Ensure keyboard navigation works
    - _Requirements: 1.1, 1.4_

- [x] 4. Update product list view to use confirmation modal





  - [x] 4.1 Modify `resources/views/staff/products/index.blade.php`


    - Replace direct delete form submission with modal trigger
    - Add modal component with product data
    - Pass product ID, name, stock, and delete route to modal
    - _Requirements: 1.1, 3.1_

  - [x] 4.2 Update delete button to trigger modal


    - Change onclick handler to show modal instead of submitting form
    - Pass product data to JavaScript function
    - Maintain existing button styling and position
    - _Requirements: 1.1, 3.1_

- [x] 5. Update product detail view to use confirmation modal





  - [x] 5.1 Modify `resources/views/staff/products/show.blade.php`


    - Add modal component with product data
    - Update delete button to trigger modal
    - Ensure consistent modal behavior with list page
    - _Requirements: 1.1, 3.2, 3.4_

- [x] 6. Update product edit view to use confirmation modal






  - [x] 6.1 Modify `resources/views/staff/products/edit.blade.php`

    - Add modal component with product data
    - Update delete button to trigger modal
    - Ensure consistent modal behavior with other pages
    - _Requirements: 1.1, 3.3, 3.4_

- [x] 7. Verify controller and service handle deletion correctly





  - Review `StaffProductController::destroy()` method
  - Review `ProductService::deleteProduct()` method
  - Confirm no stock checking logic prevents deletion
  - Confirm success message is returned after deletion
  - _Requirements: 1.3, 1.5_

- [-] 8. Write property-based tests for deletion workflow





  - [x] 8.1 Write property test for modal display universality


    - **Property 1: Modal Display Universality**
    - **Validates: Requirements 1.1**
    - Generate random products with varying stock levels
    - Test that modal appears for all products

  - [x] 8.2 Write property test for modal content completeness





    - **Property 2: Modal Content Completeness**
    - **Validates: Requirements 1.2**
    - Generate random products with random names and stock
    - Test that modal contains correct product name and stock quantity

  - [x] 8.3 Write property test for deletion confirmation effect



    - **Property 3: Deletion Confirmation Effect**
    - **Validates: Requirements 1.3**
    - Generate random products
    - Test that product doesn't exist after confirmed deletion

  - [x] 8.4 Write property test for cancellation preservation




    - **Property 4: Cancellation Preservation**
    - **Validates: Requirements 1.4**
    - Generate random products with random attributes
    - Test that product remains unchanged after cancellation

  - [x] 8.5 Write property test for post-deletion redirect





    - **Property 5: Post-Deletion Redirect**
    - **Validates: Requirements 1.5**
    - Generate random products
    - Test that deletion results in redirect to product list with success message

  - [x] 8.6 Write property test for stock warning display




    - **Property 6: Stock Warning Display**
    - **Validates: Requirements 2.1**
    - Generate random products with stock > 0
    - Test that modal contains warning message

  - [x] 8.7 Write property test for no warning with zero stock





    - **Property 7: No Warning for Zero Stock**
    - **Validates: Requirements 2.2**
    - Generate random products with stock = 0
    - Test that modal does not contain warning message

  - [ ] 8.8 Write property test for stock quantity accuracy






    - **Property 8: Stock Quantity Accuracy**
    - **Validates: Requirements 2.3**
    - Generate random products with inventory across multiple locations
    - Test that displayed stock equals sum of all inventory quantities

  - [x] 8.9 Write property test for modal consistency




    - **Property 9: Modal Consistency**
    - **Validates: Requirements 3.4**
    - Generate random products and random page contexts
    - Test that modal structure and content are identical across all pages

- [x] 9. Write unit tests for edge cases





  - [x] 9.1 Test modal rendering with zero stock


    - Verify no warning message appears
    - Verify standard confirmation message appears

  - [x] 9.2 Test modal rendering with positive stock


    - Verify warning message appears
    - Verify stock quantity is displayed correctly

  - [x] 9.3 Test total stock calculation


    - Create product with inventory at multiple locations
    - Verify getTotalStockAttribute() returns correct sum

  - [x] 9.4 Test controller deletion response


    - Test successful deletion returns redirect
    - Test redirect includes success message
    - Test deletion of non-existent product returns 404

- [x] 10. Write integration tests for complete workflow








  - [x] 10.1 Test complete deletion flow from list page



    - Navigate to product list
    - Click delete button
    - Verify modal appears
    - Confirm deletion
    - Verify redirect and success message
    - Verify product is deleted


  - [x] 10.2 Test cancellation flow from detail page





    - Navigate to product detail
    - Click delete button
    - Verify modal appears
    - Cancel deletion
    - Verify modal closes
    - Verify product still exists


  - [ ] 10.3 Test cross-page consistency
    - Test deletion from list page
    - Test deletion from detail page
    - Test deletion from edit page
    - Verify modal is identical in all cases

- [ ] 11. Final checkpoint - Ensure all tests pass




  - Ensure all tests pass, ask the user if questions arise.
