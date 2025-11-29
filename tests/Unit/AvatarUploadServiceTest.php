<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AvatarUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AvatarUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvatarUploadService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake the public storage disk
        Storage::fake('public');
        
        $this->service = app(AvatarUploadService::class);
        
        // Create a user with profile
        $this->user = User::factory()->create();
        $this->user->profile()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /** @test */
    public function it_validates_and_accepts_jpeg_files()
    {
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        
        $path = $this->service->uploadAvatar($this->user, $file);
        
        $this->assertNotNull($path);
        $this->assertStringContainsString('avatars/', $path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function it_validates_and_accepts_png_files()
    {
        $file = UploadedFile::fake()->create('avatar.png', 100, 'image/png');
        
        $path = $this->service->uploadAvatar($this->user, $file);
        
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function it_validates_and_accepts_gif_files()
    {
        $file = UploadedFile::fake()->create('avatar.gif', 100, 'image/gif');
        
        $path = $this->service->uploadAvatar($this->user, $file);
        
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function it_validates_and_accepts_webp_files()
    {
        $file = UploadedFile::fake()->create('avatar.webp', 100, 'image/webp');
        
        $path = $this->service->uploadAvatar($this->user, $file);
        
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function it_rejects_non_image_files()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The profile picture must be a file of type: jpg, jpeg, png, gif, webp.');
        
        $this->service->uploadAvatar($this->user, $file);
    }

    /** @test */
    public function it_rejects_text_files()
    {
        $file = UploadedFile::fake()->create('file.txt', 100, 'text/plain');
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The profile picture must be a file of type: jpg, jpeg, png, gif, webp.');
        
        $this->service->uploadAvatar($this->user, $file);
    }

    /** @test */
    public function it_rejects_executable_files()
    {
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The profile picture must be a file of type: jpg, jpeg, png, gif, webp.');
        
        $this->service->uploadAvatar($this->user, $file);
    }

    /** @test */
    public function it_accepts_files_under_2mb()
    {
        // Create a file that's 1MB (under the 2MB limit)
        $file = UploadedFile::fake()->create('avatar.jpg', 1024, 'image/jpeg');
        
        $path = $this->service->uploadAvatar($this->user, $file);
        
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function it_accepts_files_exactly_at_2mb()
    {
        // Create a file that's exactly 2MB
        $file = UploadedFile::fake()->create('avatar.jpg', 2048, 'image/jpeg');
        
        $path = $this->service->uploadAvatar($this->user, $file);
        
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function it_rejects_files_over_2mb()
    {
        // Create a file that's 3MB (over the 2MB limit)
        $file = UploadedFile::fake()->create('avatar.jpg', 3072, 'image/jpeg');
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The profile picture must not exceed 2MB.');
        
        $this->service->uploadAvatar($this->user, $file);
    }

    /** @test */
    public function it_rejects_very_large_files()
    {
        // Create a file that's 10MB
        $file = UploadedFile::fake()->create('avatar.jpg', 10240, 'image/jpeg');
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The profile picture must not exceed 2MB.');
        
        $this->service->uploadAvatar($this->user, $file);
    }

    /** @test */
    public function it_generates_unique_filenames_for_same_user()
    {
        $file1 = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        
        $path1 = $this->service->uploadAvatar($this->user, $file1);
        
        // Small delay to ensure different timestamp
        usleep(10000); // 10ms
        
        $path2 = $this->service->uploadAvatar($this->user, $file2);
        
        $this->assertNotEquals($path1, $path2);
        $this->assertStringContainsString('user_' . $this->user->id, $path1);
        $this->assertStringContainsString('user_' . $this->user->id, $path2);
    }

    /** @test */
    public function it_generates_unique_filenames_for_different_users()
    {
        $user2 = User::factory()->create();
        $user2->profile()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
        
        $file1 = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        
        $path1 = $this->service->uploadAvatar($this->user, $file1);
        $path2 = $this->service->uploadAvatar($user2, $file2);
        
        $this->assertNotEquals($path1, $path2);
        $this->assertStringContainsString('user_' . $this->user->id, $path1);
        $this->assertStringContainsString('user_' . $user2->id, $path2);
    }

    /** @test */
    public function it_includes_timestamp_in_filename()
    {
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        
        $path = $this->service->uploadAvatar($this->user, $file);
        
        // Extract filename from path
        $filename = basename($path);
        
        // Should contain user ID, timestamp pattern, and random string
        $this->assertMatchesRegularExpression('/user_\d+_\d{14}_[a-zA-Z0-9]{8}\.jpg/', $filename);
    }

    /** @test */
    public function it_stores_avatar_in_avatars_directory()
    {
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        
        $path = $this->service->uploadAvatar($this->user, $file);
        
        $this->assertStringStartsWith('avatars/', $path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function it_successfully_saves_files_to_storage()
    {
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        
        $path = $this->service->uploadAvatar($this->user, $file);
        
        // Verify file exists in storage
        $this->assertTrue(Storage::disk('public')->exists($path));
        
        // Verify we can retrieve the file
        $this->assertNotNull(Storage::disk('public')->get($path));
    }

    /** @test */
    public function it_updates_user_profile_with_avatar_path()
    {
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        
        $path = $this->service->uploadAvatar($this->user, $file);
        
        $this->user->refresh();
        $this->assertEquals($path, $this->user->profile->avatar_url);
    }

    /** @test */
    public function it_deletes_old_avatar_when_uploading_new_one()
    {
        // Upload first avatar
        $file1 = UploadedFile::fake()->create('avatar1.jpg', 100, 'image/jpeg');
        $path1 = $this->service->uploadAvatar($this->user, $file1);
        
        Storage::disk('public')->assertExists($path1);
        
        // Upload second avatar
        $file2 = UploadedFile::fake()->create('avatar2.jpg', 100, 'image/jpeg');
        $path2 = $this->service->uploadAvatar($this->user, $file2);
        
        // Old avatar should be deleted
        Storage::disk('public')->assertMissing($path1);
        
        // New avatar should exist
        Storage::disk('public')->assertExists($path2);
        
        // Profile should have new avatar
        $this->user->refresh();
        $this->assertEquals($path2, $this->user->profile->avatar_url);
    }

    /** @test */
    public function it_removes_avatar_file_from_storage()
    {
        // Upload an avatar first
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        $path = $this->service->uploadAvatar($this->user, $file);
        
        Storage::disk('public')->assertExists($path);
        
        // Delete the avatar
        $result = $this->service->deleteAvatar($this->user);
        
        $this->assertTrue($result);
        Storage::disk('public')->assertMissing($path);
    }

    /** @test */
    public function it_clears_avatar_url_from_database_on_deletion()
    {
        // Upload an avatar first
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        $this->service->uploadAvatar($this->user, $file);
        
        $this->user->refresh();
        $this->assertNotNull($this->user->profile->avatar_url);
        
        // Delete the avatar
        $this->service->deleteAvatar($this->user);
        
        $this->user->refresh();
        $this->assertNull($this->user->profile->avatar_url);
    }

    /** @test */
    public function it_returns_false_when_deleting_non_existent_avatar()
    {
        // User has no avatar
        $this->assertNull($this->user->profile->avatar_url);
        
        $result = $this->service->deleteAvatar($this->user);
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_deletion_when_file_already_removed()
    {
        // Upload an avatar
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        $path = $this->service->uploadAvatar($this->user, $file);
        
        // Manually delete the file (simulating external deletion)
        Storage::disk('public')->delete($path);
        
        // Should still clear database without error
        $result = $this->service->deleteAvatar($this->user);
        
        $this->assertTrue($result);
        $this->user->refresh();
        $this->assertNull($this->user->profile->avatar_url);
    }

    /** @test */
    public function it_creates_profile_if_not_exists_when_uploading()
    {
        // Create user without profile
        $userWithoutProfile = User::factory()->create();
        $this->assertNull($userWithoutProfile->profile);
        
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        $path = $this->service->uploadAvatar($userWithoutProfile, $file);
        
        $userWithoutProfile->refresh();
        $this->assertNotNull($userWithoutProfile->profile);
        $this->assertEquals($path, $userWithoutProfile->profile->avatar_url);
    }

    /** @test */
    public function it_preserves_file_extension_in_generated_filename()
    {
        $jpgFile = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        $pngFile = UploadedFile::fake()->create('avatar.png', 100, 'image/png');
        
        $jpgPath = $this->service->uploadAvatar($this->user, $jpgFile);
        
        $user2 = User::factory()->create();
        $user2->profile()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
        $pngPath = $this->service->uploadAvatar($user2, $pngFile);
        
        $this->assertStringEndsWith('.jpg', $jpgPath);
        $this->assertStringEndsWith('.png', $pngPath);
    }
}
