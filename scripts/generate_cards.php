<?php
// scripts/generate_cards.php
$colors = ['white', 'blue', 'green', 'red', 'black'];

$cards = [];
$id = 1;

// Helper to generate a cost array focusing on 2-4 colors
function generateCost($total_cost, $colors)
{
    $cost = [];
    $num_colors = rand(2, 4);
    $selected_colors = (array) array_rand(array_flip($colors), $num_colors);
    $remaining = $total_cost;

    foreach ($selected_colors as $i => $c) {
        if ($i == count($selected_colors) - 1) {
            $cost[$c] = $remaining;
        } else {
            $val = rand(1, $remaining - (count($selected_colors) - 1 - $i));
            $cost[$c] = $val;
            $remaining -= $val;
        }
    }
    return $cost;
}

// Level 1: 40 cards. Cost 3-5, Points: 0-1
for ($i = 0; $i < 40; $i++) {
    $color = $colors[array_rand($colors)];
    $points = rand(0, 5) == 5 ? 1 : 0; // chance for 1 point
    $total_cost = $points === 1 ? 4 : rand(3, 4);

    $cards[] = [
        'id' => $id++,
        'level' => 1,
        'gem' => $color,
        'points' => $points,
        'cost' => generateCost($total_cost, $colors)
    ];
}

// Level 2: 30 cards. Cost 5-8, Points: 1-3
for ($i = 0; $i < 30; $i++) {
    $color = $colors[array_rand($colors)];
    $points = rand(1, 3);
    $total_cost = $points + 4; // 1->5, 2->6, 3->7

    $cards[] = [
        'id' => $id++,
        'level' => 2,
        'gem' => $color,
        'points' => $points,
        'cost' => generateCost($total_cost, $colors)
    ];
}

// Level 3: 20 cards. Cost 7-14, Points: 3-5
for ($i = 0; $i < 20; $i++) {
    $color = $colors[array_rand($colors)];
    $points = rand(3, 5);
    $total_cost = $points + 5; // 3->8, 4->9, 5->10, up to 14

    $cards[] = [
        'id' => $id++,
        'level' => 3,
        'gem' => $color,
        'points' => $points,
        'cost' => generateCost($total_cost, $colors)
    ];
}

file_put_contents(__DIR__ . '/../assets/cards.json', json_encode($cards, JSON_PRETTY_PRINT));
echo "Generated " . count($cards) . " cards successfully.";
?>