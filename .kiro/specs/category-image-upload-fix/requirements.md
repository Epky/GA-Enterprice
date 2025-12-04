# Requirements Document

## Introduction

The category management system currently only supports image URLs (text input) for category images, not actual file uploads. Staff members cannot upload image files directly from their computer - they must provide external URLs. This creates a poor user experience and limits the ability to manage category images effectively. This feature will add proper file upload functionality for category images, similar to how product images work.

## Glossary

- **Category**: A classification grouping for products in the system
- **Staff Member**: A user with staff role who manages categories
- **Image Upload**: The process of selecting and uploading an image file from the user's device
- **Storage Path**: The location where uploaded images are stored (storage/app/public/categories)
- **Image URL Field**: The current text input that accepts only URLs
- **File Input**: An HTML input element that allows file selection from the user's device

## Requirements

### Requirement 1

**User Story:** As a staff member, I want to upload category images directly from my computer, so that I can easily add visual representations without needing external image hosting.

#### Acceptance Criteria

1. WHEN a staff member views the category create form THEN the system SHALL display a file input field for image upload
2. WHEN a staff member selects an image file THEN the system SHALL show a preview of the selected image before submission
3. WHEN a staff member submits the form with an image file THEN the system SHALL upload the file to storage and save the path to the database
4. WHEN a staff member uploads an image THEN the system SHALL validate the file is an image type (jpg, jpeg, png, gif, webp)
5. WHEN a staff member uploads an image THEN the system SHALL validate the file size does not exceed 2MB

### Requirement 2

**User Story:** As a staff member, I want to update category images by uploading new files, so that I can keep category visuals current and relevant.

#### Acceptance Criteria

1. WHEN a staff member views the category edit form THEN the system SHALL display the current category image if one exists
2. WHEN a staff member views the category edit form THEN the system SHALL display a file input field to upload a new image
3. WHEN a staff member uploads a new image THEN the system SHALL replace the old image file and update the database
4. WHEN a staff member uploads a new image THEN the system SHALL delete the old image file from storage to prevent orphaned files
5. WHEN a staff member saves without selecting a new image THEN the system SHALL preserve the existing image

### Requirement 3

**User Story:** As a staff member, I want to see category images displayed correctly in the category list, so that I can quickly identify categories visually.

#### Acceptance Criteria

1. WHEN a staff member views the category index page THEN the system SHALL display category images using the correct storage path
2. WHEN a category has an uploaded image THEN the system SHALL display the image thumbnail in the list view
3. WHEN a category has no uploaded image THEN the system SHALL display a placeholder icon
4. WHEN a staff member views the category show page THEN the system SHALL display the full category image
5. WHEN the system displays category images THEN the system SHALL use the asset helper with storage path for proper URL generation

### Requirement 4

**User Story:** As a staff member, I want the inline category creator to support image uploads, so that I can add complete category information without leaving the product creation page.

#### Acceptance Criteria

1. WHEN a staff member opens the inline category modal THEN the system SHALL display a file input field for image upload
2. WHEN a staff member uploads an image via inline creator THEN the system SHALL process the file upload identically to the main form
3. WHEN a staff member creates a category via inline creator with an image THEN the system SHALL return the new category data including the image path
4. WHEN the inline creator returns success THEN the system SHALL update the category dropdown to show the new category
5. WHEN the inline creator processes an image THEN the system SHALL validate file type and size before accepting
