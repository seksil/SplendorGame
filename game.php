<?php
// game.php
require 'config.php';
$game_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($game_id <= 0) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Splendor — เกม #<?php echo $game_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        html,
        body {
            height: 100%;
            overflow: hidden;
        }

        .game-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 12px;
            height: 100vh;
            padding: 12px;
        }

        .board-area {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-area {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .cards-grid {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            justify-content: center;
        }

        .card-row {
            display: flex;
            align-items: center;
            gap: 6px;
            justify-content: center;
        }

        .tokens-bank {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
            padding: 12px;
            flex-wrap: wrap;
        }

        .token-slot {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
        }

        .nobles-row {
            display: flex;
            justify-content: center;
            gap: 6px;
            flex-wrap: wrap;
            padding: 8px 0;
        }

        .players-scroll {
            flex: 1;
            overflow-y: auto;
            padding-right: 4px;
        }

        /* Reserved card mini */
        .reserved-mini {
            width: 65px;
            height: 90px;
            border-radius: 6px;
            background: linear-gradient(160deg, rgba(255, 255, 255, 0.06), rgba(0, 0, 0, 0.2));
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: inline-flex;
            flex-direction: column;
            cursor: pointer;
            transition: var(--transition);
            overflow: hidden;
            font-size: 0.7rem;
        }

        .reserved-mini:hover {
            transform: scale(1.05);
            border-color: rgba(245, 158, 11, 0.4);
        }

        .reserved-mini .rm-header {
            display: flex;
            justify-content: space-between;
            padding: 3px 5px;
            background: rgba(0, 0, 0, 0.4);
            font-size: 0.65rem;
        }

        .reserved-mini .rm-costs {
            padding: 3px 5px;
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .reserved-mini .rm-cost-dot {
            width: 13px;
            height: 13px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.55rem;
            font-weight: 800;
        }

        @media (max-width: 992px) {
            .game-layout {
                grid-template-columns: 1fr;
                grid-template-rows: 1fr auto;
                height: auto;
                min-height: 100vh;
            }

            html,
            body {
                overflow: auto;
            }
        }
    </style>
</head>

<body>
    <div class="game-layout">
        <!-- Main Board -->
        <div class="board-area glass-panel p-3">
            <!-- Turn Indicator -->
            <div class="turn-indicator" id="turnIndicator">
                <i class="bi bi-hourglass-split me-1"></i> กำลังโหลด...
            </div>

            <!-- Nobles -->
            <div class="nobles-row" id="nobles-area"></div>

            <!-- Cards Grid -->
            <div class="cards-grid" id="cards-area">
                <div class="card-row" id="row-level-3"></div>
                <div class="card-row" id="row-level-2"></div>
                <div class="card-row" id="row-level-1"></div>
            </div>

            <!-- Tokens Bank -->
            <div class="glass-panel-sm p-2 mt-2">
                <div class="tokens-bank" id="tokens-bank"></div>
            </div>

            <!-- Action Panel -->
            <div id="action-panel" class="action-panel mt-2" style="display: none;">
                <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
                    <span id="action-info" class="text-muted-custom small"></span>
                    <div id="action-buttons" class="d-flex gap-2"></div>
                    <button class="btn-ruby btn-sm" onclick="cancelAction()">
                        <i class="bi bi-x-lg me-1"></i> ยกเลิก
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar: Players -->
        <div class="sidebar-area glass-panel p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="title-font-sm mb-0" style="font-size: 0.9rem;">
                    <i class="bi bi-people-fill me-1"></i> ผู้เล่น
                </h5>
                <span class="badge" id="scoreBadge"
                    style="background: rgba(245,158,11,0.15); color: var(--text-gold); font-size: 0.7rem;">
                    เป้าหมาย: 15 คะแนน
                </span>
            </div>

            <div class="players-scroll" id="players-list"></div>

            <div class="mt-2 pt-2" style="border-top: 1px solid var(--border-subtle);">
                <button class="btn-ruby w-100 py-2"
                    onclick="if(confirm('ออกจากเกม?')) window.location.href='index.php'">
                    <i class="bi bi-box-arrow-left me-1"></i> ออกจากเกม
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const GAME_ID = <?php echo $game_id; ?>;
        const MY_PLAYER_ID = <?php echo isset($_SESSION['player_id']) ? $_SESSION['player_id'] : 0; ?>;
    </script>
    <script src="js/game.js"></script>
</body>

</html>