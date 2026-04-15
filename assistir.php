<?php
require_once __DIR__ . '/middleware.php';
// Resgata os parâmetros passados via POST
$iframe_url = isset($_POST['iframe_url']) ? $_POST['iframe_url'] : '';
$iframe_url_2 = isset($_POST['iframe_url_2']) ? $_POST['iframe_url_2'] : '';
$iframe_url_3 = isset($_POST['iframe_url_3']) ? $_POST['iframe_url_3'] : '';
$title = isset($_POST['title']) ? $_POST['title'] : 'Assistir Canal';
$logo = isset($_POST['logo']) ? $_POST['logo'] : '';
$is_game_context = isset($_POST['is_game_context']) && $_POST['is_game_context'] === '1';
$current_game_id = isset($_POST['current_game_id']) ? trim((string)$_POST['current_game_id']) : '';

$streams_json = isset($_POST['streams_json']) ? $_POST['streams_json'] : '';
$dynamic_streams = [];
if (!empty($streams_json)) {
    // Corrige problema comum onde '+' vira ' ' no POST
    $streams_json = str_replace(' ', '+', $streams_json);
    $decoded = base64_decode($streams_json);
    if ($decoded !== false) {
        $parsed = json_decode($decoded, true);
        if (is_array($parsed)) {
            $dynamic_streams = array_filter($parsed, function($s) {
                return !isset($s['name']) || stripos($s['name'], '[H265]') === false;
            });
        }
    }
}

// Tratamento de erro elegante se acessar direto
if (empty($iframe_url)) {
    die("
    <div style='background-color:#05070A; min-height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center; color:white; font-family:sans-serif;'>
        <h2 style='margin-bottom: 20px;'>Link não fornecido ou sessão expirada.</h2>
        <a href='index.php' style='background:#4f5bf5; color:white; text-decoration:none; padding:10px 20px; border-radius:8px; font-weight:bold;'>Voltar ao Início</a>
    </div>
    ");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($title); ?> - ElitePLAY</title>
    <link rel="icon" type="image/webp" href="assets/favicon.webp">
    <link rel="stylesheet" href="assets/fonts/outfit/outfit.css">
    <!-- JWPlayer Script -->
    <script src="https://ssl.p.jwpcdn.com/player/v/8.6.3/jwplayer.js"></script>
    <script>jwplayer.key="64HPbvSQorQcd52B8XFuhMtEoitbvY/EXJmMBfKcXZQU2Rnn";</script>
    <style>
        :root {
            --bg-dark: #05070A;
            --bg-header: rgba(11, 13, 20, 0.85);
            --bg-card: #161a2b;
            --primary-blue: #4f5bf5;
            --primary-glow: rgba(79, 91, 245, 0.3);
            --text-light: #ffffff;
            --text-muted: #8b95a5;
            --card-premium-bg: rgba(255, 255, 255, 0.03);
            --card-premium-border: rgba(255, 255, 255, 0.08);
            --glass-blur: 16px;
            --pad-x: 15px;
        }

        @media (min-width: 768px) {
            :root { --pad-x: 40px; }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        body.game-context {
            overflow-x: hidden;
        }

        /* Barra de Topo do Player */
        header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            padding: 14px !important;
            background: #0E1019;
            -webkit-backdrop-filter: blur(14px);
            position: sticky;
            top: 0;
            transform: translatey(-1px);
            z-index: 200;
            border: 1px solid rgba(255, 255, 255, 0.08);
            gap: 14px;
            border-radius: 0 0 16px 16px;
            margin: 0px 8px 0px 8px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
            width: 100%;
        }

        .btn-back {
            background-color: var(--bg-card);
            color: var(--text-light);
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            border: 1px solid rgba(255, 255, 255, 0.05);
            flex-shrink: 0;
        }

        .btn-back:active {
            transform: scale(0.95);
        }

        .channel-info {
            display: flex;
            align-items: center;
            gap: 12px;
            overflow: hidden;
        }

        .channel-info img {
            height: 38px;
            width: 38px;
            border-radius: 8px;
            background: #fff;
            padding: 2px;
            object-fit: contain;
            flex-shrink: 0;
        }

        h1 {
            font-size: 16px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .live-badge {
            background: #ff0000;
            color: white;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            animation: pulse 2s infinite;
            flex-shrink: 0;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }

        /* Container do Vídeo */
        .player-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px var(--pad-x);
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .player-wrapper.game-context {
            display: grid;
            grid-template-columns: minmax(0, 1.65fr) minmax(320px, 430px);
            align-items: start;
            gap: 18px;
            max-width: 90%;
        }

        .player-stage {
            width: 100%;
        }

        .player-container {
            width: 100%;
            aspect-ratio: 16 / 9;
            background-color: #000;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Opções de Player */
        .player-options {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 20px;
            width: 100%;
        }

        .opt-btn {
            background-color: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.05);
            color: #b4becd;
            padding: 12px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.2s;
        }

        .games-rail {
            width: 100%;
            border: 1px solid rgba(255,255,255,0.06);
            background: #0e1019;
            border-radius: 16px;
            padding: 14px;
            max-height: calc(100vh - 110px);
            overflow: auto;
            box-shadow: 0 10px 26px rgba(0,0,0,0.28);
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.14) transparent;
        }

        .games-rail::-webkit-scrollbar { width: 4px; }
        .games-rail::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

        .games-rail-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .games-rail-header h2 {
            font-size: 18px;
            font-weight: 800;
        }

        .games-rail-status {
            color: var(--text-muted);
            font-size: 12px;
        }

        .games-sections {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .games-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .games-filter-btn {
            border: 1px solid rgba(255,255,255,0.12);
            background: rgba(255,255,255,0.03);
            color: var(--text-muted);
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s ease;
        }

        .games-filter-btn.active {
            color: #ffffff;
            border-color: rgba(79, 91, 245, 0.62);
            background: linear-gradient(135deg, rgba(79, 91, 245, 0.24), rgba(37, 99, 235, 0.24));
            box-shadow: 0 0 0 1px rgba(79, 91, 245, 0.22) inset;
        }

        .game-section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .game-section-title h3 {
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.3px;
        }

        .game-section-count {
            min-width: 24px;
            height: 24px;
            padding: 0 8px;
            border-radius: 999px;
            background: rgba(79, 91, 245, 0.16);
            color: #bfc9ff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }

        .games-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .game-card {
            background: var(--card-premium-bg);
            border: 1px solid var(--card-premium-border);
            border-radius: 16px;
            backdrop-filter: blur(var(--glass-blur));
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            cursor: default;
        }

        .game-card.soon-start {
            border-color: rgba(59, 130, 246, 0.9);
            box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.36), 0 0 16px rgba(59, 130, 246, 0.22);
            animation: soon-border-pulse 1.6s ease-in-out infinite;
        }

        @keyframes soon-border-pulse {
            0%, 100% {
                border-color: rgba(59, 130, 246, 0.75);
                box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.25), 0 0 14px rgba(59, 130, 246, 0.16);
            }
            50% {
                border-color: rgba(96, 165, 250, 1);
                box-shadow: 0 0 0 1px rgba(96, 165, 250, 0.55), 0 0 26px rgba(59, 130, 246, 0.34);
            }
        }

        .game-card.current-game {
            border-color: var(--card-premium-border);
            box-shadow: none;
        }

        .card-banner {
            position: relative;
            height: 40px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: right center;
            background-color: #0c121e;
            border-radius: 12px 12px 0 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding-left: 1rem;
        }

        .banner-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, #0c121e 40%, transparent 100%);
            pointer-events: none;
        }

        .banner-title {
            position: relative;
            z-index: 1;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            max-width: 70%;
        }

        .card-premium-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-header-premium {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
        }

        .status-badge.live {
            background: rgba(34, 197, 94, 0.1);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .status-badge.finished {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-badge.interval {
            background: rgba(234, 179, 8, 0.15);
            color: #eab308;
            border: 1px solid rgba(234, 179, 8, 0.3);
        }

        .live-indicator {
            color: #ffffff;
            font-weight: 800;
            font-size: 0.65rem;
            background: rgb(255 6 6 / 78%);
            padding: 4px 8px;
            border-radius: 50px;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.3);
            animation: pulse-red 1.5s infinite;
            display: inline-flex;
            align-items: center;
        }

        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); filter: brightness(1); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); filter: brightness(1.5); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); filter: brightness(1); }
        }

        .kickoff-time {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .teams-premium-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 10px;
            margin-top: 10px;
            padding: 0;
        }

        .score-premium-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            min-width: 60px;
            background: rgba(255, 255, 255, 0.05);
            padding: 0.5rem;
            border-radius: 0.75rem;
            border: 1px solid var(--card-premium-border);
        }

        .score-premium-display {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-light);
            letter-spacing: 1px;
        }

        .score-divider {
            color: var(--text-muted);
            opacity: 0.5;
            margin: 0 2px;
        }

        .score-label {
            font-size: 0.6rem;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .team-premium {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.45rem;
            flex: 1;
            text-align: center;
        }

        .team-name-premium {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-light);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .team-logo-premium {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .team-logo-premium img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .card-footer-premium {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid var(--card-premium-border);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .game-datetime {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 70px;
        }

        .game-datetime .game-date {
            font-size: 0.65rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .game-datetime .game-time {
            font-size: 1.1rem;
            color: var(--text-light);
            font-weight: 700;
        }

        .watch-premium-button {
            display: block;
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(135deg, var(--primary-blue), #2563eb);
            color: white;
            text-align: center;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }

        .watch-premium-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
            filter: brightness(1.1);
        }

        .opt-btn.active {
            border: none;
            color: #FFF;
            background: linear-gradient(135deg, var(--primary-blue), #4f5bf5, #2563eb) !important;
        }

        /* Ajustes Específicos para Celular */
        @media (max-width: 768px) {
            .player-wrapper {
                padding: 15px 0 0 0;
            }

            .player-wrapper.game-context {
                grid-template-columns: 1fr;
                padding: 0px;
            }

            .player-container {
                border-radius: 0;
                border-left: none;
                border-right: none;
            }
            .player-options {
                padding: 0 15px;
            }
            .header-left {
                gap: 10px;
            }
            .channel-info img {
                height: 32px;
                width: 32px;
            }
            h1 {
                font-size: 15px;
            }

            .games-rail {
                max-height: none;
            }

            .games-filters {
                gap: 6px;
            }

            .games-filter-btn {
                font-size: 11px;
                padding: 6px 10px;
            }

            .card-premium-content {
                padding: 1rem;
            }

            .card-banner {
                height: 30px;
            }

            .banner-title {
                font-size: 0.75rem;
                color: #94a3b8;
                font-weight: 400;
            }

            .teams-premium-container {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                margin-bottom: 10px;
                margin-top: 0;
                padding: 0;
            }

            .status-badge,
            .live-indicator {
                padding: 0.15rem 0.55rem;
            }

            .game-datetime .game-date,
            .game-date {
                font-size: 0.75rem;
            }

            .game-datetime .game-time {
                font-size: 1.5rem;
            }

            .game-time {
                font-size: 1.5rem;
            }

            .watch-premium-button {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body class="<?php echo $is_game_context ? 'game-context' : ''; ?>">

    <header>
        <div class="header-left">
            <a href="index.php" class="btn-back">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </a>
            <div class="channel-info">
                <h1><?php echo htmlspecialchars($title); ?> <span class="live-badge">Ao Vivo</span></h1>
            </div>
        </div>
    </header>

    <div class="player-wrapper <?php echo $is_game_context ? 'game-context' : ''; ?>">
        <div class="player-stage">
            <div class="player-container" id="player-container">
                <div id="jw-player-container" style="display: none; width: 100%; height: 100%;"></div>
                <iframe id="main-player" src="<?php echo $iframe_url; ?>" 
                    allowfullscreen 
                    webkitallowfullscreen 
                    mozallowfullscreen 
                    allow="autoplay; encrypted-media; fullscreen"
                    playsinline
                    webkit-playsinline
                    style="width: 100%; height: 100%; border: none;"></iframe>
            </div>

            <div class="player-options" id="player-options" style="flex-wrap: wrap;">
            <?php
            // Reordena para EmbedTV ser a primeira opção
            $sorted_streams = [];
            $others = [];
            foreach ($dynamic_streams as $stream) {
                if (stripos($stream['name'], 'embedtv') !== false) {
                    array_unshift($sorted_streams, $stream);
                } else {
                    $others[] = $stream;
                }
            }
            $sorted_streams = array_merge($sorted_streams, $others);
            ?>
            <?php if (!empty($sorted_streams)): ?>
                <?php foreach ($sorted_streams as $index => $stream): ?>
                <button class="opt-btn <?php echo ($index === 0) ? 'active' : ''; ?>"
                                onclick="switchDynamicPlayer('<?php echo htmlspecialchars($stream['url']); ?>', this)">
                        <?php echo ($index === 0) ? 'ELITE 01' : htmlspecialchars(strtoupper($stream['name'])); ?>
                    </button>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback para canais sem metadados dinâmicos -->
                <button class="opt-btn active" onclick="switchPlayer(1, this)">OPÇÃO 1</button>
                <?php if (!empty($iframe_url_2)): ?>
                    <button class="opt-btn" onclick="switchPlayer(2, this)">OPÇÃO 2</button>
                <?php endif; ?>
                <?php if (!empty($iframe_url_3)): ?>
                    <button class="opt-btn" onclick="switchPlayer(3, this)">OPÇÃO 3</button>
                <?php endif; ?>
            <?php endif; ?>
            </div>
        </div>

        <?php if ($is_game_context): ?>
        <aside class="games-rail" id="games-rail">
            <div class="games-rail-header">
                <h2>Jogos do Dia</h2>
                <span class="games-rail-status" id="games-rail-status">Atualizando...</span>
            </div>
            <div class="games-filters" id="games-filters">
                <button class="games-filter-btn active" data-filter="all" type="button">Todos</button>
                <button class="games-filter-btn" data-filter="brasileirao" type="button">Brasileirão</button>
                <button class="games-filter-btn" data-filter="live" type="button">Ao Vivo</button>
                <button class="games-filter-btn" data-filter="finished" type="button">Encerrado</button>
                <button class="games-filter-btn" data-filter="champions" type="button">Champions League</button>
            </div>
            <div class="games-sections" id="games-sections">
                <div class="games-list">
                    <div style="padding: 14px; color: var(--text-muted); font-size: 14px; text-align: center;">Carregando jogos...</div>
                </div>
            </div>
        </aside>
        <?php endif; ?>
    </div>

    <script>
        const player1Url = "<?php echo $iframe_url; ?>";
        let player2Url = "<?php echo $iframe_url_2; ?>";
        const player3Url = "<?php echo $iframe_url_3; ?>";
        let activePrimaryPlayerUrl = player1Url;
        let activeCredentials = null;
        const isGameContext = <?php echo $is_game_context ? 'true' : 'false'; ?>;
        let currentGameId = <?php echo json_encode($current_game_id, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const initialDynamicStreams = <?php echo json_encode($sorted_streams); ?>;
        let currentDynamicStreams = Array.isArray(initialDynamicStreams) ? initialDynamicStreams.slice() : [];
        let assistJogos = [];
        let assistLastScores = [];
        let assistChannelsForGames = [];
        let assistAdminOverrides = {};
        let assistMetaLoadingPromise = null;
        let assistActiveFilter = 'all';
        let assistScoreCache = {};

        const MAIN_CHANNELS_APIS = {
            noticias70: {
                key: 'noticias70',
                url: 'external_api.php?resource=channels&source=noticias70'
            },
            bugoumods: {
                key: 'bugoumods',
                url: 'external_api.php?resource=channels&source=bugoumods'
            }
        };
        const CHANNELS_SOURCE_STORAGE_KEY = 'eliteplay_channels_source';

        function getAssistChannelsSourceConfig() {
            const source = sessionStorage.getItem(CHANNELS_SOURCE_STORAGE_KEY) === MAIN_CHANNELS_APIS.bugoumods.key
                ? MAIN_CHANNELS_APIS.bugoumods.key
                : MAIN_CHANNELS_APIS.noticias70.key;

            return source === MAIN_CHANNELS_APIS.bugoumods.key
                ? MAIN_CHANNELS_APIS.bugoumods
                : MAIN_CHANNELS_APIS.noticias70;
        }

        const localDateYmd = (d = new Date()) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

        const slugify = (text) => {
            if (!text) return '';
            return text.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/\bfc\b|\bcf\b|\bde munique\b|\bunited\b|\batletico\b|\batletico\b/g, '')
                .replace(/[^a-z0-9]/g, '').trim();
        };

        const normalizeName = (name) => {
            let n = String(name || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[\s\-]/g, '')
                .replace(/\+/g, 'plus');

            if (n.startsWith('bbb')) {
                n = n.replace(/2[0-9]cam0?/g, '')
                     .replace(/2[0-9]mosaico/g, 'mosaico');
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

            if (/^paramountplus$/.test(n) || /^paramount1$/.test(n)) {
                n = 'paramountplus1';
            }
            if (/^paramountplus1$/.test(n)) {
                n = 'paramountplus1';
            }
            if (/^paramountplus2$/.test(n) || /^paramount2$/.test(n)) {
                n = 'paramountplus2';
            }

            return n;
        };

        function splitBaseAndNumber(value) {
            const m = String(value || '').match(/^(.*?)(\d+)$/);
            if (!m) return { base: String(value || ''), num: '' };
            return { base: m[1], num: m[2] };
        }

        function scoreChannelMatch(c, pidRaw, pidNorm) {
            const cn = normalizeName(c?.nome || '');
            if (!cn || !pidNorm) return -1;

            if (String(c?.embedtv_id || '') === String(pidRaw || '')) return 1000;
            if (cn === pidNorm) return 900;

            const cnSportv = cn.replace(/^sporto(\d+)$/, 'sportv$1');
            const pidSportv = pidNorm.replace(/^sporto(\d+)$/, 'sportv$1');
            if (cnSportv === pidNorm || cn === pidSportv || cnSportv === pidSportv) return 850;

            const pidParts = splitBaseAndNumber(pidNorm);
            const cnParts = splitBaseAndNumber(cn);

            if (pidParts.num !== '') {
                if (cnParts.base === pidParts.base && cnParts.num === pidParts.num) return 840;
                if (cn === pidParts.base) return 120;
            }

            if (cn.includes(pidNorm) || pidNorm.includes(cn)) return 400;
            if (cn.startsWith(pidNorm) || pidNorm.startsWith(cn)) return 350;

            return -1;
        }

        function buildGameId(jogo) {
            const rawId = String(jogo?.id ?? '').trim();
            if (rawId) return rawId;
            const start = String(jogo?.data?.timer?.start ?? '').trim();
            const titleNorm = normalizeName(jogo?.title || 'jogo');
            if (start) return `idx_${start}_${titleNorm}`;
            return `idx_${titleNorm}`;
        }

        function parseChannelName(fullName) {
            const original = String(fullName || '').trim();
            const patterns = [/(?:FHD|HD|SD|4K|1080p|720p)/i, /\[LEG\]/i, /\(ALT\)/i, /\[ALT\]/i, /(?:\s|^)ALT(?:\s|$)/i, /(?:\s|^)\*(?:\s|$)/i];

            let splitIndex = original.length;
            patterns.forEach((p) => {
                const idx = original.search(p);
                if (idx > 0 && idx < splitIndex) splitIndex = idx;
            });

            const baseName = original.substring(0, splitIndex).trim();
            const quality = original.substring(baseName.length).trim();
            return { baseName: baseName || original, quality: quality || 'Principal' };
        }

        async function loadAssistGameMeta() {
            if (assistMetaLoadingPromise) return assistMetaLoadingPromise;

            assistMetaLoadingPromise = (async () => {
                try {
                    const sourceCfg = getAssistChannelsSourceConfig();
                    const [resEmbed, resMainSelected, overridesRes] = await Promise.all([
                        fetch('proxy_embedtv.php?resource=channels', { cache: 'no-store' }).then(r => r.json()).catch(() => null),
                        fetch(`${sourceCfg.url}&_t=${Date.now()}`, { cache: 'no-store' }).then(r => r.json()).catch(() => ({})),
                        fetch(`admin_api.php?action=get_overrides&data=${localDateYmd()}&_t=${Date.now()}`, { cache: 'no-store' }).then(r => r.json()).catch(() => ({}))
                    ]);

                    const groupedChannels = {};

                    if (resEmbed && Array.isArray(resEmbed.channels)) {
                        const embedCatMap = {};
                        if (Array.isArray(resEmbed.categories)) {
                            resEmbed.categories.forEach(ct => { embedCatMap[ct.id] = ct.name; });
                        }

                        resEmbed.channels.forEach((c) => {
                            const norm = normalizeName(c.name);
                            const m3u8Url = `https://mr.cloudfronte.lat/fontes/mr/${c.id}.m3u8`;

                            let catName = 'EmbedTV';
                            if (Array.isArray(c.categories)) {
                                const validCatId = c.categories.find(id => id !== 0);
                                if (validCatId !== undefined && embedCatMap[validCatId]) catName = embedCatMap[validCatId];
                            }

                            if (groupedChannels[norm]) {
                                if (!groupedChannels[norm].streams.some(s => s.url === m3u8Url)) {
                                    groupedChannels[norm].streams.push({ name: 'EmbedTV', url: m3u8Url, channel_name: c.name });
                                }
                                if (!groupedChannels[norm].embedtv_id) groupedChannels[norm].embedtv_id = c.id;
                                if (!groupedChannels[norm].logo && c.image) groupedChannels[norm].logo = c.image;
                            } else {
                                groupedChannels[norm] = {
                                    nome: c.name,
                                    iframe_url: c.url,
                                    categoria: catName,
                                    logo: c.image || '',
                                    embedtv_id: c.id,
                                    streams: [{ name: 'EmbedTV', url: m3u8Url, channel_name: c.name }]
                                };
                            }
                        });
                    }

                    const mergeMainSource = (resMain) => {
                        if (!resMain || typeof resMain !== 'object') return;

                        Object.keys(resMain).forEach((category) => {
                            const canais = resMain[category];
                            if (!Array.isArray(canais)) return;

                            canais.forEach((c) => {
                                const parsed = parseChannelName(c.nome);
                                const baseName = parsed.baseName || c.nome;
                                const norm = normalizeName(baseName);

                                if (!groupedChannels[norm]) {
                                    groupedChannels[norm] = {
                                        nome: baseName,
                                        iframe_url: c.link,
                                        categoria: c.categoria || category || 'Outros',
                                        logo: c.capa || '',
                                        streams: []
                                    };
                                } else {
                                    groupedChannels[norm].nome = baseName || groupedChannels[norm].nome;
                                    groupedChannels[norm].iframe_url = c.link || groupedChannels[norm].iframe_url;
                                    if (c.capa) groupedChannels[norm].logo = c.capa;
                                }

                                if (c.link && !groupedChannels[norm].streams.some(s => s.url === c.link)) {
                                    groupedChannels[norm].streams.push({ name: parsed.quality || 'Principal', url: c.link, channel_name: baseName });
                                }
                            });
                        });
                    };

                    mergeMainSource(resMainSelected);

                    assistChannelsForGames = Object.values(groupedChannels).filter(c => Array.isArray(c.streams) && c.streams.length > 0);
                    assistAdminOverrides = (overridesRes && typeof overridesRes === 'object') ? overridesRes : {};
                } catch (e) {
                    assistChannelsForGames = [];
                    assistAdminOverrides = {};
                }
            })();

            return assistMetaLoadingPromise;
        }

        async function refreshAssistOverrides() {
            try {
                const overridesRes = await fetch(`admin_api.php?action=get_overrides&data=${localDateYmd()}&_t=${Date.now()}`, { cache: 'no-store' })
                    .then(r => r.json())
                    .catch(() => ({}));
                assistAdminOverrides = (overridesRes && typeof overridesRes === 'object') ? overridesRes : {};
            } catch (e) {
            }
        }

        const getTeamLogoUrl = (name) => {
            if (!name) return '';
            const slug = slugify(name).replace(/[\s.]+/g, '-').replace(/-+/g, '-');
            return `https://d1muf25xaso8hp.cloudfront.net/https://futemax.today/assets/uploads/teams/${slug}.webp`;
        };

        const getInitials = (name) => {
            if (!name) return '??';
            return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
        };

        function teamImgFallback(img, teamName, initials) {
            const slug = String(teamName || '').toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .trim().replace(/\s+/g, '-');
            if (!img.dataset.fallback) {
                img.dataset.fallback = '1';
                img.src = 'https://imgs.futemais.eu/imgs/' + slug + '.png';
            } else if (img.dataset.fallback === '1') {
                img.dataset.fallback = '2';
                img.src = 'https://d1muf25xaso8hp.cloudfront.net/https://uploads.futemaxhd.link/teams/' + slug + '.webp';
            } else {
                img.parentElement.innerText = initials;
            }
        }

        async function fetchAssistLiveScores() {
            let html = '';
            try {
                const response = await fetch(`external_api.php?resource=placar_hoje&_t=${Date.now()}`, { cache: 'no-store' });
                html = await response.text();
            } catch (e) {
                return [];
            }

            if (!html) return [];

            try {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                return Array.from(doc.querySelectorAll('a[href]')).filter(a => {
                    const href = a.getAttribute('href') || '';
                    const hrefLower = href.toLowerCase();
                    const blockedByHref = hrefLower.includes('sub-20') || hrefLower.includes('sub20');
                    return !blockedByHref && href.includes('.html') && a.querySelector('.status-name') && a.querySelector('h5');
                }).map(match => {
                    const homeTeam = match.querySelector('h5.text-right.team_link')?.innerText?.trim() || match.querySelector('h5.text-right')?.innerText?.trim() || '';
                    const awayTeam = match.querySelector('h5.text-left.team_link')?.innerText?.trim() || match.querySelector('h5.text-left')?.innerText?.trim() || '';
                    const scoreElements = match.querySelectorAll('.match-score .badge');
                    const homeScore = scoreElements[0]?.innerText?.trim() || '0';
                    const awayScore = scoreElements[1]?.innerText?.trim() || '0';
                    const statusText = match.querySelector('.status-name')?.innerText?.trim() || '';
                    const leagueName = match.querySelector('.match-card-league-name')?.innerText?.trim() || '';
                    return { homeTeam, awayTeam, homeScore, awayScore, statusText, leagueName };
                }).filter(r => r.homeTeam && r.awayTeam);
            } catch (e) {
                return [];
            }
        }

        function matchAssistGameScores(apiGames, scrapedScores) {
            return apiGames.map(game => {
                const homeName = game.data?.teams?.home?.name || '';
                const awayName = game.data?.teams?.away?.name || '';
                const homeSlug = slugify(homeName);
                const awaySlug = slugify(awayName);

                const startTs = Number(game.data?.timer?.start || 0);
                const nowTs = Math.floor(Date.now() / 1000);

                let match = null;
                match = scrapedScores.find(ls => {
                    const lsHomeSlug = slugify(ls.homeTeam);
                    const lsAwaySlug = slugify(ls.awayTeam);
                    const homeMatch = homeSlug.length > 2 && lsHomeSlug && (lsHomeSlug.includes(homeSlug) || homeSlug.includes(lsHomeSlug));
                    const awayMatch = awaySlug.length > 2 && lsAwaySlug && (lsAwaySlug.includes(awaySlug) || awaySlug.includes(lsAwaySlug));
                    return homeMatch && awayMatch;
                }) || null;

                if (!match) {
                    const withinReasonableWindow = !startTs || (startTs >= (nowTs - 12 * 3600) && startTs <= (nowTs + 6 * 3600));
                    if (withinReasonableWindow) {
                        match = scrapedScores.find(ls => {
                            const lsHomeSlug = slugify(ls.homeTeam);
                            const lsAwaySlug = slugify(ls.awayTeam);
                            const homeMatch = homeSlug.length > 3 && lsHomeSlug && (lsHomeSlug.includes(homeSlug) || homeSlug.includes(lsHomeSlug));
                            const awayMatch = awaySlug.length > 3 && lsAwaySlug && (lsAwaySlug.includes(awaySlug) || awaySlug.includes(lsAwaySlug));
                            return homeMatch || awayMatch;
                        }) || null;
                    }
                }

                const apiStatusLabel = String(game.status_label || '').toLowerCase();
                const apiTimeText = String(game.data?.time || '').toLowerCase();
                let isActuallyFinished = apiStatusLabel.includes('enc') || apiTimeText.includes('fim') || apiTimeText.includes('enc');
                let isActuallyLive = !isActuallyFinished && (
                    apiStatusLabel.includes('vivo') ||
                    apiTimeText.includes('vivo') ||
                    apiTimeText.includes('andamento') ||
                    apiTimeText.includes('1t') ||
                    apiTimeText.includes('2t') ||
                    String(game.data?.time || '').includes("'")
                );

                let homeScore = game.homeScore ?? '';
                let awayScore = game.awayScore ?? '';
                let statusText = game.statusText || game.data?.time || 'HOJE';

                if (match) {
                    statusText = match.statusText;
                    homeScore = match.homeScore;
                    awayScore = match.awayScore;
                    const statusLow = statusText.toLowerCase();
                    const scraperFinished = statusLow.includes('fin') || statusLow.includes('fim') || statusLow.includes('enc');
                    const scraperLive = statusText.includes("'") || statusLow.includes('min') || statusLow.includes('int') || statusLow.includes('andamento') || statusLow.includes('vivo') || statusLow.includes('2t') || statusLow.includes('1t') || statusLow.includes('acresc') || statusLow.includes('penal');
                    const timeMatch = statusText.match(/(\d{1,2}):(\d{2})/);
                    const isScheduled = timeMatch && !scraperLive && !scraperFinished;

                    if (scraperFinished) {
                        isActuallyFinished = true;
                        isActuallyLive = false;
                    } else if (scraperLive) {
                        isActuallyLive = true;
                        isActuallyFinished = false;
                    } else if (isScheduled) {
                        isActuallyLive = false;
                        isActuallyFinished = false;
                    }
                }

                return {
                    ...game,
                    homeScore,
                    awayScore,
                    statusText,
                    status_label: isActuallyLive ? 'Ao Vivo' : (isActuallyFinished ? 'Encerrado' : 'Agendado'),
                    scrapedLeague: match?.leagueName || ''
                };
            });
        }

        function decodeStreamsBase64(streamsBase64) {
            if (!streamsBase64) return [];
            try {
                const decoded = decodeURIComponent(escape(atob(streamsBase64)));
                const parsed = JSON.parse(decoded);
                return Array.isArray(parsed) ? parsed.filter((s) => !isH265Stream(s)) : [];
            } catch (e) {
                return [];
            }
        }

        function isH265Stream(stream) {
            const name = String(stream?.name || '').toUpperCase();
            const url = String(stream?.url || '').toUpperCase();
            return name.includes('H265') || name.includes('H.265') || name.includes('HEVC') ||
                   url.includes('H265') || url.includes('H.265') || url.includes('HEVC');
        }

        function renderPlayerOptions(streams = []) {
            const optionsEl = document.getElementById('player-options');
            if (!optionsEl) return;

            optionsEl.innerHTML = '';

            const filteredStreams = Array.isArray(streams)
                ? streams.filter((s) => !isH265Stream(s))
                : [];

            if (filteredStreams.length > 0) {
                filteredStreams.forEach((stream, index) => {
                    const btn = document.createElement('button');
                    btn.className = `opt-btn${index === 0 ? ' active' : ''}`;
                    btn.textContent = getPlayerOptionLabel(stream, index);
                    btn.addEventListener('click', () => switchDynamicPlayer(String(stream?.url || ''), btn));
                    optionsEl.appendChild(btn);
                });
                return;
            }

            const btn1 = document.createElement('button');
            btn1.className = 'opt-btn active';
            btn1.textContent = 'OPCAO 1';
            btn1.addEventListener('click', () => switchPlayer(1, btn1));
            optionsEl.appendChild(btn1);

            if (player2Url) {
                const btn2 = document.createElement('button');
                btn2.className = 'opt-btn';
                btn2.textContent = 'OPCAO 2';
                btn2.addEventListener('click', () => switchPlayer(2, btn2));
                optionsEl.appendChild(btn2);
            }

            if (player3Url) {
                const btn3 = document.createElement('button');
                btn3.className = 'opt-btn';
                btn3.textContent = 'OPCAO 3';
                btn3.addEventListener('click', () => switchPlayer(3, btn3));
                optionsEl.appendChild(btn3);
            }
        }

        function getPlayerOptionLabel(stream, index) {
            if (index === 0) return 'ELITE 01';

            const rawName = String(stream?.name || '').trim();
            if (/^embed\s*tv$/i.test(rawName)) {
                const channel = String(stream?.channel_name || stream?.channel || '').trim();
                if (channel) return `ELITE-${channel}`.toUpperCase();
                return 'ELITE';
            }

            return String(rawName || `OPCAO ${index + 1}`).toUpperCase();
        }

        function updateHeaderTitle(title) {
            const h1 = document.querySelector('.channel-info h1');
            if (!h1) return;
            h1.innerHTML = '';
            h1.appendChild(document.createTextNode(String(title || 'Assistir Canal')));
            h1.appendChild(document.createTextNode(' '));
            const badge = document.createElement('span');
            badge.className = 'live-badge';
            badge.textContent = 'Ao Vivo';
            h1.appendChild(badge);
        }

        function openGamePlayer(iframeUrl, title, logo, streamsBase64 = '', gameId = '') {
            const decodedStreams = decodeStreamsBase64(streamsBase64);
            const safeUrl = String(iframeUrl || '');
            currentDynamicStreams = decodedStreams.length > 0
                ? decodedStreams
                : (safeUrl ? [{ name: 'EmbedTV', url: safeUrl }] : []);
            currentDynamicStreams = currentDynamicStreams.filter((s) => !isH265Stream(s));

            const firstUrl = currentDynamicStreams[0]?.url || safeUrl || activePrimaryPlayerUrl;
            if (!firstUrl) return;

            activePrimaryPlayerUrl = firstUrl;
            updateHeaderTitle(title);
            renderPlayerOptions(currentDynamicStreams);
            initPlayer(firstUrl);

            if (gameId) {
                currentGameId = String(gameId);
                updateCurrentGameCardSelection(currentGameId);
            }
        }

        function updateCurrentGameCardSelection(gameId) {
            if (!gameId) return;
            document.querySelectorAll('#games-sections .game-card').forEach((card) => {
                const cardId = card.getAttribute('data-game-id') || '';
                if (cardId === gameId) {
                    card.classList.add('current-game');
                } else {
                    card.classList.remove('current-game');
                }
            });
        }

        function getAssistGameStatusType(jogo) {
            const statusLabel = String(jogo?.status_label || '').toLowerCase();
            const statusText = String(jogo?.statusText || jogo?.data?.time || '').toLowerCase();
            const startTs = Number(jogo?.data?.timer?.start || 0);
            const nowTs = Math.floor(Date.now() / 1000);

            if (statusLabel.includes('encerr') || statusLabel.includes('final') || statusLabel.includes('fim')) return 'finished';
            if (statusText.includes('encerr') || statusText.includes('final') || statusText.includes('fim')) return 'finished';

            if (startTs > 0 && startTs < (nowTs - 3 * 60 * 60) && !statusLabel.includes('ao vivo') && !statusText.includes('vivo')) {
                return 'finished';
            }

            if (statusLabel.includes('ao vivo') || statusLabel.includes('vivo')) return 'live';
            if (statusText.includes("'") || statusText.includes('min') || statusText.includes('vivo') || statusText.includes('andamento') || statusText.includes('1t') || statusText.includes('2t') || statusText.includes('int')) return 'live';

            return 'upcoming';
        }

        function normalizeFilterText(text) {
            return String(text || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim();
        }

        function gameMatchesAssistFilter(jogo, filterKey) {
            if (filterKey === 'all') return true;

            const competition = normalizeFilterText(jogo?.scrapedLeague || jogo?.data?.league || '');
            const statusType = getAssistGameStatusType(jogo);

            if (filterKey === 'finished') {
                return statusType === 'finished';
            }

            if (filterKey === 'live') {
                return statusType === 'live';
            }

            if (filterKey === 'brasileirao') {
                const isBrasileirao = competition.includes('brasileirao') || competition.includes('campeonato brasileiro');
                const isSerieA = competition.includes('serie a') || competition.includes('serie-a') || competition.includes('série a');
                return isBrasileirao && isSerieA;
            }

            if (filterKey === 'champions') {
                return competition.includes('champions') || competition.includes('liga dos campeoes') || competition.includes('uefa champions');
            }

            return true;
        }

        function applyAssistFilter(jogos) {
            if (!Array.isArray(jogos)) return [];
            return jogos.filter(j => gameMatchesAssistFilter(j, assistActiveFilter));
        }

        function updateAssistFilterButtons() {
            document.querySelectorAll('#games-filters .games-filter-btn').forEach((btn) => {
                const key = String(btn.getAttribute('data-filter') || 'all');
                btn.classList.toggle('active', key === assistActiveFilter);
            });
        }

        function setAssistFilter(filterKey) {
            assistActiveFilter = String(filterKey || 'all');
            updateAssistFilterButtons();
            renderAssistGames(assistJogos);
        }

        function mergeAssistScoresWithCache(games) {
            if (!Array.isArray(games)) return [];

            return games.map((game) => {
                const id = buildGameId(game);
                const cache = assistScoreCache[id] || null;

                const homeRaw = String(game?.homeScore ?? '').trim();
                const awayRaw = String(game?.awayScore ?? '').trim();
                const hasCurrentScores = homeRaw !== '' && awayRaw !== '' && homeRaw !== '-' && awayRaw !== '-';

                if (hasCurrentScores) {
                    assistScoreCache[id] = {
                        homeScore: game.homeScore,
                        awayScore: game.awayScore,
                        statusText: game.statusText || game?.data?.time || '',
                        status_label: game.status_label || ''
                    };
                    return game;
                }

                if (cache) {
                    return {
                        ...game,
                        homeScore: cache.homeScore,
                        awayScore: cache.awayScore,
                        statusText: game.statusText || cache.statusText,
                        status_label: game.status_label || cache.status_label
                    };
                }

                return game;
            });
        }

        function initAssistFilters() {
            const wrap = document.getElementById('games-filters');
            if (!wrap) return;

            wrap.querySelectorAll('.games-filter-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const filterKey = String(btn.getAttribute('data-filter') || 'all');
                    setAssistFilter(filterKey);
                });
            });

            updateAssistFilterButtons();
        }

        function createAssistGameCardHTML(jogo) {
            const homeName = jogo.data?.teams?.home?.name || 'Time A';
            const awayName = jogo.data?.teams?.away?.name || 'Time B';
            const homeLogo = jogo.data?.teams?.home?.image || getTeamLogoUrl(homeName);
            const awayLogo = jogo.data?.teams?.away?.image || getTeamLogoUrl(awayName);
            const homeInitials = getInitials(homeName);
            const awayInitials = getInitials(awayName);
            const statusType = getAssistGameStatusType(jogo);
            const isLive = statusType === 'live';
            const isFinished = statusType === 'finished';
            const isInterval = isLive && String(jogo.statusText || '').toLowerCase().includes('int');
            const statusLabel = isFinished ? 'ENCERRADO' : (jogo.statusText || jogo.data?.time || 'HOJE');
            const homeScore = (jogo.homeScore !== undefined && jogo.homeScore !== null && String(jogo.homeScore).trim() !== '')
                ? jogo.homeScore
                : (isLive || isFinished ? '-' : '');
            const awayScore = (jogo.awayScore !== undefined && jogo.awayScore !== null && String(jogo.awayScore).trim() !== '')
                ? jogo.awayScore
                : (isLive || isFinished ? '-' : '');
            const originalEmbedTvUrl = jogo.players && jogo.players[0] ? jogo.players[0] : '';
            const playerUrlOpcao1 = originalEmbedTvUrl;
            const safeTitle = String(jogo.title || '').replace(/'/g, "\\'");
            const jogoId = buildGameId(jogo);
            const startTs = Number(jogo.data?.timer?.start || 0);
            const msToStart = startTs > 0 ? (startTs * 1000) - Date.now() : 0;
            const isStartingSoon = !isLive && !isFinished && msToStart > 0 && msToStart <= 60 * 60 * 1000;
            const gameCardClass = `game-card${isStartingSoon ? ' soon-start' : ''}`;
            const competition = jogo.scrapedLeague || jogo.data?.league || 'Futebol';
            const competitionSlug = slugify(competition);
            const bannerMap = [
                { check: s => s.includes('brasileiro') || s.includes('serie-a') || s.includes('serie-b') || s.includes('brasileirao'), img: 'brasileiro.webp' },
                { check: s => s.includes('libertadores') || s.includes('copa-libertadores') || s.includes('conmebol-libertadores'), img: 'libertadores.webp' },
                { check: s => s.includes('sul-americana') || s.includes('sulamericana') || s.includes('copa-sul-americana') || s.includes('conmebol-sudamericana'), img: 'sulamericana.webp' },
                { check: s => s.includes('champions') || s.includes('champions-league') || s.includes('liga-dos-campeoes') || s.includes('liga-dos-campeoes-da-uefa'), img: 'champions.webp' },
                { check: s => s.includes('italiano') || s.includes('calcio'), img: 'italiano.webp' },
                { check: s => s.includes('alemao') || s.includes('bundesliga'), img: 'alemao.webp' },
                { check: s => s.includes('ingles') || s.includes('premier') || s.includes('championship'), img: 'ingles.webp' },
                { check: s => s.includes('portugues') || s.includes('campeonato-portugues') || s.includes('liga-portugal') || s.includes('primeira-liga'), img: 'portugues.webp' },
                { check: s => s.includes('espanhol') || s.includes('laliga') || s.includes('la-liga'), img: 'espanhol.webp' },
                { check: s => s.includes('frances') || s.includes('campeonato-frances') || s.includes('ligue-1'), img: 'franca.webp' },
            ];
            const matchedBanner = bannerMap.find(b => b.check(competitionSlug));
            const bannerStyle = matchedBanner ? `style="background-image: url('api jogos/${matchedBanner.img}');"` : '';
            const currentClass = currentGameId && currentGameId === jogoId ? ' current-game' : '';

            let gameStreamsStr = '';
            const OVERRIDE_REMOVE_ORIGINAL_KEY = '__ORIGINAL_API__';
            const overrideList = jogoId && Array.isArray(assistAdminOverrides[jogoId]) ? assistAdminOverrides[jogoId] : [];
            const hasAdminOverride = overrideList.length > 0;

            let detectedStreams = [];
            let matchedChannel = null;
            const channelsToUse = Array.isArray(assistChannelsForGames) ? assistChannelsForGames : [];

            if (channelsToUse.length > 0) {
                const apiPlayerIds = (Array.isArray(jogo.players) ? jogo.players : [])
                    .flatMap(p => String(decodeURIComponent(String(p || ''))).split(','))
                    .map(v => v.trim())
                    .filter(Boolean)
                    .map(v => {
                        const tail = v.split('/').pop() || v;
                        return tail.split('?')[0].replace(/\.(m3u8|html?)$/i, '').trim();
                    })
                    .filter(Boolean);

                const byApiIdMatches = [];
                apiPlayerIds.forEach((pid) => {
                    const pidNorm = normalizeName(pid);
                    const ch = channelsToUse
                        .map(c => ({ c, score: scoreChannelMatch(c, pid, pidNorm) }))
                        .filter(item => item.score >= 0)
                        .sort((a, b) => b.score - a.score)[0]?.c || null;
                    if (ch && !byApiIdMatches.some(x => x.nome === ch.nome)) byApiIdMatches.push(ch);
                });

                if (byApiIdMatches.length > 0) {
                    matchedChannel = byApiIdMatches[0];
                    byApiIdMatches.forEach(ch => {
                        (Array.isArray(ch.streams) ? ch.streams : []).forEach(s => {
                            if (!detectedStreams.some(ds => ds.url === s.url)) {
                                detectedStreams.push({ name: s.name, url: s.url, channel_name: s.channel_name || ch.nome || '' });
                            }
                        });
                    });
                }

                if (originalEmbedTvUrl) {
                    const urlId = originalEmbedTvUrl.split('/').pop().split('?')[0];
                    if (!matchedChannel) matchedChannel = channelsToUse.find(c => c.embedtv_id === urlId);
                }

                if (!matchedChannel && detectedStreams.length === 0) {
                    const gameTitleNorm = normalizeName(jogo.title || '');
                    matchedChannel = channelsToUse.find(c => {
                        const chanNameNorm = normalizeName(c.nome || '');
                        return gameTitleNorm.includes(chanNameNorm) || chanNameNorm.includes(gameTitleNorm);
                    });
                }

                if (!matchedChannel && detectedStreams.length === 0 && originalEmbedTvUrl) {
                    let urlId = originalEmbedTvUrl.split('id=').pop().split('&')[0];
                    if (!urlId || urlId.includes('/') || urlId === originalEmbedTvUrl) {
                        urlId = originalEmbedTvUrl.split('?')[0].replace(/\/$/, '').split('/').pop();
                    }
                    if (urlId) {
                        const idNorm = normalizeName(urlId);
                        matchedChannel = channelsToUse
                            .map(c => ({ c, score: scoreChannelMatch(c, urlId, idNorm) }))
                            .filter(item => item.score >= 0)
                            .sort((a, b) => b.score - a.score)[0]?.c || null;
                    }
                }

                if (detectedStreams.length === 0 && matchedChannel && Array.isArray(matchedChannel.streams) && matchedChannel.streams.length > 0) {
                    detectedStreams = matchedChannel.streams.map(s => ({
                        name: s.name,
                        url: s.url,
                        channel_name: s.channel_name || matchedChannel.nome || ''
                    }));
                }
            }

            if (hasAdminOverride && detectedStreams.length === 0 && originalEmbedTvUrl) {
                let urlId = originalEmbedTvUrl.split('id=').pop().split('&')[0];
                if (!urlId || urlId.includes('/') || urlId === originalEmbedTvUrl) {
                    urlId = originalEmbedTvUrl.split('?')[0].replace(/\/$/, '').split('/').pop();
                }
                if (urlId) {
                    const idNorm = normalizeName(urlId);
                    const matchByUrl = channelsToUse
                        .map(c => ({ c, score: scoreChannelMatch(c, urlId, idNorm) }))
                        .filter(item => item.score >= 0)
                        .sort((a, b) => b.score - a.score)[0]?.c || null;
                    if (matchByUrl && Array.isArray(matchByUrl.streams)) {
                        detectedStreams = matchByUrl.streams.map(s => ({
                            name: s.name,
                            url: s.url,
                            channel_name: s.channel_name || matchByUrl.nome || ''
                        }));
                    }
                }
            }

            let finalStreams = [...detectedStreams];

            if (hasAdminOverride && channelsToUse.length > 0) {
                const overrideStreams = [];

                overrideList.forEach((ov) => {
                    const ovName = String(ov?.name || '').trim();
                    if (!ovName) return;

                    if (ovName === OVERRIDE_REMOVE_ORIGINAL_KEY) {
                        if (!ov.remove_channel && Array.isArray(ov.remove_qualities) && ov.remove_qualities.length > 0) {
                            finalStreams = finalStreams.filter((s) => {
                                const sq = normalizeName(String(s.name || ''));
                                return !ov.remove_qualities.some((rq) => sq.includes(normalizeName(rq)) || normalizeName(rq).includes(sq));
                            });
                        } else if (ov.remove_channel) {
                            finalStreams = [];
                        }
                        return;
                    }

                    const overrideNames = ovName.split(',').map(v => v.trim()).filter(Boolean);
                    const namesToProcess = overrideNames.length > 0 ? overrideNames : [ovName];

                    namesToProcess.forEach((singleName) => {
                        const ovNorm = normalizeName(singleName);
                        const matchChan = channelsToUse.find((c) => {
                            const cnNorm = normalizeName(c.nome || '');
                            return cnNorm === ovNorm || ovNorm.includes(cnNorm) || cnNorm.includes(ovNorm) || cnNorm.replace(/[^a-z0-9]/g, '').includes(ovNorm.replace(/[^a-z0-9]/g, ''));
                        });

                        if (!matchChan || !Array.isArray(matchChan.streams)) return;

                        if (ov.remove_channel) {
                            finalStreams = finalStreams.filter(fs => !matchChan.streams.some(ms => ms.url === fs.url));
                            return;
                        }

                        if (Array.isArray(ov.qualities) && ov.qualities.length > 0) {
                            ov.qualities.forEach((q) => {
                                const qUpper = String(q).toUpperCase();
                                const stream = matchChan.streams.find((s) => {
                                    const sNameUpper = String(s.name || '').toUpperCase();
                                    return sNameUpper.includes(qUpper) || qUpper.includes(sNameUpper);
                                });

                                if (stream && !overrideStreams.some(x => x.url === stream.url)) {
                                    const existingStream = detectedStreams.find(d => d.url === stream.url);
                                    if (existingStream) {
                                        overrideStreams.push({ name: existingStream.name, url: stream.url, channel_name: existingStream.channel_name || singleName });
                                        return;
                                    }
                                    const isEmbedtv = stream.url.includes('mr.cloudfronte.lat') || stream.url.includes('mr.s27-usa-cloudfront-net.online');
                                    if (isEmbedtv) {
                                        overrideStreams.push({ name: `ELITE-${singleName.toUpperCase()}`, url: stream.url, channel_name: singleName });
                                    } else {
                                        overrideStreams.push({ name: stream.name, url: stream.url, channel_name: singleName });
                                    }
                                }
                            });
                        } else {
                            matchChan.streams.forEach((s) => {
                                if (!overrideStreams.some(x => x.url === s.url)) {
                                    const existingStream = detectedStreams.find(d => d.url === s.url);
                                    if (existingStream) {
                                        overrideStreams.push({ name: existingStream.name, url: s.url, channel_name: existingStream.channel_name || singleName });
                                        return;
                                    }
                                    const isEmbedtv = s.url.includes('mr.cloudfronte.lat') || s.url.includes('mr.s27-usa-cloudfront-net.online');
                                    if (isEmbedtv) {
                                        overrideStreams.push({ name: `ELITE-${singleName.toUpperCase()}`, url: s.url, channel_name: singleName });
                                    } else {
                                        overrideStreams.push({ name: s.name, url: s.url, channel_name: singleName });
                                    }
                                }
                            });
                        }
                    });
                });

                if (overrideStreams.length > 0) {
                    finalStreams = [...finalStreams, ...overrideStreams];
                }
            }

            finalStreams = finalStreams.filter((s) => !isH265Stream(s));

            finalStreams = finalStreams.filter((s) => String(s?.url || '').trim() !== '').filter((s, idx, arr) => {
                const url = String(s.url || '').trim();
                return arr.findIndex(x => String(x.url || '').trim() === url) === idx;
            });

            if (finalStreams.length > 0) {
                try {
                    gameStreamsStr = btoa(unescape(encodeURIComponent(JSON.stringify(finalStreams))));
                } catch (e) {
                    gameStreamsStr = '';
                }
            }

            const initialPlayerUrl = finalStreams.length > 0 ? finalStreams[0].url : playerUrlOpcao1;

            return `
                <div class="${gameCardClass}${currentClass}" data-game-id="${String(jogoId).replace(/&/g, '&amp;').replace(/"/g, '&quot;')}">
                    <div class="card-banner" ${bannerStyle}>
                        <div class="banner-overlay"></div>
                        <span class="banner-title">${competition}</span>
                    </div>
                    <div class="card-premium-content">
                        ${(isLive || isFinished) ? `
                        <div class="card-header-premium">
                            <span class="status-badge ${isInterval ? 'interval' : (isLive ? 'live' : 'finished')}">${statusLabel}</span>
                            <span class="kickoff-time">${isLive ? '<span class="live-indicator">AO VIVO</span>' : (jogo.data?.time || '')}</span>
                        </div>` : ''}
                        <div class="teams-premium-container">
                            <div class="team-premium">
                                <div class="team-logo-premium"><img src="${homeLogo}" alt="${homeName}" onerror="teamImgFallback(this, '${homeName.replace(/'/g, "\\'")}', '${homeInitials}')"></div>
                                <span class="team-name-premium">${homeName}</span>
                            </div>
                            ${(isLive || isFinished) ? `
                            <div class="score-premium-container">
                                <div class="score-premium-display"><span>${homeScore}</span><span class="score-divider">-</span><span>${awayScore}</span></div>
                                <span class="score-label">${isFinished ? 'Final' : 'Placar'}</span>
                            </div>` : `
                            <div class="score-premium-container" style="opacity: 0.3; border: none; background: transparent;">
                                <div class="score-premium-display" style="font-size: 1rem;">VS</div>
                            </div>`}
                            <div class="team-premium">
                                <div class="team-logo-premium"><img src="${awayLogo}" alt="${awayName}" onerror="teamImgFallback(this, '${awayName.replace(/'/g, "\\'")}', '${awayInitials}')"></div>
                                <span class="team-name-premium">${awayName}</span>
                            </div>
                        </div>
                        <div class="card-footer-premium">
                            <div class="game-datetime" ${isFinished ? 'style="flex-direction: row; gap: 8px; justify-content: center; width: 100%; align-items: center;"' : ''}>
                                <span class="game-date">${(() => {
                                    const ts = jogo.data?.timer?.start;
                                    if (!ts) return 'Hoje';
                                    const d = new Date(ts * 1000);
                                    return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
                                })()}</span>
                                <span class="game-time">${(() => {
                                    const ts = jogo.data?.timer?.start;
                                    if (!ts) return '--h--';
                                    const d = new Date(ts * 1000);
                                    return `${String(d.getHours()).padStart(2, '0')}h${String(d.getMinutes()).padStart(2, '0')}`;
                                })()}</span>
                            </div>
                            ${!isFinished ? `<button onclick="openGamePlayer('${String(initialPlayerUrl || originalEmbedTvUrl).replace(/'/g, "\\'")}', '${safeTitle}', '${String(jogo.image || '').replace(/'/g, "\\'")}', '${gameStreamsStr}', '${jogoId.replace(/'/g, "\\'")}')" class="watch-premium-button" style="flex: 1;">Assistir Agora</button>` : ''}
                        </div>
                    </div>
                </div>`;
        }

        function renderAssistGames(jogos) {
            const container = document.getElementById('games-sections');
            if (!container) return;

            if (!Array.isArray(jogos) || jogos.length === 0) {
                container.innerHTML = '<div style="padding: 14px; color: var(--text-muted); text-align:center;">Nenhum jogo encontrado hoje.</div>';
                return;
            }

            const filteredJogos = applyAssistFilter(jogos);
            if (filteredJogos.length === 0) {
                container.innerHTML = '<div style="padding: 14px; color: var(--text-muted); text-align:center;">Nenhum jogo encontrado para este filtro.</div>';
                return;
            }

            const live = filteredJogos.filter(j => getAssistGameStatusType(j) === 'live');
            const upcoming = filteredJogos.filter(j => getAssistGameStatusType(j) === 'upcoming');
            const finished = filteredJogos.filter(j => getAssistGameStatusType(j) === 'finished');

            const renderSection = (title, list) => {
                if (!list.length) return '';
                return `
                    <section>
                        <div class="game-section-title">
                            <h3>${title}</h3>
                            <span class="game-section-count">${list.length}</span>
                        </div>
                        <div class="games-list">${list.map(createAssistGameCardHTML).join('')}</div>
                    </section>`;
            };

            const html = renderSection('Ao Vivo Agora', live) + renderSection('Próximos Jogos', upcoming) + renderSection('Jogos Encerrados', finished);
            container.innerHTML = html || '<div style="padding: 14px; color: var(--text-muted); text-align:center;">Nenhum jogo encontrado para este filtro.</div>';
        }

        async function loadAssistGames() {
            if (!isGameContext) return;
            const statusEl = document.getElementById('games-rail-status');
            if (statusEl) statusEl.textContent = 'Atualizando...';

            await loadAssistGameMeta();

            try {
                const [payload] = await Promise.all([
                    fetch(`assist_games_snapshot.php?_t=${Date.now()}`, { cache: 'no-store' })
                        .then(r => r.ok ? r.json() : Promise.reject(new Error('snapshot-error'))),
                    refreshAssistOverrides()
                ]);

                assistJogos = Array.isArray(payload?.games) ? payload.games : [];

                const hasMissingFinishedScore = assistJogos.some((j) => {
                    const statusType = getAssistGameStatusType(j);
                    if (statusType !== 'finished') return false;
                    const hs = String(j?.homeScore ?? '').trim();
                    const as = String(j?.awayScore ?? '').trim();
                    return hs === '' || as === '' || hs === '-' || as === '-';
                });

                if (hasMissingFinishedScore) {
                    try {
                        assistLastScores = await fetchAssistLiveScores();
                        if (Array.isArray(assistLastScores) && assistLastScores.length > 0) {
                            assistJogos = matchAssistGameScores(assistJogos, assistLastScores);
                        }
                    } catch (enrichError) {
                    }
                }

                assistJogos = mergeAssistScoresWithCache(assistJogos);

                renderAssistGames(assistJogos);

                if (statusEl) {
                    const ts = Number(payload?.generated_at || 0);
                    if (ts > 0) {
                        const d = new Date(ts * 1000);
                        const hh = String(d.getHours()).padStart(2, '0');
                        const mm = String(d.getMinutes()).padStart(2, '0');
                        const ss = String(d.getSeconds()).padStart(2, '0');
                        statusEl.textContent = `Atualizado ${hh}:${mm}:${ss}`;
                    } else {
                        statusEl.textContent = 'Atualizado agora';
                    }
                }
            } catch (e) {
                try {
                    const [jogosRes, scraped] = await Promise.all([
                        fetch(`proxy_embedtv.php?resource=jogos&_t=${Date.now()}`, { cache: 'no-store' }).then(r => r.json()).catch(() => []),
                        fetchAssistLiveScores(),
                        refreshAssistOverrides()
                    ]);

                    assistJogos = Array.isArray(jogosRes) ? jogosRes : [];
                    assistLastScores = scraped;
                    assistJogos = mergeAssistScoresWithCache(matchAssistGameScores(assistJogos, assistLastScores));
                    renderAssistGames(assistJogos);

                    if (statusEl) statusEl.textContent = 'Atualizado agora';
                } catch (fallbackError) {
                    if (statusEl) statusEl.textContent = 'Falha ao atualizar';
                }
            }
        }

        function isM3U8(url) {
            return url && url.includes('.m3u8');
        }

        function shouldUseProxy(url) {
            // Proxy apenas para APIs no backend. Arquivos m3u8 tocam direto.
            return false;
        }

        function getEmbedFallbackUrl(url) {
            if (!url) return '';
            if (url.includes('mr.cloudfronte.lat')) {
                return url.replace('mr.cloudfronte.lat', 'mr.s27-usa-cloudfront-net.online');
            }
            return '';
        }

        async function initPlayer(url) {
            const iframe = document.getElementById('main-player');
            const jwDiv = document.getElementById('jw-player-container');

            // Destroi player anterior se existir
            try { jwplayer("jw-player-container").remove(); } catch(e) {}

            if (isM3U8(url)) {
                iframe.style.display = 'none';
                jwDiv.style.display = 'block';
                iframe.src = 'about:blank';

                const setupJw = (rawUrl, attemptedFallback = false) => {
                    const streamUrl = shouldUseProxy(rawUrl)
                        ? ("proxy.php?url=" + encodeURIComponent(rawUrl))
                        : rawUrl;

                    jwplayer("jw-player-container").setup({
                        file: streamUrl,
                        type: "hls",
                        autostart: true,
                        width: "100%",
                        height: "100%",
                        aspectratio: "16:9",
                        stretching: "uniform"
                    });

                    jwplayer("jw-player-container").once('error', () => {
                        if (attemptedFallback) return;
                        const fallbackUrl = getEmbedFallbackUrl(rawUrl);
                        if (fallbackUrl) {
                            setupJw(fallbackUrl, true);
                            return;
                        }

                        // Fallback final: abre a página embed (quando disponível)
                        if (rawUrl.includes('mr.cloudfronte.lat') && activePrimaryPlayerUrl && !isM3U8(activePrimaryPlayerUrl)) {
                            jwDiv.style.display = 'none';
                            iframe.style.display = 'block';
                            iframe.src = activePrimaryPlayerUrl;
                        }
                    });
                };

                setupJw(url);

            } else {
                jwDiv.style.display = 'none';
                iframe.style.display = 'block';

                let finalUrl = url;
                if (activeCredentials) {
                    const sep = finalUrl.includes('?') ? '&' : '?';
                    finalUrl += `${sep}user=${encodeURIComponent(activeCredentials.user)}&pass=${encodeURIComponent(activeCredentials.pass)}`;
                }
                iframe.src = finalUrl;
            }
        }

        async function fetchCredentials() {
            try {
                const response = await fetch('jhghkfju38j.csv');
                if (!response.ok) return null;
                const text = await response.text();
                const lines = text.split('\n');
                for (let i = 1; i < lines.length; i++) {
                    const columns = lines[i].split(';');
                    if (columns.length >= 3) {
                        return { user: columns[1].trim(), pass: columns[2].trim() };
                    }
                }
            } catch (e) { console.error("Erro ao ler credenciais", e); }
            return null;
        }

        async function switchDynamicPlayer(url, btn) {
            document.querySelectorAll('.opt-btn').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            initPlayer(url);
        }

        async function switchPlayer(option, btn) {
            let url = player1Url;
            if (option === 2) url = player2Url;
            if (option === 3) url = player3Url;

            document.querySelectorAll('.opt-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            initPlayer(url);
        }

        window.onload = async () => {
            activeCredentials = await fetchCredentials();

            if (isGameContext) {
                initAssistFilters();
                loadAssistGames();
                setInterval(loadAssistGames, 15000);
            }

            if (currentDynamicStreams && currentDynamicStreams.length > 0) {
                // Já vem ordenado com EmbedTV primeiro
                renderPlayerOptions(currentDynamicStreams);
                const chosenStream = currentDynamicStreams[0];
                activePrimaryPlayerUrl = String(chosenStream.url || activePrimaryPlayerUrl);
                initPlayer(chosenStream.url);
                return;
            }

            renderPlayerOptions([]);
            initPlayer(player1Url);
        };
    </script>

</body>

    <!-- Modal de Sessão Expirada -->
    <div id="sessao-modal" style="
        display: none; position: fixed; inset: 0; z-index: 9999;
        background: rgba(5, 7, 10, 0.95);
        backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
        align-items: center; justify-content: center;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    ">
        <div style="
            background: #0f111a; border: 1px solid rgb(57 128 245 / 23%);
            border-radius: 20px; padding: 40px 32px; max-width: 400px; width: 90%;
            text-align: center; box-shadow: 0 0 60px rgb(57 127 245 / 23%);
            font-family: 'Outfit', sans-serif;
        ">
            <div style="font-size: 48px; margin-bottom: 16px;">🔒</div>
            <h2 style="color:#fff; font-size:20px; margin-bottom:10px;">Sessão encerrada</h2>
            <p style="color:#94a3b8; font-size:14px; line-height:1.6; margin-bottom:28px;">
                Sua conta foi acessada em outro dispositivo ou navegador.<br>
                Por segurança, apenas um acesso simultâneo é permitido.
            </p>
            <a href="login.php" style="
                display: inline-block;
                background: linear-gradient(135deg,#3b82f6,#2563eb);
                color:#fff; padding:13px 32px; border-radius:10px;
                font-weight:700; font-size:15px; text-decoration:none;
            ">Fazer Login Novamente</a>
        </div>
    </div>

    <script>
        // ---- Heartbeat no player: verifica sessão a cada 15 segundos ----
        (function() {
            const INTERVALO = 15000; // 15s — mais agressivo pois está em reprodução
            let ativo = true;

            async function verificarSessao() {
                if (!ativo) return;
                try {
                    const res = await fetch('ping.php', { cache: 'no-store' });
                    if (!res.ok) { bloquear(); return; }
                    const data = await res.json();
                    if (!data.valid) bloquear();
                } catch (e) {
                    // Falha de rede — não bloqueia imediatamente
                }
            }

            function bloquear() {
                ativo = false;
                clearInterval(intervalo);

                // Para qualquer player ativo antes de mostrar o modal
                try {
                    const iframe = document.getElementById('main-player');
                    if (iframe) iframe.src = 'about:blank';
                    if (typeof jwplayer === 'function') {
                        try { jwplayer('jw-player-container').stop(); } catch(e) {}
                    }
                } catch(e) {}

                document.getElementById('sessao-modal').style.display = 'flex';
            }

            // Verifica ao voltar para a aba (ex: usuário voltou após usar outra aba)
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) verificarSessao();
            });

            const intervalo = setInterval(verificarSessao, INTERVALO);
            setTimeout(verificarSessao, 5000);
        })();
    </script>
</html>
