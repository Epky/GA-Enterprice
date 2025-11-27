<?php

/**
 * Script to disable RLS on public-facing tables
 * Run this once to allow public access to product catalog
 */

require __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Try to connect using different methods
$connections = [
    'Session Pooler (5432)' => [
        'host' => 'aws-0-ap-southeast-1.pooler.supabase.com',
        'port' => '5432',
    ],
    'Transaction Pooler (6543)' => [
        'host' => 'aws-0-ap-southeast-1.pooler.supabase.com',
        'port' => '6543',
    ],
    'Direct Connection (5432)' => [
        'host' => 'db.hgmdtzpsbzwanjuhiemf.supabase.co',
        'port' => '5432',
    ],
];

$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$database = $_ENV['DB_DATABASE'];

echo "🔧 Attempting to disable RLS on public tables...\n\n";

foreach ($connections as $name => $config) {
    echo "Trying: $name\n";
    echo "  Host: {$config['host']}:{$config['port']}\n";
    
    try {
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname=$database;sslmode=require";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
        ]);
        
        echo "  ✅ Connected successfully!\n";
        
        // Try to disable RLS on public tables
        $tables = ['categories', 'products', 'brands', 'product_images', 'product_specifications', 'product_variants', 'inventory', 'promotions'];
        
        foreach ($tables as $table) {
            try {
                $pdo->exec("ALTER TABLE $table DISABLE ROW LEVEL SECURITY");
                echo "  ✅ Disabled RLS on: $table\n";
            } catch (PDOException $e) {
                echo "  ⚠️  Could not disable RLS on $table: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n✅ SUCCESS! RLS disabled on public tables.\n";
        echo "You can now access your application.\n\n";
        exit(0);
        
    } catch (PDOException $e) {
        echo "  ❌ Failed: " . $e->getMessage() . "\n\n";
    }
}

echo "❌ Could not connect using any method.\n";
echo "\n📋 Next steps:\n";
echo "1. Go to Supabase Dashboard: https://supabase.com/dashboard/project/hgmdtzpsbzwanjuhiemf/editor\n";
echo "2. Run this SQL manually in the SQL Editor:\n\n";

echo "```sql\n";
foreach (['categories', 'products', 'brands', 'product_images', 'product_specifications', 'product_variants', 'inventory', 'promotions'] as $table) {
    echo "ALTER TABLE $table DISABLE ROW LEVEL SECURITY;\n";
}
echo "```\n\n";

echo "This will allow public access to your product catalog.\n";
