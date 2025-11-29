<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AvatarUploadService;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: user-profile-picture-upload, Property 5: Avatar removal clears database and deletes file
 * Validates: Requirements 4.2, 4.3
 */
class AvatarRemovalPropertyTest extends TestCase
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
     * Property 5: Avatar removal clears database and deletes file
     * For any user with an existing avatar who removes their avatar,
     * the avatar file should be deleted from storage and the avatar_url should be set to null in the database.
     * 
     * @test
     */
    public function property_avatar_removal_clears_database_and_deletes_file()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Arrange: Create a user and upload an avatar
            $user = User::factory()->create();
            
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ];
            $extension = array_rand($mimeTypes);
            $mimeType = $mimeTypes[$extension];
            
            $file = UploadedFile::fake()->create(
                "avatar_{$iteration}.{$extension}",
                rand(100, 1000),
                $mimeType
            );
            
            $avatarPath = $this->avatarService->uploadAvatar($user, $file);
            
            // Verify avatar exists
            $this->assertTrue(
                Storage::disk('public')->exists($avatarPath),
                "Avatar should exist in storage before removal (iteration {$iteration})"
            );
            
            $user->refresh();
            $this->assertNotNull(
                $user->profile->avatar_url,
                "Avatar URL should be set in database before removal (iteration {$iteration})"
            );
            
            // Act: Delete the avatar
            $result = $this->avatarService->deleteAvatar($user);
            
            // Assert: Deletion should be successful
            $this->assertTrue(
                $result,
                "Delete operation should return true (iteration {$iteration})"
            );
            
            // Assert: Avatar file should be deleted from storage
            $this->assertFalse(
                Storage::disk('public')->exists($avatarPath),
                "Avatar file should be deleted from storage (iteration {$iteration})"
            );
            
            // Assert: Avatar URL should be null in database
            $user->refresh();
            $this->assertNull(
                $user->profile->avatar_url,
                "Avatar URL should be null in database after removal (iteration {$iteration})"
            );
            
            // Clean up for next iteration
            $user->profile()->delete();
            $user->delete();
        }
    }

    /**
     * Property: Removing avatar from user without avatar returns false
     * 
     * @test
     */
    public function property_removing_avatar_from_user_without_avatar_returns_false()
    {
        // Arrange: Create user without avatar
        $user = User::factory()->create();
        
        // Act: Try to delete avatar
        $result = $this->avatarService->deleteAvatar($user);
        
        // Assert: Should return false
        $this->assertFalse($result, "Deleting non-existent avatar should return false");
    }

    /**
     * Property: Removing avatar from user without profile returns false
     * 
     * @test
     */
    public function property_removing_avatar_from_user_without_profile_returns_false()
    {
        // Arrange: Create user without profile
        $user = User::factory()->create();
        $this->assertNull($user->profile, "User should not have profile");
        
        // Act: Try to delete avatar
        $result = $this->avatarService->deleteAvatar($user);
        
        // Assert: Should return false
        $this->assertFalse($result, "Deleting avatar from user without profile should return false");
    }

    /**
     * Property: Multiple removals are idempotent
     * 
     * @test
     */
    public function property_multiple_removals_are_idempotent()
    {
        // Arrange: Create user with avatar
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');
        $avatarPath = $this->avatarService->uploadAvatar($user, $file);
        
        // Act: Delete avatar multiple times
        $result1 = $this->avatarService->deleteAvatar($user);
        $result2 = $this->avatarService->deleteAvatar($user);
        $result3 = $this->avatarService->deleteAvatar($user);
        
        // Assert: First deletion should succeed
        $this->assertTrue($result1, "First deletion should return true");
        
        // Assert: Subsequent deletions should return false
        $this->assertFalse($result2, "Second deletion should return false");
        $this->assertFalse($result3, "Third deletion should return false");
        
        // Assert: File should not exist
        $this->assertFalse(Storage::disk('public')->exists($avatarPath),
            "Avatar file should not exist after deletion");
        
        // Assert: Avatar URL should be null
        $user->refresh();
        $this->assertNull($user->profile->avatar_url,
            "Avatar URL should be null after deletion");
    }

    /**
     * Property: Removal preserves other profile data
     * 
     * @test
     */
    public function property_removal_preserves_other_profile_data()
    {
        // Arrange: Create user with profile data and avatar
        $user = User::factory()->create();
        $user->profile()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
        ]);
        
        $file = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');
        $this->avatarService->uploadAvatar($user, $file);
        
        $user->refresh();
        
        // Act: Delete avatar
        $this->avatarService->deleteAvatar($user);
        
        // Assert: Profile data should be preserved
        $user->refresh();
        $this->assertEquals('John', $user->profile->first_name,
            "First name should be preserved");
        $this->assertEquals('Doe', $user->profile->last_name,
            "Last name should be preserved");
        $this->assertEquals('1234567890', $user->profile->phone,
            "Phone should be preserved");
        $this->assertNull($user->profile->avatar_url,
            "Avatar URL should be null");
    }

    /**
     * Property: Removal works for all file types
     * 
     * @test
     */
    public function property_removal_works_for_all_file_types()
    {
        $fileTypes = [
            ['jpg', 'image/jpeg'],
            ['png', 'image/png'],
            ['gif', 'image/gif'],
            ['webp', 'image/webp'],
        ];
        
        foreach ($fileTypes as [$extension, $mimeType]) {
            // Arrange: Create user with avatar of specific type
            $user = User::factory()->create();
            $file = UploadedFile::fake()->create("avatar.{$extension}", 500, $mimeType);
            $avatarPath = $this->avatarService->uploadAvatar($user, $file);
            
            // Act: Delete avatar
            $result = $this->avatarService->deleteAvatar($user);
            
            // Assert: Deletion should succeed
            $this->assertTrue($result, "Deletion of {$extension} file should succeed");
            
            // Assert: File should be deleted
            $this->assertFalse(Storage::disk('public')->exists($avatarPath),
                "{$extension} file should be deleted from storage");
            
            // Assert: Avatar URL should be null
            $user->refresh();
            $this->assertNull($user->profile->avatar_url,
                "Avatar URL should be null after deleting {$extension} file");
            
            // Clean up
            $user->profile()->delete();
            $user->delete();
        }
    }

    /**
     * Property: Removal works for avatars of different sizes
     * 
     * @test
     */
    public function property_removal_works_for_different_sizes()
    {
        $sizes = [100, 500, 1000, 1500, 2048]; // Various sizes in KB
        
        foreach ($sizes as $size) {
            // Arrange: Create user with avatar of specific size
            $user = User::factory()->create();
            $file = UploadedFile::fake()->create("avatar_{$size}.jpg", $size, 'image/jpeg');
            $avatarPath = $this->avatarService->uploadAvatar($user, $file);
            
            // Act: Delete avatar
            $result = $this->avatarService->deleteAvatar($user);
            
            // Assert: Deletion should succeed
            $this->assertTrue($result, "Deletion of {$size}KB file should succeed");
            
            // Assert: File should be deleted
            $this->assertFalse(Storage::disk('public')->exists($avatarPath),
                "{$size}KB file should be deleted from storage");
            
            // Clean up
            $user->profile()->delete();
            $user->delete();
        }
    }
}
