# Requirements Document

## Introduction

The staff dashboard displays a "Recent Inventory Movements (Last 7 Days)" widget that shows the latest inventory changes. Currently, this widget has readability issues: notes are truncated at 50 characters making transaction references unreadable, system movements (reservations/releases) clutter the view, and the table layout is cramped. This feature will improve the dashboard widget to provide a cleaner, more scannable view of recent inventory activity.

## Glossary

- **Dashboard Widget**: The "Recent Inventory Movements" table displayed on the staff dashboard
- **Business Movement**: User-initiated inventory changes (purchase, sale, return, damage, adjustment, transfer)
- **System Movement**: Internal operations (reservation, release) used for transaction processing
- **Movement Notes**: Text descriptions attached to inventory movements that may contain transaction references and reasons
- **Transaction Reference**: An identifier linking a movement to a specific transaction (e.g., WI-20251125-0001)

## Requirements

### Requirement 1

**User Story:** As a staff member viewing the dashboard, I want to see only meaningful inventory movements, so that I can quickly understand recent stock changes without system noise.

#### Acceptance Criteria

1. WHEN the dashboard loads THEN the system SHALL display only business movements by default
2. WHEN displaying movements THEN the system SHALL exclude reservation and release type movements
3. WHEN a movement is displayed THEN the system SHALL show the 10 most recent business movements
4. WHEN no business movements exist THEN the system SHALL hide the entire widget
5. WHEN movements are displayed THEN the system SHALL order them by creation date descending

### Requirement 2

**User Story:** As a staff member, I want movement notes to be clearly readable, so that I can understand what happened without clicking through to details.

#### Acceptance Criteria

1. WHEN a movement has notes containing a transaction reference THEN the system SHALL display the reference as a clickable link
2. WHEN a movement has notes containing a reason THEN the system SHALL display the reason as a badge
3. WHEN notes contain structured data THEN the system SHALL separate it from free-form text
4. WHEN notes are empty THEN the system SHALL display a subtle placeholder
5. WHEN notes exceed available space THEN the system SHALL show full text with word wrapping instead of truncation

### Requirement 3

**User Story:** As a staff member, I want the movements table to be easy to scan, so that I can quickly identify important information.

#### Acceptance Criteria

1. WHEN displaying movement quantities THEN the system SHALL use color coding for increases and decreases
2. WHEN displaying movement types THEN the system SHALL use consistent badge colors matching the full movements page
3. WHEN displaying dates THEN the system SHALL show both date and time in a readable format
4. WHEN displaying product information THEN the system SHALL show product name and SKU
5. WHEN the table is displayed THEN the system SHALL use adequate spacing for readability

### Requirement 4

**User Story:** As a staff member, I want to access detailed movement history, so that I can investigate specific inventory changes.

#### Acceptance Criteria

1. WHEN viewing the dashboard widget THEN the system SHALL display a "View All" link to the full movements page
2. WHEN a transaction reference is clicked THEN the system SHALL navigate to the transaction details page
3. WHEN the widget is displayed THEN the system SHALL indicate the time range being shown
4. WHEN movements are displayed THEN the system SHALL show the total count of movements in the widget
5. WHEN the "View All" link is clicked THEN the system SHALL preserve any relevant context or filters
