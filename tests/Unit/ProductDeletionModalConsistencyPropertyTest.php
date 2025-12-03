<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: product-deletion-with-stock, Property 9: Modal Consistency
 * Validates: Requirements 3.4
 */
class ProductDeletionModalConsistencyPropertyTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);
    }

    /**
     * Property 9: Modal Consistency
     * For any page where product deletion can be initiated (list, detail, or edit page), 
     * the confirmation modal should have the same structure, content format, and styling.
     * 
     * @test
     */
    public function property_modal_structure_is_consistent_across_all_pages()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Clean up any existing data first
            Product::query()->delete();
            Inventory::query()->delete();
            
            // Arrange: Create a random product with random attributes
            $randomName = 'Product ' . fake()->unique()->word() . ' ' . rand(1000, 9999);
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => $randomName,
            ]);

            // Generate random stock quantity (0 to 1000)
            $stockQuantity = rand(0, 1000);
            
            if ($stockQuantity > 0) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $stockQuantity,
                    'quantity_reserved' => 0,
                ]);
            }

            // Refresh to get computed total_stock
            $product->refresh();
            $actualStock = $product->total_stock;

            $user = $this->createStaffUser();

            // Act: Load all three pages
            $indexResponse = $this->actingAs($user)->get(route('staff.products.index'));
            $showResponse = $this->actingAs($user)->get(route('staff.products.show', $product));
            $editResponse = $this->actingAs($user)->get(route('staff.products.edit', $product));

            // Assert: All pages should return 200
            $indexResponse->assertStatus(200);
            $showResponse->assertStatus(200);
            $editResponse->assertStatus(200);

            // Extract modal-related content from each page
            $indexContent = $indexResponse->getContent();
            $showContent = $showResponse->getContent();
            $editContent = $editResponse->getContent();

            // Assert: All pages should have delete button with data attributes
            $this->assertStringContainsString('data-product-id="' . $product->id . '"', $indexContent,
                "Index page should have delete button with product ID");
            $this->assertStringContainsString('data-product-id="' . $product->id . '"', $showContent,
                "Show page should have delete button with product ID");
            $this->assertStringContainsString('data-product-id="' . $product->id . '"', $editContent,
                "Edit page should have delete button with product ID");

            // Assert: All pages should have product name in data attribute
            $this->assertStringContainsString('data-product-name="' . e($randomName) . '"', $indexContent,
                "Index page should have product name in data attribute");
            $this->assertStringContainsString('data-product-name="' . e($randomName) . '"', $showContent,
                "Show page should have product name in data attribute");
            $this->assertStringContainsString('data-product-name="' . e($randomName) . '"', $editContent,
                "Edit page should have product name in data attribute");

            // Assert: All pages should have stock quantity in data attribute
            $this->assertStringContainsString('data-stock-quantity="' . $actualStock . '"', $indexContent,
                "Index page should have stock quantity in data attribute");
            $this->assertStringContainsString('data-stock-quantity="' . $actualStock . '"', $showContent,
                "Show page should have stock quantity in data attribute");
            $this->assertStringContainsString('data-stock-quantity="' . $actualStock . '"', $editContent,
                "Edit page should have stock quantity in data attribute");

            // Assert: All pages should have the modal component structure
            // Check for key modal elements that should be present on all pages
            $modalElements = [
                'x-on:open-delete-modal.window',  // Alpine.js event listener
                'Delete Product',                  // Modal title
                'Are you sure you want to delete', // Confirmation text
            ];

            foreach ($modalElements as $element) {
                $this->assertStringContainsString($element, $indexContent,
                    "Index page should contain modal element: {$element}");
                $this->assertStringContainsString($element, $showContent,
                    "Show page should contain modal element: {$element}");
                $this->assertStringContainsString($element, $editContent,
                    "Edit page should contain modal element: {$element}");
            }

            // Assert: Modal styling should be consistent (check for key CSS classes)
            $stylingClasses = [
                'bg-red-600',      // Delete button color
                'hover:bg-red-700', // Delete button hover
                'bg-gray-50',      // Modal footer background
            ];

            foreach ($stylingClasses as $class) {
                $this->assertStringContainsString($class, $indexContent,
                    "Index page should contain styling class: {$class}");
                $this->assertStringContainsString($class, $showContent,
                    "Show page should contain styling class: {$class}");
                $this->assertStringContainsString($class, $editContent,
                    "Edit page should contain styling class: {$class}");
            }

            // Assert: Warning message structure should be consistent
            if ($actualStock > 0) {
                $warningElements = [
                    'Warning: Product has stock',
                    'units</span> in stock',
                    'cannot be undone',
                ];

                foreach ($warningElements as $element) {
                    $this->assertStringContainsString($element, $indexContent,
                        "Index page should contain warning element: {$element}");
                    $this->assertStringContainsString($element, $showContent,
                        "Show page should contain warning element: {$element}");
                    $this->assertStringContainsString($element, $editContent,
                        "Edit page should contain warning element: {$element}");
                }
            }
        }
    }

    /**
     * Property: Modal structure consistency with varying stock levels
     * 
     * @test
     */
    public function property_modal_structure_consistent_with_different_stock_levels()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            Product::query()->delete();
            Inventory::query()->delete();
            
            // Create product with random stock (including edge cases)
            $stockLevels = [0, 1, 10, 100, 1000, rand(1, 999)];
            $stockQuantity = $stockLevels[array_rand($stockLevels)];
            
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'name' => 'TestProduct' . $iteration,
            ]);

            if ($stockQuantity > 0) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => $stockQuantity,
                    'quantity_reserved' => 0,
                ]);
            }

            $product->refresh();
            $user = $this->createStaffUser();

            // Get all three pages
            $pages = [
                'index' => $this->actingAs($user)->get(route('staff.products.index'))->getContent(),
                'show' => $this->actingAs($user)->get(route('staff.products.show', $product))->getContent(),
                'edit' => $this->actingAs($user)->get(route('staff.products.edit', $product))->getContent(),
            ];

            // Assert: Modal structure elements should be present on all pages regardless of stock
            $requiredElements = [
                'x-on:open-delete-modal.window',
                'x-on:close-delete-modal.window',
                'x-on:keydown.escape.window',
                'role="dialog"',
                'aria-modal="true"',
                'Delete Product',
            ];

            foreach ($pages as $pageName => $content) {
                foreach ($requiredElements as $element) {
                    $this->assertStringContainsString($element, $content,
                        "Page '{$pageName}' should contain required modal element: {$element}");
                }
            }

            // Assert: Button structure should be consistent
            $buttonElements = [
                'Cancel',
                'Delete Product',
                'type="submit"',
                'type="button"',
            ];

            foreach ($pages as $pageName => $content) {
                foreach ($buttonElements as $element) {
                    $this->assertStringContainsString($element, $content,
                        "Page '{$pageName}' should contain button element: {$element}");
                }
            }
        }
    }

    /**
     * Property: Modal accessibility attributes are consistent
     * 
     * @test
     */
    public function property_modal_accessibility_attributes_consistent_across_pages()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Clean up
            Product::query()->delete();
            Inventory::query()->delete();
            
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
            ]);

            // Random stock
            if (rand(0, 1)) {
                Inventory::factory()->create([
                    'product_id' => $product->id,
                    'quantity_available' => rand(1, 500),
                ]);
            }

            $product->refresh();
            $user = $this->createStaffUser();

            // Get all pages
            $indexContent = $this->actingAs($user)->get(route('staff.products.index'))->getContent();
            $showContent = $this->actingAs($user)->get(route('staff.products.show', $product))->getContent();
            $editContent = $this->actingAs($user)->get(route('staff.products.edit', $product))->getContent();

            // Assert: Accessibility attributes should be present on all pages
            $accessibilityAttributes = [
                'role="dialog"',
                'aria-modal="true"',
                'aria-labelledby="modal-title"',
                'aria-describedby="modal-description"',
                'id="modal-title"',
                'id="modal-description"',
            ];

            foreach ($accessibilityAttributes as $attribute) {
                $this->assertStringContainsString($attribute, $indexContent,
                    "Index page should have accessibility attribute: {$attribute}");
                $this->assertStringContainsString($attribute, $showContent,
                    "Show page should have accessibility attribute: {$attribute}");
                $this->assertStringContainsString($attribute, $editContent,
                    "Edit page should have accessibility attribute: {$attribute}");
            }
        }
    }

    /**
     * Helper method to create a staff user
     */
    private function createStaffUser()
    {
        return \App\Models\User::factory()->create([
            'role' => 'staff',
        ]);
    }
}
