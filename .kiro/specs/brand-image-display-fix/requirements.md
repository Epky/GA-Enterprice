# Requirements Document

## Introduction

This document outlines the requirements for fixing the brand image display issue in the staff brand management interface. Currently, brand logos are not displaying properly in the brand index page, while the category images work correctly. This fix will ensure brand logos display consistently with the category implementation.

## Glossary

- **Brand Management System**: The staff interface for managing product brands
- **Logo URL**: The database field storing the path to the brand's logo image
- **Image Display**: The visual rendering of brand logos in the user interface
- **Storage Path**: The file system location where brand logo images are stored

## Requirements

### Requirement 1

**User Story:** As a staff member, I want to see brand logos displayed correctly in the brand management interface, so that I can visually identify brands quickly.

#### Acceptance Criteria

1. WHEN a brand has a logo_url value THEN the system SHALL display the logo image in the brand card
2. WHEN a brand logo image is displayed THEN the system SHALL use the correct storage path resolution
3. WHEN a brand has no logo_url THEN the system SHALL display a placeholder icon
4. WHEN the logo image file is missing THEN the system SHALL display a placeholder icon without errors
5. WHEN viewing the brand index page THEN the system SHALL display all brand logos with consistent sizing and styling

### Requirement 2

**User Story:** As a staff member, I want brand product counts to display accurately, so that I can see how many products are associated with each brand.

#### Acceptance Criteria

1. WHEN viewing a brand card THEN the system SHALL display the total product count
2. WHEN viewing a brand card THEN the system SHALL display the active product count  
3. WHEN the product counts are displayed THEN the system SHALL use the correct attribute names from the database query
4. WHEN a brand has zero products THEN the system SHALL display "0" for both counts
5. WHEN product counts are loaded THEN the system SHALL use efficient database queries with eager loading

### Requirement 3

**User Story:** As a developer, I want the brand image display to follow the same pattern as categories, so that the codebase is consistent and maintainable.

#### Acceptance Criteria

1. WHEN implementing brand image display THEN the system SHALL use the same storage path resolution as categories
2. WHEN implementing brand image display THEN the system SHALL use the same placeholder fallback pattern as categories
3. WHEN implementing brand image display THEN the system SHALL use the same image sizing and styling as categories
4. WHEN loading brand data THEN the system SHALL use consistent eager loading patterns
5. WHEN handling missing images THEN the system SHALL use the same error handling approach as categories
