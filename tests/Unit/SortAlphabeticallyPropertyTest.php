<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Category;
use App\Models\Product;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 15: Sort alphabetically
 * Validates: Requirements 5.5
 */
class SortAlphabeticallyPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 15: Sort alphabetically
     * For any set of products when sort="name" is selected, the products should be ordered alphabetically by name in ascending order
     * 
     * @test
     */
    public function property_sort_by_name_orders_products_alphabetically_ascending()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create products with different names
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $productCount = rand(5, 15);
            $products = [];
            
            // Create products with random names
            $names = ['Alpha Product', 'Beta Product', 'Charlie Product', 'Delta Product', 
                     'Echo Product', 'Foxtrot Product', 'Golf Product', 'Hotel Product',
                     'India Product', 'Juliet Product', 'Kilo Product', 'Lima Product',
                     'Mike Product', 'November Product', 'Oscar Product'];
            
            shuffle($names);
            
            for ($i = 0; $i < min($productCount, count($names)); $i++) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'status' => 'active',
                    'is_featured' => false,
                    'name' => $names[$i],
                ]);
                $products[] = $product;
            }
            
            // Act: Request dashboard with sort=name
            $response = $this->actingAs(User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard', ['sort' => 'name']));
            
            // Assert: Response should be successful
            $response->assertStatus(200);
            
            // Get products in expected order (name asc)
            $expectedOrder = Product::where('status', 'active')
                ->where('is_featured', false)
                ->orderBy('name', 'asc')
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
                            "Product '{$product->name}' should appear after the previous product in alphabetical order"
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
     * Property: Alphabetical sort orders by name field
     * 
     * @test
     */
    public function property_alphabetical_sort_orders_by_name_field()
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
        
        // Act: Request dashboard with sort=name
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
        
        // Verify alphabetical order: Apple < Banana < Cherry
        $this->assertLessThan($posB, $posA, "Apple should appear before Banana");
        $this->assertLessThan($posC, $posB, "Banana should appear before Cherry");
    }
}
