<?php
// api/take_tokens.php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'POST request required');
}

$game_id = intval($_POST['game_id']);
$player_id = intval($_POST['player_id']);
$tokens_requested = isset($_POST['tokens']) ? $_POST['tokens'] : []; // Array of colors e.g. ['red', 'blue', 'green'] or ['red', 'red']

try {
    $pdo->beginTransaction();

    // Lock game row
    $stmt = $pdo->prepare("SELECT * FROM SpenderGame_games WHERE id = ? FOR UPDATE");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game || $game['status'] !== 'active') {
        throw new Exception("Game is not active.");
    }

    if ($game['turn_player_id'] != $player_id) {
        throw new Exception("It is not your turn!");
    }

    // Lock player row
    $stmt = $pdo->prepare("SELECT * FROM SpenderGame_players WHERE id = ? FOR UPDATE");
    $stmt->execute([$player_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    $board_tokens = json_decode($game['tokens_available'], true);
    $player_tokens = json_decode($player['tokens_owned'], true);

    // Validate tokens requested
    if (count($tokens_requested) == 2) {
        if ($tokens_requested[0] !== $tokens_requested[1]) {
            throw new Exception("If taking 2 tokens, they must be the same color.");
        }
        $color = $tokens_requested[0];
        if ($board_tokens[$color] < 4) {
            throw new Exception("Can only take 2 of same color if there are 4 or more available.");
        }
        if ($color == 'gold') {
            throw new Exception("Cannot directly take gold tokens.");
        }
    } else if (count($tokens_requested) == 3) {
        $unique = array_unique($tokens_requested);
        if (count($unique) != 3) {
            throw new Exception("If taking 3 tokens, they must all be different colors.");
        }
        if (in_array('gold', $tokens_requested)) {
            throw new Exception("Cannot directly take gold tokens.");
        }
    } else {
        throw new Exception("Must take exactly 2 or 3 tokens.");
    }

    // Process taking tokens
    $player_total = array_sum($player_tokens);
    $taking_total = count($tokens_requested);

    if ($player_total + $taking_total > 10) {
        // Technically rules say they must discard, but for simplicity we block it.
        throw new Exception("You cannot have more than 10 tokens. Please take fewer or none.");
    }

    foreach ($tokens_requested as $color) {
        if ($board_tokens[$color] <= 0) {
            throw new Exception("Not enough $color tokens available.");
        }
        $board_tokens[$color] -= 1;
        $player_tokens[$color] += 1;
    }

    // Update next turn
    $stmt = $pdo->prepare("SELECT id FROM SpenderGame_players WHERE game_id = ? ORDER BY turn_order ASC");
    $stmt->execute([$game_id]);
    $all_players = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $currentIndex = array_search($player_id, $all_players);
    $next_player_id = $all_players[($currentIndex + 1) % count($all_players)];

    // Save state
    $stmt = $pdo->prepare("UPDATE SpenderGame_games SET tokens_available = ?, turn_player_id = ? WHERE id = ?");
    $stmt->execute([json_encode($board_tokens), $next_player_id, $game_id]);

    $stmt = $pdo->prepare("UPDATE SpenderGame_players SET tokens_owned = ? WHERE id = ?");
    $stmt->execute([json_encode($player_tokens), $player_id]);

    $pdo->commit();
    jsonResponse(true, [], 'Tokens taken successfully.');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(false, [], $e->getMessage());
}
?>