<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-dashboard-reorganization, Property 9: Payment method data includes percentages
 * 
 * Property: For any payment method in the distribution chart, the payment method 
 * data should include a percentage value
 * 
 * Validates: Requirements 3.4
 */
class AnalyticsPaymentMethodPercentagesPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * @test
     * Property: For any payment method in the distribution chart, the payment method 
     * data should include a percentage value
     */
    public function payment_method_data_includes_percentage_values()
    {
        // Run property test 100 times with different random data
        for ($i = 0; $i < 100; $i++) {
            // Clear data between iterations
            \App\Models\Payment::query()->delete();
            \App\Models\Order::query()->delete();
            \App\Models\User::query()->delete();
            
            // Generate random payment methods
            $paymentMethods = ['cash', 'credit_card', 'debit_card', 'gcash', 'paymaya'];
            $selectedMethods = array_rand(array_flip($paymentMethods), rand(1, count($paymentMethods)));
            if (!is_array($selectedMethods)) {
                $selectedMethods = [$selectedMethods];
            }
            
            // Create a user
            $user = User::factory()->create();
            
            // Create orders with random payment methods
            foreach ($selectedMethods as $method) {
                $orderCount = rand(1, 10);
                
                for ($j = 0; $j < $orderCount; $j++) {
                    $order = Order::factory()->create([
                        'user_id' => $user->id,
                        'order_status' => 'completed',
                        'payment_status' => 'paid',
                        'total_amount' => rand(100, 10000),
                        'created_at' => Carbon::now()->subDays(rand(0, 30)),
                    ]);
                    
                    Payment::create([
                        'order_id' => $order->id,
                        'payment_method' => $method,
                        'amount' => $order->total_amount,
                        'payment_status' => 'completed',
                    ]);
                }
            }
            
            // Get payment method distribution
            $result = $this->analyticsService->getPaymentMethodDistribution('month');
            
            // Property: Result should be a collection
            $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result, 
                "Payment method distribution should return a collection");
            
            // Property: Each payment method must have percentage fields
            foreach ($result as $payment) {
                // Must have payment_method field
                $this->assertTrue(isset($payment->payment_method), 
                    "Payment method data must include payment_method field");
                
                // Must have order_count field
                $this->assertTrue(isset($payment->order_count), 
                    "Payment method data must include order_count field");
                
                // Must have total_revenue field
                $this->assertTrue(isset($payment->total_revenue), 
                    "Payment method data must include total_revenue field");
                
                // Must have order_percentage field
                $this->assertTrue(isset($payment->order_percentage), 
                    "Payment method data must include order_percentage field");
                
                // Must have revenue_percentage field
                $this->assertTrue(isset($payment->revenue_percentage), 
                    "Payment method data must include revenue_percentage field");
                
                // Percentages must be numeric
                $this->assertIsNumeric($payment->order_percentage, 
                    "Order percentage must be numeric for payment method: {$payment->payment_method}");
                $this->assertIsNumeric($payment->revenue_percentage, 
                    "Revenue percentage must be numeric for payment method: {$payment->payment_method}");
                
                // Percentages must be between 0 and 100
                $this->assertGreaterThanOrEqual(0, $payment->order_percentage, 
                    "Order percentage must be >= 0 for payment method: {$payment->payment_method}");
                $this->assertLessThanOrEqual(100, $payment->order_percentage, 
                    "Order percentage must be <= 100 for payment method: {$payment->payment_method}");
                
                $this->assertGreaterThanOrEqual(0, $payment->revenue_percentage, 
                    "Revenue percentage must be >= 0 for payment method: {$payment->payment_method}");
                $this->assertLessThanOrEqual(100, $payment->revenue_percentage, 
                    "Revenue percentage must be <= 100 for payment method: {$payment->payment_method}");
            }
            
            // Property: Sum of all order percentages should equal 100% (with tolerance for rounding)
            if ($result->count() > 0) {
                $totalOrderPercentage = $result->sum('order_percentage');
                $this->assertEqualsWithDelta(100.0, $totalOrderPercentage, 0.5, 
                    "Sum of all order percentages should equal 100% (got {$totalOrderPercentage}%)");
                
                // Property: Sum of all revenue percentages should equal 100% (with tolerance for rounding)
                $totalRevenuePercentage = $result->sum('revenue_percentage');
                $this->assertEqualsWithDelta(100.0, $totalRevenuePercentage, 0.5, 
                    "Sum of all revenue percentages should equal 100% (got {$totalRevenuePercentage}%)");
            }
        }
    }

    /**
     * @test
     * Edge case: When there are no payments, collection should be empty
     */
    public function payment_method_distribution_is_empty_when_no_payments()
    {
        // Get payment method distribution with no payments
        $result = $this->analyticsService->getPaymentMethodDistribution('month');
        
        // Should return empty collection
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(0, $result);
    }
}
