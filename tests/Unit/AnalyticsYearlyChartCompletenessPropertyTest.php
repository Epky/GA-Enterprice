<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalyticsYearlyChartCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * **Feature: admin-analytics-dashboard, Property 13: Chart data completeness for yearly view**
     * **Validates: Requirements 8.3**
     * 
     * Property: For any year period, the chart data should contain exactly 12 data points 
     * representing each month
     */
    public function test_yearly_chart_contains_all_12_months(): void
    {
        $service = new AnalyticsService();

        // Test with multiple random years to ensure property holds
        for ($i = 0; $i < 5; $i++) {
            // Generate a random year in the past 5 years
            $randomYear = Carbon::now()->subYears(rand(0, 5))->startOfYear();
            
            // Create some random orders in this year (but not for every month)
            $numOrders = rand(1, 8);
            for ($j = 0; $j < $numOrders; $j++) {
                $randomMonth = rand(1, 12);
                $randomDay = rand(1, 28); // Use 28 to avoid month-end issues
                Order::factory()->create([
                    'order_status' => 'completed',
                    'payment_status' => 'paid',
                    'created_at' => $randomYear->copy()->addMonths($randomMonth - 1)->addDays($randomDay - 1),
                    'total_amount' => rand(100, 1000),
                ]);
            }

            // Get chart data for this year
            $chartData = $service->getDailySalesTrend('year', $randomYear, $randomYear->copy()->endOfYear());

            // Property: The number of data points should equal 12 (one for each month)
            $this->assertCount(
                12,
                $chartData['dates'],
                "Chart should have exactly 12 date entries for year {$randomYear->format('Y')}"
            );

            $this->assertCount(
                12,
                $chartData['revenue'],
                "Chart should have exactly 12 revenue entries for year {$randomYear->format('Y')}"
            );

            $this->assertCount(
                12,
                $chartData['orders'],
                "Chart should have exactly 12 order count entries for year {$randomYear->format('Y')}"
            );

            // Property: All arrays should have the same length
            $this->assertEquals(
                count($chartData['dates']),
                count($chartData['revenue']),
                "Dates and revenue arrays should have the same length"
            );

            $this->assertEquals(
                count($chartData['dates']),
                count($chartData['orders']),
                "Dates and orders arrays should have the same length"
            );

            // Property: Months with no data should have zero values
            foreach ($chartData['revenue'] as $revenue) {
                $this->assertIsFloat($revenue, "Revenue should be a float");
                $this->assertGreaterThanOrEqual(0, $revenue, "Revenue should be non-negative");
            }

            foreach ($chartData['orders'] as $orderCount) {
                $this->assertIsInt($orderCount, "Order count should be an integer");
                $this->assertGreaterThanOrEqual(0, $orderCount, "Order count should be non-negative");
            }
        }
    }

    /**
     * Edge case: Test with a year that has no orders at all
     */
    public function test_yearly_chart_with_no_orders_shows_all_months_with_zero(): void
    {
        $service = new AnalyticsService();

        // Use a year far in the future with no orders
        $futureYear = Carbon::now()->addYears(10)->startOfYear();

        // Get chart data for this empty year
        $chartData = $service->getDailySalesTrend('year', $futureYear, $futureYear->copy()->endOfYear());

        // Should still have all 12 months
        $this->assertCount(12, $chartData['dates']);
        $this->assertCount(12, $chartData['revenue']);
        $this->assertCount(12, $chartData['orders']);

        // All values should be zero
        foreach ($chartData['revenue'] as $revenue) {
            $this->assertEquals(0.0, $revenue, "Revenue should be 0.0 for months with no orders");
        }

        foreach ($chartData['orders'] as $orderCount) {
            $this->assertEquals(0, $orderCount, "Order count should be 0 for months with no orders");
        }
    }

    /**
     * Property test: Verify that months are in sequential order
     */
    public function test_yearly_chart_months_are_sequential(): void
    {
        $service = new AnalyticsService();

        // Test with current year
        $currentYear = Carbon::now()->startOfYear();
        $chartData = $service->getDailySalesTrend('year', $currentYear, $currentYear->copy()->endOfYear());

        // Verify months are sequential (each month follows the previous)
        $expectedDate = $currentYear->copy();
        foreach ($chartData['dates'] as $index => $dateLabel) {
            $expectedLabel = $expectedDate->format('M Y');
            $this->assertEquals(
                $expectedLabel,
                $dateLabel,
                "Month at index {$index} should be {$expectedLabel}"
            );
            $expectedDate->addMonth();
        }
    }

    /**
     * Property test: Verify that the year period always returns exactly 12 months
     * regardless of the actual year span
     */
    public function test_yearly_chart_always_returns_12_months(): void
    {
        $service = new AnalyticsService();

        // Test with various year configurations
        $testYears = [
            Carbon::create(2020, 1, 1), // Leap year
            Carbon::create(2021, 1, 1), // Non-leap year
            Carbon::create(2024, 1, 1), // Recent leap year
            Carbon::now()->startOfYear(), // Current year
        ];

        foreach ($testYears as $year) {
            $chartData = $service->getDailySalesTrend('year', $year, $year->copy()->endOfYear());
            
            $this->assertCount(
                12,
                $chartData['dates'],
                "Year {$year->format('Y')} should have exactly 12 months"
            );
        }
    }

    /**
     * Property test: Verify that month labels include both month and year
     */
    public function test_yearly_chart_month_labels_include_year(): void
    {
        $service = new AnalyticsService();

        $testYear = Carbon::create(2023, 1, 1);
        $chartData = $service->getDailySalesTrend('year', $testYear, $testYear->copy()->endOfYear());

        // Each label should contain the year
        foreach ($chartData['dates'] as $dateLabel) {
            $this->assertStringContainsString(
                '2023',
                $dateLabel,
                "Month label should include the year"
            );
        }
    }

    /**
     * Property test: Verify that orders are aggregated correctly by month
     */
    public function test_yearly_chart_aggregates_orders_by_month(): void
    {
        $service = new AnalyticsService();

        $testYear = Carbon::create(2023, 1, 1);

        // Create orders in specific months
        // January: 3 orders
        Order::factory()->count(3)->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::create(2023, 1, 15),
            'total_amount' => 100,
        ]);

        // March: 2 orders
        Order::factory()->count(2)->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'created_at' => Carbon::create(2023, 3, 10),
            'total_amount' => 200,
        ]);

        $chartData = $service->getDailySalesTrend('year', $testYear, $testYear->copy()->endOfYear());

        // January (index 0) should have 3 orders
        $this->assertEquals(3, $chartData['orders'][0], "January should have 3 orders");
        
        // February (index 1) should have 0 orders
        $this->assertEquals(0, $chartData['orders'][1], "February should have 0 orders");
        
        // March (index 2) should have 2 orders
        $this->assertEquals(2, $chartData['orders'][2], "March should have 2 orders");

        // Revenue should be aggregated correctly
        $this->assertEquals(300.0, $chartData['revenue'][0], "January revenue should be 300");
        $this->assertEquals(0.0, $chartData['revenue'][1], "February revenue should be 0");
        $this->assertEquals(400.0, $chartData['revenue'][2], "March revenue should be 400");
    }
}
