<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 14: Sort by price descending
 * Validates: Requirements 5.4
 */
class SortByPriceDescendingPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 14: Sort by price descending
     * For any set of products when sort="price_high" is selected, the products should be ordered by base_price in descending order
     * 
     * @test
     */
    public function property_sort_by_price_high_orders_products_by_base_price_descending()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create products with different prices
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $productCount = rand(5, 15);
            $products = [];
            
            // Create products with random prices
            for ($i = 0; $i < $productCount; $i++) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => false,
                    'base_price' => rand(100, 10000) / 10, // Random price between 10.0 and 1000.0
                ]);
                $products[] = $product;
            }
            
            // Act: Request dashboard with sort=price_high
            $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard', ['sort' => 'price_high']));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Get products in expected order (base_price desc)
            $expectedOrder = Product::where('status', 'active')
                ->where('is_featured', false)
                ->orderBy('base_price', 'desc')
                ->get();
            
            // Extract the HTML content
            $content = $response->getContent();
            
            // Find positions of product names in the HTML
            $positions = [];
            foreach ($expectedOrder as $product) {
                $pos = strpos($content, $product->name);
                if ($pos !== false) {
                    $positions[$product->id] = $pos;
                }
            }
            
            // Verify that products appear in the correct order
            // Each product should appear before the next one in the expected order
            $previousPosition = -1;
            foreach ($expectedOrder as $index => $product) {
                if (isset($positions[$product->id])) {
                    if ($previousPosition !== -1) {
                        $this->assertGreaterThan(
                            $previousPosition,
                            $positions[$product->id],
                            "Product '{$product->name}' (price {$product->base_price}) should appear after the previous product in price-descending order"
                        );
                    }
                    $previousPosition = $positions[$product->id];
                }
                
                // Only check first page of results (12 products)
                if ($index >= 11) {
                    break;
                }
            }
        }
    }

    /**
     * Property: Highest priced product appears first
     * 
     * @test
     */
    public function property_highest_priced_product_appears_first()
    {
        // Arrange: Create products with different prices
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $cheapProduct = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
            'base_price' => 100.00,
            'name' => 'Cheap Product',
        ]);
        
        $expensiveProduct = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
            'base_price' => 1000.00,
            'name' => 'Expensive Product',
        ]);
        
        // Act: Request dashboard with sort=price_high
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', ['sort' => 'price_high']));
        
        // Assert: Expensive product should appear first
        $content = $response->getContent();
        $posExpensive = strpos($content, $expensiveProduct->name);
        $posCheap = strpos($content, $cheapProduct->name);
        
        $this->assertNotFalse($posExpensive, "Expensive product should be visible");
        $this->assertNotFalse($posCheap, "Cheap product should be visible");
        $this->assertLessThan($posCheap, $posExpensive, "Expensive product should appear before cheap product");
    }
}
