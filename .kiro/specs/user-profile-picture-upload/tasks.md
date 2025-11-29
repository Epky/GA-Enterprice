# Implementation Plan

- [x] 1. Create AvatarUploadService





  - Create new service class at `app/Services/AvatarUploadService.php`
  - Implement file validation methods (type, size, dimensions)
  - Implement upload method that stores file and returns path
  - Implement delete method that removes file from storage
  - Implement helper methods for filename generation and path management
  - Use existing ImageUploadService as reference for patterns
  - _Requirements: 1.2, 1.3, 1.4, 1.5, 3.2, 4.2, 6.2, 6.3_

- [x] 1.1 Write property test for valid image upload


  - **Property 1: Valid image upload stores file and updates database**
  - **Validates: Requirements 1.4**

- [x] 1.2 Write property test for invalid file type rejection


  - **Property 2: Invalid file type rejection**
  - **Validates: Requirements 1.5**

- [x] 1.3 Write property test for file size validation


  - **Property 3: File size validation**
  - **Validates: Requirements 1.3**

- [x] 2. Add User model helper methods




  - Add `getAvatarUrlAttribute()` accessor to User model
  - Add `getAvatarOrDefaultAttribute()` accessor for fallback handling
  - Add `getDefaultAvatarUrl()` method for placeholder generation
  - Add `getInitials()` method for initials-based placeholders
  - _Requirements: 5.4_

- [x] 3. Extend ProfileController with avatar operations





  - Inject AvatarUploadService into ProfileController constructor
  - Create `uploadAvatar()` method to handle avatar uploads
  - Create `deleteAvatar()` method to handle avatar removal
  - Update existing `update()` method to handle avatar if present in request
  - Add proper authorization checks (user can only modify own avatar)
  - Add validation rules for avatar uploads
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 3.1, 3.4, 4.1, 4.2, 4.3, 4.4_

- [x] 3.1 Write property test for old avatar deletion on update


  - **Property 4: Old avatar deletion on update**
  - **Validates: Requirements 3.2**

- [x] 3.2 Write property test for avatar removal


  - **Property 5: Avatar removal clears database and deletes file**
  - **Validates: Requirements 4.2, 4.3**

- [x] 4. Add routes for avatar operations





  - Add POST route for avatar upload: `profile.avatar.upload`
  - Add DELETE route for avatar removal: `profile.avatar.delete`
  - Ensure routes are protected by auth middleware
  - _Requirements: 3.1, 4.1_

- [x] 5. Create profile picture upload form partial





  - Create `resources/views/profile/partials/update-profile-picture-form.blade.php`
  - Display current avatar or placeholder with proper styling
  - Add file input with accept attribute for images only
  - Add preview container for selected image
  - Add upload button (shown when file selected)
  - Add remove button (shown when avatar exists)
  - Display validation errors
  - Include CSRF token and proper form attributes
  - _Requirements: 1.1, 2.1, 2.2, 2.3, 2.4, 4.1, 4.5_

- [x] 6. Create reusable avatar display component





  - Create `resources/views/components/user-avatar.blade.php`
  - Accept user object and size parameter (sm, md, lg)
  - Display avatar image if exists, otherwise show placeholder
  - Use consistent rounded styling
  - Add proper alt text for accessibility
  - Support different sizes with Tailwind classes
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 6.1 Write property test for avatar display consistency


  - **Property 6: Avatar display consistency**
  - **Validates: Requirements 5.1, 5.2, 5.3**

- [x] 6.2 Write property test for placeholder display


  - **Property 7: Placeholder display for missing avatars**
  - **Validates: Requirements 5.4**

- [x] 7. Implement JavaScript avatar preview functionality





  - Create `resources/js/avatar-preview.js` module
  - Add event listener for file input change
  - Implement client-side file validation (type and size)
  - Display instant preview of selected image
  - Show/hide upload and cancel buttons based on state
  - Handle cancel action to reset preview
  - Import and initialize in main app.js
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [x] 8. Integrate avatar upload form into profile edit page




  - Add profile picture section to `resources/views/profile/edit.blade.php`
  - Include the update-profile-picture-form partial
  - Position it prominently at the top of the profile page
  - Ensure consistent styling with existing sections
  - _Requirements: 1.1_

- [x] 9. Update navigation to display user avatars




  - Update admin layout navigation to show avatar
  - Update customer layout navigation to show avatar
  - Update staff layout navigation to show avatar
  - Use the reusable user-avatar component
  - Ensure avatar displays in dropdown menus
  - _Requirements: 5.1, 5.3_

- [x] 9.1 Write property test for unique filename generation


  - **Property 8: Unique filename generation**
  - **Validates: Requirements 6.3**

- [x] 10. Add avatar cleanup on user deletion







  - Update User model's delete event or ProfileController destroy method
  - Call AvatarUploadService to delete avatar file before user deletion
  - Ensure cleanup happens in transaction with user deletion
  - _Requirements: 6.5_

- [x] 10.1 Write property test for user deletion cleanup




  - **Property 9: User deletion cleanup**
  - **Validates: Requirements 6.5**

- [x] 11. Write unit tests for AvatarUploadService





  - Test validateAvatarFile with various file types
  - Test validateAvatarFile with various file sizes
  - Test generateAvatarFilename returns unique names
  - Test storeAvatar successfully saves files
  - Test deleteAvatarFile removes files from storage

- [x] 12. Write unit tests for ProfileController avatar methods








  - Test uploadAvatar with valid file
  - Test uploadAvatar with invalid file type
  - Test uploadAvatar with oversized file
  - Test deleteAvatar removes file and clears database
  - Test authorization (user can only modify own avatar)

- [x] 13. Write unit tests for User model avatar methods




  - Test getAvatarUrlAttribute returns correct URL
  - Test getAvatarOrDefaultAttribute with avatar present
  - Test getAvatarOrDefaultAttribute without avatar
  - Test getInitials with profile data
  - Test getInitials without profile data



- [x] 14. Write integration tests for complete avatar workflows



  - Test full upload flow from form submission to display
  - Test update flow (upload, then upload new avatar)
  - Test delete flow (upload, then delete avatar)
  - Test avatar display across multiple pages

- [x] 15. Checkpoint - Ensure all tests pass



  - Ensure all tests pass, ask the user if questions arise.
