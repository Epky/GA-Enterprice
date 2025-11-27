<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

/**
 * Feature: dashboard-movements-widget-improvement, Property 5: Reason badge display
 * 
 * For any movement with notes containing a reason pattern,
 * the rendered output should include a badge element displaying the extracted reason text
 * 
 * Validates: Requirements 2.2
 */
class DashboardReasonBadgeDisplayPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: Reason badge display
     * 
     * For any movement with notes containing a reason,
     * when rendered with the movement-notes component,
     * the output should contain a badge with the reason text
     */
    public function test_reason_creates_badge_display(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Generate random reason
            $reasons = ['Damaged', 'Expired', 'Customer Return', 'Quality Issue', 'Overstock'];
            $reason = $reasons[array_rand($reasons)];
            
            // Create a product and inventory
            $product = Product::factory()->create();
            Inventory::factory()->create([
                'product_id' => $product->id,
                'variant_id' => null
            ]);
            
            // Create movement with reason in notes (using correct format with parentheses)
            $movement = InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'variant_id' => null,
                'movement_type' => 'adjustment',
                'quantity' => -rand(1, 10),
                'notes' => "(Reason: {$reason})"
            ]);
            
            // Render the movement-notes component
            $rendered = Blade::render(
                '<x-movement-notes :movement="$movement" />',
                ['movement' => $movement]
            );
            
            // Property: The rendered output should contain the reason text
            // The component extracts reasons via the model's accessor
            $this->assertStringContainsString($reason, $rendered,
                "Reason '{$reason}' should appear in rendered output");
            
            // Property: When a reason is present, it should be displayed in a badge
            // Check for badge-related classes
            $hasReasonBadge = str_contains($rendered, 'bg-blue-100') && str_contains($rendered, 'text-blue-800');
            $this->assertTrue($hasReasonBadge,
                "Rendered output should contain reason badge styling when reason is present");
        }
    }

    /**
     * Property: Movements without reasons should not have reason badges
     */
    public function test_movements_without_reason_have_no_badge(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create a product and inventory
            $product = Product::factory()->create();
            Inventory::factory()->create([
                'product_id' => $product->id,
                'variant_id' => null
            ]);
            
            // Generate random notes without reason
            $notesOptions = [
                'Regular stock update',
                'Inventory count',
                'Manual entry',
                null
            ];
            $notes = $notesOptions[array_rand($notesOptions)];
            
            // Create movement without reason
            $movement = InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'variant_id' => null,
                'movement_type' => 'adjustment',
                'quantity' => rand(-10, 10),
                'notes' => $notes
            ]);
            
            // Render the movement-notes component
            $rendered = Blade::render(
                '<x-movement-notes :movement="$movement" />',
                ['movement' => $movement]
            );
            
            // Property: Should not contain "Reason:" text
            $this->assertStringNotContainsString('Reason:', $rendered,
                "Movements without reasons should not show 'Reason:' text");
        }
    }
}
