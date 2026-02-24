// js/sounds.js — Splendor Sound Engine (Web Audio API)
// Synthesized sounds — no external files needed!

const SoundEngine = (() => {
    let audioCtx = null;
    let musicEnabled = true;
    let sfxEnabled = true;
    let bgmGain = null;
    let bgmOsc = null;
    let bgmPlaying = false;

    function getCtx() {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    // ===== SFX: Token Pickup (coin clink) =====
    function playTokenPickup() {
        if (!sfxEnabled) return;
        const ctx = getCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(1200, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(2400, ctx.currentTime + 0.06);
        osc.frequency.exponentialRampToValueAtTime(1800, ctx.currentTime + 0.12);
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);
        osc.connect(gain).connect(ctx.destination);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.2);
    }

    // ===== SFX: Card Buy (success chime) =====
    function playCardBuy() {
        if (!sfxEnabled) return;
        const ctx = getCtx();
        const notes = [523, 659, 784]; // C5, E5, G5
        notes.forEach((freq, i) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.12, ctx.currentTime + i * 0.1);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.1 + 0.3);
            osc.connect(gain).connect(ctx.destination);
            osc.start(ctx.currentTime + i * 0.1);
            osc.stop(ctx.currentTime + i * 0.1 + 0.3);
        });
    }

    // ===== SFX: Card Reserve (soft bookmark) =====
    function playCardReserve() {
        if (!sfxEnabled) return;
        const ctx = getCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(600, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(900, ctx.currentTime + 0.15);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
        osc.connect(gain).connect(ctx.destination);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.25);
    }

    // ===== SFX: Your Turn (notification bell) =====
    function playYourTurn() {
        if (!sfxEnabled) return;
        const ctx = getCtx();
        [880, 1100].forEach((freq, i) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.1, ctx.currentTime + i * 0.15);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.15 + 0.4);
            osc.connect(gain).connect(ctx.destination);
            osc.start(ctx.currentTime + i * 0.15);
            osc.stop(ctx.currentTime + i * 0.15 + 0.4);
        });
    }

    // ===== SFX: Win Fanfare =====
    function playWinFanfare() {
        if (!sfxEnabled) return;
        const ctx = getCtx();
        const melody = [523, 587, 659, 784, 1047]; // C5, D5, E5, G5, C6
        melody.forEach((freq, i) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'square';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.08, ctx.currentTime + i * 0.12);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.12 + 0.4);
            osc.connect(gain).connect(ctx.destination);
            osc.start(ctx.currentTime + i * 0.12);
            osc.stop(ctx.currentTime + i * 0.12 + 0.4);
        });
    }

    // ===== SFX: Error / Warning =====
    function playError() {
        if (!sfxEnabled) return;
        const ctx = getCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(200, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(120, ctx.currentTime + 0.2);
        gain.gain.setValueAtTime(0.06, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
        osc.connect(gain).connect(ctx.destination);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.25);
    }

    // ===== SFX: Player Left =====
    function playPlayerLeft() {
        if (!sfxEnabled) return;
        const ctx = getCtx();
        [400, 300, 200].forEach((freq, i) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.08, ctx.currentTime + i * 0.15);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.15 + 0.3);
            osc.connect(gain).connect(ctx.destination);
            osc.start(ctx.currentTime + i * 0.15);
            osc.stop(ctx.currentTime + i * 0.15 + 0.3);
        });
    }

    // ===== SFX: Dice Roll (for lobby) =====
    function playDiceRoll() {
        if (!sfxEnabled) return;
        const ctx = getCtx();
        for (let i = 0; i < 8; i++) {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'square';
            osc.frequency.value = 800 + Math.random() * 600;
            gain.gain.setValueAtTime(0.04, ctx.currentTime + i * 0.06);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.06 + 0.05);
            osc.connect(gain).connect(ctx.destination);
            osc.start(ctx.currentTime + i * 0.06);
            osc.stop(ctx.currentTime + i * 0.06 + 0.05);
        }
    }

    // ===== SFX: Button Click =====
    function playClick() {
        if (!sfxEnabled) return;
        const ctx = getCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.value = 1000;
        gain.gain.setValueAtTime(0.08, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.06);
        osc.connect(gain).connect(ctx.destination);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.06);
    }

    // ===== BGM: Ambient Looping Chords =====
    function startBGM() {
        if (!musicEnabled || bgmPlaying) return;
        const ctx = getCtx();
        bgmGain = ctx.createGain();
        bgmGain.gain.setValueAtTime(0.15, ctx.currentTime); // Master BGM Volume
        bgmGain.connect(ctx.destination);

        const chords = [
            [261, 329, 392],  // Cmaj
            [293, 370, 440],  // Dmaj
            [220, 277, 329],  // Am
            [246, 311, 370],  // Bm
        ];

        let chordIdx = 0;
        function playChord() {
            if (!bgmPlaying) return;
            const chord = chords[chordIdx % chords.length];
            const now = getCtx().currentTime;

            chord.forEach((freq, i) => {
                const osc = ctx.createOscillator();
                const g = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, now);

                // Volume envelope for each note
                g.gain.setValueAtTime(0, now);
                g.gain.linearRampToValueAtTime(0.08, now + 0.5); // Fade in
                g.gain.exponentialRampToValueAtTime(0.001, now + 3.5); // Fade out

                osc.connect(g).connect(bgmGain);
                osc.start(now);
                osc.stop(now + 4.0);
            });
            chordIdx++;
            setTimeout(playChord, 3800); // Trigger next chord just before current ends
        }

        bgmPlaying = true;
        playChord();
    }

    function stopBGM() {
        bgmPlaying = false;
        if (bgmGain) {
            bgmGain.gain.exponentialRampToValueAtTime(0.001, getCtx().currentTime + 0.5);
        }
    }

    function toggleMusic() {
        musicEnabled = !musicEnabled;
        if (musicEnabled) {
            startBGM();
        } else {
            stopBGM();
        }
        return musicEnabled;
    }

    function toggleSFX() {
        sfxEnabled = !sfxEnabled;
        return sfxEnabled;
    }

    function isMusicOn() { return musicEnabled; }
    function isSFXOn() { return sfxEnabled; }

    // Auto-init audio context on first user interaction
    function initOnInteraction() {
        document.addEventListener('click', function handler() {
            getCtx();
            document.removeEventListener('click', handler);
        }, { once: true });
    }

    initOnInteraction();

    return {
        tokenPickup: playTokenPickup,
        cardBuy: playCardBuy,
        cardReserve: playCardReserve,
        yourTurn: playYourTurn,
        winFanfare: playWinFanfare,
        error: playError,
        playerLeft: playPlayerLeft,
        diceRoll: playDiceRoll,
        click: playClick,
        startBGM,
        stopBGM,
        toggleMusic,
        toggleSFX,
        isMusicOn,
        isSFXOn,
    };
})();
