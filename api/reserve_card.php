<?php
// api/reserve_card.php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'POST request required');
}

$game_id = intval($_POST['game_id']);
$player_id = intval($_POST['player_id']);
$card_id = intval($_POST['card_id']);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM SpenderGame_games WHERE id = ? FOR UPDATE");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM SpenderGame_players WHERE id = ? FOR UPDATE");
    $stmt->execute([$player_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game || $game['status'] !== 'active')
        throw new Exception("Game is not active.");
    if ($game['turn_player_id'] != $player_id)
        throw new Exception("It is not your turn!");

    $player_reserved = json_decode($player['cards_reserved'], true);
    if (count($player_reserved) >= 3) {
        throw new Exception("You can only reserve up to 3 cards.");
    }

    $board_cards = json_decode($game['board_cards'], true);
    $cardToReserve = null;
    $cardLevelForReplacement = null;
    $cardIndexOnBoard = null;

    foreach (['1', '2', '3'] as $level) {
        foreach ($board_cards['level_' . $level] as $i => $c) {
            if ($c['id'] == $card_id) {
                $cardToReserve = $c;
                $cardLevelForReplacement = $level;
                $cardIndexOnBoard = $i;
                break 2;
            }
        }
    }

    if (!$cardToReserve)
        throw new Exception("Card not found on the board.");

    // Remove from board
    array_splice($board_cards['level_' . $cardLevelForReplacement], $cardIndexOnBoard, 1);

    // Replace if deck not empty
    if (count($board_cards['decks'][$cardLevelForReplacement]) > 0) {
        $new_card = array_shift($board_cards['decks'][$cardLevelForReplacement]);
        $board_cards['level_' . $cardLevelForReplacement][] = $new_card;
    }

    // Add to reserved
    $player_reserved[] = $cardToReserve;

    // Give gold token if available
    $board_tokens = json_decode($game['tokens_available'], true);
    $player_tokens = json_decode($player['tokens_owned'], true);

    $got_gold = false;
    if ($board_tokens['gold'] > 0 && array_sum($player_tokens) < 10) {
        $board_tokens['gold'] -= 1;
        $player_tokens['gold'] += 1;
        $got_gold = true;
    }

    // Next player
    $stmt = $pdo->prepare("SELECT id FROM SpenderGame_players WHERE game_id = ? ORDER BY turn_order ASC");
    $stmt->execute([$game_id]);
    $all_players = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $currentIndex = array_search($player_id, $all_players);
    $next_player_id = $all_players[($currentIndex + 1) % count($all_players)];

    // Save
    $stmt = $pdo->prepare("UPDATE SpenderGame_games SET tokens_available = ?, board_cards = ?, turn_player_id = ? WHERE id = ?");
    $stmt->execute([json_encode($board_tokens), json_encode($board_cards), $next_player_id, $game_id]);

    $stmt = $pdo->prepare("UPDATE SpenderGame_players SET tokens_owned = ?, cards_reserved = ? WHERE id = ?");
    $stmt->execute([json_encode($player_tokens), json_encode($player_reserved), $player_id]);

    $pdo->commit();
    jsonResponse(true, ['got_gold' => $got_gold], 'Card reserved successfully.');

} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    jsonResponse(false, [], $e->getMessage());
}
?>