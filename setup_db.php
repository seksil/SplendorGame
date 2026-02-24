<?php
// setup_db.php
require 'config.php';

try {
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    // Drop existing tables to recreate (Warn users before running this normally, but fine for development)
    $pdo->exec("DROP TABLE IF EXISTS SpenderGame_players");
    $pdo->exec("DROP TABLE IF EXISTS SpenderGame_games");

    // Create games table
    $pdo->exec("CREATE TABLE IF NOT EXISTS SpenderGame_games (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_code VARCHAR(10) UNIQUE,
        status ENUM('waiting', 'active', 'finished') DEFAULT 'waiting',
        turn_player_id INT DEFAULT NULL,
        tokens_available JSON,
        board_cards JSON,
        board_nobles JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create players table
    $pdo->exec("CREATE TABLE IF NOT EXISTS SpenderGame_players (
        id INT AUTO_INCREMENT PRIMARY KEY,
        game_id INT NOT NULL,
        name VARCHAR(50) NOT NULL,
        session_id VARCHAR(100),
        is_host BOOLEAN DEFAULT FALSE,
        score INT DEFAULT 0,
        tokens_owned JSON,
        cards_owned JSON,
        cards_reserved JSON,
        nobles_owned JSON,
        turn_order INT,
        FOREIGN KEY (game_id) REFERENCES SpenderGame_games(id) ON DELETE CASCADE
    )");

    echo "<h3>Database and tables created successfully!</h3>";
    echo "<p><a href='index.php'>Go to Game Lobby</a></p>";

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
?>