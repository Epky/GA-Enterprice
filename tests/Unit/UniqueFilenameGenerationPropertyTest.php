<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AvatarUploadService;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature: user-profile-picture-upload, Property 8: Unique filename generation
 * Validates: Requirements 6.3
 */
class UniqueFilenameGenerationPropertyTest extends TestCase
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
     * Property 8: Unique filename generation
     * For any avatar upload, the generated filename should be unique to prevent 
     * naming conflicts, even if multiple users upload files with the same original name.
     * 
     * @test
     */
    public function property_unique_filename_generation()
    {
        // Run the test multiple times with different scenarios
        for ($iteration = 0; $iteration < 100; $iteration++) {
            // Arrange: Create multiple users
            $userCount = rand(2, 5);
            $users = User::factory()->count($userCount)->create();
            
            // Use the same original filename for all uploads
            $originalFilename = "profile_picture.jpg";
            $uploadedPaths = [];
            
            // Act: Each user uploads a file with the same original name
            foreach ($users as $user) {
                $file = UploadedFile::fake()->create($originalFilename, 500, 'image/jpeg');
                $path = $this->avatarService->uploadAvatar($user, $file);
                $uploadedPaths[] = $path;
            }
            
            // Assert: All generated filenames should be unique
            $uniquePaths = array_unique($uploadedPaths);
            $this->assertCount(
                count($uploadedPaths), 
                $uniquePaths, 
                "All generated filenames should be unique even with same original name (iteration {$iteration})"
            );
            
            // Assert: Each file should exist in storage
            foreach ($uploadedPaths as $path) {
                $this->assertTrue(
                    Storage::disk('public')->exists($path),
                    "Each uploaded file should exist in storage (iteration {$iteration})"
                );
            }
            
            // Clean up for next iteration
            foreach ($users as $user) {
                if ($user->profile && $user->profile->avatar_url) {
                    Storage::disk('public')->delete($user->profile->avatar_url);
                }
                $user->profile()->delete();
                $user->delete();
            }
        }
    }

    /**
     * Property: Same user uploading multiple times generates unique filenames
     * 
     * @test
     */
    public function property_same_user_multiple_uploads_generate_unique_filenames()
    {
        // Arrange: Create a single user
        $user = User::factory()->create();
        $uploadedPaths = [];
        
        // Act: Upload multiple avatars for the same user
        for ($i = 0; $i < 10; $i++) {
            $file = UploadedFile::fake()->create("avatar.jpg", 500, 'image/jpeg');
            $path = $this->avatarService->uploadAvatar($user, $file);
            $uploadedPaths[] = $path;
            
            // Refresh user to get updated profile
            $user->refresh();
            
            // Small delay to ensure timestamp changes
            usleep(10000); // 10ms delay
        }
        
        // Assert: All filenames should be unique
        $uniquePaths = array_unique($uploadedPaths);
        $this->assertCount(
            count($uploadedPaths), 
            $uniquePaths, 
            "Same user uploading multiple times should generate unique filenames"
        );
    }

    /**
     * Property: Concurrent uploads generate unique filenames
     * 
     * @test
     */
    public function property_concurrent_uploads_generate_unique_filenames()
    {
        // Arrange: Create multiple users
        $users = User::factory()->count(20)->create();
        $uploadedPaths = [];
        
        // Act: Simulate concurrent uploads (all at once, no delays)
        foreach ($users as $user) {
            $file = UploadedFile::fake()->create("photo.png", 500, 'image/png');
            $path = $this->avatarService->uploadAvatar($user, $file);
            $uploadedPaths[] = $path;
        }
        
        // Assert: All filenames should be unique despite concurrent uploads
        $uniquePaths = array_unique($uploadedPaths);
        $this->assertCount(
            count($uploadedPaths), 
            $uniquePaths, 
            "Concurrent uploads should generate unique filenames"
        );
    }

    /**
     * Property: Different file extensions maintain uniqueness
     * 
     * @test
     */
    public function property_different_extensions_maintain_uniqueness()
    {
        // Arrange: Create users
        $users = User::factory()->count(5)->create();
        $extensions = ['jpg', 'png', 'gif', 'webp', 'jpeg'];
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'jpeg' => 'image/jpeg',
        ];
        $uploadedPaths = [];
        
        // Act: Each user uploads with a different extension
        foreach ($users as $index => $user) {
            $extension = $extensions[$index];
            $mimeType = $mimeTypes[$extension];
            $file = UploadedFile::fake()->create("avatar.{$extension}", 500, $mimeType);
            $path = $this->avatarService->uploadAvatar($user, $file);
            $uploadedPaths[] = $path;
        }
        
        // Assert: All filenames should be unique
        $uniquePaths = array_unique($uploadedPaths);
        $this->assertCount(
            count($uploadedPaths), 
            $uniquePaths, 
            "Files with different extensions should have unique filenames"
        );
    }

    /**
     * Property: Filename contains user identifier
     * 
     * @test
     */
    public function property_filename_contains_user_identifier()
    {
        // Run multiple iterations
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Arrange: Create a user
            $user = User::factory()->create();
            $file = UploadedFile::fake()->create("test.jpg", 500, 'image/jpeg');
            
            // Act: Upload avatar
            $path = $this->avatarService->uploadAvatar($user, $file);
            
            // Assert: Filename should contain user ID for uniqueness
            $filename = basename($path);
            $this->assertStringContainsString(
                (string)$user->id, 
                $filename, 
                "Filename should contain user ID for uniqueness (iteration {$iteration})"
            );
            
            // Clean up
            Storage::disk('public')->delete($path);
            $user->profile()->delete();
            $user->delete();
        }
    }

    /**
     * Property: Filename format is consistent
     * 
     * @test
     */
    public function property_filename_format_is_consistent()
    {
        // Arrange: Create multiple users
        $users = User::factory()->count(10)->create();
        
        // Act: Upload avatars
        foreach ($users as $user) {
            $file = UploadedFile::fake()->create("avatar.jpg", 500, 'image/jpeg');
            $path = $this->avatarService->uploadAvatar($user, $file);
            
            // Assert: Filename should follow expected pattern
            $filename = basename($path);
            
            // Should start with "user_"
            $this->assertStringStartsWith('user_', $filename, 
                "Filename should start with 'user_'");
            
            // Should contain user ID
            $this->assertStringContainsString((string)$user->id, $filename, 
                "Filename should contain user ID");
            
            // Should have proper extension
            $this->assertMatchesRegularExpression('/\.(jpg|jpeg|png|gif|webp)$/', $filename, 
                "Filename should have valid image extension");
        }
    }

    /**
     * Property: No filename collisions across many uploads
     * 
     * @test
     */
    public function property_no_filename_collisions_across_many_uploads()
    {
        // Arrange: Create many users
        $users = User::factory()->count(100)->create();
        $allPaths = [];
        
        // Act: Upload avatars for all users
        foreach ($users as $user) {
            $file = UploadedFile::fake()->create("image.jpg", 500, 'image/jpeg');
            $path = $this->avatarService->uploadAvatar($user, $file);
            $allPaths[] = $path;
        }
        
        // Assert: No collisions - all paths should be unique
        $uniquePaths = array_unique($allPaths);
        $this->assertCount(
            count($allPaths), 
            $uniquePaths, 
            "No filename collisions should occur across 100 uploads"
        );
        
        // Assert: No overwrites - all files should exist
        foreach ($allPaths as $path) {
            $this->assertTrue(
                Storage::disk('public')->exists($path),
                "All uploaded files should exist without overwrites"
            );
        }
    }
}
