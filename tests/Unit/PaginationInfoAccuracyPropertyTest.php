<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 17: Pagination info accuracy
 * Validates: Requirements 6.1
 */
class PaginationInfoAccuracyPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 17: Pagination info accuracy
     * For any page of results, the "Showing X to Y of Z products" text should 
     * accurately reflect firstItem(), lastItem(), and total() values from the paginator
     * 
     * @test
     */
    public function property_pagination_info_displays_accurate_range_and_total()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create random number of products (between 15 and 50)
            $totalProducts = rand(15, 50);
            
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            Product::factory()->count($totalProducts)->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
            ]);
            
            // Test different pages
            $pageSize = 12; // As defined in the controller
            $totalPages = ceil($totalProducts / $pageSize);
            
            // Test first page, middle page, and last page
            $pagesToTest = [1];
            if ($totalPages > 2) {
                $pagesToTest[] = ceil($totalPages / 2); // Middle page
            }
            if ($totalPages > 1) {
                $pagesToTest[] = $totalPages; // Last page
            }
            
            foreach ($pagesToTest as $page) {
                // Act: Request specific page
                $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                    ->get(route('customer.dashboard', ['page' => $page]));
                
                // Assert: Check that pagination info is accurate
                $response->assertStatus(200);
                
                // Calculate expected values
                $expectedFirstItem = (($page - 1) * $pageSize) + 1;
                $expectedLastItem = min($page * $pageSize, $totalProducts);
                
                // Check that the response contains the correct pagination info
                $content = $response->getContent();
                
                // Look for the pagination text pattern
                $this->assertStringContainsString(
                    "Showing <span class=\"font-semibold\">{$expectedFirstItem}</span>",
                    $content,
                    "Expected first item to be {$expectedFirstItem} on page {$page} with {$totalProducts} total products"
                );
                
                $this->assertStringContainsString(
                    "to <span class=\"font-semibold\">{$expectedLastItem}</span>",
                    $content,
                    "Expected last item to be {$expectedLastItem} on page {$page} with {$totalProducts} total products"
                );
                
                $this->assertStringContainsString(
                    "of <span class=\"font-semibold\">{$totalProducts}</span> products",
                    $content,
                    "Expected total to be {$totalProducts} on page {$page}"
                );
            }
        }
    }

    /**
     * Property: When no products exist, pagination info should show zeros
     * 
     * @test
     */
    public function property_pagination_info_shows_zeros_when_no_products()
    {
        // Arrange: Create categories and brands but no products
        Category::factory()->create(['is_active' => true]);
        Brand::factory()->create(['is_active' => true]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Should show "Showing 0 to 0 of 0 products"
        $this->assertStringContainsString(
            "Showing <span class=\"font-semibold\">0</span>",
            $content,
            "Expected first item to be 0 when no products exist"
        );
        
        $this->assertStringContainsString(
            "to <span class=\"font-semibold\">0</span>",
            $content,
            "Expected last item to be 0 when no products exist"
        );
        
        $this->assertStringContainsString(
            "of <span class=\"font-semibold\">0</span> products",
            $content,
            "Expected total to be 0 when no products exist"
        );
    }

    /**
     * Property: Pagination info should be accurate with filters applied
     * 
     * @test
     */
    public function property_pagination_info_accurate_with_filters()
    {
        // Arrange: Create products in different categories
        $category1 = Category::factory()->create(['is_active' => true]);
        $category2 = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        // Create 20 products in category 1 and 15 in category 2
        Product::factory()->count(20)->create([
            'category_id' => $category1->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        Product::factory()->count(15)->create([
            'category_id' => $category2->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
        
        // Act: Filter by category 1
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard', ['category' => $category1->id]));
        
        // Assert: Should show pagination info for 20 products
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // First page should show 1 to 12 of 20
        $this->assertStringContainsString(
            "Showing <span class=\"font-semibold\">1</span>",
            $content
        );
        
        $this->assertStringContainsString(
            "to <span class=\"font-semibold\">12</span>",
            $content
        );
        
        $this->assertStringContainsString(
            "of <span class=\"font-semibold\">20</span> products",
            $content
        );
    }
}
