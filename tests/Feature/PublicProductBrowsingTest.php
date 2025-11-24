<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Category;
use App\Models\Product;
use App\Models\Brand;

class PublicProductBrowsingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the landing page displays active categories with product counts
     */
    public function test_landing_page_displays_active_categories_with_product_counts(): void
    {
        // Create a brand first
        $brand = Brand::factory()->create();
        
        // Create active root categories with products
        $category1 = Category::factory()->active()->root()->create([
            'name' => 'Skincare',
            'slug' => 'skincare',
            'description' => 'Complete skincare solutions'
        ]);
        
        $category2 = Category::factory()->active()->root()->create([
            'name' => 'Makeup',
            'slug' => 'makeup',
            'description' => 'Beauty products'
        ]);
        
        // Create inactive category (should not appear)
        $inactiveCategory = Category::factory()->inactive()->root()->create([
            'name' => 'Inactive',
            'slug' => 'inactive'
        ]);
        
        // Create products for categories
        Product::factory()->count(3)->create([
            'category_id' => $category1->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        Product::factory()->count(5)->create([
            'category_id' => $category2->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        // Visit landing page
        $response = $this->get('/');
        
        $response->assertStatus(200);
        
        // Assert active categories are displayed
        $response->assertSee('Skincare');
        $response->assertSee('Makeup');
        $response->assertSee('Complete skincare solutions');
        $response->assertSee('Beauty products');
        
        // Assert product counts are displayed
        $response->assertSee('3 products');
        $response->assertSee('5 products');
        
        // Assert inactive category is not displayed
        $response->assertDontSee('Inactive');
        
        // Assert category links are present
        $response->assertSee(route('products.category', 'skincare'), false);
        $response->assertSee(route('products.category', 'makeup'), false);
    }

    /**
     * Test that category links navigate to correct category pages
     */
    public function test_category_links_navigate_to_category_pages(): void
    {
        // Create a brand first
        $brand = Brand::factory()->create();
        
        // Create a category
        $category = Category::factory()->active()->root()->create([
            'name' => 'Skincare',
            'slug' => 'skincare'
        ]);
        
        // Create products for the category
        Product::factory()->count(2)->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active'
        ]);
        
        // Visit category page using slug
        $response = $this->get(route('products.category', 'skincare'));
        
        $response->assertStatus(200);
        $response->assertSee('Skincare');
    }

    /**
     * Test that empty categories display appropriate message
     */
    public function test_landing_page_handles_no_categories_gracefully(): void
    {
        // Visit landing page with no categories
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('No categories available at the moment.');
    }
}
