<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-analytics-dashboard, Property 7: Top products ordered by quantity descending
 * Validates: Requirements 4.4
 */
class AnalyticsTopProductsSortingPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property 7: Top products ordered by quantity descending
     * For any list of top selling products, each product's quantity_sold should be 
     * greater than or equal to the next product's quantity_sold
     * 
     * @test
     */
    public function property_top_products_ordered_by_quantity_descending()
    {
        // Create brand and category
        $brand = \App\Models\Brand::factory()->create();
        $category = \App\Models\Category::factory()->create();
        
        // Arrange: Create products with varying sales quantities
        $numProducts = 20;
        $products = collect();
        for ($p = 0; $p < $numProducts; $p++) {
            $products->push(Product::factory()->create([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'sku' => 'TEST-' . $p . '-' . uniqid(),
                'slug' => 'test-product-' . $p . '-' . uniqid(),
            ]));
        }
        
        // Create completed orders with random order items
        $numOrders = 15;
        for ($i = 0; $i < $numOrders; $i++) {
            $order = Order::factory()->create([
                'order_status' => 'completed',
                'created_at' => Carbon::now()->subDays(rand(1, 25)),
            ]);
            
            // Add random number of items to each order
            $numItems = rand(1, 5);
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                $quantity = rand(1, 10);
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->base_price,
                    'total_price' => $quantity * $product->base_price,
                ]);
            }
        }
        
        // Act: Get top selling products
        $limit = 10;
        $topProducts = $this->analyticsService->getTopSellingProducts($limit, 'month');
        
        // Assert: Each product's quantity should be >= next product's quantity
        for ($i = 0; $i < $topProducts->count() - 1; $i++) {
            $currentProduct = $topProducts[$i];
            $nextProduct = $topProducts[$i + 1];
            
            $this->assertGreaterThanOrEqual(
                $nextProduct->total_quantity_sold,
                $currentProduct->total_quantity_sold,
                "Product at position {$i} should have quantity >= product at position " . ($i + 1) . 
                ". Got {$currentProduct->total_quantity_sold} and {$nextProduct->total_quantity_sold}"
            );
        }
        
        // Assert: Result count should not exceed limit
        $this->assertLessThanOrEqual(
            $limit,
            $topProducts->count(),
            "Result count should not exceed limit"
        );
        
        // Assert: All products have positive quantities
        foreach ($topProducts as $product) {
            $this->assertGreaterThan(
                0,
                $product->total_quantity_sold,
                "All top products should have positive quantities sold"
            );
        }
    }

    /**
     * Property: Top products sorting is stable for equal quantities
     * 
     * @test
     */
    public function property_top_products_sorting_is_stable_for_equal_quantities()
    {
        // Arrange: Create products with identical sales quantities
        $products = Product::factory()->count(5)->create();
        
        $order = Order::factory()->create([
            'order_status' => 'completed',
            'created_at' => Carbon::now()->subDays(5),
        ]);
        
        // Give each product the same quantity sold
        foreach ($products as $product) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 10, // Same quantity for all
                'unit_price' => $product->base_price,
                'total_price' => 10 * $product->base_price,
            ]);
        }
        
        // Act: Get top selling products multiple times
        $result1 = $this->analyticsService->getTopSellingProducts(10, 'month');
        $result2 = $this->analyticsService->getTopSellingProducts(10, 'month');
        
        // Assert: All products should have the same quantity
        foreach ($result1 as $product) {
            $this->assertEquals(
                10,
                $product->total_quantity_sold,
                "All products should have equal quantity sold"
            );
        }
        
        // Assert: Results should be consistent across calls
        $this->assertEquals(
            $result1->pluck('product_id')->toArray(),
            $result2->pluck('product_id')->toArray(),
            "Results should be consistent for equal quantities"
        );
    }

    /**
     * Property: Top products excludes non-completed orders
     * 
     * @test
     */
    public function property_top_products_excludes_non_completed_orders()
    {
        // Arrange: Create products
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        
        // Create completed order with product1
        $completedOrder = Order::factory()->create([
            'order_status' => 'completed',
            'created_at' => Carbon::now()->subDays(5),
        ]);
        OrderItem::factory()->create([
            'order_id' => $completedOrder->id,
            'product_id' => $product1->id,
            'quantity' => 10,
            'unit_price' => $product1->base_price,
            'total_price' => 10 * $product1->base_price,
        ]);
        
        // Create pending order with product2 (should be excluded)
        $pendingOrder = Order::factory()->create([
            'order_status' => 'pending',
            'created_at' => Carbon::now()->subDays(5),
        ]);
        OrderItem::factory()->create([
            'order_id' => $pendingOrder->id,
            'product_id' => $product2->id,
            'quantity' => 100, // Much higher quantity but should be excluded
            'unit_price' => $product2->base_price,
            'total_price' => 100 * $product2->base_price,
        ]);
        
        // Create cancelled order with product2 (should be excluded)
        $cancelledOrder = Order::factory()->create([
            'order_status' => 'cancelled',
            'created_at' => Carbon::now()->subDays(5),
        ]);
        OrderItem::factory()->create([
            'order_id' => $cancelledOrder->id,
            'product_id' => $product2->id,
            'quantity' => 50,
            'unit_price' => $product2->base_price,
            'total_price' => 50 * $product2->base_price,
        ]);
        
        // Act
        $topProducts = $this->analyticsService->getTopSellingProducts(10, 'month');
        
        // Assert: Only product1 should appear (from completed order)
        $this->assertEquals(1, $topProducts->count(), "Only completed orders should be included");
        $this->assertEquals($product1->id, $topProducts->first()->product_id);
        $this->assertEquals(10, $topProducts->first()->total_quantity_sold);
    }

    /**
     * Property: Top products respects limit parameter
     * 
     * @test
     */
    public function property_top_products_respects_limit_parameter()
    {
        // Arrange: Create many products with sales
        $products = Product::factory()->count(20)->create();
        
        foreach ($products as $product) {
            $order = Order::factory()->create([
                'order_status' => 'completed',
                'created_at' => Carbon::now()->subDays(5),
            ]);
            $quantity = rand(1, 100);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->base_price,
                'total_price' => $quantity * $product->base_price,
            ]);
        }
        
        // Act: Test various limits
        $limits = [1, 5, 10, 15];
        foreach ($limits as $limit) {
            $topProducts = $this->analyticsService->getTopSellingProducts($limit, 'month');
            
            // Assert: Result count should match limit (or less if fewer products exist)
            $this->assertLessThanOrEqual(
                $limit,
                $topProducts->count(),
                "Result count should not exceed limit of {$limit}"
            );
        }
    }

    /**
     * Property: Top products returns empty collection when no sales exist
     * 
     * @test
     */
    public function property_top_products_returns_empty_when_no_sales()
    {
        // Arrange: Create products but no orders
        Product::factory()->count(5)->create();
        
        // Act
        $topProducts = $this->analyticsService->getTopSellingProducts(10, 'month');
        
        // Assert
        $this->assertEquals(0, $topProducts->count(), "Should return empty collection when no sales exist");
    }

    /**
     * Property: Top products aggregates quantities correctly across multiple orders
     * 
     * @test
     */
    public function property_top_products_aggregates_quantities_across_orders()
    {
        // Arrange: Create a product and multiple orders
        $product = Product::factory()->create();
        
        $quantities = [5, 10, 15, 20]; // Total: 50
        foreach ($quantities as $quantity) {
            $order = Order::factory()->create([
                'order_status' => 'completed',
                'created_at' => Carbon::now()->subDays(rand(1, 25)),
            ]);
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->base_price,
                'total_price' => $quantity * $product->base_price,
            ]);
        }
        
        // Act
        $topProducts = $this->analyticsService->getTopSellingProducts(10, 'month');
        
        // Assert
        $this->assertEquals(1, $topProducts->count());
        $this->assertEquals(
            array_sum($quantities),
            $topProducts->first()->total_quantity_sold,
            "Should aggregate quantities across all orders"
        );
    }
}
