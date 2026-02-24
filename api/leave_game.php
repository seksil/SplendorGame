<?php
// api/leave_game.php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'POST request required');
}

$player_id = isset($_SESSION['player_id']) ? intval($_SESSION['player_id']) : 0;
$game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;

if ($player_id <= 0 || $game_id <= 0) {
    jsonResponse(false, [], 'Invalid request.');
}

try {
    // Delete player from the game
    $stmt = $pdo->prepare("DELETE FROM SpenderGame_players WHERE id = ? AND game_id = ?");
    $stmt->execute([$player_id, $game_id]);

    // Clear session
    unset($_SESSION['player_id']);
    unset($_SESSION['game_id']);

    jsonResponse(true, [], 'Left game successfully.');

} catch (Exception $e) {
    jsonResponse(false, [], $e->getMessage());
}
?>