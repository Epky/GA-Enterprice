# Implementation Plan

- [x] 1. Enhance InventoryMovement model with classification and parsing methods





  - Add `isBusinessMovement()` method to check if movement type is in business set
  - Add `isSystemMovement()` method to check if movement type is in system set
  - Add `getTransactionReferenceAttribute()` accessor to extract transaction IDs from notes
  - Add `getReasonAttribute()` accessor to parse reason from notes
  - Add `getCleanNotesAttribute()` accessor to return notes without structured data
  - Add constants for business and system movement type arrays
  - _Requirements: 1.1, 2.4, 3.1, 3.2_

- [x] 1.1 Write property test for transaction reference extraction


  - **Property 4: Transaction reference extraction**
  - **Validates: Requirements 2.4**

- [x] 1.2 Write property test for reason extraction


  - **Property 5: Reason extraction from notes**
  - **Validates: Requirements 3.1**

- [x] 1.3 Write property test for clean notes separation


  - **Property 6: Clean notes separation**
  - **Validates: Requirements 3.2**

- [x] 1.4 Write unit tests for movement classification methods


  - Test `isBusinessMovement()` with all business types
  - Test `isSystemMovement()` with system types
  - Test edge cases with unknown types
  - _Requirements: 1.1_

- [x] 2. Add filtering logic to InventoryService





  - Update `getInventoryMovements()` to accept `include_system_movements` parameter
  - Implement default filtering to exclude reservation and release types
  - Add query scope to filter by movement type classification
  - Ensure filter combines properly with existing filters (location, date, etc.)
  - _Requirements: 1.1, 1.5, 4.4_

- [x] 2.1 Write property test for default business-only filtering


  - **Property 1: Default business-only filtering**
  - **Validates: Requirements 1.1**

- [x] 2.2 Write property test for system movements inclusion


  - **Property 2: System movements inclusion when toggled**
  - **Validates: Requirements 1.5**

- [x] 2.3 Write property test for multiple filter combination


  - **Property 7: Multiple filter combination**
  - **Validates: Requirements 4.4**

- [x] 3. Implement movement grouping logic in InventoryService





  - Add `groupRelatedMovements()` method to group by transaction reference
  - Extract transaction references from notes to identify related movements
  - Structure grouped data with 'primary' (business movement) and 'related' (system movements)
  - Add `group_related` parameter to `getInventoryMovements()` method
  - _Requirements: 2.1_

- [x] 3.1 Write property test for related movements grouping


  - **Property 3: Related movements grouping**
  - **Validates: Requirements 2.1**

- [x] 3.2 Write unit tests for grouping edge cases


  - Test movements without transaction references remain ungrouped
  - Test movements with same reference are grouped together
  - Test mixed business and system movements
  - _Requirements: 2.1_

- [x] 4. Update StaffInventoryController movements method





  - Add `include_system_movements` request parameter handling (default: false)
  - Add `group_related` request parameter handling (default: true)
  - Pass parameters to InventoryService
  - Pass toggle state to view for UI rendering
  - _Requirements: 1.4, 1.5_

- [x] 5. Create Blade component for grouped movement display





  - Create `resources/views/components/movement-group.blade.php` component
  - Display primary movement prominently with full details
  - Display related system movements as nested, indented sub-items
  - Add visual indicators (icons, indentation) for hierarchy
  - Format transaction references as clickable links
  - _Requirements: 2.2, 2.3, 2.5_

- [x] 6. Create Blade component for formatted movement notes





  - Create `resources/views/components/movement-notes.blade.php` component
  - Display extracted reason as a colored badge
  - Display clean notes as regular text
  - Display transaction references as clickable links
  - Show subtle placeholder for empty notes
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 7. Update movements view with enhanced filtering UI





  - Add "Show System Movements" toggle checkbox to filter section
  - Update movement type filter to group options (Business / System)
  - Add visual indicator when system movements are shown
  - Maintain filter state in query parameters
  - _Requirements: 1.4, 4.1_

- [x] 8. Update movements table to use new components and styling




  - Replace inline note rendering with movement-notes component
  - Implement grouped movement display using movement-group component
  - Add color coding for quantities (green for positive, red for negative)
  - Ensure consistent badge colors for movement types
  - Display both product name and SKU
  - _Requirements: 5.1, 5.2, 5.4_

- [x] 8.1 Write property test for quantity color coding


  - **Property 8: Quantity color coding**
  - **Validates: Requirements 5.1**


- [x] 8.2 Write property test for consistent type badge colors


  - **Property 9: Consistent type badge colors**
  - **Validates: Requirements 5.2**

- [x] 9. Add CSS improvements for scannability





  - Add sticky table header for long lists
  - Improve row hover states
  - Add subtle borders between grouped movements
  - Ensure responsive design for mobile devices
  - _Requirements: 5.5_

- [x] 10. Checkpoint - Ensure all tests pass





  - Ensure all tests pass, ask the user if questions arise.
