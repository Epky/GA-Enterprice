<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardWidgetVisibilityTest extends TestCase
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
     * Test widget is hidden when movements collection is empty
     * 
     * @test
     */
    public function test_widget_is_hidden_when_no_movements_exist()
    {
        // Act: Load the dashboard with no movements
        $response = $this->actingAs($this->staffUser)->get(route('staff.dashboard'));

        // Assert: Widget section should not be present (check for the time range indicator)
        $response->assertStatus(200);
        $response->assertDontSee('Last 7 days •');
    }

    /**
     * Test widget is shown when movements exist
     * 
     * @test
     */
    public function test_widget_is_shown_when_movements_exist()
    {
        // Arrange: Create a product with inventory and a business movement
        $product = Product::factory()->create(['status' => 'active']);
        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 100,
        ]);
        
        InventoryMovement::factory()->create([
            'product_id' => $product->id,
            'movement_type' => 'purchase',
            'quantity' => 50,
            'created_at' => now()->subDays(2),
        ]);

        // Act: Load the dashboard
        $response = $this->actingAs($this->staffUser)->get(route('staff.dashboard'));

        // Assert: Widget section should be present
        $response->assertStatus(200);
        $response->assertSee('Recent Inventory Movements');
        $response->assertSee('Last 7 days');
    }

    /**
     * Test "View All" link is present when widget is shown
     * 
     * @test
     */
    public function test_view_all_link_is_present_when_widget_is_shown()
    {
        // Arrange: Create a product with inventory and a business movement
        $product = Product::factory()->create(['status' => 'active']);
        Inventory::factory()->create([
            'product_id' => $product->id,
            'quantity_available' => 100,
        ]);
        
        InventoryMovement::factory()->create([
            'product_id' => $product->id,
            'movement_type' => 'sale',
            'quantity' => -10,
            'created_at' => now()->subDays(1),
        ]);

        // Act: Load the dashboard
        $response = $this->actingAs($this->staffUser)->get(route('staff.dashboard'));

        // Assert: "View All" link should be present in the movements widget context
        $response->assertStatus(200);
        $response->assertSee('Recent Inventory Movements');
        $response->assertSee(route('staff.inventory.movements'));
    }
}
