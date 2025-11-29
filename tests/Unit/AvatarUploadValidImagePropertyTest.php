<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AvatarUploadService;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: user-profile-picture-upload, Property 1: Valid image upload stores file and updates database
 * Validates: Requirements 1.4
 */
class AvatarUploadValidImagePropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AvatarUploadService $avatarService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->avatarService = new AvatarUploadService();
        Storage::fake('public');
    }

    /**
     * Property 1: Valid image upload stores file and updates database
     * For any valid image file (correct type, size, dimensions) uploaded by any user,
     * the system should successfully store the file in the avatars directory and 
     * update the user's avatar_url in the database.
     * 
     * @test
     */
    public function property_valid_image_upload_stores_file_and_updates_database()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Arrange: Create a random user
            $user = User::factory()->create();
            
            // Create random valid image parameters
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ];
            $extension = array_rand($mimeTypes);
            $mimeType = $mimeTypes[$extension];
            
            // Random file size (minimum 1KB, maximum 2MB = 2048KB)
            $sizeInKB = rand(1, 2048);
            
            // Create a fake image file with proper MIME type
            $file = UploadedFile::fake()->create(
                "test_avatar_{$iteration}.{$extension}",
                $sizeInKB,
                $mimeType
            );
            
            // Act: Upload the avatar
            $path = $this->avatarService->uploadAvatar($user, $file);
            
            // Assert: File should be stored in the avatars directory
            $this->assertStringStartsWith('avatars/', $path, 
                "Avatar should be stored in avatars directory (iteration {$iteration})");
            
            // Assert: File should exist in storage
            Storage::disk('public')->assertExists($path);
            
            // Assert: User profile should be updated with avatar_url
            $user->refresh();
            $this->assertNotNull($user->profile, 
                "User profile should exist after avatar upload (iteration {$iteration})");
            $this->assertEquals($path, $user->profile->avatar_url, 
                "User profile avatar_url should match stored path (iteration {$iteration})");
            
            // Assert: Avatar URL should be retrievable
            $avatarUrl = $this->avatarService->getAvatarUrl($user);
            $this->assertNotNull($avatarUrl, 
                "Avatar URL should be retrievable (iteration {$iteration})");
            
            // Clean up for next iteration
            Storage::disk('public')->delete($path);
            $user->profile()->delete();
            $user->delete();
        }
    }

    /**
     * Property: Valid image upload creates profile if not exists
     * 
     * @test
     */
    public function property_valid_image_upload_creates_profile_if_not_exists()
    {
        // Arrange: Create user without profile
        $user = User::factory()->create();
        $this->assertNull($user->profile, "User should not have profile initially");
        
        $file = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');
        
        // Act: Upload avatar
        $path = $this->avatarService->uploadAvatar($user, $file);
        
        // Assert: Profile should be created
        $user->refresh();
        $this->assertNotNull($user->profile, "Profile should be created");
        $this->assertEquals($path, $user->profile->avatar_url, "Avatar URL should be set");
    }

    /**
     * Property: Valid image upload updates existing profile
     * 
     * @test
     */
    public function property_valid_image_upload_updates_existing_profile()
    {
        // Arrange: Create user with profile
        $user = User::factory()->create();
        $user->profile()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        
        $file = UploadedFile::fake()->create('avatar.png', 800, 'image/png');
        
        // Act: Upload avatar
        $path = $this->avatarService->uploadAvatar($user, $file);
        
        // Assert: Profile should be updated, not replaced
        $user->refresh();
        $this->assertEquals('John', $user->profile->first_name, "Profile data should be preserved");
        $this->assertEquals('Doe', $user->profile->last_name, "Profile data should be preserved");
        $this->assertEquals($path, $user->profile->avatar_url, "Avatar URL should be updated");
    }

    /**
     * Property: Valid image upload with different extensions works correctly
     * 
     * @test
     */
    public function property_valid_image_upload_handles_all_allowed_extensions()
    {
        $allowedTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
        
        foreach ($allowedTypes as $extension => $mimeType) {
            // Arrange
            $user = User::factory()->create();
            $file = UploadedFile::fake()->create("avatar.{$extension}", 500, $mimeType);
            
            // Act
            $path = $this->avatarService->uploadAvatar($user, $file);
            
            // Assert
            Storage::disk('public')->assertExists($path);
            $this->assertStringEndsWith(".{$extension}", $path, 
                "File should have correct extension: {$extension}");
            
            // Clean up
            Storage::disk('public')->delete($path);
            $user->profile()->delete();
            $user->delete();
        }
    }

    /**
     * Property: Valid image upload at minimum size works
     * 
     * @test
     */
    public function property_valid_image_upload_at_minimum_size()
    {
        // Arrange: Create image at minimum size (1KB)
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('avatar.jpg', 1, 'image/jpeg');
        
        // Act
        $path = $this->avatarService->uploadAvatar($user, $file);
        
        // Assert
        Storage::disk('public')->assertExists($path);
        $user->refresh();
        $this->assertEquals($path, $user->profile->avatar_url);
    }

    /**
     * Property: Valid image upload at maximum size works
     * 
     * @test
     */
    public function property_valid_image_upload_at_maximum_size()
    {
        // Arrange: Create image at exactly maximum size (2MB = 2048KB)
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('avatar.jpg', 2048, 'image/jpeg');
        
        // Act
        $path = $this->avatarService->uploadAvatar($user, $file);
        
        // Assert
        Storage::disk('public')->assertExists($path);
        $user->refresh();
        $this->assertEquals($path, $user->profile->avatar_url);
    }

    /**
     * Property: Multiple users can upload avatars independently
     * 
     * @test
     */
    public function property_multiple_users_can_upload_avatars_independently()
    {
        // Arrange: Create multiple users
        $users = User::factory()->count(10)->create();
        $uploadedPaths = [];
        
        // Act: Each user uploads an avatar
        foreach ($users as $user) {
            $file = UploadedFile::fake()->create("avatar_{$user->id}.jpg", 500, 'image/jpeg');
            $path = $this->avatarService->uploadAvatar($user, $file);
            $uploadedPaths[$user->id] = $path;
        }
        
        // Assert: Each user should have their own unique avatar
        foreach ($users as $user) {
            $user->refresh();
            $this->assertNotNull($user->profile->avatar_url);
            $this->assertEquals($uploadedPaths[$user->id], $user->profile->avatar_url);
            Storage::disk('public')->assertExists($user->profile->avatar_url);
        }
        
        // Assert: All paths should be unique
        $uniquePaths = array_unique($uploadedPaths);
        $this->assertCount(count($uploadedPaths), $uniquePaths, 
            "All avatar paths should be unique");
    }
}
