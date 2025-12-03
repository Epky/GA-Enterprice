# Design Document

## Overview

The product image upload functionality currently suffers from a critical bug where uploading a single image results in three duplicate images being created. The root cause has been identified: the ImageManager component is being initialized three times due to multiple event listeners (immediate execution, DOMContentLoaded, and window load), causing the file input change event handler to be attached three times. When a user selects one file, all three handlers fire, processing the same file three times and creating three duplicate database records and file uploads.

This design addresses the issue by implementing a singleton pattern for ImageManager initialization and adding safeguards to prevent duplicate processing.

## Architecture

### Current Architecture (Problematic)

```
User selects 1 image
    ↓
File input change event fires
    ↓
Handler 1 processes file → Creates Image 1
Handler 2 processes file → Creates Image 2  
Handler 3 processes file → Creates Image 3
    ↓
Result: 3 duplicate images
```

### Proposed Architecture (Fixed)

```
User selects 1 image
    ↓
File input change event fires
    ↓
Single handler processes file → Creates Image 1
(Duplicate handlers prevented by singleton pattern)
    ↓
Result: 1 image (correct)
```

## Components and Interfaces

### 1. ImageManager Class (resources/js/image-manager.js)

**Current Issues:**
- No protection against multiple instantiations
- Event handlers attached without checking for existing handlers
- No tracking of processed files

**Proposed Changes:**
- Add singleton pattern to prevent multiple instances
- Track processed files to prevent duplicate processing
- Add instance checking before attaching event handlers

**Interface:**
```javascript
class ImageManager {
    static instances = new Map(); // Track instances by element ID
    
    constructor(options) {
        // Check if instance already exists for this element
        const elementId = options.fileInput?.id;
        if (elementId && ImageManager.instances.has(elementId)) {
            return ImageManager.instances.get(elementId);
        }
        
        // Initialize instance
        this.processedFiles = new Set(); // Track processed files
        // ... existing initialization
        
        // Store instance
        if (elementId) {
            ImageManager.instances.set(elementId, this);
        }
    }
    
    handleFileSelect(e) {
        const files = e.target.files;
        
        // Prevent duplicate processing
        const fileSignatures = Array.from(files).map(f => 
            `${f.name}-${f.size}-${f.lastModified}`
        );
        
        // Filter out already processed files
        const newFiles = Array.from(files).filter((file, index) => {
            const signature = fileSignatures[index];
            if (this.processedFiles.has(signature)) {
                return false;
            }
            this.processedFiles.add(signature);
            return true;
        });
        
        if (newFiles.length === 0) {
            return; // All files already processed
        }
        
        this.handleFiles(newFiles);
    }
}
```

### 2. Image Manager Component (resources/views/components/image-manager.blade.php)

**Current Issues:**
- Initialization function called three times:
  1. Immediate execution
  2. DOMContentLoaded event
  3. Window load event
- No check for existing initialization

**Proposed Changes:**
- Use a single initialization approach with proper guards
- Add flag to track if already initialized
- Remove redundant event listeners

**Interface:**
```javascript
(function() {
    // Unique initialization flag for this instance
    const initFlagName = 'imageManagerInit_{{ str_replace('-', '_', $name) }}';
    
    function initImageManager_{{ str_replace('-', '_', $name) }}() {
        // Check if already initialized
        if (window[initFlagName]) {
            console.log('ImageManager already initialized for {{ $name }}');
            return;
        }
        
        // Check if ImageManager class is available
        if (typeof ImageManager === 'undefined') {
            console.log('ImageManager not loaded yet, retrying...');
            setTimeout(initImageManager_{{ str_replace('-', '_', $name) }}, 100);
            return;
        }
        
        // Check if DOM elements are ready
        const fileInput = document.getElementById('{{ $name }}');
        if (!fileInput) {
            console.log('File input not ready yet, retrying...');
            setTimeout(initImageManager_{{ str_replace('-', '_', $name) }}, 100);
            return;
        }
        
        // Mark as initialized BEFORE creating instance
        window[initFlagName] = true;
        
        // Initialize (singleton pattern will prevent duplicates)
        const imageManagerInstance = new ImageManager({
            // ... options
        });
        
        // Store reference
        window.imageManager_{{ str_replace('-', '_', $name) }} = imageManagerInstance;
    }
    
    // Single initialization approach
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initImageManager_{{ str_replace('-', '_', $name) }}, { once: true });
    } else {
        // DOM already loaded
        initImageManager_{{ str_replace('-', '_', $name) }}();
    }
})();
```

### 3. Product Service (app/Services/ProductService.php)

**Current State:**
- Correctly calls ImageUploadService once
- No issues identified in this layer

**No Changes Required**

### 4. Image Upload Service (app/Services/ImageUploadService.php)

**Current State:**
- Correctly processes each file once
- No duplicate creation logic

**Proposed Enhancement:**
- Add duplicate detection as a safety measure
- Check if file with same hash already exists for product

**Interface:**
```php
public function uploadProductImages(Product $product, array $files, array $options = []): array
{
    $uploadedImages = [];
    $startOrder = $this->getNextDisplayOrder($product);
    $processedHashes = [];

    DB::transaction(function () use ($product, $files, $options, &$uploadedImages, &$processedHashes, $startOrder) {
        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                // Generate file hash to detect duplicates
                $fileHash = md5_file($file->getPathname());
                
                // Skip if we've already processed this exact file in this batch
                if (in_array($fileHash, $processedHashes)) {
                    continue;
                }
                
                $processedHashes[] = $fileHash;
                
                $uploadedImages[] = $this->uploadSingleProductImage(
                    $product,
                    $file,
                    array_merge($options, [
                        'alt_text' => $options['alt_texts'][$index] ?? null,
                        'display_order' => $startOrder + count($uploadedImages),
                    ])
                );
            }
        }
    });

    return $uploadedImages;
}
```

## Data Models

No changes to database schema required. The issue is purely in the frontend/JavaScript layer.

**Existing Models:**
- `Product` - No changes
- `ProductImage` - No changes

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Single initialization per component
*For any* image manager component instance, the initialization function should execute exactly once, regardless of how many DOM events fire
**Validates: Requirements 2.5**

### Property 2: File processing uniqueness
*For any* file selected by the user, the file should be processed exactly once within a single selection event, even if multiple event handlers exist
**Validates: Requirements 1.1, 1.5**

### Property 3: Image record uniqueness
*For any* uploaded file, exactly one database record should be created in the product_images table
**Validates: Requirements 1.2**

### Property 4: File storage uniqueness
*For any* uploaded file, exactly one file should be stored in the storage system
**Validates: Requirements 1.3**

### Property 5: Event handler singularity
*For any* file input element, exactly one change event handler should be attached by the ImageManager
**Validates: Requirements 2.2, 3.5**

### Property 6: Batch upload correctness
*For any* set of N files selected by the user, exactly N image records should be created
**Validates: Requirements 1.4**

### Property 7: Initialization idempotency
*For any* image manager component, calling the initialization function multiple times should have the same effect as calling it once
**Validates: Requirements 3.1**

## Error Handling

### Frontend Errors

1. **Multiple Initialization Attempts**
   - Detection: Check initialization flag before proceeding
   - Handling: Return early if already initialized
   - Logging: Console log for debugging

2. **Duplicate File Processing**
   - Detection: Check file signature against processed set
   - Handling: Skip already processed files
   - Logging: Console log skipped files

3. **Missing DOM Elements**
   - Detection: Check for null/undefined elements
   - Handling: Retry with timeout or fail gracefully
   - Logging: Console error with element details

### Backend Errors

1. **Duplicate File Upload**
   - Detection: Check file hash against recent uploads
   - Handling: Skip duplicate, return existing image
   - Logging: Log duplicate attempt

2. **Transaction Failures**
   - Detection: Database transaction rollback
   - Handling: Clean up any partial uploads
   - Logging: Log full error with context

## Testing Strategy

### Unit Tests

1. **ImageManager Singleton Test**
   - Test that creating multiple instances with same element returns same instance
   - Test that different elements get different instances

2. **File Processing Deduplication Test**
   - Test that processing same file twice only creates one preview
   - Test that file signature generation is consistent

3. **Initialization Flag Test**
   - Test that initialization flag prevents multiple initializations
   - Test that flag is set before instance creation

### Property-Based Tests

Property-based tests will use **Pest PHP** with **Pest Property Testing** plugin for PHP tests, and **fast-check** library for JavaScript tests.

1. **Property Test: Single Initialization**
   - Generate random component configurations
   - Call initialization multiple times
   - Verify only one instance exists

2. **Property Test: File Processing Uniqueness**
   - Generate random sets of files (including duplicates)
   - Process files through ImageManager
   - Verify each unique file processed exactly once

3. **Property Test: Image Record Creation**
   - Generate random file uploads
   - Submit to backend
   - Verify database records match file count exactly

4. **Property Test: Event Handler Count**
   - Initialize ImageManager
   - Check event listeners on file input
   - Verify exactly one change handler exists

### Integration Tests

1. **Full Upload Flow Test**
   - Select 1 image through UI
   - Submit form
   - Verify 1 image in database
   - Verify 1 file in storage

2. **Multiple Image Upload Test**
   - Select 3 different images
   - Submit form
   - Verify 3 images in database
   - Verify 3 files in storage

3. **Edit Page Upload Test**
   - Load product edit page with existing images
   - Upload 1 new image
   - Verify only 1 new image added

## Implementation Plan

### Phase 1: Frontend Fixes (Critical)

1. **Update ImageManager Class**
   - Add singleton pattern with instance tracking
   - Add file processing deduplication
   - Add processed files tracking

2. **Update Image Manager Component**
   - Remove redundant initialization calls
   - Add initialization flag check
   - Use single initialization approach with `{ once: true }` option

3. **Add Logging**
   - Add console logs for debugging
   - Track initialization attempts
   - Track file processing

### Phase 2: Backend Safety Measures

1. **Update ImageUploadService**
   - Add file hash checking
   - Add duplicate detection
   - Add batch processing safeguards

2. **Add Validation**
   - Validate file uniqueness in batch
   - Check for recent duplicate uploads

### Phase 3: Testing

1. **Write Unit Tests**
   - Test singleton pattern
   - Test file deduplication
   - Test initialization guards

2. **Write Property Tests**
   - Test initialization idempotency
   - Test file processing uniqueness
   - Test image record creation

3. **Manual Testing**
   - Test on product create page
   - Test on product edit page
   - Test with various file counts

### Phase 4: Verification

1. **Verify Fix**
   - Upload 1 image → Verify 1 image created
   - Upload 3 images → Verify 3 images created
   - Test on both create and edit pages

2. **Performance Check**
   - Verify no performance degradation
   - Check initialization time
   - Check file processing time

## Security Considerations

1. **File Hash Validation**
   - Use secure hashing (MD5 sufficient for deduplication)
   - Don't expose file hashes to client

2. **Rate Limiting**
   - Consider adding rate limiting for uploads
   - Prevent abuse through rapid uploads

3. **File Validation**
   - Maintain existing file type validation
   - Maintain existing file size validation

## Performance Considerations

1. **Singleton Pattern**
   - Minimal overhead (Map lookup)
   - Prevents memory waste from duplicate instances

2. **File Signature Generation**
   - Lightweight (name + size + timestamp)
   - No file reading required

3. **Processed Files Tracking**
   - Set data structure for O(1) lookup
   - Cleared after upload completes

## Rollback Plan

If issues arise after deployment:

1. **Immediate Rollback**
   - Revert image-manager.js to previous version
   - Revert image-manager.blade.php to previous version

2. **Temporary Workaround**
   - Add manual deduplication in backend
   - Filter duplicate images by file hash

3. **Investigation**
   - Check browser console for errors
   - Review server logs for upload issues
   - Test in different browsers

## Success Criteria

1. Uploading 1 image creates exactly 1 image record
2. Uploading N images creates exactly N image records
3. No duplicate images in database
4. No duplicate files in storage
5. All existing functionality continues to work
6. No performance degradation
