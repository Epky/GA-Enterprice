<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Order;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

/**
 * Feature: dashboard-movements-widget-improvement, Property 4: Transaction reference linking
 * 
 * For any movement with notes containing a transaction reference pattern,
 * the rendered output should include a clickable link with the correct href to that transaction
 * 
 * Validates: Requirements 2.1
 */
class DashboardTransactionReferenceLinkingPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: Transaction reference linking
     * 
     * For any movement with notes containing a transaction reference,
     * when rendered with the movement-notes component,
     * the output should contain a clickable link with the correct href
     */
    public function test_transaction_reference_creates_clickable_link(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Generate random transaction reference
            $transactionId = 'WI-' . now()->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Create an order with this transaction reference
            $order = Order::factory()->create([
                'order_number' => $transactionId,
                'order_type' => 'walk_in'
            ]);
            
            // Create a product and inventory
            $product = Product::factory()->create();
            Inventory::factory()->create([
                'product_id' => $product->id,
                'variant_id' => null
            ]);
            
            // Create movement with transaction reference in notes
            $movement = InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'variant_id' => null,
                'movement_type' => 'sale',
                'quantity' => -rand(1, 10),
                'notes' => "Transaction: {$transactionId} - Customer purchase"
            ]);
            
            // Render the movement-notes component
            $rendered = Blade::render(
                '<x-movement-notes :movement="$movement" />',
                ['movement' => $movement]
            );
            
            // Property: The rendered output should contain a link to the transaction
            $expectedHref = route('staff.walk-in-transaction.show', ['order' => $order->id]);
            
            $this->assertStringContainsString($transactionId, $rendered, 
                "Transaction reference {$transactionId} should appear in rendered output");
            
            $this->assertStringContainsString('href=', $rendered,
                "Rendered output should contain a link element");
            
            $this->assertStringContainsString($expectedHref, $rendered,
                "Link should point to the correct transaction details page");
        }
    }

    /**
     * Property: Movements without transaction references should not have links
     */
    public function test_movements_without_transaction_reference_have_no_link(): void
    {
        // Run property test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create a product and inventory
            $product = Product::factory()->create();
            Inventory::factory()->create([
                'product_id' => $product->id,
                'variant_id' => null
            ]);
            
            // Generate random notes without transaction reference
            $notesOptions = [
                'Manual adjustment',
                'Damaged item',
                'Stock correction',
                'Inventory count adjustment',
                null
            ];
            $notes = $notesOptions[array_rand($notesOptions)];
            
            // Create movement without transaction reference
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
            
            // Property: Should not contain transaction-related links
            $this->assertStringNotContainsString('walk-in-transaction.show', $rendered,
                "Movements without transaction references should not have transaction links");
        }
    }
}
