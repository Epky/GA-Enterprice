<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Boot the model and register event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Clean up avatar when user is being deleted
        static::deleting(function ($user) {
            // Refresh the user to ensure we have the latest profile data
            $user->refresh();
            $user->load('profile');
            
            if ($user->profile && $user->profile->avatar_url) {
                $disk = Storage::disk('public');
                if ($disk->exists($user->profile->avatar_url)) {
                    $disk->delete($user->profile->avatar_url);
                }
            }
        });
    }

    /**
     * Check if user has admin role
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has staff role
     */
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Check if user has customer role
     */
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    /**
     * Get the user's profile
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Get the user's cart
     */
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Get the user's full name (computed from profile if name field is empty)
     */
    public function getFullNameAttribute(): string
    {
        // If name field exists and is not empty, use it
        if (!empty($this->attributes['name'])) {
            return $this->attributes['name'];
        }
        
        // Otherwise, construct from profile
        if ($this->profile) {
            return trim($this->profile->first_name . ' ' . $this->profile->last_name);
        }
        
        return $this->email; // Fallback to email if no profile
    }

    /**
     * Get the user's first name
     */
    public function getFirstNameAttribute(): ?string
    {
        return $this->profile?->first_name;
    }

    /**
     * Get the user's last name
     */
    public function getLastNameAttribute(): ?string
    {
        return $this->profile?->last_name;
    }

    /**
     * Get the user's avatar URL from profile
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->profile?->avatar_url;
    }

    /**
     * Get the user's avatar URL or default placeholder
     */
    public function getAvatarOrDefaultAttribute(): string
    {
        if ($this->avatar_url) {
            return Storage::url($this->avatar_url);
        }
        return $this->getDefaultAvatarUrl();
    }

    /**
     * Get the default avatar URL with user initials
     */
    public function getDefaultAvatarUrl(): string
    {
        $initials = $this->getInitials();
        return "https://ui-avatars.com/api/?name={$initials}&size=200&background=random";
    }

    /**
     * Get the user's initials for avatar placeholder
     */
    public function getInitials(): string
    {
        if ($this->profile && $this->profile->first_name && $this->profile->last_name) {
            $first = substr($this->profile->first_name, 0, 1);
            $last = substr($this->profile->last_name, 0, 1);
            return strtoupper($first . $last);
        }
        return strtoupper(substr($this->email, 0, 2));
    }
}
