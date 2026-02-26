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
                height: 100dvh;
                min-height: 100dvh;
                padding: 4px;
                gap: 4px;
                overflow: hidden;
            }

            html,
            body {
                overflow: hidden !important;
                height: 100dvh;
                width: 100%;
                position: fixed;
            }

            .board-area {
                padding: 4px !important;
                justify-content: space-evenly;
            }

            .sidebar-area {
                padding: 6px !important;
            }

            .cards-grid {
                gap: 4px;
            }

            .card-row {
                gap: 4px;
            }

            .card-spl {
                width: max(55px, 10vh) !important;
                height: max(78px, 14vh) !important;
                position: relative !important;
            }

            .card-points {
                font-size: 0.95rem !important;
            }

            .deck-indicator {
                width: max(45px, 8vh) !important;
                height: max(60px, 11vh) !important;
                margin-right: 4px !important;
            }

            .card-header-spl {
                padding: 3px 5px !important;
            }

            .card-gem-icon {
                width: 16px !important;
                height: 16px !important;
                font-size: 0.6rem !important;
            }

            .card-body-spl {
                font-size: 1.2rem !important;
                min-height: 0 !important;
                flex: 1 1 auto;
                opacity: 0.1 !important;
                margin-bottom: 12px !important; /* give space for absolute bottom */
            }

            .card-cost-container {
                position: absolute !important;
                bottom: 2px !important;
                left: 1px !important;
                right: 1px !important;
                padding: 1px !important;
                gap: 2px !important;
                display: flex !important;
                flex-wrap: wrap !important;
                justify-content: flex-start !important;
            }

            .card-cost {
                gap: 1px !important;
            }

            .card-cost-dot {
                width: 12px !important;
                height: 12px !important;
                font-size: 0.55rem !important;
                margin: 0 !important;
            }

            .noble-spl {
                width: max(45px, 8.5vh) !important;
                height: max(45px, 8.5vh) !important;
                margin: 2px !important;
            }

            .noble-points {
                font-size: 0.8rem !important;
                padding: 2px 4px !important;
            }

            .noble-req .card-cost-dot {
                width: 10px !important;
                height: 10px !important;
                font-size: 0.45rem !important;
            }

            .token {
                width: max(28px, 5vh) !important;
                height: max(28px, 5vh) !important;
                font-size: 0.75rem !important;
                margin: 2px !important;
                border-width: 1px !important;
            }

            .token .badge {
                font-size: 0.6rem !important;
                padding: 1px 4px !important;
                bottom: -4px !important;
                right: -4px !important;
            }

            .turn-indicator {
                padding: 4px 8px !important;
                margin-bottom: 2px !important;
                font-size: 0.8rem !important;
            }

            .nobles-row {
                padding: 2px 0 !important;
                gap: 4px !important;
            }

            .tokens-bank {
                padding: 4px !important;
                gap: 4px !important;
            }

            .players-scroll {
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                overflow-x: auto;
                overflow-y: hidden;
                gap: 6px;
                padding-right: 0;
                padding-bottom: 4px;
                align-items: stretch;
            }

            .player-tag {
                flex: 0 0 auto;
                min-width: 130px;
                margin-bottom: 0 !important;
                padding: 6px 8px !important;
            }

            .player-name {
                font-size: 0.8rem !important;
            }

            .player-score {
                font-size: 0.95rem !important;
            }

            .player-gem-mini {
                padding: 1px 3px !important;
                font-size: 0.6rem !important;
                margin: 1px !important;
            }

            .player-gem-dot {
                width: 8px !important;
                height: 8px !important;
            }

            .sidebar-area>.mt-2.pt-2 {
                margin-top: 4px !important;
                padding-top: 4px !important;
                display: flex;
                gap: 6px;
                align-items: center;
            }

            .sidebar-area>.mt-2.pt-2 .d-flex.gap-2.mb-2 {
                margin-bottom: 0 !important;
                flex: 1;
                gap: 4px !important;
            }

            .sidebar-area>.mt-2.pt-2>button.btn-ruby {
                width: auto !important;
                flex: 0 0 auto;
                padding: 4px 10px !important;
                font-size: 0.75rem !important;
            }

            .btn-crystal {
                padding: 4px 6px !important;
                font-size: 0.75rem !important;
            }

            .reserved-mini {
                width: 45px !important;
                height: 60px !important;
                position: relative !important;
            }
            
            .rm-header {
                padding: 2px 3px !important;
                font-size: 0.6rem !important;
            }

            .rm-costs {
                position: absolute !important;
                bottom: 2px !important;
                left: 1px !important;
                right: 1px !important;
                padding: 1px !important;
                gap: 1px !important;
                display: flex !important;
                flex-wrap: wrap !important;
                justify-content: flex-start !important;
            }

            .rm-cost-dot {
                width: 10px !important;
                height: 10px !important;
                font-size: 0.45rem !important;
                margin: 0 !important;
            }

            .action-panel {
                padding: 6px 10px !important;
                margin-top: 2px !important;
            }

            .action-panel button {
                padding: 4px 10px !important;
                font-size: 0.8rem !important;
            }

            .glass-panel-sm {
                margin-top: 2px !important;
            }

            #scoreBadge {
                font-size: 0.6rem !important;
                padding: 2px 4px !important;
            }

            .sidebar-area h5 {
                font-size: 0.8rem !important;
                margin-bottom: 0 !important;
            }

            .sidebar-area .mb-3 {
                margin-bottom: 4px !important;
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
                <div class="d-flex gap-2 mb-2">
                    <button class="btn-crystal flex-fill py-1" onclick="toggleBGM(this)"
                        style="font-size:0.8rem; padding: 5px;">
                        <i class="bi bi-music-note-beamed me-1"></i> <span>เพลง: เปิด</span>
                    </button>
                    <button class="btn-crystal flex-fill py-1" onclick="toggleSFX(this)"
                        style="font-size:0.8rem; padding: 5px;">
                        <i class="bi bi-volume-up me-1"></i> <span>เสียง: เปิด</span>
                    </button>
                </div>
                <button class="btn-ruby w-100 py-2" onclick="leaveGame()">
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
    <script src="js/sounds.js"></script>
    <script src="js/game.js"></script>
    <script>
        function toggleBGM(btn) {
            const on = SoundEngine.toggleMusic();
            btn.querySelector('span').textContent = 'เพลง: ' + (on ? 'เปิด' : 'ปิด');
            btn.querySelector('i').className = 'bi ' + (on ? 'bi-music-note-beamed' : 'bi-music-note') + ' me-1';
        }
        function toggleSFX(btn) {
            const on = SoundEngine.toggleSFX();
            btn.querySelector('span').textContent = 'เสียง: ' + (on ? 'เปิด' : 'ปิด');
            btn.querySelector('i').className = 'bi ' + (on ? 'bi-volume-up' : 'bi-volume-mute') + ' me-1';
        }

        // Visitor tracking
        $.post('api/track_visit.php', { page: 'game' });
    </script>
</body>

</html>