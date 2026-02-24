<?php
// api/create_room.php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'POST request required');
}

$player_name = isset($_POST['player_name']) ? trim($_POST['player_name']) : '';
if (empty($player_name)) {
    jsonResponse(false, [], 'Player name is required.');
}

try {
    $pdo->beginTransaction();

    // Generate unique 5-character room code
    $room_code = '';
    $is_unique = false;
    while (!$is_unique) {
        $room_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 5));
        $stmt = $pdo->prepare("SELECT id FROM SpenderGame_games WHERE room_code = ?");
        $stmt->execute([$room_code]);
        if (!$stmt->fetch()) {
            $is_unique = true;
        }
    }

    // Create game in 'waiting' state
    $stmt = $pdo->prepare("INSERT INTO SpenderGame_games (room_code, status) VALUES (?, 'waiting')");
    $stmt->execute([$room_code]);
    $game_id = $pdo->lastInsertId();

    // Generate session ID for the host
    $session_id = session_id();

    // Create host player
    $stmt = $pdo->prepare("INSERT INTO SpenderGame_players (game_id, name, session_id, is_host) VALUES (?, ?, ?, TRUE)");
    $stmt->execute([$game_id, $player_name, $session_id]);
    $player_id = $pdo->lastInsertId();

    $_SESSION['player_id'] = $player_id;
    $_SESSION['game_id'] = $game_id;
    $_SESSION['room_code'] = $room_code;

    $pdo->commit();
    jsonResponse(true, ['room_code' => $room_code], 'Room created successfully.');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, [], $e->getMessage());
}
?>