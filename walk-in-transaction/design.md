# Walk-In Transaction Feature Design

## Overview

The Walk-In Transaction feature is a point-of-sale (POS) system that enables staff members to process in-store purchases efficiently. The system leverages the existing Laravel e-commerce infrastructure, including the Product, Inventory, Order, and Payment models. Staff can create transactions by entering customer names, selecting products, specifying quantities, and automatically updating inventory upon completion.

The feature integrates seamlessly with the existing inventory management system (InventoryService) to ensure real-time stock updates and maintains comprehensive audit trails for all transactions. The system supports receipt generation and transaction history for customer service and reporting purposes.

## Architecture

### System Components

The walk-in transaction system follows Laravel's MVC architecture and integrates with existing services:

1. **Presentation Layer**
   - Blade views for transaction interface
   - JavaScript for dynamic product selection and cart management
   - Real-time total calculation

2. **Application Layer**
   - WalkInTransactionController: Handles HTTP requests and orchestrates transaction flow
   - WalkInTransactionService: Business logic for transaction processing
   - Existing InventoryService: Stock management and movement tracking

3. **Domain Layer**
   - Order Model: Represents walk-in transactions (order_type = 'walk_in')
   - OrderItem Model: Individual products in transaction
   - Payment Model: Payment information
   - Product, Inventory, InventoryMovement Models: Existing models

4. **Infrastructure Layer**
   - PostgreSQL database (Supabase)
   - Laravel Eloquent ORM
   - Database transactions for atomicity

### Integration Points

- **Inventory System**: Uses existing InventoryService for stock updates
- **Product Catalog**: Leverages Product model with search capabilities
- **User Authentication**: Staff middleware for access control
- **Audit Trail**: Integrates with InventoryMovement for tracking


## Components and Interfaces

### 1. WalkInTransactionController

**Responsibilities:**
- Handle HTTP requests for walk-in transactions
- Validate user input
- Coordinate with service layer
- Return appropriate responses

**Key Methods:**
```php
public function index(): View
public function create(): View
public function store(Request $request): RedirectResponse
public function show(Order $order): View
public function cancel(Order $order): RedirectResponse
public function receipt(Order $order): View
public function history(Request $request): View
```

### 2. WalkInTransactionService

**Responsibilities:**
- Implement business logic for transactions
- Coordinate inventory updates
- Generate transaction reference numbers
- Handle transaction lifecycle

**Key Methods:**
```php
public function createTransaction(array $data): Order
public function addItem(Order $order, array $itemData): OrderItem
public function removeItem(OrderItem $item): bool
public function updateItemQuantity(OrderItem $item, int $quantity): OrderItem
public function calculateTotal(Order $order): array
public function completeTransaction(Order $order, array $paymentData): Order
public function cancelTransaction(Order $order): bool
public function generateReceipt(Order $order): array
public function getTransactionHistory(array $filters): LengthAwarePaginator
```

### 3. Models

**Order Model (Extended)**
- Existing model with order_type = 'walk_in'
- Relationships: orderItems, payment, staff (user)
- Scopes: walkIn(), pending(), completed()

**OrderItem Model (Existing)**
- Represents individual products in transaction
- Stores snapshot of product data at time of sale

**Payment Model (Existing)**
- Records payment information
- Supports multiple payment methods

### 4. Request Validation Classes

**WalkInTransactionStoreRequest**
```php
- customer_name: required|string|max:255
- customer_phone: nullable|string|max:20
- items: required|array|min:1
- items.*.product_id: required|exists:products,id
- items.*.quantity: required|integer|min:1
- payment_method: required|in:cash,credit_card,debit_card,gcash
```

**WalkInTransactionItemRequest**
```php
- product_id: required|exists:products,id
- variant_id: nullable|exists:product_variants,id
- quantity: required|integer|min:1|max:available_stock
```


## Data Models

### Walk-In Transaction Data Structure

**Order (walk_in type)**
```php
[
    'id' => integer,
    'order_number' => string (unique, e.g., 'WI-20251121-0001'),
    'user_id' => null (for walk-in customers),
    'order_type' => 'walk_in',
    'order_status' => enum ['pending', 'completed', 'cancelled'],
    'payment_status' => enum ['pending', 'paid'],
    'customer_name' => string,
    'customer_email' => string|null,
    'customer_phone' => string|null,
    'subtotal' => decimal(10,2),
    'tax_amount' => decimal(10,2),
    'discount_amount' => decimal(10,2),
    'total_amount' => decimal(10,2),
    'notes' => text|null,
    'internal_notes' => text|null,
    'created_at' => timestamp,
    'updated_at' => timestamp,
]
```

**OrderItem**
```php
[
    'id' => integer,
    'order_id' => integer,
    'product_id' => integer,
    'variant_id' => integer|null,
    'product_name' => string (snapshot),
    'variant_name' => string|null (snapshot),
    'sku' => string (snapshot),
    'quantity' => integer,
    'unit_price' => decimal(10,2) (snapshot),
    'discount_amount' => decimal(10,2),
    'tax_amount' => decimal(10,2),
    'total_price' => decimal(10,2),
    'created_at' => timestamp,
]
```

**Payment**
```php
[
    'id' => integer,
    'order_id' => integer,
    'payment_method' => enum ['cash', 'credit_card', 'debit_card', 'gcash', 'paymaya'],
    'payment_status' => enum ['pending', 'completed', 'failed'],
    'amount' => decimal(10,2),
    'transaction_id' => string|null,
    'payment_details' => json|null,
    'paid_at' => timestamp|null,
    'created_at' => timestamp,
    'updated_at' => timestamp,
]
```

### Transaction State Machine

```
[Pending] --complete--> [Completed]
    |
    +--cancel--> [Cancelled]
```

**State Transitions:**
- Pending → Completed: When transaction is finalized with payment
- Pending → Cancelled: When staff cancels before completion
- Completed: Terminal state (no further transitions)
- Cancelled: Terminal state (no further transitions)


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

After analyzing all acceptance criteria, I identified several redundancies:
- Properties 5.2 and 5.3 are redundant with the combination of 4.4, 4.5, and 5.1
- The core property is that the total always equals the sum of subtotals (5.1), and modifications/removals trigger recalculation (4.4, 4.5)

The following properties represent the unique, non-redundant validation requirements:

### Core Transaction Properties

**Property 1: Transaction creation with pending status**
*For any* valid customer name (non-whitespace), creating a transaction should result in a new order record with status 'pending' and order_type 'walk_in'
**Validates: Requirements 1.2**

**Property 2: Staff association**
*For any* transaction created by an authenticated staff member, the transaction should be associated with that staff member's user ID
**Validates: Requirements 1.3**

**Property 3: Whitespace name rejection**
*For any* string composed entirely of whitespace characters, attempting to create a transaction should be rejected with a validation error
**Validates: Requirements 1.4**

### Product Selection Properties

**Property 4: Product search filtering**
*For any* search query (name or SKU), all returned products should match the query in either their name or SKU fields
**Validates: Requirements 2.2**

**Property 5: Product addition with default quantity**
*For any* in-stock product added to a transaction, the product should appear in the transaction items with a quantity of 1
**Validates: Requirements 2.3**

### Quantity Management Properties

**Property 6: Positive integer quantity validation**
*For any* quantity input, the system should accept only positive integers and reject zero, negative numbers, and non-integer values
**Validates: Requirements 3.2**

**Property 7: Subtotal calculation**
*For any* transaction item, the subtotal should equal the quantity multiplied by the unit price
**Validates: Requirements 3.4**

### Transaction Items Properties

**Property 8: Item list growth**
*For any* transaction, adding N distinct products should result in a transaction items list of length N
**Validates: Requirements 4.2**

**Property 9: Item display completeness**
*For any* transaction item, the display should include product name, quantity, unit price, and subtotal
**Validates: Requirements 4.3**

**Property 10: Item removal and recalculation**
*For any* transaction with items, removing an item should delete it from the list and update the transaction total to reflect the removal
**Validates: Requirements 4.4**

**Property 11: Quantity modification updates**
*For any* transaction item, modifying the quantity should update both the item subtotal and the transaction total
**Validates: Requirements 4.5**

### Total Calculation Properties

**Property 12: Total equals sum of subtotals**
*For any* transaction, the total amount should equal the sum of all item subtotals
**Validates: Requirements 5.1**

**Property 13: Currency formatting**
*For any* monetary amount displayed, the format should include exactly two decimal places and the appropriate currency symbol
**Validates: Requirements 5.5**

### Transaction Completion Properties

**Property 14: Completion status**
*For any* transaction with at least one item, confirming the transaction should save it with status 'completed'
**Validates: Requirements 6.1**

**Property 15: Timestamp recording**
*For any* confirmed transaction, the system should record a timestamp in the confirmed_at field
**Validates: Requirements 6.2**

**Property 16: Unique reference numbers**
*For any* set of transactions, all transaction reference numbers should be unique
**Validates: Requirements 6.5**

### Inventory Management Properties

**Property 17: Stock reduction**
*For any* completed transaction, each product's stock quantity should be reduced by the purchased quantity
**Validates: Requirements 7.1**

**Property 18: Movement record creation**
*For any* inventory update from a transaction, an inventory movement record should be created with movement_type 'sale'
**Validates: Requirements 7.2, 7.4**

**Property 19: Transaction atomicity**
*For any* transaction, if any inventory update fails, the entire transaction should be rolled back and no changes should persist
**Validates: Requirements 7.3**

**Property 20: Inventory verification before completion**
*For any* transaction, the status should only change to 'completed' after all inventory updates have succeeded
**Validates: Requirements 7.5**

### Receipt Generation Properties

**Property 21: Receipt header completeness**
*For any* completed transaction, the generated receipt should contain customer name, transaction date, staff member name, and transaction reference number
**Validates: Requirements 8.1**

**Property 22: Receipt item details**
*For any* receipt, all purchased items should be included with their names, quantities, unit prices, and subtotals
**Validates: Requirements 8.2**

**Property 23: Receipt total display**
*For any* receipt, the transaction total amount should be displayed
**Validates: Requirements 8.3**

### Transaction Cancellation Properties

**Property 24: Pending transaction cancellation**
*For any* pending transaction, cancelling it should result in the transaction being marked as 'cancelled' without persisting to completed status
**Validates: Requirements 9.1**

**Property 25: Inventory preservation on cancellation**
*For any* cancelled transaction, no inventory quantities should be modified
**Validates: Requirements 9.2**

### Transaction History Properties

**Property 26: History ordering**
*For any* transaction history view, transactions should be ordered by creation date in descending order (newest first)
**Validates: Requirements 10.1**

**Property 27: History display fields**
*For any* transaction in history, the display should include reference number, customer name, date, total amount, and staff member name
**Validates: Requirements 10.2**

**Property 28: Transaction detail completeness**
*For any* selected transaction from history, the detail view should display all transaction items with complete information
**Validates: Requirements 10.3**

**Property 29: History search filtering**
*For any* search query on transaction history, results should match the query in customer name, date range, or transaction reference number
**Validates: Requirements 10.4**


## Error Handling

### Validation Errors

**Input Validation:**
- Customer name: Reject whitespace-only strings, empty strings
- Product selection: Verify product exists and is active
- Quantity: Reject non-positive integers, quantities exceeding stock
- Payment method: Validate against allowed payment methods

**Error Responses:**
- Return 422 Unprocessable Entity with validation error messages
- Display user-friendly error messages in the UI
- Maintain form state to allow correction without data loss

### Business Logic Errors

**Insufficient Stock:**
- Check stock availability before adding items
- Display available quantity to staff
- Prevent transaction completion if stock becomes unavailable

**Transaction State Errors:**
- Prevent modification of completed transactions
- Prevent cancellation of completed transactions
- Validate state transitions

**Inventory Update Failures:**
- Wrap transaction completion in database transaction
- Rollback all changes if any inventory update fails
- Log detailed error information for debugging
- Display clear error message to staff

### System Errors

**Database Errors:**
- Catch and log database exceptions
- Display generic error message to user
- Maintain data integrity through transactions

**Concurrent Access:**
- Use database row locking for inventory updates
- Handle race conditions gracefully
- Retry failed operations where appropriate

### Error Recovery

**Automatic Recovery:**
- Database transactions ensure atomicity
- Failed transactions leave no partial state

**Manual Recovery:**
- Staff can cancel failed transactions
- Transaction history shows all attempts
- Audit trail maintains complete record


## Testing Strategy

### Dual Testing Approach

This feature will employ both unit testing and property-based testing to ensure comprehensive coverage:

- **Unit tests** verify specific examples, edge cases, and error conditions
- **Property tests** verify universal properties that should hold across all inputs
- Together they provide comprehensive coverage: unit tests catch concrete bugs, property tests verify general correctness

### Property-Based Testing

**Framework:** We will use **Pest with Pest Property Testing plugin** for PHP property-based testing.

**Configuration:**
- Each property-based test will run a minimum of 100 iterations
- Tests will use random data generators for products, quantities, prices, and customer names
- Each property-based test will be tagged with a comment referencing the design document property

**Tag Format:**
```php
// Feature: walk-in-transaction, Property 1: Transaction creation with pending status
```

**Key Property Tests:**

1. **Transaction Creation Properties (Properties 1-3)**
   - Generate random customer names and verify pending status
   - Test whitespace rejection across various whitespace combinations
   - Verify staff association with different authenticated users

2. **Calculation Properties (Properties 7, 12)**
   - Generate random quantities and prices
   - Verify subtotal = quantity × price
   - Verify total = sum of all subtotals
   - Test with various numbers of items

3. **Inventory Properties (Properties 17-20)**
   - Generate random transactions with multiple items
   - Verify stock reductions match purchased quantities
   - Test transaction rollback on inventory failures
   - Verify movement records are created correctly

4. **Uniqueness Properties (Property 16)**
   - Generate multiple concurrent transactions
   - Verify all reference numbers are unique

5. **Ordering Properties (Property 26)**
   - Generate transactions with random timestamps
   - Verify history is always ordered by date descending

### Unit Testing

**Test Coverage:**

1. **Controller Tests**
   - Test HTTP request handling
   - Verify middleware protection (staff-only access)
   - Test response formats and redirects
   - Verify validation error handling

2. **Service Tests**
   - Test transaction creation with valid data
   - Test item addition/removal/modification
   - Test total calculation with specific examples
   - Test transaction completion flow
   - Test cancellation logic
   - Test receipt generation
   - Test history retrieval with filters

3. **Integration Tests**
   - Test complete transaction flow from creation to completion
   - Test inventory integration
   - Test concurrent transaction handling
   - Test database transaction rollback scenarios

4. **Edge Case Tests**
   - Empty transaction confirmation attempt
   - Out of stock product addition
   - Quantity exceeding available stock
   - Cancelling completed transaction
   - Modifying completed transaction

### Test Data Generators

**For Property-Based Tests:**

```php
// Customer name generator (valid names)
function validCustomerName(): string

// Whitespace-only string generator
function whitespaceString(): string

// Product generator with stock
function productWithStock(int $minStock = 1): Product

// Quantity generator (positive integers)
function positiveQuantity(int $max = 100): int

// Transaction item generator
function transactionItem(): array

// Price generator
function price(float $min = 1.00, float $max = 1000.00): float
```

### Test Execution

**Continuous Integration:**
- Run all tests on every commit
- Property tests run with 100 iterations in CI
- Fail build on any test failure

**Local Development:**
- Quick test suite for rapid feedback
- Full property test suite before commits
- Coverage reports to identify gaps

### Success Criteria

- All unit tests pass
- All property-based tests pass with 100+ iterations
- Code coverage > 80% for service and controller layers
- No critical bugs in manual testing
- All edge cases handled gracefully

