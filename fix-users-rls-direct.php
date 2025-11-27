<?php

// Direct connection to Supabase using service role credentials
// This bypasses Laravel's connection pooling

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['SUPABASE_DB_HOST'];
$port = $_ENV['SUPABASE_DB_PORT'];
$database = $_ENV['SUPABASE_DB_DATABASE'];
$username = $_ENV['SUPABASE_DB_USERNAME'];
$password = $_ENV['SUPABASE_DB_PASSWORD'];

echo "Connecting to Supabase directly...\n";
echo "Host: $host\n";
echo "Database: $database\n";
echo "Username: $username\n\n";

try {
    // Create direct PDO connection
    $dsn = "pgsql:host=$host;port=$port;dbname=$database;sslmode=require";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 30,
    ]);
    
    echo "✓ Connected successfully!\n\n";
    
    // Set role to service_role
    echo "Setting role to service_role...\n";
    $pdo->exec("SET ROLE service_role");
    echo "✓ Role set\n\n";
    
    echo "Fixing users table RLS policies...\n";
    
    // Drop existing policies
    echo "Dropping existing policies...\n";
    $pdo->exec("DROP POLICY IF EXISTS service_role_all_access ON users");
    $pdo->exec("DROP POLICY IF EXISTS users_auth_read ON users");
    $pdo->exec("DROP POLICY IF EXISTS users_self_read ON users");
    $pdo->exec("DROP POLICY IF EXISTS users_self_update ON users");
    echo "✓ Dropped old policies\n\n";
    
    // Create new policies
    echo "Creating new policies...\n";
    
    // Policy 1: Service role has full access
    $pdo->exec("
        CREATE POLICY service_role_all_access ON users
        FOR ALL
        TO service_role
        USING (true)
        WITH CHECK (true)
    ");
    echo "✓ Created service_role_all_access policy\n";
    
    // Policy 2: Authenticated users can read their own data
    $pdo->exec("
        CREATE POLICY users_self_read ON users
        FOR SELECT
        TO authenticated
        USING (auth.uid() = id::text)
    ");
    echo "✓ Created users_self_read policy\n";
    
    // Policy 3: Authenticated users can update their own data
    $pdo->exec("
        CREATE POLICY users_self_update ON users
        FOR UPDATE
        TO authenticated
        USING (auth.uid() = id::text)
        WITH CHECK (auth.uid() = id::text)
    ");
    echo "✓ Created users_self_update policy\n";
    
    // Policy 4: Allow anon role to read users for authentication
    $pdo->exec("
        CREATE POLICY users_auth_read ON users
        FOR SELECT
        TO anon
        USING (true)
    ");
    echo "✓ Created users_auth_read policy\n";
    
    echo "\n✅ Successfully fixed users table RLS policies!\n";
    echo "\nYou should now be able to log in.\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
