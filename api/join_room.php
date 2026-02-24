<?php
// api/join_room.php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'POST request required');
}

$player_name = isset($_POST['player_name']) ? trim($_POST['player_name']) : '';
$room_code = isset($_POST['room_code']) ? strtoupper(trim($_POST['room_code'])) : '';

if (empty($player_name) || empty($room_code)) {
    jsonResponse(false, [], 'Name and Room Code are required.');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id, status FROM SpenderGame_games WHERE room_code = ? FOR UPDATE");
    $stmt->execute([$room_code]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        throw new Exception("Room not found.");
    }
    if ($game['status'] !== 'waiting') {
        throw new Exception("Game has already started or finished.");
    }

    $game_id = $game['id'];

    // Check player count (max 4)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM SpenderGame_players WHERE game_id = ?");
    $stmt->execute([$game_id]);
    $player_count = $stmt->fetchColumn();

    if ($player_count >= 4) {
        throw new Exception("Room is full.");
    }

    // Generate session ID for the new player
    $session_id = session_id();

    // Check if player already in room (rejoin logic)
    $stmt = $pdo->prepare("SELECT id FROM SpenderGame_players WHERE game_id = ? AND session_id = ?");
    $stmt->execute([$game_id, $session_id]);
    $existing_player = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_player) {
        $player_id = $existing_player['id'];
    } else {
        // Create new player
        $stmt = $pdo->prepare("INSERT INTO SpenderGame_players (game_id, name, session_id, is_host) VALUES (?, ?, ?, FALSE)");
        $stmt->execute([$game_id, $player_name, $session_id]);
        $player_id = $pdo->lastInsertId();
    }

    $_SESSION['player_id'] = $player_id;
    $_SESSION['game_id'] = $game_id;
    $_SESSION['room_code'] = $room_code;

    $pdo->commit();
    jsonResponse(true, ['room_code' => $room_code], 'Joined room successfully.');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, [], $e->getMessage());
}
?>