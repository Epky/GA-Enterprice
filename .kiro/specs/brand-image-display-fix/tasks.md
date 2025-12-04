# Implementation Plan

- [x] 1. Investigate and document current state


  - Compare category and brand image display implementations
  - Identify exact differences in image path resolution
  - Document current product count attribute usage
  - _Requirements: 1.1, 1.2, 2.1, 2.2, 3.1_


- [x] 2. Fix brand index view image display

  - Update logo_url image src to use correct storage path resolution
  - Implement placeholder fallback for missing logos
  - Apply consistent CSS classes and sizing
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 3.1, 3.2, 3.3_

- [x] 3. Fix product count attribute names in view


  - Update view to use products_count instead of product_count
  - Update view to use active_products_count instead of active_product_count
  - Add null coalescing operators for safety
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [x] 4. Verify Brand model accessors


  - Check if Brand model has conflicting accessor methods
  - Remove or update any accessor methods that conflict with withCount
  - Ensure model is compatible with controller's data loading
  - _Requirements: 2.3, 2.5_

- [x] 5. Test brand image display


  - Test with brands that have logo images
  - Test with brands without logo images
  - Test with missing image files
  - Verify consistent styling with categories
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 3.1, 3.2, 3.3_

- [x] 6. Test product count display

  - Verify total product counts display correctly
  - Verify active product counts display correctly
  - Test with brands having zero products
  - Test with brands having many products
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [x] 7. Checkpoint - Ensure all tests pass


  - Ensure all tests pass, ask the user if questions arise.
