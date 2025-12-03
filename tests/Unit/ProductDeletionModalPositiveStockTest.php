<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;

class ProductDeletionModalPositiveStockTest extends TestCase
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

    public function test_modal_renders_with_warning_for_positive_stock()
    {
        // Create a product with inventory
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 50,
            'quantity_reserved' => 10,
        ]);

        $stockQuantity = $product->fresh()->total_stock;

        // Render the modal component
        $view = View::make('components.delete-confirmation-modal', [
            'productId' => $product->id,
            'productName' => $product->name,
            'stockQuantity' => $stockQuantity,
            'deleteRoute' => route('staff.products.destroy', $product),
        ]);

        $html = $view->render();

        // Verify warning message appears
        $this->assertStringContainsString('Warning: Product has stock', $html);
        $this->assertStringContainsString('60 units', $html);
        $this->assertStringContainsString('bg-red-50', $html);
        
        // Verify standard confirmation message does NOT appear
        $this->assertStringNotContainsString('This action cannot be undone. This will permanently delete the product and all associated data.', $html);
    }

    public function test_modal_displays_correct_stock_quantity()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 25,
            'quantity_reserved' => 5,
        ]);

        $stockQuantity = $product->fresh()->total_stock; // Should be 30

        $view = View::make('components.delete-confirmation-modal', [
            'productId' => $product->id,
            'productName' => $product->name,
            'stockQuantity' => $stockQuantity,
            'deleteRoute' => route('staff.products.destroy', $product),
        ]);

        $html = $view->render();

        // Verify the exact stock quantity is displayed
        $this->assertStringContainsString('30 units', $html);
    }

    public function test_modal_uses_red_icon_for_positive_stock()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 100,
        ]);

        $stockQuantity = $product->fresh()->total_stock;

        $view = View::make('components.delete-confirmation-modal', [
            'productId' => $product->id,
            'productName' => $product->name,
            'stockQuantity' => $stockQuantity,
            'deleteRoute' => route('staff.products.destroy', $product),
        ]);

        $html = $view->render();

        // Verify red icon is used (not yellow)
        $this->assertStringContainsString('bg-red-100', $html);
        $this->assertStringContainsString('text-red-600', $html);
        $this->assertStringNotContainsString('bg-yellow-100', $html);
    }

    public function test_modal_warning_mentions_permanent_deletion()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 75,
        ]);

        $stockQuantity = $product->fresh()->total_stock;

        $view = View::make('components.delete-confirmation-modal', [
            'productId' => $product->id,
            'productName' => $product->name,
            'stockQuantity' => $stockQuantity,
            'deleteRoute' => route('staff.products.destroy', $product),
        ]);

        $html = $view->render();

        // Verify warning mentions permanent deletion and cannot be undone
        $this->assertStringContainsString('permanently remove all stock records', $html);
        $this->assertStringContainsString('cannot be undone', $html);
    }

    public function test_modal_contains_product_name_for_positive_stock()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Special Product XYZ',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 10,
        ]);

        $stockQuantity = $product->fresh()->total_stock;

        $view = View::make('components.delete-confirmation-modal', [
            'productId' => $product->id,
            'productName' => $product->name,
            'stockQuantity' => $stockQuantity,
            'deleteRoute' => route('staff.products.destroy', $product),
        ]);

        $html = $view->render();

        // Verify product name is displayed
        $this->assertStringContainsString('Special Product XYZ', $html);
    }

    public function test_modal_has_delete_and_cancel_buttons_for_positive_stock()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
        ]);

        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 50,
        ]);

        $stockQuantity = $product->fresh()->total_stock;

        $view = View::make('components.delete-confirmation-modal', [
            'productId' => $product->id,
            'productName' => $product->name,
            'stockQuantity' => $stockQuantity,
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
