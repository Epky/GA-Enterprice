# Requirements Document

## Introduction

Ang walk-in transaction system ay may bug kung saan ang stock detection ay hindi tama. Ang system ay nag-display ng "Insufficient stock to reserve. Available: 0" kahit may actual stock pa sa inventory. Ang problema ay nangyayari dahil ang `total_stock` attribute ay sumusukat lang ng `quantity_available` at hindi kasama ang total inventory (available + reserved).

## Glossary

- **Inventory System**: Ang system na nag-track ng product stock levels
- **Walk-In Transaction System**: Ang system para sa point-of-sale transactions
- **Product Model**: Ang Eloquent model na nag-represent ng products
- **Inventory Model**: Ang Eloquent model na nag-track ng stock quantities
- **quantity_available**: Ang stock na available para i-reserve
- **quantity_reserved**: Ang stock na naka-reserve na para sa orders
- **total_stock**: Ang kabuuang stock (available + reserved)

## Requirements

### Requirement 1

**User Story:** As a staff member, I want to see accurate stock levels when creating walk-in transactions, so that I can properly add items to orders.

#### Acceptance Criteria

1. WHEN the system checks product stock availability THEN the system SHALL calculate the total stock correctly by summing quantity_available and quantity_reserved
2. WHEN displaying available stock in error messages THEN the system SHALL show only the quantity_available (not reserved stock)
3. WHEN a product has inventory records THEN the system SHALL aggregate stock across all inventory locations
4. WHEN a product has no inventory records THEN the system SHALL return zero as the total stock
5. WHEN checking stock for walk-in transactions THEN the system SHALL use quantity_available to determine if stock can be reserved

### Requirement 2

**User Story:** As a staff member, I want clear error messages when stock is insufficient, so that I understand why I cannot add items to an order.

#### Acceptance Criteria

1. WHEN attempting to add an item with insufficient stock THEN the system SHALL display the actual quantity_available in the error message
2. WHEN stock is reserved by other orders THEN the system SHALL indicate that stock is reserved but not available
3. WHEN displaying stock information THEN the system SHALL distinguish between total stock and available stock
4. WHEN an error occurs during stock reservation THEN the system SHALL provide actionable information to the user

### Requirement 3

**User Story:** As a developer, I want consistent stock calculation methods across the application, so that stock levels are accurate everywhere.

#### Acceptance Criteria

1. WHEN calculating total stock THEN the system SHALL use the same calculation method across all components
2. WHEN checking stock availability THEN the system SHALL always use quantity_available from inventory records
3. WHEN aggregating stock across locations THEN the system SHALL sum quantities correctly
4. WHEN accessing stock attributes THEN the system SHALL use efficient database queries to avoid N+1 problems
