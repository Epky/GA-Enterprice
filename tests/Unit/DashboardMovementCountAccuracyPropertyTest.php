<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: dashboard-movements-widget-improvement, Property 13: Movement count accuracy
 * Validates: Requirements 4.4
 */
class DashboardMovementCountAccuracyPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a staff user for authentication
        $this->staffUser = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Property 13: Movement count accuracy
     * For any dashboard display, if a count is shown, it should equal the actual number of movements displayed in the widget
     * 
     * @test
     */
    public function property_movement_count_equals_displayed_movements()
    {
        // Arrange: Create random number of business movements (between 1 and 15)
        $movementCount = rand(1, 15);
        $product = Product::factory()->create(['status' => 'active']);
        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 1000,
        ]);

        // Create movements well within the 7-day window (between 1-5 days ago)
        for ($i = 0; $i < $movementCount; $i++) {
            InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'movement_type' => fake()->randomElement(['purchase', 'sale', 'return', 'damage', 'adjustment', 'transfer']),
                'quantity' => rand(-50, 50),
                'created_at' => now()->subDays(rand(1, 5))->subHours(rand(0, 23)),
            ]);
        }

        // Act: Load the dashboard
        $response = $this->actingAs($this->staffUser)->get(route('staff.dashboard'));

        // Assert: The count shown should match the actual number of movements displayed
        // The widget shows max 10 movements, so expected count is min(movementCount, 10)
        $expectedDisplayCount = min($movementCount, 10);
        
        // Get the response content
        $content = $response->getContent();
        
        // Count how many movement rows are actually displayed in the table
        // Each movement row has a specific structure with product name, type badge, etc.
        $actualDisplayCount = substr_count($content, '<tr class="hover:bg-gray-50 transition-colors duration-150">');
        
        // The displayed count should match our expectation
        $this->assertEquals($expectedDisplayCount, $actualDisplayCount, 
            "Expected {$expectedDisplayCount} movements to be displayed, but found {$actualDisplayCount}");
    }

    /**
     * Property: Movement count is accurate when no movements exist
     * 
     * @test
     */
    public function property_movement_count_is_zero_when_no_movements()
    {
        // Act: Load the dashboard with no movements
        $response = $this->actingAs($this->staffUser)->get(route('staff.dashboard'));

        // Assert: No movement rows should be displayed
        $content = $response->getContent();
        $actualDisplayCount = substr_count($content, '<tr class="hover:bg-gray-50 transition-colors duration-150">');
        
        $this->assertEquals(0, $actualDisplayCount, 
            "Expected 0 movements to be displayed, but found {$actualDisplayCount}");
    }

    /**
     * Property: Movement count respects the 10-item limit
     * 
     * @test
     */
    public function property_movement_count_respects_limit()
    {
        // Arrange: Create more than 10 movements
        $movementCount = 25;
        $product = Product::factory()->create(['status' => 'active']);
        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 1000,
        ]);

        for ($i = 0; $i < $movementCount; $i++) {
            InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'movement_type' => 'purchase',
                'quantity' => 10,
                'created_at' => now()->subDays(rand(0, 6)),
            ]);
        }

        // Act: Load the dashboard
        $response = $this->actingAs($this->staffUser)->get(route('staff.dashboard'));

        // Assert: Only 10 movements should be displayed
        $content = $response->getContent();
        $actualDisplayCount = substr_count($content, '<tr class="hover:bg-gray-50 transition-colors duration-150">');
        
        $this->assertEquals(10, $actualDisplayCount, 
            "Expected exactly 10 movements to be displayed (limit), but found {$actualDisplayCount}");
    }

    /**
     * Property: Movement count only includes business movements
     * 
     * @test
     */
    public function property_movement_count_excludes_system_movements()
    {
        // Arrange: Create mix of business and system movements
        $product = Product::factory()->create(['status' => 'active']);
        $inventory = Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 1000,
        ]);

        // Create 3 business movements well within the last 7 days
        for ($i = 0; $i < 3; $i++) {
            InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'movement_type' => 'purchase',
                'quantity' => 10,
                'created_at' => now()->subDays(2)->subHours($i), // 2 days ago, staggered by hours
            ]);
        }

        // Create 10 system movements (should not be displayed)
        for ($i = 0; $i < 10; $i++) {
            InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'movement_type' => fake()->randomElement(['reservation', 'release']),
                'quantity' => 10,
                'created_at' => now()->subDays(2)->subHours($i), // 2 days ago, staggered by hours
            ]);
        }

        // Act: Load the dashboard
        $response = $this->actingAs($this->staffUser)->get(route('staff.dashboard'));

        // Assert: Only 3 business movements should be displayed (system movements excluded)
        $content = $response->getContent();
        $actualDisplayCount = substr_count($content, '<tr class="hover:bg-gray-50 transition-colors duration-150">');
        
        $this->assertEquals(3, $actualDisplayCount, 
            "Expected 3 business movements to be displayed (excluding system movements), but found {$actualDisplayCount}");
        
        // Also verify that system movement types are not present in the output
        $this->assertStringNotContainsString('Reservation', $content);
        $this->assertStringNotContainsString('Release', $content);
    }
}
