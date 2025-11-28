<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * Feature: customer-dashboard-redesign, Property 12: Sort by newest
 * Validates: Requirements 5.2
 */
class SortByNewestPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 12: Sort by newest
     * For any set of products when sort="newest" is selected, the products should be ordered by created_at in descending order
     * 
     * @test
     */
    public function property_sort_by_newest_orders_products_by_created_at_descending()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create products with different creation dates
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $productCount = rand(5, 15);
            $products = [];
            
            // Create products with random timestamps
            for ($i = 0; $i < $productCount; $i++) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => false,
                    'created_at' => Carbon::now()->subDays(rand(1, 100)),
                ]);
                $products[] = $product;
            }
            
            // Act: Request dashboard with sort=newest
            $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard', ['sort' => 'newest']));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Get products in expected order (created_at desc)
            $expectedOrder = Product::where('status', 'active')
                ->where('is_featured', false)
                ->orderBy('created_at', 'desc')
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
                            "Product '{$product->name}' (created at {$product->created_at}) should appear after the previous product in newest-first order"
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
     * Property: Sort by newest is the default when no sort parameter is provided
     * 
     * @test
     */
    public function property_newest_is_default_sort_order()
    {
        // Arrange: Create products with different creation dates
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
            'created_at' => Carbon::now()->subDays(10),
        ]);
        
        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
            'created_at' => Carbon::now()->subDays(1),
        ]);
        
        // Act: Request dashboard without sort parameter
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Newer product should appear first
        $content = $response->getContent();
        $pos1 = strpos($content, $product1->name);
        $pos2 = strpos($content, $product2->name);
        
        $this->assertNotFalse($pos2, "Newer product should be visible");
        $this->assertNotFalse($pos1, "Older product should be visible");
        $this->assertLessThan($pos1, $pos2, "Newer product should appear before older product");
    }
}
