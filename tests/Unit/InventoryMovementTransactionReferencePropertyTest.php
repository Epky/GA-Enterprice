<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryMovementTransactionReferencePropertyTest extends TestCase
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
     * Feature: inventory-movements-display-improvement, Property 4: Transaction reference extraction
     * Validates: Requirements 2.4
     * 
     * @test
     */
    public function property_transaction_reference_extraction_from_notes()
    {
        // Run 100 iterations with random walk-in transaction IDs
        for ($i = 0; $i < 100; $i++) {
            // Generate random walk-in transaction ID: WI-YYYYMMDD-NNNN
            $year = fake()->numberBetween(2020, 2030);
            $month = str_pad(fake()->numberBetween(1, 12), 2, '0', STR_PAD_LEFT);
            $day = str_pad(fake()->numberBetween(1, 28), 2, '0', STR_PAD_LEFT);
            $sequence = str_pad(fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);
            $transactionId = "WI-{$year}{$month}{$day}-{$sequence}";

            // Generate random note patterns that contain the transaction ID
            $notePatterns = [
                "Reserved for walk-in transaction: {$transactionId}",
                "Walk-in transaction completed: {$transactionId}",
                "Released from walk-in transaction: {$transactionId}",
                "Some text before {$transactionId} and after",
                "{$transactionId}",
            ];

            $notes = fake()->randomElement($notePatterns);

            // Create movement with the notes
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => fake()->randomElement(['sale', 'reservation', 'release']),
                'quantity' => fake()->numberBetween(-100, 100),
                'notes' => $notes,
                'performed_by' => $this->user->id,
            ]);

            // Property: transaction_reference accessor should extract the transaction ID correctly
            $transactionRef = $movement->transaction_reference;

            $this->assertNotNull(
                $transactionRef,
                "Iteration {$i}: Transaction reference should not be null for notes: {$notes}"
            );

            $this->assertIsArray(
                $transactionRef,
                "Iteration {$i}: Transaction reference should be an array"
            );

            $this->assertArrayHasKey(
                'type',
                $transactionRef,
                "Iteration {$i}: Transaction reference should have 'type' key"
            );

            $this->assertArrayHasKey(
                'id',
                $transactionRef,
                "Iteration {$i}: Transaction reference should have 'id' key"
            );

            $this->assertEquals(
                'walk-in',
                $transactionRef['type'],
                "Iteration {$i}: Transaction reference type should be 'walk-in'"
            );

            $this->assertEquals(
                $transactionId,
                $transactionRef['id'],
                "Iteration {$i}: Transaction reference ID should match the generated ID"
            );

            // Clean up
            $movement->delete();
        }
    }

    /**
     * Feature: inventory-movements-display-improvement, Property 4: Transaction reference extraction
     * Edge case: Notes without transaction references
     * 
     * @test
     */
    public function property_transaction_reference_returns_null_for_notes_without_references()
    {
        // Run 50 iterations with notes that don't contain transaction references
        for ($i = 0; $i < 50; $i++) {
            $notesWithoutRef = [
                'Manual adjustment',
                'Damaged during shipping',
                'Customer return',
                'Stock count correction',
                '',
                null,
                'Some random text without any transaction ID',
            ];

            $notes = fake()->randomElement($notesWithoutRef);

            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'movement_type' => fake()->randomElement(['adjustment', 'damage', 'return']),
                'quantity' => fake()->numberBetween(-100, 100),
                'notes' => $notes,
                'performed_by' => $this->user->id,
            ]);

            // Property: transaction_reference should be null when notes don't contain a reference
            $transactionRef = $movement->transaction_reference;

            $this->assertNull(
                $transactionRef,
                "Iteration {$i}: Transaction reference should be null for notes without references: " . ($notes ?? 'null')
            );

            // Clean up
            $movement->delete();
        }
    }
}
