x# Implementation Plan: Admin Analytics Dashboard

-   [x] 1. Create AnalyticsService foundation

    -   Create `app/Services/AnalyticsService.php` with class structure and constructor
    -   Implement helper methods for date range parsing (today, week, month, year)
    -   Implement method to get previous period dates for comparison
    -   Add caching helper methods
    -   _Requirements: 1.1, 1.5, 15.1-15.5_

-   [x] 1.1 Write property test for date range parsing

    -   **Property 1: Date range boundaries**
    -   **Validates: Requirements 1.1**

-   [x] 2. Implement revenue calculation methods

    -   [x] 2.1 Implement `calculateRevenue()` method with date filtering

        -   Query orders with completed status and paid payment_status
        -   Filter by date range
        -   Sum total_amount
        -   Calculate previous period revenue for comparison
        -   Calculate percentage change
        -   _Requirements: 1.1, 1.2, 1.4_

    -   [x] 2.2 Write property test for revenue calculation

        -   **Property 1: Revenue calculation includes only valid orders**
        -   **Validates: Requirements 1.1, 1.2**

    -   [x] 2.3 Write property test for percentage change

        -   **Property 3: Percentage change calculation accuracy**
        -   **Validates: Requirements 1.4**

    -   [x] 2.4 Implement revenue formatting helper

        -   Format currency with symbol and two decimal places
        -   _Requirements: 1.3_

    -   [x] 2.5 Write property test for currency formatting

        -   **Property 2: Currency formatting consistency**
        -   **Validates: Requirements 1.3, 3.4**

-   [x] 3. Implement order metrics methods

    -   [x] 3.1 Implement `getOrderMetrics()` method

        -   Count total orders (excluding cancelled)
        -   Count by order_type (walk-in vs online)
        -   Calculate average order value
        -   Handle zero orders edge case
        -   _Requirements: 2.1, 2.2, 2.3, 2.4, 3.1, 3.2, 3.3_

    -   [x] 3.2 Write property test for order counting

        -   **Property 4: Order counting excludes cancelled orders**
        -   **Validates: Requirements 2.2, 2.3**

    -   [x] 3.3 Write property test for order type breakdown

        -   **Property 5: Order type breakdown sums to total**
        -   **Validates: Requirements 2.4**

    -   [x] 3.4 Write property test for AOV calculation

        -   **Property 6: Average order value calculation**
        -   **Validates: Requirements 3.1, 3.2, 3.3**

-   [x] 4. Implement product analytics methods

    -   [x] 4.1 Implement `getTopSellingProducts()` method

        -   Join order_items with orders and products
        -   Filter by completed orders
        -   Group by product_id
        -   Sum quantities and revenue
        -   Order by quantity descending
        -   Limit to specified number (default 10)
        -   _Requirements: 4.1, 4.2, 4.3, 4.4_

    -   [x] 4.2 Write property test for top products sorting

        -   **Property 7: Top products ordered by quantity descending**
        -   **Validates: Requirements 4.4**

    -   [x] 4.3 Write property test for top products aggregation

        -   **Property 8: Top products aggregation accuracy**
        -   **Validates: Requirements 4.2**

-   [x] 5. Implement category and brand analytics

    -   [x] 5.1 Implement `getSalesByCategory()` method

        -   Join orders with order_items and products
        -   Group by category_id
        -   Sum revenue per category
        -   Calculate percentage of total
        -   Order by revenue descending
        -   _Requirements: 5.1, 5.2, 5.3, 5.4_

    -   [x] 5.2 Implement `getSalesByBrand()` method

        -   Similar to category but group by brand_id
        -   _Requirements: 6.1, 6.2, 6.3, 6.4_

    -   [x] 5.3 Write property test for category revenue sum

        -   **Property 9: Category revenue sums to total revenue**
        -   **Validates: Requirements 5.1, 5.2**

    -   [x] 5.4 Write property test for percentage distribution

        -   **Property 10: Percentage distribution sums to 100**
        -   **Validates: Requirements 5.3, 7.4**

    -   [x] 5.5 Write property test for descending sort order

        -   **Property 11: Descending sort order maintained**
        -   **Validates: Requirements 5.4, 6.4**

-   [x] 6. Implement payment and channel analytics

    -   [x] 6.1 Implement `getPaymentMethodDistribution()` method

        -   Join orders with payments
        -   Group by payment_method
        -   Count orders and sum revenue per method
        -   Calculate percentages
        -   _Requirements: 7.1, 7.2, 7.3, 7.4_

    -   [x] 6.2 Implement `getChannelComparison()` method

        -   Group orders by order_type
        -   Calculate revenue and count for walk-in vs online
        -   Calculate percentages
        -   _Requirements: 11.1, 11.2, 11.3, 11.4_

-   [x] 7. Implement chart data methods

    -   [x] 7.1 Implement `getDailySalesTrend()` method

        -   Generate date array for period (daily for month, monthly for year)
        -   Query revenue and order count per date
        -   Fill missing dates with zero
        -   Return arrays for Chart.js consumption
        -   _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

    -   [x] 7.2 Write property test for monthly chart completeness

        -   **Property 12: Chart data completeness for monthly view**
        -   **Validates: Requirements 8.2, 8.5**

    -   [x] 7.3 Write property test for yearly chart completeness

        -   **Property 13: Chart data completeness for yearly view**
        -   **Validates: Requirements 8.3**

-   [x] 8. Implement inventory and customer analytics

    -   [x] 8.1 Implement `getInventoryAlerts()` method

        -   Query inventory where quantity_available <= reorder_level
        -   Calculate stock percentage
        -   Order by severity (lowest percentage first)
        -   Include product details
        -   _Requirements: 9.1, 9.2, 9.3, 9.4_

    -   [x] 8.2 Write property test for low stock filter

        -   **Property 14: Low stock filter accuracy**
        -   **Validates: Requirements 9.2**

    -   [x] 8.3 Implement `getCustomerMetrics()` method

        -   Count total customers (role = 'customer')
        -   Count new customers by period
        -   Calculate growth rate
        -   _Requirements: 10.1, 10.2, 10.3, 10.4_

    -   [x] 8.4 Implement `getRevenueByLocation()` method

        -   Join orders with inventory locations
        -   Group by location
        -   Sum revenue per location
        -   _Requirements: 13.1, 13.2, 13.3, 13.4_

-   [x] 9. Implement profit analytics

    -   [x] 9.1 Implement `getProfitMetrics()` method

        -   Query order_items with products
        -   Calculate (sale_price - cost_price) \* quantity
        -   Filter out products with null cost_price
        -   Sum to get gross profit
        -   Calculate profit margin percentage
        -   _Requirements: 14.1, 14.2, 14.3, 14.4_

    -   [x] 9.2 Write property test for profit calculation

        -   **Property 16: Profit calculation accuracy**
        -   **Validates: Requirements 14.1, 14.2, 14.4**

    -   [x] 9.3 Write property test for profit margin

        -   **Property 17: Profit margin percentage calculation**
        -   **Validates: Requirements 14.3**

-   [x] 10. Checkpoint - Ensure all AnalyticsService tests pass

    -   Ensure all tests pass, ask the user if questions arise.

-   [x] 11. Update AdminDashboardController

    -   [x] 11.1 Inject AnalyticsService into controller

        -   Add constructor dependency injection
        -   Store service instance
        -   _Requirements: All_

    -   [x] 11.2 Update `index()` method to fetch analytics

        -   Get time period from request (default to 'month')
        -   Call all AnalyticsService methods
        -   Pass data to view
        -   Handle errors gracefully
        -   _Requirements: All_

    -   [x] 11.3 Implement `exportAnalytics()` method

        -   Get analytics data for selected period
        -   Generate CSV with headers
        -   Stream response with proper headers
        -   Include date range in filename
        -   _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

    -   [x] 11.4 Write property test for CSV export

        -   **Property 15: CSV export data integrity**
        -   **Validates: Requirements 12.2, 12.3, 12.4**

    -   [x] 11.5 Implement `getAnalyticsData()` AJAX endpoint

        -   Accept period parameter
        -   Return JSON response with analytics
        -   Handle errors with proper JSON error format
        -   _Requirements: All_

-   [x] 11.6 Write unit tests for controller methods

    -   Test request validation
    -   Test error handling
    -   Test response formatting

-   [x] 12. Create Blade components

    -   [x] 12.1 Create `analytics-card.blade.php` component

        -   Props: title, value, icon, color, change, changeType
        -   Display metric card with icon and change indicator
        -   _Requirements: 1.1, 2.1, 3.1, 10.1_

    -   [x] 12.2 Create `sales-chart.blade.php` component

        -   Props: chartData, chartType, title
        -   Integrate Chart.js
        -   Render line/bar chart
        -   _Requirements: 8.1, 8.2, 8.3, 8.4_

    -   [x] 12.3 Create `top-products-table.blade.php` component

        -   Props: products, period
        -   Display table with product name, quantity, revenue
        -   _Requirements: 4.1, 4.3_

    -   [x] 12.4 Create `category-breakdown.blade.php` component

        -   Props: categories
        -   Display category sales with percentages
        -   _Requirements: 5.1, 5.3_

    -   [x] 12.5 Create `payment-methods-chart.blade.php` component

        -   Props: paymentData
        -   Display pie/doughnut chart
        -   _Requirements: 7.1, 7.3_

-   [x] 13. Update admin dashboard view

    -   [x] 13.1 Add time period filter dropdown

        -   Options: Today, This Week, This Month, This Year, Custom Range
        -   Use Alpine.js or vanilla JS for interactivity
        -   Submit form or AJAX request on change
        -   _Requirements: 1.5, 2.5, 3.5, 4.5, 5.5_

    -   [x] 13.2 Add revenue metrics section

        -   Use analytics-card components
        -   Display total revenue, order count, AOV
        -   Show percentage changes
        -   _Requirements: 1.1, 1.3, 1.4, 2.1, 3.1_

    -   [x] 13.3 Add sales trend chart section

        -   Use sales-chart component
        -   Display daily/monthly revenue trend
        -   _Requirements: 8.1, 8.2, 8.3, 8.4_

    -   [x] 13.4 Add top products section

        -   Use top-products-table component
        -   Display top 10 selling products
        -   _Requirements: 4.1, 4.3, 4.4_

    -   [x] 13.5 Add category and brand breakdown section

        -   Use category-breakdown component
        -   Display sales by category and brand
        -   _Requirements: 5.1, 5.3, 6.1, 6.3_

    -   [x] 13.6 Add payment methods section

        -   Use payment-methods-chart component
        -   Display payment distribution
        -   _Requirements: 7.1, 7.3, 7.4_

    -   [x] 13.7 Add channel comparison section

        -   Display walk-in vs online metrics
        -   Use analytics-card components
        -   _Requirements: 11.1, 11.2, 11.3_

    -   [x] 13.8 Add inventory alerts widget

        -   Display low stock count
        -   Link to detailed alerts page
        -   _Requirements: 9.1, 9.2, 9.5_

    -   [x] 13.9 Add customer metrics section

        -   Display total and new customers
        -   Show growth rate
        -   _Requirements: 10.1, 10.2, 10.3, 10.4_

    -   [x] 13.10 Add profit metrics section

        -   Display gross profit and margin
        -   Use analytics-card components
        -   _Requirements: 14.1, 14.3_

    -   [x] 13.11 Add export button

        -   Button to trigger CSV export
        -   Show loading state during export
        -   _Requirements: 12.1, 12.5_

-   [x] 14. Add Chart.js integration

    -   [x] 14.1 Install Chart.js via npm

        -   Run `npm install chart.js`
        -   Import in resources/js/app.js
        -   _Requirements: 8.1_

    -   [x] 14.2 Create chart initialization JavaScript

        -   Create resources/js/analytics-charts.js
        -   Initialize line chart for sales trend
        -   Initialize pie chart for payment methods
        -   Handle responsive sizing
        -   _Requirements: 8.1, 7.1_

-   [x] 15. Implement caching

    -   [x] 15.1 Add cache wrapper in AnalyticsService

        -   Cache key format: analytics:{metric}:{period}:{date}
        -   TTL: 15 minutes for current period
        -   TTL: 24 hours for past periods
        -   _Requirements: 15.1, 15.2, 15.5_

    -   [x] 15.2 Add cache clearing mechanism

        -   Clear relevant caches when new orders are created
        -   Clear caches when orders are updated
        -   _Requirements: 15.1_

-   [ ] 16. Add database indexes (if not exist)

    -   [x] 16.1 Check and add index on orders.created_at

        -   Create migration if needed
        -   _Requirements: 15.2_

    -   [x] 16.2 Check and add index on orders.order_status

        -   Create migration if needed
        -   _Requirements: 15.2_

    -   [x] 16.3 Check and add index on orders.payment_status

        -   Create migration if needed
        -   _Requirements: 15.2_

-   [x] 17. Add routes

    -   [x] 17.1 Update routes/admin.php

        -   Add route for analytics export
        -   Add route for AJAX analytics data
        -   _Requirements: 12.1, All_

-   [x] 18. Implement error handling

    -   [x] 18.1 Add try-catch blocks in AnalyticsService

        -   Handle database exceptions
        -   Log errors
        -   Return empty/default values gracefully
        -   _Requirements: All_

    -   [x] 18.2 Add error handling in controller

        -   Catch service exceptions
        -   Display user-friendly error messages
        -   Return JSON errors for AJAX requests
        -   _Requirements: All_

    -   [x] 18.3 Add validation for custom date ranges

        -   Validate start date < end date
        -   Validate date format
        -   Show validation errors
        -   _Requirements: 1.5_

-   [x] 19. Write integration tests

    -   Test full dashboard page load
    -   Test time period filter changes
    -   Test export functionality
    -   Test AJAX data refresh
    -   Test with various data scenarios

-   [x] 20. Final checkpoint - Ensure all tests pass

    -   Ensure all tests pass, ask the user if questions arise.

-   [x] 21. Performance optimization

    -   [x] 21.1 Run performance benchmarks

        -   Test with 10k, 100k orders
        -   Identify slow queries
        -   _Requirements: 15.1_

    -   [x] 21.2 Optimize slow queries

        -   Add missing indexes
        -   Refactor complex queries
        -   Use database views if needed
        -   _Requirements: 15.2_

    -   [x] 21.3 Test cache effectiveness

        -   Verify cache hit rates
        -   Adjust TTL if needed
        -   _Requirements: 15.5_

-   [x] 22. Documentation and cleanup

    -   [x] 22.1 Add PHPDoc comments to all methods

        -   Document parameters and return types
        -   Add usage examples
        -   _Requirements: All_

    -   [x] 22.2 Update README with analytics features

        -   Document available metrics
        -   Document time period options
        -   Document export functionality
        -   _Requirements: All_

    -   [x] 22.3 Create admin user guide

        -   How to use analytics dashboard
        -   How to interpret metrics
        -   How to export data
        -   _Requirements: All_
