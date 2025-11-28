<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-analytics-dashboard, Property 9: Category revenue sums to total revenue
 * Validates: Requirements 5.1, 5.2
 */
class AnalyticsCategoryRevenueSumPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property 9: Category revenue sums to total revenue
     * For any period, the sum of revenue across all categories should equal 
     * the total revenue for that period
     * 
     * @test
     */
    public function property_category_revenue_sums_to_total_revenue()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up first to avoid unique constraint violations
            OrderItem::query()->delete();
            Order::query()->delete();
            Product::query()->delete();
            Category::query()->delete();
            Brand::query()->delete();
            
            // Arrange: Create random categories and brands with unique slugs
            $categoryCount = rand(3, 6);
            $categories = collect();
            for ($i = 0; $i < $categoryCount; $i++) {
                $uniqueId = 'cat-iter' . $iteration . '-idx' . $i . '-' . uniqid() . '-' . mt_rand();
                $name = 'Category ' . $uniqueId;
                $categories->push(Category::create([
                    'name' => $name,
                    'slug' => \Illuminate\Support\Str::slug($name),
                    'description' => 'Test category',
                    'display_order' => $i,
                    'is_active' => true,
                ]));
            }
            
            $brandCount = rand(2, 4);
            $brands = collect();
            for ($i = 0; $i < $brandCount; $i++) {
                $uniqueId = 'brand-iter' . $iteration . '-idx' . $i . '-' . uniqid() . '-' . mt_rand();
                $name = 'Brand ' . $uniqueId;
                $brands->push(Brand::create([
                    'name' => $name,
                    'slug' => \Illuminate\Support\Str::slug($name),
                    'description' => 'Test brand',
                    'is_active' => true,
                ]));
            }
            
            // Create products in various categories with unique SKUs
            $products = [];
            $productCounter = 0;
            foreach ($categories as $category) {
                $productsInCategoryCount = rand(2, 5);
                for ($j = 0; $j < $productsInCategoryCount; $j++) {
                    $products[] = Product::factory()->create([
                        'category_id' => $category->id,
                        'brand_id' => $brands->random()->id,
                        'sku' => 'SKU-iter' . $iteration . '-prod' . $productCounter++,
                    ]);
                }
            }
            
            // Create completed orders with order items
            $totalExpectedRevenue = 0;
            for ($i = 0; $i < rand(10, 20); $i++) {
                $order = Order::factory()->create([
                    'order_status' => 'completed',
                    'payment_status' => 'paid',
                    'created_at' => Carbon::now()->subDays(rand(1, 25)),
                ]);
                
                // Add random order items
                $orderRevenue = 0;
                for ($j = 0; $j < rand(1, 4); $j++) {
                    $product = $products[array_rand($products)];
                    $quantity = rand(1, 5);
                    $unitPrice = rand(100, 1000) / 10; // Random price between 10 and 100
                    
                    OrderItem::factory()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ]);
                    
                    $orderRevenue += $quantity * $unitPrice;
                }
                
                // Update order total
                $order->update(['total_amount' => $orderRevenue]);
                $totalExpectedRevenue += $orderRevenue;
            }
            
            // Act: Get sales by category
            $categorySales = $this->analyticsService->getSalesByCategory('month');
            
            // Calculate sum of category revenues
            $sumOfCategoryRevenues = $categorySales->sum('total_revenue');
            
            // Assert: Sum of category revenues should equal total revenue
            $this->assertEqualsWithDelta(
                $totalExpectedRevenue,
                $sumOfCategoryRevenues,
                0.1,
                "Sum of category revenues should equal total revenue (iteration {$iteration})"
            );
        }
        
        // Final cleanup
        OrderItem::query()->delete();
        Order::query()->delete();
        Product::query()->delete();
        Category::query()->delete();
        Brand::query()->delete();
    }

    /**
     * Property: Category revenue sum handles single category
     * 
     * @test
     */
    public function property_category_revenue_sum_handles_single_category()
    {
        // Arrange: Create single category with products
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        $products = Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        // Create orders
        $totalRevenue = 0;
        for ($i = 0; $i < 5; $i++) {
            $order = Order::factory()->create([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'created_at' => Carbon::now()->subDays(rand(1, 25)),
            ]);
            
            $orderRevenue = 0;
            foreach ($products as $product) {
                $quantity = rand(1, 3);
                $unitPrice = rand(100, 500) / 10;
                
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]);
                
                $orderRevenue += $quantity * $unitPrice;
            }
            
            $order->update(['total_amount' => $orderRevenue]);
            $totalRevenue += $orderRevenue;
        }
        
        // Act
        $categorySales = $this->analyticsService->getSalesByCategory('month');
        
        // Assert
        $this->assertCount(1, $categorySales, "Should have exactly one category");
        $this->assertEqualsWithDelta(
            $totalRevenue,
            $categorySales->first()->total_revenue,
            0.1,
            "Single category revenue should equal total revenue"
        );
    }

    /**
     * Property: Category revenue sum handles no orders
     * 
     * @test
     */
    public function property_category_revenue_sum_handles_no_orders()
    {
        // Arrange: Create categories and products but no orders
        $categories = Category::factory()->count(3)->create();
        $brand = Brand::factory()->create();
        
        foreach ($categories as $category) {
            Product::factory()->count(2)->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
            ]);
        }
        
        // Act
        $categorySales = $this->analyticsService->getSalesByCategory('month');
        
        // Assert
        $this->assertCount(0, $categorySales, "Should have no category sales when no orders exist");
        $this->assertEquals(0, $categorySales->sum('total_revenue'), "Total revenue should be zero");
    }

    /**
     * Property: Category revenue sum excludes non-completed orders
     * 
     * @test
     */
    public function property_category_revenue_sum_excludes_non_completed_orders()
    {
        // Arrange: Create categories and products
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        // Create completed order (should be included)
        $completedOrder = Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 100.00,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $completedOrder->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
        
        // Create pending order (should NOT be included)
        $pendingOrder = Order::factory()->create([
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'total_amount' => 200.00,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $pendingOrder->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
        ]);
        
        // Act
        $categorySales = $this->analyticsService->getSalesByCategory('month');
        
        // Assert: Should only include completed order
        $this->assertEqualsWithDelta(
            100.00,
            $categorySales->sum('total_revenue'),
            0.1,
            "Category revenue should only include completed orders"
        );
    }
}
