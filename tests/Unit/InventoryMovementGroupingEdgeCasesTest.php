<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InventoryService;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for movement grouping edge cases
 * 
 * Validates: Requirements 2.1
 */
class InventoryMovementGroupingEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;
    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inventoryService = new InventoryService();
        $this->user = User::factory()->create();
        $this->product = Product::factory()->create();
    }

    /**
     * Test movements without transaction references remain ungrouped
     */
    public function test_movements_without_transaction_references_remain_ungrouped(): void
    {
        // Create movements without transaction references
        $movement1 = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'purchase',
            'quantity' => 10,
            'notes' => 'Regular purchase without transaction reference',
            'performed_by' => $this->user->id,
        ]);

        $movement2 = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'adjustment',
            'quantity' => 5,
            'notes' => 'Stock adjustment',
            'performed_by' => $this->user->id,
        ]);

        $movements = collect([$movement1, $movement2]);

        // Apply grouping
        $grouped = $this->inventoryService->groupRelatedMovements($movements);

        // Assert both movements are ungrouped
        $this->assertCount(2, $grouped);
        
        foreach ($grouped as $group) {
            $this->assertNull($group['transaction_ref']);
            $this->assertEmpty($group['related']);
            $this->assertNotNull($group['primary']);
        }
    }

    /**
     * Test movements with same reference are grouped together
     */
    public function test_movements_with_same_reference_are_grouped_together(): void
    {
        $transactionId = 'WI-20251125-0001';

        // Create a business movement with transaction reference
        $saleMovement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'sale',
            'quantity' => -5,
            'notes' => "Walk-in transaction completed: {$transactionId}",
            'performed_by' => $this->user->id,
        ]);

        // Create related system movements
        $reservationMovement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'reservation',
            'quantity' => -5,
            'notes' => "Reserved for walk-in transaction: {$transactionId}",
            'performed_by' => $this->user->id,
        ]);

        $releaseMovement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'release',
            'quantity' => 5,
            'notes' => "Released from walk-in transaction: {$transactionId}",
            'performed_by' => $this->user->id,
        ]);

        $movements = collect([$saleMovement, $reservationMovement, $releaseMovement]);

        // Apply grouping
        $grouped = $this->inventoryService->groupRelatedMovements($movements);

        // Assert movements are grouped
        $this->assertCount(1, $grouped);
        
        $group = $grouped->first();
        $this->assertEquals($saleMovement->id, $group['primary']->id);
        $this->assertEquals($transactionId, $group['transaction_ref']);
        $this->assertCount(2, $group['related']);
        
        $relatedIds = $group['related']->pluck('id')->toArray();
        $this->assertContains($reservationMovement->id, $relatedIds);
        $this->assertContains($releaseMovement->id, $relatedIds);
    }

    /**
     * Test mixed business and system movements
     */
    public function test_mixed_business_and_system_movements(): void
    {
        $transactionId = 'WI-20251125-0002';

        // Create business movement with transaction reference
        $saleMovement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'sale',
            'quantity' => -3,
            'notes' => "Walk-in transaction completed: {$transactionId}",
            'performed_by' => $this->user->id,
        ]);

        // Create related system movement
        $reservationMovement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'reservation',
            'quantity' => -3,
            'notes' => "Reserved for walk-in transaction: {$transactionId}",
            'performed_by' => $this->user->id,
        ]);

        // Create unrelated business movement
        $purchaseMovement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'purchase',
            'quantity' => 20,
            'notes' => 'Regular purchase',
            'performed_by' => $this->user->id,
        ]);

        // Create orphaned system movement (no matching business movement)
        $orphanedReservation = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'reservation',
            'quantity' => -2,
            'notes' => 'Reserved for walk-in transaction: WI-20251125-9999',
            'performed_by' => $this->user->id,
        ]);

        $movements = collect([$saleMovement, $reservationMovement, $purchaseMovement, $orphanedReservation]);

        // Apply grouping
        $grouped = $this->inventoryService->groupRelatedMovements($movements);

        // Assert correct grouping
        $this->assertCount(3, $grouped);

        // Find the grouped sale movement
        $saleGroup = $grouped->first(fn($g) => $g['primary']->id === $saleMovement->id);
        $this->assertNotNull($saleGroup);
        $this->assertEquals($transactionId, $saleGroup['transaction_ref']);
        $this->assertCount(1, $saleGroup['related']);
        $this->assertEquals($reservationMovement->id, $saleGroup['related']->first()->id);

        // Find the ungrouped purchase movement
        $purchaseGroup = $grouped->first(fn($g) => $g['primary']->id === $purchaseMovement->id);
        $this->assertNotNull($purchaseGroup);
        $this->assertNull($purchaseGroup['transaction_ref']);
        $this->assertEmpty($purchaseGroup['related']);

        // Find the orphaned reservation
        $orphanedGroup = $grouped->first(fn($g) => $g['primary']->id === $orphanedReservation->id);
        $this->assertNotNull($orphanedGroup);
        $this->assertNull($orphanedGroup['transaction_ref']);
        $this->assertEmpty($orphanedGroup['related']);
    }

    /**
     * Test empty collection
     */
    public function test_empty_collection_returns_empty_result(): void
    {
        $movements = collect();

        $grouped = $this->inventoryService->groupRelatedMovements($movements);

        $this->assertCount(0, $grouped);
    }

    /**
     * Test single movement without transaction reference
     */
    public function test_single_movement_without_transaction_reference(): void
    {
        $movement = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'purchase',
            'quantity' => 10,
            'notes' => 'Single purchase',
            'performed_by' => $this->user->id,
        ]);

        $movements = collect([$movement]);

        $grouped = $this->inventoryService->groupRelatedMovements($movements);

        $this->assertCount(1, $grouped);
        $group = $grouped->first();
        $this->assertEquals($movement->id, $group['primary']->id);
        $this->assertNull($group['transaction_ref']);
        $this->assertEmpty($group['related']);
    }

    /**
     * Test movements for different products are not grouped together
     */
    public function test_movements_for_different_products_are_not_grouped(): void
    {
        $product2 = Product::factory()->create();
        $transactionId = 'WI-20251125-0003';

        // Create movements for product 1
        $sale1 = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'sale',
            'quantity' => -2,
            'notes' => "Walk-in transaction completed: {$transactionId}",
            'performed_by' => $this->user->id,
        ]);

        $reservation1 = InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'movement_type' => 'reservation',
            'quantity' => -2,
            'notes' => "Reserved for walk-in transaction: {$transactionId}",
            'performed_by' => $this->user->id,
        ]);

        // Create movements for product 2 with same transaction ID
        $sale2 = InventoryMovement::factory()->create([
            'product_id' => $product2->id,
            'movement_type' => 'sale',
            'quantity' => -3,
            'notes' => "Walk-in transaction completed: {$transactionId}",
            'performed_by' => $this->user->id,
        ]);

        $reservation2 = InventoryMovement::factory()->create([
            'product_id' => $product2->id,
            'movement_type' => 'reservation',
            'quantity' => -3,
            'notes' => "Reserved for walk-in transaction: {$transactionId}",
            'performed_by' => $this->user->id,
        ]);

        $movements = collect([$sale1, $reservation1, $sale2, $reservation2]);

        $grouped = $this->inventoryService->groupRelatedMovements($movements);

        // Should have 2 groups (one per product)
        $this->assertCount(2, $grouped);

        // Each group should only contain movements for its product
        foreach ($grouped as $group) {
            $this->assertCount(1, $group['related']);
            $this->assertEquals($group['primary']->product_id, $group['related']->first()->product_id);
        }
    }
}
