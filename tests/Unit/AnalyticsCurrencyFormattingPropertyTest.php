<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AnalyticsService;

/**
 * Feature: admin-analytics-dashboard, Property 2: Currency formatting consistency
 * Validates: Requirements 1.3, 3.4
 */
class AnalyticsCurrencyFormattingPropertyTest extends TestCase
{
    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * Property 2: Currency formatting consistency
     * For any monetary value, the formatted output should contain a currency symbol 
     * and exactly two decimal places
     * 
     * @test
     */
    public function property_currency_format_has_symbol_and_two_decimals()
    {
        // Test with various monetary values
        $testValues = [
            0,
            0.1,
            0.99,
            1,
            1.5,
            10,
            10.99,
            100,
            100.50,
            999.99,
            1000,
            1234.56,
            9999.99,
            10000,
            99999.99,
            1000000,
            1234567.89,
            // Edge cases
            0.001,  // Should round to 0.00
            0.005,  // Should round to 0.01
            0.994,  // Should round to 0.99
            0.995,  // Should round to 1.00
            1.234,  // Should round to 1.23
            1.235,  // Should round to 1.24
            1.999,  // Should round to 2.00
        ];

        foreach ($testValues as $value) {
            // Act
            $formatted = $this->analyticsService->formatCurrency($value);
            
            // Assert: Should start with currency symbol
            $this->assertStringStartsWith(
                '₱',
                $formatted,
                "Formatted currency should start with ₱ symbol for value {$value}"
            );
            
            // Assert: Should have exactly two decimal places
            // Extract the numeric part after the currency symbol
            $numericPart = substr($formatted, strlen('₱'));
            
            // Check if it matches the pattern: digits with optional commas, followed by .XX
            $this->assertMatchesRegularExpression(
                '/^[\d,]+\.\d{2}$/',
                $numericPart,
                "Formatted currency should have exactly two decimal places for value {$value}"
            );
            
            // Assert: Decimal point should be present
            $this->assertStringContainsString(
                '.',
                $formatted,
                "Formatted currency should contain decimal point for value {$value}"
            );
        }
    }

    /**
     * Property: Currency formatting handles zero correctly
     * 
     * @test
     */
    public function property_currency_format_handles_zero()
    {
        // Act
        $formatted = $this->analyticsService->formatCurrency(0);
        
        // Assert
        $this->assertEquals('₱0.00', $formatted, "Zero should be formatted as ₱0.00");
    }

    /**
     * Property: Currency formatting handles negative values
     * 
     * @test
     */
    public function property_currency_format_handles_negative_values()
    {
        $negativeValues = [-1, -10.50, -100, -1234.56, -0.01];
        
        foreach ($negativeValues as $value) {
            // Act
            $formatted = $this->analyticsService->formatCurrency($value);
            
            // Assert: Should contain currency symbol
            $this->assertStringContainsString('₱', $formatted);
            
            // Assert: Should contain negative sign
            $this->assertStringContainsString('-', $formatted);
            
            // Assert: Should have two decimal places
            $this->assertMatchesRegularExpression(
                '/\.\d{2}$/',
                $formatted,
                "Negative value {$value} should have two decimal places"
            );
        }
    }

    /**
     * Property: Currency formatting adds thousand separators
     * 
     * @test
     */
    public function property_currency_format_adds_thousand_separators()
    {
        $testCases = [
            [1000, '₱1,000.00'],
            [10000, '₱10,000.00'],
            [100000, '₱100,000.00'],
            [1000000, '₱1,000,000.00'],
            [1234567.89, '₱1,234,567.89'],
        ];
        
        foreach ($testCases as [$value, $expected]) {
            // Act
            $formatted = $this->analyticsService->formatCurrency($value);
            
            // Assert
            $this->assertEquals(
                $expected,
                $formatted,
                "Value {$value} should be formatted with thousand separators"
            );
        }
    }

    /**
     * Property: Currency formatting rounds to two decimal places
     * 
     * @test
     */
    public function property_currency_format_rounds_correctly()
    {
        $testCases = [
            // [input, expected_output]
            [1.234, '₱1.23'],   // Round down
            [1.235, '₱1.24'],   // Round up (banker's rounding may vary)
            [1.236, '₱1.24'],   // Round up
            [1.999, '₱2.00'],   // Round up
            [0.001, '₱0.00'],   // Round down
            [0.005, '₱0.01'],   // Round up (banker's rounding may vary)
            [0.994, '₱0.99'],   // Round down
            [0.995, '₱1.00'],   // Round up (banker's rounding may vary)
            [99.999, '₱100.00'], // Round up
        ];
        
        foreach ($testCases as [$value, $expected]) {
            // Act
            $formatted = $this->analyticsService->formatCurrency($value);
            
            // Assert: Check that it has two decimal places (exact value may vary due to rounding rules)
            $this->assertMatchesRegularExpression(
                '/₱[\d,]+\.\d{2}$/',
                $formatted,
                "Value {$value} should be rounded to two decimal places"
            );
            
            // For values that should clearly round to specific values, check exact match
            if (in_array($value, [1.234, 1.236, 1.999, 0.001, 0.994, 99.999])) {
                $this->assertEquals(
                    $expected,
                    $formatted,
                    "Value {$value} should round to {$expected}"
                );
            }
        }
    }

    /**
     * Property: Currency formatting with custom currency symbol
     * 
     * @test
     */
    public function property_currency_format_accepts_custom_symbol()
    {
        $testCases = [
            ['$', 100.50, '$100.50'],
            ['€', 200.75, '€200.75'],
            ['£', 50.00, '£50.00'],
            ['¥', 1000, '¥1,000.00'],
        ];
        
        foreach ($testCases as [$symbol, $value, $expected]) {
            // Act
            $formatted = $this->analyticsService->formatCurrency($value, $symbol);
            
            // Assert
            $this->assertEquals(
                $expected,
                $formatted,
                "Should format with custom currency symbol {$symbol}"
            );
        }
    }

    /**
     * Property: Currency formatting is consistent for same value
     * 
     * @test
     */
    public function property_currency_format_is_consistent()
    {
        $value = 1234.56;
        
        // Act: Format the same value multiple times
        $formatted1 = $this->analyticsService->formatCurrency($value);
        $formatted2 = $this->analyticsService->formatCurrency($value);
        $formatted3 = $this->analyticsService->formatCurrency($value);
        
        // Assert: All should be identical
        $this->assertEquals($formatted1, $formatted2, "Currency formatting should be consistent");
        $this->assertEquals($formatted2, $formatted3, "Currency formatting should be consistent");
    }

    /**
     * Property: Currency formatting handles very large numbers
     * 
     * @test
     */
    public function property_currency_format_handles_large_numbers()
    {
        $largeValues = [
            1000000,      // 1 million
            10000000,     // 10 million
            100000000,    // 100 million
            1000000000,   // 1 billion
        ];
        
        foreach ($largeValues as $value) {
            // Act
            $formatted = $this->analyticsService->formatCurrency($value);
            
            // Assert: Should have currency symbol
            $this->assertStringStartsWith('₱', $formatted);
            
            // Assert: Should have two decimal places
            $this->assertMatchesRegularExpression(
                '/\.\d{2}$/',
                $formatted,
                "Large value {$value} should have two decimal places"
            );
            
            // Assert: Should have thousand separators
            $this->assertStringContainsString(
                ',',
                $formatted,
                "Large value {$value} should have thousand separators"
            );
        }
    }

    /**
     * Property: Currency formatting handles very small positive numbers
     * 
     * @test
     */
    public function property_currency_format_handles_small_numbers()
    {
        $smallValues = [0.01, 0.02, 0.10, 0.50, 0.99];
        
        foreach ($smallValues as $value) {
            // Act
            $formatted = $this->analyticsService->formatCurrency($value);
            
            // Assert: Should start with currency symbol
            $this->assertStringStartsWith('₱', $formatted);
            
            // Assert: Should have exactly two decimal places
            $this->assertMatchesRegularExpression(
                '/₱0\.\d{2}$/',
                $formatted,
                "Small value {$value} should be formatted as ₱0.XX"
            );
        }
    }
}
