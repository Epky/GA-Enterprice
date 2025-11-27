# Requirements Document

## Introduction

The inventory movements history page currently displays all movement types including internal system operations (Reservation, Release) that are not meaningful to staff users reviewing inventory history. This creates a cluttered, confusing interface where important business movements (purchases, sales, adjustments) are buried among technical system operations. This feature will improve the movements display by filtering, grouping, and presenting movement data in a more user-friendly manner.

## Glossary

- **Movement System**: The inventory tracking component that records all stock changes
- **Business Movement**: User-initiated inventory changes (purchase, sale, return, damage, adjustment, transfer)
- **System Movement**: Internal operations (reservation, release) used for transaction processing
- **Movement History View**: The staff interface displaying inventory movement records
- **Movement Filter**: User controls for selecting which movements to display

## Requirements

### Requirement 1

**User Story:** As a staff member, I want to view only meaningful inventory movements by default, so that I can quickly understand actual stock changes without system noise.

#### Acceptance Criteria

1. WHEN a staff member views the inventory movements page THEN the Movement System SHALL display only business movements by default
2. WHEN displaying movements THEN the Movement System SHALL exclude reservation and release type movements from the default view
3. WHEN a movement is a business movement THEN the Movement System SHALL include it in the default filtered results
4. WHERE a staff member needs to see all movements THEN the Movement System SHALL provide a toggle to show system movements
5. WHEN the show system movements toggle is enabled THEN the Movement System SHALL display all movement types including reservations and releases

### Requirement 2

**User Story:** As a staff member, I want related movements to be visually grouped, so that I can understand the complete context of inventory changes.

#### Acceptance Criteria

1. WHEN a sale movement has associated reservation movements THEN the Movement System SHALL display them as a grouped transaction
2. WHEN displaying grouped movements THEN the Movement System SHALL show the primary business movement prominently
3. WHEN displaying grouped movements THEN the Movement System SHALL show related system movements as nested sub-items
4. WHEN a movement is part of a walk-in transaction THEN the Movement System SHALL extract and display the transaction reference
5. WHEN displaying transaction references THEN the Movement System SHALL format them as clickable links to the transaction details

### Requirement 3

**User Story:** As a staff member, I want movement notes to be clearly formatted, so that I can quickly identify reasons and important information.

#### Acceptance Criteria

1. WHEN a movement has notes containing a reason THEN the Movement System SHALL extract and display the reason as a badge
2. WHEN displaying movement notes THEN the Movement System SHALL separate structured data from free-form text
3. WHEN notes contain transaction references THEN the Movement System SHALL format them as actionable links
4. WHEN notes are empty THEN the Movement System SHALL display a subtle placeholder instead of "No notes"
5. WHEN notes exceed display space THEN the Movement System SHALL truncate with an expand option

### Requirement 4

**User Story:** As a staff member, I want to filter movements by business relevance, so that I can focus on the information I need.

#### Acceptance Criteria

1. WHEN the movement type filter is displayed THEN the Movement System SHALL group types into business and system categories
2. WHEN a staff member selects a filter THEN the Movement System SHALL persist the selection across page refreshes
3. WHEN filtering by movement type THEN the Movement System SHALL update the results without page reload
4. WHEN multiple filters are applied THEN the Movement System SHALL combine them with AND logic
5. WHEN filters are cleared THEN the Movement System SHALL return to the default business-only view

### Requirement 5

**User Story:** As a staff member, I want the movements table to be scannable, so that I can quickly find specific information.

#### Acceptance Criteria

1. WHEN displaying movement quantities THEN the Movement System SHALL use color coding for increases and decreases
2. WHEN displaying movement types THEN the Movement System SHALL use consistent badge colors for each type
3. WHEN displaying dates THEN the Movement System SHALL show relative time for recent movements
4. WHEN displaying product information THEN the Movement System SHALL show both name and SKU
5. WHEN the table has many rows THEN the Movement System SHALL maintain header visibility during scrolling
