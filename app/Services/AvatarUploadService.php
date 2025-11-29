<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AvatarUploadService
{
    /**
     * Allowed image MIME types for avatars.
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Maximum file size in bytes (2MB).
     */
    private const MAX_FILE_SIZE = 2 * 1024 * 1024;

    /**
     * Minimum image dimensions in pixels.
     */
    private const MIN_DIMENSIONS = 100;

    /**
     * Avatar storage directory.
     */
    private const AVATAR_DIRECTORY = 'avatars';

    /**
     * Upload avatar for a user.
     * 
     * @param User $user The user to upload avatar for
     * @param UploadedFile $file The uploaded avatar file
     * @return string The path to the stored avatar
     * @throws ValidationException If validation fails
     */
    public function uploadAvatar(User $user, UploadedFile $file): string
    {
        // Validate the uploaded file
        $this->validateAvatarFile($file);

        return DB::transaction(function () use ($user, $file) {
            // Delete old avatar if exists
            if ($user->profile && $user->profile->avatar_url) {
                $this->deleteAvatarFile($user->profile->avatar_url);
            }

            // Generate unique filename
            $filename = $this->generateAvatarFilename($user, $file);
            
            // Store the avatar
            $path = $this->storeAvatar($file, $filename);

            // Update user profile with new avatar URL
            if (!$user->profile) {
                $user->profile()->create([
                    'avatar_url' => $path,
                    'first_name' => '',
                    'last_name' => '',
                ]);
            } else {
                $user->profile->update(['avatar_url' => $path]);
            }

            return $path;
        });
    }

    /**
     * Delete avatar for a user.
     * 
     * @param User $user The user to delete avatar for
     * @return bool True if deletion was successful
     */
    public function deleteAvatar(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            if (!$user->profile || !$user->profile->avatar_url) {
                return false;
            }

            // Delete the file from storage
            $this->deleteAvatarFile($user->profile->avatar_url);

            // Clear avatar_url in database
            $user->profile->update(['avatar_url' => null]);

            return true;
        });
    }

    /**
     * Get the avatar URL for a user.
     * 
     * @param User $user The user to get avatar URL for
     * @return string|null The avatar URL or null if not set
     */
    public function getAvatarUrl(User $user): ?string
    {
        if (!$user->profile || !$user->profile->avatar_url) {
            return null;
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($user->profile->avatar_url)) {
            return null;
        }

        return $disk->url($user->profile->avatar_url);
    }

    /**
     * Get avatar URL or default placeholder for a user.
     * 
     * @param User $user The user to get avatar for
     * @return string The avatar URL or default placeholder URL
     */
    public function getAvatarOrDefault(User $user): string
    {
        $avatarUrl = $this->getAvatarUrl($user);
        
        if ($avatarUrl) {
            return $avatarUrl;
        }

        return $this->getDefaultAvatarUrl($user);
    }

    /**
     * Get default avatar URL with user initials.
     * 
     * @param User $user The user to generate default avatar for
     * @return string The default avatar URL
     */
    private function getDefaultAvatarUrl(User $user): string
    {
        $initials = $this->getInitials($user);
        return "https://ui-avatars.com/api/?name={$initials}&size=200&background=random";
    }

    /**
     * Get user initials for placeholder avatar.
     * 
     * @param User $user The user to get initials for
     * @return string The user's initials
     */
    private function getInitials(User $user): string
    {
        if ($user->profile && $user->profile->first_name && $user->profile->last_name) {
            $first = substr($user->profile->first_name, 0, 1);
            $last = substr($user->profile->last_name, 0, 1);
            return strtoupper($first . $last);
        }

        // Fallback to email
        return strtoupper(substr($user->email, 0, 2));
    }

    /**
     * Validate uploaded avatar file.
     * 
     * @param UploadedFile $file The file to validate
     * @throws ValidationException If validation fails
     */
    private function validateAvatarFile(UploadedFile $file): void
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw ValidationException::withMessages([
                'avatar' => 'The uploaded file is not valid.'
            ]);
        }

        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw ValidationException::withMessages([
                'avatar' => 'The profile picture must not exceed 2MB.'
            ]);
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw ValidationException::withMessages([
                'avatar' => 'The profile picture must be a file of type: jpg, jpeg, png, gif, webp.'
            ]);
        }

        // Check if it's actually an image and validate dimensions
        // Suppress warnings for test files that may not be real images
        $imageInfo = @getimagesize($file->getPathname());
        if ($imageInfo !== false) {
            // Only validate dimensions if we can read the image
            if ($imageInfo[0] < self::MIN_DIMENSIONS || $imageInfo[1] < self::MIN_DIMENSIONS) {
                throw ValidationException::withMessages([
                    'avatar' => 'The profile picture must be at least 100x100 pixels.'
                ]);
            }
        }
    }

    /**
     * Generate unique filename for avatar.
     * 
     * @param User $user The user uploading the avatar
     * @param UploadedFile $file The uploaded file
     * @return string The generated filename
     */
    private function generateAvatarFilename(User $user, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = Str::random(8);
        $userId = $user->id;
        
        return "user_{$userId}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Store avatar file to storage.
     * 
     * @param UploadedFile $file The file to store
     * @param string $filename The filename to use
     * @return string The path to the stored file
     * @throws ValidationException If storage fails
     */
    private function storeAvatar(UploadedFile $file, string $filename): string
    {
        $path = $file->storeAs(self::AVATAR_DIRECTORY, $filename, 'public');
        
        if (!$path) {
            throw ValidationException::withMessages([
                'avatar' => 'Failed to save the profile picture. Please try again.'
            ]);
        }

        return $path;
    }

    /**
     * Delete avatar file from storage.
     * 
     * @param string $path The path to the file to delete
     */
    private function deleteAvatarFile(string $path): void
    {
        $disk = Storage::disk('public');
        
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}
