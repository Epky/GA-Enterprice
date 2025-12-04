# Design Document

## Overview

This feature adds proper file upload functionality for category images in the staff category management system. Currently, the system only accepts image URLs via text input, which creates friction for staff members who want to upload images directly from their computers. This design implements file upload inputs, image preview, validation, storage management, and proper display across all category views.

The solution follows the existing pattern used for product images and brand images (after the brand-image-display-fix), ensuring consistency across the application.

## Architecture

### Components Involved

1. **StaffCategoryController** - Handles category CRUD operations and image processing
2. **Category Model** - Represents category data including image path
3. **CategoryRequest** - Validates category form data including image uploads
4. **View Templates** - Forms and display pages for categories
5. **Storage System** - Laravel's file storage for managing uploaded images

### Data Flow

```
User selects image file
    ↓
Form submission with multipart/form-data
    ↓
CategoryRequest validates file (type, size)
    ↓
Controller processes upload
    ↓
File stored in storage/app/public/categories
    ↓
Path saved to database (categories.image_url)
    ↓
Views display image using asset() helper
```

## Components and Interfaces

### 1. Form Views

**Category Create Form** (`resources/views/staff/categories/create.blade.php`)
- Replace URL text input with file input
- Add image preview functionality
- Add file validation messages

**Category Edit Form** (`resources/views/staff/categories/edit.blade.php`)
- Display current image if exists
- Add file input for new image upload
- Add image preview functionality
- Show option to keep existing image

**Inline Category Modal** (`resources/views/components/inline-add-modal.blade.php`)
- Add file input field for categories
- Handle file upload in AJAX submission
- Return image path in success response

### 2. Controller Methods

**StaffCategoryController::store()**
```php
public function store(CategoryRequest $request)
{
    // Handle image upload if present
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('categories', 'public');
        $validated['image_url'] = $imagePath;
    }
    
    // Create category with image path
    $category = Category::create($validated);
}
```

**StaffCategoryController::update()**
```php
public function update(CategoryRequest $request, Category $category)
{
    // Handle new image upload
    if ($request->hasFile('image')) {
        // Delete old image if exists
        if ($category->image_url && Storage::disk('public')->exists($category->image_url)) {
            Storage::disk('public')->delete($category->image_url);
        }
        
        // Store new image
        $imagePath = $request->file('image')->store('categories', 'public');
        $validated['image_url'] = $imagePath;
    }
    
    // Update category
    $category->update($validated);
}
```

**StaffCategoryController::storeInline()**
```php
public function storeInline(CategoryRequest $request)
{
    // Handle image upload for inline creation
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('categories', 'public');
        $validated['image_url'] = $imagePath;
    }
    
    $category = Category::create($validated);
    
    return response()->json([
        'success' => true,
        'data' => [
            'id' => $category->id,
            'name' => $category->name,
            'image_url' => $category->image_url ? asset('storage/' . $category->image_url) : null
        ]
    ]);
}
```

### 3. Request Validation

**CategoryRequest**
```php
public function rules()
{
    return [
        'name' => 'required|string|max:255|unique:categories,name,' . $this->category?->id,
        'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048', // 2MB max
        'image_url' => 'nullable|url', // Keep for backward compatibility
        // ... other fields
    ];
}
```

### 4. View Display

**Index View** - Display thumbnails
```blade
@if($category->image_url)
    <img src="{{ asset('storage/' . $category->image_url) }}" 
         alt="{{ $category->name }}" 
         class="h-10 w-10 rounded object-cover">
@else
    <div class="h-10 w-10 rounded bg-gray-200 flex items-center justify-center">
        <i class="fas fa-folder text-gray-400"></i>
    </div>
@endif
```

**Show View** - Display full image
```blade
@if($category->image_url)
    <img src="{{ asset('storage/' . $category->image_url) }}" 
         alt="{{ $category->name }}" 
         class="w-full max-w-md rounded-lg shadow-md">
@endif
```

## Data Models

### Category Model

The `categories` table already has an `image_url` column that stores the image path. No database changes are needed.

**Existing Schema:**
```php
Schema::table('categories', function (Blueprint $table) {
    $table->string('image_url')->nullable(); // Stores: 'categories/filename.jpg'
});
```

**Model Accessor (Optional Enhancement):**
```php
public function getImageUrlAttribute($value)
{
    if ($value && !str_starts_with($value, 'http')) {
        return asset('storage/' . $value);
    }
    return $value;
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Image file validation
*For any* file upload attempt, if the file is not an image type (jpeg, jpg, png, gif, webp) or exceeds 2MB, the system should reject the upload and return a validation error
**Validates: Requirements 1.4, 1.5, 4.5**

### Property 2: Image storage path consistency
*For any* successfully uploaded category image, the stored path should follow the pattern 'categories/{filename}' and the file should exist in storage/app/public/categories
**Validates: Requirements 1.3, 2.3**

### Property 3: Old image deletion on update
*For any* category update with a new image upload, if an old image exists in storage, the system should delete the old image file before storing the new one
**Validates: Requirements 2.4**

### Property 4: Image preservation without upload
*For any* category update without a new image file, the existing image_url value should remain unchanged in the database
**Validates: Requirements 2.5**

### Property 5: Image display URL generation
*For any* category with an image_url value, the displayed image URL should use asset('storage/' . image_url) to generate the correct public URL
**Validates: Requirements 3.1, 3.2, 3.4, 3.5**

### Property 6: Inline creator image handling
*For any* category created via inline creator with an image upload, the response should include the image path and the file should be stored identically to the main form submission
**Validates: Requirements 4.2, 4.3**

## Error Handling

### Validation Errors

1. **Invalid File Type**
   - Error: "The image must be a file of type: jpeg, jpg, png, gif, webp."
   - Display: Show error message below file input
   - Action: Prevent form submission

2. **File Too Large**
   - Error: "The image may not be greater than 2048 kilobytes."
   - Display: Show error message below file input
   - Action: Prevent form submission

3. **Upload Failure**
   - Error: "Failed to upload image. Please try again."
   - Display: Flash error message
   - Action: Return to form with input preserved
   - Logging: Log the exception details

### Storage Errors

1. **Storage Permission Issues**
   - Error: "Unable to save image. Please contact administrator."
   - Logging: Log full error with file path
   - Action: Rollback transaction, return error

2. **Disk Space Issues**
   - Error: "Insufficient storage space. Please contact administrator."
   - Logging: Log disk space error
   - Action: Rollback transaction, return error

### Display Errors

1. **Missing Image File**
   - Behavior: Display placeholder icon
   - No error message to user
   - Logging: Log warning about missing file

2. **Invalid Image Path**
   - Behavior: Display placeholder icon
   - No error message to user
   - Logging: Log warning about invalid path

## Testing Strategy

### Unit Tests

1. **CategoryRequest Validation Test**
   - Test valid image uploads pass validation
   - Test invalid file types are rejected
   - Test oversized files are rejected
   - Test missing image is allowed (nullable)

2. **Controller Image Processing Test**
   - Test image upload stores file correctly
   - Test image path is saved to database
   - Test old image is deleted on update
   - Test existing image is preserved without new upload

3. **Model Accessor Test** (if implemented)
   - Test image URL generation for stored paths
   - Test external URLs are returned unchanged
   - Test null values are handled correctly

### Property-Based Tests

Property-based tests will use **Pest** with **Pest's built-in property testing** capabilities. Each test will run a minimum of 100 iterations.

1. **Property 1: Image file validation**
   - Generate random files with various types and sizes
   - Verify only valid image types under 2MB are accepted
   - Verify invalid files are rejected with appropriate errors

2. **Property 2: Image storage path consistency**
   - Generate random valid image uploads
   - Verify all stored paths follow 'categories/{filename}' pattern
   - Verify files exist at the stored paths

3. **Property 3: Old image deletion on update**
   - Generate categories with existing images
   - Upload new images
   - Verify old image files are deleted from storage

4. **Property 4: Image preservation without upload**
   - Generate categories with existing images
   - Update categories without new image uploads
   - Verify image_url remains unchanged

5. **Property 5: Image display URL generation**
   - Generate categories with various image_url values
   - Verify displayed URLs use correct asset() helper format
   - Verify external URLs are handled correctly

6. **Property 6: Inline creator image handling**
   - Generate random category data with images
   - Create via inline creator
   - Verify files are stored and paths are returned correctly

### Integration Tests

1. **Category Creation with Image**
   - Create category with image upload
   - Verify file is stored
   - Verify database record is correct
   - Verify image displays in list view

2. **Category Update with Image**
   - Update category with new image
   - Verify old image is deleted
   - Verify new image is stored
   - Verify image displays correctly

3. **Inline Category Creation with Image**
   - Create category via inline modal with image
   - Verify AJAX response includes image data
   - Verify file is stored
   - Verify dropdown is updated

### Manual Testing Checklist

1. Upload various image formats (jpg, png, gif, webp)
2. Try uploading non-image files (should fail)
3. Try uploading oversized files (should fail)
4. Verify image preview works before submission
5. Verify images display correctly in list view
6. Verify images display correctly in detail view
7. Update category with new image (verify old is deleted)
8. Update category without new image (verify existing preserved)
9. Test inline category creator with image upload
10. Verify placeholder displays for categories without images
