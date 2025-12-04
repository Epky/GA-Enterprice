<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== File Session Checker ===\n\n";

$sessionPath = storage_path('framework/sessions');
$sessionFiles = glob($sessionPath . '/*');

if (empty($sessionFiles)) {
    echo "✗ No session files found\n";
    exit(1);
}

echo "Found " . count($sessionFiles) . " session file(s):\n\n";

foreach ($sessionFiles as $file) {
    if (basename($file) === '.gitignore') {
        continue;
    }
    
    echo "Session: " . basename($file) . "\n";
    
    $content = file_get_contents($file);
    
    // Laravel sessions are serialized
    try {
        $data = unserialize($content);
        
        // Look for the auth user ID
        $userId = null;
        foreach ($data as $key => $value) {
            if (strpos($key, 'login_web_') === 0) {
                $userId = $value;
                break;
            }
        }
        
        if ($userId) {
            $user = DB::table('users')->where('id', $userId)->first();
            if ($user) {
                $roleIcon = $user->role === 'admin' ? '✓' : '✗';
                echo "  {$roleIcon} User: {$user->email}\n";
                echo "  Role: {$user->role}\n";
                echo "  Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
                
                if ($user->role !== 'admin') {
                    echo "  ⚠ This user does NOT have admin access!\n";
                    echo "  → You need to log in with an admin account\n";
                    echo "  → Or change this user's role to 'admin'\n";
                }
            } else {
                echo "  ✗ User ID {$userId} not found in database\n";
            }
        } else {
            echo "  ✗ No authenticated user in this session\n";
        }
    } catch (Exception $e) {
        echo "  ✗ Could not parse session data\n";
    }
    
    echo "\n";
}

echo "Available admin accounts:\n";
$adminUsers = DB::table('users')->where('role', 'admin')->where('is_active', true)->get();
foreach ($adminUsers as $user) {
    echo "  • {$user->email}\n";
}

echo "\n";
echo "To fix:\n";
echo "1. Log out from your current account\n";
echo "2. Log in with one of the admin accounts above\n";
echo "3. Or run: php fix-user-admin-role.php your@email.com\n";
echo "\n";
