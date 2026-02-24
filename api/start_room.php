<?php
// api/start_room.php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'POST request required');
}

$room_code = isset($_POST['room_code']) ? strtoupper(trim($_POST['room_code'])) : '';
$player_id = isset($_SESSION['player_id']) ? $_SESSION['player_id'] : 0;

if (empty($room_code) || $player_id == 0) {
    jsonResponse(false, [], 'Invalid request.');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM SpenderGame_games WHERE room_code = ? FOR UPDATE");
    $stmt->execute([$room_code]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game)
        throw new Exception("Room not found.");
    if ($game['status'] !== 'waiting')
        throw new Exception("Game has already started.");

    $game_id = $game['id'];

    // Ensure requesting player is host
    $stmt = $pdo->prepare("SELECT is_host FROM SpenderGame_players WHERE id = ? AND game_id = ?");
    $stmt->execute([$player_id, $game_id]);
    $is_host = $stmt->fetchColumn();

    if (!$is_host) {
        throw new Exception("Only the host can start the game.");
    }

    $stmt = $pdo->prepare("SELECT id, name FROM SpenderGame_players WHERE game_id = ? ORDER BY id ASC");
    $stmt->execute([$game_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $player_count = count($players);

    if ($player_count < 2) {
        throw new Exception("Need at least 2 players to start.");
    }

    // --- RANDOM TURN ORDER ---
    shuffle($players);
    $player_ids = array_column($players, 'id');
    $player_names = array_column($players, 'name');

    // --- GAME INITIALIZATION LOGIC (Moved from init_game.php) ---

    $cards = json_decode(file_get_contents('../assets/cards.json'), true);
    $nobles = json_decode(file_get_contents('../assets/nobles.json'), true);

    shuffle($cards);
    shuffle($nobles);

    $gem_count = ($player_count == 2) ? 4 : (($player_count == 3) ? 5 : 7);
    $initial_tokens = [
        'white' => $gem_count,
        'blue' => $gem_count,
        'green' => $gem_count,
        'red' => $gem_count,
        'black' => $gem_count,
        'gold' => 5
    ];

    $board_nobles = array_slice($nobles, 0, $player_count + 1);

    $decks = [1 => [], 2 => [], 3 => []];
    foreach ($cards as $card) {
        $decks[$card['level']][] = $card;
    }

    $board_cards = [
        'level_1' => array_splice($decks[1], 0, 4),
        'level_2' => array_splice($decks[2], 0, 4),
        'level_3' => array_splice($decks[3], 0, 4),
        'decks' => $decks
    ];

    $first_player_id = $player_ids[0];

    // Update game status
    $stmt = $pdo->prepare("UPDATE SpenderGame_games SET status = 'active', turn_player_id = ?, tokens_available = ?, board_cards = ?, board_nobles = ? WHERE id = ?");
    $stmt->execute([
        $first_player_id,
        json_encode($initial_tokens),
        json_encode($board_cards),
        json_encode($board_nobles),
        $game_id
    ]);

    // Update player turn orders and initialize their assets
    $turn_order = 1;
    $empty_tokens = json_encode(['white' => 0, 'blue' => 0, 'green' => 0, 'red' => 0, 'black' => 0, 'gold' => 0]);
    foreach ($player_ids as $pid) {
        $stmt = $pdo->prepare("UPDATE SpenderGame_players SET tokens_owned = ?, cards_owned = '[]', cards_reserved = '[]', nobles_owned = '[]', turn_order = ? WHERE id = ?");
        $stmt->execute([$empty_tokens, $turn_order, $pid]);
        $turn_order++;
    }

    $pdo->commit();
    jsonResponse(true, [
        'game_id' => $game_id,
        'player_order' => $player_names,
        'first_player' => $player_names[0]
    ], 'Game started.');

} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    jsonResponse(false, [], $e->getMessage());
}
?>