<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;

class ProductDeletionModalZeroStockTest extends TestCase
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

    public function test_modal_renders_without_warning_for_zero_stock()
    {
        // Create a product with no inventory (zero stock)
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
        ]);

        // Render the modal component
        $view = View::make('components.delete-confirmation-modal', [
            'productId' => $product->id,
            'productName' => $product->name,
            'stockQuantity' => 0,
            'deleteRoute' => route('staff.products.destroy', $product),
        ]);

        $html = $view->render();

        // Verify no warning message appears
        $this->assertStringNotContainsString('Warning: Product has stock', $html);
        $this->assertStringNotContainsString('units in stock', $html);
        $this->assertStringNotContainsString('bg-red-50', $html);
        
        // Verify standard confirmation message appears
        $this->assertStringContainsString('This action cannot be undone', $html);
        $this->assertStringContainsString('permanently delete the product', $html);
    }

    public function test_modal_uses_yellow_icon_for_zero_stock()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
        ]);

        $view = View::make('components.delete-confirmation-modal', [
            'productId' => $product->id,
            'productName' => $product->name,
            'stockQuantity' => 0,
            'deleteRoute' => route('staff.products.destroy', $product),
        ]);

        $html = $view->render();

        // Verify yellow icon is used (not red)
        $this->assertStringContainsString('bg-yellow-100', $html);
        $this->assertStringContainsString('text-yellow-600', $html);
        $this->assertStringNotContainsString('bg-red-100', $html);
    }

    public function test_modal_contains_product_name_for_zero_stock()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Unique Product Name 12345',
        ]);

        $view = View::make('components.delete-confirmation-modal', [
            'productId' => $product->id,
            'productName' => $product->name,
            'stockQuantity' => 0,
            'deleteRoute' => route('staff.products.destroy', $product),
        ]);

        $html = $view->render();

        // Verify product name is displayed
        $this->assertStringContainsString('Unique Product Name 12345', $html);
    }

    public function test_modal_has_delete_and_cancel_buttons_for_zero_stock()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
        ]);

        $view = View::make('components.delete-confirmation-modal', [
            'productId' => $product->id,
            'productName' => $product->name,
            'stockQuantity' => 0,
            'deleteRoute' => route('staff.products.destroy', $product),
        ]);

        $html = $view->render();

        // Verify both buttons are present
        $this->assertStringContainsString('Delete Product', $html);
        $this->assertStringContainsString('Cancel', $html);
        
        // Verify delete button has destructive styling
        $this->assertStringContainsString('bg-red-600', $html);
    }
}
