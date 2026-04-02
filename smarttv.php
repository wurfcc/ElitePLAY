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
            width: min(42vw, 540px);
            z-index: 30;
            transform: translate3d(-102%, 0, 0);
            transition: transform 0.16s ease-out;
            background: var(--panel-strong);
            border-right: 1px solid var(--line);
            display: grid;
            grid-template-rows: auto 1fr auto;
            will-change: transform;
            contain: layout paint;
        }

        .menu-panel.with-category {
            transform: translate3d(190px, 0, 0);
        }

        .category-panel {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 182px;
            z-index: 31;
            transform: translate3d(-102%, 0, 0);
            transition: transform 0.16s ease-out;
            background: rgba(3, 12, 35, 0.98);
            border-right: 1px solid var(--line);
            display: grid;
            grid-template-rows: auto 1fr auto;
            will-change: transform;
            contain: layout paint;
        }

        .category-panel.open {
            transform: translate3d(0, 0, 0);
        }

        .games-panel {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: min(42vw, 540px);
            z-index: 30;
            transform: translate3d(102%, 0, 0);
            transition: transform 0.16s ease-out;
            background: var(--panel-strong);
            border-left: 1px solid var(--line);
            display: grid;
            grid-template-rows: auto 1fr auto;
            will-change: transform;
            contain: layout paint;
        }

        .games-panel.open { transform: translate3d(0, 0, 0); }

        .menu-panel.open { transform: translate3d(0, 0, 0); }

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
            font-size: 13px;
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

        .menu-foot {
            border-top: 1px solid var(--line);
            padding: 12px 16px;
            color: #cbd5e1;
            font-size: 12px;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .games-list {
            margin: 0;
            padding: 10px;
            list-style: none;
            overflow-y: auto;
            display: grid;
            gap: 8px;
            contain: content;
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
            transition: background-color 0.14s linear, border-color 0.14s linear, box-shadow 0.14s linear;
        }

        .game-item.active {
            border-color: var(--focus);
            background: linear-gradient(135deg, rgba(12, 74, 110, 0.5), rgba(7, 17, 38, 0.9));
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.35);
        }

        .game-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .game-time {
            font-size: 15px;
            font-weight: 700;
            color: #e2e8f0;
        }

        .game-status {
            font-size: 11px;
            text-transform: uppercase;
            color: #7dd3fc;
            border: 1px solid rgba(125, 211, 252, 0.35);
            border-radius: 999px;
            padding: 3px 8px;
        }

        .game-league {
            font-size: 12px;
            color: #93c5fd;
            line-height: 1.2;
        }

        .game-teams {
            display: grid;
            gap: 8px;
        }

        .game-team {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .game-team .team-name {
            font-size: 14px;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .game-team .team-score {
            margin-left: auto;
            font-size: 14px;
            font-weight: 700;
            color: #f8fafc;
            min-width: 14px;
            text-align: right;
        }

        .game-team img {
            width: 28px;
            height: 28px;
            object-fit: contain;
            pointer-events: none;
            flex-shrink: 0;
        }

        .hint-pill {
            padding: 4px 8px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(30, 41, 59, 0.7);
        }

        .auth-overlay {
            position: absolute;
            inset: 0;
            z-index: 45;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(2, 6, 23, 0.78);
            backdrop-filter: blur(3px);
        }

        .auth-overlay.show {
            display: flex;
        }

        .auth-card {
            width: min(760px, 90vw);
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
    </style>
</head>
<body>
<div class="screen">
    <video id="tv-player" autoplay playsinline muted></video>

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
        <div class="menu-foot">
            <span class="hint-pill">LEFT (2x): categorias</span>
            <span class="hint-pill">UP/DOWN: navegar</span>
            <span class="hint-pill">ENTER: reproduzir</span>
            <span class="hint-pill">RIGHT: jogos hoje</span>
        </div>
    </aside>

    <aside class="category-panel" id="category-panel" aria-hidden="true">
        <div class="menu-head">
            <h2>Categorias</h2>
            <p>Setas para escolher</p>
        </div>
        <ul class="category-list" id="category-list"></ul>
        <div class="menu-foot">
            <span class="hint-pill">UP/DOWN: navegar</span>
            <span class="hint-pill">ENTER: filtrar</span>
            <span class="hint-pill">RIGHT/BACK: fechar</span>
        </div>
    </aside>

    <aside class="games-panel" id="games-panel" aria-hidden="true">
        <div class="menu-head">
            <h2>Jogos de hoje</h2>
            <p>Setas para navegar, Enter para assistir</p>
        </div>
        <ul class="games-list" id="games-list"></ul>
        <div class="menu-foot">
            <span class="hint-pill">RIGHT: abrir jogos</span>
            <span class="hint-pill">UP/DOWN: navegar</span>
            <span class="hint-pill">ENTER: abrir jogo</span>
            <span class="hint-pill">LEFT/BACK: fechar</span>
        </div>
    </aside>

    <div class="auth-overlay" id="auth-overlay">
        <div class="auth-card">
            <h2>Autorizar aplicativo pelo celular</h2>
            <p>Faça login no seu celular e clique no icone de cadeado no topo do header.</p>
            <p class="pair-status" id="pair-status">Aguardando autorização...</p>
            <div class="auth-actions">
                <button class="auth-btn" type="button" id="retry-pair-btn">Tentar novamente</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>
</div>

<script>
    const CHANNELS_URL = 'proxy_embedtv.php?resource=channels';
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
    const toast = document.getElementById('toast');
    const authOverlay = document.getElementById('auth-overlay');
    const pairStatus = document.getElementById('pair-status');
    const retryPairBtn = document.getElementById('retry-pair-btn');

    let hls = null;
    let channels = [];
    let filteredChannels = [];
    let gamesToday = [];
    let categories = [];
    let selectedCategory = 'ALL';
    let selectedIndex = 0;
    let selectedGameIndex = 0;
    let categoryNavIndex = 0;
    let menuOpen = false;
    let categoryOpen = false;
    let gamesOpen = false;
    let lastOkAt = 0;
    let smartTvAuthorized = false;
    let pairId = '';
    let pairPollTimer = null;
    let hudHideTimer = null;
    let gamesRefreshTimer = null;
    let channelItemEls = [];
    let gameItemEls = [];
    let categoryBtnEls = [];

    const FIXED_CATEGORIES = ['TELECINE', 'PREMIERE', 'SPORTV', 'ESPN', 'ESPORTES', 'HBO', 'BBB', 'ABERTOS'];

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

    function normalizeText(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
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
            btn.classList.toggle('nav-focus', idx === categoryNavIndex && menuOpen);
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

            li.innerHTML = `
                <div class="game-top">
                    <span class="game-time">${game.hourLabel}</span>
                    <span class="game-status">${game.statusText || game.status}</span>
                </div>
                <div class="game-league">${game.league}</div>
                <div class="game-teams">
                    <div class="game-team"><img src="${homeLogo}" alt="${game.homeName}"><span class="team-name">${game.homeName}</span><span class="team-score">${game.homeScore || ''}</span></div>
                    <div class="game-team"><img src="${awayLogo}" alt="${game.awayName}"><span class="team-name">${game.awayName}</span><span class="team-score">${game.awayScore || ''}</span></div>
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
        video.removeAttribute('src');
        video.load();
    }

    async function playChannel(channel) {
        if (!channel) return;

        const stream = channel.streamUrl;
        hudName.textContent = channel.name;
        showTopHudTemporarily();

        destroyPlayer();

        const canUseNative = video.canPlayType('application/vnd.apple.mpegurl');
        if (canUseNative) {
            video.src = stream;
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
            hls.loadSource(stream);
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
        closeGamesPanel(false);
        menuOpen = true;
        menuPanel.classList.add('open');
        menuPanel.setAttribute('aria-hidden', 'false');
        if (topHud) topHud.classList.remove('hidden');
        updateCategoryNavFocus();
        updateActiveChannelItem();
    }

    function closeMenu(selectedChannel) {
        closeCategoryPanel(false);
        menuOpen = false;
        menuPanel.classList.remove('open');
        menuPanel.setAttribute('aria-hidden', 'true');
        updateCategoryNavFocus();
        if (selectedChannel) {
            showTopHudTemporarily();
        }
    }

    function openCategoryPanel() {
        if (!menuOpen) return;
        categoryOpen = true;
        categoryPanel.classList.add('open');
        categoryPanel.setAttribute('aria-hidden', 'false');
        menuPanel.classList.add('with-category');
        updateCategoryNavFocus();
    }

    function closeCategoryPanel(applyCurrent) {
        if (!categoryOpen) return;
        categoryOpen = false;
        categoryPanel.classList.remove('open');
        categoryPanel.setAttribute('aria-hidden', 'true');
        menuPanel.classList.remove('with-category');
        if (applyCurrent) {
            applyCategoryByIndex(categoryNavIndex);
        }
        updateCategoryNavFocus();
    }

    function openGamesPanel() {
        closeMenu(false);
        gamesOpen = true;
        gamesPanel.classList.add('open');
        gamesPanel.setAttribute('aria-hidden', 'false');
        if (topHud) topHud.classList.remove('hidden');
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

        if (isBack) {
            event.stopPropagation();
            if (gamesOpen) {
                closeGamesPanel(false);
                return;
            }
            if (categoryOpen) {
                closeCategoryPanel(false);
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
            if (gamesOpen) {
                closeGamesPanel(false);
                openMenu();
            } else if (!menuOpen) {
                openMenu();
            } else if (!categoryOpen) {
                openCategoryPanel();
            } else {
                setCategoryByStep(-1);
                updateCategoryNavFocus();
            }
            return;
        }

        if (key === 'ArrowRight') {
            if (categoryOpen) {
                closeCategoryPanel(true);
                return;
            }
            if (menuOpen) {
                closeMenu(false);
            }
            openGamesPanel();
            return;
        }

        if (!menuOpen && !gamesOpen) {
            if (isBack) {
                showToast('Pressione ArrowLeft para abrir canais');
            }
            return;
        }

        if (key === 'ArrowUp') {
            if (categoryOpen) {
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
            if (categoryOpen) {
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
            if (categoryOpen) {
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
            const liveScores = await fetchLiveScores();
            applyLiveScoresToGames(liveScores);
            if (selectedGameIndex >= gamesToday.length) {
                selectedGameIndex = Math.max(0, gamesToday.length - 1);
            }
            renderGamesList();
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
            const response = await fetch(`${CHANNELS_URL}&_t=${Date.now()}`, { cache: 'no-store' });
            const payload = await response.json();

            channels = mapChannels(payload);
            categories = [...FIXED_CATEGORIES];
            if (!categories.includes(selectedCategory)) {
                selectedCategory = categories[0] || 'TELECINE';
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

        const created = await fetch(`${SMARTTV_PAIR_API}?action=create`, {
            method: 'POST',
            cache: 'no-store'
        }).then(r => r.json()).catch(() => null);

        if (!created?.ok || !created?.pair_id) {
            setPairStatus(created?.error || 'Falha ao iniciar autorização.');
            return;
        }

        pairId = created.pair_id;
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
        if (categoryOpen) {
            closeCategoryPanel(false);
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
