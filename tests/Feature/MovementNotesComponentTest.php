<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\InventoryMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

class MovementNotesComponentTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffUser = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);

        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
    }

    public function test_component_displays_reason_badge()
    {
        $movement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'adjustment',
            'quantity' => 10,
            'notes' => 'Stock correction (Reason: Inventory audit)',
            'performed_by' => $this->staffUser->id,
        ]);

        $rendered = Blade::render(
            '<x-movement-notes :movement="$movement" />',
            ['movement' => $movement]
        );

        $this->assertStringContainsString('Inventory audit', $rendered);
        $this->assertStringContainsString('bg-blue-100', $rendered);
    }

    public function test_component_displays_transaction_reference_as_link()
    {
        $movement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'sale',
            'quantity' => -5,
            'notes' => 'Walk-in transaction completed: WI-20251125-0001',
            'performed_by' => $this->staffUser->id,
        ]);

        $rendered = Blade::render(
            '<x-movement-notes :movement="$movement" />',
            ['movement' => $movement]
        );

        $this->assertStringContainsString('WI-20251125-0001', $rendered);
        $this->assertStringContainsString('bg-purple-100', $rendered);
    }

    public function test_component_displays_clean_notes()
    {
        $movement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'adjustment',
            'quantity' => 5,
            'notes' => 'Found extra stock during audit (Reason: Physical count)',
            'performed_by' => $this->staffUser->id,
        ]);

        $rendered = Blade::render(
            '<x-movement-notes :movement="$movement" />',
            ['movement' => $movement]
        );

        $this->assertStringContainsString('Physical count', $rendered);
        $this->assertStringContainsString('Found extra stock during audit', $rendered);
    }

    public function test_component_displays_placeholder_for_empty_notes()
    {
        $movement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'purchase',
            'quantity' => 20,
            'notes' => null,
            'performed_by' => $this->staffUser->id,
        ]);

        $rendered = Blade::render(
            '<x-movement-notes :movement="$movement" />',
            ['movement' => $movement]
        );

        $this->assertStringContainsString('No notes', $rendered);
        $this->assertStringContainsString('italic', $rendered);
    }

    public function test_component_handles_compact_mode()
    {
        $movement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'adjustment',
            'quantity' => 10,
            'notes' => 'Test note (Reason: Testing)',
            'performed_by' => $this->staffUser->id,
        ]);

        $rendered = Blade::render(
            '<x-movement-notes :movement="$movement" :compact="true" />',
            ['movement' => $movement]
        );

        $this->assertStringContainsString('Testing', $rendered);
        $this->assertStringContainsString('text-xs', $rendered);
    }

    public function test_component_separates_structured_data_from_notes()
    {
        $movement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'sale',
            'quantity' => -3,
            'notes' => 'Customer purchase Walk-in transaction completed: WI-20251125-0002 (Reason: Walk-in sale)',
            'performed_by' => $this->staffUser->id,
        ]);

        $rendered = Blade::render(
            '<x-movement-notes :movement="$movement" />',
            ['movement' => $movement]
        );

        // Should display reason badge
        $this->assertStringContainsString('Walk-in sale', $rendered);
        
        // Should display transaction reference
        $this->assertStringContainsString('WI-20251125-0002', $rendered);
        
        // Should display clean notes without structured data
        $this->assertStringContainsString('Customer purchase', $rendered);
    }
}
