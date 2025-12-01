# Implementation Plan

- [-] 1. Create backend API endpoints for inline deletion



- [x] 1.1 Add deleteInline method to StaffCategoryController


  - Implement deletion logic with product count validation
  - Return appropriate JSON responses for success/error cases
  - Include CSRF protection and authorization checks
  - _Requirements: 1.2, 1.3, 1.4, 1.5_


- [x] 1.2 Add deleteInline method to StaffBrandController

  - Implement deletion logic with product count validation
  - Return appropriate JSON responses for success/error cases
  - Include CSRF protection and authorization checks
  - _Requirements: 2.2, 2.3, 2.4, 2.5_

- [x] 1.3 Add delete routes to staff.php







  - Add DELETE route for categories/inline/{category}
  - Add DELETE route for brands/inline/{brand}
  - Apply staff middleware and CSRF protection
  - _Requirements: 1.1, 2.1_

- [x] 2. Create searchable select Blade component




- [x] 2.1 Create component file structure

  - Create resources/views/components/searchable-select.blade.php
  - Define component props (name, label, items, selected, deleteRoute, refreshRoute)
  - Create basic HTML structure with hidden input, trigger button, and dropdown panel
  - _Requirements: 1.1, 2.1, 5.1, 5.2_


- [x] 2.2 Implement dropdown item rendering
  - Loop through items and render list items
  - Add item name display
  - Add delete button with trash icon for each item
  - Include data attributes for JavaScript interaction
  - _Requirements: 1.1, 2.1_


- [x] 2.3 Add search input to dropdown
  - Create search input field at top of dropdown
  - Add search icon and placeholder text
  - Style search box appropriately
  - _Requirements: 5.1, 5.2_


- [x] 2.4 Style the component with Tailwind CSS

  - Style trigger button to match existing form inputs
  - Style dropdown panel with proper positioning and shadows
  - Style search box and items list
  - Add hover states for items and delete buttons
  - Ensure mobile responsiveness
  - _Requirements: 4.1_

- [x] 3. Implement JavaScript functionality





- [x] 3.1 Create SearchableSelect class


  - Create resources/js/searchable-select.js
  - Define class constructor with element and options parameters
  - Initialize component state (isOpen, selectedId, items)
  - Set up event listeners for trigger button
  - _Requirements: 1.1, 2.1, 5.1, 5.2_

- [x] 3.2 Implement dropdown open/close functionality

  - Add open() method to show dropdown
  - Add close() method to hide dropdown
  - Implement click-outside-to-close behavior
  - Add escape key handler to close dropdown
  - Manage focus states
  - _Requirements: 1.1, 2.1, 5.1, 5.2_

- [x] 3.3 Implement search filtering

  - Add search input event listener with debouncing (300ms)
  - Implement search() method to filter items by query
  - Show/hide items based on search match (case-insensitive)
  - Display "No results found" message when no matches
  - Clear search when dropdown closes
  - _Requirements: 5.3, 5.4, 5.5_

- [x] 3.4 Implement item selection

  - Add click handlers for list items
  - Implement selectItem(id, name) method
  - Update hidden input value
  - Update trigger button text
  - Close dropdown after selection
  - _Requirements: 1.1, 2.1_

- [x] 3.5 Implement delete functionality

  - Add click handlers for delete buttons
  - Implement deleteItem(id) method with confirmation dialog
  - Show confirmation with item name
  - Make AJAX DELETE request to server
  - Handle loading state during deletion
  - _Requirements: 1.2, 1.3, 1.4, 2.2, 2.3, 2.4, 4.2, 4.4_


- [x] 3.6 Implement dropdown refresh after deletion

  - Implement refreshItems() method
  - Make AJAX GET request to refresh route
  - Update dropdown items list
  - Preserve form state during refresh
  - Handle errors gracefully
  - _Requirements: 3.1, 3.2_

- [x] 3.7 Implement visual feedback

  - Add showLoading() and hideLoading() methods
  - Add showMessage(type, text) method for success/error messages
  - Implement hover effects for interactive elements
  - Add loading spinner during AJAX operations
  - Display toast notifications for success/error
  - _Requirements: 4.1, 4.2, 4.3_

- [x] 3.8 Add error handling

  - Handle network errors
  - Handle server errors (500)
  - Handle validation errors (422)
  - Handle deletion prevention (items with products)
  - Display appropriate error messages
  - Log errors to console for debugging
  - _Requirements: 1.5, 2.5, 4.3_

- [x] 4. Update product forms to use new component





- [x] 4.1 Update create.blade.php


  - Replace category select with searchable-select component
  - Replace brand select with searchable-select component
  - Pass correct props (items, deleteRoute, refreshRoute)
  - Ensure form validation still works
  - _Requirements: 1.1, 2.1, 5.1, 5.2_

- [x] 4.2 Update edit.blade.php


  - Replace category select with searchable-select component
  - Replace brand select with searchable-select component
  - Pass selected values correctly
  - Ensure form validation still works
  - _Requirements: 1.1, 2.1, 5.1, 5.2_

- [x] 4.3 Initialize JavaScript components


  - Add script to initialize SearchableSelect instances
  - Pass configuration options (routes, CSRF token)
  - Ensure components work on both create and edit pages
  - _Requirements: 1.1, 2.1, 5.1, 5.2_

- [x] 5. Add CSS styles




- [x] 5.1 Add component styles to app.css


  - Style dropdown positioning and z-index
  - Add transition animations for dropdown
  - Style loading states
  - Style toast notifications
  - Ensure consistent styling with existing UI
  - _Requirements: 4.1_

- [x] 6. Checkpoint - Ensure all tests pass





  - Ensure all tests pass, ask the user if questions arise.
