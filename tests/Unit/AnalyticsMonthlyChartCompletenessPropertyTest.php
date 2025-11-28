<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalyticsMonthlyChartCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * **Feature: admin-analytics-dashboard, Property 12: Chart data completeness for monthly view**
     * **Validates: Requirements 8.2, 8.5**
     * 
     * Property: For any month period, the chart data should contain exactly the number 
     * of days in that month, with each day represented even if revenue is 0
     */
    public function test_monthly_chart_contains_all_days_of_month(): void
    {
        $service = new AnalyticsService();

        // Test with multiple random months to ensure property holds
        for ($i = 0; $i < 10; $i++) {
            // Generate a random month in the past 2 years
            $randomMonth = Carbon::now()->subMonths(rand(0, 24))->startOfMonth();
            $daysInMonth = $randomMonth->daysInMonth;
            
            // Create some random orders in this month (but not for every day)
            $numOrders = rand(1, min(10, $daysInMonth));
            for ($j = 0; $j < $numOrders; $j++) {
                $randomDay = rand(1, $daysInMonth);
                Order::factory()->create([
                    'order_status' => 'completed',
                    'payment_status' => 'paid',
                    'created_at' => $randomMonth->copy()->addDays($randomDay - 1),
                    'total_amount' => rand(100, 1000),
                ]);
            }

            // Get chart data for this month
            $chartData = $service->getDailySalesTrend('month', $randomMonth, $randomMonth->copy()->endOfMonth());

            // Property: The number of data points should equal the number of days in the month
            $this->assertCount(
                $daysInMonth,
                $chartData['dates'],
                "Chart should have exactly {$daysInMonth} date entries for month {$randomMonth->format('Y-m')}"
            );

            $this->assertCount(
                $daysInMonth,
                $chartData['revenue'],
                "Chart should have exactly {$daysInMonth} revenue entries for month {$randomMonth->format('Y-m')}"
            );

            $this->assertCount(
                $daysInMonth,
                $chartData['orders'],
                "Chart should have exactly {$daysInMonth} order count entries for month {$randomMonth->format('Y-m')}"
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

            // Property: Days with no data should have zero values
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
     * Edge case: Test with a month that has no orders at all
     */
    public function test_monthly_chart_with_no_orders_shows_all_days_with_zero(): void
    {
        $service = new AnalyticsService();

        // Use a month far in the future with no orders
        $futureMonth = Carbon::now()->addYears(5)->startOfMonth();
        $daysInMonth = $futureMonth->daysInMonth;

        // Get chart data for this empty month
        $chartData = $service->getDailySalesTrend('month', $futureMonth, $futureMonth->copy()->endOfMonth());

        // Should still have all days
        $this->assertCount($daysInMonth, $chartData['dates']);
        $this->assertCount($daysInMonth, $chartData['revenue']);
        $this->assertCount($daysInMonth, $chartData['orders']);

        // All values should be zero
        foreach ($chartData['revenue'] as $revenue) {
            $this->assertEquals(0.0, $revenue, "Revenue should be 0.0 for days with no orders");
        }

        foreach ($chartData['orders'] as $orderCount) {
            $this->assertEquals(0, $orderCount, "Order count should be 0 for days with no orders");
        }
    }

    /**
     * Edge case: Test with February in a leap year vs non-leap year
     */
    public function test_monthly_chart_handles_february_correctly(): void
    {
        $service = new AnalyticsService();

        // Test leap year February (29 days)
        $leapYearFeb = Carbon::create(2024, 2, 1)->startOfMonth();
        $chartData = $service->getDailySalesTrend('month', $leapYearFeb, $leapYearFeb->copy()->endOfMonth());
        $this->assertCount(29, $chartData['dates'], "Leap year February should have 29 days");

        // Test non-leap year February (28 days)
        $nonLeapYearFeb = Carbon::create(2023, 2, 1)->startOfMonth();
        $chartData = $service->getDailySalesTrend('month', $nonLeapYearFeb, $nonLeapYearFeb->copy()->endOfMonth());
        $this->assertCount(28, $chartData['dates'], "Non-leap year February should have 28 days");
    }

    /**
     * Property test: Verify that dates are in sequential order
     */
    public function test_monthly_chart_dates_are_sequential(): void
    {
        $service = new AnalyticsService();

        // Test with current month
        $currentMonth = Carbon::now()->startOfMonth();
        $chartData = $service->getDailySalesTrend('month', $currentMonth, $currentMonth->copy()->endOfMonth());

        // Verify dates are sequential (each day follows the previous)
        $expectedDate = $currentMonth->copy();
        foreach ($chartData['dates'] as $index => $dateLabel) {
            $expectedLabel = $expectedDate->format('M d');
            $this->assertEquals(
                $expectedLabel,
                $dateLabel,
                "Date at index {$index} should be {$expectedLabel}"
            );
            $expectedDate->addDay();
        }
    }
}
