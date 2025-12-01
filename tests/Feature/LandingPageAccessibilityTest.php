<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductImage;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LandingPageAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->customer = User::factory()->create([
            'role' => 'customer',
        ]);
    }

    /** @test */
    public function product_images_have_alt_text()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'status' => 'active',
            'category_id' => $category->id,
        ]);
        
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        
        // Check that product image has alt text
        $response->assertSee('alt="' . $product->name . ' - Product image"', false);
    }

    /** @test */
    public function placeholder_images_have_aria_label()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'status' => 'active',
            'category_id' => $category->id,
        ]);
        
        // No image created - should show placeholder

        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        
        // Check that placeholder has aria-label
        $response->assertSee('aria-label="No image available for ' . $product->name . '"', false);
    }

    /** @test */
    public function search_form_has_proper_labels()
    {
        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        
        // Check for search input label
        $response->assertSee('id="search-input"', false);
        $response->assertSee('aria-label="Search for products, brands, or categories"', false);
        
        // Check for submit button aria-label
        $response->assertSee('aria-label="Submit search"', false);
    }

    /** @test */
    public function filter_form_has_proper_labels()
    {
        Category::factory()->create(['is_active' => true]);
        Brand::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        
        // Check for category filter label
        $response->assertSee('id="category-filter"', false);
        $response->assertSee('for="category-filter"', false);
        
        // Check for brand filter label
        $response->assertSee('id="brand-filter"', false);
        $response->assertSee('for="brand-filter"', false);
        
        // Check for price range labels
        $response->assertSee('id="min-price"', false);
        $response->assertSee('id="max-price"', false);
        $response->assertSee('for="min-price"', false);
        $response->assertSee('for="max-price"', false);
    }

    /** @test */
    public function sort_select_has_proper_label()
    {
        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        
        // Check for sort select label
        $response->assertSee('id="sort-select"', false);
        $response->assertSee('for="sort-select"', false);
        $response->assertSee('aria-label="Sort products by"', false);
    }

    /** @test */
    public function product_cards_have_aria_labels()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'status' => 'active',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        
        // Check for product card aria-label
        $response->assertSee('aria-label="Product: ' . $product->name . '"', false);
        
        // Check for view details link aria-label
        $response->assertSee('aria-label="View details for ' . $product->name . '"', false);
    }

    /** @test */
    public function featured_products_section_has_proper_heading()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'status' => 'active',
            'is_featured' => true,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        
        // Check for featured products heading with id
        $response->assertSee('id="featured-products-heading"', false);
        $response->assertSee('aria-labelledby="featured-products-heading"', false);
    }

    /** @test */
    public function out_of_stock_badge_has_aria_label()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'status' => 'active',
            'category_id' => $category->id,
        ]);
        
        // Create inventory with zero stock
        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 0,
        ]);

        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        
        // Check for out of stock badge with aria-label
        $response->assertSee('aria-label="Out of stock"', false);
    }

    /** @test */
    public function price_displays_have_aria_labels()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'status' => 'active',
            'category_id' => $category->id,
            'base_price' => 1234.56,
        ]);

        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        
        // Check for price aria-label
        $response->assertSee('aria-label="Price: 1,234.56 pesos"', false);
    }

    /** @test */
    public function filter_error_messages_are_accessible()
    {
        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard', [
                'min_price' => 1000,
                'max_price' => 500, // Invalid: min > max
            ]));

        $response->assertStatus(302); // Redirects with error
        
        $response = $this->followRedirects($response);
        
        // Check for error alert with proper ARIA attributes
        $response->assertSee('role="alert"', false);
        $response->assertSee('aria-live="assertive"', false);
    }

    /** @test */
    public function pagination_has_proper_aria_label()
    {
        $category = Category::factory()->create(['is_active' => true]);
        
        // Create more than 12 products to trigger pagination
        Product::factory()->count(15)->create([
            'status' => 'active',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        
        // Check for pagination nav with aria-label
        $response->assertSee('aria-label="Pagination"', false);
    }

    /** @test */
    public function no_results_message_has_proper_aria_attributes()
    {
        // Don't create any products

        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        
        // Check for no results message with proper ARIA
        $response->assertSee('role="status"', false);
        $response->assertSee('aria-live="polite"', false);
    }

    /** @test */
    public function results_count_has_aria_live_region()
    {
        $category = Category::factory()->create(['is_active' => true]);
        Product::factory()->count(5)->create([
            'status' => 'active',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        
        // Check for results count with aria-live
        $response->assertSee('role="status"', false);
        $response->assertSee('aria-live="polite"', false);
        $response->assertSee('Showing', false);
    }

    /** @test */
    public function semantic_html_elements_are_used()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'status' => 'active',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        
        // Check for semantic HTML elements
        $response->assertSee('<main', false);
        $response->assertSee('<aside', false);
        $response->assertSee('<article', false);
        $response->assertSee('<nav', false);
        $response->assertSee('role="search"', false);
    }

    /** @test */
    public function buttons_meet_minimum_touch_target_size()
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'status' => 'active',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->get(route('customer.dashboard'));

        $response->assertStatus(200);
        
        // Check for minimum touch target size (44x44px)
        $response->assertSee('min-h-[44px]', false);
        $response->assertSee('min-w-[44px]', false);
    }
}
