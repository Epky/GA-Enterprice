# Design Document: Admin Analytics Dashboard

## Overview

The Admin Analytics Dashboard is a comprehensive business intelligence interface that provides administrators and business owners with real-time insights into sales performance, inventory status, customer behavior, and overall business health. The dashboard aggregates data from orders, payments, products, inventory, and users to present actionable metrics through an intuitive visual interface.

The system will be built as an extension to the existing admin dashboard, leveraging Laravel's Eloquent ORM for data aggregation, Blade templates for rendering, and Chart.js for data visualization. The design emphasizes performance through query optimization, caching strategies, and efficient data aggregation.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Admin Dashboard View                     │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐        │
│  │ Sales Widget │ │ Revenue      │ │ Inventory    │        │
│  │              │ │ Widget       │ │ Widget       │        │
│  └──────────────┘ └──────────────┘ └──────────────┘        │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐        │
│  │ Top Products │ │ Category     │ │ Payment      │        │
│  │ Widget       │ │ Sales Widget │ │ Methods      │        │
│  └──────────────┘ └──────────────┘ └──────────────┘        │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              AdminDashboardController                        │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  - index()                                            │  │
│  │  - getSalesAnalytics()                               │  │
│  │  - getRevenueMetrics()                               │  │
│  │  - getTopProducts()                                  │  │
│  │  - exportAnalytics()                                 │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                  AnalyticsService                            │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  - calculateRevenue($startDate, $endDate)            │  │
│  │  - getOrderMetrics($startDate, $endDate)             │  │
│  │  - getTopSellingProducts($limit, $period)            │  │
│  │  - getSalesByCategory($period)                       │  │
│  │  - getSalesByBrand($period)                          │  │
│  │  - getPaymentMethodDistribution($period)             │  │
│  │  - getChannelComparison($period)                     │  │
│  │  - getProfitMetrics($period)                         │  │
│  │  - getDailySalesTrend($period)                       │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Data Models                               │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐      │
│  │  Order   │ │ Payment  │ │ Product  │ │Inventory │      │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘      │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐                   │
│  │OrderItem │ │ Category │ │  Brand   │                   │
│  └──────────┘ └──────────┘ └──────────┘                   │
└─────────────────────────────────────────────────────────────┘
```

### Component Interaction Flow

1. **Admin accesses dashboard** → Controller receives request with optional time period filter
2. **Controller delegates to AnalyticsService** → Service methods calculate metrics using Eloquent queries
3. **Service queries database** → Optimized queries with joins, aggregations, and indexes
4. **Results cached** → Frequently accessed metrics cached for 5-15 minutes
5. **Data formatted and returned** → Controller passes formatted data to view
6. **View renders widgets** → Blade components render individual analytics widgets
7. **Charts rendered** → Chart.js renders interactive visualizations

## Components and Interfaces

### 1. AnalyticsService

**Purpose:** Centralized service for all analytics calculations and data aggregation.

**Public Methods:**

```php
class AnalyticsService
{
    /**
     * Calculate total revenue for a given period
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array ['total' => float, 'previous_total' => float, 'change_percent' => float]
     */
    public function calculateRevenue(Carbon $startDate, Carbon $endDate): array;

    /**
     * Get order metrics (count, average value, etc.)
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array ['total_orders' => int, 'completed_orders' => int, 'avg_order_value' => float, ...]
     */
    public function getOrderMetrics(Carbon $startDate, Carbon $endDate): array;

    /**
     * Get top selling products
     * @param int $limit
     * @param string $period ('today'|'week'|'month'|'year')
     * @return Collection
     */
    public function getTopSellingProducts(int $limit = 10, string $period = 'month'): Collection;

    /**
     * Get sales breakdown by category
     * @param string $period
     * @return Collection
     */
    public function getSalesByCategory(string $period = 'month'): Collection;

    /**
     * Get sales breakdown by brand
     * @param string $period
     * @return Collection
     */
    public function getSalesByBrand(string $period = 'month'): Collection;

    /**
     * Get payment method distribution
     * @param string $period
     * @return Collection
     */
    public function getPaymentMethodDistribution(string $period = 'month'): Collection;

    /**
     * Compare walk-in vs online sales
     * @param string $period
     * @return array ['walk_in' => array, 'online' => array]
     */
    public function getChannelComparison(string $period = 'month'): array;

    /**
     * Calculate profit metrics
     * @param string $period
     * @return array ['gross_profit' => float, 'profit_margin' => float, 'total_cost' => float]
     */
    public function getProfitMetrics(string $period = 'month'): array;

    /**
     * Get daily sales trend data for charts
     * @param string $period
     * @return array ['dates' => array, 'revenue' => array, 'orders' => array]
     */
    public function getDailySalesTrend(string $period = 'month'): array;

    /**
     * Get customer acquisition metrics
     * @param string $period
     * @return array ['total_customers' => int, 'new_customers' => int, 'growth_rate' => float]
     */
    public function getCustomerMetrics(string $period = 'month'): array;

    /**
     * Get inventory alerts summary
     * @return array ['low_stock_count' => int, 'out_of_stock_count' => int, 'items' => Collection]
     */
    public function getInventoryAlerts(): array;

    /**
     * Get revenue by location
     * @param string $period
     * @return Collection
     */
    public function getRevenueByLocation(string $period = 'month'): Collection;
}
```

### 2. AdminDashboardController (Enhanced)

**Purpose:** Handle HTTP requests for the admin dashboard and coordinate analytics display.

**Public Methods:**

```php
class DashboardController extends Controller
{
    protected AnalyticsService $analyticsService;

    /**
     * Display the admin dashboard with analytics
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View;

    /**
     * Export analytics data to CSV
     * @param Request $request
     * @return StreamedResponse
     */
    public function exportAnalytics(Request $request): StreamedResponse;

    /**
     * Get analytics data via AJAX for dynamic updates
     * @param Request $request
     * @return JsonResponse
     */
    public function getAnalyticsData(Request $request): JsonResponse;
}
```

### 3. Blade Components

**AnalyticsCard Component:**
```php
// resources/views/components/analytics-card.blade.php
// Props: title, value, icon, color, change, changeType
```

**SalesChart Component:**
```php
// resources/views/components/sales-chart.blade.php
// Props: chartData, chartType, title
```

**TopProductsTable Component:**
```php
// resources/views/components/top-products-table.blade.php
// Props: products, period
```

## Data Models

### Existing Models (No Changes Required)

The following existing models will be used without modification:
- `Order` - Contains order data with status, amounts, dates
- `OrderItem` - Contains individual product sales data
- `Payment` - Contains payment method and transaction data
- `Product` - Contains product information and pricing
- `Inventory` - Contains stock levels and locations
- `Category` - Contains product categories
- `Brand` - Contains brand information
- `User` - Contains customer data

### Query Optimization Considerations

**Indexes Required:**
- `orders.created_at` - For date range queries
- `orders.order_status` - For filtering completed orders
- `orders.payment_status` - For filtering paid orders
- `order_items.product_id` - For product sales aggregation
- `payments.payment_method` - For payment distribution
- `inventory.quantity_available` - For low stock queries

These indexes should already exist based on the migration files, but will be verified during implementation.

## Correct
ness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Acceptance Criteria Testing Prework

1.1 WHEN an admin views the dashboard THEN the system SHALL display total revenue for today, this week, this month, and this year
  Thoughts: This is about displaying revenue across multiple time periods. We can test this by generating random orders with various dates and amounts, then verifying that the revenue calculation for each period sums only the orders within that period's date range.
  Testable: yes - property

1.2 WHEN calculating revenue THEN the system SHALL include only orders with order_status 'completed' and payment_status 'paid'
  Thoughts: This is a filtering rule that should apply to all revenue calculations. We can generate random orders with various statuses and verify that only completed+paid orders are included in revenue totals.
  Testable: yes - property

1.3 WHEN displaying revenue THEN the system SHALL format amounts with currency symbol and two decimal places
  Thoughts: This is about string formatting of currency values. We can test that for any revenue amount, the formatted output contains a currency symbol and exactly two decimal places.
  Testable: yes - property

1.4 WHEN comparing periods THEN the system SHALL display percentage change compared to previous period
  Thoughts: This is a calculation that should work for any two periods. We can test that the percentage change formula is correct: ((current - previous) / previous) * 100
  Testable: yes - property

1.5 WHERE a time period filter is selected THEN the system SHALL update all revenue metrics to reflect the selected period
  Thoughts: This is about ensuring consistency across all metrics when a filter is applied. This is more of an integration concern than a unit-testable property.
  Testable: no

2.1 WHEN an admin views the dashboard THEN the system SHALL display total order count for today, this week, this month, and this year
  Thoughts: Similar to 1.1, this is about counting orders across time periods. We can test with random orders and verify counts match the expected number for each period.
  Testable: yes - property

2.2 WHEN counting orders THEN the system SHALL include orders with order_status 'completed', 'pending', and 'processing'
  Thoughts: This is a filtering rule. We can generate orders with various statuses and verify only the specified statuses are counted.
  Testable: yes - property

2.3 WHEN counting orders THEN the system SHALL exclude orders with order_status 'cancelled'
  Thoughts: This is the inverse of 2.2. We can verify that cancelled orders never appear in counts.
  Testable: yes - property

2.4 WHEN displaying order counts THEN the system SHALL show breakdown by order_type (walk-in vs online)
  Thoughts: This is about grouping and counting. We can test that the sum of walk-in + online counts equals total count.
  Testable: yes - property

2.5 WHERE a time period filter is selected THEN the system SHALL update order counts to reflect the selected period
  Thoughts: Same as 1.5, this is an integration concern.
  Testable: no

3.1 WHEN an admin views the dashboard THEN the system SHALL calculate and display average order value (AOV)
  Thoughts: This is a calculation that should work for any set of orders. We can test the formula: total_revenue / order_count.
  Testable: yes - property

3.2 WHEN calculating AOV THEN the system SHALL divide total revenue by number of completed orders
  Thoughts: This is the same as 3.1, just more specific about the formula.
  Testable: yes - property

3.3 WHEN no completed orders exist THEN the system SHALL display AOV as zero
  Thoughts: This is an edge case for division by zero. We should test that empty order sets return 0.
  Testable: edge case

3.4 WHEN displaying AOV THEN the system SHALL format the value with currency symbol and two decimal places
  Thoughts: Same formatting rule as 1.3.
  Testable: yes - property

3.5 WHERE a time period filter is selected THEN the system SHALL recalculate AOV for the selected period
  Thoughts: Integration concern, same as 1.5.
  Testable: no

4.1 WHEN an admin views the dashboard THEN the system SHALL display the top 10 selling products by quantity sold
  Thoughts: This is about sorting and limiting results. We can test that results are ordered by quantity descending and limited to 10.
  Testable: yes - property

4.2 WHEN calculating top products THEN the system SHALL sum quantities from order_items where order status is 'completed'
  Thoughts: This is about aggregation with filtering. We can test that quantities are summed correctly and only from completed orders.
  Testable: yes - property

4.3 WHEN displaying top products THEN the system SHALL show product name, total quantity sold, and total revenue generated
  Thoughts: This is about ensuring required fields are present in output. We can test that all three fields exist for each product.
  Testable: yes - property

4.4 WHEN displaying top products THEN the system SHALL order results by quantity sold in descending order
  Thoughts: This is about sort order. We can test that each item has quantity >= next item's quantity.
  Testable: yes - property

4.5 WHERE a time period filter is selected THEN the system SHALL recalculate top products for the selected period
  Thoughts: Integration concern.
  Testable: no

5.1 WHEN an admin views the dashboard THEN the system SHALL display revenue breakdown by product category
  Thoughts: This is about grouping and summing. We can test that category revenues sum to total revenue.
  Testable: yes - property

5.2 WHEN calculating category sales THEN the system SHALL sum revenue from completed orders grouped by product category
  Thoughts: This is about aggregation logic. We can test with random orders and verify grouping is correct.
  Testable: yes - property

5.3 WHEN displaying category sales THEN the system SHALL show category name, total revenue, and percentage of total sales
  Thoughts: This is about required fields and percentage calculation. We can test that percentages sum to 100%.
  Testable: yes - property

5.4 WHEN displaying category sales THEN the system SHALL order results by revenue in descending order
  Thoughts: Sort order property, same as 4.4.
  Testable: yes - property

5.5 WHERE a time period filter is selected THEN the system SHALL recalculate category sales for the selected period
  Thoughts: Integration concern.
  Testable: no

6.1-6.5: Similar patterns to 5.1-5.5 for brands
  Testable: yes - property (for 6.1-6.4), no (for 6.5)

7.1-7.5: Similar patterns for payment methods
  Testable: yes - property (for 7.1-7.4), no (for 7.5)

8.1 WHEN an admin views the dashboard THEN the system SHALL display a line chart showing daily revenue for the selected period
  Thoughts: This is about data structure for charting. We can test that the output contains arrays of dates and corresponding revenue values.
  Testable: yes - property

8.2 WHEN the selected period is "this month" THEN the system SHALL show daily data points for each day of the month
  Thoughts: This is about ensuring complete date coverage. We can test that all days in the month are present.
  Testable: yes - property

8.3 WHEN the selected period is "this year" THEN the system SHALL show monthly data points for each month
  Thoughts: Similar to 8.2 but for months.
  Testable: yes - property

8.4 WHEN displaying the chart THEN the system SHALL include both revenue and order count as separate lines
  Thoughts: This is about data structure. We can test that both datasets are present.
  Testable: yes - property

8.5 WHERE no data exists for a date THEN the system SHALL display zero for that date
  Thoughts: This is an edge case for missing data.
  Testable: edge case

9.1 WHEN an admin views the dashboard THEN the system SHALL display count of low stock items
  Thoughts: This is a counting operation with a filter. We can test with random inventory records.
  Testable: yes - property

9.2 WHEN counting low stock items THEN the system SHALL include inventory records where quantity_available is less than or equal to reorder_level
  Thoughts: This is the filter logic. We can test that the condition is applied correctly.
  Testable: yes - property

9.3 WHEN displaying low stock alerts THEN the system SHALL show product name, current quantity, and reorder level
  Thoughts: Required fields property.
  Testable: yes - property

9.4 WHEN displaying low stock alerts THEN the system SHALL order by severity (lowest stock percentage first)
  Thoughts: Sort order based on calculation.
  Testable: yes - property

9.5 WHEN an admin clicks on low stock count THEN the system SHALL navigate to the detailed inventory alerts page
  Thoughts: This is a UI navigation concern, not a data property.
  Testable: no

10.1-10.5: Customer metrics - similar patterns
  Testable: yes - property (for 10.1-10.4), no (for 10.5)

11.1-11.5: Channel comparison - similar patterns
  Testable: yes - property (for 11.1-11.4), no (for 11.5)

12.1 WHEN an admin clicks export button THEN the system SHALL generate a downloadable report file
  Thoughts: This is about file generation. We can test that a file is created with correct format.
  Testable: yes - example

12.2 WHEN generating export THEN the system SHALL include all visible analytics data for the selected time period
  Thoughts: This is about data completeness. We can test that exported data matches displayed data.
  Testable: yes - property

12.3 WHEN generating export THEN the system SHALL support CSV format
  Thoughts: This is about file format validation. We can test that output is valid CSV.
  Testable: yes - property

12.4 WHEN generating export THEN the system SHALL include headers for all data columns
  Thoughts: This is about CSV structure. We can test that first row contains headers.
  Testable: yes - property

12.5 WHEN export is complete THEN the system SHALL trigger browser download with filename including date range
  Thoughts: This is a browser interaction, not easily unit testable.
  Testable: no

13.1-13.5: Location revenue - similar patterns to category/brand
  Testable: yes - property (for 13.1-13.4), no (for 13.5)

14.1 WHEN an admin views the dashboard THEN the system SHALL calculate and display gross profit
  Thoughts: This is a calculation: revenue - cost. We can test the formula.
  Testable: yes - property

14.2 WHEN calculating gross profit THEN the system SHALL subtract total cost_price from total revenue for completed orders
  Thoughts: Same as 14.1, more specific.
  Testable: yes - property

14.3 WHEN displaying profit THEN the system SHALL show profit amount and profit margin percentage
  Thoughts: Required fields and percentage calculation.
  Testable: yes - property

14.4 WHEN cost_price is not available for a product THEN the system SHALL exclude that product from profit calculations
  Thoughts: This is about handling null values. We can test that null costs don't break calculations.
  Testable: yes - property

14.5 WHERE a time period filter is selected THEN the system SHALL recalculate profit metrics for the selected period
  Thoughts: Integration concern.
  Testable: no

15.1-15.5: Performance requirements
  Thoughts: These are performance/infrastructure concerns, not functional properties.
  Testable: no

### Property Reflection

After reviewing all properties, I've identified the following redundancies:

1. **Revenue calculation properties (1.1, 1.2)** - These can be combined into one property that tests revenue calculation includes only completed+paid orders within date range
2. **Order counting properties (2.2, 2.3)** - These are inverse of each other and can be combined into one property about status filtering
3. **AOV calculation properties (3.1, 3.2)** - These are the same property stated differently
4. **Formatting properties (1.3, 3.4)** - These are the same formatting rule applied to different values, can be one property
5. **Sort order properties (4.4, 5.4, 9.4)** - These follow the same descending sort pattern, can be generalized
6. **Required fields properties (4.3, 5.3, 9.3)** - Similar pattern of ensuring fields are present
7. **Percentage sum properties (5.3, 7.4)** - Both test that percentages sum to 100%

After consolidation, we'll focus on unique, high-value properties that provide meaningful correctness guarantees.

### Correctness Properties

**Property 1: Revenue calculation includes only valid orders**
*For any* date range, the calculated revenue should equal the sum of total_amount from orders where order_status is 'completed' AND payment_status is 'paid' AND created_at is within the date range
**Validates: Requirements 1.1, 1.2**

**Property 2: Currency formatting consistency**
*For any* monetary value, the formatted output should contain a currency symbol and exactly two decimal places
**Validates: Requirements 1.3, 3.4**

**Property 3: Percentage change calculation accuracy**
*For any* two numeric values representing current and previous period metrics, the percentage change should equal ((current - previous) / previous) * 100, or 0 when previous is 0
**Validates: Requirements 1.4**

**Property 4: Order counting excludes cancelled orders**
*For any* date range, the order count should equal the number of orders where order_status is NOT 'cancelled' AND created_at is within the date range
**Validates: Requirements 2.2, 2.3**

**Property 5: Order type breakdown sums to total**
*For any* date range, the sum of walk-in order count and online order count should equal the total order count
**Validates: Requirements 2.4**

**Property 6: Average order value calculation**
*For any* set of completed orders, if the count is greater than 0, AOV should equal total_revenue / order_count, otherwise AOV should be 0
**Validates: Requirements 3.1, 3.2, 3.3**

**Property 7: Top products ordered by quantity descending**
*For any* list of top selling products, each product's quantity_sold should be greater than or equal to the next product's quantity_sold
**Validates: Requirements 4.4**

**Property 8: Top products aggregation accuracy**
*For any* product in the top products list, the total_quantity_sold should equal the sum of quantities from order_items where product_id matches and order status is 'completed'
**Validates: Requirements 4.2**

**Property 9: Category revenue sums to total revenue**
*For any* period, the sum of revenue across all categories should equal the total revenue for that period
**Validates: Requirements 5.1, 5.2**

**Property 10: Percentage distribution sums to 100**
*For any* breakdown by category, brand, or payment method, the sum of all percentage values should equal 100% (within rounding tolerance of 0.1%)
**Validates: Requirements 5.3, 7.4**

**Property 11: Descending sort order maintained**
*For any* list sorted by a numeric value (revenue, quantity, count), each item's sort value should be greater than or equal to the next item's sort value
**Validates: Requirements 5.4, 6.4, 7.4**

**Property 12: Chart data completeness for monthly view**
*For any* month period, the chart data should contain exactly the number of days in that month, with each day represented even if revenue is 0
**Validates: Requirements 8.2, 8.5**

**Property 13: Chart data completeness for yearly view**
*For any* year period, the chart data should contain exactly 12 data points representing each month
**Validates: Requirements 8.3**

**Property 14: Low stock filter accuracy**
*For any* inventory record in the low stock alerts, the quantity_available should be less than or equal to the reorder_level
**Validates: Requirements 9.2**

**Property 15: CSV export data integrity**
*For any* exported CSV file, the number of data rows should match the number of items in the source data, and each row should have the same number of columns as the header row
**Validates: Requirements 12.2, 12.3, 12.4**

**Property 16: Profit calculation accuracy**
*For any* set of completed orders, gross profit should equal the sum of (sale_price - cost_price) * quantity for all order items where cost_price is not null
**Validates: Requirements 14.1, 14.2, 14.4**

**Property 17: Profit margin percentage calculation**
*For any* profit calculation where revenue is greater than 0, profit_margin_percentage should equal (gross_profit / total_revenue) * 100
**Validates: Requirements 14.3**

## Error Handling

### Expected Error Scenarios

1. **No Data Available**
   - Scenario: Admin views analytics for a period with no orders
   - Handling: Display "No data available" message with helpful context
   - User Experience: Show zero values with clear indication that no transactions occurred

2. **Database Connection Failure**
   - Scenario: Database becomes unavailable during analytics calculation
   - Handling: Catch database exceptions, log error, display user-friendly error message
   - User Experience: Show error banner with retry option

3. **Invalid Date Range**
   - Scenario: User selects end date before start date in custom range
   - Handling: Validate date inputs, show validation error
   - User Experience: Highlight invalid fields with clear error message

4. **Missing Product Data**
   - Scenario: Order items reference deleted products
   - Handling: Handle null product relationships gracefully
   - User Experience: Show "Unknown Product" or skip in aggregations

5. **Division by Zero**
   - Scenario: Calculating percentages or averages with zero denominator
   - Handling: Check for zero before division, return 0 or null as appropriate
   - User Experience: Display 0% or "N/A" instead of error

6. **Export File Generation Failure**
   - Scenario: Server cannot write export file due to permissions or disk space
   - Handling: Catch file system exceptions, log error, notify user
   - User Experience: Show error message with suggestion to contact administrator

7. **Slow Query Performance**
   - Scenario: Analytics queries take too long on large datasets
   - Handling: Implement query timeouts, use caching, optimize queries
   - User Experience: Show loading indicators, consider pagination

### Error Response Format

```php
// For AJAX requests
{
    "success": false,
    "error": {
        "message": "User-friendly error message",
        "code": "ERROR_CODE",
        "details": "Technical details for debugging"
    }
}

// For page loads
// Display error banner with:
// - Clear error message
// - Suggested action (retry, contact support, etc.)
// - Fallback to showing last successful data if cached
```

## Testing Strategy

### Unit Testing

**AnalyticsService Unit Tests:**
- Test each calculation method with known input data
- Test edge cases (empty datasets, null values, zero amounts)
- Test date range filtering logic
- Test aggregation accuracy
- Test percentage calculations
- Test sorting and limiting logic

**Controller Unit Tests:**
- Test request validation
- Test response formatting
- Test error handling
- Test export file generation

**Example Unit Tests:**
```php
// Test revenue calculation with mixed order statuses
public function test_revenue_includes_only_completed_paid_orders()
{
    // Create orders with various statuses
    // Assert only completed+paid orders are included in revenue
}

// Test AOV with zero orders
public function test_aov_returns_zero_when_no_orders()
{
    // Assert AOV is 0 when no completed orders exist
}

// Test percentage calculation
public function test_percentage_change_calculation()
{
    // Test with various current/previous values
    // Assert formula is correct
}
```

### Property-Based Testing

**Property Testing Framework:** Use Pest PHP with property testing plugin or implement custom generators

**Test Generators:**
```php
// Order generator with random statuses, amounts, dates
function generateRandomOrder(): Order

// Product generator with random prices, categories
function generateRandomProduct(): Product

// Date range generator
function generateRandomDateRange(): array
```

**Property Tests:**
- Revenue calculation properties (Properties 1, 3)
- Order counting properties (Properties 4, 5)
- AOV calculation property (Property 6)
- Sorting properties (Properties 7, 11)
- Aggregation properties (Properties 8, 9)
- Percentage properties (Property 10)
- Chart data properties (Properties 12, 13)
- Profit calculation properties (Properties 16, 17)

**Example Property Test:**
```php
test('revenue only includes completed and paid orders', function () {
    // Generate 100 random orders with various statuses
    $orders = generateRandomOrders(100);
    
    // Calculate expected revenue (manual sum of completed+paid)
    $expected = $orders->filter(fn($o) => 
        $o->order_status === 'completed' && 
        $o->payment_status === 'paid'
    )->sum('total_amount');
    
    // Get actual revenue from service
    $actual = $this->analyticsService->calculateRevenue(
        now()->subMonth(), 
        now()
    )['total'];
    
    // Assert they match
    expect($actual)->toBe($expected);
})->repeat(100); // Run 100 times with different random data
```

### Integration Testing

- Test full dashboard page load with real database
- Test time period filter changes update all widgets
- Test export functionality end-to-end
- Test AJAX data refresh
- Test with large datasets for performance
- Test caching behavior

### Performance Testing

- Benchmark analytics queries with 10k, 100k, 1M orders
- Test cache hit rates
- Test page load times
- Identify slow queries and optimize
- Test concurrent user access

## Implementation Notes

### Caching Strategy

```php
// Cache key format: analytics:{metric}:{period}:{date}
// Example: analytics:revenue:month:2024-11
// TTL: 15 minutes for current period, 24 hours for past periods

Cache::remember("analytics:revenue:month:{$month}", 900, function() {
    return $this->calculateRevenue($startDate, $endDate);
});
```

### Query Optimization

- Use eager loading for relationships
- Use database aggregation functions (SUM, COUNT, AVG)
- Limit result sets appropriately
- Use indexes on frequently queried columns
- Consider database views for complex aggregations

### Frontend Considerations

- Use Chart.js for interactive charts
- Implement loading states for async data
- Use Alpine.js or vanilla JS for time period filter
- Responsive design for mobile viewing
- Export button with loading indicator

### Security Considerations

- Ensure only admin users can access analytics
- Validate and sanitize all user inputs
- Prevent SQL injection through parameterized queries
- Rate limit export functionality
- Log access to sensitive analytics data
