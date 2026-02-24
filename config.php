<?php
// config.php
session_start();

$host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'seksil';

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