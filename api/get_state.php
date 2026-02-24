<?php
// api/get_state.php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, [], 'GET request required');
}

$game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;

if ($game_id <= 0) {
    jsonResponse(false, [], 'Invalid game ID');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM SpenderGame_games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        jsonResponse(false, [], 'Game not found');
    }

    // Decode JSON strings into arrays for frontend
    $game['tokens_available'] = json_decode($game['tokens_available'], true);
    $game['board_cards'] = json_decode($game['board_cards'], true);
    $game['board_nobles'] = json_decode($game['board_nobles'], true);

    // We don't send the full remaining decks to frontend to prevent cheating or large payload
    unset($game['board_cards']['decks']);

    // Get all players
    $stmt = $pdo->prepare("SELECT * FROM SpenderGame_players WHERE game_id = ? ORDER BY turn_order ASC");
    $stmt->execute([$game_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($players as &$player) {
        $player['tokens_owned'] = json_decode($player['tokens_owned'], true);
        $player['cards_owned'] = json_decode($player['cards_owned'], true);

        // Hide actual reserved cards from other players (only return count)
        $reserved = json_decode($player['cards_reserved'], true);
        $player['reserved_count'] = count($reserved);
        // $player['cards_reserved'] will remain the full array, we will filter it based on who is asking later if needed,
        // but since this is local multiplayer, we pass the full array for the active player UI to render.
        $player['cards_reserved'] = $reserved;

        $player['nobles_owned'] = json_decode($player['nobles_owned'], true);
    }

    jsonResponse(true, [
        'game' => $game,
        'players' => $players
    ], 'Game state retrieved');

} catch (Exception $e) {
    jsonResponse(false, [], $e->getMessage());
}
?>