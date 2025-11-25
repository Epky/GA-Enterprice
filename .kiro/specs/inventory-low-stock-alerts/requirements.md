# Requirements Document

## Introduction

Ang inventory system ay nangangailangan ng real-time low stock alert system na mag-monitor ng bawat product at mag-detect kung kailan na malapit nang maubos ang stock. Ang system ay dapat mag-display ng warning at critical alerts based sa reorder level ng bawat product, para ma-inform agad ang staff na kailangan na mag-restock.

## Glossary

- **Inventory System**: Ang system na nag-track ng product stock levels
- **Low Stock Alert**: Notification na malapit nang maubos ang stock ng product
- **Warning Level**: Stock level na 50% ng reorder level (yellow alert)
- **Critical Level**: Stock level na 25% ng reorder level (red alert)
- **Reorder Level**: Ang minimum stock level na dapat i-maintain para sa product
- **Alert Dashboard**: Ang page na nag-display ng lahat ng low stock alerts
- **Real-time Detection**: Automatic detection ng stock levels habang nagbabago ang inventory

## Requirements

### Requirement 1

**User Story:** As a staff member, I want to see real-time low stock alerts for each product, so that I can restock before items run out.

#### Acceptance Criteria

1. WHEN the system checks inventory levels THEN the system SHALL detect products with stock at or below warning level (50% of reorder level)
2. WHEN the system checks inventory levels THEN the system SHALL detect products with stock at or below critical level (25% of reorder level)
3. WHEN a product's stock changes THEN the system SHALL immediately recalculate the alert status
4. WHEN displaying alerts THEN the system SHALL show the current stock, reorder level, and alert severity
5. WHEN a product has no reorder level set THEN the system SHALL not generate alerts for that product

### Requirement 2

**User Story:** As a staff member, I want to view a dedicated alerts page, so that I can see all low stock items in one place.

#### Acceptance Criteria

1. WHEN accessing the alerts page THEN the system SHALL display all products with warning or critical stock levels
2. WHEN displaying alerts THEN the system SHALL group products by severity (critical first, then warning)
3. WHEN displaying alerts THEN the system SHALL show product name, current stock, reorder level, and location
4. WHEN filtering by location THEN the system SHALL show only alerts for that specific location
5. WHEN no alerts exist THEN the system SHALL display a message indicating all stock levels are healthy

### Requirement 3

**User Story:** As a staff member, I want to see alert counts on the dashboard, so that I know how many items need attention without visiting the alerts page.

#### Acceptance Criteria

1. WHEN viewing the dashboard THEN the system SHALL display the count of critical alerts
2. WHEN viewing the dashboard THEN the system SHALL display the count of warning alerts
3. WHEN viewing the dashboard THEN the system SHALL display the total count of all alerts
4. WHEN clicking on alert counts THEN the system SHALL navigate to the alerts page with appropriate filters
5. WHEN alert counts change THEN the system SHALL update the display immediately

### Requirement 4

**User Story:** As a staff member, I want visual indicators for alert severity, so that I can quickly identify which items need urgent attention.

#### Acceptance Criteria

1. WHEN displaying critical alerts THEN the system SHALL use red color indicators
2. WHEN displaying warning alerts THEN the system SHALL use yellow/orange color indicators
3. WHEN displaying healthy stock THEN the system SHALL use green color indicators
4. WHEN showing alert badges THEN the system SHALL include the severity level text
5. WHEN sorting alerts THEN the system SHALL prioritize critical alerts over warning alerts

### Requirement 5

**User Story:** As a staff member, I want to see the percentage of remaining stock, so that I can understand how urgent the restock is.

#### Acceptance Criteria

1. WHEN displaying an alert THEN the system SHALL calculate the percentage of stock remaining relative to reorder level
2. WHEN stock is at 25% or below THEN the system SHALL classify it as critical
3. WHEN stock is between 26% and 50% THEN the system SHALL classify it as warning
4. WHEN stock is above 50% THEN the system SHALL classify it as healthy
5. WHEN calculating percentages THEN the system SHALL use quantity_available (not reserved stock)
