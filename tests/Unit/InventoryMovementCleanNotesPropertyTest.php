<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryMovementCleanNotesPropertyTest extends TestCase
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
     * Feature: inventory-movements-display-improvement, Property 6: Clean notes separation
     * Validates: Requirements 3.2
     * 
     * @test
     */
    public function property_clean_notes_removes_structured_data()
    {
        // Run 100 iterations with various combinations of structured data
        for ($i = 0; $i < 100; $i++) {
            // Generate random clean text
            $cleanTexts = [
                'Manual adjustment made',
                'Product was damaged',
                'Customer requested return',
                'Inventory count correction',
                'Quality issue found',
            ];

            $cleanText = fake()->randomElement($cleanTexts);

            // Generate random structured data
            $reasons = ['Damaged', 'Expired', 'Quality issue', 'Customer complaint'];
            $reason = fake()->randomElement($reasons);
            
            $year = fake()->numberBetween(2020, 2030);
            $month = str_pad(fake()->numberBetween(1, 12), 2, '0', STR_PAD_LEFT);
            $day = str_pad(fake()->numberBetween(1, 28), 2, '0', STR_PAD_LEFT);
            $sequence = str_pad(fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);
            $transactionId = "WI-{$year}{$month}{$day}-{$sequence}";

            // Create notes with various combinations
            $notePatterns = [
                "{$cleanText} (Reason: {$reason})",
                "Reserved for walk-in transaction: {$transactionId} {$cleanText}",
                "{$cleanText} Walk-in transaction completed: {$transactionId}",
                "{$cleanText} (Reason: {$reason}) Reserved for walk-in transaction: {$transactionId}",
                "Released from walk-in transaction: {$transactionId} {$cleanText} (Reason: {$reason})",
            ];

            $notes = fake()->randomElement($notePatterns);

            // Create movement with the notes
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => fake()->randomElement(['sale', 'reservation', 'adjustment']),
                'quantity' => fake()->numberBetween(-100, 100),
                'notes' => $notes,
                'performed_by' => $this->user->id,
            ]);

            // Property: clean_notes should contain only the clean text without structured data
            $cleanNotes = $movement->clean_notes;

            $this->assertNotNull(
                $cleanNotes,
                "Iteration {$i}: Clean notes should not be null for notes: {$notes}"
            );

            // Property: clean notes should contain the original clean text
            $this->assertStringContainsString(
                $cleanText,
                $cleanNotes,
                "Iteration {$i}: Clean notes should contain the original clean text"
            );

            // Property: clean notes should NOT contain the reason pattern
            $this->assertStringNotContainsString(
                "(Reason:",
                $cleanNotes,
                "Iteration {$i}: Clean notes should not contain reason pattern"
            );

            // Property: clean notes should NOT contain transaction reference patterns
            $this->assertStringNotContainsString(
                "Reserved for walk-in transaction:",
                $cleanNotes,
                "Iteration {$i}: Clean notes should not contain 'Reserved for walk-in transaction:'"
            );

            $this->assertStringNotContainsString(
                "Walk-in transaction completed:",
                $cleanNotes,
                "Iteration {$i}: Clean notes should not contain 'Walk-in transaction completed:'"
            );

            $this->assertStringNotContainsString(
                "Released from walk-in transaction:",
                $cleanNotes,
                "Iteration {$i}: Clean notes should not contain 'Released from walk-in transaction:'"
            );

            // Clean up
            $movement->delete();
        }
    }

    /**
     * Feature: inventory-movements-display-improvement, Property 6: Clean notes separation
     * Edge case: Notes with only structured data
     * 
     * @test
     */
    public function property_clean_notes_returns_null_when_only_structured_data()
    {
        // Run 50 iterations with notes containing only structured data
        for ($i = 0; $i < 50; $i++) {
            $year = fake()->numberBetween(2020, 2030);
            $month = str_pad(fake()->numberBetween(1, 12), 2, '0', STR_PAD_LEFT);
            $day = str_pad(fake()->numberBetween(1, 28), 2, '0', STR_PAD_LEFT);
            $sequence = str_pad(fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);
            $transactionId = "WI-{$year}{$month}{$day}-{$sequence}";

            $notesOnlyStructured = [
                "(Reason: Damaged)",
                "Reserved for walk-in transaction: {$transactionId}",
                "Walk-in transaction completed: {$transactionId}",
                "(Reason: Expired) Reserved for walk-in transaction: {$transactionId}",
            ];

            $notes = fake()->randomElement($notesOnlyStructured);

            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => fake()->randomElement(['reservation', 'release', 'sale']),
                'quantity' => fake()->numberBetween(-100, 100),
                'notes' => $notes,
                'performed_by' => $this->user->id,
            ]);

            // Property: clean_notes should be null when notes contain only structured data
            $cleanNotes = $movement->clean_notes;

            $this->assertNull(
                $cleanNotes,
                "Iteration {$i}: Clean notes should be null when notes contain only structured data: {$notes}"
            );

            // Clean up
            $movement->delete();
        }
    }

    /**
     * Feature: inventory-movements-display-improvement, Property 6: Clean notes separation
     * Edge case: Empty or null notes
     * 
     * @test
     */
    public function property_clean_notes_returns_null_for_empty_notes()
    {
        // Run 30 iterations with empty or null notes
        for ($i = 0; $i < 30; $i++) {
            $emptyNotes = [null, '', '   '];
            $notes = fake()->randomElement($emptyNotes);

            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => fake()->randomElement(['purchase', 'sale', 'adjustment']),
                'quantity' => fake()->numberBetween(-100, 100),
                'notes' => $notes,
                'performed_by' => $this->user->id,
            ]);

            // Property: clean_notes should be null for empty notes
            $cleanNotes = $movement->clean_notes;

            $this->assertNull(
                $cleanNotes,
                "Iteration {$i}: Clean notes should be null for empty notes"
            );

            // Clean up
            $movement->delete();
        }
    }
}
