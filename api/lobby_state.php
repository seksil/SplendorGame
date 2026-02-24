<?php
// api/lobby_state.php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, [], 'GET request required');
}

$room_code = isset($_GET['room_code']) ? strtoupper(trim($_GET['room_code'])) : '';

if (empty($room_code)) {
    jsonResponse(false, [], 'Room code required');
}

try {
    $stmt = $pdo->prepare("SELECT id, status FROM SpenderGame_games WHERE room_code = ?");
    $stmt->execute([$room_code]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        jsonResponse(false, [], 'Room not found');
    }

    $stmt = $pdo->prepare("SELECT id, name, is_host, turn_order FROM SpenderGame_players WHERE game_id = ? ORDER BY id ASC");
    $stmt->execute([$game['id']]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If game is active, find the first player by turn_order for the reveal animation
    $first_player = null;
    $player_order = [];
    if ($game['status'] === 'active') {
        $sorted = $players;
        usort($sorted, function ($a, $b) {
            return $a['turn_order'] - $b['turn_order']; });
        $player_order = array_column($sorted, 'name');
        $first_player = $player_order[0] ?? null;
    }

    jsonResponse(true, [
        'game' => $game,
        'players' => $players,
        'first_player' => $first_player,
        'player_order' => $player_order
    ], 'Lobby state retrieved');

} catch (Exception $e) {
    jsonResponse(false, [], $e->getMessage());
}
?>