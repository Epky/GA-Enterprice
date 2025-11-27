<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InventoryService;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

/**
 * Feature: inventory-movements-display-improvement, Property 3: Related movements grouping
 * 
 * For any sale movement with a transaction reference in its notes, calling groupRelatedMovements 
 * should return a structure containing that sale as the primary movement and any reservation/release 
 * movements with the same transaction reference as related movements
 * 
 * Validates: Requirements 2.1
 */
class InventoryMovementGroupingPropertyTest extends TestCase
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
     * Property Test: Related movements grouping
     * 
     * Generate multiple scenarios with business movements that have transaction references
     * and related system movements, then verify grouping is correct.
     */
    public function test_related_movements_are_grouped_by_transaction_reference(): void
    {
        // Run the property test multiple times with different data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Generate a random transaction reference
            $transactionId = $this->generateTransactionId();
            
            // Generate random number of products (1-3)
            $productCount = rand(1, 3);
            $products = [];
            for ($i = 0; $i < $productCount; $i++) {
                $products[] = Product::factory()->create();
            }

            $movements = collect();

            // For each product, create a business movement with transaction reference
            // and random number of related system movements (0-2)
            foreach ($products as $product) {
                // Create business movement (sale) with transaction reference
                $businessMovement = InventoryMovement::factory()->create([
                    'product_id' => $product->id,
                    'movement_type' => 'sale',
                    'quantity' => -rand(1, 10),
                    'notes' => "Walk-in transaction completed: {$transactionId}",
                    'performed_by' => $this->user->id,
                    'created_at' => now()->subMinutes(rand(1, 100)),
                ]);
                $movements->push($businessMovement);

                // Create random number of related system movements
                $relatedCount = rand(0, 2);
                for ($j = 0; $j < $relatedCount; $j++) {
                    $systemType = ['reservation', 'release'][rand(0, 1)];
                    $systemMovement = InventoryMovement::factory()->create([
                        'product_id' => $product->id,
                        'movement_type' => $systemType,
                        'quantity' => $systemType === 'reservation' ? -rand(1, 10) : rand(1, 10),
                        'notes' => $systemType === 'reservation' 
                            ? "Reserved for walk-in transaction: {$transactionId}"
                            : "Released from walk-in transaction: {$transactionId}",
                        'performed_by' => $this->user->id,
                        'created_at' => now()->subMinutes(rand(1, 100)),
                    ]);
                    $movements->push($systemMovement);
                }
            }

            // Also add some unrelated movements without transaction references
            $unrelatedCount = rand(0, 2);
            for ($i = 0; $i < $unrelatedCount; $i++) {
                $unrelatedProduct = Product::factory()->create();
                $unrelatedMovement = InventoryMovement::factory()->create([
                    'product_id' => $unrelatedProduct->id,
                    'movement_type' => ['purchase', 'adjustment'][rand(0, 1)],
                    'quantity' => rand(1, 10),
                    'notes' => 'Regular movement without transaction reference',
                    'performed_by' => $this->user->id,
                    'created_at' => now()->subMinutes(rand(1, 100)),
                ]);
                $movements->push($unrelatedMovement);
            }

            // Shuffle movements to simulate real-world ordering
            $movements = $movements->shuffle();

            // Apply grouping
            $grouped = $this->inventoryService->groupRelatedMovements($movements);

            // Verify properties
            $this->assertGroupingProperties($grouped, $movements, $transactionId, $products);

            // Clean up for next iteration
            InventoryMovement::query()->delete();
            Product::whereIn('id', collect($products)->pluck('id'))->delete();
        }
    }

    /**
     * Verify that the grouping satisfies all required properties
     */
    private function assertGroupingProperties(
        Collection $grouped, 
        Collection $originalMovements, 
        string $transactionId,
        array $products
    ): void {
        // Property 1: All original movements should be accounted for
        $allMovementsInGroups = $grouped->flatMap(function ($group) {
            return collect([$group['primary']])->merge($group['related']);
        });
        
        $this->assertEquals(
            $originalMovements->pluck('id')->sort()->values()->toArray(),
            $allMovementsInGroups->pluck('id')->sort()->values()->toArray(),
            'All movements should be present in grouped result'
        );

        // Property 2: Business movements with transaction references should be primary
        foreach ($grouped as $group) {
            $primary = $group['primary'];
            
            if ($primary->transaction_reference && $primary->isBusinessMovement()) {
                // This should have a transaction_ref set
                $this->assertNotNull($group['transaction_ref'], 'Business movement with transaction should have transaction_ref');
                $this->assertEquals($transactionId, $group['transaction_ref'], 'Transaction ref should match');
                
                // All related movements should be system movements with same transaction ref
                foreach ($group['related'] as $related) {
                    $this->assertTrue($related->isSystemMovement(), 'Related movements should be system movements');
                    $this->assertNotNull($related->transaction_reference, 'Related movement should have transaction reference');
                    $this->assertEquals($transactionId, $related->transaction_reference['id'], 'Related movement should have same transaction ID');
                    $this->assertEquals($primary->product_id, $related->product_id, 'Related movement should be for same product');
                }
            }
        }

        // Property 3: Movements without transaction references should be ungrouped
        foreach ($grouped as $group) {
            $primary = $group['primary'];
            
            if (!$primary->transaction_reference || $primary->isSystemMovement()) {
                $this->assertNull($group['transaction_ref'], 'Movement without transaction ref should have null transaction_ref');
                $this->assertEmpty($group['related'], 'Movement without transaction ref should have no related movements');
            }
        }

        // Property 4: Each movement should appear exactly once
        $movementIds = $allMovementsInGroups->pluck('id')->toArray();
        $uniqueIds = array_unique($movementIds);
        $this->assertEquals(count($movementIds), count($uniqueIds), 'Each movement should appear exactly once');

        // Property 5: Related movements for same product should be grouped together
        foreach ($products as $product) {
            $businessMovementsForProduct = $originalMovements->filter(function ($m) use ($product, $transactionId) {
                return $m->product_id === $product->id && 
                       $m->isBusinessMovement() && 
                       $m->transaction_reference &&
                       $m->transaction_reference['id'] === $transactionId;
            });

            $systemMovementsForProduct = $originalMovements->filter(function ($m) use ($product, $transactionId) {
                return $m->product_id === $product->id && 
                       $m->isSystemMovement() && 
                       $m->transaction_reference &&
                       $m->transaction_reference['id'] === $transactionId;
            });

            if ($businessMovementsForProduct->isNotEmpty()) {
                // Find the group for this business movement
                $group = $grouped->first(function ($g) use ($businessMovementsForProduct) {
                    return $businessMovementsForProduct->pluck('id')->contains($g['primary']->id);
                });

                if ($group) {
                    // All system movements for this product should be in related
                    $relatedIds = $group['related']->pluck('id')->toArray();
                    foreach ($systemMovementsForProduct as $systemMovement) {
                        $this->assertContains(
                            $systemMovement->id, 
                            $relatedIds, 
                            "System movement {$systemMovement->id} for product {$product->id} should be in related movements"
                        );
                    }
                }
            }
        }
    }

    /**
     * Generate a random walk-in transaction ID
     */
    private function generateTransactionId(): string
    {
        $date = now()->subDays(rand(0, 30))->format('Ymd');
        $sequence = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "WI-{$date}-{$sequence}";
    }
}
