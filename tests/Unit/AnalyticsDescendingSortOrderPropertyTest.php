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
 * Feature: admin-analytics-dashboard, Property 11: Descending sort order maintained
 * Validates: Requirements 5.4, 6.4
 */
class AnalyticsDescendingSortOrderPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property 11: Descending sort order maintained
     * For any list sorted by a numeric value (revenue, quantity, count), each item's 
     * sort value should be greater than or equal to the next item's sort value
     * 
     * @test
     */
    public function property_category_sales_ordered_by_revenue_descending()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 5; $iteration++) {
            // Clean up first to avoid unique constraint violations
            \DB::table('order_items')->delete();
            \DB::table('payments')->delete();
            \DB::table('orders')->delete();
            \DB::table('inventory')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            
            // Arrange: Create random categories and brands with unique slugs
            $categoryCount = rand(5, 10);
            $categories = collect();
            for ($i = 0; $i < $categoryCount; $i++) {
                $uniqueId = 'cat-iter' . $iteration . '-idx' . $i . '-' . uniqid();
                $name = 'Category ' . $uniqueId;
                $categories->push(Category::factory()->create([
                    'name' => $name,
                    'slug' => \Illuminate\Support\Str::slug($name),
                ]));
            }
            
            $brandCount = rand(2, 4);
            $brands = collect();
            for ($i = 0; $i < $brandCount; $i++) {
                $uniqueId = 'brand-iter' . $iteration . '-idx' . $i . '-' . uniqid();
                $name = 'Brand ' . $uniqueId;
                $brands->push(Brand::factory()->create([
                    'name' => $name,
                    'slug' => \Illuminate\Support\Str::slug($name),
                ]));
            }
            
            // Create products in various categories
            $products = [];
            foreach ($categories as $category) {
                $productsInCategory = Product::factory()->count(rand(2, 4))->create([
                    'category_id' => $category->id,
                    'brand_id' => $brands->random()->id,
                ]);
                $products = array_merge($products, $productsInCategory->all());
            }
            
            // Create completed orders with order items
            for ($i = 0; $i < rand(20, 40); $i++) {
                $order = Order::factory()->create([
                    'order_status' => 'completed',
                    'payment_status' => 'paid',
                    'created_at' => Carbon::now()->subDays(rand(1, 25)),
                ]);
                
                // Add random order items
                $orderRevenue = 0;
                for ($j = 0; $j < rand(1, 3); $j++) {
                    $product = $products[array_rand($products)];
                    $quantity = rand(1, 5);
                    $unitPrice = rand(100, 1000) / 10;
                    
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
            }
            
            // Act: Get sales by category
            $categorySales = $this->analyticsService->getSalesByCategory('month');
            
            // Assert: Each item's revenue should be >= next item's revenue
            for ($i = 0; $i < $categorySales->count() - 1; $i++) {
                $this->assertGreaterThanOrEqual(
                    $categorySales[$i + 1]->total_revenue,
                    $categorySales[$i]->total_revenue,
                    "Category at index {$i} should have revenue >= category at index " . ($i + 1) . " (iteration {$iteration})"
                );
            }
        }
        
        // Final cleanup
        OrderItem::query()->delete();
        Order::query()->delete();
        Product::query()->delete();
        Category::query()->delete();
        Brand::query()->delete();
    }

    /**
     * Property: Brand sales ordered by revenue descending
     * 
     * @test
     */
    public function property_brand_sales_ordered_by_revenue_descending()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 5; $iteration++) {
            // Clean up first
            OrderItem::query()->delete();
            Order::query()->delete();
            Product::query()->delete();
            Category::query()->delete();
            Brand::query()->delete();
            
            // Arrange: Create random categories and brands with unique slugs
            $categoryCount = rand(2, 4);
            $categories = collect();
            for ($i = 0; $i < $categoryCount; $i++) {
                $uniqueId = 'cat-iter' . $iteration . '-idx' . $i . '-' . uniqid();
                $name = 'Category ' . $uniqueId;
                $categories->push(Category::factory()->create([
                    'name' => $name,
                    'slug' => \Illuminate\Support\Str::slug($name),
                ]));
            }
            
            $brandCount = rand(5, 10);
            $brands = collect();
            for ($i = 0; $i < $brandCount; $i++) {
                $uniqueId = 'brand-iter' . $iteration . '-idx' . $i . '-' . uniqid();
                $name = 'Brand ' . $uniqueId;
                $brands->push(Brand::factory()->create([
                    'name' => $name,
                    'slug' => \Illuminate\Support\Str::slug($name),
                ]));
            }
            
            // Create products in various brands with unique SKUs
            $products = [];
            $productCounter = 0;
            foreach ($brands as $brand) {
                $productsInBrandCount = rand(2, 4);
                for ($j = 0; $j < $productsInBrandCount; $j++) {
                    $products[] = Product::factory()->create([
                        'category_id' => $categories->random()->id,
                        'brand_id' => $brand->id,
                        'sku' => 'SKU-' . $iteration . '-' . $productCounter++,
                    ]);
                }
            }
            
            // Create completed orders with order items
            for ($i = 0; $i < rand(20, 40); $i++) {
                $order = Order::factory()->create([
                    'order_status' => 'completed',
                    'payment_status' => 'paid',
                    'created_at' => Carbon::now()->subDays(rand(1, 25)),
                ]);
                
                // Add random order items
                $orderRevenue = 0;
                for ($j = 0; $j < rand(1, 3); $j++) {
                    $product = $products[array_rand($products)];
                    $quantity = rand(1, 5);
                    $unitPrice = rand(100, 1000) / 10;
                    
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
            }
            
            // Act: Get sales by brand
            $brandSales = $this->analyticsService->getSalesByBrand('month');
            
            // Assert: Each item's revenue should be >= next item's revenue
            for ($i = 0; $i < $brandSales->count() - 1; $i++) {
                $this->assertGreaterThanOrEqual(
                    $brandSales[$i + 1]->total_revenue,
                    $brandSales[$i]->total_revenue,
                    "Brand at index {$i} should have revenue >= brand at index " . ($i + 1) . " (iteration {$iteration})"
                );
            }
        }
    }

    /**
     * Property: Sort order maintained with equal values
     * 
     * @test
     */
    public function property_sort_order_stable_with_equal_revenues()
    {
        // Arrange: Create categories with intentionally equal revenues
        $categories = Category::factory()->count(3)->create();
        $brand = Brand::factory()->create();
        
        // Create products for each category
        $products = [];
        foreach ($categories as $category) {
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
            ]);
            $products[] = $product;
        }
        
        // Create orders with same revenue for each product
        $fixedRevenue = 100.00;
        foreach ($products as $product) {
            $order = Order::factory()->create([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'total_amount' => $fixedRevenue,
                'created_at' => Carbon::now()->subDays(5),
            ]);
            
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $fixedRevenue,
            ]);
        }
        
        // Act
        $categorySales = $this->analyticsService->getSalesByCategory('month');
        
        // Assert: All revenues should be equal
        foreach ($categorySales as $category) {
            $this->assertEquals(
                $fixedRevenue,
                $category->total_revenue,
                "All categories should have equal revenue"
            );
        }
        
        // Assert: Sort order should still be maintained (all equal)
        for ($i = 0; $i < $categorySales->count() - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $categorySales[$i + 1]->total_revenue,
                $categorySales[$i]->total_revenue,
                "Sort order should be maintained even with equal values"
            );
        }
    }

    /**
     * Property: Sort order maintained with single item
     * 
     * @test
     */
    public function property_sort_order_maintained_with_single_item()
    {
        // Arrange: Create single category
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        $order = Order::factory()->create([
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'total_amount' => 100.00,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);
        
        // Act
        $categorySales = $this->analyticsService->getSalesByCategory('month');
        
        // Assert: Should have exactly one item
        $this->assertCount(1, $categorySales, "Should have exactly one category");
        
        // Sort order property is trivially satisfied with one item
        $this->assertTrue(true, "Sort order is trivially maintained with single item");
    }

    /**
     * Property: Sort order maintained with empty result
     * 
     * @test
     */
    public function property_sort_order_maintained_with_empty_result()
    {
        // Arrange: Create categories but no orders
        $categories = Category::factory()->count(3)->create();
        $brand = Brand::factory()->create();
        
        foreach ($categories as $category) {
            Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
            ]);
        }
        
        // Act
        $categorySales = $this->analyticsService->getSalesByCategory('month');
        
        // Assert: Should have no sales
        $this->assertCount(0, $categorySales, "Should have no category sales");
        
        // Sort order property is trivially satisfied with empty result
        $this->assertTrue(true, "Sort order is trivially maintained with empty result");
    }

    /**
     * Property: Sort order is consistent across multiple calls
     * 
     * @test
     */
    public function property_sort_order_is_consistent()
    {
        // Arrange: Create fixed set of data
        $categories = Category::factory()->count(5)->create();
        $brands = Brand::factory()->count(2)->create();
        
        $products = [];
        foreach ($categories as $category) {
            $productsInCategory = Product::factory()->count(2)->create([
                'category_id' => $category->id,
                'brand_id' => $brands->random()->id,
            ]);
            $products = array_merge($products, $productsInCategory->all());
        }
        
        // Create orders
        for ($i = 0; $i < 15; $i++) {
            $order = Order::factory()->create([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'created_at' => Carbon::now()->subDays(rand(1, 25)),
            ]);
            
            $orderRevenue = 0;
            for ($j = 0; $j < 2; $j++) {
                $product = $products[array_rand($products)];
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
        }
        
        // Act: Call multiple times
        $result1 = $this->analyticsService->getSalesByCategory('month');
        $result2 = $this->analyticsService->getSalesByCategory('month');
        $result3 = $this->analyticsService->getSalesByCategory('month');
        
        // Assert: All results should have same sort order
        $this->assertEquals(
            $result1->pluck('category_id')->toArray(),
            $result2->pluck('category_id')->toArray(),
            "Sort order should be consistent across calls"
        );
        $this->assertEquals(
            $result2->pluck('category_id')->toArray(),
            $result3->pluck('category_id')->toArray(),
            "Sort order should be consistent across calls"
        );
    }
}
