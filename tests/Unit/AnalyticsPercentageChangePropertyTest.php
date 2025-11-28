<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-analytics-dashboard, Property 3: Percentage change calculation accuracy
 * Validates: Requirements 1.4
 */
class AnalyticsPercentageChangePropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property 3: Percentage change calculation accuracy
     * For any two numeric values representing current and previous period metrics, 
     * the percentage change should equal ((current - previous) / previous) * 100, 
     * or 0 when previous is 0
     * 
     * @test
     */
    public function property_percentage_change_formula_is_accurate()
    {
        // Test with various combinations of current and previous values
        $testCases = [
            // [current, previous, expected_percentage]
            [100, 50, 100.0],      // 100% increase
            [50, 100, -50.0],      // 50% decrease
            [150, 100, 50.0],      // 50% increase
            [75, 100, -25.0],      // 25% decrease
            [200, 100, 100.0],     // 100% increase
            [100, 100, 0.0],       // No change
            [0, 100, -100.0],      // 100% decrease
            [100, 0, 100.0],       // From zero (special case)
            [0, 0, 0.0],           // Both zero (special case)
            [1000, 500, 100.0],    // Large numbers
            [0.5, 0.25, 100.0],    // Decimal numbers
            [33.33, 100, -66.67],  // Decimal result
        ];

        foreach ($testCases as [$current, $previous, $expected]) {
            // Arrange: Create orders for current and previous periods
            $currentStart = Carbon::now()->subDays(30)->startOfDay();
            $currentEnd = Carbon::now()->endOfDay();
            
            $previousPeriod = $this->analyticsService->getPreviousPeriod($currentStart, $currentEnd);
            
            // Create orders for current period
            if ($current > 0) {
                Order::factory()->create([
                    'order_status' => 'completed',
                    'payment_status' => 'paid',
                    'total_amount' => $current,
                    'created_at' => $currentStart->copy()->addDays(5),
                ]);
            }
            
            // Create orders for previous period
            if ($previous > 0) {
                Order::factory()->create([
                    'order_status' => 'completed',
                    'payment_status' => 'paid',
                    'total_amount' => $previous,
                    'created_at' => $previousPeriod['start']->copy()->addDays(5),
                ]);
            }
            
            // Act
            $result = $this->analyticsService->calculateRevenue($currentStart, $currentEnd);
            
            // Assert: Percentage change should match expected value (with small tolerance for floating point)
            $this->assertEqualsWithDelta(
                $expected,
                $result['change_percent'],
                0.01,
                "Percentage change should be {$expected}% for current={$current}, previous={$previous}"
            );
            
            // Clean up for next test case
            Order::query()->delete();
        }
    }

    /**
     * Property: Percentage change handles zero previous value correctly
     * 
     * @test
     */
    public function property_percentage_change_handles_zero_previous_value()
    {
        // Arrange: Create orders only in current period
        $currentStart = Carbon::now()->subDays(30)->startOfDay();
        $currentEnd = Carbon::now()->endOfDay();
        
        Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 500.00,
            'created_at' => $currentStart->copy()->addDays(5),
        ]);
        
        // No orders in previous period (previous = 0)
        
        // Act
        $result = $this->analyticsService->calculateRevenue($currentStart, $currentEnd);
        
        // Assert: When previous is 0 and current > 0, should return 100%
        $this->assertEquals(
            100.0,
            $result['change_percent'],
            "Percentage change should be 100% when previous is 0 and current is positive"
        );
    }

    /**
     * Property: Percentage change returns zero when both periods are zero
     * 
     * @test
     */
    public function property_percentage_change_returns_zero_when_both_periods_zero()
    {
        // Arrange: No orders in either period
        $currentStart = Carbon::now()->subDays(30)->startOfDay();
        $currentEnd = Carbon::now()->endOfDay();
        
        // Act
        $result = $this->analyticsService->calculateRevenue($currentStart, $currentEnd);
        
        // Assert: When both are 0, should return 0%
        $this->assertEquals(
            0.0,
            $result['change_percent'],
            "Percentage change should be 0% when both current and previous are zero"
        );
    }

    /**
     * Property: Percentage change is negative when revenue decreases
     * 
     * @test
     */
    public function property_percentage_change_is_negative_for_decrease()
    {
        // Run multiple iterations with random decreasing values
        for ($i = 0; $i < 10; $i++) {
            $currentStart = Carbon::now()->subDays(30)->startOfDay();
            $currentEnd = Carbon::now()->endOfDay();
            $previousPeriod = $this->analyticsService->getPreviousPeriod($currentStart, $currentEnd);
            
            // Generate random values where current < previous
            $previousAmount = rand(500, 1000);
            $currentAmount = rand(100, $previousAmount - 1);
            
            // Create orders
            Order::factory()->create([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'total_amount' => $currentAmount,
                'created_at' => $currentStart->copy()->addDays(5),
            ]);
            
            Order::factory()->create([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'total_amount' => $previousAmount,
                'created_at' => $previousPeriod['start']->copy()->addDays(5),
            ]);
            
            // Act
            $result = $this->analyticsService->calculateRevenue($currentStart, $currentEnd);
            
            // Assert: Percentage change should be negative
            $this->assertLessThan(
                0,
                $result['change_percent'],
                "Percentage change should be negative when revenue decreases (iteration {$i})"
            );
            
            // Clean up
            Order::query()->delete();
        }
    }

    /**
     * Property: Percentage change is positive when revenue increases
     * 
     * @test
     */
    public function property_percentage_change_is_positive_for_increase()
    {
        // Run multiple iterations with random increasing values
        for ($i = 0; $i < 10; $i++) {
            $currentStart = Carbon::now()->subDays(30)->startOfDay();
            $currentEnd = Carbon::now()->endOfDay();
            $previousPeriod = $this->analyticsService->getPreviousPeriod($currentStart, $currentEnd);
            
            // Generate random values where current > previous
            $previousAmount = rand(100, 500);
            $currentAmount = rand($previousAmount + 1, 1000);
            
            // Create orders
            Order::factory()->create([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'total_amount' => $currentAmount,
                'created_at' => $currentStart->copy()->addDays(5),
            ]);
            
            Order::factory()->create([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'total_amount' => $previousAmount,
                'created_at' => $previousPeriod['start']->copy()->addDays(5),
            ]);
            
            // Act
            $result = $this->analyticsService->calculateRevenue($currentStart, $currentEnd);
            
            // Assert: Percentage change should be positive
            $this->assertGreaterThan(
                0,
                $result['change_percent'],
                "Percentage change should be positive when revenue increases (iteration {$i})"
            );
            
            // Clean up
            Order::query()->delete();
        }
    }

    /**
     * Property: Percentage change is zero when values are equal
     * 
     * @test
     */
    public function property_percentage_change_is_zero_when_values_equal()
    {
        // Run multiple iterations with equal values
        for ($i = 0; $i < 5; $i++) {
            $currentStart = Carbon::now()->subDays(30)->startOfDay();
            $currentEnd = Carbon::now()->endOfDay();
            $previousPeriod = $this->analyticsService->getPreviousPeriod($currentStart, $currentEnd);
            
            $amount = rand(100, 1000);
            
            // Create orders with same amount
            Order::factory()->create([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'total_amount' => $amount,
                'created_at' => $currentStart->copy()->addDays(5),
            ]);
            
            Order::factory()->create([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'total_amount' => $amount,
                'created_at' => $previousPeriod['start']->copy()->addDays(5),
            ]);
            
            // Act
            $result = $this->analyticsService->calculateRevenue($currentStart, $currentEnd);
            
            // Assert: Percentage change should be zero
            $this->assertEquals(
                0.0,
                $result['change_percent'],
                "Percentage change should be 0% when current equals previous (iteration {$i})"
            );
            
            // Clean up
            Order::query()->delete();
        }
    }

    /**
     * Property: 100% decrease means current is zero
     * 
     * @test
     */
    public function property_hundred_percent_decrease_means_current_is_zero()
    {
        // Arrange: Create orders only in previous period
        $currentStart = Carbon::now()->subDays(30)->startOfDay();
        $currentEnd = Carbon::now()->endOfDay();
        $previousPeriod = $this->analyticsService->getPreviousPeriod($currentStart, $currentEnd);
        
        Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 1000.00,
            'created_at' => $previousPeriod['start']->copy()->addDays(5),
        ]);
        
        // No orders in current period
        
        // Act
        $result = $this->analyticsService->calculateRevenue($currentStart, $currentEnd);
        
        // Assert: Should be -100%
        $this->assertEquals(
            -100.0,
            $result['change_percent'],
            "Percentage change should be -100% when current is 0 and previous is positive"
        );
        $this->assertEquals(0.0, $result['total'], "Current revenue should be 0");
    }
}
