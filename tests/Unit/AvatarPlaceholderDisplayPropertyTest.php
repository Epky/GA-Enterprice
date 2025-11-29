<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: user-profile-picture-upload, Property 7: Placeholder display for missing avatars
 * Validates: Requirements 5.4
 */
class AvatarPlaceholderDisplayPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property 7: Placeholder display for missing avatars
     * For any user without an avatar_url, a default placeholder (with initials or icon)
     * should be displayed in all locations where user identity is shown.
     * 
     * @test
     */
    public function property_placeholder_displays_for_users_without_avatar()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Arrange: Create a random user without an avatar
            $user = User::factory()->create();
            
            // Randomly decide whether to create a profile or not
            $hasProfile = rand(0, 1) === 1;
            
            if ($hasProfile) {
                $user->profile()->create([
                    'first_name' => 'Test' . $iteration,
                    'last_name' => 'User' . $iteration,
                    'avatar_url' => null, // Explicitly no avatar
                ]);
            }
            
            // Act: Render the avatar component
            $html = $this->blade(
                '<x-user-avatar :user="$user" />',
                ['user' => $user]
            );
            
            // Assert: Should NOT contain an img tag (no avatar to display)
            $this->assertStringNotContainsString('<img', $html,
                "Avatar component should not render img tag for user without avatar (iteration {$iteration})");
            
            // Assert: Should contain a div placeholder
            $this->assertStringContainsString('<div', $html,
                "Avatar component should render a div placeholder (iteration {$iteration})");
            
            // Assert: Placeholder should have rounded styling
            $this->assertStringContainsString('rounded-full', $html,
                "Placeholder should have rounded styling (iteration {$iteration})");
            
            // Assert: Placeholder should have background color/gradient
            $this->assertMatchesRegularExpression('/bg-gradient|bg-\w+/', $html,
                "Placeholder should have background styling (iteration {$iteration})");
            
            // Assert: Placeholder should display initials
            $initials = $user->getInitials();
            $this->assertStringContainsString($initials, $html,
                "Placeholder should display user initials: {$initials} (iteration {$iteration})");
            
            // Clean up for next iteration
            if ($hasProfile) {
                $user->profile()->delete();
            }
            $user->delete();
        }
    }

    /**
     * Property: Placeholder displays correct initials for users with profiles
     * 
     * @test
     */
    public function property_placeholder_displays_correct_initials_with_profile()
    {
        $testCases = [
            ['first_name' => 'John', 'last_name' => 'Doe', 'expected' => 'JD'],
            ['first_name' => 'Jane', 'last_name' => 'Smith', 'expected' => 'JS'],
            ['first_name' => 'Alice', 'last_name' => 'Brown', 'expected' => 'AB'],
            ['first_name' => 'Bob', 'last_name' => 'Wilson', 'expected' => 'BW'],
            ['first_name' => 'a', 'last_name' => 'b', 'expected' => 'AB'], // Lowercase
            ['first_name' => 'X', 'last_name' => 'Y', 'expected' => 'XY'], // Single letters
        ];
        
        foreach ($testCases as $testCase) {
            // Arrange
            $user = User::factory()->create();
            $user->profile()->create([
                'first_name' => $testCase['first_name'],
                'last_name' => $testCase['last_name'],
                'avatar_url' => null,
            ]);
            
            // Act
            $html = $this->blade(
                '<x-user-avatar :user="$user" />',
                ['user' => $user]
            );
            
            // Assert: Correct initials should be displayed
            $this->assertStringContainsString($testCase['expected'], $html,
                "Placeholder should display initials {$testCase['expected']} for {$testCase['first_name']} {$testCase['last_name']}");
            
            // Clean up
            $user->profile()->delete();
            $user->delete();
        }
    }

    /**
     * Property: Placeholder displays initials from email for users without profiles
     * 
     * @test
     */
    public function property_placeholder_displays_email_initials_without_profile()
    {
        $testCases = [
            ['email' => 'john.doe@example.com', 'expected_pattern' => '/[A-Z]{2}/'],
            ['email' => 'test@test.com', 'expected_pattern' => '/[A-Z]{2}/'],
            ['email' => 'a@b.com', 'expected_pattern' => '/[A-Z]{2}/'],
        ];
        
        foreach ($testCases as $testCase) {
            // Arrange: Create user without profile
            $user = User::factory()->create([
                'email' => $testCase['email'],
            ]);
            
            $this->assertNull($user->profile, "User should not have a profile");
            
            // Act
            $html = $this->blade(
                '<x-user-avatar :user="$user" />',
                ['user' => $user]
            );
            
            // Assert: Should display some initials (from email)
            $initials = $user->getInitials();
            $this->assertNotEmpty($initials, "Initials should be generated from email");
            $this->assertStringContainsString($initials, $html,
                "Placeholder should display initials from email: {$testCase['email']}");
            
            // Assert: Initials should be uppercase letters
            $this->assertMatchesRegularExpression($testCase['expected_pattern'], $initials,
                "Initials should be uppercase letters");
            
            // Clean up
            $user->delete();
        }
    }

    /**
     * Property: Placeholder applies correct size classes
     * 
     * @test
     */
    public function property_placeholder_applies_correct_size_classes()
    {
        $sizeMapping = [
            'sm' => ['w-8', 'h-8', 'text-xs'],
            'md' => ['w-10', 'h-10', 'text-sm'],
            'lg' => ['w-16', 'h-16', 'text-lg'],
        ];
        
        foreach ($sizeMapping as $size => $expectedClasses) {
            // Arrange
            $user = User::factory()->create();
            $user->profile()->create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'avatar_url' => null,
            ]);
            
            // Act
            $html = $this->blade(
                '<x-user-avatar :user="$user" :size="$size" />',
                ['user' => $user, 'size' => $size]
            );
            
            // Assert: Correct size classes should be present
            foreach ($expectedClasses as $class) {
                $this->assertStringContainsString($class, $html,
                    "Placeholder should include {$class} for size {$size}");
            }
            
            // Clean up
            $user->profile()->delete();
            $user->delete();
        }
    }

    /**
     * Property: Placeholder has consistent styling
     * 
     * @test
     */
    public function property_placeholder_has_consistent_styling()
    {
        // Arrange
        $user = User::factory()->create();
        $user->profile()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'avatar_url' => null,
        ]);
        
        // Act
        $html = $this->blade(
            '<x-user-avatar :user="$user" />',
            ['user' => $user]
        );
        
        // Assert: Placeholder should have consistent styling elements
        $this->assertStringContainsString('rounded-full', $html, "Should be circular");
        $this->assertStringContainsString('flex', $html, "Should use flexbox for centering");
        $this->assertStringContainsString('items-center', $html, "Should center items vertically");
        $this->assertStringContainsString('justify-center', $html, "Should center items horizontally");
        $this->assertStringContainsString('text-white', $html, "Should have white text");
        $this->assertStringContainsString('font-semibold', $html, "Should have semibold font");
        
        // Assert: Should have a background color or gradient
        $this->assertMatchesRegularExpression('/bg-gradient|bg-\w+/', $html,
            "Should have background styling");
    }

    /**
     * Property: Placeholder handles custom classes
     * 
     * @test
     */
    public function property_placeholder_handles_custom_classes()
    {
        // Arrange
        $user = User::factory()->create();
        $user->profile()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'avatar_url' => null,
        ]);
        
        $customClass = 'border-4 border-red-500';
        
        // Act
        $html = $this->blade(
            '<x-user-avatar :user="$user" class="' . $customClass . '" />',
            ['user' => $user]
        );
        
        // Assert: Custom classes should be applied
        $this->assertStringContainsString('border-4', $html);
        $this->assertStringContainsString('border-red-500', $html);
        
        // Assert: Default classes should still be present
        $this->assertStringContainsString('rounded-full', $html);
        
        // Clean up
        $user->profile()->delete();
    }

    /**
     * Property: Placeholder displays consistently across multiple renders
     * 
     * @test
     */
    public function property_placeholder_displays_consistently_across_renders()
    {
        // Arrange
        $user = User::factory()->create();
        $user->profile()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'avatar_url' => null,
        ]);
        
        // Act: Render multiple times
        $renders = [];
        for ($i = 0; $i < 5; $i++) {
            $html = $this->blade(
                '<x-user-avatar :user="$user" />',
                ['user' => $user]
            );
            $renders[] = $html;
        }
        
        // Assert: All renders should contain the same initials
        $initials = $user->getInitials();
        foreach ($renders as $index => $html) {
            $this->assertStringContainsString($initials, $html,
                "Render {$index} should contain initials {$initials}");
            $this->assertStringContainsString('rounded-full', $html,
                "Render {$index} should have consistent styling");
        }
        
        // Clean up
        $user->profile()->delete();
    }
}
