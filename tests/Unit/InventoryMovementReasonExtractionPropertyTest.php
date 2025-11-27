<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryMovementReasonExtractionPropertyTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Brand $brand;
    private Product $product;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->brand = Brand::factory()->create(['is_active' => true]);
        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'status' => 'active',
        ]);
        $this->user = User::factory()->create(['role' => 'staff']);
    }

    /**
     * Feature: inventory-movements-display-improvement, Property 5: Reason extraction from notes
     * Validates: Requirements 3.1
     * 
     * @test
     */
    public function property_reason_extraction_from_notes()
    {
        // Run 100 iterations with random reasons
        for ($i = 0; $i < 100; $i++) {
            // Generate random reason text
            $reasonTexts = [
                'Damaged during shipping',
                'Customer complaint',
                'Expired product',
                'Stock count correction',
                'Quality control failure',
                'Warehouse reorganization',
                'Seasonal clearance',
                'Promotional event',
            ];

            $reasonText = fake()->randomElement($reasonTexts);

            // Generate notes with reason in various formats
            $notePatterns = [
                "(Reason: {$reasonText})",
                "Some text before (Reason: {$reasonText})",
                "(Reason: {$reasonText}) and some text after",
                "Text before (Reason: {$reasonText}) and after",
                "Multiple words (Reason: {$reasonText}) more text",
            ];

            $notes = fake()->randomElement($notePatterns);

            // Create movement with the notes
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => fake()->randomElement(['adjustment', 'damage', 'return']),
                'quantity' => fake()->numberBetween(-100, 100),
                'notes' => $notes,
                'performed_by' => $this->user->id,
            ]);

            // Property: reason accessor should extract the reason text correctly
            $extractedReason = $movement->reason;

            $this->assertNotNull(
                $extractedReason,
                "Iteration {$i}: Reason should not be null for notes: {$notes}"
            );

            $this->assertEquals(
                $reasonText,
                $extractedReason,
                "Iteration {$i}: Extracted reason should match the original reason text"
            );

            // Clean up
            $movement->delete();
        }
    }

    /**
     * Feature: inventory-movements-display-improvement, Property 5: Reason extraction from notes
     * Edge case: Notes without reasons
     * 
     * @test
     */
    public function property_reason_returns_null_for_notes_without_reasons()
    {
        // Run 50 iterations with notes that don't contain reasons
        for ($i = 0; $i < 50; $i++) {
            $notesWithoutReason = [
                'Manual adjustment',
                'Reserved for walk-in transaction: WI-20251125-0001',
                'Walk-in transaction completed: WI-20251125-0001',
                'Some random text',
                '',
                null,
            ];

            $notes = fake()->randomElement($notesWithoutReason);

            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => fake()->randomElement(['sale', 'purchase', 'transfer']),
                'quantity' => fake()->numberBetween(-100, 100),
                'notes' => $notes,
                'performed_by' => $this->user->id,
            ]);

            // Property: reason should be null when notes don't contain a reason
            $extractedReason = $movement->reason;

            $this->assertNull(
                $extractedReason,
                "Iteration {$i}: Reason should be null for notes without reason pattern: " . ($notes ?? 'null')
            );

            // Clean up
            $movement->delete();
        }
    }

    /**
     * Feature: inventory-movements-display-improvement, Property 5: Reason extraction from notes
     * Edge case: Reason with extra whitespace
     * 
     * @test
     */
    public function property_reason_extraction_handles_whitespace()
    {
        // Run 30 iterations with reasons that have extra whitespace
        for ($i = 0; $i < 30; $i++) {
            $reasonText = 'Test reason';
            
            // Add random whitespace around the reason
            $spaces = str_repeat(' ', fake()->numberBetween(1, 5));
            $notes = "(Reason:{$spaces}{$reasonText})";

            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => 'adjustment',
                'quantity' => fake()->numberBetween(-100, 100),
                'notes' => $notes,
                'performed_by' => $this->user->id,
            ]);

            // Property: reason should be trimmed of whitespace
            $extractedReason = $movement->reason;

            $this->assertEquals(
                $reasonText,
                $extractedReason,
                "Iteration {$i}: Extracted reason should be trimmed of whitespace"
            );

            // Clean up
            $movement->delete();
        }
    }
}
