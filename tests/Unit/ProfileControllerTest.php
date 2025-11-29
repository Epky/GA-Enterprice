<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Http\Controllers\ProfileController;
use App\Services\AvatarUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private ProfileController $controller;
    private AvatarUploadService $avatarService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake the public storage disk
        Storage::fake('public');
        
        $this->avatarService = app(AvatarUploadService::class);
        $this->controller = new ProfileController($this->avatarService);
        
        // Create a user with profile
        $this->user = User::factory()->create();
        $this->user->profile()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /** @test */
    public function it_uploads_avatar_with_valid_file()
    {
        // Authenticate the user
        $this->actingAs($this->user);
        
        // Create a valid image file (using create instead of image to avoid GD dependency)
        $file = UploadedFile::fake()->create('avatar.jpg', 1024, 'image/jpeg');
        
        // Create request with the file
        $request = Request::create('/profile/avatar', 'POST', [], [], ['avatar' => $file]);
        $request->setUserResolver(fn() => $this->user);
        
        // Call the uploadAvatar method
        $response = $this->controller->uploadAvatar($request);
        
        // Assert redirect with success status
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('profile.edit'), $response->getTargetUrl());
        $this->assertEquals('avatar-uploaded', $response->getSession()->get('status'));
        
        // Assert file was stored
        $this->user->refresh();
        $this->assertNotNull($this->user->profile->avatar_url);
        $this->assertTrue(Storage::disk('public')->exists($this->user->profile->avatar_url));
    }

    /** @test */
    public function it_rejects_upload_with_invalid_file_type()
    {
        // Authenticate the user
        $this->actingAs($this->user);
        
        // Create an invalid file (PDF)
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        
        // Create request with the file
        $request = Request::create('/profile/avatar', 'POST', [], [], ['avatar' => $file]);
        $request->setUserResolver(fn() => $this->user);
        
        // Expect validation exception to be thrown
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        // Call the uploadAvatar method
        $this->controller->uploadAvatar($request);
    }

    /** @test */
    public function it_rejects_upload_with_oversized_file()
    {
        // Authenticate the user
        $this->actingAs($this->user);
        
        // Create an oversized file (3MB)
        $file = UploadedFile::fake()->create('avatar.jpg', 3072, 'image/jpeg');
        
        // Create request with the file
        $request = Request::create('/profile/avatar', 'POST', [], [], ['avatar' => $file]);
        $request->setUserResolver(fn() => $this->user);
        
        // Expect validation exception to be thrown
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        // Call the uploadAvatar method
        $this->controller->uploadAvatar($request);
    }

    /** @test */
    public function it_deletes_avatar_and_removes_file_and_clears_database()
    {
        // Authenticate the user
        $this->actingAs($this->user);
        
        // First upload an avatar
        $file = UploadedFile::fake()->create('avatar.jpg', 1024, 'image/jpeg');
        $path = $this->avatarService->uploadAvatar($this->user, $file);
        
        $this->user->refresh();
        $this->assertNotNull($this->user->profile->avatar_url);
        $this->assertTrue(Storage::disk('public')->exists($path));
        
        // Create request for deletion
        $request = Request::create('/profile/avatar', 'DELETE');
        $request->setUserResolver(fn() => $this->user);
        
        // Call the deleteAvatar method
        $response = $this->controller->deleteAvatar($request);
        
        // Assert redirect with success status
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('profile.edit'), $response->getTargetUrl());
        $this->assertEquals('avatar-deleted', $response->getSession()->get('status'));
        
        // Assert file was removed from storage
        $this->assertFalse(Storage::disk('public')->exists($path));
        
        // Assert database was cleared
        $this->user->refresh();
        $this->assertNull($this->user->profile->avatar_url);
    }

    /** @test */
    public function it_prevents_user_from_uploading_avatar_for_another_user()
    {
        // Create two users
        $user1 = User::factory()->create();
        $user1->profile()->create([
            'first_name' => 'User',
            'last_name' => 'One',
        ]);
        
        $user2 = User::factory()->create();
        $user2->profile()->create([
            'first_name' => 'User',
            'last_name' => 'Two',
        ]);
        
        // Authenticate as user1
        $this->actingAs($user1);
        
        // Create a valid image file
        $file = UploadedFile::fake()->create('avatar.jpg', 1024, 'image/jpeg');
        
        // Create request with the file, but try to set it for user2
        $request = Request::create('/profile/avatar', 'POST', [], [], ['avatar' => $file]);
        // The controller uses $request->user(), so it will always use the authenticated user
        $request->setUserResolver(fn() => $user1);
        
        // Call the uploadAvatar method
        $response = $this->controller->uploadAvatar($request);
        
        // Assert the avatar was set for user1 (the authenticated user)
        $user1->refresh();
        $this->assertNotNull($user1->profile->avatar_url);
        
        // Assert user2's avatar was not affected
        $user2->refresh();
        $this->assertNull($user2->profile->avatar_url);
    }

    /** @test */
    public function it_prevents_user_from_deleting_another_users_avatar()
    {
        // Create two users with avatars
        $user1 = User::factory()->create();
        $user1->profile()->create([
            'first_name' => 'User',
            'last_name' => 'One',
        ]);
        
        $user2 = User::factory()->create();
        $user2->profile()->create([
            'first_name' => 'User',
            'last_name' => 'Two',
        ]);
        
        // Upload avatars for both users
        $file1 = UploadedFile::fake()->create('avatar1.jpg', 1024, 'image/jpeg');
        $path1 = $this->avatarService->uploadAvatar($user1, $file1);
        
        $file2 = UploadedFile::fake()->create('avatar2.jpg', 1024, 'image/jpeg');
        $path2 = $this->avatarService->uploadAvatar($user2, $file2);
        
        // Authenticate as user1
        $this->actingAs($user1);
        
        // Create request for deletion
        $request = Request::create('/profile/avatar', 'DELETE');
        // The controller uses $request->user(), so it will always use the authenticated user
        $request->setUserResolver(fn() => $user1);
        
        // Call the deleteAvatar method
        $response = $this->controller->deleteAvatar($request);
        
        // Assert user1's avatar was deleted
        $user1->refresh();
        $this->assertNull($user1->profile->avatar_url);
        $this->assertFalse(Storage::disk('public')->exists($path1));
        
        // Assert user2's avatar was not affected
        $user2->refresh();
        $this->assertNotNull($user2->profile->avatar_url);
        $this->assertTrue(Storage::disk('public')->exists($path2));
    }

    /** @test */
    public function it_requires_authentication_for_avatar_upload()
    {
        // Don't authenticate any user
        
        // Create a valid image file
        $file = UploadedFile::fake()->create('avatar.jpg', 1024, 'image/jpeg');
        
        // Create request with the file but no authenticated user
        $request = Request::create('/profile/avatar', 'POST', [], [], ['avatar' => $file]);
        $request->setUserResolver(fn() => null);
        
        // Expect an error when trying to call user() on null
        $this->expectException(\Error::class);
        
        // Call the uploadAvatar method
        $this->controller->uploadAvatar($request);
    }

    /** @test */
    public function it_requires_authentication_for_avatar_deletion()
    {
        // Don't authenticate any user
        
        // Create request for deletion but no authenticated user
        $request = Request::create('/profile/avatar', 'DELETE');
        $request->setUserResolver(fn() => null);
        
        // Expect an error when trying to call user() on null
        $this->expectException(\Error::class);
        
        // Call the deleteAvatar method
        $this->controller->deleteAvatar($request);
    }

    /** @test */
    public function it_handles_service_exception_during_upload()
    {
        // Authenticate the user
        $this->actingAs($this->user);
        
        // Create a valid image file
        $file = UploadedFile::fake()->create('avatar.jpg', 1024, 'image/jpeg');
        
        // Mock the service to throw an exception
        $mockService = $this->createMock(AvatarUploadService::class);
        $mockService->method('uploadAvatar')
            ->willThrowException(new \Exception('Storage failure'));
        
        $controller = new ProfileController($mockService);
        
        // Create request with the file
        $request = Request::create('/profile/avatar', 'POST', [], [], ['avatar' => $file]);
        $request->setUserResolver(fn() => $this->user);
        
        // Call the uploadAvatar method
        $response = $controller->uploadAvatar($request);
        
        // Assert redirect with error
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->getSession()->has('errors'));
        $errors = $response->getSession()->get('errors');
        $this->assertTrue($errors->has('avatar'));
    }

    /** @test */
    public function it_handles_service_exception_during_deletion()
    {
        // Authenticate the user
        $this->actingAs($this->user);
        
        // Mock the service to throw an exception
        $mockService = $this->createMock(AvatarUploadService::class);
        $mockService->method('deleteAvatar')
            ->willThrowException(new \Exception('Deletion failure'));
        
        $controller = new ProfileController($mockService);
        
        // Create request for deletion
        $request = Request::create('/profile/avatar', 'DELETE');
        $request->setUserResolver(fn() => $this->user);
        
        // Call the deleteAvatar method
        $response = $controller->deleteAvatar($request);
        
        // Assert redirect with error
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->getSession()->has('errors'));
        $errors = $response->getSession()->get('errors');
        $this->assertTrue($errors->has('avatar'));
    }
}
