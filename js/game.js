// js/game.js — Splendor Premium Game Engine

let gameState = null;
let currentAction = null;
let selectedTokens = [];
let activePlayerId = null;
let myPlayerId = (typeof MY_PLAYER_ID !== 'undefined') ? parseInt(MY_PLAYER_ID) : 0;
let winnerShown = false;
let knownPlayerNames = {};

const GEM_EMOJI = { white: '⚪', blue: '🔵', green: '🟢', red: '🔴', black: '⚫', gold: '🟡' };
const GEM_LABEL = { white: 'เพชร', blue: 'ไพลิน', green: 'มรกต', red: 'ทับทิม', black: 'นิล', gold: 'ทอง' };
const GEM_COLORS = ['white', 'blue', 'green', 'red', 'black', 'gold'];

// ===================== Toast System =====================
function showToast(msg, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast-msg toast-${type}`;
    const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', info: 'bi-info-circle-fill', warning: 'bi-exclamation-triangle-fill' };
    toast.innerHTML = `<i class="bi ${icons[type] || icons.info}"></i> ${msg}`;
    document.getElementById('toastContainer').appendChild(toast);
    setTimeout(() => { toast.classList.add('toast-out'); setTimeout(() => toast.remove(), 300); }, 3500);
}

// ===================== Polling =====================
function pollState() {
    $.getJSON('api/get_state.php', { game_id: GAME_ID }, function (res) {
        if (res.success) {
            // Always sync my player ID from server session
            if (res.data.my_player_id && parseInt(res.data.my_player_id) > 0) {
                myPlayerId = parseInt(res.data.my_player_id);
            }

            // Track player changes
            if (res.data.players) {
                const currentIds = {};
                res.data.players.forEach(p => { currentIds[p.id] = p.name; });

                // Check for players who left
                let someoneLeft = false;
                let leftName = '';
                for (let id in knownPlayerNames) {
                    if (!currentIds[id]) {
                        leftName = knownPlayerNames[id];
                        someoneLeft = true;
                        showToast(`⚠️ ${leftName} ออกจากเกมแล้ว`, 'warning');
                    }
                }
                knownPlayerNames = currentIds;

                // If only 1 player remains, end the game
                if (someoneLeft && res.data.players.length < 2) {
                    showGameEndOverlay(leftName);
                    return;
                }
            }

            let stateChanged = JSON.stringify(gameState) !== JSON.stringify(res.data);
            if (stateChanged) {
                gameState = res.data;
                renderBoard(gameState);
                renderPlayers(gameState);
                checkWinner(gameState);
            }
        }
    });
}

// ===================== Board Rendering =====================
function renderBoard(data) {
    const board = data.game;

    // Nobles
    let noblesHtml = '';
    board.board_nobles.forEach(n => {
        let reqHtml = '';
        for (let color in n.requirements) {
            reqHtml += `<div class="card-cost-dot token-${color}">${n.requirements[color]}</div>`;
        }
        noblesHtml += `<div class="noble-spl anim-pop">
            <div class="noble-points">${n.points}</div>
            <div class="noble-req">${reqHtml}</div>
        </div>`;
    });
    $('#nobles-area').html(noblesHtml);

    // Cards
    ['3', '2', '1'].forEach(level => {
        let rowHtml = `<div class="deck-indicator">
            <div class="level-num">${level}</div>
            <small>Lv.</small>
        </div>`;
        board.board_cards['level_' + level].forEach(c => {
            rowHtml += generateCardHtml(c);
        });
        $('#row-level-' + level).html(rowHtml);
    });

    // Tokens Bank
    let tokensHtml = '';
    GEM_COLORS.forEach(color => {
        let count = board.tokens_available[color] || 0;
        const isSelected = selectedTokens.includes(color);
        tokensHtml += `<div class="token-slot">
            <div class="token token-${color} ${isSelected ? 'selected' : ''} ${count <= 0 ? 'opacity-25' : ''}"
                onclick="handleTokenClick('${color}')"
                ${count <= 0 ? 'style="cursor:not-allowed; pointer-events:' + (count <= 0 ? 'none' : 'auto') + '"' : ''}>
                <span class="badge">${count}</span>
            </div>
            <span class="token-label">${GEM_EMOJI[color]}</span>
        </div>`;
    });
    $('#tokens-bank').html(tokensHtml);

    // Turn Indicator
    const turnPlayer = data.players.find(p => parseInt(p.id) === parseInt(board.turn_player_id));
    if (turnPlayer) {
        const isMyTurn = myPlayerId === 0 || parseInt(board.turn_player_id) === parseInt(myPlayerId);
        $('#turnIndicator').html(`
            ${isMyTurn ? '<i class="bi bi-hand-index-fill me-1"></i>' : '<i class="bi bi-hourglass-split me-1"></i>'}
            ${isMyTurn ? '🎯 ตาของคุณ' : `⏳ ตาของ <strong>${turnPlayer.name}</strong>`}
        `);
    }
}

function generateCardHtml(c) {
    let costHtml = '';
    for (let color in c.cost) {
        if (c.cost[color] > 0) {
            costHtml += `<div class="card-cost">
                <div class="card-cost-dot token-${color}">${c.cost[color]}</div>
            </div>`;
        }
    }
    return `<div class="card-spl gem-${c.gem} anim-pop" onclick="handleCardClick(${c.id}, ${c.level})">
        <div class="card-header-spl">
            <div class="card-points">${c.points > 0 ? c.points : ''}</div>
            <div class="card-gem-icon token-${c.gem}">${GEM_EMOJI[c.gem] || ''}</div>
        </div>
        <div class="card-body-spl">${GEM_EMOJI[c.gem] || '💎'}</div>
        <div class="card-cost-container">${costHtml}</div>
    </div>`;
}

// ===================== Players Rendering =====================
function renderPlayers(data) {
    const turnPlayerId = data.game.turn_player_id;
    activePlayerId = parseInt(turnPlayerId);
    let playersHtml = '';

    data.players.forEach((p, idx) => {
        const isActive = parseInt(p.id) === parseInt(turnPlayerId);
        const totalTokens = Object.values(p.tokens_owned || {}).reduce((a, b) => a + b, 0);

        // Gems display
        let gemsHtml = '';
        GEM_COLORS.forEach(color => {
            const t = p.tokens_owned[color] || 0;
            const card = (p.cards_owned && p.cards_owned[color]) || 0;
            if (t > 0 || card > 0) {
                gemsHtml += `<div class="player-gem-mini">
                    <div class="player-gem-dot token-${color}"></div>
                    <span class="text-light">${t}</span>
                    ${card > 0 ? `<span class="text-gold">+${card}</span>` : ''}
                </div>`;
            }
        });

        // Reserved cards
        let reservedHtml = '';
        if (isActive && p.cards_reserved && p.cards_reserved.length > 0) {
            reservedHtml = `<div class="mt-2 pt-2" style="border-top: 1px solid var(--border-subtle);">
                <small class="text-gold d-block mb-1"><i class="bi bi-bookmark-fill me-1"></i>การ์ดจอง</small>
                <div class="d-flex gap-1 flex-wrap">`;
            p.cards_reserved.forEach(rc => {
                let rcCost = '';
                for (let color in rc.cost) {
                    if (rc.cost[color] > 0) {
                        rcCost += `<div class="d-flex align-items-center gap-1">
                            <div class="rm-cost-dot token-${color}">${rc.cost[color]}</div>
                        </div>`;
                    }
                }
                reservedHtml += `<div class="reserved-mini" onclick="handleReservedCardClick(${rc.id})">
                    <div class="rm-header">
                        <span class="text-gold fw-bold">${rc.points > 0 ? rc.points : ''}</span>
                        <span>${GEM_EMOJI[rc.gem]}</span>
                    </div>
                    <div class="rm-costs">${rcCost}</div>
                </div>`;
            });
            reservedHtml += `</div></div>`;
        }

        const winnerBadge = p.score >= 15 ? '<span class="badge bg-success ms-1 anim-pop" style="font-size:0.65rem;">🏆 ชนะ!</span>' : '';

        playersHtml += `<div class="player-tag ${isActive ? 'active-player' : ''}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="player-name">
                    ${isActive ? '▶ ' : ''}${p.name} ${winnerBadge}
                </div>
                <div class="player-score">
                    <i class="bi bi-star-fill" style="font-size:0.8rem;"></i> ${p.score}
                </div>
            </div>
            <div class="d-flex gap-1 mb-2 flex-wrap" style="font-size:0.75rem;">
                <span class="badge" style="background: rgba(255,255,255,0.06); color: var(--text-secondary);">
                    <i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i>Token: ${totalTokens}/10
                </span>
                <span class="badge" style="background: rgba(255,255,255,0.06); color: var(--text-secondary);">
                    <i class="bi bi-bookmark me-1" style="font-size:0.5rem;"></i>จอง: ${p.cards_reserved ? p.cards_reserved.length : 0}/3
                </span>
            </div>
            <div class="d-flex gap-1 flex-wrap">${gemsHtml}</div>
            ${reservedHtml}
        </div>`;
    });

    $('#players-list').html(playersHtml);
}

// ===================== Token Actions =====================
function handleTokenClick(color) {
    if (parseInt(myPlayerId) !== 0 && parseInt(activePlayerId) !== parseInt(myPlayerId)) {
        showToast('ยังไม่ถึงตาคุณ!', 'warning');
        return;
    }
    if (color === 'gold') {
        showToast('ไม่สามารถหยิบ Gold ได้โดยตรง ต้องจองการ์ดเท่านั้น', 'warning');
        return;
    }

    const countOnBoard = gameState.game.tokens_available[color] || 0;
    let countInSelection = selectedTokens.filter(c => c === color).length;

    if (selectedTokens.length >= 3) {
        showToast('หยิบได้สูงสุด 3 เหรียญ', 'warning');
        return;
    }

    // Logic: 2 of same color
    if (countInSelection === 1) {
        if (selectedTokens.length > 1) {
            showToast('การหยิบ 2 สีเดียวกัน ไม่สามารถหยิบสีอื่นได้', 'warning');
            return;
        }
        if (countOnBoard < 4) {
            showToast('ต้องมีเหรียญสีนี้เหลือ ≥ 4 จึงจะหยิบ 2 ได้', 'warning');
            return;
        }
    }

    // Logic: already picked 2 of same
    if (countInSelection === 0 && selectedTokens.length >= 2 && selectedTokens[0] === selectedTokens[1]) {
        showToast('คุณเลือกหยิบ 2 สีเดียวกันแล้ว', 'warning');
        return;
    }

    selectedTokens.push(color);

    // Re-render tokens to show selection
    renderBoard(gameState);

    // Show action panel
    const selectedDisplay = selectedTokens.map(c => GEM_EMOJI[c]).join(' ');
    $('#action-panel').slideDown(200);
    $('#action-info').html(`เลือกแล้ว: ${selectedDisplay}`);
    $('#action-buttons').html(`
        <button class="btn-emerald btn-sm" onclick="confirmTokens()">
            <i class="bi bi-check-lg me-1"></i> หยิบ Token
        </button>
    `);
}

function confirmTokens() {
    const btn = $('#action-buttons button');
    btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> กำลังหยิบ...');

    $.post('api/take_tokens.php', {
        game_id: GAME_ID,
        player_id: activePlayerId,
        tokens: selectedTokens
    }, function (res) {
        if (res.success) {
            showToast(`หยิบ ${selectedTokens.map(c => GEM_EMOJI[c]).join(' ')} สำเร็จ!`, 'success');
            cancelAction();
            pollState();
        } else {
            showToast(res.message, 'error');
            cancelAction();
        }
    }, 'json');
}

// ===================== Card Actions =====================
function handleCardClick(cardId, level) {
    if (parseInt(myPlayerId) !== 0 && parseInt(activePlayerId) !== parseInt(myPlayerId)) {
        showToast('ยังไม่ถึงตาคุณ!', 'warning');
        return;
    }
    $('#action-panel').slideDown(200);
    $('#action-info').html('เลือกการ์ด');
    $('#action-buttons').html(`
        <button class="btn-emerald btn-sm" onclick="buyCard(${cardId}, false)">
            <i class="bi bi-cart-check me-1"></i> ซื้อการ์ด
        </button>
        <button class="btn-crystal btn-sm" style="padding: 8px 16px;" onclick="reserveCard(${cardId})">
            <i class="bi bi-bookmark-plus me-1"></i> จองการ์ด
        </button>
    `);
}

function handleReservedCardClick(cardId) {
    if (parseInt(myPlayerId) !== 0 && parseInt(activePlayerId) !== parseInt(myPlayerId)) {
        showToast('ยังไม่ถึงตาคุณ!', 'warning');
        return;
    }
    $('#action-panel').slideDown(200);
    $('#action-info').html('การ์ดที่จองไว้');
    $('#action-buttons').html(`
        <button class="btn-emerald btn-sm" onclick="buyCard(${cardId}, true)">
            <i class="bi bi-cart-check me-1"></i> ซื้อการ์ดที่จอง
        </button>
    `);
}

function cancelAction() {
    selectedTokens = [];
    currentAction = null;
    $('#action-panel').slideUp(200);
    if (gameState) renderBoard(gameState);
}

function buyCard(cardId, is_reserved) {
    $.post('api/buy_card.php', {
        game_id: GAME_ID,
        player_id: activePlayerId,
        card_id: cardId,
        is_reserved: is_reserved
    }, function (res) {
        if (res.success) {
            showToast('ซื้อการ์ดสำเร็จ! 🎉', 'success');
            if (res.data && res.data.noble_acquired) {
                setTimeout(() => showToast('👑 ขุนนางมาเยี่ยมคุณ! +3 คะแนน', 'warning'), 800);
            }
            cancelAction();
            pollState();
        } else {
            showToast(res.message, 'error');
        }
    }, 'json');
}

function reserveCard(cardId) {
    $.post('api/reserve_card.php', {
        game_id: GAME_ID,
        player_id: activePlayerId,
        card_id: cardId
    }, function (res) {
        if (res.success) {
            if (res.data && res.data.got_gold) {
                showToast('จองการ์ดสำเร็จ! ได้รับ 🟡 Gold Token 1 เหรียญ', 'success');
            } else {
                showToast('จองการ์ดสำเร็จ! (ไม่มี Gold เหลือหรือ Token เต็ม)', 'info');
            }
            cancelAction();
            pollState();
        } else {
            showToast(res.message, 'error');
        }
    }, 'json');
}

// ===================== Winner Check =====================
function checkWinner(data) {
    if (winnerShown) return;
    const winner = data.players.find(p => p.score >= 15);
    if (winner) {
        winnerShown = true;
        setTimeout(() => showWinnerCelebration(winner), 500);
    }
}

function showWinnerCelebration(winner) {
    // Confetti
    for (let i = 0; i < 60; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + 'vw';
        confetti.style.animationDuration = (2 + Math.random() * 2) + 's';
        confetti.style.animationDelay = Math.random() * 1.5 + 's';
        const colors = ['#FCD34D', '#EF4444', '#22C55E', '#3B82F6', '#F59E0B', '#A855F7'];
        confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.width = (6 + Math.random() * 8) + 'px';
        confetti.style.height = (6 + Math.random() * 8) + 'px';
        document.body.appendChild(confetti);
        setTimeout(() => confetti.remove(), 5000);
    }

    // Winner Overlay
    const overlay = document.createElement('div');
    overlay.className = 'winner-overlay';
    overlay.innerHTML = `<div class="winner-card">
        <div class="winner-crown">👑</div>
        <div class="winner-name">${winner.name}</div>
        <div class="winner-score">${winner.score} คะแนน · ชนะเกม!</div>
        <button class="btn-gem mt-4" onclick="this.closest('.winner-overlay').remove()">
            <i class="bi bi-trophy me-1"></i> ยอดเยี่ยม!
        </button>
        <div class="mt-3">
            <a href="index.php" class="text-muted-custom small" style="text-decoration:none;">
                <i class="bi bi-house me-1"></i> กลับหน้าหลัก
            </a>
        </div>
    </div>`;
    document.body.appendChild(overlay);
}

// ===================== Game End (Player Left) =====================
function showGameEndOverlay(playerName) {
    const overlay = document.createElement('div');
    overlay.className = 'winner-overlay';
    overlay.innerHTML = `<div class="winner-card">
        <div style="font-size: 3rem; margin-bottom: 10px;">🚪</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-gold); margin-bottom: 8px;">
            ${playerName} ออกจากเกม
        </div>
        <div style="color: var(--text-secondary); margin-bottom: 20px;">
            ไม่สามารถเล่นต่อได้ เกมจะจบลงอัตโนมัติ
        </div>
        <div style="font-size: 0.85rem; color: var(--text-secondary);">
            <i class="bi bi-arrow-right-circle me-1"></i>
            กำลังกลับหน้าหลัก<span class="waiting-dots"></span>
        </div>
    </div>`;
    document.body.appendChild(overlay);

    setTimeout(() => { window.location.href = 'index.php'; }, 4000);
}

// ===================== Leave Game =====================
function leaveGame() {
    if (!confirm('ออกจากเกม?')) return;
    $.post('api/leave_game.php', { game_id: GAME_ID }, function () {
        window.location.href = 'index.php';
    }).fail(function () {
        window.location.href = 'index.php';
    });
}

// ===================== Init =====================
$(document).ready(function () {
    pollState();
    setInterval(pollState, 2000);
});
