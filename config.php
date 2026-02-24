<?php
// config.php
session_start();

define('APP_VERSION', '1.0.0');

// Environment Detection
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'seksil.udru.ac.th') {
    // Production (seksil.udru.ac.th)
    $host = 'localhost'; // Usually localhost on server
    $db_user = 'seksil';  // Matching the local db_name and URL pattern
    $db_pass = 'o6249@MS';
    $db_name = 'seksil';
} else {
    // Local Development
    $host = '127.0.0.1';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'seksil';
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