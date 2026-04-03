<?php
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ElitePLAY Smart TV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1"></script>
    <style>
        :root {
            --bg: #020617;
            --panel: rgba(2, 10, 30, 0.9);
            --panel-strong: rgba(3, 12, 35, 0.98);
            --line: rgba(148, 163, 184, 0.26);
            --text: #e2e8f0;
            --muted: #94a3b8;
            --focus: #38bdf8;
            --focus-soft: rgba(56, 189, 248, 0.18);
        }

        * { box-sizing: border-box; }

        * {
            font-family: 'Outfit', 'Segoe UI', sans-serif;
        }

        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: 'Outfit', 'Segoe UI', sans-serif;
            overflow: hidden;
        }

        .screen {
            position: relative;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(90vw 90vh at 80% -10%, rgba(56, 189, 248, 0.2), transparent 50%),
                radial-gradient(80vw 80vh at -10% 100%, rgba(14, 116, 144, 0.2), transparent 55%),
                #020617;
        }

        #tv-player {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #000;
        }

        #tv-iframe-player {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
            background: #000;
            display: none;
            pointer-events: none;
        }

        .top-hud {
            position: absolute;
            inset: 0 0 auto 0;
            z-index: 20;
            padding: 18px 26px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            background: linear-gradient(180deg, rgba(2, 6, 23, 0.78), rgba(2, 6, 23, 0));
            pointer-events: none;
            opacity: 1;
            transition: opacity 0.24s ease;
        }

        .top-hud.hidden {
            opacity: 0;
        }

        .brand {
            display: block;
            line-height: 0;
        }

        .brand img {
            width: 220px;
            max-width: 36vw;
            height: auto;
            display: block;
            object-fit: contain;
        }

        .channel-hud {
            text-align: right;
            max-width: 52vw;
        }

        .channel-hud h1 {
            margin: 0;
            font-size: 26px;
            text-shadow: 0 3px 20px rgba(0, 0, 0, 0.65);
        }

        .menu-panel {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: min(42vw, 480px);
            z-index: 31;
            transform: translate3d(-102%, 0, 0);
            transition: transform 0.16s ease-out;
            background: var(--panel-strong);
            border-right: 1px solid var(--line);
            display: grid;
            grid-template-rows: auto 1fr auto;
            will-change: transform;
            contain: layout paint;
        }

        .menu-panel.with-category { transform: translate3d(182px, 0, 0); }

        .category-panel {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 182px;
            z-index: 32;
            transform: translate3d(-102%, 0, 0);
            transition: transform 0.16s ease-out;
            background: #030c23;
            border-right: 1px solid var(--line);
            display: grid;
            grid-template-rows: auto 1fr;
            will-change: transform;
            contain: layout paint;
        }

        .category-panel.open {
            transform: translate3d(0, 0, 0);
        }

        .games-panel {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: min(42vw, 540px);
            z-index: 30;
            transform: translate3d(-102%, 0, 0);
            transition: transform 0.16s ease-out;
            background: var(--panel-strong);
            border-right: 1px solid var(--line);
            display: grid;
            grid-template-rows: auto 1fr;
            will-change: transform;
            contain: layout paint;
            min-height: 0;
        }

        .games-panel.open { transform: translate3d(0, 0, 0); }

        .menu-panel.open { transform: translate3d(0, 0, 0); }
        .menu-panel.open.with-category { transform: translate3d(182px, 0, 0); }

        .menu-head {
            padding: 18px 18px 12px;
            border-bottom: 1px solid var(--line);
        }

        .menu-head h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .menu-head p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .category-list {
            margin: 0;
            padding: 10px;
            list-style: none;
            overflow-y: auto;
            display: grid;
            gap: 8px;
            contain: content;
        }

        .category-list::-webkit-scrollbar { width: 5px; }
        .category-list::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.35); border-radius: 999px; }

        .cat-btn {
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(15, 23, 42, 0.75);
            color: var(--text);
            border-radius: 10px;
            padding: 10px 8px;
            white-space: normal;
            text-align: center;
            font-size: 16px;
            font-weight: 600;
        }

        .cat-btn.active {
            border-color: var(--focus);
            background: var(--focus-soft);
            color: #e0f2fe;
        }

        .cat-btn.nav-focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.22);
            color: #dcfce7;
        }

        .channel-list {
            margin: 0;
            padding: 10px;
            list-style: none;
            overflow-y: auto;
            display: grid;
            gap: 8px;
            contain: content;
        }

        .channel-list::-webkit-scrollbar { width: 6px; }
        .channel-list::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.35); border-radius: 999px; }

        .channel-item {
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            background: rgba(8, 15, 35, 0.82);
            padding: 14px;
            display: grid;
            grid-template-columns: 72px 1fr;
            gap: 10px;
            align-items: center;
            transition: background-color 0.14s linear, border-color 0.14s linear, box-shadow 0.14s linear;
        }

        .channel-item img {
            width: 74px;
            height: 50px;
            object-fit: contain;
            pointer-events: none;
        }

        .channel-item .name {
            font-size: 22px;
            font-weight: 600;
            line-height: 1.2;
        }

        .channel-item.active {
            border-color: var(--focus);
            background: linear-gradient(135deg, rgba(12, 74, 110, 0.5), rgba(7, 17, 38, 0.9));
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.35);
        }

        .games-list {
            margin: 0;
            padding: 10px;
            list-style: none;
            overflow-y: auto;
            display: grid;
            gap: 8px;
            contain: content;
            -webkit-overflow-scrolling: touch;
            min-height: 0;
        }

        .games-list::-webkit-scrollbar { width: 6px; }
        .games-list::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.35); border-radius: 999px; }

        .game-item {
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            background: rgba(8, 15, 35, 0.82);
            padding: 12px;
            display: grid;
            gap: 10px;
            color: #e2e8f0;
            min-height: 190px;
            overflow: hidden;
            transition: background-color 0.14s linear, border-color 0.14s linear, box-shadow 0.14s linear;
        }

        .game-item.active {
            border-color: var(--focus);
            background: linear-gradient(135deg, rgba(12, 74, 110, 0.5), rgba(7, 17, 38, 0.9));
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.35);
        }

        .game-head {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        }

        .game-head-title {
            font-size: 16px;
            font-weight: 700;
            color: #f8fafc;
            line-height: 1.2;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .game-body {
            display: grid;
            gap: 10px;
        }

        .game-badges {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .game-badge {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.4px;
            color: #f8fafc;
            border: 1px solid rgba(148,163,184,0.35);
            border-radius: 999px;
            padding: 3px 8px;
            white-space: nowrap;
        }

        .game-badge.live {
            color: #fecaca;
            border-color: rgba(239, 68, 68, 0.5);
            background: rgba(185, 28, 28, 0.35);
        }

        .game-badge.minute {
            color: #86efac;
            border-color: rgba(34, 197, 94, 0.45);
            background: rgba(6, 78, 59, 0.34);
        }

        .game-badge.minute.idle {
            color: #cbd5e1;
            border-color: rgba(148,163,184,0.35);
            background: rgba(51,65,85,0.34);
        }

        .game-teams-grid {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 12px;
        }

        .game-league {
            font-size: 12px;
            color: #93c5fd;
            line-height: 1.2;
        }

        .game-team-col {
            display: grid;
            justify-items: center;
            gap: 6px;
            min-width: 0;
        }

        .game-team-name {
            font-size: 15px;
            font-weight: 700;
            text-align: center;
            line-height: 1.15;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .game-score-box {
            border: 1px solid rgba(148,163,184,0.28);
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.45);
            min-width: 110px;
            padding: 8px 10px;
            display: grid;
            justify-items: center;
            gap: 2px;
        }

        .game-score {
            font-size: 34px;
            font-weight: 700;
            line-height: 1;
            letter-spacing: 0.3px;
        }

        .game-score-box small {
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }

        .game-team-col img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            pointer-events: none;
        }

        .game-foot {
            border-top: 1px solid rgba(148,163,184,0.2);
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 10px;
        }

        .game-foot .date {
            font-size: 12px;
            color: #94a3b8;
            letter-spacing: 0.4px;
        }

        .game-foot .time {
            font-size: 14px;
            font-weight: 700;
            color: #e2e8f0;
        }

        .auth-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 45;
            display: none;
            background: rgba(2, 6, 23, 0.78);
            backdrop-filter: blur(3px);
        }

        .auth-overlay.show {
            display: block;
        }

        .auth-card {
            width: min(760px, 90vw);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border: 1px solid rgba(56, 189, 248, 0.42);
            border-radius: 18px;
            background: linear-gradient(145deg, rgba(7, 19, 44, 0.97), rgba(3, 11, 28, 0.94));
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            padding: 26px;
            text-align: center;
            display: grid;
            gap: 14px;
        }

        .auth-card h2 {
            margin: 0;
            font-size: 30px;
            letter-spacing: 0.4px;
        }

        .auth-card p {
            margin: 0;
            color: #dbeafe;
            font-size: 17px;
            line-height: 1.45;
        }

        .auth-card .pair-status {
            color: #93c5fd;
            font-size: 14px;
        }

        .auth-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .auth-qr-wrap {
            display: grid;
            justify-items: center;
            gap: 8px;
            margin-top: 4px;
        }

        .auth-qr {
            width: 220px;
            height: 220px;
            border-radius: 12px;
            border: 1px solid rgba(148,163,184,0.35);
            background: #fff;
            object-fit: contain;
        }

        .auth-code {
            font-size: 13px;
            letter-spacing: 1.1px;
            color: #bfdbfe;
        }

        .auth-btn {
            border: 1px solid rgba(56, 189, 248, 0.35);
            background: rgba(56, 189, 248, 0.16);
            color: #e0f2fe;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .auth-btn:hover {
            background: rgba(56, 189, 248, 0.28);
        }

        .toast {
            position: absolute;
            left: 50%;
            bottom: 28px;
            transform: translateX(-50%);
            z-index: 40;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.4);
            background: rgba(2, 6, 23, 0.88);
            color: var(--text);
            font-size: 13px;
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }

        .toast.show { opacity: 1; }

        .quality-panel {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 14px;
            width: min(92vw, 1280px);
            z-index: 41;
            border: 1px solid rgba(148,163,184,0.35);
            border-radius: 14px;
            background: rgba(2, 10, 30, 0.94);
            padding: 12px;
            display: none;
            gap: 8px;
            backdrop-filter: blur(6px);
        }

        .quality-panel.open {
            display: grid;
        }

        .quality-title {
            font-size: 18px;
            color: #cbd5e1;
            font-weight: 600;
            padding: 2px 6px 8px;
        }

        #quality-list {
            display: flex;
            align-items: center;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 2px;
        }

        .quality-item {
            border: 1px solid rgba(148,163,184,0.3);
            border-radius: 10px;
            background: rgba(15,23,42,0.82);
            color: #e2e8f0;
            font-size: 20px;
            font-weight: 600;
            padding: 12px 18px;
            min-width: 140px;
            white-space: nowrap;
        }

        .quality-item.active {
            border-color: #22c55e;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.24);
            color: #dcfce7;
        }
    </style>
</head>
<body>
<div class="screen">
    <video id="tv-player" autoplay playsinline muted></video>
    <iframe id="tv-iframe-player" title="Smart TV Player"></iframe>

    <div class="top-hud">
        <div class="brand">
            <img src="imagens/elitelogo.webp" alt="ElitePLAY">
        </div>
        <div class="channel-hud">
            <h1 id="hud-channel-name">Carregando canais...</h1>
        </div>
    </div>

    <aside class="menu-panel" id="menu-panel" aria-hidden="true">
        <div class="menu-head">
            <h2>Canais ao vivo</h2>
            <p>Setas para navegar, Enter para assistir</p>
        </div>
        <ul class="channel-list" id="channel-list"></ul>
    </aside>

    <aside class="category-panel" id="category-panel" aria-hidden="true">
        <div class="menu-head">
            <h2>Categorias</h2>
            <p>Setas para escolher</p>
        </div>
        <ul class="category-list" id="category-list"></ul>
    </aside>

    <aside class="games-panel" id="games-panel" aria-hidden="true">
        <div class="menu-head">
            <h2>Jogos de hoje</h2>
            <p>Setas para navegar, Enter para assistir</p>
        </div>
        <ul class="games-list" id="games-list"></ul>
    </aside>

    <div class="auth-overlay" id="auth-overlay">
        <div class="auth-card">
            <h2>Autorizar aplicativo pelo celular</h2>
            <p>Escaneie o QR Code, faça login no celular e confirme a autorização.</p>
            <div class="auth-qr-wrap">
                <img class="auth-qr" id="auth-qr" alt="QR Code para autorizar Smart TV" src="">
                <div class="auth-code" id="auth-code">Gerando pareamento...</div>
            </div>
            <p class="pair-status" id="pair-status">Aguardando autorização...</p>
            <div class="auth-actions">
                <button class="auth-btn" type="button" id="retry-pair-btn">Tentar novamente</button>
            </div>
        </div>
    </div>

    <div class="quality-panel" id="quality-panel" aria-hidden="true">
        <div class="quality-title">Qualidade do canal</div>
        <div id="quality-list"></div>
    </div>

    <div class="toast" id="toast"></div>
</div>

<script>
    const CHANNELS_URL = 'proxy_embedtv.php?resource=channels';
    const SOURCE70_URL = 'smarttv_70_proxy.php';
    const SCORES_URL = 'smarttv_scores_proxy.php';
    const SMARTTV_PAIR_API = 'smarttv_pair_api.php';
    const SMARTTV_TOKEN_KEY = 'eliteplay_smarttv_token';
    const menuPanel = document.getElementById('menu-panel');
    const categoryPanel = document.getElementById('category-panel');
    const gamesPanel = document.getElementById('games-panel');
    const categoryList = document.getElementById('category-list');
    const channelList = document.getElementById('channel-list');
    const gamesList = document.getElementById('games-list');
    const topHud = document.querySelector('.top-hud');
    const hudName = document.getElementById('hud-channel-name');
    const video = document.getElementById('tv-player');
    const iframePlayer = document.getElementById('tv-iframe-player');
    const toast = document.getElementById('toast');
    const qualityPanel = document.getElementById('quality-panel');
    const qualityList = document.getElementById('quality-list');
    const authOverlay = document.getElementById('auth-overlay');
    const authQr = document.getElementById('auth-qr');
    const authCode = document.getElementById('auth-code');
    const pairStatus = document.getElementById('pair-status');
    const retryPairBtn = document.getElementById('retry-pair-btn');

    let hls = null;
    let channels = [];
    let filteredChannels = [];
    let hidden70SourceData = null;
    let gamesToday = [];
    let categories = [];
    let selectedCategory = 'ALL';
    let selectedIndex = 0;
    let selectedGameIndex = 0;
    let categoryNavIndex = 0;
    let menuOpen = false;
    let categoryOpen = false;
    let gamesOpen = false;
    let menuFocus = 'channels';
    let lastOkAt = 0;
    let smartTvAuthorized = false;
    let pairId = '';
    let pairPollTimer = null;
    let hudHideTimer = null;
    let gamesRefreshTimer = null;
    let channelItemEls = [];
    let gameItemEls = [];
    let categoryBtnEls = [];
    let currentPlayingChannel = null;
    let currentPlayingUrl = '';
    let qualityOptions = [];
    let qualityIndex = 0;
    let qualityOpen = false;

    const FIXED_CATEGORIES = ['TODOS', 'TELECINE', 'PREMIERE', 'SPORTV', 'ESPN', 'ESPORTES', 'HBO', 'BBB', 'ABERTOS'];

    function isM3U8(url) {
        return /\.m3u8(\?|$)/i.test(String(url || ''));
    }

    function shouldUseProxy(url) {
        if (!url) return false;
        const u = String(url);
        if (u.includes('s27-usa-cloudfront-net.online')) return false;
        return isM3U8(u);
    }

    function toBase64Utf8(value) {
        try {
            return btoa(unescape(encodeURIComponent(String(value || ''))));
        } catch (e) {
            return btoa(String(value || ''));
        }
    }

    function build70PlayerUrl(embedUrl, fallbackName = 'Canal') {
        try {
            const u = new URL(String(embedUrl || ''));
            const host = u.hostname.toLowerCase();
            if (!host.includes('embed.70noticias.com.br')) {
                return String(embedUrl || '');
            }

            const id = u.searchParams.get('v');
            if (!id) {
                return String(embedUrl || '');
            }

            const nameRaw = u.searchParams.get('n') || fallbackName;
            const img = u.searchParams.get('i') || '';
            const nameB64 = toBase64Utf8(nameRaw);
            return `https://embed.70noticias.com.br/player.php?type=live_streams&id=${encodeURIComponent(id)}&ext=m3u8&name=${encodeURIComponent(nameB64)}&img=${encodeURIComponent(img)}`;
        } catch (e) {
            return String(embedUrl || '');
        }
    }

    function showTopHudTemporarily() {
        if (!topHud) return;

        if (hudHideTimer) {
            clearTimeout(hudHideTimer);
            hudHideTimer = null;
        }

        topHud.classList.remove('hidden');
        hudHideTimer = setTimeout(() => {
            if (!menuOpen && !gamesOpen && !categoryOpen) {
                topHud.classList.add('hidden');
            }
        }, 3000);
    }

    function setPairStatus(message) {
        if (pairStatus) {
            pairStatus.textContent = message;
        }
    }

    function showAuthOverlay() {
        authOverlay.classList.add('show');
    }

    function hideAuthOverlay() {
        authOverlay.classList.remove('show');
    }

    function stopPairPolling() {
        if (pairPollTimer) {
            clearInterval(pairPollTimer);
            pairPollTimer = null;
        }
    }

    function requestFullscreenMode() {
        const root = document.documentElement;
        const request = root.requestFullscreen
            || root.webkitRequestFullscreen
            || root.msRequestFullscreen;

        if (typeof request === 'function') {
            request.call(root).catch(() => null);
            return;
        }

        if (typeof video.webkitEnterFullscreen === 'function') {
            try {
                video.webkitEnterFullscreen();
            } catch (e) {
                // sem acao
            }
        }
    }

    function showToast(message) {
        toast.textContent = message;
        toast.classList.add('show');
        window.clearTimeout(showToast._timer);
        showToast._timer = setTimeout(() => toast.classList.remove('show'), 1800);
    }

    function buildQualityOptionsFromChannel(channel) {
        if (!channel) return [];
        const base = Array.isArray(channel.streams) ? channel.streams : [];
        const dedup = [];
        const seen = new Set();

        base.forEach(stream => {
            const url = String(stream?.url || '');
            if (!url || seen.has(url)) return;
            seen.add(url);
            dedup.push({
                label: String(stream?.name || 'Qualidade'),
                url,
            });
        });

        if (!dedup.length && channel.streamUrl) {
            dedup.push({ label: 'EmbedTV', url: channel.streamUrl });
        }

        return sortQualityOptions(dedup);
    }

    function renderQualityPanel() {
        if (!qualityList) return;

        if (!qualityOptions.length) {
            qualityList.innerHTML = '<div class="quality-item active">Auto</div>';
            return;
        }

        qualityList.innerHTML = '';
        qualityOptions.forEach((opt, idx) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'quality-item' + (idx === qualityIndex ? ' active' : '');
            item.textContent = opt.label;
            item.addEventListener('click', () => {
                qualityIndex = idx;
                applyQualitySelection();
            });
            qualityList.appendChild(item);
        });
    }

    function openQualityPanel() {
        if (!currentPlayingChannel || menuOpen || gamesOpen) return;

        qualityOptions = buildQualityOptionsFromChannel(currentPlayingChannel);
        qualityIndex = Math.max(0, qualityOptions.findIndex(opt => opt.url === currentPlayingUrl));
        if (qualityIndex < 0) qualityIndex = 0;

        qualityOpen = true;
        qualityPanel.classList.add('open');
        qualityPanel.setAttribute('aria-hidden', 'false');
        renderQualityPanel();
    }

    function closeQualityPanel() {
        qualityOpen = false;
        qualityPanel.classList.remove('open');
        qualityPanel.setAttribute('aria-hidden', 'true');
    }

    function applyQualitySelection() {
        const selected = qualityOptions[qualityIndex];
        if (!selected || !currentPlayingChannel) {
            closeQualityPanel();
            return;
        }
        playChannel(currentPlayingChannel, selected.url);
        closeQualityPanel();
        showToast(`Qualidade: ${selected.label}`);
    }

    function normalizeText(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function normalizeChannelName(name) {
        let n = String(name || '').toLowerCase().replace(/[\s\-]/g, '');

        if (n.startsWith('bbb')) {
            n = n.replace(/2[0-9]cam0?/g, '').replace(/2[0-9]mosaico/g, 'mosaico');
        }

        if (n.startsWith('hbomax')) {
            n = n.replace('hbomax', 'max');
        }

        n = n.replace(/([a-z])0+([0-9]+)$/, '$1$2');

        if (/premiereclubes|premiereserie/i.test(n)) {
            n = 'premiere1';
        }

        if (/^(sportv|espn)1$/i.test(n)) {
            n = n.replace(/1$/i, '');
        }

        return n;
    }

    function parse70Name(fullName) {
        const original = String(fullName || '').trim();
        const patterns = [/(?:FHD|HD|SD|4K|1080p|720p)/i, /\[LEG\]/i, /\(ALT\)/i, /\[ALT\]/i, /(?:\s|^)ALT(?:\s|$)/i, /(?:\s|^)\*(?:\s|$)/i];
        let splitIndex = original.length;

        for (const p of patterns) {
            const idx = original.search(p);
            if (idx > 0 && idx < splitIndex) splitIndex = idx;
        }

        const baseName = original.substring(0, splitIndex).trim();
        const quality = original.substring(baseName.length).trim();
        return { baseName: baseName || original, quality: quality || 'Principal' };
    }

    function merge70StreamsIntoEmbed(embedChannels, source70) {
        const merged = Array.isArray(embedChannels) ? embedChannels.map(c => ({ ...c, streams: [...(c.streams || [])] })) : [];
        const mapByNorm = new Map();
        merged.forEach(c => {
            mapByNorm.set(normalizeChannelName(c.name), c);
            mapByNorm.set(normalizeChannelName(String(c.name).replace(/^sporto/i, 'sportv')), c);
        });

        if (!source70 || typeof source70 !== 'object') {
            return merged;
        }

        Object.keys(source70).forEach(cat => {
            const list = Array.isArray(source70[cat]) ? source70[cat] : [];
            list.forEach(item => {
                const parsed = parse70Name(item?.nome || '');
                const norm = normalizeChannelName(parsed.baseName);
                const target = mapByNorm.get(norm) || mapByNorm.get(normalizeChannelName(parsed.baseName.replace(/^sporto/i, 'sportv')));
                const url = String(item?.link || '');
                if (!target || !url) return;

                if (!target.streams.some(s => s.url === url)) {
                    target.streams.push({ name: parsed.quality, url, source: '70noticias' });
                }

                if (!target.logo && item?.capa) {
                    target.logo = String(item.capa);
                }
            });
        });

        return merged;
    }

    function sortQualityOptions(options) {
        const rank = (name) => {
            const n = String(name || '').toUpperCase();
            if (n.includes('4K')) return 1;
            if (n.includes('FHD') || n.includes('1080')) return 2;
            if (n.includes('HD') || n.includes('720')) return 3;
            if (n.includes('SD') || n.includes('480')) return 4;
            if (n.includes('EMBEDTV')) return 5;
            return 6;
        };
        return [...options].sort((a, b) => rank(a.label) - rank(b.label));
    }

    function guessCategory(channel, categoryMap) {
        const baseCategory = Array.isArray(channel.categories)
            ? channel.categories.map(id => categoryMap[id]).find(Boolean)
            : '';

        const name = normalizeText(channel.name);
        if (name.includes('sportv') || name.includes('sporto') || name.includes('spor tv')) return 'SPORTV';
        if (name.includes('premiere')) return 'PREMIERE';
        if (name.includes('espn')) return 'ESPN';
        if (name.includes('globo')) return 'GLOBO';
        return baseCategory || 'OUTROS';
    }

    function mapChannels(payload) {
        const categoryMap = {};
        (payload.categories || []).forEach(cat => {
            if (cat && typeof cat.id !== 'undefined') {
                categoryMap[cat.id] = String(cat.name || '');
            }
        });

        return (payload.channels || []).map(item => {
            const streamFromId = item.id
                ? `https://mr.s27-usa-cloudfront-net.online/fontes/mr/${item.id}.m3u8`
                : '';

            const fallback = String(item.url || '');
            const streamUrl = streamFromId || fallback;

            return {
                id: item.id,
                name: String(item.name || 'Canal sem nome'),
                logo: String(item.image || ''),
                category: guessCategory(item, categoryMap),
                streamUrl,
                fallbackUrl: fallback,
                streams: [{ name: 'EmbedTV', url: streamUrl, source: 'embedtv' }],
            };
        }).filter(c => !!c.streamUrl);
    }

    function gameStatusByTimer(start, end) {
        const now = Date.now() / 1000;
        if (start && end && now >= start && now <= end) return 'AO VIVO';
        if (end && now > end) return 'ENCERRADO';
        return 'AGENDADO';
    }

    function formatGameHour(ts) {
        if (!ts) return '--:--';
        const d = new Date(ts * 1000);
        return d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    function formatGameDate(ts) {
        if (!ts) return '--/--/----';
        const d = new Date(ts * 1000);
        return d.toLocaleDateString('pt-BR');
    }

    function mapGames(payload) {
        const raw = Array.isArray(payload) ? payload : [];
        return raw.map((item, idx) => {
            const timer = item?.data?.timer || {};
            const start = Number(timer.start || 0);
            const end = Number(timer.end || 0);
            const teams = item?.data?.teams || {};
            const home = teams.home || {};
            const away = teams.away || {};

            return {
                id: `gm_${idx}_${start}`,
                title: String(item?.title || ''),
                league: String(item?.data?.league || 'Jogo de hoje'),
                image: String(item?.image || ''),
                start,
                end,
                status: gameStatusByTimer(start, end),
                statusText: '',
                hourLabel: formatGameHour(start),
                dateLabel: formatGameDate(start),
                homeName: String(home.name || 'Time mandante'),
                homeImg: String(home.image || ''),
                homeScore: '',
                awayName: String(away.name || 'Time visitante'),
                awayImg: String(away.image || ''),
                awayScore: '',
                players: Array.isArray(item?.players) ? item.players : [],
            };
        }).sort((a, b) => a.start - b.start);
    }

    function normalizeChannelToken(value) {
        return normalizeText(value).replace(/[^a-z0-9]/g, '');
    }

    function findChannelForGame(game) {
        if (!game || !Array.isArray(game.players)) return null;

        for (const playerRef of game.players) {
            const ref = String(playerRef || '');
            if (ref.includes('.m3u8')) {
                return {
                    name: game.title || 'Jogo ao vivo',
                    category: 'JOGOS',
                    streamUrl: ref,
                };
            }

            let token = '';
            try {
                const url = new URL(ref);
                const pieces = url.pathname.split('/').filter(Boolean);
                token = pieces.length ? pieces[pieces.length - 1] : '';
            } catch (e) {
                token = ref.split('/').filter(Boolean).pop() || '';
            }

            const wanted = normalizeChannelToken(token).replace(/^sporto/, 'sportv');
            if (!wanted) continue;

            const found = channels.find(ch => {
                const cname = normalizeChannelToken(ch.name).replace(/^sporto/, 'sportv');
                return cname.includes(wanted) || wanted.includes(cname);
            });

            if (found) return found;
        }

        return null;
    }

    function channelMatchesFixedCategory(channel, categoryName) {
        if (categoryName === 'TODOS') return true;

        const name = normalizeText(channel?.name || '');
        const cat = normalizeText(channel?.category || '');
        const combined = `${name} ${cat}`;

        if (categoryName === 'TELECINE') return combined.includes('telecine');
        if (categoryName === 'PREMIERE') return combined.includes('premiere');
        if (categoryName === 'SPORTV') return combined.includes('sportv') || combined.includes('sporto') || combined.includes('spor tv');
        if (categoryName === 'ESPN') return combined.includes('espn');
        if (categoryName === 'HBO') return combined.includes('hbo') || combined.includes('max');
        if (categoryName === 'BBB') return combined.includes('bbb');

        if (categoryName === 'ESPORTES') {
            return (
                combined.includes('sportv') || combined.includes('sporto') || combined.includes('premiere') || combined.includes('espn') ||
                combined.includes('combate') || combined.includes('ufc') || combined.includes('futebol') || combined.includes('lutas') || combined.includes('esporte')
            );
        }

        if (categoryName === 'ABERTOS') {
            return (
                combined.includes('globo') || combined.includes('sbt') || combined.includes('record') ||
                combined.includes('band') || combined.includes('rede tv') || combined.includes('abertos')
            );
        }

        return true;
    }

    async function fetchLiveScores() {
        let html = '';
        try {
            const response = await fetch(`${SCORES_URL}?_t=${Date.now()}`, { cache: 'no-store' });
            html = await response.text();
        } catch (e) {
            return [];
        }

        if (!html) return [];

        try {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const links = Array.from(doc.querySelectorAll('a[href]')).filter(a => {
                const href = String(a.getAttribute('href') || '').toLowerCase();
                return href.includes('.html') && a.querySelector('.status-name') && a.querySelector('h5');
            });

            return links.map(match => {
                const homeTeam = match.querySelector('h5.text-right.team_link')?.innerText?.trim()
                    || match.querySelector('h5.text-right')?.innerText?.trim() || '';
                const awayTeam = match.querySelector('h5.text-left.team_link')?.innerText?.trim()
                    || match.querySelector('h5.text-left')?.innerText?.trim() || '';
                const scoreElements = match.querySelectorAll('.match-score .badge');
                const homeScore = scoreElements[0]?.innerText?.trim() || '';
                const awayScore = scoreElements[1]?.innerText?.trim() || '';
                const statusText = match.querySelector('.status-name')?.innerText?.trim() || '';
                return { homeTeam, awayTeam, homeScore, awayScore, statusText };
            }).filter(item => item.homeTeam && item.awayTeam);
        } catch (e) {
            return [];
        }
    }

    function applyLiveScoresToGames(scores) {
        if (!Array.isArray(scores) || !scores.length || !gamesToday.length) return;

        gamesToday = gamesToday.map(game => {
            const homeSlug = normalizeText(game.homeName).replace(/[^a-z0-9]/g, '');
            const awaySlug = normalizeText(game.awayName).replace(/[^a-z0-9]/g, '');

            const match = scores.find(item => {
                const sHome = normalizeText(item.homeTeam).replace(/[^a-z0-9]/g, '');
                const sAway = normalizeText(item.awayTeam).replace(/[^a-z0-9]/g, '');
                return (sHome.includes(homeSlug) || homeSlug.includes(sHome)) && (sAway.includes(awaySlug) || awaySlug.includes(sAway));
            });

            if (!match) return game;

            const statusLow = normalizeText(match.statusText);
            let status = game.status;
            if (statusLow.includes('fin') || statusLow.includes('enc') || statusLow.includes('fim')) status = 'ENCERRADO';
            else if (statusLow.includes('vivo') || statusLow.includes("'") || statusLow.includes('1t') || statusLow.includes('2t') || statusLow.includes('int')) status = 'AO VIVO';

            return {
                ...game,
                status,
                statusText: match.statusText,
                homeScore: match.homeScore,
                awayScore: match.awayScore,
            };
        });
    }

    function renderCategories() {
        categoryList.innerHTML = '';
        categoryBtnEls = [];

        const selectedPos = Math.max(0, categories.indexOf(selectedCategory));
        categoryNavIndex = selectedPos;

        categories.forEach((cat, idx) => {
            const btn = document.createElement('button');
            btn.className = 'cat-btn' + (selectedCategory === cat ? ' active' : '');
            btn.textContent = cat;
            btn.addEventListener('click', () => {
                selectedCategory = cat;
                categoryNavIndex = idx;
                selectedIndex = 0;
                applyCategoryFilter();
            });
            const li = document.createElement('li');
            li.appendChild(btn);
            categoryBtnEls.push(btn);
            categoryList.appendChild(li);
        });

        updateCategoryNavFocus();
    }

    function updateCategoryNavFocus() {
        if (!categoryBtnEls.length) return;
        categoryBtnEls.forEach((btn, idx) => {
            btn.classList.toggle('nav-focus', idx === categoryNavIndex && menuOpen && menuFocus === 'categories');
        });
        const activeBtn = categoryBtnEls[categoryNavIndex];
        if (activeBtn) {
            activeBtn.scrollIntoView({ block: 'nearest', inline: 'center' });
        }
    }

    function applyCategoryByIndex(index) {
        const safeIndex = ((index % categories.length) + categories.length) % categories.length;
        categoryNavIndex = safeIndex;
        selectedCategory = categories[safeIndex];
        selectedIndex = 0;
        if (channelList) channelList.scrollTop = 0;
        applyCategoryFilter();
    }

    function updateActiveChannelItem() {
        if (!channelItemEls.length) return;
        channelItemEls.forEach((el, idx) => {
            el.classList.toggle('active', idx === selectedIndex);
        });
        const active = channelItemEls[selectedIndex];
        if (active) {
            active.scrollIntoView({ block: 'nearest' });
        }
    }

    function renderChannelList() {
        channelItemEls = [];
        if (!filteredChannels.length) {
            channelList.innerHTML = '<li class="channel-item"><div></div><div><div class="name">Nenhum canal nessa categoria</div></div></li>';
            return;
        }

        channelList.innerHTML = '';

        filteredChannels.forEach((channel, index) => {
            const li = document.createElement('li');
            li.className = 'channel-item' + (selectedIndex === index ? ' active' : '');
            li.dataset.index = String(index);

            const safeLogo = channel.logo || 'imagens/elitelogo.webp';
            const thumb = document.createElement('img');
            thumb.src = safeLogo;
            thumb.alt = channel.name;

            const infoWrap = document.createElement('div');
            const nameEl = document.createElement('div');
            nameEl.className = 'name';
            nameEl.textContent = channel.name;

            infoWrap.appendChild(nameEl);

            li.appendChild(thumb);
            li.appendChild(infoWrap);

            li.addEventListener('click', () => {
                selectedIndex = index;
                updateActiveChannelItem();
                playSelectedChannel();
                closeMenu(true);
            });

            channelList.appendChild(li);
            channelItemEls.push(li);
        });

        updateActiveChannelItem();
    }

    function updateActiveGameItem() {
        if (!gameItemEls.length) return;
        gameItemEls.forEach((el, idx) => {
            el.classList.toggle('active', idx === selectedGameIndex);
        });
        const active = gameItemEls[selectedGameIndex];
        if (active) {
            active.scrollIntoView({ block: 'nearest' });
        }
    }

    function renderGamesList() {
        gameItemEls = [];
        if (!gamesToday.length) {
            gamesList.innerHTML = '<li class="game-item"><div class="game-league">Nenhum jogo encontrado para hoje.</div></li>';
            return;
        }

        gamesList.innerHTML = '';

        gamesToday.forEach((game, index) => {
            const li = document.createElement('li');
            li.className = 'game-item' + (selectedGameIndex === index ? ' active' : '');
            li.dataset.index = String(index);

            const homeLogo = game.homeImg || game.image || 'imagens/elitelogo.webp';
            const awayLogo = game.awayImg || game.image || 'imagens/elitelogo.webp';

            const statusLabel = String(game.statusText || game.status || 'AGENDADO').toUpperCase();
            const minuteLabel = game.status === 'AO VIVO'
                ? String(game.statusText || '').toUpperCase().trim() || 'AO VIVO'
                : 'AGENDADO';
            const minuteClass = game.status === 'AO VIVO' ? 'game-badge minute' : 'game-badge minute idle';
            const homeScore = game.homeScore !== '' ? game.homeScore : '0';
            const awayScore = game.awayScore !== '' ? game.awayScore : '0';

            li.innerHTML = `
                <div class="game-head">
                    <div class="game-head-title">${game.league}</div>
                </div>
                <div class="game-body">
                    <div class="game-badges">
                        <span class="${minuteClass}">${minuteLabel}</span>
                        <span class="game-badge ${game.status === 'AO VIVO' ? 'live' : ''}">${statusLabel}</span>
                    </div>
                    <div class="game-teams-grid">
                        <div class="game-team-col">
                            <img src="${homeLogo}" alt="${game.homeName}">
                            <div class="game-team-name">${game.homeName}</div>
                        </div>
                        <div class="game-score-box">
                            <div class="game-score">${homeScore} - ${awayScore}</div>
                            <small>Placar</small>
                        </div>
                        <div class="game-team-col">
                            <img src="${awayLogo}" alt="${game.awayName}">
                            <div class="game-team-name">${game.awayName}</div>
                        </div>
                    </div>
                    <div class="game-foot">
                        <span class="date">${game.dateLabel}</span>
                        <span class="time">${game.hourLabel}</span>
                    </div>
                </div>
            `;

            li.addEventListener('click', () => {
                selectedGameIndex = index;
                updateActiveGameItem();
                playSelectedGame();
            });

            gamesList.appendChild(li);
            gameItemEls.push(li);
        });

        updateActiveGameItem();
    }

    function playSelectedGame() {
        const game = gamesToday[selectedGameIndex];
        if (!game) return;

        const matched = findChannelForGame(game);
        if (!matched) {
            showToast('Canal deste jogo nao encontrado');
            return;
        }

        playChannel(matched);
        closeGamesPanel(true);
    }

    function applyCategoryFilter() {
        filteredChannels = channels.filter(c => channelMatchesFixedCategory(c, selectedCategory));

        if (selectedIndex >= filteredChannels.length) {
            selectedIndex = Math.max(filteredChannels.length - 1, 0);
        }

        const idx = categories.indexOf(selectedCategory);
        if (idx >= 0) categoryNavIndex = idx;

        renderCategories();
        renderChannelList();
    }

    function destroyPlayer() {
        if (hls) {
            hls.destroy();
            hls = null;
        }

        iframePlayer.src = 'about:blank';
        iframePlayer.style.display = 'none';
        video.style.display = 'block';

        video.removeAttribute('src');
        video.load();
    }

    async function playChannel(channel, preferredUrl = '') {
        if (!channel) return;

        const stream = preferredUrl || channel.streamUrl;
        currentPlayingChannel = channel;
        currentPlayingUrl = stream;
        hudName.textContent = channel.name;
        showTopHudTemporarily();

        destroyPlayer();

        if (!isM3U8(stream)) {
            const iframeStream = build70PlayerUrl(stream, channel.name);
            video.style.display = 'none';
            iframePlayer.style.display = 'block';
            iframePlayer.src = iframeStream;
            try { window.focus(); } catch (e) {}
            return;
        }

        const finalStream = shouldUseProxy(stream)
            ? `proxy.php?url=${encodeURIComponent(stream)}`
            : stream;

        const canUseNative = video.canPlayType('application/vnd.apple.mpegurl');
        if (canUseNative) {
            video.src = finalStream;
            video.muted = false;
            video.play().catch(() => {
                video.muted = true;
                video.play().catch(() => null);
            });
            return;
        }

        if (window.Hls && Hls.isSupported()) {
            hls = new Hls({
                enableWorker: true,
                lowLatencyMode: true,
                maxBufferLength: 25,
            });
            hls.loadSource(finalStream);
            hls.attachMedia(video);

            hls.on(Hls.Events.MANIFEST_PARSED, () => {
                video.muted = false;
                video.play().catch(() => {
                    video.muted = true;
                    video.play().catch(() => null);
                });
            });

            hls.on(Hls.Events.ERROR, (_evt, data) => {
                if (data && data.fatal) {
                    showToast('Falha ao reproduzir este canal');
                }
            });
            return;
        }

        showToast('Seu dispositivo nao suporta este player');
    }

    function playSelectedChannel() {
        playChannel(filteredChannels[selectedIndex]);
    }

    function openMenu() {
        closeQualityPanel();
        closeGamesPanel(false);
        menuOpen = true;
        categoryOpen = true;
        menuFocus = 'channels';
        menuPanel.classList.add('open');
        menuPanel.setAttribute('aria-hidden', 'false');
        categoryPanel.classList.add('open');
        categoryPanel.setAttribute('aria-hidden', 'false');
        menuPanel.classList.add('with-category');
        if (topHud) topHud.classList.remove('hidden');
        updateCategoryNavFocus();
        updateActiveChannelItem();
    }

    function closeMenu(selectedChannel) {
        menuOpen = false;
        categoryOpen = false;
        menuFocus = 'channels';
        menuPanel.classList.remove('open');
        menuPanel.setAttribute('aria-hidden', 'true');
        categoryPanel.classList.remove('open');
        categoryPanel.setAttribute('aria-hidden', 'true');
        menuPanel.classList.remove('with-category');
        updateCategoryNavFocus();
        if (selectedChannel) {
            showTopHudTemporarily();
        }
    }

    function openCategoryPanel() {
        if (!menuOpen) return;
        menuFocus = 'categories';
        updateCategoryNavFocus();
    }

    function closeCategoryPanel(applyCurrent) {
        if (!menuOpen) return;
        menuFocus = 'channels';
        if (applyCurrent) {
            applyCategoryByIndex(categoryNavIndex);
        }
        updateCategoryNavFocus();
    }

    function openGamesPanel() {
        closeQualityPanel();
        closeMenu(false);
        gamesOpen = true;
        selectedGameIndex = 0;
        gamesPanel.classList.add('open');
        gamesPanel.setAttribute('aria-hidden', 'false');
        if (topHud) topHud.classList.remove('hidden');
        gamesList.innerHTML = '<li class="game-item"><div class="game-league">Carregando jogos...</div></li>';
        gamesList.scrollTop = 0;
        fetchGamesToday();
        updateActiveGameItem();
    }

    function closeGamesPanel(selectedGame) {
        gamesOpen = false;
        gamesPanel.classList.remove('open');
        gamesPanel.setAttribute('aria-hidden', 'true');
        if (selectedGame) {
            showTopHudTemporarily();
        }
    }

    function setCategoryByStep(step) {
        if (!categories.length) return;
        const next = (categoryNavIndex + step + categories.length) % categories.length;
        applyCategoryByIndex(next);
    }

    function handleRemoteNavigation(event) {
        const key = event.key;
        const keyCode = event.keyCode || 0;

        const isBack = key === 'Backspace' || key === 'Escape' || key === 'BrowserBack' || key === 'GoBack' || keyCode === 8 || keyCode === 27 || keyCode === 166 || keyCode === 10009 || keyCode === 461;
        const isArrow = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(key);
        const isEnter = key === 'Enter' || keyCode === 13;

        if (isArrow || isEnter || isBack) {
            event.preventDefault();
        }

        if (!smartTvAuthorized) {
            if (isEnter || key === 'ArrowLeft') {
                showToast('Autorize pelo celular para liberar o player');
            }
            return;
        }

        if (qualityOpen) {
            if (!qualityOptions.length) {
                if (isBack || isEnter) {
                    closeQualityPanel();
                }
                return;
            }

            if (key === 'ArrowUp' || key === 'ArrowLeft') {
                qualityIndex = Math.max(0, qualityIndex - 1);
                renderQualityPanel();
                return;
            }
            if (key === 'ArrowDown' || key === 'ArrowRight') {
                qualityIndex = Math.min(qualityOptions.length - 1, qualityIndex + 1);
                renderQualityPanel();
                return;
            }
            if (isEnter) {
                applyQualitySelection();
                return;
            }
            if (isBack) {
                closeQualityPanel();
                return;
            }
        }

        if (isBack) {
            event.stopPropagation();
            if (gamesOpen) {
                closeGamesPanel(false);
                return;
            }
            if (menuOpen) {
                closeMenu(false);
                return;
            }
            showToast('Pressione ArrowLeft para abrir canais');
            return;
        }

        if (isEnter && !event.repeat) {
            const now = Date.now();
            if (now - lastOkAt <= 350) {
                requestFullscreenMode();
                showToast('Tela cheia ativada');
                lastOkAt = 0;
                return;
            }
            lastOkAt = now;
        }

        if (key === 'ArrowLeft') {
            if (!menuOpen && !gamesOpen) {
                openMenu();
            } else if (menuOpen && menuFocus === 'channels') {
                openCategoryPanel();
            } else if (menuOpen && menuFocus === 'categories') {
                openGamesPanel();
            }
            return;
        }

        if (key === 'ArrowRight') {
            if (gamesOpen) {
                openMenu();
                openCategoryPanel();
                return;
            }

            if (menuOpen && menuFocus === 'categories') {
                closeCategoryPanel(true);
                return;
            }

            if (menuOpen && menuFocus === 'channels') {
                closeMenu(false);
                return;
            }

            return;
        }

        if (!menuOpen && !gamesOpen) {
            if (key === 'ArrowDown') {
                openQualityPanel();
            }
            return;
        }

        if (key === 'ArrowUp') {
            if (menuOpen && menuFocus === 'categories') {
                setCategoryByStep(-1);
                updateCategoryNavFocus();
            } else if (menuOpen) {
                selectedIndex = Math.max(0, selectedIndex - 1);
                updateActiveChannelItem();
            } else if (gamesOpen) {
                selectedGameIndex = Math.max(0, selectedGameIndex - 1);
                updateActiveGameItem();
            }
            return;
        }

        if (key === 'ArrowDown') {
            if (menuOpen && menuFocus === 'categories') {
                setCategoryByStep(1);
                updateCategoryNavFocus();
            } else if (menuOpen) {
                selectedIndex = Math.min(filteredChannels.length - 1, selectedIndex + 1);
                updateActiveChannelItem();
            } else if (gamesOpen) {
                selectedGameIndex = Math.min(gamesToday.length - 1, selectedGameIndex + 1);
                updateActiveGameItem();
            }
            return;
        }

        if (isEnter) {
            if (menuOpen && menuFocus === 'categories') {
                closeCategoryPanel(true);
            } else if (menuOpen) {
                playSelectedChannel();
                closeMenu(true);
            } else if (gamesOpen) {
                playSelectedGame();
            }
            return;
        }
    }

    async function fetchGamesToday() {
        try {
            const response = await fetch(`proxy_embedtv.php?resource=jogos&_t=${Date.now()}`, { cache: 'no-store' });
            const payload = await response.json();
            gamesToday = mapGames(payload);
            if (selectedGameIndex >= gamesToday.length) {
                selectedGameIndex = Math.max(0, gamesToday.length - 1);
            }
            renderGamesList();

            // Atualiza placar em segundo plano para não travar a abertura do menu
            fetchLiveScores().then((liveScores) => {
                applyLiveScoresToGames(liveScores);
                renderGamesList();
            }).catch(() => null);
        } catch (error) {
            gamesToday = [];
            renderGamesList();
        }
    }

    function startGamesAutoRefresh() {
        if (gamesRefreshTimer) {
            clearInterval(gamesRefreshTimer);
            gamesRefreshTimer = null;
        }
        gamesRefreshTimer = setInterval(() => {
            fetchGamesToday();
        }, 20000);
    }

    async function loadChannelsExperience() {
        try {
            const [embedPayload, source70Payload] = await Promise.all([
                fetch(`${CHANNELS_URL}&_t=${Date.now()}`, { cache: 'no-store' }).then(r => r.json()).catch(() => null),
                fetch(`${SOURCE70_URL}?_t=${Date.now()}`, { cache: 'no-store' }).then(r => r.json()).catch(() => null),
            ]);

            if (!embedPayload) {
                throw new Error('Sem payload de canais EmbedTV');
            }

            hidden70SourceData = source70Payload;
            channels = merge70StreamsIntoEmbed(mapChannels(embedPayload), hidden70SourceData);
            categories = [...FIXED_CATEGORIES];
            if (!categories.includes(selectedCategory)) {
                selectedCategory = categories[0] || 'TODOS';
            }

            applyCategoryFilter();

            if (filteredChannels.length > 0) {
                playSelectedChannel();
            } else {
                hudName.textContent = 'Nenhum canal disponivel';
            }

            await fetchGamesToday();
            startGamesAutoRefresh();

            openMenu();
        } catch (error) {
            hudName.textContent = 'Erro ao carregar canais';
        }
    }

    async function validateStoredSmartTvToken() {
        const token = localStorage.getItem(SMARTTV_TOKEN_KEY) || '';
        if (!token) {
            return false;
        }

        const res = await fetch(`${SMARTTV_PAIR_API}?action=validate&auth_token=${encodeURIComponent(token)}&_t=${Date.now()}`, {
            cache: 'no-store'
        }).then(r => r.json()).catch(() => null);

        if (res?.authorized) {
            return true;
        }

        localStorage.removeItem(SMARTTV_TOKEN_KEY);
        return false;
    }

    async function checkPairStatus() {
        if (!pairId) return;

        const res = await fetch(`${SMARTTV_PAIR_API}?action=status&pair_id=${encodeURIComponent(pairId)}&_t=${Date.now()}`, {
            cache: 'no-store'
        }).then(r => r.json()).catch(() => null);

        if (!res) {
            setPairStatus('Erro ao verificar autorização. Tentando novamente...');
            return;
        }

        if (res.expired) {
            stopPairPolling();
            setPairStatus('A solicitação expirou. Clique em "Tentar novamente".');
            return;
        }

        if (res.authorized && res.auth_token) {
            stopPairPolling();
            localStorage.setItem(SMARTTV_TOKEN_KEY, res.auth_token);
            smartTvAuthorized = true;
            hideAuthOverlay();
            showToast('Smart TV autorizada!');
            loadChannelsExperience();
        }
    }

    async function startPairingFlow() {
        stopPairPolling();
        pairId = '';
        showAuthOverlay();
        setPairStatus('Aguardando autorização...');
        if (authCode) authCode.textContent = 'Gerando pareamento...';
        if (authQr) authQr.removeAttribute('src');

        const created = await fetch(`${SMARTTV_PAIR_API}?action=create`, {
            method: 'POST',
            cache: 'no-store'
        }).then(r => r.json()).catch(() => null);

        if (!created?.ok || !created?.pair_id) {
            setPairStatus(created?.error || 'Falha ao iniciar autorização.');
            return;
        }

        pairId = created.pair_id;
        if (authCode) {
            authCode.textContent = created.pair_code ? `Codigo: ${created.pair_code}` : 'Escaneie para autorizar';
        }
        if (authQr && created.qr_image) {
            authQr.src = created.qr_image;
        }
        setPairStatus('Aguardando autorização pelo celular...');
        pairPollTimer = setInterval(checkPairStatus, 2500);
        checkPairStatus();
    }

    async function initSmartTvPage() {
        const alreadyAuthorized = await validateStoredSmartTvToken();
        if (alreadyAuthorized) {
            smartTvAuthorized = true;
            hideAuthOverlay();
            loadChannelsExperience();
            return;
        }

        smartTvAuthorized = false;
        await startPairingFlow();
    }

    function lockBackNavigation() {
        try {
            history.replaceState({ eliteplaySmartTv: true }, '', location.href);
            history.pushState({ eliteplaySmartTvGuard: true }, '', location.href);
        } catch (e) {
            // ignora em ambientes sem history API completa
        }
    }

    function handlePopStateNavigation() {
        if (gamesOpen) {
            closeGamesPanel(false);
            lockBackNavigation();
            return;
        }
        if (menuOpen) {
            closeMenu(false);
            lockBackNavigation();
            return;
        }
        lockBackNavigation();
    }

    document.addEventListener('keydown', handleRemoteNavigation, true);
    window.addEventListener('popstate', handlePopStateNavigation);
    lockBackNavigation();
    if (retryPairBtn) {
        retryPairBtn.addEventListener('click', () => {
            startPairingFlow();
        });
    }
    initSmartTvPage();
</script>
</body>
</html>
