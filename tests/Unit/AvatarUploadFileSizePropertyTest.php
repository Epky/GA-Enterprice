<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AvatarUploadService;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

/**
 * Feature: user-profile-picture-upload, Property 3: File size validation
 * Validates: Requirements 1.3
 */
class AvatarUploadFileSizePropertyTest extends TestCase
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
     * Property 3: File size validation
     * For any file exceeding 2MB in size, the upload attempt should be rejected
     * with an error message, and no changes should be made to storage or database.
     * 
     * @test
     */
    public function property_files_exceeding_2mb_are_rejected()
    {
        // Test with various file sizes over 2MB (2048KB)
        $oversizedFiles = [
            2049, // Just over limit
            2100, // Slightly over
            3000, // 3MB
            5000, // 5MB
            10000, // 10MB
        ];

        foreach ($oversizedFiles as $sizeInKB) {
            // Arrange: Create a user
            $user = User::factory()->create();
            
            // Create an oversized file
            $file = UploadedFile::fake()->create("large_avatar.jpg", $sizeInKB, 'image/jpeg');
            
            // Act & Assert: Upload should throw ValidationException
            try {
                $this->avatarService->uploadAvatar($user, $file);
                $this->fail("Expected ValidationException for file size: {$sizeInKB}KB");
            } catch (ValidationException $e) {
                // Assert: Error message should mention size limit
                $errors = $e->errors();
                $this->assertArrayHasKey('avatar', $errors, 
                    "Error should be for 'avatar' field (size: {$sizeInKB}KB)");
                $this->assertStringContainsString('2MB', $errors['avatar'][0], 
                    "Error message should mention 2MB limit (size: {$sizeInKB}KB)");
            }
            
            // Assert: No profile should be created
            $user->refresh();
            $this->assertNull($user->profile, 
                "Profile should not be created for oversized file: {$sizeInKB}KB");
            
            // Assert: No files should be stored
            $files = Storage::disk('public')->allFiles('avatars');
            $this->assertEmpty($files, 
                "No files should be stored for oversized file: {$sizeInKB}KB");
            
            // Clean up
            $user->delete();
        }
    }

    /**
     * Property: Files at exactly 2MB are accepted
     * 
     * @test
     */
    public function property_files_at_exactly_2mb_are_accepted()
    {
        // Arrange: Create file at exactly 2MB (2048KB)
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('avatar.jpg', 2048, 'image/jpeg');
        
        // Act: Upload should succeed
        $path = $this->avatarService->uploadAvatar($user, $file);
        
        // Assert: File should be stored
        Storage::disk('public')->assertExists($path);
        $user->refresh();
        $this->assertEquals($path, $user->profile->avatar_url);
    }

    /**
     * Property: Files just under 2MB are accepted
     * 
     * @test
     */
    public function property_files_just_under_2mb_are_accepted()
    {
        // Test files just under the limit
        $validSizes = [2047, 2046, 2040, 2000, 1500, 1000];
        
        foreach ($validSizes as $sizeInKB) {
            // Arrange
            $user = User::factory()->create();
            $file = UploadedFile::fake()->create("avatar_{$sizeInKB}.jpg", $sizeInKB, 'image/jpeg');
            
            // Act
            $path = $this->avatarService->uploadAvatar($user, $file);
            
            // Assert
            Storage::disk('public')->assertExists($path);
            $user->refresh();
            $this->assertEquals($path, $user->profile->avatar_url, 
                "File of size {$sizeInKB}KB should be accepted");
            
            // Clean up
            Storage::disk('public')->delete($path);
            $user->profile()->delete();
            $user->delete();
        }
    }

    /**
     * Property: Oversized file rejection preserves existing profile
     * 
     * @test
     */
    public function property_oversized_file_preserves_existing_profile()
    {
        // Arrange: Create user with profile
        $user = User::factory()->create();
        $user->profile()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'avatar_url' => null,
        ]);
        
        $originalProfile = $user->profile->toArray();
        
        // Create oversized file
        $file = UploadedFile::fake()->create('large.jpg', 3000, 'image/jpeg');
        
        // Act & Assert: Upload should fail
        try {
            $this->avatarService->uploadAvatar($user, $file);
            $this->fail("Expected ValidationException for oversized file");
        } catch (ValidationException $e) {
            // Expected
        }
        
        // Assert: Profile should remain unchanged
        $user->refresh();
        $this->assertEquals($originalProfile['first_name'], $user->profile->first_name);
        $this->assertEquals($originalProfile['last_name'], $user->profile->last_name);
        $this->assertNull($user->profile->avatar_url, "Avatar URL should remain null");
    }

    /**
     * Property: Oversized file rejection preserves existing avatar
     * 
     * @test
     */
    public function property_oversized_file_preserves_existing_avatar()
    {
        // Arrange: Create user with existing avatar
        $user = User::factory()->create();
        
        // Upload a valid avatar first
        $validFile = UploadedFile::fake()->create('avatar.jpg', 1000, 'image/jpeg');
        $originalPath = $this->avatarService->uploadAvatar($user, $validFile);
        
        $user->refresh();
        $this->assertEquals($originalPath, $user->profile->avatar_url);
        
        // Try to upload oversized file
        $oversizedFile = UploadedFile::fake()->create('large.jpg', 5000, 'image/jpeg');
        
        // Act & Assert: Upload should fail
        try {
            $this->avatarService->uploadAvatar($user, $oversizedFile);
            $this->fail("Expected ValidationException for oversized file");
        } catch (ValidationException $e) {
            // Expected
        }
        
        // Assert: Original avatar should still exist
        $user->refresh();
        $this->assertEquals($originalPath, $user->profile->avatar_url, 
            "Original avatar URL should be preserved");
        Storage::disk('public')->assertExists($originalPath);
        
        // Assert: No new files should be created
        $files = Storage::disk('public')->allFiles('avatars');
        $this->assertCount(1, $files, "Only the original avatar should exist");
    }

    /**
     * Property: Random file sizes over 2MB are consistently rejected
     * 
     * @test
     */
    public function property_random_oversized_files_are_rejected()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Arrange: Create random oversized file (2049KB to 10000KB)
            $user = User::factory()->create();
            $sizeInKB = rand(2049, 10000);
            
            $file = UploadedFile::fake()->create("avatar_{$iteration}.jpg", $sizeInKB, 'image/jpeg');
            
            // Act & Assert
            $exceptionThrown = false;
            try {
                $this->avatarService->uploadAvatar($user, $file);
            } catch (ValidationException $e) {
                $exceptionThrown = true;
                $errors = $e->errors();
                $this->assertArrayHasKey('avatar', $errors);
            }
            
            $this->assertTrue($exceptionThrown, 
                "ValidationException should be thrown for size: {$sizeInKB}KB (iteration {$iteration})");
            
            // Assert: No changes to database or storage
            $user->refresh();
            $this->assertNull($user->profile, 
                "No profile should be created (iteration {$iteration})");
            
            $files = Storage::disk('public')->allFiles('avatars');
            $this->assertEmpty($files, 
                "No files should be stored (iteration {$iteration})");
            
            // Clean up
            $user->delete();
        }
    }

    /**
     * Property: Random file sizes under 2MB are consistently accepted
     * 
     * @test
     */
    public function property_random_valid_sized_files_are_accepted()
    {
        for ($iteration = 0; $iteration < 50; $iteration++) {
            // Arrange: Create random valid-sized file (1KB to 2048KB)
            $user = User::factory()->create();
            $sizeInKB = rand(1, 2048);
            
            $file = UploadedFile::fake()->create("avatar_{$iteration}.jpg", $sizeInKB, 'image/jpeg');
            
            // Act
            $path = $this->avatarService->uploadAvatar($user, $file);
            
            // Assert
            $this->assertNotNull($path, 
                "Path should be returned for size: {$sizeInKB}KB (iteration {$iteration})");
            Storage::disk('public')->assertExists($path);
            
            $user->refresh();
            $this->assertNotNull($user->profile, 
                "Profile should be created (iteration {$iteration})");
            $this->assertEquals($path, $user->profile->avatar_url, 
                "Avatar URL should be set (iteration {$iteration})");
            
            // Clean up
            Storage::disk('public')->delete($path);
            $user->profile()->delete();
            $user->delete();
        }
    }

    /**
     * Property: Very small files are accepted
     * 
     * @test
     */
    public function property_very_small_files_are_accepted()
    {
        // Test with very small file sizes
        $smallSizes = [1, 5, 10, 50, 100];
        
        foreach ($smallSizes as $sizeInKB) {
            // Arrange
            $user = User::factory()->create();
            $file = UploadedFile::fake()->create("tiny_{$sizeInKB}.jpg", $sizeInKB, 'image/jpeg');
            
            // Act
            $path = $this->avatarService->uploadAvatar($user, $file);
            
            // Assert
            Storage::disk('public')->assertExists($path);
            $user->refresh();
            $this->assertEquals($path, $user->profile->avatar_url, 
                "File of size {$sizeInKB}KB should be accepted");
            
            // Clean up
            Storage::disk('public')->delete($path);
            $user->profile()->delete();
            $user->delete();
        }
    }
}
