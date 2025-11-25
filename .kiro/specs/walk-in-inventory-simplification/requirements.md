# Requirements Document

## Introduction

Ang kasalukuyang walk-in transaction system ay gumagamit ng "reservation" at "release" inventory movements na hindi angkop para sa walk-in process. Sa walk-in transactions, ang customer ay direktang bumibili at kumukuha ng produkto, kaya hindi kailangan ng reservation step. Ang feature na ito ay mag-simplify ng inventory movement tracking para sa walk-in transactions upang mas accurate at maintainable ang system.

## Glossary

- **Walk-In Transaction**: Transaksyon kung saan ang customer ay personal na pumupunta sa tindahan at direktang bumibili ng produkto
- **Inventory Movement**: Record ng pagbabago sa stock quantity (restock, sale, return, etc.)
- **Movement Type**: Uri ng inventory movement (restock, sale, return, adjustment, damage, transfer)
- **Online Order**: Order na ginawa online na may pending status at nangangailangan ng reservation
- **Reservation**: Pag-reserve ng stock para sa pending orders (applicable lang sa online orders)
- **Available Stock**: Stock na available para mabili
- **Reserved Stock**: Stock na naka-reserve para sa pending orders

## Requirements

### Requirement 1

**User Story:** Bilang staff member, gusto kong makita ang simplified inventory movement history para sa walk-in transactions, para mas madali kong maintindihan ang actual flow ng stock.

#### Acceptance Criteria

1. WHEN a walk-in transaction is completed THEN the system SHALL create a single "sale" inventory movement record
2. WHEN viewing inventory movement history THEN the system SHALL NOT display "reservation" or "release" movements for walk-in transactions
3. WHEN a walk-in transaction is cancelled before completion THEN the system SHALL NOT create any inventory movement records
4. WHEN displaying movement history THEN the system SHALL show clear labels for each movement type (Restock, Sale, Return, Adjustment, Damage, Transfer)
5. WHEN filtering movements by type THEN the system SHALL provide options for all valid movement types excluding reservation and release

### Requirement 2

**User Story:** Bilang staff member, gusto kong ang walk-in transaction process ay direktang mag-deduct ng stock sa completion, para accurate ang real-time inventory.

#### Acceptance Criteria

1. WHEN adding items to a walk-in transaction THEN the system SHALL check available stock WITHOUT creating reservation records
2. WHEN completing a walk-in transaction THEN the system SHALL directly deduct the quantity from available stock
3. WHEN completing a walk-in transaction THEN the system SHALL create a single inventory movement record with type "sale"
4. WHEN a walk-in transaction is in progress THEN the system SHALL NOT modify the inventory quantities until completion
5. WHEN cancelling a walk-in transaction THEN the system SHALL NOT need to release any reserved stock

### Requirement 3

**User Story:** Bilang system administrator, gusto kong malinaw ang distinction between walk-in at online order inventory handling, para maintainable ang codebase.

#### Acceptance Criteria

1. WHEN processing walk-in transactions THEN the system SHALL use direct stock deduction without reservation
2. WHEN processing online orders THEN the system SHALL use reservation and fulfillment workflow
3. WHEN viewing movement history THEN the system SHALL clearly indicate the source transaction type (walk-in vs online)
4. WHEN generating reports THEN the system SHALL separate walk-in sales from online sales
5. WHEN tracking inventory movements THEN the system SHALL use consistent movement types across all transaction types

### Requirement 4

**User Story:** Bilang staff member, gusto kong makita ang comprehensive movement history na may proper context, para maintindihan ko ang bawat stock change.

#### Acceptance Criteria

1. WHEN viewing a movement record THEN the system SHALL display the movement type, quantity, date, staff member, and transaction reference
2. WHEN a movement is related to a walk-in transaction THEN the system SHALL display the order number and customer name
3. WHEN filtering movements THEN the system SHALL allow filtering by date range, movement type, location, and product
4. WHEN viewing movement details THEN the system SHALL show the before and after stock levels
5. WHEN exporting movement history THEN the system SHALL include all relevant context and metadata
