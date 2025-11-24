<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔧 Starting admin account creation process...');
        
        try {
            // Define admin account data
            $adminEmail = 'admin@admin.com';
            $adminData = [
                'name' => 'Administrator',
                'email' => $adminEmail,
                'password' => Hash::make('admin'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ];

            // Validate required fields
            $this->validateAdminData($adminData);

            // Check if admin account already exists
            $existingAdmin = User::where('email', $adminEmail)->first();
            
            if ($existingAdmin) {
                $this->command->warn("⚠️  Admin account with email '{$adminEmail}' already exists. Skipping creation.");
                $this->command->info("   Account ID: {$existingAdmin->id}");
                $this->command->info("   Account Name: {$existingAdmin->name}");
                $this->command->info("   Account Role: {$existingAdmin->role}");
                $this->command->info("   Account Status: " . ($existingAdmin->is_active ? 'Active' : 'Inactive'));
                return;
            }

            // Create the admin account
            $admin = User::create($adminData);
            
            $this->command->info("✅ Admin account created successfully!");
            $this->command->info("   Email: {$admin->email}");
            $this->command->info("   Password: admin (Please change after first login)");
            $this->command->info("   Role: {$admin->role}");
            $this->command->info("   Status: " . ($admin->is_active ? 'Active' : 'Inactive'));
            $this->command->info("   Account ID: {$admin->id}");
            
            // Log the creation for audit purposes
            Log::info('Admin account created via seeder', [
                'admin_id' => $admin->id,
                'admin_email' => $admin->email,
                'created_at' => $admin->created_at
            ]);
            
        } catch (Exception $e) {
            $this->command->error("❌ Failed to create admin account: " . $e->getMessage());
            Log::error('Admin account creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Validate admin account data
     *
     * @param array $data
     * @throws Exception
     */
    private function validateAdminData(array $data): void
    {
        $requiredFields = ['name', 'email', 'password', 'role', 'is_active'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                throw new Exception("Required field '{$field}' is missing or empty");
            }
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format: {$data['email']}");
        }

        // Validate role
        $validRoles = ['admin', 'staff', 'customer'];
        if (!in_array($data['role'], $validRoles)) {
            throw new Exception("Invalid role: {$data['role']}. Must be one of: " . implode(', ', $validRoles));
        }

        // Validate is_active is boolean
        if (!is_bool($data['is_active'])) {
            throw new Exception("Field 'is_active' must be a boolean value");
        }
    }
}
