<?php
/**
 * Database Setup Script
 * Run this to create the school_sms database
 */

// Load environment variables from .env file
$envFile = __DIR__ . '/.env';
$envVars = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $envVars[trim($key)] = trim($value);
    }
}

$host = $envVars['DB_HOST'] ?? '127.0.0.1';
$port = $envVars['DB_PORT'] ?? '5432';
$dbname = 'postgres'; // Connect to default postgres database first
$username = $envVars['DB_USERNAME'] ?? 'postgres';
$password = $envVars['DB_PASSWORD'] ?? '';

try {
    // Connect to PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✓ Connected to PostgreSQL successfully!\n\n";
    
    // Check if database exists
    $stmt = $pdo->query("SELECT 1 FROM pg_database WHERE datname = 'school_sms'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "✓ Database 'school_sms' already exists!\n";
    } else {
        echo "Creating database 'school_sms'...\n";
        $pdo->exec("CREATE DATABASE school_sms WITH ENCODING='UTF8'");
        echo "✓ Database 'school_sms' created successfully!\n";
    }
    
    // Test connection to the new database
    $dsn = "pgsql:host=$host;port=$port;dbname=school_sms";
    $pdo = new PDO($dsn, $username, $password);
    echo "✓ Successfully connected to 'school_sms' database!\n\n";
    
    echo "==============================================\n";
    echo "✓ Database setup completed successfully!\n";
    echo "==============================================\n\n";
    echo "Next steps:\n";
    echo "1. Run: php artisan migrate\n";
    echo "2. Run: php artisan db:seed (optional)\n";
    echo "3. Run: php artisan serve\n\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
    echo "Please check:\n";
    echo "1. PostgreSQL service is running\n";
    echo "2. Username and password are correct in .env file\n";
    echo "3. PostgreSQL is listening on port 5432\n";
}
