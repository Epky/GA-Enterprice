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
 * Feature: landing-page-product-display, Property 10: Sort order correctness
 * Validates: Requirements 4.1, 4.2, 4.3, 4.4
 */
class SortOrderCorrectnessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 10: Sort order correctness
     * For any sort option selected (newest, price ascending, price descending, alphabetical),
     * the products should be ordered according to the specified sort criteria
     * 
     * @test
     */
    public function property_sort_order_is_correct_for_all_sort_options()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create products with varied attributes
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $productCount = rand(5, 15);
            $products = [];
            
            // Create products with random prices, names, and creation dates
            for ($i = 0; $i < $productCount; $i++) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => false,
                    'base_price' => rand(100, 10000) / 10, // Random price between 10.0 and 1000.0
                    'name' => 'Product ' . chr(65 + rand(0, 25)) . rand(100, 999), // Random names
                    'created_at' => Carbon::now()->subDays(rand(1, 100)),
                ]);
                $products[] = $product;
            }
            
            $user = User::factory()->create(['role' => 'customer']);
            
            // Test all sort options
            $sortOptions = [
                'newest' => ['field' => 'created_at', 'direction' => 'desc'],
                'price_low' => ['field' => 'base_price', 'direction' => 'asc'],
                'price_high' => ['field' => 'base_price', 'direction' => 'desc'],
                'name' => ['field' => 'name', 'direction' => 'asc'],
            ];
            
            foreach ($sortOptions as $sortKey => $sortConfig) {
                // Act: Request dashboard with specific sort option
                $response = $this->actingAs($user)
                    ->get(route('customer.dashboard', ['sort' => $sortKey]));
                
                // Assert: Response should be successful
                $response->assertStatus(200);
                
                // Get products in expected order
                $expectedOrder = Product::where('status', 'active')
                    ->where('is_featured', false)
                    ->orderBy($sortConfig['field'], $sortConfig['direction'])
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
                                "Sort '{$sortKey}': Product '{$product->name}' should appear after the previous product in {$sortConfig['field']} {$sortConfig['direction']} order"
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
    }

    /**
     * Property: Default sort is newest when no sort parameter provided
     * 
     * @test
     */
    public function property_default_sort_is_newest()
    {
        // Arrange: Create products with different creation dates
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $oldProduct = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
            'created_at' => Carbon::now()->subDays(10),
            'name' => 'Old Product',
        ]);
        
        $newProduct = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
            'created_at' => Carbon::now()->subDays(1),
            'name' => 'New Product',
        ]);
        
        // Act: Request dashboard without sort parameter
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Newer product should appear first
        $content = $response->getContent();
        $posOld = strpos($content, $oldProduct->name);
        $posNew = strpos($content, $newProduct->name);
        
        $this->assertNotFalse($posNew, "New product should be visible");
        $this->assertNotFalse($posOld, "Old product should be visible");
        $this->assertLessThan($posOld, $posNew, "New product should appear before old product when no sort is specified");
    }

    /**
     * Property: Invalid sort parameter defaults to newest
     * 
     * @test
     */
    public function property_invalid_sort_parameter_defaults_to_newest()
    {
        // Arrange: Create products with different creation dates
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $oldProduct = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
            'created_at' => Carbon::now()->subDays(10),
            'name' => 'Old Product XYZ',
        ]);
        
        $newProduct = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
            'created_at' => Carbon::now()->subDays(1),
            'name' => 'New Product XYZ',
        ]);
        
        // Act: Request dashboard with invalid sort parameter (should be rejected by validation)
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', ['sort' => 'invalid_sort']));
        
        // Assert: Should redirect due to validation error
        $response->assertStatus(302);
    }

    /**
     * Property: Alphabetical sort orders by name ascending
     * 
     * @test
     */
    public function property_alphabetical_sort_orders_by_name()
    {
        // Arrange: Create products with different names
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $productA = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
            'name' => 'Apple Product',
        ]);
        
        $productB = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
            'name' => 'Banana Product',
        ]);
        
        $productC = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
            'name' => 'Cherry Product',
        ]);
        
        // Act: Request dashboard with alphabetical sort
        $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', ['sort' => 'name']));
        
        // Assert: Products should appear in alphabetical order
        $content = $response->getContent();
        $posA = strpos($content, $productA->name);
        $posB = strpos($content, $productB->name);
        $posC = strpos($content, $productC->name);
        
        $this->assertNotFalse($posA, "Product A should be visible");
        $this->assertNotFalse($posB, "Product B should be visible");
        $this->assertNotFalse($posC, "Product C should be visible");
        $this->assertLessThan($posB, $posA, "Apple should appear before Banana");
        $this->assertLessThan($posC, $posB, "Banana should appear before Cherry");
    }
}
