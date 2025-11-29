<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AvatarWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user with profile
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'role' => 'customer',
        ]);

        $this->user->profile()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Fake the storage for testing
        Storage::fake('public');
    }

    /**
     * Test full upload flow from form submission to display
     * Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 5.1, 5.2, 5.3
     */
    public function test_full_upload_flow_from_submission_to_display(): void
    {
        // Step 1: Visit profile edit page
        $response = $this->actingAs($this->user)->get(route('profile.edit'));
        $response->assertStatus(200);
        
        // Assert profile picture section is present
        $response->assertSee('Profile Picture');
        $response->assertSee('Upload a profile picture to personalize your account');
        
        // Assert file input is present
        $response->assertSee('avatar-input', false);
        
        // Assert default placeholder is shown (user has no avatar yet)
        $content = $response->getContent();
        $this->assertStringContainsString('ui-avatars.com', $content);

        // Step 2: Upload a valid avatar
        // Create a fake image file without requiring GD extension
        $file = UploadedFile::fake()->create('avatar.jpg', 1024, 'image/jpeg');
        
        $uploadResponse = $this->actingAs($this->user)
            ->post(route('profile.avatar.upload'), [
                'avatar' => $file,
            ]);

        // Assert redirect back to profile edit
        $uploadResponse->assertRedirect(route('profile.edit'));
        $uploadResponse->assertSessionHas('status', 'avatar-uploaded');

        // Assert file was stored
        $this->user->refresh();
        $this->assertNotNull($this->user->profile->avatar_url);
        Storage::disk('public')->assertExists($this->user->profile->avatar_url);

        // Step 3: Verify avatar displays on profile page
        $displayResponse = $this->actingAs($this->user)->get(route('profile.edit'));
        $displayResponse->assertStatus(200);
        
        // Assert success message is shown
        $displayResponse->assertSee('Profile picture uploaded successfully');
        
        // Assert remove button is now visible
        $displayResponse->assertSee('Remove Picture');
        
        // Assert avatar URL is in the page
        $avatarUrl = Storage::url($this->user->profile->avatar_url);
        $displayResponse->assertSee($avatarUrl, false);

        // Step 4: Verify avatar displays in navigation
        $dashboardResponse = $this->actingAs($this->user)->get(route('customer.dashboard'));
        $dashboardResponse->assertStatus(200);
        
        // Avatar should be displayed in navigation
        $dashboardContent = $dashboardResponse->getContent();
        $this->assertStringContainsString($this->user->profile->avatar_url, $dashboardContent);
    }

    /**
     * Test update flow (upload, then upload new avatar)
     * Requirements: 3.1, 3.2, 3.3, 3.4
     */
    public function test_update_flow_replaces_old_avatar(): void
    {
        // Step 1: Upload first avatar
        $firstFile = UploadedFile::fake()->create('first-avatar.jpg', 500, 'image/jpeg');
        
        $this->actingAs($this->user)
            ->post(route('profile.avatar.upload'), [
                'avatar' => $firstFile,
            ]);

        $this->user->refresh();
        $firstAvatarPath = $this->user->profile->avatar_url;
        
        // Assert first avatar was stored
        $this->assertNotNull($firstAvatarPath);
        Storage::disk('public')->assertExists($firstAvatarPath);

        // Step 2: Upload second avatar (update)
        $secondFile = UploadedFile::fake()->create('second-avatar.png', 800, 'image/png');
        
        $updateResponse = $this->actingAs($this->user)
            ->post(route('profile.avatar.upload'), [
                'avatar' => $secondFile,
            ]);

        $updateResponse->assertRedirect(route('profile.edit'));
        $updateResponse->assertSessionHas('status', 'avatar-uploaded');

        // Step 3: Verify old avatar was deleted
        Storage::disk('public')->assertMissing($firstAvatarPath);

        // Step 4: Verify new avatar was stored
        $this->user->refresh();
        $secondAvatarPath = $this->user->profile->avatar_url;
        
        $this->assertNotNull($secondAvatarPath);
        $this->assertNotEquals($firstAvatarPath, $secondAvatarPath);
        Storage::disk('public')->assertExists($secondAvatarPath);

        // Step 5: Verify new avatar displays on profile page
        $displayResponse = $this->actingAs($this->user)->get(route('profile.edit'));
        $displayResponse->assertStatus(200);
        
        $newAvatarUrl = Storage::url($secondAvatarPath);
        $displayResponse->assertSee($newAvatarUrl, false);
        
        // Old avatar URL should not be present
        $oldAvatarUrl = Storage::url($firstAvatarPath);
        $displayResponse->assertDontSee($oldAvatarUrl, false);

        // Step 6: Verify success message
        $displayResponse->assertSee('Profile picture uploaded successfully');
    }

    /**
     * Test delete flow (upload, then delete avatar)
     * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 5.4
     */
    public function test_delete_flow_removes_avatar_and_shows_placeholder(): void
    {
        // Step 1: Upload an avatar first
        $file = UploadedFile::fake()->create('avatar.jpg', 1000, 'image/jpeg');
        
        $this->actingAs($this->user)
            ->post(route('profile.avatar.upload'), [
                'avatar' => $file,
            ]);

        $this->user->refresh();
        $avatarPath = $this->user->profile->avatar_url;
        
        // Assert avatar was stored
        $this->assertNotNull($avatarPath);
        Storage::disk('public')->assertExists($avatarPath);

        // Step 2: Verify remove button is visible
        $beforeDeleteResponse = $this->actingAs($this->user)->get(route('profile.edit'));
        $beforeDeleteResponse->assertStatus(200);
        $beforeDeleteResponse->assertSee('Remove Picture');

        // Step 3: Delete the avatar
        $deleteResponse = $this->actingAs($this->user)
            ->delete(route('profile.avatar.delete'));

        $deleteResponse->assertRedirect(route('profile.edit'));
        $deleteResponse->assertSessionHas('status', 'avatar-deleted');

        // Step 4: Verify avatar was deleted from storage
        Storage::disk('public')->assertMissing($avatarPath);

        // Step 5: Verify avatar_url was cleared in database
        $this->user->refresh();
        $this->assertNull($this->user->profile->avatar_url);

        // Step 6: Verify placeholder is shown on profile page
        $afterDeleteResponse = $this->actingAs($this->user)->get(route('profile.edit'));
        $afterDeleteResponse->assertStatus(200);
        
        // Assert remove button is no longer visible
        $afterDeleteResponse->assertDontSee('Remove Picture');
        
        // Assert default placeholder is shown
        $content = $afterDeleteResponse->getContent();
        $this->assertStringContainsString('ui-avatars.com', $content);

        // Step 7: Verify placeholder displays in navigation
        $dashboardResponse = $this->actingAs($this->user)->get(route('customer.dashboard'));
        $dashboardResponse->assertStatus(200);
        
        // Should show initials-based placeholder
        $dashboardContent = $dashboardResponse->getContent();
        $this->assertStringContainsString('JD', $dashboardContent); // John Doe initials
    }

    /**
     * Test avatar display across multiple pages
     * Requirements: 5.1, 5.2, 5.3, 5.5
     */
    public function test_avatar_displays_consistently_across_pages(): void
    {
        // Upload an avatar
        $file = UploadedFile::fake()->create('avatar.jpg', 800, 'image/jpeg');
        
        $this->actingAs($this->user)
            ->post(route('profile.avatar.upload'), [
                'avatar' => $file,
            ]);

        $this->user->refresh();
        $avatarPath = $this->user->profile->avatar_url;

        // Test 1: Profile edit page
        $profileResponse = $this->actingAs($this->user)->get(route('profile.edit'));
        $profileResponse->assertStatus(200);
        $profileResponse->assertSee($avatarPath, false);

        // Test 2: Customer dashboard
        $dashboardResponse = $this->actingAs($this->user)->get(route('customer.dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee($avatarPath, false);

        // Test 3: Cart page
        $cartResponse = $this->actingAs($this->user)->get(route('cart.index'));
        $cartResponse->assertStatus(200);
        $cartResponse->assertSee($avatarPath, false);

        // Verify consistent styling (all should use rounded-full class)
        $profileContent = $profileResponse->getContent();
        $dashboardContent = $dashboardResponse->getContent();
        $cartContent = $cartResponse->getContent();

        $this->assertStringContainsString('rounded-full', $profileContent);
        $this->assertStringContainsString('rounded-full', $dashboardContent);
        $this->assertStringContainsString('rounded-full', $cartContent);
    }

    /**
     * Test avatar display for different user roles
     * Requirements: 5.1, 5.3
     */
    public function test_avatar_displays_for_different_roles(): void
    {
        // Test for staff user
        $staffUser = User::factory()->create(['role' => 'staff']);
        $staffUser->profile()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $staffFile = UploadedFile::fake()->create('staff-avatar.jpg', 500, 'image/jpeg');
        
        $this->actingAs($staffUser)
            ->post(route('profile.avatar.upload'), [
                'avatar' => $staffFile,
            ]);

        $staffUser->refresh();
        
        // Verify staff avatar displays on staff dashboard
        $staffDashboard = $this->actingAs($staffUser)->get(route('staff.dashboard'));
        $staffDashboard->assertStatus(200);
        $staffDashboard->assertSee($staffUser->profile->avatar_url, false);

        // Test for admin user
        $adminUser = User::factory()->create(['role' => 'admin']);
        $adminUser->profile()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);

        $adminFile = UploadedFile::fake()->create('admin-avatar.jpg', 500, 'image/jpeg');
        
        $this->actingAs($adminUser)
            ->post(route('profile.avatar.upload'), [
                'avatar' => $adminFile,
            ]);

        $adminUser->refresh();
        
        // Verify admin avatar displays on admin dashboard
        $adminDashboard = $this->actingAs($adminUser)->get(route('admin.dashboard'));
        $adminDashboard->assertStatus(200);
        $adminDashboard->assertSee($adminUser->profile->avatar_url, false);
    }

    /**
     * Test invalid file upload shows error and maintains state
     * Requirements: 1.5
     */
    public function test_invalid_file_upload_shows_error(): void
    {
        // Try to upload a non-image file
        $invalidFile = UploadedFile::fake()->create('document.pdf', 500);
        
        $response = $this->actingAs($this->user)
            ->post(route('profile.avatar.upload'), [
                'avatar' => $invalidFile,
            ]);

        // Assert validation error
        $response->assertSessionHasErrors('avatar');

        // Verify no avatar was stored
        $this->user->refresh();
        $this->assertNull($this->user->profile->avatar_url);

        // Verify no files were created in storage
        $files = Storage::disk('public')->allFiles('avatars');
        $this->assertEmpty($files);
    }

    /**
     * Test oversized file upload shows error
     * Requirements: 1.3
     */
    public function test_oversized_file_upload_shows_error(): void
    {
        // Try to upload a file larger than 2MB
        $largeFile = UploadedFile::fake()->create('large-avatar.jpg', 3000, 'image/jpeg'); // 3MB
        
        $response = $this->actingAs($this->user)
            ->post(route('profile.avatar.upload'), [
                'avatar' => $largeFile,
            ]);

        // Assert validation error
        $response->assertSessionHasErrors('avatar');

        // Verify no avatar was stored
        $this->user->refresh();
        $this->assertNull($this->user->profile->avatar_url);
    }

    /**
     * Test avatar cleanup on user deletion
     * Requirements: 6.5
     */
    public function test_avatar_cleanup_on_user_deletion(): void
    {
        // Upload an avatar
        $file = UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg');
        
        $this->actingAs($this->user)
            ->post(route('profile.avatar.upload'), [
                'avatar' => $file,
            ]);

        $this->user->refresh();
        $avatarPath = $this->user->profile->avatar_url;
        
        // Verify avatar exists
        Storage::disk('public')->assertExists($avatarPath);

        // Delete the user
        $this->user->delete();

        // Verify avatar file was deleted
        Storage::disk('public')->assertMissing($avatarPath);
    }

    /**
     * Test multiple sequential uploads and deletes
     * Requirements: 3.2, 4.2, 4.3
     */
    public function test_multiple_sequential_uploads_and_deletes(): void
    {
        $avatarPaths = [];

        // Upload first avatar
        $file1 = UploadedFile::fake()->create('avatar1.jpg', 500, 'image/jpeg');
        $this->actingAs($this->user)->post(route('profile.avatar.upload'), ['avatar' => $file1]);
        $this->user->refresh();
        $avatarPaths[] = $this->user->profile->avatar_url;

        // Upload second avatar (should delete first)
        $file2 = UploadedFile::fake()->create('avatar2.png', 500, 'image/png');
        $this->actingAs($this->user)->post(route('profile.avatar.upload'), ['avatar' => $file2]);
        $this->user->refresh();
        $avatarPaths[] = $this->user->profile->avatar_url;

        // Verify first avatar was deleted
        Storage::disk('public')->assertMissing($avatarPaths[0]);
        Storage::disk('public')->assertExists($avatarPaths[1]);

        // Delete avatar
        $this->actingAs($this->user)->delete(route('profile.avatar.delete'));
        $this->user->refresh();

        // Verify second avatar was deleted
        Storage::disk('public')->assertMissing($avatarPaths[1]);
        $this->assertNull($this->user->profile->avatar_url);

        // Upload third avatar
        $file3 = UploadedFile::fake()->create('avatar3.gif', 500, 'image/gif');
        $this->actingAs($this->user)->post(route('profile.avatar.upload'), ['avatar' => $file3]);
        $this->user->refresh();
        $avatarPaths[] = $this->user->profile->avatar_url;

        // Verify third avatar exists
        Storage::disk('public')->assertExists($avatarPaths[2]);

        // Verify only the latest avatar exists
        $allFiles = Storage::disk('public')->allFiles('avatars');
        $this->assertCount(1, $allFiles);
    }

    /**
     * Test placeholder display for user without profile
     * Requirements: 5.4
     */
    public function test_placeholder_display_for_user_without_profile(): void
    {
        // Create user without profile
        $userWithoutProfile = User::factory()->create([
            'email' => 'noprofile@example.com',
            'role' => 'customer',
        ]);

        // Visit profile page
        $response = $this->actingAs($userWithoutProfile)->get(route('profile.edit'));
        $response->assertStatus(200);

        // Should show placeholder with email initials
        $content = $response->getContent();
        $this->assertStringContainsString('ui-avatars.com', $content);
        
        // Should use first 2 characters of email as initials
        $expectedInitials = strtoupper(substr('noprofile@example.com', 0, 2));
        $this->assertStringContainsString($expectedInitials, $content);
    }

    /**
     * Test concurrent avatar operations don't cause conflicts
     * Requirements: 6.3
     */
    public function test_concurrent_avatar_operations_no_conflicts(): void
    {
        // Create multiple users
        $users = User::factory()->count(3)->create(['role' => 'customer']);
        
        foreach ($users as $user) {
            $user->profile()->create([
                'first_name' => 'User',
                'last_name' => "Number{$user->id}",
            ]);
        }

        // Upload avatars for all users
        foreach ($users as $user) {
            $file = UploadedFile::fake()->create("avatar-{$user->id}.jpg", 500, 'image/jpeg');
            
            $this->actingAs($user)
                ->post(route('profile.avatar.upload'), [
                    'avatar' => $file,
                ]);
        }

        // Verify each user has their own unique avatar
        $avatarPaths = [];
        foreach ($users as $user) {
            $user->refresh();
            $this->assertNotNull($user->profile->avatar_url);
            
            // Ensure no duplicate paths
            $this->assertNotContains($user->profile->avatar_url, $avatarPaths);
            $avatarPaths[] = $user->profile->avatar_url;
            
            // Verify file exists
            Storage::disk('public')->assertExists($user->profile->avatar_url);
        }

        // Verify we have exactly 3 unique avatar files
        $this->assertCount(3, $avatarPaths);
        $this->assertCount(3, array_unique($avatarPaths));
    }
}
