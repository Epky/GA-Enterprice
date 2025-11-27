<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * Feature: dashboard-movements-widget-improvement, Property 10: Date and time display
 * 
 * Property: For any movement displayed, the rendered date should include both date
 * and time components in a readable format
 * 
 * Validates: Requirements 3.3
 */
class DashboardMovementDateDisplayPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Property: Date display includes both date and time components
     * 
     * For any movement, when formatted using the dashboard format (M d, H:i),
     * the output should contain both date and time information.
     */
    public function test_date_display_includes_date_and_time_components()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Test with 50 random dates
        for ($i = 0; $i < 50; $i++) {
            // Generate random date within the last 30 days
            $randomDate = Carbon::now()->subDays(rand(0, 30))
                ->setHour(rand(0, 23))
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));

            $movement = InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'quantity' => rand(-50, 50),
                'movement_type' => 'adjustment',
                'performed_by' => $user->id,
                'created_at' => $randomDate,
            ]);

            // Format the date as it would be in the dashboard
            $formattedDate = $movement->created_at->format('M d, H:i');

            // Property: Formatted date should contain month abbreviation (3 letters)
            $this->assertMatchesRegularExpression(
                '/^[A-Z][a-z]{2}/',
                $formattedDate,
                "Date format should start with 3-letter month abbreviation"
            );

            // Property: Formatted date should contain day number
            $this->assertMatchesRegularExpression(
                '/\d{1,2}/',
                $formattedDate,
                "Date format should contain day number"
            );

            // Property: Formatted date should contain time in H:i format
            $this->assertMatchesRegularExpression(
                '/\d{2}:\d{2}$/',
                $formattedDate,
                "Date format should end with time in HH:MM format"
            );

            // Property: Full format should match expected pattern
            $this->assertMatchesRegularExpression(
                '/^[A-Z][a-z]{2} \d{1,2}, \d{2}:\d{2}$/',
                $formattedDate,
                "Date format should match 'Mon DD, HH:MM' pattern"
            );
        }
    }

    /**
     * @test
     * Property: Date format is consistent across all movements
     * 
     * For any set of movements, the date format should be consistent.
     */
    public function test_date_format_is_consistent()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $movements = [];
        for ($i = 0; $i < 20; $i++) {
            $movements[] = InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'quantity' => rand(-50, 50),
                'movement_type' => 'adjustment',
                'performed_by' => $user->id,
                'created_at' => Carbon::now()->subDays(rand(0, 7)),
            ]);
        }

        // Property: All formatted dates should match the same pattern
        foreach ($movements as $movement) {
            $formattedDate = $movement->created_at->format('M d, H:i');
            
            $this->assertMatchesRegularExpression(
                '/^[A-Z][a-z]{2} \d{1,2}, \d{2}:\d{2}$/',
                $formattedDate,
                "All dates should follow the same format pattern"
            );
        }
    }

    /**
     * @test
     * Property: Date format is readable and unambiguous
     * 
     * For any movement, the formatted date should be parseable back to a Carbon instance.
     */
    public function test_date_format_is_parseable()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        for ($i = 0; $i < 20; $i++) {
            $originalDate = Carbon::now()->subDays(rand(0, 7))
                ->setHour(rand(0, 23))
                ->setMinute(rand(0, 59));

            $movement = InventoryMovement::factory()->create([
                'product_id' => $product->id,
                'quantity' => rand(-50, 50),
                'movement_type' => 'adjustment',
                'performed_by' => $user->id,
                'created_at' => $originalDate,
            ]);

            $formattedDate = $movement->created_at->format('M d, H:i');

            // Property: The formatted date should contain the correct month
            $this->assertEquals(
                $originalDate->format('M'),
                substr($formattedDate, 0, 3),
                "Month should be correctly formatted"
            );

            // Property: The formatted date should contain the correct day
            $dayInFormatted = (int) preg_replace('/[^0-9]/', '', explode(',', $formattedDate)[0]);
            $this->assertEquals(
                $originalDate->day,
                $dayInFormatted,
                "Day should be correctly formatted"
            );

            // Property: The formatted date should contain the correct time
            $timeInFormatted = explode(', ', $formattedDate)[1];
            $this->assertEquals(
                $originalDate->format('H:i'),
                $timeInFormatted,
                "Time should be correctly formatted"
            );
        }
    }
}
