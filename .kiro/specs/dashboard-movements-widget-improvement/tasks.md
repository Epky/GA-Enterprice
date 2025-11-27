# Implementation Plan

- [x] 1. Update DashboardController to use InventoryService





  - Inject InventoryService into DashboardController constructor
  - Replace raw database query for recent movements with InventoryService call
  - Apply business-only filter (include_system_movements = false)
  - Set date range to last 7 days
  - Limit results to 10 movements
  - Disable grouping for dashboard display
  - _Requirements: 1.1, 1.2, 1.3, 1.5_


- [x] 1.1 Write property test for business movements only



  - **Property 1: Business movements only**
  - **Validates: Requirements 1.1**

- [x] 1.2 Write property test for recent movements limit



  - **Property 2: Recent movements limit**
  - **Validates: Requirements 1.3**

- [x] 1.3 Write property test for descending date order



  - **Property 3: Descending date order**
  - **Validates: Requirements 1.5**

- [x] 2. Update dashboard view to use InventoryMovement models





  - Replace raw database result access with InventoryMovement model methods
  - Use model's getTypeBadgeColor() method for consistent badge colors
  - Use model's getQuantityColorClass() method for quantity color coding
  - Access product and variant through model relationships
  - Use model's created_at for date formatting
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 2.1 Write property test for quantity color coding



  - **Property 8: Quantity color coding**
  - **Validates: Requirements 3.1**

- [x] 2.2 Write property test for consistent badge colors



  - **Property 9: Consistent badge colors**
  - **Validates: Requirements 3.2**

- [x] 2.3 Write property test for date and time display



  - **Property 10: Date and time display**
  - **Validates: Requirements 3.3**

- [x] 2.4 Write property test for product information completeness



  - **Property 11: Product information completeness**
  - **Validates: Requirements 3.4**

- [x] 3. Replace inline note rendering with movement-notes component





  - Remove Str::limit() truncation from notes display
  - Replace inline notes rendering with <x-movement-notes :movement="$movement" /> component
  - Ensure component receives full InventoryMovement model
  - Remove manual note truncation logic
  - _Requirements: 2.1, 2.2, 2.3, 2.5_

- [x] 3.1 Write property test for transaction reference linking



  - **Property 4: Transaction reference linking**
  - **Validates: Requirements 2.1**

- [x] 3.2 Write property test for reason badge display



  - **Property 5: Reason badge display**
  - **Validates: Requirements 2.2**

- [x] 3.3 Write property test for structured data separation


  - **Property 6: Structured data separation**
  - **Validates: Requirements 2.3**

- [x] 3.4 Write property test for no note truncation


  - **Property 7: No note truncation**
  - **Validates: Requirements 2.5**

- [x] 3.5 Write property test for transaction link navigation


  - **Property 12: Transaction link navigation**
  - **Validates: Requirements 4.2**

- [x] 4. Improve table layout and spacing





  - Increase padding in table cells (from px-4 py-3 to px-6 py-4)
  - Add word-wrap and break-word classes to notes column
  - Ensure adequate column widths for readability
  - Add hover effects for better row scanning
  - Maintain responsive design for mobile devices
  - _Requirements: 3.5_

- [x] 5. Add widget visibility logic




  - Wrap widget in conditional check for movement count
  - Hide entire widget section when no movements exist
  - Ensure "View All" link is always visible when widget is shown
  - Add time range indicator in widget header
  - _Requirements: 1.4, 4.1, 4.3_

- [x] 5.1 Write unit test for widget visibility



  - Test widget is hidden when movements collection is empty
  - Test widget is shown when movements exist
  - Test "View All" link is present when widget is shown
  - _Requirements: 1.4, 4.1_

- [x] 5.2 Write property test for movement count accuracy




  - **Property 13: Movement count accuracy**
  - **Validates: Requirements 4.4**

- [x] 6. Checkpoint - Ensure all tests pass





  - Ensure all tests pass, ask the user if questions arise.
