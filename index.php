<?php
// index.php
require 'config.php';
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Splendor — เกมพ่อค้าอัญมณี</title>
    <meta name="description" content="เล่นเกม Splendor ออนไลน์ เกมพ่อค้าอัญมณีที่สนุกและท้าทาย รองรับ 2-4 ผู้เล่น">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.08), transparent 70%);
            top: -100px;
            left: -100px;
            border-radius: 50%;
            animation: floatOrb 8s ease-in-out infinite;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.06), transparent 70%);
            bottom: -50px;
            right: -50px;
            border-radius: 50%;
            animation: floatOrb 10s ease-in-out infinite reverse;
        }

        @keyframes floatOrb {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(30px, -20px) scale(1.1);
            }
        }

        .main-card {
            max-width: 500px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .gem-deco {
            position: absolute;
            font-size: 1.5rem;
            opacity: 0.15;
            animation: floatGem 6s ease-in-out infinite;
        }

        @keyframes floatGem {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-10px) rotate(10deg);
            }
        }

        .subtitle {
            font-size: 0.95rem;
            color: var(--text-secondary);
            letter-spacing: 2px;
        }

        .tab-selector {
            display: flex;
            gap: 4px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: var(--radius-md);
            padding: 4px;
        }

        .tab-selector .tab-btn {
            flex: 1;
            padding: 10px 16px;
            border: none;
            border-radius: var(--radius-sm);
            background: transparent;
            color: var(--text-secondary);
            font-weight: 500;
            transition: var(--transition);
            cursor: pointer;
            font-family: 'Prompt', sans-serif;
            font-size: 0.9rem;
        }

        .tab-selector .tab-btn.active {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.1));
            color: var(--text-gold);
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.15);
        }

        .tab-selector .tab-btn:hover:not(.active) {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.05);
        }

        .form-section {
            display: none;
            animation: fadeInUp 0.3s ease-out;
        }

        .form-section.active {
            display: block;
        }

        .room-code-input {
            font-family: 'Cinzel', serif;
            letter-spacing: 6px;
            text-transform: uppercase;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-gold) !important;
        }

        .how-to-play-btn {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            transition: var(--transition);
            cursor: pointer;
        }

        .how-to-play-btn:hover {
            border-color: rgba(245, 158, 11, 0.3);
            color: var(--text-gold);
            background: rgba(245, 158, 11, 0.05);
        }

        .player-info-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: var(--text-secondary);
        }
    </style>
</head>

<body>
    <div class="hero-section">
        <!-- Decorative gems -->
        <span class="gem-deco" style="top:15%;left:10%">💎</span>
        <span class="gem-deco" style="top:25%;right:15%;animation-delay:1s">🔴</span>
        <span class="gem-deco" style="bottom:20%;left:15%;animation-delay:2s">🟢</span>
        <span class="gem-deco" style="bottom:30%;right:10%;animation-delay:3s">⚪</span>
        <span class="gem-deco" style="top:60%;left:5%;animation-delay:4s">🔵</span>

        <div class="main-card glass-panel p-4 p-md-5">
            <!-- Title -->
            <div class="text-center mb-4">
                <h1 class="title-font display-5 mb-2">Splendor</h1>
                <p class="subtitle mb-0">เกมพ่อค้าอัญมณี</p>
            </div>

            <!-- Tab Selector -->
            <div class="tab-selector mb-4">
                <button class="tab-btn active" onclick="switchTab('join')">
                    <i class="bi bi-door-open me-1"></i> เข้าร่วม
                </button>
                <button class="tab-btn" onclick="switchTab('create')">
                    <i class="bi bi-plus-circle me-1"></i> สร้างห้อง
                </button>
            </div>

            <!-- Join Room Form -->
            <div class="form-section active" id="tab-join">
                <form id="joinRoomForm">
                    <div class="mb-3">
                        <label class="form-label small text-muted-custom">ชื่อผู้เล่น</label>
                        <input type="text" class="input-gem" name="player_name" placeholder="กรอกชื่อของคุณ" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small text-muted-custom">รหัสห้อง</label>
                        <input type="text" class="input-gem room-code-input" name="room_code" placeholder="XXXXX"
                            required maxlength="5">
                    </div>
                    <button type="submit" class="btn-gem w-100 py-3 fs-5">
                        <i class="bi bi-play-fill me-1"></i> เข้าร่วมเกม
                    </button>
                </form>
            </div>

            <!-- Create Room Form -->
            <div class="form-section" id="tab-create">
                <form id="createRoomForm">
                    <div class="mb-3">
                        <label class="form-label small text-muted-custom">ชื่อผู้เล่น</label>
                        <input type="text" class="input-gem" name="player_name" placeholder="กรอกชื่อของคุณ" required>
                    </div>

                    <!-- Player Count Selector -->
                    <div class="mb-4">
                        <label class="form-label small text-muted-custom">จำนวนผู้เล่น</label>
                        <div class="d-flex justify-content-center gap-3">
                            <button type="button" class="player-count-btn" onclick="selectPlayerCount(2, this)">
                                2 <span>คน</span>
                            </button>
                            <button type="button" class="player-count-btn active" onclick="selectPlayerCount(3, this)">
                                3 <span>คน</span>
                            </button>
                            <button type="button" class="player-count-btn" onclick="selectPlayerCount(4, this)">
                                4 <span>คน</span>
                            </button>
                        </div>
                        <input type="hidden" name="max_players" id="maxPlayersInput" value="3">
                        <div class="text-center mt-2">
                            <span class="player-info-chip" id="tokenInfoChip">
                                <i class="bi bi-gem"></i> Token ละ 5 เหรียญ · Noble 4 ใบ
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn-crystal w-100 py-3 fs-5">
                        <i class="bi bi-plus-lg me-1"></i> สร้างห้องเกม
                    </button>
                </form>
            </div>

            <!-- Bottom Links -->
            <div class="text-center mt-4 pt-3" style="border-top: 1px solid var(--border-subtle);">
                <button class="how-to-play-btn" data-bs-toggle="modal" data-bs-target="#howToPlayModal">
                    <i class="bi bi-book me-1"></i> วิธีเล่นเกม
                </button>
            </div>
        </div>
    </div>

    <!-- How to Play Modal -->
    <div class="modal fade" id="howToPlayModal" tabindex="-1" aria-labelledby="howToPlayLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content modal-dark">
                <div class="modal-header">
                    <h4 class="modal-title title-font-sm" id="howToPlayLabel">
                        <i class="bi bi-book-fill text-gold me-2"></i> วิธีเล่น Splendor
                    </h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Goal -->
                    <div class="rules-section">
                        <div class="d-flex align-items-start">
                            <div class="rules-icon bg-gem-gold">🏆</div>
                            <div>
                                <div class="rules-title">เป้าหมาย</div>
                                <p class="mb-0" style="color: var(--text-secondary);">
                                    เป็นผู้เล่นคนแรกที่สะสม <strong class="text-gold">15 คะแนน</strong>
                                    จากการซื้อการ์ดพัฒนาและดึงดูดขุนนาง (Nobles) ให้มาเยี่ยม
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Turn Actions -->
                    <div class="rules-section">
                        <div class="d-flex align-items-start">
                            <div class="rules-icon bg-gem-blue">🎯</div>
                            <div>
                                <div class="rules-title">การเล่นแต่ละตา</div>
                                <p class="mb-2" style="color: var(--text-secondary);">
                                    ในแต่ละตา คุณเลือกทำ <strong>1 ใน 3 สิ่ง</strong> ต่อไปนี้:
                                </p>
                            </div>
                        </div>

                        <!-- Action 1 -->
                        <div class="ms-5 mt-3 p-3 rounded" style="background: rgba(255,255,255,0.03);">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge me-2"
                                    style="background: var(--gem-blue); color: #fff; padding: 5px 10px;">1</span>
                                <strong>หยิบ Token (อัญมณี)</strong>
                            </div>
                            <ul class="mb-0" style="color: var(--text-secondary); padding-left: 20px;">
                                <li>หยิบ <strong class="text-light">3 เหรียญ</strong> สีต่างกัน</li>
                                <li>หรือหยิบ <strong class="text-light">2 เหรียญ</strong> สีเดียวกัน
                                    (ต้องมีเหรียญสีนั้นเหลือ ≥ 4 เหรียญ)</li>
                                <li>ถือ Token ได้สูงสุด <strong class="text-light">10 เหรียญ</strong></li>
                            </ul>
                        </div>

                        <!-- Action 2 -->
                        <div class="ms-5 mt-2 p-3 rounded" style="background: rgba(255,255,255,0.03);">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge me-2"
                                    style="background: var(--gem-green); color: #fff; padding: 5px 10px;">2</span>
                                <strong>ซื้อการ์ดพัฒนา</strong>
                            </div>
                            <ul class="mb-0" style="color: var(--text-secondary); padding-left: 20px;">
                                <li>จ่าย Token ตามราคาที่แสดงบนการ์ด</li>
                                <li>การ์ดที่มีจะช่วย <strong class="text-light">ลดราคา</strong> การซื้อการ์ดใบต่อไป</li>
                                <li>การ์ดบางใบให้ <strong class="text-gold">คะแนน</strong></li>
                            </ul>
                        </div>

                        <!-- Action 3 -->
                        <div class="ms-5 mt-2 p-3 rounded" style="background: rgba(255,255,255,0.03);">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge me-2"
                                    style="background: var(--gem-gold); color: #1a1a1a; padding: 5px 10px;">3</span>
                                <strong>จองการ์ด</strong>
                            </div>
                            <ul class="mb-0" style="color: var(--text-secondary); padding-left: 20px;">
                                <li>จองการ์ดไว้ซื้อทีหลัง (เก็บไว้ได้สูงสุด <strong class="text-light">3 ใบ</strong>)
                                </li>
                                <li>ได้รับ <strong class="text-gold">Gold Token 1 เหรียญ</strong> (ใช้แทนสีใดก็ได้)</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Nobles -->
                    <div class="rules-section">
                        <div class="d-flex align-items-start">
                            <div class="rules-icon bg-gem-red">👑</div>
                            <div>
                                <div class="rules-title">ขุนนาง (Nobles)</div>
                                <p class="mb-0" style="color: var(--text-secondary);">
                                    เมื่อคุณมีการ์ดครบตามเงื่อนไขของขุนนาง ขุนนางจะ <strong
                                        class="text-light">มาเยี่ยมอัตโนมัติ</strong>
                                    แต่ละคนให้ <strong class="text-gold">3 คะแนน</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Player Count Rules -->
                    <div class="rules-section">
                        <div class="d-flex align-items-start">
                            <div class="rules-icon bg-gem-green">👥</div>
                            <div>
                                <div class="rules-title">กติกาตามจำนวนผู้เล่น</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <table class="table table-sm table-borderless mb-0"
                                style="color: var(--text-secondary); --bs-table-bg: transparent; --bs-table-color: var(--text-secondary);">
                                <thead>
                                    <tr style="border-bottom: 1px solid var(--border-subtle);">
                                        <th class="text-gold">จำนวนคน</th>
                                        <th class="text-center text-gold">Token ต่อสี</th>
                                        <th class="text-center text-gold">Gold Token</th>
                                        <th class="text-center text-gold">Noble</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><i class="bi bi-people-fill me-1"></i> 2 คน</td>
                                        <td class="text-center fw-bold text-light">4</td>
                                        <td class="text-center fw-bold text-light">5</td>
                                        <td class="text-center fw-bold text-light">3</td>
                                    </tr>
                                    <tr>
                                        <td><i class="bi bi-people-fill me-1"></i> 3 คน</td>
                                        <td class="text-center fw-bold text-light">5</td>
                                        <td class="text-center fw-bold text-light">5</td>
                                        <td class="text-center fw-bold text-light">4</td>
                                    </tr>
                                    <tr>
                                        <td><i class="bi bi-people-fill me-1"></i> 4 คน</td>
                                        <td class="text-center fw-bold text-light">7</td>
                                        <td class="text-center fw-bold text-light">5</td>
                                        <td class="text-center fw-bold text-light">5</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Card Levels -->
                    <div class="rules-section">
                        <div class="d-flex align-items-start">
                            <div class="rules-icon" style="background: var(--gem-black);">🃏</div>
                            <div>
                                <div class="rules-title">ระดับการ์ด</div>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="player-info-chip">
                                        <strong class="text-light">Level 1</strong> ราคาถูก · คะแนนน้อย
                                    </span>
                                    <span class="player-info-chip">
                                        <strong class="text-light">Level 2</strong> ราคาปานกลาง
                                    </span>
                                    <span class="player-info-chip">
                                        <strong class="text-light">Level 3</strong> ราคาแพง · คะแนนมาก
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tips -->
                    <div class="rules-section" style="border-bottom: none;">
                        <div class="d-flex align-items-start">
                            <div class="rules-icon bg-gem-gold">💡</div>
                            <div>
                                <div class="rules-title">เคล็ดลับ</div>
                                <ul class="mb-0" style="color: var(--text-secondary); padding-left: 20px;">
                                    <li>สะสมการ์ด Level 1 เพื่อลดค่าใช้จ่าย</li>
                                    <li>จับตามอง Noble cards — วางแผนสะสมสีที่ตรง</li>
                                    <li>จอง Card ที่คู่แข่งต้องการเพื่อบล็อก!</li>
                                    <li>Gold Token ใช้แทนอัญมณีสีได้ทุกสี</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-gem" data-bs-dismiss="modal">
                        <i class="bi bi-controller me-1"></i> เข้าใจแล้ว เล่นเลย!
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab switching
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
            event.target.closest('.tab-btn').classList.add('active');
            document.getElementById('tab-' + tab).classList.add('active');
        }

        // Player count selection
        let selectedMaxPlayers = 3;
        function selectPlayerCount(count, btn) {
            selectedMaxPlayers = count;
            document.getElementById('maxPlayersInput').value = count;
            document.querySelectorAll('.player-count-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Update token info
            const tokenInfo = { 2: '4', 3: '5', 4: '7' };
            const nobleInfo = { 2: '3', 3: '4', 4: '5' };
            document.getElementById('tokenInfoChip').innerHTML =
                `<i class="bi bi-gem"></i> Token ละ ${tokenInfo[count]} เหรียญ · Noble ${nobleInfo[count]} ใบ`;
        }

        // Toast function
        function showToast(msg, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast-msg toast-${type}`;
            const icons = { success: 'bi-check-circle', error: 'bi-x-circle', info: 'bi-info-circle', warning: 'bi-exclamation-triangle' };
            toast.innerHTML = `<i class="bi ${icons[type]}"></i> ${msg}`;
            document.getElementById('toastContainer').appendChild(toast);
            setTimeout(() => {
                toast.classList.add('toast-out');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Create Room
        $('#createRoomForm').submit(function (e) {
            e.preventDefault();
            const btn = $(this).find('button[type=submit]');
            btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> กำลังสร้าง...');

            $.ajax({
                url: 'api/create_room.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        showToast('สร้างห้องสำเร็จ!', 'success');
                        setTimeout(() => {
                            window.location.href = 'lobby.php?room=' + res.data.room_code;
                        }, 500);
                    } else {
                        showToast(res.message, 'error');
                        btn.prop('disabled', false).html('<i class="bi bi-plus-lg me-1"></i> สร้างห้องเกม');
                    }
                },
                error: function () {
                    showToast('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
                    btn.prop('disabled', false).html('<i class="bi bi-plus-lg me-1"></i> สร้างห้องเกม');
                }
            });
        });

        // Join Room
        $('#joinRoomForm').submit(function (e) {
            e.preventDefault();
            const btn = $(this).find('button[type=submit]');
            btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> กำลังเข้าร่วม...');

            $.ajax({
                url: 'api/join_room.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        showToast('เข้าร่วมสำเร็จ!', 'success');
                        setTimeout(() => {
                            window.location.href = 'lobby.php?room=' + res.data.room_code;
                        }, 500);
                    } else {
                        showToast(res.message, 'error');
                        btn.prop('disabled', false).html('<i class="bi bi-play-fill me-1"></i> เข้าร่วมเกม');
                    }
                },
                error: function () {
                    showToast('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
                    btn.prop('disabled', false).html('<i class="bi bi-play-fill me-1"></i> เข้าร่วมเกม');
                }
            });
        });
    </script>
</body>

</html>