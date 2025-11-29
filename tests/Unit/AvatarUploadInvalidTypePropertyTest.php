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
 * Feature: user-profile-picture-upload, Property 2: Invalid file type rejection
 * Validates: Requirements 1.5
 */
class AvatarUploadInvalidTypePropertyTest extends TestCase
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
     * Property 2: Invalid file type rejection
     * For any file that is not an allowed image type (jpg, jpeg, png, gif, webp),
     * the upload attempt should be rejected with an appropriate error message,
     * and no changes should be made to storage or database.
     * 
     * @test
     */
    public function property_invalid_file_type_is_rejected()
    {
        // Test with various invalid MIME types
        $invalidMimeTypes = [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.ms-excel' => 'xls',
            'text/plain' => 'txt',
            'application/zip' => 'zip',
            'video/mp4' => 'mp4',
            'audio/mpeg' => 'mp3',
            'application/json' => 'json',
            'text/html' => 'html',
            'application/xml' => 'xml',
        ];

        foreach ($invalidMimeTypes as $mimeType => $extension) {
            // Arrange: Create a user
            $user = User::factory()->create();
            
            // Create a file with invalid MIME type
            $file = UploadedFile::fake()->create("document.{$extension}", 500, $mimeType);
            
            // Act & Assert: Upload should throw ValidationException
            try {
                $this->avatarService->uploadAvatar($user, $file);
                $this->fail("Expected ValidationException for MIME type: {$mimeType}");
            } catch (ValidationException $e) {
                // Assert: Error message should mention valid file types
                $errors = $e->errors();
                $this->assertArrayHasKey('avatar', $errors, 
                    "Error should be for 'avatar' field (MIME: {$mimeType})");
                $this->assertStringContainsString('jpg', $errors['avatar'][0], 
                    "Error message should mention valid types (MIME: {$mimeType})");
            }
            
            // Assert: No profile should be created
            $user->refresh();
            $this->assertNull($user->profile, 
                "Profile should not be created for invalid file type: {$mimeType}");
            
            // Assert: No files should be stored
            $files = Storage::disk('public')->allFiles('avatars');
            $this->assertEmpty($files, 
                "No files should be stored for invalid type: {$mimeType}");
            
            // Clean up
            $user->delete();
        }
    }

    /**
     * Property: Invalid file type rejection with existing profile
     * 
     * @test
     */
    public function property_invalid_file_type_preserves_existing_profile()
    {
        // Arrange: Create user with profile
        $user = User::factory()->create();
        $user->profile()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'avatar_url' => null,
        ]);
        
        $originalProfile = $user->profile->toArray();
        
        // Create invalid file
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');
        
        // Act & Assert: Upload should fail
        try {
            $this->avatarService->uploadAvatar($user, $file);
            $this->fail("Expected ValidationException for PDF file");
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
     * Property: Invalid file type rejection with existing avatar
     * 
     * @test
     */
    public function property_invalid_file_type_preserves_existing_avatar()
    {
        // Arrange: Create user with existing avatar
        $user = User::factory()->create();
        
        // Upload a valid avatar first
        $validFile = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');
        $originalPath = $this->avatarService->uploadAvatar($user, $validFile);
        
        $user->refresh();
        $this->assertEquals($originalPath, $user->profile->avatar_url);
        
        // Try to upload invalid file
        $invalidFile = UploadedFile::fake()->create('document.txt', 500, 'text/plain');
        
        // Act & Assert: Upload should fail
        try {
            $this->avatarService->uploadAvatar($user, $invalidFile);
            $this->fail("Expected ValidationException for text file");
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
     * Property: Random invalid MIME types are consistently rejected
     * 
     * @test
     */
    public function property_random_invalid_mime_types_are_rejected()
    {
        // Generate random invalid MIME types
        $invalidPrefixes = ['application/', 'text/', 'video/', 'audio/'];
        $invalidSuffixes = ['pdf', 'doc', 'txt', 'mp4', 'mp3', 'zip', 'xml', 'json'];
        
        for ($iteration = 0; $iteration < 20; $iteration++) {
            // Arrange
            $user = User::factory()->create();
            
            // Create random invalid MIME type
            $prefix = $invalidPrefixes[array_rand($invalidPrefixes)];
            $suffix = $invalidSuffixes[array_rand($invalidSuffixes)];
            $mimeType = $prefix . $suffix;
            
            $file = UploadedFile::fake()->create("file.{$suffix}", rand(100, 1000), $mimeType);
            
            // Act & Assert
            $exceptionThrown = false;
            try {
                $this->avatarService->uploadAvatar($user, $file);
            } catch (ValidationException $e) {
                $exceptionThrown = true;
            }
            
            $this->assertTrue($exceptionThrown, 
                "ValidationException should be thrown for MIME type: {$mimeType} (iteration {$iteration})");
            
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
     * Property: Image MIME types with wrong extensions are handled correctly
     * 
     * @test
     */
    public function property_correct_mime_type_with_wrong_extension_is_accepted()
    {
        // Arrange: File with correct MIME type but wrong extension
        $user = User::factory()->create();
        
        // Create file with image MIME type but non-image extension
        // The service validates by MIME type, not extension
        $file = UploadedFile::fake()->create('avatar.txt', 500, 'image/jpeg');
        
        // Act: Upload should succeed because MIME type is valid
        $path = $this->avatarService->uploadAvatar($user, $file);
        
        // Assert: File should be stored
        Storage::disk('public')->assertExists($path);
        $user->refresh();
        $this->assertEquals($path, $user->profile->avatar_url);
    }

    /**
     * Property: Empty or null MIME type is rejected
     * 
     * @test
     */
    public function property_empty_mime_type_is_rejected()
    {
        // Arrange
        $user = User::factory()->create();
        
        // Create file with empty MIME type
        $file = UploadedFile::fake()->create('file.unknown', 500, '');
        
        // Act & Assert
        try {
            $this->avatarService->uploadAvatar($user, $file);
            $this->fail("Expected ValidationException for empty MIME type");
        } catch (ValidationException $e) {
            // Expected
        }
        
        // Assert: No changes
        $user->refresh();
        $this->assertNull($user->profile);
    }
}
