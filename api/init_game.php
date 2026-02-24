<?php
// api/init_game.php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'POST request required');
}

$player_names = isset($_POST['players']) ? $_POST['players'] : [];
// Remove empty names
$player_names = array_filter($player_names, function ($v) {
    return trim($v) !== '';
});

if (count($player_names) < 2 || count($player_names) > 4) {
    jsonResponse(false, [], 'Game requires 2 to 4 players.');
}

try {
    $pdo->beginTransaction();

    // Load assets
    $cards = json_decode(file_get_contents('../assets/cards.json'), true);
    $nobles = json_decode(file_get_contents('../assets/nobles.json'), true);

    if (!$cards || !$nobles) {
        throw new Exception("Missing game assets (cards or nobles).");
    }

    // Shuffle assets
    shuffle($cards);
    shuffle($nobles);

    // Initial tokens based on player count
    // 2 players: 4 of each gem / 3 players: 5 / 4 players: 7
    // Gold is always 5
    $gem_count = (count($player_names) == 2) ? 4 : ((count($player_names) == 3) ? 5 : 7);
    $initial_tokens = [
        'white' => $gem_count,
        'blue' => $gem_count,
        'green' => $gem_count,
        'red' => $gem_count,
        'black' => $gem_count,
        'gold' => 5
    ];

    // Deal Nobles (Player count + 1)
    $board_nobles = array_slice($nobles, 0, count($player_names) + 1);

    // Sort cards into decks by level
    $decks = [1 => [], 2 => [], 3 => []];
    foreach ($cards as $card) {
        $decks[$card['level']][] = $card;
    }

    // Initial Board Cards (4 of each level)
    $board_cards = [
        'level_1' => array_splice($decks[1], 0, 4),
        'level_2' => array_splice($decks[2], 0, 4),
        'level_3' => array_splice($decks[3], 0, 4),
        'decks' => $decks // Store remaining in DB
    ];

    // Create Game Record
    $stmt = $pdo->prepare("INSERT INTO SpenderGame_games (status, tokens_available, board_cards, board_nobles) VALUES ('active', ?, ?, ?)");
    $stmt->execute([
        json_encode($initial_tokens),
        json_encode($board_cards),
        json_encode($board_nobles)
    ]);

    $game_id = $pdo->lastInsertId();

    // Create Players
    $turn_order = 1;
    foreach ($player_names as $name) {
        $stmt = $pdo->prepare("INSERT INTO SpenderGame_players (game_id, name, score, tokens_owned, cards_owned, cards_reserved, nobles_owned, turn_order) VALUES (?, ?, 0, ?, '[]', '[]', '[]', ?)");
        $empty_tokens = json_encode(['white' => 0, 'blue' => 0, 'green' => 0, 'red' => 0, 'black' => 0, 'gold' => 0]);
        $stmt->execute([
            $game_id,
            trim($name),
            $empty_tokens,
            $turn_order
        ]);

        // Set first player
        if ($turn_order == 1) {
            $first_player_id = $pdo->lastInsertId();
            $pdo->query("UPDATE SpenderGame_games SET turn_player_id = $first_player_id WHERE id = $game_id");
        }
        $turn_order++;
    }

    $pdo->commit();

    jsonResponse(true, ['game_id' => $game_id], 'Game initialized successfully.');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, [], $e->getMessage());
}
?>