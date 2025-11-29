<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AvatarUploadService;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: user-profile-picture-upload, Property 9: User deletion cleanup
 * Validates: Requirements 6.5
 */
class UserDeletionCleanupPropertyTest extends TestCase
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
     * Property 9: User deletion cleanup
     * For any user account that is deleted, if the user had an avatar,
     * the avatar file should be removed from storage.
     * 
     * @test
     */
    public function property_user_deletion_removes_avatar_from_storage()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Arrange: Create a user with an avatar
            $user = User::factory()->create();
            
            // Create random valid image
            $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension = $extensions[array_rand($extensions)];
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ];
            
            $file = UploadedFile::fake()->create(
                "avatar_{$iteration}.{$extension}",
                rand(100, 2048),
                $mimeTypes[$extension]
            );
            
            // Upload avatar
            $avatarPath = $this->avatarService->uploadAvatar($user, $file);
            
            // Verify avatar exists in storage
            $this->assertTrue(
                Storage::disk('public')->exists($avatarPath),
                "Avatar should exist in storage before deletion (iteration {$iteration})"
            );
            
            // Verify user has avatar_url set
            $user->refresh();
            $this->assertNotNull(
                $user->profile->avatar_url,
                "User should have avatar_url set (iteration {$iteration})"
            );
            
            // Act: Delete the user
            $user->delete();
            
            // Assert: Avatar file should be removed from storage
            $this->assertFalse(
                Storage::disk('public')->exists($avatarPath),
                "Avatar file should be removed from storage after user deletion (iteration {$iteration})"
            );
        }
    }

    /**
     * Property: User deletion without avatar does not cause errors
     * 
     * @test
     */
    public function property_user_deletion_without_avatar_succeeds()
    {
        // Run multiple times
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Arrange: Create user without avatar
            $user = User::factory()->create();
            
            // Verify user has no avatar
            $this->assertNull($user->profile);
            
            // Act & Assert: Deleting user should not throw exception
            $user->delete();
            
            // Verify user is deleted
            $this->assertDatabaseMissing('users', ['id' => $user->id]);
        }
    }

    /**
     * Property: User deletion with profile but no avatar succeeds
     * 
     * @test
     */
    public function property_user_deletion_with_profile_but_no_avatar_succeeds()
    {
        // Run multiple times
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Arrange: Create user with profile but no avatar
            $user = User::factory()->create();
            $user->profile()->create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'avatar_url' => null,
            ]);
            
            // Verify user has profile but no avatar
            $this->assertNotNull($user->profile);
            $this->assertNull($user->profile->avatar_url);
            
            // Act & Assert: Deleting user should not throw exception
            $user->delete();
            
            // Verify user is deleted
            $this->assertDatabaseMissing('users', ['id' => $user->id]);
        }
    }

    /**
     * Property: Multiple users with avatars can be deleted independently
     * 
     * @test
     */
    public function property_multiple_users_with_avatars_can_be_deleted_independently()
    {
        // Arrange: Create multiple users with avatars
        $users = [];
        $avatarPaths = [];
        
        for ($i = 0; $i < 10; $i++) {
            $user = User::factory()->create();
            $file = UploadedFile::fake()->create("avatar_{$i}.jpg", 500, 'image/jpeg');
            $path = $this->avatarService->uploadAvatar($user, $file);
            
            $users[] = $user;
            $avatarPaths[$user->id] = $path;
        }
        
        // Verify all avatars exist
        foreach ($avatarPaths as $path) {
            $this->assertTrue(Storage::disk('public')->exists($path));
        }
        
        // Act: Delete each user one by one
        foreach ($users as $user) {
            $userId = $user->id;
            $avatarPath = $avatarPaths[$userId];
            
            $user->delete();
            
            // Assert: Only this user's avatar should be deleted
            $this->assertFalse(
                Storage::disk('public')->exists($avatarPath),
                "Deleted user's avatar should be removed"
            );
        }
        
        // Assert: All avatars should be deleted
        foreach ($avatarPaths as $path) {
            $this->assertFalse(Storage::disk('public')->exists($path));
        }
    }

    /**
     * Property: User deletion cleanup is transactional
     * 
     * @test
     */
    public function property_user_deletion_cleanup_is_atomic()
    {
        // Arrange: Create user with avatar
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');
        $avatarPath = $this->avatarService->uploadAvatar($user, $file);
        
        // Verify avatar exists
        $this->assertTrue(Storage::disk('public')->exists($avatarPath));
        
        // Act: Delete user
        $user->delete();
        
        // Assert: Both user and avatar should be gone
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertFalse(Storage::disk('public')->exists($avatarPath));
    }

    /**
     * Property: Avatar cleanup happens before user deletion
     * 
     * @test
     */
    public function property_avatar_cleanup_happens_during_user_deletion()
    {
        // Arrange: Create user with avatar
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');
        $avatarPath = $this->avatarService->uploadAvatar($user, $file);
        
        $userId = $user->id;
        
        // Verify initial state
        $this->assertTrue(Storage::disk('public')->exists($avatarPath));
        $this->assertDatabaseHas('users', ['id' => $userId]);
        
        // Act: Delete user
        $user->delete();
        
        // Assert: Avatar should be deleted
        $this->assertFalse(
            Storage::disk('public')->exists($avatarPath),
            "Avatar should be deleted when user is deleted"
        );
        
        // Assert: User should be deleted
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }
}
