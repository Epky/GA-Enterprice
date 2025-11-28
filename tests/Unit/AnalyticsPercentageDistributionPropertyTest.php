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
 * Feature: admin-analytics-dashboard, Property 10: Percentage distribution sums to 100
 * Validates: Requirements 5.3, 7.4
 */
class AnalyticsPercentageDistributionPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property 10: Percentage distribution sums to 100
     * For any breakdown by category, brand, or payment method, the sum of all 
     * percentage values should equal 100% (within rounding tolerance of 0.1%)
     * 
     * @test
     */
    public function property_category_percentage_distribution_sums_to_100()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up first to avoid unique constraint violations
            \DB::table('order_items')->delete();
            \DB::table('payments')->delete();
            \DB::table('orders')->delete();
            \DB::table('inventory')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            
            // Arrange: Create random categories and brands with unique slugs
            $categoryCount = rand(3, 8);
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
            for ($i = 0; $i < rand(15, 30); $i++) {
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
            
            // Calculate sum of percentages
            $sumOfPercentages = $categorySales->sum('percentage');
            
            // Assert: Sum should be 100% (within tolerance of 0.1% for rounding)
            $this->assertEqualsWithDelta(
                100.0,
                $sumOfPercentages,
                0.1,
                "Sum of category percentages should equal 100% (iteration {$iteration})"
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
     * Property: Brand percentage distribution sums to 100
     * 
     * @test
     */
    public function property_brand_percentage_distribution_sums_to_100()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up first to avoid unique constraint violations
            \DB::table('order_items')->delete();
            \DB::table('payments')->delete();
            \DB::table('orders')->delete();
            \DB::table('inventory')->delete();
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            
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
            
            $brandCount = rand(3, 8);
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
                $productsInBrandCount = rand(2, 5);
                for ($j = 0; $j < $productsInBrandCount; $j++) {
                    $products[] = Product::factory()->create([
                        'category_id' => $categories->random()->id,
                        'brand_id' => $brand->id,
                        'sku' => 'SKU-iter' . $iteration . '-prod' . $productCounter++,
                    ]);
                }
            }
            
            // Create completed orders with order items
            for ($i = 0; $i < rand(15, 30); $i++) {
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
            
            // Calculate sum of percentages
            $sumOfPercentages = $brandSales->sum('percentage');
            
            // Assert: Sum should be 100% (within tolerance of 0.1% for rounding)
            $this->assertEqualsWithDelta(
                100.0,
                $sumOfPercentages,
                0.1,
                "Sum of brand percentages should equal 100% (iteration {$iteration})"
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
     * Property: Percentage distribution handles single item
     * 
     * @test
     */
    public function property_percentage_distribution_handles_single_category()
    {
        // Arrange: Create single category
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        $products = Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        // Create orders
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
        }
        
        // Act
        $categorySales = $this->analyticsService->getSalesByCategory('month');
        
        // Assert: Single category should have 100% of sales
        $this->assertCount(1, $categorySales, "Should have exactly one category");
        $this->assertEquals(
            100.0,
            $categorySales->first()->percentage,
            "Single category should have 100% of sales"
        );
    }

    /**
     * Property: Percentage distribution handles no orders
     * 
     * @test
     */
    public function property_percentage_distribution_handles_no_orders()
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
        
        // Assert: Should have no sales, so sum of percentages should be 0
        $this->assertEquals(0, $categorySales->sum('percentage'), "Sum of percentages should be 0 when no orders exist");
    }

    /**
     * Property: Each individual percentage is between 0 and 100
     * 
     * @test
     */
    public function property_each_percentage_is_between_0_and_100()
    {
        // Arrange: Create categories and products
        $categories = Category::factory()->count(5)->create();
        $brands = Brand::factory()->count(3)->create();
        
        $products = [];
        foreach ($categories as $category) {
            $productsInCategory = Product::factory()->count(2)->create([
                'category_id' => $category->id,
                'brand_id' => $brands->random()->id,
            ]);
            $products = array_merge($products, $productsInCategory->all());
        }
        
        // Create orders
        for ($i = 0; $i < 20; $i++) {
            $order = Order::factory()->create([
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'created_at' => Carbon::now()->subDays(rand(1, 25)),
            ]);
            
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
            
            $order->update(['total_amount' => $orderRevenue]);
        }
        
        // Act
        $categorySales = $this->analyticsService->getSalesByCategory('month');
        
        // Assert: Each percentage should be between 0 and 100
        foreach ($categorySales as $category) {
            $this->assertGreaterThanOrEqual(
                0.0,
                $category->percentage,
                "Percentage should be >= 0"
            );
            $this->assertLessThanOrEqual(
                100.0,
                $category->percentage,
                "Percentage should be <= 100"
            );
        }
    }

    /**
     * Property: Percentage distribution is consistent across multiple calls
     * 
     * @test
     */
    public function property_percentage_distribution_is_consistent()
    {
        // Arrange: Create fixed set of data
        $categories = Category::factory()->count(4)->create();
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
        for ($i = 0; $i < 10; $i++) {
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
        
        // Assert: All results should have same sum of percentages
        $sum1 = $result1->sum('percentage');
        $sum2 = $result2->sum('percentage');
        $sum3 = $result3->sum('percentage');
        
        $this->assertEquals($sum1, $sum2, "Percentage distribution should be consistent");
        $this->assertEquals($sum2, $sum3, "Percentage distribution should be consistent");
    }
}
