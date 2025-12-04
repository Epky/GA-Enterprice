# Implementation Plan

- [ ] 1. Update CategoryRequest validation for file uploads
  - Add validation rule for 'image' field (nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048)
  - Keep 'image_url' field for backward compatibility with URL inputs
  - Add custom error messages for image validation
  - _Requirements: 1.4, 1.5, 4.5_

- [ ] 1.1 Write property test for image file validation
  - **Property 1: Image file validation**
  - **Validates: Requirements 1.4, 1.5, 4.5**

- [ ] 2. Update StaffCategoryController store method for image uploads
  - Add image file upload handling in store() method
  - Store uploaded file to 'categories' directory in public disk
  - Save file path to image_url column
  - Handle case when no image is uploaded
  - _Requirements: 1.3_

- [ ] 2.1 Write property test for image storage path consistency
  - **Property 2: Image storage path consistency**
  - **Validates: Requirements 1.3, 2.3**

- [ ] 3. Update StaffCategoryController update method for image replacement
  - Add image file upload handling in update() method
  - Delete old image file from storage if new image is uploaded
  - Store new image file to 'categories' directory
  - Preserve existing image_url if no new image is uploaded
  - _Requirements: 2.3, 2.4, 2.5_

- [ ] 3.1 Write property test for old image deletion on update
  - **Property 3: Old image deletion on update**
  - **Validates: Requirements 2.4**

- [ ] 3.2 Write property test for image preservation without upload
  - **Property 4: Image preservation without upload**
  - **Validates: Requirements 2.5**

- [ ] 4. Update category create form view
  - Replace URL text input with file input field (type="file" accept="image/*")
  - Add image preview functionality using JavaScript
  - Update form to properly handle file uploads
  - Add file size and type hints for users
  - _Requirements: 1.1, 1.2_

- [ ] 5. Update category edit form view
  - Display current category image if exists
  - Add file input field for new image upload
  - Add image preview functionality for new uploads
  - Show option to keep existing image
  - _Requirements: 2.1, 2.2_

- [ ] 6. Update category index view for image display
  - Update image display to use asset('storage/' . $category->image_url)
  - Ensure placeholder displays when no image exists
  - Verify thumbnail sizing and styling
  - _Requirements: 3.1, 3.2, 3.3_

- [ ] 6.1 Write property test for image display URL generation
  - **Property 5: Image display URL generation**
  - **Validates: Requirements 3.1, 3.2, 3.4, 3.5**

- [ ] 7. Update category show view for image display
  - Update image display to use asset('storage/' . $category->image_url)
  - Display full-size image with proper styling
  - Handle case when no image exists
  - _Requirements: 3.4_

- [ ] 8. Update inline category creator for image uploads
  - Add file input field to inline category modal
  - Handle file upload in AJAX submission using FormData
  - Update storeInline() method to process image uploads
  - Return image path in JSON response
  - Update JavaScript to handle image data in response
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [ ] 8.1 Write property test for inline creator image handling
  - **Property 6: Inline creator image handling**
  - **Validates: Requirements 4.2, 4.3**

- [ ] 9. Write unit tests for CategoryRequest validation
  - Test valid image uploads pass validation
  - Test invalid file types are rejected
  - Test oversized files are rejected
  - Test nullable image field allows submission without image
  - _Requirements: 1.4, 1.5_

- [ ] 10. Write unit tests for controller image processing
  - Test store() method handles image upload correctly
  - Test update() method replaces old image
  - Test update() method preserves existing image when no new upload
  - Test storeInline() method handles image upload
  - _Requirements: 1.3, 2.3, 2.4, 2.5, 4.2_

- [ ] 11. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 12. Create image preview JavaScript functionality
  - Create reusable JavaScript function for image preview
  - Show preview when file is selected
  - Clear preview when file is removed
  - Handle multiple file input fields on same page
  - _Requirements: 1.2, 2.2_

- [ ] 13. Add error handling and user feedback
  - Display validation errors clearly on forms
  - Show success messages after image upload
  - Handle storage errors gracefully
  - Log errors for debugging
  - _Requirements: All_

- [ ] 14. Final Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
