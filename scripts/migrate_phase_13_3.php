<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Database\PDOFactory;
use Dotenv\Dotenv;

// Load .env if it exists, but don't crash if it doesn't (relies on system env)
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbName = $_ENV['DB_NAME'] ?? 'test';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

$factory = new PDOFactory($host, $dbName, $user, $pass);
$pdo = $factory->create();

echo "Migrating verification_codes table...\n";

try {
    // Check if columns exist to avoid error
    $stmt = $pdo->query("SHOW COLUMNS FROM verification_codes LIKE 'identity_type'");
    if ($stmt->fetch()) {
        echo "Column identity_type already exists. Skipping rename.\n";
    } else {
        $pdo->exec("ALTER TABLE verification_codes CHANGE COLUMN subject_type identity_type VARCHAR(32) NOT NULL");
        echo "Renamed subject_type to identity_type.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM verification_codes LIKE 'identity_id'");
    if ($stmt->fetch()) {
        echo "Column identity_id already exists. Skipping rename.\n";
    } else {
        $pdo->exec("ALTER TABLE verification_codes CHANGE COLUMN subject_identifier identity_id VARCHAR(64) NOT NULL");
        echo "Renamed subject_identifier to identity_id.\n";
    }

    // Add indices
    try {
        $pdo->exec("CREATE INDEX idx_identity_lookup ON verification_codes (identity_type, identity_id, purpose)");
        echo "Created index idx_identity_lookup.\n";
    } catch (PDOException $e) {
        // Index might exist or duplicate key name
        echo "Index idx_identity_lookup creation skipped/failed: " . $e->getMessage() . "\n";
    }

    try {
        $pdo->exec("CREATE INDEX idx_code_hash ON verification_codes (code_hash)");
        echo "Created index idx_code_hash.\n";
    } catch (PDOException $e) {
        echo "Index idx_code_hash creation skipped/failed: " . $e->getMessage() . "\n";
    }

    echo "Migration complete.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
