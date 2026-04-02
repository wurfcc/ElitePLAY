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
        }

        .brand {
            display: grid;
            gap: 4px;
        }

        .brand strong {
            font-size: 22px;
            letter-spacing: 0.4px;
        }

        .brand span {
            color: var(--muted);
            font-size: 12px;
            letter-spacing: 0.6px;
            text-transform: uppercase;
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

        .channel-hud p {
            margin: 4px 0 0;
            color: #cbd5e1;
            font-size: 13px;
            letter-spacing: 0.4px;
        }

        .menu-panel {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: min(42vw, 540px);
            z-index: 30;
            transform: translateX(-102%);
            transition: transform 0.28s ease;
            background: var(--panel-strong);
            border-right: 1px solid var(--line);
            backdrop-filter: blur(8px);
            display: grid;
            grid-template-rows: auto auto 1fr auto;
        }

        .menu-panel.open { transform: translateX(0); }

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

        .category-row {
            display: flex;
            gap: 8px;
            padding: 12px 14px;
            overflow-x: auto;
            border-bottom: 1px solid var(--line);
        }

        .category-row::-webkit-scrollbar { height: 4px; }
        .category-row::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.35); border-radius: 999px; }

        .cat-btn {
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(15, 23, 42, 0.75);
            color: var(--text);
            border-radius: 999px;
            padding: 7px 12px;
            white-space: nowrap;
            font-size: 12px;
            font-weight: 600;
        }

        .cat-btn.active {
            border-color: var(--focus);
            background: var(--focus-soft);
            color: #e0f2fe;
        }

        .channel-list {
            margin: 0;
            padding: 10px;
            list-style: none;
            overflow-y: auto;
            display: grid;
            gap: 8px;
        }

        .channel-list::-webkit-scrollbar { width: 6px; }
        .channel-list::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.35); border-radius: 999px; }

        .channel-item {
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            background: rgba(8, 15, 35, 0.82);
            padding: 10px;
            display: grid;
            grid-template-columns: 72px 1fr;
            gap: 10px;
            align-items: center;
        }

        .channel-item img {
            width: 72px;
            height: 48px;
            border-radius: 8px;
            object-fit: cover;
            background: #0f172a;
            border: 1px solid rgba(148, 163, 184, 0.3);
            pointer-events: none;
        }

        .channel-item .name {
            font-size: 15px;
            font-weight: 700;
            line-height: 1.2;
        }

        .channel-item .meta {
            margin-top: 4px;
            color: var(--muted);
            font-size: 11px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
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
            <strong>ElitePLAY Smart TV</strong>
            <span>Use ArrowLeft para abrir canais</span>
        </div>
        <div class="channel-hud">
            <h1 id="hud-channel-name">Carregando canais...</h1>
            <p id="hud-channel-meta">Aguardando reproducao</p>
        </div>
    </div>

    <aside class="menu-panel" id="menu-panel" aria-hidden="true">
        <div class="menu-head">
            <h2>Canais ao vivo</h2>
            <p>Setas para navegar, Enter para assistir</p>
        </div>
        <div class="category-row" id="category-row"></div>
        <ul class="channel-list" id="channel-list"></ul>
        <div class="menu-foot">
            <span class="hint-pill">LEFT: abrir menu</span>
            <span class="hint-pill">UP/DOWN: navegar</span>
            <span class="hint-pill">ENTER: reproduzir</span>
            <span class="hint-pill">RIGHT/BACK: fechar</span>
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
    const SMARTTV_PAIR_API = 'smarttv_pair_api.php';
    const SMARTTV_TOKEN_KEY = 'eliteplay_smarttv_token';
    const menuPanel = document.getElementById('menu-panel');
    const categoryRow = document.getElementById('category-row');
    const channelList = document.getElementById('channel-list');
    const hudName = document.getElementById('hud-channel-name');
    const hudMeta = document.getElementById('hud-channel-meta');
    const video = document.getElementById('tv-player');
    const toast = document.getElementById('toast');
    const authOverlay = document.getElementById('auth-overlay');
    const pairStatus = document.getElementById('pair-status');
    const retryPairBtn = document.getElementById('retry-pair-btn');

    let hls = null;
    let channels = [];
    let filteredChannels = [];
    let categories = [];
    let selectedCategory = 'ALL';
    let selectedIndex = 0;
    let menuOpen = false;
    let lastOkAt = 0;
    let smartTvAuthorized = false;
    let pairId = '';
    let pairPollTimer = null;

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

    function renderCategories() {
        categoryRow.innerHTML = '';
        const items = ['ALL', ...categories];

        items.forEach(cat => {
            const btn = document.createElement('button');
            btn.className = 'cat-btn' + (selectedCategory === cat ? ' active' : '');
            btn.textContent = cat === 'ALL' ? 'TODOS' : cat;
            btn.addEventListener('click', () => {
                selectedCategory = cat;
                selectedIndex = 0;
                applyCategoryFilter();
            });
            categoryRow.appendChild(btn);
        });
    }

    function renderChannelList() {
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

            const metaEl = document.createElement('div');
            metaEl.className = 'meta';
            metaEl.textContent = channel.category;

            infoWrap.appendChild(nameEl);
            infoWrap.appendChild(metaEl);

            li.appendChild(thumb);
            li.appendChild(infoWrap);

            li.addEventListener('click', () => {
                selectedIndex = index;
                renderChannelList();
                playSelectedChannel();
                closeMenu();
            });

            channelList.appendChild(li);
        });

        const active = channelList.querySelector('.channel-item.active');
        if (active) {
            active.scrollIntoView({ block: 'nearest' });
        }
    }

    function applyCategoryFilter() {
        if (selectedCategory === 'ALL') {
            filteredChannels = [...channels];
        } else {
            filteredChannels = channels.filter(c => c.category === selectedCategory);
        }

        if (selectedIndex >= filteredChannels.length) {
            selectedIndex = Math.max(filteredChannels.length - 1, 0);
        }

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
        hudMeta.textContent = `Categoria: ${channel.category}`;

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
        menuOpen = true;
        menuPanel.classList.add('open');
        menuPanel.setAttribute('aria-hidden', 'false');
        renderChannelList();
    }

    function closeMenu() {
        menuOpen = false;
        menuPanel.classList.remove('open');
        menuPanel.setAttribute('aria-hidden', 'true');
    }

    function setCategoryByStep(step) {
        const all = ['ALL', ...categories];
        const current = all.indexOf(selectedCategory);
        const next = (current + step + all.length) % all.length;
        selectedCategory = all[next];
        selectedIndex = 0;
        applyCategoryFilter();
        showToast(selectedCategory === 'ALL' ? 'Todos os canais' : selectedCategory);
    }

    function handleRemoteNavigation(event) {
        const key = event.key;
        const keyCode = event.keyCode || 0;

        const isBack = key === 'Backspace' || key === 'Escape' || keyCode === 10009 || keyCode === 461;
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
            if (!menuOpen) {
                openMenu();
            } else {
                setCategoryByStep(-1);
            }
            return;
        }

        if (key === 'ArrowRight') {
            if (menuOpen) {
                playSelectedChannel();
                closeMenu();
            }
            return;
        }

        if (!menuOpen) {
            if (isBack) {
                showToast('Pressione ArrowLeft para abrir canais');
            }
            return;
        }

        if (key === 'ArrowUp') {
            selectedIndex = Math.max(0, selectedIndex - 1);
            renderChannelList();
            return;
        }

        if (key === 'ArrowDown') {
            selectedIndex = Math.min(filteredChannels.length - 1, selectedIndex + 1);
            renderChannelList();
            return;
        }

        if (isEnter) {
            playSelectedChannel();
            closeMenu();
            return;
        }

        if (isBack) {
            closeMenu();
        }
    }

    async function loadChannelsExperience() {
        try {
            const response = await fetch(`${CHANNELS_URL}&_t=${Date.now()}`, { cache: 'no-store' });
            const payload = await response.json();

            channels = mapChannels(payload);
            categories = [...new Set(channels.map(c => c.category))]
                .filter(Boolean)
                .sort((a, b) => a.localeCompare(b, 'pt-BR'));

            applyCategoryFilter();

            if (filteredChannels.length > 0) {
                playSelectedChannel();
            } else {
                hudName.textContent = 'Nenhum canal disponivel';
                hudMeta.textContent = 'Verifique a API de canais';
            }

            openMenu();
        } catch (error) {
            hudName.textContent = 'Erro ao carregar canais';
            hudMeta.textContent = 'Tente novamente em alguns segundos';
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

    document.addEventListener('keydown', handleRemoteNavigation);
    if (retryPairBtn) {
        retryPairBtn.addEventListener('click', () => {
            startPairingFlow();
        });
    }
    initSmartTvPage();
</script>
</body>
</html>
