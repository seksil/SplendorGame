<?php
// api/track_visit.php — Record visitor data
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'POST request required');
}

$page = isset($_POST['page']) ? trim($_POST['page']) : 'unknown';
$player_name = isset($_POST['player_name']) ? trim($_POST['player_name']) : '';

// Get visitor info
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
// Take first IP if multiple (proxy chain)
if (strpos($ip, ',') !== false) {
    $ip = trim(explode(',', $ip)[0]);
}
$user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512);
$referrer = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 512);
$sess_id = session_id();

try {
    // Spam protection: don't record same IP+page within 5 minutes
    $stmt = $pdo->prepare("
        SELECT id FROM SpenderGame_visitors 
        WHERE ip_address = ? AND page_visited = ? 
        AND visited_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        LIMIT 1
    ");
    $stmt->execute([$ip, $page]);

    if ($stmt->fetch()) {
        jsonResponse(true, [], 'Already recorded recently');
    }

    // Insert visit record
    $stmt = $pdo->prepare("
        INSERT INTO SpenderGame_visitors (ip_address, user_agent, page_visited, referrer, session_id, player_name)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$ip, $user_agent, $page, $referrer, $sess_id, $player_name]);

    jsonResponse(true, [], 'Visit recorded');

} catch (Exception $e) {
    jsonResponse(false, [], $e->getMessage());
}
?>