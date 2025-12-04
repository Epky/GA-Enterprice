<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Admin Access Diagnostic ===\n\n";

// 1. Check admin users
echo "1. Admin Users in Database:\n";
$adminUsers = DB::table('users')->where('role', 'admin')->get();
if ($adminUsers->isEmpty()) {
    echo "   ✗ No admin users found!\n";
} else {
    foreach ($adminUsers as $user) {
        $active = $user->is_active ? '✓' : '✗';
        echo "   {$active} {$user->email} (ID: {$user->id})\n";
    }
}
echo "\n";

// 2. Check active sessions
echo "2. Active Sessions:\n";
$sessions = DB::table('sessions')->get();
if ($sessions->isEmpty()) {
    echo "   ✗ No active sessions found\n";
    echo "   → You need to log in through the browser\n";
} else {
    echo "   Found {$sessions->count()} active session(s)\n";
    foreach ($sessions as $session) {
        // Try to decode the payload
        $payload = unserialize(base64_decode($session->payload));
        $userId = $payload['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'] ?? null;
        
        if ($userId) {
            $user = DB::table('users')->where('id', $userId)->first();
            if ($user) {
                $roleIcon = $user->role === 'admin' ? '✓' : '✗';
                echo "   {$roleIcon} Session for: {$user->email} (Role: {$user->role})\n";
                echo "      Last Activity: " . date('Y-m-d H:i:s', $session->last_activity) . "\n";
            }
        }
    }
}
echo "\n";

// 3. Recommendations
echo "3. Recommendations:\n";

$hasAdminUser = !$adminUsers->isEmpty();
$hasActiveSession = !$sessions->isEmpty();

if (!$hasAdminUser) {
    echo "   ✗ Create an admin user first\n";
    echo "     Run: php artisan tinker\n";
    echo "     Then: User::where('email', 'your@email.com')->update(['role' => 'admin']);\n";
} elseif (!$hasActiveSession) {
    echo "   → Log in through your browser with an admin account:\n";
    foreach ($adminUsers as $user) {
        if ($user->is_active) {
            echo "     • {$user->email}\n";
        }
    }
} else {
    // Check if any active session is for an admin
    $hasAdminSession = false;
    foreach ($sessions as $session) {
        $payload = unserialize(base64_decode($session->payload));
        $userId = $payload['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'] ?? null;
        if ($userId) {
            $user = DB::table('users')->where('id', $userId)->first();
            if ($user && $user->role === 'admin') {
                $hasAdminSession = true;
                break;
            }
        }
    }
    
    if ($hasAdminSession) {
        echo "   ✓ You have an active admin session\n";
        echo "   → Try clearing your browser cache and cookies\n";
        echo "   → Or log out and log back in\n";
    } else {
        echo "   ✗ Your current session is not for an admin user\n";
        echo "   → Log out and log in with an admin account:\n";
        foreach ($adminUsers as $user) {
            if ($user->is_active) {
                echo "     • {$user->email}\n";
            }
        }
    }
}

echo "\n";

// 4. Quick fix option
echo "4. Quick Fix Options:\n";
echo "   A. Change your current user to admin:\n";
echo "      php fix-user-admin-role.php your@email.com\n\n";
echo "   B. Clear all sessions (force re-login):\n";
echo "      php clear-sessions.php\n\n";
echo "   C. Log in with an existing admin account:\n";
foreach ($adminUsers as $user) {
    if ($user->is_active) {
        echo "      • {$user->email}\n";
    }
}

echo "\n";
