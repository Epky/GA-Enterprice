<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-analytics-dashboard, Property 4: Order counting excludes cancelled orders
 * Validates: Requirements 2.2, 2.3
 */
class AnalyticsOrderCountingPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property 4: Order counting excludes cancelled orders
     * For any date range, the order count should equal the number of orders 
     * where order_status is NOT 'cancelled' AND created_at is within the date range
     * 
     * @test
     */
    public function property_order_count_excludes_cancelled_orders()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Arrange: Create a random date range
            $startDate = Carbon::now()->subDays(rand(30, 90))->startOfDay();
            $endDate = Carbon::now()->subDays(rand(1, 29))->endOfDay();
            
            $validOrderCount = 0;
            
            // Create completed orders within range (should be included)
            $completedCount = rand(3, 8);
            for ($i = 0; $i < $completedCount; $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                Order::factory()->create([
                    'order_status' => 'completed',
                    'created_at' => $randomDate,
                ]);
                $validOrderCount++;
            }
            
            // Create pending orders within range (should be included)
            $pendingCount = rand(2, 5);
            for ($i = 0; $i < $pendingCount; $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                Order::factory()->create([
                    'order_status' => 'pending',
                    'created_at' => $randomDate,
                ]);
                $validOrderCount++;
            }
            
            // Create processing orders within range (should be included)
            $processingCount = rand(1, 4);
            for ($i = 0; $i < $processingCount; $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                Order::factory()->create([
                    'order_status' => 'processing',
                    'created_at' => $randomDate,
                ]);
                $validOrderCount++;
            }
            
            // Create cancelled orders within range (should NOT be included)
            for ($i = 0; $i < rand(2, 6); $i++) {
                $randomDate = $startDate->copy()->addSeconds(rand(0, $startDate->diffInSeconds($endDate)));
                Order::factory()->create([
                    'order_status' => 'cancelled',
                    'created_at' => $randomDate,
                ]);
            }
            
            // Create orders outside range (should NOT be included)
            for ($i = 0; $i < rand(2, 5); $i++) {
                Order::factory()->create([
                    'order_status' => 'completed',
                    'created_at' => $startDate->copy()->subDays(rand(1, 30)),
                ]);
            }
            
            // Act: Get order metrics from service
            $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
            
            // Assert: Total orders should match expected count (excluding cancelled)
            $this->assertEquals(
                $validOrderCount,
                $result['total_orders'],
                "Total orders should exclude cancelled orders and only count orders within date range (iteration {$iteration})"
            );
            
            // Clean up for next iteration
            Order::query()->delete();
        }
    }

    /**
     * Property: Order counting includes completed, pending, and processing statuses
     * 
     * @test
     */
    public function property_order_count_includes_valid_statuses()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Create orders with valid statuses
        Order::factory()->count(5)->create([
            'order_status' => 'completed',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        Order::factory()->count(3)->create([
            'order_status' => 'pending',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        Order::factory()->count(2)->create([
            'order_status' => 'processing',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Create cancelled orders (should not be counted)
        Order::factory()->count(4)->create([
            'order_status' => 'cancelled',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert
        $this->assertEquals(10, $result['total_orders'], "Should count completed, pending, and processing orders");
        $this->assertEquals(5, $result['completed_orders'], "Should count completed orders");
        $this->assertEquals(3, $result['pending_orders'], "Should count pending orders");
        $this->assertEquals(2, $result['processing_orders'], "Should count processing orders");
    }

    /**
     * Property: Order counting returns zero when only cancelled orders exist
     * 
     * @test
     */
    public function property_order_count_returns_zero_when_only_cancelled_orders_exist()
    {
        // Arrange
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Create only cancelled orders
        Order::factory()->count(10)->create([
            'order_status' => 'cancelled',
            'created_at' => Carbon::now()->subDays(rand(1, 29)),
        ]);
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert
        $this->assertEquals(0, $result['total_orders'], "Should return zero when only cancelled orders exist");
    }

    /**
     * Property: Order counting handles empty date range
     * 
     * @test
     */
    public function property_order_count_handles_empty_date_range()
    {
        // Arrange: Create orders but query a date range with no orders
        Order::factory()->count(5)->create([
            'order_status' => 'completed',
            'created_at' => Carbon::now()->subYears(2),
        ]);
        
        $startDate = Carbon::now()->subDays(7)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert
        $this->assertEquals(0, $result['total_orders'], "Should return zero for date range with no orders");
    }

    /**
     * Property: Order counting respects exact date boundaries
     * 
     * @test
     */
    public function property_order_count_respects_exact_date_boundaries()
    {
        // Arrange: Create orders at boundary dates
        $startDate = Carbon::create(2024, 1, 15, 0, 0, 0);
        $endDate = Carbon::create(2024, 1, 20, 23, 59, 59);
        
        // Order exactly at start boundary (should be included)
        Order::factory()->create([
            'order_status' => 'completed',
            'created_at' => $startDate,
        ]);
        
        // Order exactly at end boundary (should be included)
        Order::factory()->create([
            'order_status' => 'pending',
            'created_at' => $endDate,
        ]);
        
        // Order just before start (should NOT be included)
        Order::factory()->create([
            'order_status' => 'completed',
            'created_at' => $startDate->copy()->subSecond(),
        ]);
        
        // Order just after end (should NOT be included)
        Order::factory()->create([
            'order_status' => 'completed',
            'created_at' => $endDate->copy()->addSecond(),
        ]);
        
        // Act
        $result = $this->analyticsService->getOrderMetrics($startDate, $endDate);
        
        // Assert: Should only include orders at boundaries
        $this->assertEquals(
            2,
            $result['total_orders'],
            "Should include orders at exact boundaries but exclude orders outside"
        );
    }
}
