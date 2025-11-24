<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StaffUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default staff user if it doesn't exist
        \App\Models\User::firstOrCreate(
            ['email' => 'staff@staff.com'],
            [
                'name' => 'Staff Member',
                'email' => 'staff@staff.com',
                'password' => \Illuminate\Support\Facades\Hash::make('staff'),
                'role' => 'staff',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
