<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: customer-dashboard-redesign, Property 11: Price formatting
 * Validates: Requirements 4.6
 */
class ProductPriceFormattingPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 11: Price formatting
     * For any product price value, the displayed price should be formatted as 
     * "₱X,XXX.XX" with Philippine Peso symbol, thousand separators, and exactly 2 decimal places
     * 
     * @test
     */
    public function property_prices_formatted_with_peso_symbol_and_two_decimals()
    {
        // Run the test multiple times with different random prices
        for ($iteration = 0; $iteration < 10; $iteration++) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange: Create product with random price
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            // Generate random price (from 1 to 99999.99)
            $price = rand(100, 9999999) / 100; // Generates prices like 1.00, 123.45, 9999.99
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
                'base_price' => $price,
            ]);
            
            // Act: Render the dashboard view
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert: Price should be formatted correctly
            $response->assertStatus(200);
            
            // Expected format: ₱X,XXX.XX
            $expectedPrice = '₱' . number_format($price, 2);
            $response->assertSee($expectedPrice, false);
            
            // Verify the format has exactly 2 decimal places
            $this->assertMatchesRegularExpression('/₱[\d,]+\.\d{2}/', $expectedPrice,
                "Price should have exactly 2 decimal places");
        }
    }

    /**
     * Property: Prices with thousands should have comma separators
     * 
     * @test
     */
    public function property_prices_over_thousand_have_comma_separators()
    {
        // Test with various prices over 1000
        $testPrices = [1000.00, 1234.56, 10000.00, 12345.67, 99999.99];
        
        foreach ($testPrices as $price) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
                'base_price' => $price,
            ]);
            
            // Act
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert: Price should have comma separators
            $response->assertStatus(200);
            
            $expectedPrice = '₱' . number_format($price, 2);
            $response->assertSee($expectedPrice, false);
            
            // Verify comma is present for prices >= 1000
            if ($price >= 1000) {
                $this->assertStringContainsString(',', $expectedPrice,
                    "Price {$price} should contain comma separator");
            }
        }
    }

    /**
     * Property: Prices under 1000 should not have comma separators
     * 
     * @test
     */
    public function property_prices_under_thousand_have_no_comma_separators()
    {
        // Test with various prices under 1000
        $testPrices = [0.99, 9.99, 99.99, 999.99];
        
        foreach ($testPrices as $price) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
                'base_price' => $price,
            ]);
            
            // Act
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert: Price should not have comma separators
            $response->assertStatus(200);
            
            $expectedPrice = '₱' . number_format($price, 2);
            $response->assertSee($expectedPrice, false);
            
            // Verify no comma is present for prices < 1000
            $this->assertStringNotContainsString(',', $expectedPrice,
                "Price {$price} should not contain comma separator");
        }
    }

    /**
     * Property: All product prices on page should be consistently formatted
     * 
     * @test
     */
    public function property_all_prices_consistently_formatted()
    {
        // Arrange: Create multiple products with different prices
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $prices = [9.99, 99.99, 999.99, 1234.56, 9999.99];
        $products = [];
        
        foreach ($prices as $price) {
            $products[] = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
                'base_price' => $price,
            ]);
        }
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: All prices should be formatted consistently
        $response->assertStatus(200);
        
        foreach ($prices as $price) {
            $expectedPrice = '₱' . number_format($price, 2);
            $response->assertSee($expectedPrice, false);
        }
    }

    /**
     * Property: Zero prices should be formatted correctly
     * 
     * @test
     */
    public function property_zero_price_formatted_correctly()
    {
        // Arrange: Create product with zero price
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
            'base_price' => 0.00,
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Zero price should be formatted as ₱0.00
        $response->assertStatus(200);
        $response->assertSee('₱0.00', false);
    }

    /**
     * Property: Price formatting should handle decimal rounding correctly
     * 
     * @test
     */
    public function property_price_rounding_handled_correctly()
    {
        // Test prices that might have rounding issues
        $testCases = [
            ['input' => 10.005, 'expected' => '₱10.01'],  // Rounds up
            ['input' => 10.004, 'expected' => '₱10.00'],  // Rounds down
            ['input' => 99.995, 'expected' => '₱100.00'], // Rounds up to next integer
            ['input' => 99.994, 'expected' => '₱99.99'],  // Rounds down
        ];
        
        foreach ($testCases as $testCase) {
            // Clean up before each iteration
            \DB::table('products')->delete();
            \DB::table('categories')->delete();
            \DB::table('brands')->delete();
            \DB::table('users')->delete();
            
            // Arrange
            $category = Category::factory()->create(['is_active' => true]);
            $brand = Brand::factory()->create(['is_active' => true]);
            
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'status' => 'active',
                'is_featured' => false,
                'base_price' => $testCase['input'],
            ]);
            
            // Act
            $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
                ->get(route('customer.dashboard'));
            
            // Assert: Price should be rounded correctly
            $response->assertStatus(200);
            $response->assertSee($testCase['expected'], false);
        }
    }

    /**
     * Property: Featured products should also have correctly formatted prices
     * 
     * @test
     */
    public function property_featured_product_prices_formatted_correctly()
    {
        // Arrange: Create featured product with random price
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $price = rand(100, 9999999) / 100;
        
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => true,
            'base_price' => $price,
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Featured product price should be formatted correctly
        $response->assertStatus(200);
        
        $expectedPrice = '₱' . number_format($price, 2);
        $response->assertSee($expectedPrice, false);
    }

    /**
     * Property: Price format should be consistent in both featured and regular product sections
     * 
     * @test
     */
    public function property_price_format_consistent_across_sections()
    {
        // Arrange: Create both featured and regular products with same price
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $price = 1234.56;
        
        $featuredProduct = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => true,
            'base_price' => $price,
        ]);
        
        $regularProduct = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'is_featured' => false,
            'base_price' => $price,
        ]);
        
        // Act
        $response = $this->actingAs(\App\Models\User::factory()->create(['role' => 'customer']))
            ->get(route('customer.dashboard'));
        
        // Assert: Both should show the same formatted price
        $response->assertStatus(200);
        
        $expectedPrice = '₱1,234.56';
        $content = $response->getContent();
        
        // Count occurrences - should be at least 2 (one for featured, one for regular)
        $count = substr_count($content, $expectedPrice);
        $this->assertGreaterThanOrEqual(2, $count,
            "Expected price format should appear at least twice (featured and regular sections)");
    }
}
