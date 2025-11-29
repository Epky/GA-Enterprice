# Design Document

## Overview

This feature adds profile picture upload functionality to the user profile management system. Users can upload, preview, update, and remove profile pictures that will be displayed throughout the application. The implementation leverages the existing ImageUploadService for consistent file handling and extends the ProfileController to manage avatar operations.

## Architecture

### High-Level Architecture

```
User Interface (Blade Views)
    ↓
ProfileController (HTTP Layer)
    ↓
AvatarUploadService (Business Logic)
    ↓
Storage System (File Management)
    ↓
UserProfile Model (Data Layer)
```

### Component Interaction Flow

1. **Upload Flow**: User selects image → JavaScript preview → Form submission → Controller validation → Service processes upload → Storage saves file → Database updated → UI refreshed
2. **Display Flow**: Page loads → Controller fetches user → View checks avatar_url → Displays image or placeholder
3. **Update Flow**: User uploads new image → Service deletes old file → New file stored → Database updated
4. **Delete Flow**: User clicks remove → Controller validates → Service deletes file → Database cleared → Placeholder shown

## Components and Interfaces

### 1. AvatarUploadService

A new service class dedicated to handling user avatar operations, following the pattern established by ImageUploadService.

```php
class AvatarUploadService
{
    // Constants
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB
    private const MIN_DIMENSIONS = 100; // 100x100 pixels minimum
    private const AVATAR_DIRECTORY = 'avatars';
    
    // Public Methods
    public function uploadAvatar(User $user, UploadedFile $file): string
    public function deleteAvatar(User $user): bool
    public function getAvatarUrl(User $user): ?string
    public function getAvatarOrDefault(User $user): string
    
    // Private Methods
    private function validateAvatarFile(UploadedFile $file): void
    private function generateAvatarFilename(User $user, UploadedFile $file): string
    private function storeAvatar(UploadedFile $file, string $filename): string
    private function deleteAvatarFile(string $path): void
}
```

### 2. ProfileController Extensions

Add new methods to handle avatar-specific operations:

```php
class ProfileController extends Controller
{
    public function __construct(
        private AvatarUploadService $avatarService
    ) {}
    
    // New Methods
    public function uploadAvatar(Request $request): RedirectResponse
    public function deleteAvatar(Request $request): RedirectResponse
    
    // Modified Methods
    public function update(ProfileUpdateRequest $request): RedirectResponse
    // Will now handle avatar upload if present in request
}
```

### 3. View Components

**Profile Picture Upload Component** (`resources/views/profile/partials/update-profile-picture-form.blade.php`)
- Displays current avatar or placeholder
- File input for selecting new image
- JavaScript-powered preview
- Upload and remove buttons
- Validation error display

**Avatar Display Component** (`resources/views/components/user-avatar.blade.php`)
- Reusable component for displaying user avatars
- Accepts size parameter (sm, md, lg)
- Shows image or initials-based placeholder
- Consistent styling across application

### 4. JavaScript Module

**Avatar Preview Handler** (`resources/js/avatar-preview.js`)
- Handles file selection events
- Displays instant preview
- Validates file size and type client-side
- Manages UI state (preview/upload/cancel buttons)

## Data Models

### UserProfile Model Updates

The `user_profiles` table already has the `avatar_url` column. No migration needed.

```php
// Existing field in user_profiles table
'avatar_url' => 'string|nullable'
```

### Model Methods

Add helper methods to User model:

```php
class User extends Authenticatable
{
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->profile?->avatar_url;
    }
    
    public function getAvatarOrDefaultAttribute(): string
    {
        if ($this->avatar_url) {
            return Storage::url($this->avatar_url);
        }
        return $this->getDefaultAvatarUrl();
    }
    
    public function getDefaultAvatarUrl(): string
    {
        // Generate URL for default avatar with initials
        $initials = $this->getInitials();
        return "https://ui-avatars.com/api/?name={$initials}&size=200&background=random";
    }
    
    public function getInitials(): string
    {
        if ($this->profile) {
            $first = substr($this->profile->first_name, 0, 1);
            $last = substr($this->profile->last_name, 0, 1);
            return strtoupper($first . $last);
        }
        return strtoupper(substr($this->email, 0, 2));
    }
}
```



## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Valid image upload stores file and updates database

*For any* valid image file (correct type, size, dimensions) uploaded by any user, the system should successfully store the file in the avatars directory and update the user's avatar_url in the database.

**Validates: Requirements 1.4**

### Property 2: Invalid file type rejection

*For any* file that is not an allowed image type (jpg, jpeg, png, gif, webp), the upload attempt should be rejected with an appropriate error message, and no changes should be made to storage or database.

**Validates: Requirements 1.5**

### Property 3: File size validation

*For any* file exceeding 2MB in size, the upload attempt should be rejected with an error message, and no changes should be made to storage or database.

**Validates: Requirements 1.3**

### Property 4: Old avatar deletion on update

*For any* user with an existing avatar who uploads a new avatar, the old avatar file should be deleted from storage, and only the new avatar should remain.

**Validates: Requirements 3.2**

### Property 5: Avatar removal clears database and deletes file

*For any* user with an existing avatar who removes their avatar, the avatar file should be deleted from storage and the avatar_url should be set to null in the database.

**Validates: Requirements 4.2, 4.3**

### Property 6: Avatar display consistency

*For any* user with an avatar_url in the database, the avatar should be displayed in all locations where user identity is shown (navigation, profile page, dropdowns).

**Validates: Requirements 5.1, 5.2, 5.3**

### Property 7: Placeholder display for missing avatars

*For any* user without an avatar_url, a default placeholder (with initials or icon) should be displayed in all locations where user identity is shown.

**Validates: Requirements 5.4**

### Property 8: Unique filename generation

*For any* avatar upload, the generated filename should be unique to prevent naming conflicts, even if multiple users upload files with the same original name.

**Validates: Requirements 6.3**

### Property 9: User deletion cleanup

*For any* user account that is deleted, if the user had an avatar, the avatar file should be removed from storage.

**Validates: Requirements 6.5**

## Error Handling

### Validation Errors

1. **Invalid File Type**
   - Error Message: "The profile picture must be a file of type: jpg, jpeg, png, gif, webp."
   - HTTP Status: 422 (Unprocessable Entity)
   - Action: Display error, maintain current state

2. **File Too Large**
   - Error Message: "The profile picture must not exceed 2MB."
   - HTTP Status: 422
   - Action: Display error, maintain current state

3. **Invalid Image Dimensions**
   - Error Message: "The profile picture must be at least 100x100 pixels."
   - HTTP Status: 422
   - Action: Display error, maintain current state

4. **Corrupted File**
   - Error Message: "The uploaded file is not a valid image."
   - HTTP Status: 422
   - Action: Display error, maintain current state

### Storage Errors

1. **Storage Write Failure**
   - Error Message: "Failed to save the profile picture. Please try again."
   - HTTP Status: 500
   - Action: Log error, rollback transaction, display user-friendly message

2. **Storage Delete Failure**
   - Error Message: "Failed to remove the profile picture. Please try again."
   - HTTP Status: 500
   - Action: Log error, display user-friendly message
   - Note: Database should not be updated if file deletion fails

### Permission Errors

1. **Unauthorized Access**
   - Error Message: "You are not authorized to modify this profile."
   - HTTP Status: 403
   - Action: Redirect to appropriate page

### Recovery Strategies

- **Transaction Rollback**: All database operations wrapped in transactions
- **Orphaned File Cleanup**: Scheduled job to remove avatar files not referenced in database
- **Graceful Degradation**: If avatar cannot be loaded, show placeholder instead of broken image
- **Retry Logic**: For transient storage errors, implement automatic retry with exponential backoff

## Testing Strategy

### Unit Tests

Unit tests will verify specific functionality and edge cases:

1. **AvatarUploadService Tests**
   - Test file validation with various file types
   - Test file size validation
   - Test filename generation uniqueness
   - Test storage path construction
   - Test file deletion

2. **ProfileController Tests**
   - Test avatar upload endpoint with valid file
   - Test avatar upload endpoint with invalid file
   - Test avatar deletion endpoint
   - Test authorization checks

3. **User Model Tests**
   - Test getAvatarUrlAttribute method
   - Test getAvatarOrDefaultAttribute method
   - Test getInitials method
   - Test getDefaultAvatarUrl method

### Property-Based Tests

Property-based tests will verify universal properties across many random inputs using a PHP property testing library (e.g., Eris or php-quickcheck):

1. **Property Test: Valid Upload Success**
   - Generate random valid image files
   - Verify all uploads succeed and update database
   - Verify files exist in storage

2. **Property Test: Invalid Type Rejection**
   - Generate random non-image files
   - Verify all uploads are rejected
   - Verify no database or storage changes

3. **Property Test: Size Limit Enforcement**
   - Generate random files over 2MB
   - Verify all uploads are rejected
   - Verify no database or storage changes

4. **Property Test: Update Cleanup**
   - Generate random sequences of avatar uploads
   - Verify only the latest avatar exists in storage
   - Verify old avatars are deleted

5. **Property Test: Display Consistency**
   - Generate random user states (with/without avatars)
   - Verify avatar or placeholder is always displayed
   - Verify display is consistent across all UI locations

### Integration Tests

1. **Full Upload Flow Test**
   - Simulate complete user journey from file selection to display
   - Verify all components work together correctly

2. **Update Flow Test**
   - Upload avatar, then upload new avatar
   - Verify old file is deleted and new file is displayed

3. **Delete Flow Test**
   - Upload avatar, then delete it
   - Verify file is removed and placeholder is shown

4. **Multi-User Test**
   - Multiple users uploading avatars simultaneously
   - Verify no conflicts or data corruption

### Browser Tests

1. **JavaScript Preview Test**
   - Test file selection triggers preview
   - Test preview displays correctly
   - Test cancel removes preview

2. **Form Submission Test**
   - Test form submits with avatar file
   - Test validation errors display correctly
   - Test success message appears

3. **Responsive Design Test**
   - Test avatar display on mobile devices
   - Test upload interface on various screen sizes

### Test Configuration

- **Property Test Iterations**: Minimum 100 iterations per property test
- **Test Data**: Use factories to generate test users and files
- **Cleanup**: Ensure test files are removed after each test
- **Isolation**: Each test should be independent and not affect others

## Implementation Notes

### Security Considerations

1. **File Validation**: Always validate on server-side, never trust client-side validation alone
2. **File Type Verification**: Use getimagesize() to verify actual image content, not just extension
3. **Storage Permissions**: Ensure avatar directory has appropriate permissions
4. **Path Traversal Prevention**: Sanitize all file paths to prevent directory traversal attacks
5. **User Authorization**: Verify user can only modify their own avatar

### Performance Considerations

1. **Image Optimization**: Consider adding image compression/resizing in future enhancement
2. **CDN Integration**: For production, consider serving avatars through CDN
3. **Lazy Loading**: Implement lazy loading for avatar images in lists
4. **Caching**: Add appropriate cache headers for avatar images

### Accessibility Considerations

1. **Alt Text**: Always include descriptive alt text for avatar images
2. **Keyboard Navigation**: Ensure file input is keyboard accessible
3. **Screen Reader Support**: Add ARIA labels for avatar upload controls
4. **Focus Management**: Maintain proper focus flow during upload process

### Future Enhancements

1. **Image Cropping**: Allow users to crop/adjust uploaded images
2. **Multiple Sizes**: Generate thumbnail and full-size versions
3. **Avatar Library**: Provide default avatar options to choose from
4. **Social Media Import**: Allow importing profile pictures from social media
5. **Gravatar Integration**: Fall back to Gravatar if available
