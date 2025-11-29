<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AvatarUploadService;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: user-profile-picture-upload, Property 4: Old avatar deletion on update
 * Validates: Requirements 3.2
 */
class AvatarUpdateDeletesOldPropertyTest extends TestCase
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
     * Property 4: Old avatar deletion on update
     * For any user with an existing avatar who uploads a new avatar,
     * the old avatar file should be deleted from storage, and only the new avatar should remain.
     * 
     * @test
     */
    public function property_old_avatar_deleted_on_update()
    {
        // Run the test multiple times with different random data
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Arrange: Create a user and upload an initial avatar
            $user = User::factory()->create();
            
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ];
            $extension1 = array_rand($mimeTypes);
            $mimeType1 = $mimeTypes[$extension1];
            
            $firstFile = UploadedFile::fake()->create(
                "first_avatar_{$iteration}.{$extension1}",
                rand(100, 1000),
                $mimeType1
            );
            
            $firstPath = $this->avatarService->uploadAvatar($user, $firstFile);
            
            // Verify first avatar exists
            $this->assertTrue(
                Storage::disk('public')->exists($firstPath),
                "First avatar should exist in storage (iteration {$iteration})"
            );
            
            // Refresh user to get updated profile
            $user->refresh();
            
            // Act: Upload a new avatar
            $extension2 = array_rand($mimeTypes);
            $mimeType2 = $mimeTypes[$extension2];
            
            $secondFile = UploadedFile::fake()->create(
                "second_avatar_{$iteration}.{$extension2}",
                rand(100, 1000),
                $mimeType2
            );
            
            $secondPath = $this->avatarService->uploadAvatar($user, $secondFile);
            
            // Assert: Old avatar should be deleted from storage
            $this->assertFalse(
                Storage::disk('public')->exists($firstPath),
                "Old avatar should be deleted from storage (iteration {$iteration})"
            );
            
            // Assert: New avatar should exist in storage
            $this->assertTrue(
                Storage::disk('public')->exists($secondPath),
                "New avatar should exist in storage (iteration {$iteration})"
            );
            
            // Assert: User profile should have the new avatar URL
            $user->refresh();
            $this->assertEquals(
                $secondPath,
                $user->profile->avatar_url,
                "User profile should have new avatar URL (iteration {$iteration})"
            );
            
            // Assert: Paths should be different
            $this->assertNotEquals(
                $firstPath,
                $secondPath,
                "New avatar path should be different from old path (iteration {$iteration})"
            );
            
            // Clean up for next iteration
            Storage::disk('public')->delete($secondPath);
            $user->profile()->delete();
            $user->delete();
        }
    }

    /**
     * Property: Multiple sequential updates delete all old avatars
     * 
     * @test
     */
    public function property_multiple_sequential_updates_delete_all_old_avatars()
    {
        // Arrange: Create a user
        $user = User::factory()->create();
        $uploadedPaths = [];
        
        // Act: Upload multiple avatars sequentially
        for ($i = 0; $i < 5; $i++) {
            $file = UploadedFile::fake()->create("avatar_{$i}.jpg", 500, 'image/jpeg');
            $path = $this->avatarService->uploadAvatar($user, $file);
            $uploadedPaths[] = $path;
        }
        
        // Assert: Only the last avatar should exist
        $lastPath = end($uploadedPaths);
        
        foreach ($uploadedPaths as $index => $path) {
            if ($path === $lastPath) {
                $this->assertTrue(
                    Storage::disk('public')->exists($path),
                    "Last avatar should exist in storage"
                );
            } else {
                $this->assertFalse(
                    Storage::disk('public')->exists($path),
                    "Old avatar at index {$index} should be deleted from storage"
                );
            }
        }
        
        // Assert: User should have only the last avatar URL
        $user->refresh();
        $this->assertEquals($lastPath, $user->profile->avatar_url);
    }

    /**
     * Property: Update with same filename still deletes old file
     * 
     * @test
     */
    public function property_update_with_same_filename_deletes_old_file()
    {
        // Arrange: Create user and upload avatar
        $user = User::factory()->create();
        $firstFile = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');
        $firstPath = $this->avatarService->uploadAvatar($user, $firstFile);
        
        // Act: Upload another file with same name
        $secondFile = UploadedFile::fake()->create('avatar.jpg', 600, 'image/jpeg');
        $secondPath = $this->avatarService->uploadAvatar($user, $secondFile);
        
        // Assert: Paths should be different (unique filenames generated)
        $this->assertNotEquals($firstPath, $secondPath, 
            "Service should generate unique filenames even with same original name");
        
        // Assert: Old file should be deleted
        $this->assertFalse(Storage::disk('public')->exists($firstPath),
            "Old avatar should be deleted");
        
        // Assert: New file should exist
        $this->assertTrue(Storage::disk('public')->exists($secondPath),
            "New avatar should exist");
    }

    /**
     * Property: Update from different file types works correctly
     * 
     * @test
     */
    public function property_update_from_different_file_types()
    {
        $fileTypes = [
            ['jpg', 'image/jpeg'],
            ['png', 'image/png'],
            ['gif', 'image/gif'],
            ['webp', 'image/webp'],
        ];
        
        // Arrange: Create user
        $user = User::factory()->create();
        $previousPath = null;
        
        // Act & Assert: Upload each file type, verifying old one is deleted
        foreach ($fileTypes as [$extension, $mimeType]) {
            $file = UploadedFile::fake()->create("avatar.{$extension}", 500, $mimeType);
            $newPath = $this->avatarService->uploadAvatar($user, $file);
            
            // If there was a previous avatar, it should be deleted
            if ($previousPath !== null) {
                $this->assertFalse(
                    Storage::disk('public')->exists($previousPath),
                    "Previous avatar ({$previousPath}) should be deleted when uploading {$extension}"
                );
            }
            
            // New avatar should exist
            $this->assertTrue(
                Storage::disk('public')->exists($newPath),
                "New avatar ({$newPath}) should exist"
            );
            
            $previousPath = $newPath;
        }
    }

    /**
     * Property: Update with different sizes deletes old avatar
     * 
     * @test
     */
    public function property_update_with_different_sizes_deletes_old()
    {
        $sizes = [100, 500, 1000, 1500, 2048]; // Various sizes in KB
        
        // Arrange: Create user
        $user = User::factory()->create();
        $previousPath = null;
        
        // Act & Assert: Upload files of different sizes
        foreach ($sizes as $size) {
            $file = UploadedFile::fake()->create("avatar_{$size}.jpg", $size, 'image/jpeg');
            $newPath = $this->avatarService->uploadAvatar($user, $file);
            
            // If there was a previous avatar, it should be deleted
            if ($previousPath !== null) {
                $this->assertFalse(
                    Storage::disk('public')->exists($previousPath),
                    "Previous avatar should be deleted when uploading {$size}KB file"
                );
            }
            
            // New avatar should exist
            $this->assertTrue(
                Storage::disk('public')->exists($newPath),
                "New {$size}KB avatar should exist"
            );
            
            $previousPath = $newPath;
        }
    }
}
