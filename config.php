<?php
// config.php
session_start();

define('APP_VERSION', '1.0.0');

// Simple .env parser for local development
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Environment Detection
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'seksil.udru.ac.th') {
    // Production (seksil.udru.ac.th)
    $host = getenv('PROD_DB_HOST') ?: 'localhost';
    $db_user = getenv('PROD_DB_USER') ?: 'seksil';
    $db_pass = getenv('PROD_DB_PASS') ?: '';
    $db_name = getenv('PROD_DB_NAME') ?: 'seksil';
} else {
    // Local Development
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $db_user = getenv('DB_USER') ?: 'root';
    $db_pass = getenv('DB_PASS') ?: '';
    $db_name = getenv('DB_NAME') ?: 'seksil';
}

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Select DB if it exists
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'");
    if ($stmt->fetch()) {
        $pdo->query("USE $db_name");
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to send JSON response
function jsonResponse($success, $data = [], $message = '')
{
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}
?>