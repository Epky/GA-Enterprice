<?php

echo "=== Supabase Connection Diagnostic Tool ===\n\n";

// Load environment
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['SUPABASE_DB_HOST'] ?? 'not set';
$port = $_ENV['SUPABASE_DB_PORT'] ?? 'not set';
$database = $_ENV['SUPABASE_DB_DATABASE'] ?? 'not set';
$username = $_ENV['SUPABASE_DB_USERNAME'] ?? 'not set';
$password = $_ENV['SUPABASE_DB_PASSWORD'] ?? 'not set';

echo "Current Configuration:\n";
echo "  Host: $host\n";
echo "  Port: $port\n";
echo "  Database: $database\n";
echo "  Username: $username\n";
echo "  Password: " . (strlen($password) > 0 ? str_repeat('*', min(strlen($password), 20)) : 'not set') . "\n\n";

// Test 1: Try direct connection with pooler
echo "Test 1: Connection Pooler (Port 6543)...\n";
try {
    $dsn = "pgsql:host=$host;port=6543;dbname=$database;sslmode=require";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,
    ]);
    echo "✅ SUCCESS! Connection pooler works on port 6543\n\n";
    $pdo = null;
} catch (PDOException $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 2: Try session pooler
echo "Test 2: Session Pooler (Port 5432)...\n";
try {
    $dsn = "pgsql:host=$host;port=5432;dbname=$database;sslmode=require";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,
    ]);
    echo "✅ SUCCESS! Session pooler works on port 5432\n\n";
    $pdo = null;
} catch (PDOException $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 3: Try with just 'postgres' as username
echo "Test 3: Trying with simplified username 'postgres'...\n";
try {
    $dsn = "pgsql:host=$host;port=6543;dbname=$database;sslmode=require";
    $pdo = new PDO($dsn, 'postgres', $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,
    ]);
    echo "✅ SUCCESS! Works with username 'postgres'\n\n";
    $pdo = null;
} catch (PDOException $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n\n";
}

echo "\n=== Diagnosis Complete ===\n\n";

echo "NEXT STEPS:\n\n";
echo "1. Go to your Supabase Dashboard:\n";
echo "   https://supabase.com/dashboard/project/hgmdtzpsbzwanjuhiemf/settings/database\n\n";

echo "2. Look for the 'Connection string' section\n\n";

echo "3. Copy the EXACT connection string shown there\n";
echo "   It should look like:\n";
echo "   postgresql://postgres.[ref]:[password]@[host]:[port]/postgres\n\n";

echo "4. Update your .env file with the EXACT values from that string\n\n";

echo "5. Common issues:\n";
echo "   - Username should be 'postgres.hgmdtzpsbzwanjuhiemf' (with the dot)\n";
echo "   - Password must be URL-decoded if it contains special characters\n";
echo "   - Host should be 'aws-0-ap-southeast-1.pooler.supabase.com'\n";
echo "   - Port is usually 6543 for transaction mode or 5432 for session mode\n\n";

echo "6. If ALL tests failed, your Supabase project might be:\n";
echo "   - Paused (free tier projects pause after inactivity)\n";
echo "   - Using wrong credentials\n";
echo "   - Behind a firewall\n\n";

echo "7. To unpause a project:\n";
echo "   - Go to https://supabase.com/dashboard/project/hgmdtzpsbzwanjuhiemf\n";
echo "   - Look for a 'Resume' or 'Restore' button\n";
echo "   - Wait a few minutes after resuming\n\n";
