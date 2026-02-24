<?php
// lobby.php
require 'config.php';

$room_code = isset($_GET['room']) ? strtoupper(trim($_GET['room'])) : '';
if (empty($room_code)) {
    header("Location: index.php");
    exit;
}

$player_id = isset($_SESSION['player_id']) ? $_SESSION['player_id'] : 0;
if ($player_id == 0) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Splendor — ห้องรอ <?php echo htmlspecialchars($room_code); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .lobby-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lobby-card {
            max-width: 520px;
            width: 100%;
        }

        .room-code-display {
            font-family: 'Cinzel', serif;
            font-size: 2.2rem;
            letter-spacing: 10px;
            color: var(--text-gold);
            text-shadow: 0 0 20px rgba(245, 158, 11, 0.3);
        }

        .empty-slot {
            border-style: dashed !important;
            opacity: 0.4;
        }

        .player-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            flex-shrink: 0;
        }

        .copy-btn {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .copy-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22C55E;
            display: inline-block;
            animation: pulseDot 1.5s infinite;
        }

        @keyframes pulseDot {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.5;
                transform: scale(1.3);
            }
        }
    </style>
</head>

<body>
    <div class="lobby-container">
        <div class="lobby-card glass-panel p-4 p-md-5">
            <!-- Room Code -->
            <div class="text-center mb-4">
                <p class="text-muted-custom small mb-2">
                    <i class="bi bi-door-open-fill me-1"></i> รหัสห้อง
                </p>
                <div class="room-code-display mb-2"><?php echo htmlspecialchars($room_code); ?></div>
                <button class="copy-btn" onclick="copyRoomCode()">
                    <i class="bi bi-clipboard me-1"></i> คัดลอกรหัส
                </button>
            </div>

            <div class="text-center mb-3">
                <p class="text-muted-custom small mb-0">
                    แชร์รหัสนี้ให้เพื่อนเพื่อเข้าร่วมเกม
                </p>
            </div>

            <!-- Players List -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-gold mb-0">
                        <i class="bi bi-people-fill me-1"></i> ผู้เล่นในห้อง
                    </h6>
                    <span class="badge" id="playerCountBadge"
                        style="background: rgba(245,158,11,0.15); color: var(--text-gold); padding: 5px 12px;">
                        0 / 4
                    </span>
                </div>
                <div id="players-list">
                    <div class="lobby-player-slot">
                        <div class="player-avatar" style="background: var(--gem-black);">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <span class="text-muted-custom">กำลังโหลด...</span>
                    </div>
                </div>
            </div>

            <!-- Host Controls -->
            <div id="host-controls" style="display: none;">
                <button class="btn-gem w-100 py-3 fs-5" onclick="startGame()" id="startBtn">
                    <i class="bi bi-play-fill me-1"></i> เริ่มเกม
                </button>
                <p class="text-center text-muted-custom small mt-2 mb-0">
                    ต้องมีอย่างน้อย 2 คนจึงจะเริ่มได้
                </p>
            </div>

            <div id="waiting-msg" style="display: none;">
                <div class="text-center p-3 rounded" style="background: rgba(255,255,255,0.03);">
                    <span class="pulse-dot me-2"></span>
                    <span class="text-muted-custom">รอเจ้าของห้องเริ่มเกม<span class="waiting-dots"></span></span>
                </div>
            </div>

            <!-- Leave -->
            <div class="text-center mt-4 pt-3" style="border-top: 1px solid var(--border-subtle);">
                <a href="index.php" class="text-muted-custom small" style="text-decoration: none;">
                    <i class="bi bi-arrow-left me-1"></i> ออกจากห้อง
                </a>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ROOM_CODE = "<?php echo $room_code; ?>";
        const MY_PLAYER_ID = <?php echo $player_id; ?>;

        const avatarColors = ['var(--gem-blue)', 'var(--gem-green)', 'var(--gem-red)', 'var(--gem-gold)'];
        const gemEmojis = ['💎', '🟢', '🔴', '🟡'];

        function showToast(msg, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast-msg toast-${type}`;
            const icons = { success: 'bi-check-circle', error: 'bi-x-circle', info: 'bi-info-circle', warning: 'bi-exclamation-triangle' };
            toast.innerHTML = `<i class="bi ${icons[type]}"></i> ${msg}`;
            document.getElementById('toastContainer').appendChild(toast);
            setTimeout(() => { toast.classList.add('toast-out'); setTimeout(() => toast.remove(), 300); }, 3000);
        }

        function copyRoomCode() {
            navigator.clipboard.writeText(ROOM_CODE).then(() => {
                showToast('คัดลอกรหัสห้องแล้ว!', 'success');
            });
        }

        let lastPlayerCount = 0;

        function pollLobby() {
            $.getJSON('api/lobby_state.php', { room_code: ROOM_CODE }, function (res) {
                if (res.success) {
                    if (res.data.game.status === 'active') {
                        window.location.href = 'game.php?id=' + res.data.game.id;
                        return;
                    }

                    const players = res.data.players;
                    let html = '';
                    let isHost = false;

                    players.forEach((p, i) => {
                        const isMe = p.id == MY_PLAYER_ID;
                        const isPlayerHost = p.is_host == 1;
                        if (isMe && isPlayerHost) isHost = true;

                        html += `<div class="lobby-player-slot anim-pop" style="animation-delay: ${i * 0.1}s">
                            <div class="player-avatar" style="background: ${avatarColors[i % 4]};">
                                ${p.name.charAt(0).toUpperCase()}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">${p.name}</div>
                                <div class="d-flex gap-1 mt-1">
                                    ${isPlayerHost ? '<span class="badge" style="background: rgba(239,68,68,0.2); color: #FCA5A5; font-size:0.7rem;">HOST</span>' : ''}
                                    ${isMe ? '<span class="badge" style="background: rgba(34,197,94,0.2); color: #86EFAC; font-size:0.7rem;">คุณ</span>' : ''}
                                </div>
                            </div>
                            <span class="pulse-dot"></span>
                        </div>`;
                    });

                    // Empty slots
                    const maxPlayers = 4;
                    for (let i = players.length; i < maxPlayers; i++) {
                        html += `<div class="lobby-player-slot empty-slot">
                            <div class="player-avatar" style="background: rgba(255,255,255,0.05);">
                                <i class="bi bi-person-dash" style="color: var(--text-secondary);"></i>
                            </div>
                            <span class="text-muted-custom small">รอผู้เล่น...</span>
                        </div>`;
                    }

                    $('#players-list').html(html);
                    $('#playerCountBadge').text(players.length + ' / ' + maxPlayers);

                    if (players.length !== lastPlayerCount && lastPlayerCount > 0) {
                        showToast(`มีผู้เล่น ${players.length} คนในห้อง`, 'info');
                    }
                    lastPlayerCount = players.length;

                    if (isHost) {
                        $('#host-controls').show();
                        $('#waiting-msg').hide();
                        if (players.length < 2) {
                            $('#startBtn').prop('disabled', true).css('opacity', '0.5');
                        } else {
                            $('#startBtn').prop('disabled', false).css('opacity', '1');
                        }
                    } else {
                        $('#host-controls').hide();
                        $('#waiting-msg').show();
                    }
                } else {
                    showToast(res.message, 'error');
                    setTimeout(() => { window.location.href = 'index.php'; }, 2000);
                }
            });
        }

        function startGame() {
            const btn = $('#startBtn');
            btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> กำลังเริ่ม...');

            $.post('api/start_room.php', { room_code: ROOM_CODE }, function (res) {
                if (res.success) {
                    showToast('เริ่มเกม!', 'success');
                    window.location.href = 'game.php?id=' + res.data.game_id;
                } else {
                    showToast(res.message, 'error');
                    btn.prop('disabled', false).html('<i class="bi bi-play-fill me-1"></i> เริ่มเกม');
                }
            }, 'json');
        }

        $(document).ready(function () {
            pollLobby();
            setInterval(pollLobby, 1500);
        });
    </script>
</body>

</html>