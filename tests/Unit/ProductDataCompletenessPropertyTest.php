<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: admin-dashboard-reorganization, Property 5: Product data completeness
 * Validates: Requirements 2.4
 * 
 * Property: For any product displayed in top-selling products, 
 * the product data should include product name, quantity sold, and revenue generated
 */
class ProductDataCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that top-selling products contain all required data fields
     * 
     * @test
     */
    public function property_top_products_contain_complete_data()
    {
        // Create test data
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        
        // Create multiple products
        $products = Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        // Create orders with items for each product
        foreach ($products as $product) {
            $order = Order::factory()->create([
                'order_status' => 'completed',
                'total_amount' => 1000,
            ]);
            
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => rand(1, 10),
                'unit_price' => 100,
            ]);
        }
        
        $analyticsService = app(AnalyticsService::class);
        
        // Get top selling products
        $topProducts = $analyticsService->getTopSellingProducts(10, 'month');
        
        // Verify that we have products
        $this->assertNotEmpty($topProducts, "Top products should not be empty");
        
        // Verify each product has complete data
        foreach ($topProducts as $product) {
            // Verify product name is present and not empty
            $this->assertObjectHasProperty('product_name', $product, 
                "Product should have product_name property");
            $this->assertNotEmpty($product->product_name, 
                "Product name should not be empty");
            
            // Verify quantity sold is present and numeric
            $this->assertObjectHasProperty('total_quantity_sold', $product, 
                "Product should have total_quantity_sold property");
            $this->assertTrue(is_numeric($product->total_quantity_sold), 
                "Total quantity sold should be numeric");
            $this->assertGreaterThan(0, $product->total_quantity_sold, 
                "Total quantity sold should be greater than 0 for top products");
            
            // Verify revenue is present and numeric
            $this->assertObjectHasProperty('total_revenue', $product, 
                "Product should have total_revenue property");
            $this->assertTrue(is_numeric($product->total_revenue), 
                "Total revenue should be numeric");
            $this->assertGreaterThanOrEqual(0, $product->total_revenue, 
                "Total revenue should be non-negative");
        }
    }
    
    /**
     * Test that product data completeness holds for different time periods
     * 
     * @test
     */
    public function property_product_data_complete_across_all_periods()
    {
        // Create test data
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        
        $order = Order::factory()->create([
            'order_status' => 'completed',
            'total_amount' => 500,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 100,
        ]);
        
        $analyticsService = app(AnalyticsService::class);
        
        // Test different periods
        $periods = ['today', 'week', 'month', 'year'];
        
        foreach ($periods as $period) {
            $topProducts = $analyticsService->getTopSellingProducts(10, $period);
            
            // If there are products for this period, verify completeness
            foreach ($topProducts as $product) {
                $this->assertObjectHasProperty('product_name', $product, 
                    "Product should have product_name for period: {$period}");
                $this->assertObjectHasProperty('total_quantity_sold', $product, 
                    "Product should have total_quantity_sold for period: {$period}");
                $this->assertObjectHasProperty('total_revenue', $product, 
                    "Product should have total_revenue for period: {$period}");
                    
                // Verify values are not null
                $this->assertNotNull($product->product_name, 
                    "Product name should not be null for period: {$period}");
                $this->assertNotNull($product->total_quantity_sold, 
                    "Total quantity sold should not be null for period: {$period}");
                $this->assertNotNull($product->total_revenue, 
                    "Total revenue should not be null for period: {$period}");
            }
        }
    }
}
