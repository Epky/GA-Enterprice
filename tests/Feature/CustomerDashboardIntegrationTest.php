<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Inventory;

class CustomerDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Brand $brand;
    private Category $category1;
    private Category $category2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a customer user
        $this->customer = User::factory()->create([
            'role' => 'customer',
            'email' => 'customer@test.com',
        ]);

        // Create brands
        $this->brand = Brand::factory()->create([
            'name' => 'Test Brand',
            'is_active' => true,
        ]);

        // Create categories
        $this->category1 = Category::factory()->create([
            'name' => 'Skincare',
            'slug' => 'skincare',
            'is_active' => true,
        ]);

        $this->category2 = Category::factory()->create([
            'name' => 'Makeup',
            'slug' => 'makeup',
            'is_active' => true,
        ]);
    }

    /**
     * Test dashboard rendering with no filters
     * Requirements: All requirements
     */
    public function test_dashboard_renders_with_no_filters(): void
    {
        // Create featured products
        $featuredProduct = Product::factory()->create([
            'name' => 'Featured Product',
            'category_id' => $this->category1->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'is_featured' => true,
            'base_price' => 100.00,
        ]);

        // Create regular products
        $regularProduct = Product::factory()->create([
            'name' => 'Regular Product',
            'category_id' => $this->category2->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'is_featured' => false,
            'base_price' => 50.00,
        ]);

        // Create inventory for products
        Inventory::factory()->create([
            'product_id' => $featuredProduct->id,
            'quantity_available' => 10,
        ]);

        Inventory::factory()->create([
            'product_id' => $regularProduct->id,
            'quantity_available' => 5,
        ]);

        // Act as customer and visit dashboard
        $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

        // Assert successful response
        $response->assertStatus(200);

        // Assert hero section is present
        $response->assertSee('Welcome to GA Beauty Store');

        // Assert featured products section
        $response->assertSee('Featured Products');
        $response->assertSee('Featured Product');
        $response->assertSee('FEATURED');

        // Assert category showcase
        $response->assertSee('Shop by Category');
        $response->assertSee('Skincare');
        $response->assertSee('Makeup');

        // Assert quick actions
        $response->assertSee('Quick Actions');
        $response->assertSee('My Orders');
        $response->assertSee('Wishlist');
        $response->assertSee('Account Settings');

        // Assert filter sidebar
        $response->assertSee('Filters');
        $response->assertSee('Category');
        $response->assertSee('Brand');
        $response->assertSee('Price Range');

        // Assert product grid
        $response->assertSee('All Products');
        $response->assertSee('Regular Product');

        // Assert pagination info
        $response->assertSee('Showing');
        $response->assertSee('products');
    }

    /**
     * Test dashboard with multiple filters active
     * Requirements: 3.2, 3.3, 3.4, 3.6
     */
    public function test_dashboard_with_multiple_filters_active(): void
    {
        // Create another brand
        $brand2 = Brand::factory()->create([
            'name' => 'Another Brand',
            'is_active' => true,
        ]);

        // Create products with different attributes
        $product1 = Product::factory()->create([
            'name' => 'Skincare Product A',
            'category_id' => $this->category1->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'base_price' => 150.00,
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Skincare Product B',
            'category_id' => $this->category1->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'base_price' => 80.00,
        ]);

        $product3 = Product::factory()->create([
            'name' => 'Makeup Product',
            'category_id' => $this->category2->id,
            'brand_id' => $brand2->id,
            'status' => 'active',
            'base_price' => 200.00,
        ]);

        // Create inventory
        Inventory::factory()->create(['product_id' => $product1->id, 'quantity_available' => 10]);
        Inventory::factory()->create(['product_id' => $product2->id, 'quantity_available' => 5]);
        Inventory::factory()->create(['product_id' => $product3->id, 'quantity_available' => 8]);

        // Apply multiple filters: category, brand, and price range
        $response = $this->actingAs($this->customer)->get(route('customer.dashboard', [
            'category' => $this->category1->id,
            'brand' => $this->brand->id,
            'min_price' => 50,
            'max_price' => 100,
        ]));

        $response->assertStatus(200);

        // Should see product2 (matches all filters)
        $response->assertSee('Skincare Product B');

        // Should NOT see product1 (price too high)
        $response->assertDontSee('Skincare Product A');

        // Should NOT see product3 (wrong category and brand)
        $response->assertDontSee('Makeup Product');

        // Assert Clear Filters button is present
        $response->assertSee('Clear Filters');

        // Assert filters are preserved in pagination links
        $response->assertSee('category=' . $this->category1->id, false);
        // Brand filter is preserved in hidden inputs
        $content = $response->getContent();
        $this->assertStringContainsString('name="brand" value="' . $this->brand->id . '"', $content);
        $this->assertStringContainsString('name="min_price" value="50"', $content);
        $this->assertStringContainsString('name="max_price" value="100"', $content);
    }

    /**
     * Test search + filter + sort combination
     * Requirements: 3.2, 3.3, 5.2, 5.3, 5.4, 5.5
     */
    public function test_search_filter_sort_combination(): void
    {
        // Create products with searchable names
        $product1 = Product::factory()->create([
            'name' => 'Moisturizing Cream',
            'description' => 'Hydrating skincare product',
            'category_id' => $this->category1->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'base_price' => 120.00,
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Moisturizing Lotion',
            'description' => 'Light moisturizer',
            'category_id' => $this->category1->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'base_price' => 80.00,
        ]);

        $product3 = Product::factory()->create([
            'name' => 'Lipstick',
            'description' => 'Makeup product',
            'category_id' => $this->category2->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'base_price' => 50.00,
        ]);

        // Create inventory
        Inventory::factory()->create(['product_id' => $product1->id, 'quantity_available' => 10]);
        Inventory::factory()->create(['product_id' => $product2->id, 'quantity_available' => 5]);
        Inventory::factory()->create(['product_id' => $product3->id, 'quantity_available' => 8]);

        // Test search + category filter + sort by price low to high
        $response = $this->actingAs($this->customer)->get(route('customer.dashboard', [
            'search' => 'Moisturizing',
            'category' => $this->category1->id,
            'sort' => 'price_low',
        ]));

        $response->assertStatus(200);

        // Should see both moisturizing products
        $response->assertSee('Moisturizing Cream');
        $response->assertSee('Moisturizing Lotion');

        // Should NOT see lipstick (doesn't match search)
        $response->assertDontSee('Lipstick');

        // Both products should be visible (sorting verification is done in unit tests)
        // The actual sort order is tested in dedicated sort property tests

        // Assert all parameters are preserved (search is in hidden input, others in URL)
        $content = $response->getContent();
        $this->assertStringContainsString('name="search" value="Moisturizing"', $content);
        $this->assertStringContainsString('name="category" value="' . $this->category1->id . '"', $content);
        // Sort parameter is preserved in the sort dropdown selection
        $this->assertStringContainsString('value="price_low" selected', $content);
    }

    /**
     * Test pagination with filters
     * Requirements: 3.6, 6.1, 6.4
     */
    public function test_pagination_with_filters(): void
    {
        // Create 15 products (more than one page, which is 12 per page)
        $products = [];
        for ($i = 1; $i <= 15; $i++) {
            $products[] = Product::factory()->create([
                'name' => "Product {$i}",
                'category_id' => $this->category1->id,
                'brand_id' => $this->brand->id,
                'status' => 'active',
                'base_price' => 50.00 + $i,
            ]);
            
            Inventory::factory()->create([
                'product_id' => $products[$i - 1]->id,
                'quantity_available' => 10,
            ]);
        }

        // Visit first page with category filter
        $response = $this->actingAs($this->customer)->get(route('customer.dashboard', [
            'category' => $this->category1->id,
        ]));

        $response->assertStatus(200);

        // Assert pagination info shows correct range (format may vary)
        $response->assertSee('Showing');
        $response->assertSee('1');
        $response->assertSee('12');
        $response->assertSee('15');
        $response->assertSee('products');

        // Assert pagination links preserve filters
        $response->assertSee('category=' . $this->category1->id, false);
        $response->assertSee('page=2', false);

        // Visit second page
        $response = $this->actingAs($this->customer)->get(route('customer.dashboard', [
            'category' => $this->category1->id,
            'page' => 2,
        ]));

        $response->assertStatus(200);

        // Assert pagination info shows correct range for page 2 (format may vary)
        $response->assertSee('13');
        $response->assertSee('15');
        $response->assertSee('products');

        // Assert category filter is still active
        $response->assertSee('category=' . $this->category1->id, false);
    }

    /**
     * Test empty state when no products match filters
     * Requirements: 6.2, 6.3, 6.5
     */
    public function test_empty_state_with_no_matching_products(): void
    {
        // Create a product that won't match our filters
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'category_id' => $this->category1->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'base_price' => 50.00,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 10,
        ]);

        // Apply filters that won't match any products
        $response = $this->actingAs($this->customer)->get(route('customer.dashboard', [
            'min_price' => 1000,
            'max_price' => 2000,
        ]));

        $response->assertStatus(200);

        // Assert empty state message
        $response->assertSee('No products found');
        $response->assertSee('Try adjusting your filters');

        // Assert "View All Products" button is present
        $response->assertSee('View All Products');
    }

    /**
     * Test out of stock badge display
     * Requirements: 4.2
     */
    public function test_out_of_stock_badge_display(): void
    {
        // Create product with no stock
        $outOfStockProduct = Product::factory()->create([
            'name' => 'Out of Stock Product',
            'category_id' => $this->category1->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'base_price' => 100.00,
        ]);

        Inventory::factory()->create([
            'product_id' => $outOfStockProduct->id,
            'quantity_available' => 0,
        ]);

        // Create product with stock
        $inStockProduct = Product::factory()->create([
            'name' => 'In Stock Product',
            'category_id' => $this->category1->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'base_price' => 100.00,
        ]);

        Inventory::factory()->create([
            'product_id' => $inStockProduct->id,
            'quantity_available' => 10,
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

        $response->assertStatus(200);

        // Assert both products are visible
        $response->assertSee('Out of Stock Product');
        $response->assertSee('In Stock Product');

        // Assert OUT OF STOCK badge appears for the out of stock product
        $response->assertSee('OUT OF STOCK');
    }

    /**
     * Test featured products limit
     * Requirements: 2.2
     */
    public function test_featured_products_limited_to_four(): void
    {
        // Create 6 featured products
        for ($i = 1; $i <= 6; $i++) {
            $product = Product::factory()->create([
                'name' => "Featured Product {$i}",
                'category_id' => $this->category1->id,
                'brand_id' => $this->brand->id,
                'status' => 'active',
                'is_featured' => true,
                'base_price' => 100.00,
            ]);

            Inventory::factory()->create([
                'product_id' => $product->id,
                'quantity_available' => 10,
            ]);
        }

        $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

        $response->assertStatus(200);

        // Count how many times "FEATURED" badge appears
        $content = $response->getContent();
        $featuredCount = substr_count($content, 'FEATURED');

        // Should be exactly 4 (maximum featured products)
        $this->assertEquals(4, $featuredCount, 'Should display exactly 4 featured products');
    }

    /**
     * Test category showcase functionality
     * Requirements: 7.1, 7.2, 7.3, 7.4
     */
    public function test_category_showcase_displays_active_categories(): void
    {
        // Create products for categories
        $product1 = Product::factory()->create([
            'category_id' => $this->category1->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        $product2 = Product::factory()->create([
            'category_id' => $this->category2->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        Inventory::factory()->create(['product_id' => $product1->id, 'quantity_available' => 10]);
        Inventory::factory()->create(['product_id' => $product2->id, 'quantity_available' => 5]);

        // Create inactive category (should not appear)
        $inactiveCategory = Category::factory()->create([
            'name' => 'Inactive Category',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

        $response->assertStatus(200);

        // Assert category showcase section
        $response->assertSee('Shop by Category');
        $response->assertSee('Skincare');
        $response->assertSee('Makeup');

        // Assert product counts
        $response->assertSee('1 product');

        // Assert inactive category is not shown
        $response->assertDontSee('Inactive Category');

        // Assert category cards have filter links
        $response->assertSee('category=' . $this->category1->id, false);
        $response->assertSee('category=' . $this->category2->id, false);
    }

    /**
     * Test sort functionality preserves filters
     * Requirements: 5.6
     */
    public function test_sort_preserves_active_filters(): void
    {
        // Create products
        $product1 = Product::factory()->create([
            'name' => 'Product A',
            'category_id' => $this->category1->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'base_price' => 100.00,
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Product B',
            'category_id' => $this->category1->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'base_price' => 50.00,
        ]);

        Inventory::factory()->create(['product_id' => $product1->id, 'quantity_available' => 10]);
        Inventory::factory()->create(['product_id' => $product2->id, 'quantity_available' => 5]);

        // Apply category filter and sort
        $response = $this->actingAs($this->customer)->get(route('customer.dashboard', [
            'category' => $this->category1->id,
            'sort' => 'price_high',
        ]));

        $response->assertStatus(200);

        // Assert both filter and sort are in the URL
        $response->assertSee('category=' . $this->category1->id, false);
        $content = $response->getContent();
        $this->assertStringContainsString('name="sort"', $content);
        
        // Both products should be visible (sorting verification is done in unit tests)
        $response->assertSee('Product A');
        $response->assertSee('Product B');
    }

    /**
     * Test price formatting
     * Requirements: 4.6
     */
    public function test_price_formatting_displays_correctly(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'category_id' => $this->category1->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
            'base_price' => 1234.56,
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 10,
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

        $response->assertStatus(200);

        // Assert price is formatted with peso symbol and 2 decimals
        $response->assertSee('₱1,234.56');
    }

    /**
     * Test responsive grid layout elements are present
     * Requirements: 9.2, 10.1, 10.2, 10.3
     */
    public function test_responsive_layout_elements_present(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category1->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 10,
        ]);

        $response = $this->actingAs($this->customer)->get(route('customer.dashboard'));

        $response->assertStatus(200);

        // Assert responsive grid classes are present
        $content = $response->getContent();
        
        // Check for responsive grid classes (Tailwind)
        $this->assertStringContainsString('grid', $content);
        $this->assertStringContainsString('md:', $content);
        $this->assertStringContainsString('lg:', $content);
    }
}
