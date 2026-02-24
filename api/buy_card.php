<?php
// api/buy_card.php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'POST request required');
}

$game_id = intval($_POST['game_id']);
$player_id = intval($_POST['player_id']);
$card_id = intval($_POST['card_id']);
$is_reserved = isset($_POST['is_reserved']) && $_POST['is_reserved'] == 'true';

try {
    $pdo->beginTransaction();

    // Lock game row
    $stmt = $pdo->prepare("SELECT * FROM SpenderGame_games WHERE id = ? FOR UPDATE");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    // Lock player row
    $stmt = $pdo->prepare("SELECT * FROM SpenderGame_players WHERE id = ? FOR UPDATE");
    $stmt->execute([$player_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game || $game['status'] !== 'active')
        throw new Exception("Game is not active.");
    if ($game['turn_player_id'] != $player_id)
        throw new Exception("It is not your turn!");

    $board_tokens = json_decode($game['tokens_available'], true);
    $board_cards = json_decode($game['board_cards'], true);

    $player_tokens = json_decode($player['tokens_owned'], true);
    $player_cards = json_decode($player['cards_owned'], true);
    $player_reserved = json_decode($player['cards_reserved'], true);

    $cardToBuy = null;
    $cardLevelForReplacement = null;
    $cardIndexOnBoard = null;

    if ($is_reserved) {
        foreach ($player_reserved as $i => $c) {
            if ($c['id'] == $card_id) {
                $cardToBuy = $c;
                $cardIndexOnBoard = $i;
                break;
            }
        }
        if (!$cardToBuy)
            throw new Exception("Card not found in your reserved cards.");
    } else {
        foreach (['1', '2', '3'] as $level) {
            foreach ($board_cards['level_' . $level] as $i => $c) {
                if ($c['id'] == $card_id) {
                    $cardToBuy = $c;
                    $cardLevelForReplacement = $level;
                    $cardIndexOnBoard = $i;
                    break 2;
                }
            }
        }
        if (!$cardToBuy)
            throw new Exception("Card not found on the board.");
    }

    // Check affordance
    $cost = $cardToBuy['cost'];
    $gold_needed = 0;

    foreach ($cost as $color => $amount) {
        $discount = isset($player_cards[$color]) ? $player_cards[$color] : 0;
        $net_cost = max(0, $amount - $discount);

        $player_has = isset($player_tokens[$color]) ? $player_tokens[$color] : 0;
        if ($player_has < $net_cost) {
            $gold_needed += ($net_cost - $player_has);
        }
    }

    if ($player_tokens['gold'] < $gold_needed) {
        throw new Exception("You cannot afford this card.");
    }

    // Deduct tokens
    foreach ($cost as $color => $amount) {
        $discount = isset($player_cards[$color]) ? $player_cards[$color] : 0;
        $net_cost = max(0, $amount - $discount);

        $player_has = isset($player_tokens[$color]) ? $player_tokens[$color] : 0;
        if ($player_has < $net_cost) {
            $board_tokens[$color] += $player_has;
            $player_tokens[$color] = 0;
            // The rest is paid in gold, handled below
        } else {
            $board_tokens[$color] += $net_cost;
            $player_tokens[$color] -= $net_cost;
        }
    }

    if ($gold_needed > 0) {
        $player_tokens['gold'] -= $gold_needed;
        $board_tokens['gold'] += $gold_needed;
    }

    // Add card to player
    $gem_color = $cardToBuy['gem'];
    if (!isset($player_cards[$gem_color]))
        $player_cards[$gem_color] = 0;
    $player_cards[$gem_color] += 1;

    $new_score = $player['score'] + $cardToBuy['points'];

    // Remove card from board or reserved
    if ($is_reserved) {
        array_splice($player_reserved, $cardIndexOnBoard, 1);
    } else {
        array_splice($board_cards['level_' . $cardLevelForReplacement], $cardIndexOnBoard, 1);
        // Deal new card
        if (count($board_cards['decks'][$cardLevelForReplacement]) > 0) {
            $new_card = array_shift($board_cards['decks'][$cardLevelForReplacement]);
            $board_cards['level_' . $cardLevelForReplacement][] = $new_card;
        }
    }

    // Check Nobles
    $board_nobles = json_decode($game['board_nobles'], true);
    $player_nobles = json_decode($player['nobles_owned'], true);
    $noble_acquired = false;

    foreach ($board_nobles as $i => $noble) {
        $can_afford_noble = true;
        foreach ($noble['requirements'] as $req_col => $req_amt) {
            $has_cards = isset($player_cards[$req_col]) ? $player_cards[$req_col] : 0;
            if ($has_cards < $req_amt) {
                $can_afford_noble = false;
                break;
            }
        }
        if ($can_afford_noble) {
            $player_nobles[] = $noble;
            $new_score += $noble['points'];
            array_splice($board_nobles, $i, 1);
            $noble_acquired = true;
            break; // Only 1 noble per turn
        }
    }

    // Next player turn
    $stmt = $pdo->prepare("SELECT id FROM SpenderGame_players WHERE game_id = ? ORDER BY turn_order ASC");
    $stmt->execute([$game_id]);
    $all_players = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $currentIndex = array_search($player_id, $all_players);
    $next_player_id = $all_players[($currentIndex + 1) % count($all_players)];

    // Save
    $stmt = $pdo->prepare("UPDATE SpenderGame_games SET tokens_available = ?, board_cards = ?, board_nobles = ?, turn_player_id = ? WHERE id = ?");
    $stmt->execute([json_encode($board_tokens), json_encode($board_cards), json_encode($board_nobles), $next_player_id, $game_id]);

    $stmt = $pdo->prepare("UPDATE SpenderGame_players SET score = ?, tokens_owned = ?, cards_owned = ?, cards_reserved = ?, nobles_owned = ? WHERE id = ?");
    $stmt->execute([$new_score, json_encode($player_tokens), json_encode($player_cards), json_encode($player_reserved), json_encode($player_nobles), $player_id]);

    $pdo->commit();
    jsonResponse(true, ['noble_acquired' => $noble_acquired], 'Card bought successfully.');

} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    jsonResponse(false, [], $e->getMessage());
}
?>