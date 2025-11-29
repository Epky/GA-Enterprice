<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\Component;

/**
 * Feature: user-profile-picture-upload, Property 6: Avatar display consistency
 * Validates: Requirements 5.1, 5.2, 5.3
 */
class AvatarDisplayConsistencyPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Property 6: Avatar display consistency
     * For any user with an avatar_url in the database, the avatar should be displayed
     * in all locations where user identity is shown (navigation, profile page, dropdowns).
     * 
     * @test
     */
    public function property_avatar_displays_consistently_across_all_locations()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Arrange: Create a random user with an avatar
            $user = User::factory()->create();
            
            // Create a fake avatar file (using create instead of image to avoid GD dependency)
            $file = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');
            $path = $file->store('avatars', 'public');
            
            // Set avatar_url in user profile
            $user->profile()->create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'avatar_url' => $path,
            ]);
            
            // Act: Render the avatar component with different sizes
            $sizes = ['sm', 'md', 'lg'];
            
            foreach ($sizes as $size) {
                $html = $this->blade(
                    '<x-user-avatar :user="$user" :size="$size" />',
                    ['user' => $user, 'size' => $size]
                );
                
                // Assert: Avatar image should be present
                $this->assertStringContainsString('<img', $html,
                    "Avatar component should render an img tag for user with avatar (iteration {$iteration}, size {$size})");
                
                // Assert: Avatar URL should be in the src attribute
                $expectedUrl = Storage::url($path);
                $this->assertStringContainsString($expectedUrl, $html,
                    "Avatar component should include the correct avatar URL (iteration {$iteration}, size {$size})");
                
                // Assert: Alt text should be present for accessibility
                $this->assertStringContainsString('alt=', $html,
                    "Avatar component should include alt text (iteration {$iteration}, size {$size})");
                $this->assertStringContainsString('profile picture', $html,
                    "Avatar component should include descriptive alt text (iteration {$iteration}, size {$size})");
                
                // Assert: Rounded styling should be applied
                $this->assertStringContainsString('rounded-full', $html,
                    "Avatar component should have rounded styling (iteration {$iteration}, size {$size})");
                
                // Assert: Size class should be applied
                $this->assertMatchesRegularExpression('/w-\d+/', $html,
                    "Avatar component should have width class (iteration {$iteration}, size {$size})");
                $this->assertMatchesRegularExpression('/h-\d+/', $html,
                    "Avatar component should have height class (iteration {$iteration}, size {$size})");
            }
            
            // Clean up for next iteration
            Storage::disk('public')->delete($path);
            $user->profile()->delete();
            $user->delete();
        }
    }

    /**
     * Property: Avatar component renders consistently with different user data
     * 
     * @test
     */
    public function property_avatar_renders_consistently_with_different_user_data()
    {
        // Test with various user profile configurations
        $testCases = [
            ['first_name' => 'John', 'last_name' => 'Doe'],
            ['first_name' => 'Jane', 'last_name' => 'Smith'],
            ['first_name' => 'A', 'last_name' => 'B'], // Short names
            ['first_name' => 'VeryLongFirstName', 'last_name' => 'VeryLongLastName'], // Long names
        ];
        
        foreach ($testCases as $profileData) {
            // Arrange
            $user = User::factory()->create();
            $file = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');
            $path = $file->store('avatars', 'public');
            
            $profileData['avatar_url'] = $path;
            $user->profile()->create($profileData);
            
            // Act
            $html = $this->blade(
                '<x-user-avatar :user="$user" />',
                ['user' => $user]
            );
            
            // Assert: Avatar should render with image
            $this->assertStringContainsString('<img', $html);
            $this->assertStringContainsString(Storage::url($path), $html);
            
            // Assert: Alt text should include user name
            $expectedName = "{$profileData['first_name']} {$profileData['last_name']}";
            $this->assertStringContainsString($expectedName, $html);
            
            // Clean up
            Storage::disk('public')->delete($path);
            $user->profile()->delete();
            $user->delete();
        }
    }

    /**
     * Property: Avatar component applies correct size classes
     * 
     * @test
     */
    public function property_avatar_applies_correct_size_classes()
    {
        $sizeMapping = [
            'sm' => ['w-8', 'h-8'],
            'md' => ['w-10', 'h-10'],
            'lg' => ['w-16', 'h-16'],
        ];
        
        foreach ($sizeMapping as $size => $expectedClasses) {
            // Arrange
            $user = User::factory()->create();
            $file = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');
            $path = $file->store('avatars', 'public');
            
            $user->profile()->create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'avatar_url' => $path,
            ]);
            
            // Act
            $html = $this->blade(
                '<x-user-avatar :user="$user" :size="$size" />',
                ['user' => $user, 'size' => $size]
            );
            
            // Assert: Correct size classes should be present
            foreach ($expectedClasses as $class) {
                $this->assertStringContainsString($class, $html,
                    "Avatar component should include {$class} for size {$size}");
            }
            
            // Clean up
            Storage::disk('public')->delete($path);
            $user->profile()->delete();
            $user->delete();
        }
    }

    /**
     * Property: Avatar component handles custom classes
     * 
     * @test
     */
    public function property_avatar_handles_custom_classes()
    {
        // Arrange
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');
        $path = $file->store('avatars', 'public');
        
        $user->profile()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'avatar_url' => $path,
        ]);
        
        $customClass = 'border-2 border-blue-500';
        
        // Act
        $html = $this->blade(
            '<x-user-avatar :user="$user" class="' . $customClass . '" />',
            ['user' => $user]
        );
        
        // Assert: Custom classes should be applied
        $this->assertStringContainsString('border-2', $html);
        $this->assertStringContainsString('border-blue-500', $html);
        
        // Assert: Default classes should still be present
        $this->assertStringContainsString('rounded-full', $html);
        
        // Clean up
        Storage::disk('public')->delete($path);
    }

    /**
     * Property: Avatar component is accessible
     * 
     * @test
     */
    public function property_avatar_component_is_accessible()
    {
        // Arrange
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');
        $path = $file->store('avatars', 'public');
        
        $user->profile()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'avatar_url' => $path,
        ]);
        
        // Act
        $html = $this->blade(
            '<x-user-avatar :user="$user" />',
            ['user' => $user]
        );
        
        // Assert: Alt text should be descriptive and include user name
        $this->assertStringContainsString('alt=', $html);
        $this->assertStringContainsString('John Doe', $html);
        $this->assertStringContainsString('profile picture', $html);
        
        // Assert: Image should have proper attributes for accessibility
        $this->assertMatchesRegularExpression('/alt="[^"]*profile picture[^"]*"/', $html);
        
        // Clean up
        Storage::disk('public')->delete($path);
    }
}
