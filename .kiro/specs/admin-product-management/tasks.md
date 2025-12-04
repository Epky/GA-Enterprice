# Implementation Plan

- [x] 1. Create AdminProductController with core CRUD operations





  - Create `app/Http/Controllers/Admin/AdminProductController.php`
  - Implement index, create, store, show, edit, update, destroy methods
  - Inject ProductService dependency
  - Reuse existing validation requests (ProductStoreRequest, ProductUpdateRequest)
  - _Requirements: 1.1, 1.2, 2.1, 2.2, 2.3, 3.1, 3.2, 3.3, 4.1, 4.3_

- [x] 2. Add admin product routes




  - Update `routes/admin.php` with product resource routes
  - Add image management routes (upload, delete, set-primary)
  - Add quick action routes (toggle-featured)
  - Use admin middleware and naming convention
  - _Requirements: 1.1, 2.1, 3.1, 4.1, 5.2_

- [x] 3. Create admin product list view





  - Create `resources/views/admin/products/index.blade.php`
  - Extend admin layout
  - Display product table with name, category, brand, price, stock, images
  - Include search and filter forms
  - Add pagination
  - Include "Create Product" button
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 4. Create admin product creation view





  - Create `resources/views/admin/products/create.blade.php`
  - Extend admin layout
  - Include product form with all required fields
  - Integrate searchable-select component for category/brand
  - Integrate image-manager component
  - Integrate inline-add-modal for quick category/brand creation
  - Display validation errors
  - _Requirements: 2.1, 2.2, 2.4, 2.5, 2.6_

- [x] 5. Create admin product edit view





  - Create `resources/views/admin/products/edit.blade.php`
  - Extend admin layout
  - Pre-fill form with existing product data
  - Integrate searchable-select component for category/brand
  - Integrate image-manager component with existing images
  - Integrate inline-add-modal for quick category/brand creation
  - Display validation errors
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_

- [x] 6. Create admin product detail view



  - Create `resources/views/admin/products/show.blade.php`
  - Extend admin layout
  - Display all product information
  - Show product images
  - Display stock information
  - Include edit and delete buttons
  - _Requirements: 1.5, 3.1, 4.1_

- [x] 7. Add products menu item to admin navigation





  - Update `resources/views/layouts/admin.blade.php`
  - Add products navigation link
  - Implement active state highlighting
  - Position appropriately in navigation menu
  - _Requirements: 5.1, 5.2, 5.3_

- [x] 8. Implement image management methods in AdminProductController





  - Add uploadImages method
  - Add deleteImage method
  - Add setPrimaryImage method
  - Delegate to ImageUploadService
  - Return JSON responses for AJAX calls
  - _Requirements: 2.4, 3.4_

- [x] 9. Implement product deletion with stock warning





  - Add destroy method logic
  - Calculate total stock across all locations
  - Integrate delete-confirmation-modal component
  - Display stock warning when quantity > 0
  - Perform soft delete on confirmation
  - _Requirements: 4.1, 4.2, 4.3, 4.4_
-

- [x] 10. Add quick action methods



  - Implement toggleFeatured method
  - Return appropriate redirect with success message
  - Handle errors gracefully
  - _Requirements: 3.3_

- [x] 11. Checkpoint - Ensure all tests pass





  - Ensure all tests pass, ask the user if questions arise.
