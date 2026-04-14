<?php require_once __DIR__ . '/middleware.php';
$isAdmin = isset($usuario_logado['is_admin']) && $usuario_logado['is_admin'] == 1;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ElitePLAY - Canais ao Vivo</title>
    <link rel="icon" type="image/webp" href="assets/favicon.webp">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #05070a;
            --bg-card: rgba(255, 255, 255, 0.03);
            --bg-input: #0b0d14;
            --primary-blue: #3b82f6;
            --text-light: #ffffff;
            --text-muted: #94a3b8;
            --btn-dark: #1e293b;
            --pad-x: 15px;
            --accent-glow: rgba(59, 130, 246, 0.5);
            --glass-blur: 16px;
        }

        @media (min-width: 768px) {
            :root {
                --pad-x: 40px;
            }
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
            padding-bottom: 70px;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Background Blobs para profundidade */
        .background-blobs {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            filter: blur(100px);
            opacity: 0.4;
        }

        .blob { position: absolute; border-radius: 50%; }
        .blob-1 { width: 400px; height: 400px; background: #1d4ed8; top: -100px; right: -100px; }
        .blob-2 { width: 350px; height: 350px; background: #7e22ce; bottom: -50px; left: -100px; }
        .blob-3 { width: 300px; height: 300px; background: #0f172a; top: 40%; left: 40%; }

        /* Header e Busca */
        header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            padding: 14px var(--pad-x);
            background: rgba(15, 17, 26, 0.96);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            position: sticky;
            top: 0;
            z-index: 200;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.32);
            gap: 14px;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.6px;
        }

        .logo span {
            color: var(--text-muted);
            font-weight: normal;
        }

        .user-icon {
            background-color: var(--bg-input);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }

        .admin-link {
            background-color: var(--bg-input);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.2s;
        }

        .admin-link:hover {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .search-container {
            width: 100%;
            order: 3;
        }

        @media (min-width: 992px) {
            .search-container {
                order: 0;
                width: min(680px, 58vw);
                margin: 0 auto;
            }
        }

        .search-input {
            width: 100%;
            background-color: #0a0d16;
            border: 1px solid rgba(255, 255, 255, 0.09);
            border-radius: 12px;
            padding: 14px 20px;
            color: var(--text-light);
            font-size: 15px;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .search-input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
        }

        .search-input::placeholder {
            color: var(--text-muted);
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 72px;
            left: 0;
            width: 248px;
            height: calc(100vh - 72px);
            background: linear-gradient(180deg, rgba(15,17,26,0.97) 0%, rgba(10,12,20,0.97) 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            z-index: 100;
            padding: 16px 0 22px 0;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.14) transparent;
            transition: transform 0.3s ease;
        }

        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

        .sidebar-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #8ea2c7;
            padding: 14px 20px 10px;
            font-weight: 700;
        }

        .sidebar .cat-btn {
            background: transparent;
            border: none;
            color: #b7c4da;
            padding: 11px 20px;
            border-radius: 0;
            cursor: pointer;
            white-space: normal;
            font-weight: 700;
            font-size: 14px;
            line-height: 1.2;
            display: flex;
            align-items: center;
            gap: 9px;
            transition: all 0.2s;
            width: 100%;
            text-align: left;
            border-left: 3px solid transparent;
        }

        .sidebar .cat-btn:hover {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-light);
        }

        .sidebar .cat-btn.active {
            background: rgba(55, 114, 255, 0.1);
            color: var(--primary-blue);
            border-left-color: var(--primary-blue);
        }

        /* Layout principal com sidebar */
        .main-content {
            margin-left: 248px;
            min-height: 100vh;
        }

        .jogos-section, .channels-section {
            padding: 0 var(--pad-x);
            margin-top: 15px;
        }

        .sidebar-toggle {
            display: none;
            background: transparent;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
            order: -1; /* Mantém à esquerda do logo se necessário */
        }

        .close-sidebar {
            display: none;
            position: absolute;
            top: 15px;
            right: 15px;
            background: transparent;
            border: none;
            color: white;
            font-size: 30px;
            cursor: pointer;
            z-index: 310;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 99;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                background: #0f111a; /* Cor sólida para mobile */
                z-index: 300;
                top: 0;
                height: 100vh;
                box-shadow: 10px 0 30px rgba(0,0,0,0.5);
                padding-top: 60px;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar-toggle {
                display: flex;
            }
            .close-sidebar {
                display: block;
            }
            .sidebar-overlay {
                z-index: 290;
            }
            .sidebar-overlay.open {
                display: block;
            }
            .main-content {
                margin-left: 0;
            }
            .admin-link {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }
        }

        .section-title {
            margin: 0;
            padding: 0;
        }

        /* Seção de Jogos */

        .jogos-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            padding: 10px 0;
        }

        .carousel-wrapper {
            position: relative;
        }

        .carousel-wrapper .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            background: rgba(15, 17, 26, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.2s;
            backdrop-filter: blur(5px);
        }

        .carousel-wrapper .carousel-arrow:hover {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-wrapper .carousel-arrow.arrow-left { left: -8px; }
        .carousel-wrapper .carousel-arrow.arrow-right { right: -8px; }

        .jogos-carousel {
            display: flex;
            gap: 14px;
            padding: 10px 0;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
            cursor: grab;
        }

        .jogos-carousel.dragging { cursor: grabbing; scroll-behavior: auto; scroll-snap-type: none; }
        .jogos-carousel::-webkit-scrollbar { display: none; }

        .jogos-carousel .carousel-item {
            min-width: 300px;
            max-width: 340px;
            flex-shrink: 0;
            scroll-snap-align: start;
            user-select: none;
        }

        .jogos-carousel .carousel-item .game-card { width: 100%; }

        .jogo-card-horizontal {
            background-color: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            flex-direction: row;
            align-items: center;
            cursor: pointer;
            transition: transform 0.2s, border-color 0.2s;
        }

        .jogo-card-horizontal:active {
            transform: scale(0.98);
        }

        .jogo-img {
            width: 100px;
            height: 90px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .jogo-info {
            padding: 12px 15px;
            flex-grow: 1;
            min-width: 0;
        }

        .jogo-league {
            font-size: 10px;
            color: var(--primary-blue);
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .jogo-title {
            font-size: 14px;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text-light);
        }

        /* Grid de Canais */
        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            padding: 0 var(--pad-x);
        }

        .card {
            background-color: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .card-img-container {
            width: 100%;
            aspect-ratio: 16 / 9;
            background-color: #0b0d14;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .card-img-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .card-content {
            padding: 12px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-title {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-status {
            background-color: var(--bg-input);
            color: var(--text-muted);
            font-size: 10px;
            padding: 6px 8px;
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 12px;
            min-height: 44px;
            justify-content: center;
        }

        .epg-category {
            font-size: 9px;
            color: var(--primary-blue);
            font-weight: 600;
            text-transform: uppercase;
        }

        .epg-title {
            color: #fff;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-actions {
            margin-top: auto;
        }

        .btn-watch {
            width: 100%;
            background-color: var(--primary-blue);
            color: var(--text-light);
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            transition: opacity 0.2s;
        }

        .btn-watch:active {
            opacity: 0.8;
            transform: scale(0.98);
        }

        @media (min-width: 768px) {
            header {
                flex-wrap: nowrap;
                padding: 20px var(--pad-x);
            }
            .search-container {
                width: auto;
                order: initial;
                flex-grow: 1;
                max-width: 500px;
                margin: 0 30px;
            }
            .section-title {
                font-size: 22px;
            }
            .jogos-grid {
                grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            }
            .jogo-card-horizontal {
                flex-direction: column;
            }
            .jogo-img {
                width: 100%;
                height: 150px;
            }
            .grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 20px;
            }
            .card-img-container {
                height: 160px;
            }
            .card-title {
                font-size: 15px;
            }
            .card-status {
                font-size: 11px;
                padding: 8px 10px;
            }
            .btn-watch {
                font-size: 13px;
                padding: 12px;
            }
        }

        /* Estilos Premium para Cards de Jogos */
        :root {
            --card-premium-bg: rgba(255, 255, 255, 0.03);
            --card-premium-border: rgba(255, 255, 255, 0.08);
            --accent-glow: rgba(59, 130, 246, 0.5);
            --success-color: #10b981;
            --glass-blur: 16px;
        }

        .jogos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
            width: 100%;
            padding: 15px 0;
        }

        .game-card {
            background: var(--card-premium-bg);
            border: 1px solid var(--card-premium-border);
            border-radius: 1.25rem;
            backdrop-filter: blur(var(--glass-blur));
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            cursor: default;
        }

        .game-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
            background: rgba(255, 255, 255, 0.06);
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
            margin-bottom: 0px;
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
            /* letter-spacing: 1.2px; */
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
            font-size: 0.85rem;
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
        }

        .watch-premium-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
            filter: brightness(1.1);
        }

        /* Seções de Grupos de Jogos */
        .section-group-premium {
            margin-bottom: 2rem;
            width: 100%;
        }

        .section-header-premium {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--card-premium-border);
        }

        .section-header-premium h2 {
            font-size: 1rem;
            text-transform: uppercase;
            background: linear-gradient(to right, #fff, #cdd7e4);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .count-badge-premium {
            background: #353535;
            color: white;
            padding: 0.1rem 0.4rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .jogos-grid {
                grid-template-columns: 1fr;
            }
            .card-premium-content {
                padding: 1rem;
            }
            .card-banner {
                height: 30px;
            }
            .banner-title {
                font-size: 0.60rem;
            }
            .status-badge, .live-indicator {
                padding: 0.15rem 0.55rem;
            }
            .game-datetime .game-time {
                font-size: 1.1rem;
            }
            .watch-premium-button {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>

    <div class="background-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <header>
        <div style="display: flex; align-items: center; gap: 15px;">
            <button class="sidebar-toggle" id="sidebar-toggle" onclick="toggleSidebar()">☰</button>
            <div class="logo">Elite<span>PLAY</span></div>
        </div>
        <div class="search-container">
            <input type="text" class="search-input" placeholder="Pesquisar canais ou eventos...">
        </div>
        <?php if ($isAdmin): ?>
        <a href="admin.php" class="admin-link" title="Painel Admin">⚙️</a>
        <?php endif; ?>
        <div class="user-icon">👤</div>
    </header>

    <!-- Sidebar -->
    <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>
    <nav class="sidebar" id="sidebar">
        <button class="close-sidebar" onclick="toggleSidebar()">×</button>
        <div class="categories" id="categories-container">
            <button class="cat-btn active">▦ Carregando...</button>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <div class="main-content">
        <div class="jogos-section" id="jogos-section" style="display: none;">
            <div id="jogos-horizontal-wrapper"></div>
        </div>

        <div class="channels-section">
            <div class="section-header-premium" style="margin-top: 35px; align-items: baseline;">
                <h2 class="section-title">Todos os Canais</h2>
            </div>

            <div class="grid" id="channels-grid"></div>
        </div>
    </div>

    <script>
        const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        const apiUrl = 'https://embed.70noticias.com.br/?api=1&t=live&c=all';
        const epgUrl = 'https://embedtv.cv/api/epgs';
        const jogosUrl = 'https://embedtv.cv/api/jogos';
        const embedtvChannelsUrl = 'https://embedtv.cv/api/channels';
        const localDateYmd = (d = new Date()) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        
        const CACHE_TTL = 5 * 60 * 1000; // 5 minutos
        const CACHE_KEYS = {
            channels: 'eliteplay_channels',
            jogos: 'eliteplay_jogos'
        };

        let allChannels = [];
        let epgData = {};
        let allJogos = [];
        let allJogosProcessed = [];
        let embedtvChannels = [];
        let lastScrapedScores = [];
        let dataLoadedFromCache = false;

        // Cache utilities
        function getCache(key) {
            try {
                const cached = sessionStorage.getItem(key);
                if (!cached) return null;
                const { data, timestamp } = JSON.parse(cached);
                if (Date.now() - timestamp > CACHE_TTL) {
                    sessionStorage.removeItem(key);
                    return null;
                }
                return data;
            } catch { return null; }
        }

        function setCache(key, data) {
            try {
                sessionStorage.setItem(key, JSON.stringify({ data, timestamp: Date.now() }));
            } catch {}
        }

        // Fetch com stale-while-revalidate
        async function fetchWithCache(url, cacheKey, forceRefresh = false) {
            if (!forceRefresh) {
                const cached = getCache(cacheKey);
                if (cached) return cached;
            }
            try {
                const res = await fetch(url);
                const data = await res.json();
                setCache(cacheKey, data);
                return data;
            } catch (e) {
                const cached = getCache(cacheKey);
                if (cached) return cached;
                throw e;
            }
        }

        // Verifica se é volta do navegador
        function isBackFromAssistir() {
            return performance.navigation.type === 2 || sessionStorage.getItem('eliteplay_from_assistir');
        }

        // --- Lógica de Scraping e Matching (do Dash Premium) ---
        const slugify = (text) => {
            if (!text) return '';
            return text.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/\bfc\b|\bcf\b|\bde munique\b|\bunited\b|\batletico\b|\batlético\b/g, '')
                .replace(/[^a-z0-9]/g, '').trim();
        };

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
            const slug = teamName.toLowerCase()
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

        async function fetchLiveScores() {
            const TARGET = 'https://www.placardefutebol.com.br/jogos-de-hoje';
            const PROXIES = [
                'https://corsproxy.io/?',
                'https://api.allorigins.win/raw?url=',
                'https://api.codetabs.com/v1/proxy?quest='
            ];

            let html = '';
            for (const proxy of PROXIES) {
                try {
                    const url = proxy + encodeURIComponent(TARGET + '?t=' + Date.now());
                    const response = await fetch(url);
                    const text = await response.text();
                    if (text.length > 500 && !text.includes('"error"') && text.includes('status-name')) {
                        html = text;
                        console.log('[ElitePLAY Scraper] Proxy OK:', proxy.split('/')[2]);
                        break;
                    }
                } catch (e) { 
                    console.warn('[ElitePLAY Scraper] Proxy falhou:', proxy.split('/')[2]);
                }
            }

            if (!html) { 
                console.warn('[ElitePLAY Scraper] Todos os proxies falharam!'); 
                return []; 
            }

            try {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Pega TODOS os links de jogos (qualquer liga)
                const allLinks = Array.from(doc.querySelectorAll('a[href]')).filter(a => {
                    const href = a.getAttribute('href') || '';
                    return href.includes('.html') && a.querySelector('.status-name') && a.querySelector('h5');
                });

                const results = allLinks.map(match => {
                    const homeTeam = match.querySelector('h5.text-right.team_link')?.innerText?.trim() || 
                                     match.querySelector('h5.text-right')?.innerText?.trim() || '';
                    const awayTeam = match.querySelector('h5.text-left.team_link')?.innerText?.trim() || 
                                     match.querySelector('h5.text-left')?.innerText?.trim() || '';
                    const scoreElements = match.querySelectorAll('.match-score .badge');
                    const homeScore = scoreElements[0]?.innerText?.trim() || '0';
                    const awayScore = scoreElements[1]?.innerText?.trim() || '0';
                    const statusText = match.querySelector('.status-name')?.innerText?.trim() || '';
                    const leagueName = match.querySelector('.match-card-league-name')?.innerText?.trim() || '';
                    return { homeTeam, awayTeam, homeScore, awayScore, statusText, leagueName };
                }).filter(r => r.homeTeam && r.awayTeam);

                // LOG LIMPO: Apenas uma linha no console para indicar atualização
                console.log(`[ElitePLAY] Placar Atualizado: ${results.length} jogos encontrados.`);
                return results;
            } catch (e) { 
                console.error('[ElitePLAY Scraper] Erro no parse:', e);
                return []; 
            }
        }

        function matchGameScores(apiGames, scrapedScores) {
            return apiGames.map(game => {
                const homeName = game.data?.teams?.home?.name || '';
                const awayName = game.data?.teams?.away?.name || '';
                const homeSlug = slugify(homeName);
                const awaySlug = slugify(awayName);
                
                // --- Matching por NOME DE EQUIPE (slugify) ---
                let match = scrapedScores.find(ls => {
                    const lsHomeSlug = slugify(ls.homeTeam);
                    const lsAwaySlug = slugify(ls.awayTeam);
                    return (lsHomeSlug.includes(homeSlug) || homeSlug.includes(lsHomeSlug)) &&
                           (lsAwaySlug.includes(awaySlug) || awaySlug.includes(lsAwaySlug));
                });

                if (!match) {
                    match = scrapedScores.find(ls => {
                        const lsHomeSlug = slugify(ls.homeTeam);
                        const lsAwaySlug = slugify(ls.awayTeam);
                        const homeMatch = homeSlug.length > 3 && (lsHomeSlug.includes(homeSlug) || homeSlug.includes(lsHomeSlug));
                        const awayMatch = awaySlug.length > 3 && (lsAwaySlug.includes(awaySlug) || awaySlug.includes(lsAwaySlug));
                        return homeMatch || awayMatch;
                    });
                }
                
                let isActuallyLive = false;
                let isActuallyFinished = false;
                let homeScore = '';
                let awayScore = '';
                let statusText = game.data?.time || 'HOJE';

                // --- ÚNICA REFERÊNCIA: Site do Placar (placardefutebol.com.br) ---
                if (match) {
                    statusText = match.statusText; // Texto original do site (ex: "HOJE 18:00", "45'", "INTERVALO", "Finalizado")
                    homeScore = match.homeScore;
                    awayScore = match.awayScore;

                    const statusLow = match.statusText.toLowerCase();

                    // Detecta ENCERRADO
                    const scraperFinished = statusLow.includes('fin') || statusLow.includes('fim') || statusLow.includes('enc');

                    // Detecta AO VIVO (minutos, intervalo, tempos, etc)
                    const scraperLive = match.statusText.includes("'") || 
                                     statusLow.includes('min') || 
                                     statusLow.includes('int') || 
                                     statusLow.includes('andamento') || 
                                     statusLow.includes('vivo') || 
                                     statusLow.includes('2t') || 
                                     statusLow.includes('1t') ||
                                     statusLow.includes('acrésc') || 
                                     statusLow.includes('penal');

                    // Detecta HORÁRIO agendado: "18:00", "HOJE 18:00", "Hoje 21:30"
                    const timeMatch = match.statusText.match(/(\d{1,2}):(\d{2})/);
                    const isScheduled = timeMatch && !scraperLive && !scraperFinished;

                    if (scraperLive) {
                        isActuallyLive = true;
                    } else if (scraperFinished) {
                        isActuallyFinished = true;
                    } else if (isScheduled) {
                        // Jogo agendado — mantém como Agendado
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

        function createGameCardHTML(jogo) {
            const homeName = jogo.data?.teams?.home?.name || 'Time A';
            const awayName = jogo.data?.teams?.away?.name || 'Time B';
            
            // Prioriza as imagens vindas do JSON da API (webp/png)
            const homeLogo = jogo.data?.teams?.home?.image || getTeamLogoUrl(homeName);
            const awayLogo = jogo.data?.teams?.away?.image || getTeamLogoUrl(awayName);
            
            const homeInitials = getInitials(homeName);
            const awayInitials = getInitials(awayName);
            
            const isLive = jogo.status_label === 'Ao Vivo';
            const isFinished = jogo.status_label === 'Encerrado';
            const isInterval = (jogo.statusText || '').toLowerCase().includes('int');
            
            // Usa o statusText do scraper (já definido no match). Se não houver match, usa o horário da API.
            const statusLabel = jogo.statusText || jogo.data?.time || 'HOJE';
            
            const homeScore = jogo.homeScore !== undefined ? jogo.homeScore : (isLive || isFinished ? '0' : '');
            const awayScore = jogo.awayScore !== undefined ? jogo.awayScore : (isLive || isFinished ? '0' : '');

            let originalEmbedTvUrl = jogo.players && jogo.players[0] ? jogo.players[0] : '';
            const playerUrlOpcao1 = originalEmbedTvUrl;
            const safeTitle = jogo.title.replace(/'/g, "\\'");

            // Tenta encontrar o canal correspondente para pegar as qualidades (FHD, HD, SD) da nova API
            let gameStreamsStr = '';
            const jogoId = (jogo.id !== undefined && jogo.id !== null && String(jogo.id).trim() !== '')
                ? String(jogo.id)
                : ((jogo.data?.timer?.start ? `idx_${jogo.data.timer.start}` : `idx_${normalizeName(jogo.title)}`));

            const OVERRIDE_REMOVE_ORIGINAL_KEY = '__ORIGINAL_API__';
            const adminOverrides = window.adminJogosOverrides || {};
            console.log('[INDEX] adminOverrides keys:', Object.keys(adminOverrides), 'jogoId:', jogoId);
            const overrideList = jogoId && Array.isArray(adminOverrides[jogoId]) ? adminOverrides[jogoId] : [];
            const hasAdminOverride = overrideList.length > 0;
            console.log('[INDEX] hasAdminOverride:', hasAdminOverride, 'overrideList:', overrideList);

            let detectedStreams = [];
            const channelsToUse = (typeof window.channelsForGames !== 'undefined' && window.channelsForGames.length > 0)
                ? window.channelsForGames
                : allChannels;

            if (typeof channelsToUse !== 'undefined' && channelsToUse.length > 0) {
                const gameTitleNorm = normalizeName(jogo.title);
                let matchedChannel = channelsToUse.find(c => {
                    const chanNameNorm = normalizeName(c.nome);
                    return gameTitleNorm.includes(chanNameNorm) || chanNameNorm.includes(gameTitleNorm);
                });

                if (!matchedChannel && originalEmbedTvUrl) {
                    let urlId = originalEmbedTvUrl.split('id=').pop().split('&')[0];
                    if (!urlId || urlId.includes('/') || urlId === originalEmbedTvUrl) {
                        urlId = originalEmbedTvUrl.split('?')[0].replace(/\/$/, '').split('/').pop();
                    }
                    if (urlId) {
                        const idNorm = normalizeName(urlId);
                        matchedChannel = channelsToUse.find(c => {
                            const cn = normalizeName(c.nome);
                            return cn.includes(idNorm) || idNorm.includes(cn);
                        });
                    }
                }

                if (matchedChannel && matchedChannel.streams && matchedChannel.streams.length > 0) {
                    detectedStreams = matchedChannel.streams.slice();
                }
            }

            // Se tem override E detectedStreams está vazio, tenta encontrar o canal original via embed URL
            if (hasAdminOverride && detectedStreams.length === 0 && originalEmbedTvUrl) {
                let urlId = originalEmbedTvUrl.split('id=').pop().split('&')[0];
                if (!urlId || urlId.includes('/') || urlId === originalEmbedTvUrl) {
                    urlId = originalEmbedTvUrl.split('?')[0].replace(/\/$/, '').split('/').pop();
                }
                if (urlId) {
                    const idNorm = normalizeName(urlId);
                    const matchByUrl = channelsToUse.find(c => {
                        const cn = normalizeName(c.nome);
                        return cn.includes(idNorm) || idNorm.includes(cn);
                    });
                    if (matchByUrl && matchByUrl.streams) {
                        detectedStreams = matchByUrl.streams.slice();
                    }
                }
            }

            let finalStreams = [...detectedStreams];
            console.log('[DEBUG] jogoId:', jogoId, 'hasOverride:', hasAdminOverride, 'detectedStreams length:', detectedStreams.length);

            // se tem override, processa com controle total
            if (hasAdminOverride && typeof channelsToUse !== 'undefined') {
                const overrideStreams = [];

                overrideList.forEach((ov) => {
                    const ovName = String(ov?.name || '').trim();
                    if (!ovName) return;

                    // __ORIGINAL_API__ é para controlar remoção de qualidades do canal original
                    if (ovName === OVERRIDE_REMOVE_ORIGINAL_KEY) {
                        if (!ov.remove_channel && Array.isArray(ov.remove_qualities) && ov.remove_qualities.length > 0) {
                            // Remove as qualidades especificadas do original
                            finalStreams = finalStreams.filter(s => {
                                const sq = normalizeName(String(s.name || ''));
                                return !ov.remove_qualities.some(rq => sq.includes(normalizeName(rq)) || normalizeName(rq).includes(sq));
                            });
                        } else if (ov.remove_channel) {
                            // Remove completamente o canal original
                            finalStreams = [];
                        }
                        return;
                    }

                    const ovNorm = normalizeName(ovName);

                    // Procura canal pelo nome exato ou similar
                    const matchChan = channelsToUse.find(c => {
                        const cnNorm = normalizeName(c.nome);
                        return cnNorm === ovNorm ||
                               ovNorm.includes(cnNorm) ||
                               cnNorm.includes(ovNorm) ||
                               cnNorm.replace(/[^a-z0-9]/g, '').includes(ovNorm.replace(/[^a-z0-9]/g, ''));
                    });

                    if (ov.remove_channel) return;
                    if (!matchChan || !matchChan.streams) return;

                    // CONTROLE TOTAL: usa SOMENTE as qualidades especificadas no override
                    if (Array.isArray(ov.qualities) && ov.qualities.length > 0) {
                        ov.qualities.forEach(q => {
                            // Procura stream que contenha a qualidade especificada
                            const qUpper = String(q).toUpperCase();
                            const stream = matchChan.streams.find(s => {
                                const sNameUpper = String(s.name).toUpperCase();
                                return sNameUpper.includes(qUpper) || qUpper.includes(sNameUpper);
                            });
                            if (stream && !overrideStreams.some(x => x.url === stream.url)) {
                                // Primeiro verifica se essa URL já existe em detectedStreams (usa nome original)
                                const existingStream = detectedStreams.find(d => d.url === stream.url);
                                if (existingStream) {
                                    overrideStreams.push({ name: existingStream.name, url: stream.url });
                                    return;
                                }
                                // Verifica se é stream do EmbedTV (URL contém mr.s27-usa-cloudfront-net.online)
                                const isEmbedtv = stream.url.includes('mr.s27-usa-cloudfront-net.online');
                                if (isEmbedtv) {
                                    // EmbedTV: nome no formato ELITE-CHANNELNAME (sem qualidade)
                                    overrideStreams.push({ name: `ELITE-${ovName.toUpperCase()}`, url: stream.url });
                                } else {
                                    // Original API: usa o nome original do stream
                                    overrideStreams.push({ name: stream.name, url: stream.url });
                                }
                            }
                        });
                    } else {
                        // Se não especificar qualities, adiciona todos os streams do canal
                        matchChan.streams.forEach(s => {
                            if (!overrideStreams.some(x => x.url === s.url)) {
                                // Primeiro verifica se essa URL já existe em detectedStreams
                                const existingStream = detectedStreams.find(d => d.url === s.url);
                                if (existingStream) {
                                    overrideStreams.push({ name: existingStream.name, url: s.url });
                                    return;
                                }
                                // Verifica se é stream do EmbedTV
                                const isEmbedtv = s.url.includes('mr.s27-usa-cloudfront-net.online');
                                if (isEmbedtv) {
                                    overrideStreams.push({ name: `ELITE-${ovName.toUpperCase()}`, url: s.url });
                                } else {
                                    overrideStreams.push({ name: s.name, url: s.url });
                                }
                            }
                        });
                    }
                });

                // Se tem override, combina original (mantém nomes) + overrides (ELITE 02, 03...)
                if (overrideStreams.length > 0) {
                    finalStreams = [...finalStreams, ...overrideStreams];
                }
            }

            console.log('[DEBUG] finalStreams:', finalStreams.length, finalStreams.map(s => s.name));

            if (finalStreams.length > 0) {
                gameStreamsStr = btoa(unescape(encodeURIComponent(JSON.stringify(finalStreams))));
            }

            const competition = jogo.scrapedLeague || jogo.data?.league || 'Futebol';
            const competitionSlug = slugify(competition);
            const bannerMap = [
                { check: s => s.includes('brasileiro') || s.includes('serie-a') || s.includes('serie-b') || s.includes('brasileirao'), img: 'brasileiro.webp' },
                { check: s => s.includes('italiano') || s.includes('calcio'), img: 'italiano.webp' },
                { check: s => s.includes('alemao') || s.includes('bundesliga'), img: 'alemao.webp' },
                { check: s => s.includes('ingles') || s.includes('premier') || s.includes('championship'), img: 'ingles.webp' },
                { check: s => s.includes('portugues') || s.includes('campeonato-portugues') || s.includes('liga-portugal') || s.includes('primeira-liga'), img: 'portugues.webp' },
                { check: s => s.includes('espanhol') || s.includes('laliga') || s.includes('la-liga'), img: 'espanhol.webp' },
                { check: s => s.includes('frances') || s.includes('campeonato-frances') || s.includes('ligue-1'), img: 'franca.webp' },
            ];
            const matchedBanner = bannerMap.find(b => b.check(competitionSlug));
            const bannerStyle = matchedBanner ? `style="background-image: url('api jogos/${matchedBanner.img}');"` : '';

            return `
                <div class="game-card">
                    <div class="card-banner" ${bannerStyle}>
                        <div class="banner-overlay"></div>
                        <span class="banner-title">${competition}</span>
                    </div>
                    <div class="card-premium-content">
                        ${(isLive || isFinished) ? `
                        <div class="card-header-premium">
                            <span class="status-badge ${isInterval ? 'interval' : (isLive ? 'live' : 'finished')}">${statusLabel}</span>
                            <span class="kickoff-time">${isLive ? '<span class="live-indicator">AO VIVO</span>' : (jogo.data?.time || '')}</span>
                        </div>
                        ` : ''}
                        <div class="teams-premium-container">
                            <div class="team-premium">
                                <div class="team-logo-premium">
                                    <img src="${homeLogo}" alt="${homeName}" onerror="teamImgFallback(this, '${homeName.replace(/'/g, "\\'") }', '${homeInitials}')">
                                </div>
                                <span class="team-name-premium">${homeName}</span>
                            </div>
                            ${(isLive || isFinished) ? `
                                <div class="score-premium-container">
                                    <div class="score-premium-display">
                                        <span>${homeScore}</span>
                                        <span class="score-divider">-</span>
                                        <span>${awayScore}</span>
                                    </div>
                                    <span class="score-label">${isFinished ? 'Final' : 'Placar'}</span>
                                </div>
                            ` : `
                                <div class="score-premium-container" style="opacity: 0.3; border: none; background: transparent;">
                                    <div class="score-premium-display" style="font-size: 1rem;">VS</div>
                                </div>
                            `}
                            <div class="team-premium">
                                <div class="team-logo-premium">
                                    <img src="${awayLogo}" alt="${awayName}" onerror="teamImgFallback(this, '${awayName.replace(/'/g, "\\'") }', '${awayInitials}')">
                                </div>
                                <span class="team-name-premium">${awayName}</span>
                            </div>
                        </div>
                        <div class="card-footer-premium">
                            <div class="game-datetime" ${isFinished ? 'style="flex-direction: row; gap: 8px; justify-content: center; width: 100%; align-items: center;"' : ''}>
                                <span class="game-date">${(() => {
                                    const ts = jogo.data?.timer?.start;
                                    if (!ts) return 'Hoje';
                                    const d = new Date(ts * 1000);
                                    const dd = String(d.getDate()).padStart(2, '0');
                                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                                    const yyyy = d.getFullYear();
                                    return `${dd}/${mm}/${yyyy}`;
                                })()}</span>
                                <span class="game-time">${(() => {
                                    const ts = jogo.data?.timer?.start;
                                    if (!ts) return '--h--';
                                    const d = new Date(ts * 1000);
                                    const hh = String(d.getHours()).padStart(2, '0');
                                    const mi = String(d.getMinutes()).padStart(2, '0');
                                    return `${hh}h${mi}`;
                                })()}</span>
                            </div>
                            ${!isFinished ? `<button onclick="enviarParaPlayer('${playerUrlOpcao1}', '${safeTitle}', '${jogo.image}', '${originalEmbedTvUrl}', '', '${gameStreamsStr}')" class="watch-premium-button" style="flex: 1;">Assistir Agora</button>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        async function fetchChannels() {
            try {
                const fromAssistir = sessionStorage.getItem('eliteplay_from_assistir') === '1';
                
                // Puxa as 2 fontes em paralelo
                let res70, resEmbed;
                if (fromAssistir) {
                    [res70, resEmbed] = await Promise.all([
                        fetchWithCache(apiUrl, CACHE_KEYS.channels).catch(() => ({})),
                        fetchWithCache(embedtvChannelsUrl, CACHE_KEYS.channels + '_embed').catch(() => null)
                    ]);
                } else {
                    [res70, resEmbed] = await Promise.all([
                        fetchWithCache(apiUrl, CACHE_KEYS.channels).catch(() => ({})),
                        fetchWithCache(embedtvChannelsUrl, CACHE_KEYS.channels + '_embed').catch(() => null)
                    ]);
                }

                let groupedChannels = {};

                // --- 1. Processa 70Noticias (Prioridade - Qualidades FHD/HD) ---
                const parseName = (fullName) => {
                    let original = fullName.trim();
                    const patterns = [/(?:FHD|HD|SD|4K|1080p|720p)/i, /\[LEG\]/i, /\(ALT\)/i, /\[ALT\]/i, /(?:\s|^)ALT(?:\s|$)/i, /(?:\s|^)\*(?:\s|$)/i];
                    
                    let splitIndex = original.length;
                    for (const p of patterns) {
                        const m = original.match(p);
                        if (m) {
                            const idx = original.search(p);
                            if (idx > 0 && idx < splitIndex) splitIndex = idx;
                        }
                    }
                    
                    const baseName = original.substring(0, splitIndex).trim();
                    const quality = original.substring(baseName.length).trim();
                    return { baseName: baseName || original, quality: quality || 'Principal' };
                };

                for (const category in res70) {
                    const canais = res70[category];
                    if (Array.isArray(canais)) {
                        canais.forEach(c => {
                            const parsed = parseName(c.nome);
                            const baseName = parsed.baseName || c.nome;
                            const is4K = parsed.quality === '4K' || c.nome.toLowerCase().includes('4k');
                            const norm = normalizeName(baseName);
                            const channelCategory = c.categoria || category || 'Outros';

                            if (!groupedChannels[norm]) {
                                groupedChannels[norm] = {
                                    nome: baseName,
                                    iframe_url: c.link,
                                    categoria: is4K ? 'CANAIS 4K' : channelCategory,
                                    logo: c.capa || '',
                                    streams: []
                                };
                            } else if (is4K) {
                                groupedChannels[norm].categoria = 'CANAIS 4K';
                            }
                            // Evita duplicatas de URL na mesma API
                            if (!groupedChannels[norm].streams.some(s => s.url === c.link)) {
                                // Nome completo: CANAL-QUALITY (ex: ESPN-FHD)
                                const qualityName = parsed.quality ? `${parsed.quality}` : 'Principal';
                                groupedChannels[norm].streams.push({ name: qualityName, url: c.link });
                            }
                        });
                    }
                }

                // --- 2. Processa EmbedTV (Adiciona como Opção se já existe ou cria novo) ---
                if (resEmbed && resEmbed.channels) {
                    embedtvChannels = resEmbed.channels; // Cache para fallback de logos
                    
                    // Mapa de IDs para nomes de categorias da EmbedTV
                    const embedCatMap = {};
                    if (resEmbed.categories) {
                        resEmbed.categories.forEach(ct => embedCatMap[ct.id] = ct.name);
                    }

                    resEmbed.channels.forEach(c => {
                        const norm = normalizeName(c.name);

                        // Monta URL m3u8 usando o ID do canal
                        const m3u8Url = `https://mr.s27-usa-cloudfront-net.online/fontes/mr/${c.id}.m3u8`;

                        // Determina a categoria da EmbedTV (pega a primeira válida que não seja 'Todos')
                        let catName = 'EmbedTV';
                        if (c.categories && Array.isArray(c.categories)) {
                            const validCatId = c.categories.find(id => id !== 0);
                            if (validCatId !== undefined && embedCatMap[validCatId]) {
                                catName = embedCatMap[validCatId];
                            }
                        }

                        if (groupedChannels[norm]) {
                            if (!groupedChannels[norm].streams.some(s => s.url === m3u8Url)) {
                                groupedChannels[norm].streams.push({ name: 'EmbedTV', url: m3u8Url });
                            }
                            // Armazena o ID do embedtv para uso no logo dinâmico
                            if (!groupedChannels[norm].embedtv_id) {
                                groupedChannels[norm].embedtv_id = c.id;
                            }
                            // Usa logo da EmbedTV como fallback se não tiver logo da 70noticias
                            if (!groupedChannels[norm].logo && c.image) {
                                groupedChannels[norm].logo = c.image;
                            }
                        } else {
                            groupedChannels[norm] = {
                                nome: c.name,
                                iframe_url: c.url,
                                categoria: catName,
                                logo: c.image || '',
                                embedtv_id: c.id,
                                streams: [{ name: 'EmbedTV', url: m3u8Url }]
                            };
                        }
                    });
                }

                let combinedChannels = Object.values(groupedChannels);

                // Remove canais com [H265] no nome e filtra streams com [H265]
                combinedChannels = combinedChannels
                    .filter(c => !c.nome.includes('[H265]'))
                    .map(c => ({
                        ...c,
                        streams: c.streams.filter(s => !s.name.includes('[H265]'))
                    }));

                // --- 4. Refinamento de Categorização (Faz o "geral" pedido pelo usuário) ---
                // Mapeamento de keywords para categorias conhecidas
                const keywordMap = {
                    'premiere': 'PREMIERE',
                    'espn': 'ESPN',
                    'sportv': 'SPORTV',
                    'telecine': 'TELECINE',
                    'hbo': 'HBO',
                    'combate': 'LUTAS',
                    'ufc': 'LUTAS',
                    'globo': 'GLOBO',
                    'record': 'RECORD',
                    'band': 'BAND',
                    'sbt': 'SBT',
                    'cnn': 'NOTICIAS',
                    'cartoon': 'INFANTIL',
                    'disney': 'INFANTIL',
                    'discovery': 'VARIEDADES',
                    'max': 'FILMES E SERIES', // HBO Max / Max
                    'telecine': 'TELECINE',
                    'paramount': 'FILMES E SERIES',
                    'warner': 'FILMES E SERIES',
                    'axn': 'FILMES E SERIES',
                    'universal': 'FILMES E SERIES',
                    'fox': 'FILMES E SERIES',
                    'star': 'FILMES E SERIES',
                    'prime video': 'PrimeVideo',
                };

                combinedChannels.forEach(chan => {
                    const lowName = chan.nome.toLowerCase();
                    // Se a categoria for genérica ou de uma API específica, tenta mover para uma categoria global
                    if (['EmbedTV', 'CineTve', 'Outros', 'GERAL', 'TV', 'FILMES E SERIES'].includes(chan.categoria)) {
                        for (const kw in keywordMap) {
                            if (lowName.includes(kw)) {
                                chan.categoria = keywordMap[kw];
                                break;
                            }
                        }
                    }
                    // Harmonização: Garante que canais importantes estejam nas categorias principais
                    if (lowName.includes('espn') && chan.categoria !== 'ESPN') chan.categoria = 'ESPN';
                    if (lowName.includes('premiere') && chan.categoria !== 'PREMIERE') chan.categoria = 'PREMIERE';
                    if (lowName.includes('telecine') && chan.categoria !== 'TELECINE') chan.categoria = 'TELECINE';
                    if (lowName.includes('globo') && chan.categoria !== 'GLOBO') chan.categoria = 'GLOBO';
                    // Requerimento do usuário: MAX e HBO MAX na categoria HBO
                    if ((lowName.includes('hbo') || lowName.includes('max')) && chan.categoria !== 'HBO') chan.categoria = 'HBO';
                });

                if (combinedChannels.length > 0) {
                    allChannels = combinedChannels;
                    window.channelsForGames = allChannels;
                    renderCategories(allChannels);
                    renderChannels(allChannels);
                    updateMainCounter(allChannels.length);
                    
                    if (allJogosProcessed && allJogosProcessed.length > 0) {
                         renderHorizontalJogos(allJogosProcessed);
                    }
                    
                    fetch(epgUrl).then(res => res.json()).then(result => {
                        epgData = result.reduce((acc, item) => { acc[item.id] = item.epg; return acc; }, {});
                    }).catch(() => null);
                }

                // 3. Carrega jogos, Overrides do admin e Scores (usa cache se voltar do assistir)
                let jogos, adminOverrides;
                if (fromAssistir) {
                    [jogos, adminOverrides] = await Promise.all([
                        fetchWithCache(`${jogosUrl}?_t=${Date.now()}`, CACHE_KEYS.jogos).catch(() => []),
                        fetchWithCache(`admin_api.php?action=get_overrides&data=${localDateYmd()}&_t=${Date.now()}`, CACHE_KEYS.jogos + '_overrides').catch(() => ({}))
                    ]);
                } else {
                    [jogos, adminOverrides] = await Promise.all([
                        fetchWithCache(`${jogosUrl}?_t=${Date.now()}`, CACHE_KEYS.jogos).catch(() => []),
                        fetchWithCache(`admin_api.php?action=get_overrides&data=${localDateYmd()}&_t=${Date.now()}`, CACHE_KEYS.jogos + '_overrides').catch(() => ({}))
                    ]);
                }

                if (!Array.isArray(jogos)) jogos = [];

                // Armazena overrides globalmente para uso em createGameCardHTML
                window.adminJogosOverrides = adminOverrides || {};

                allJogos = jogos;
                allJogosProcessed = matchGameScores(allJogos, []);
                renderHorizontalJogos(allJogosProcessed);
                
                // Sempre busca scores frescos
                fetchLiveScores().then(scraped => {
                    lastScrapedScores = scraped;
                    allJogosProcessed = matchGameScores(allJogos, scraped);
                    renderHorizontalJogos(allJogosProcessed);
                });

            } catch (error) { console.error("Erro geral fetchChannels:", error); }
        }

        // Função auxiliar para atualizar o contador principal rapidamente
        function updateMainCounter(count) {
            const titleRow = document.querySelector('.section-header-premium:not(.section-group-premium .section-header-premium)');
            if (titleRow) {
                const currentText = titleRow.querySelector('.section-title')?.innerText || 'Todos os Canais';
                titleRow.innerHTML = `
                    <h2 class="section-title">${currentText}</h2>
                    <span class="count-badge-premium">${count}</span>
                `;
            }
        }

        // Atualização periódica de placares (cada 15s) - REATIVADA
        setInterval(async () => {
            const isSearching = document.querySelector('.search-input').value.trim().length > 0;
            if (allJogos.length > 0 && !isSearching) {
                const scraped = await fetchLiveScores();
                lastScrapedScores = scraped;
                const gamesWithScores = matchGameScores(allJogos, scraped);
                allJogosProcessed = gamesWithScores;
                renderHorizontalJogos(gamesWithScores);
                const activeBtn = document.querySelector('.cat-btn.active');
                if (activeBtn && activeBtn.innerText.includes('JOGOS')) {
                    renderMainGridJogos(gamesWithScores, activeBtn);
                }
            }
        }, 15000);

        // Função auxiliar para comparar nomes (remove espaços, traços, deixa tudo minúsculo e iguala canais base ao canal 1)
        const normalizeName = (name) => {
            let n = name.toLowerCase().replace(/[\s\-]/g, '');

            // Tratamento especial para unificar canais do BBB ("BBB 26 CAM 01" com "BBB - 1", etc)
            if (n.startsWith('bbb')) {
                n = n.replace(/2[0-9]cam0?/g, '')
                     .replace(/2[0-9]mosaico/g, 'mosaico');
            }

            // Unifica "HBO MAX" com "MAX"
            if (n.startsWith('hbomax')) {
                n = n.replace('hbomax', 'max');
            }

            // Remove zeros à esquerda de números (ex: max01 -> max1)
            n = n.replace(/([a-z])0+([0-9]+)$/, '$1$2');

            // PREMIERE CLUBES = PREMIERE 1 (unificados)
            if (/premiereclubes|premiereserie/i.test(n)) {
                n = 'premiere1';
            }

            // Unifica SPORTV 1 = SPORTV e ESPN 1 = ESPN (mas mantém 2, 3, 4, 5, 6)
            if (/^(sportv|espn)1$/i.test(n)) {
                n = n.replace(/1$/i, '');
            }

            return n;
        };

        function renderHorizontalJogos(jogos) {
            const container = document.getElementById('jogos-section');
            const wrapper = document.getElementById('jogos-horizontal-wrapper');
            
            // PRIORIDADE: Se o usuário estiver buscando algo, não mexemos no grid/topo
            if (document.querySelector('.search-input').value.trim().length > 0) return;

            if (!jogos || jogos.length === 0) { container.style.display = 'none'; return; }

            container.style.display = 'block';
            wrapper.innerHTML = '';

            const live = jogos.filter(j => j.status_label === 'Ao Vivo');
            const upcoming = jogos.filter(j => j.status_label === 'Agendado');
            const finished = jogos.filter(j => j.status_label === 'Encerrado');

            const renderHorizontalSection = (title, list) => {
                if (list.length === 0) return '';
                return `
                    <div class="section-group-premium" style="margin-bottom: 25px;">
                        <div class="section-header-premium">
                            <h2>${title}</h2>
                            <span class="count-badge-premium">${list.length}</span>
                        </div>
                        <div class="jogos-grid">
                            ${list.map(j => createGameCardHTML(j)).join('')}
                        </div>
                    </div>
                `;
            };

            const renderFinishedCarousel = (title, list) => {
                if (list.length === 0) return '';
                const uid = 'carousel-' + Date.now();
                return `
                    <div class="section-group-premium" style="margin-bottom: 25px;">
                        <div class="section-header-premium">
                            <h2>${title}</h2>
                            <span class="count-badge-premium">${list.length}</span>
                        </div>
                        <div class="carousel-wrapper">
                            <button class="carousel-arrow arrow-left" onclick="scrollCarousel('${uid}', -1)">❮</button>
                            <div class="jogos-carousel" id="${uid}">
                                ${list.map(j => `<div class="carousel-item">${createGameCardHTML(j)}</div>`).join('')}
                            </div>
                            <button class="carousel-arrow arrow-right" onclick="scrollCarousel('${uid}', 1)">❯</button>
                        </div>
                    </div>
                `;
            };

            wrapper.innerHTML = renderHorizontalSection('Ao Vivo Agora', live) + 
                               renderHorizontalSection('Próximos Jogos', upcoming) + 
                               renderFinishedCarousel('Jogos Encerrados', finished);
            initCarousels();
        }

        function renderCategories(channels) {
            const categoriesContainer = document.getElementById('categories-container');
            const allCategories = [...new Set(channels.map(c => c.categoria))].filter(Boolean);
            
            // Ordem fixa dos primeiros itens (Normalizada para CaseInsensitive matching)
            const priorityOrder = ['PREMIERE', 'ESPN', 'SPORTV', 'GLOBO', 'ESPORTES', 'FILMES E SERIES', 'HBO', 'TELECINE', 'PrimeVideo', 'ABERTOS', 'EmbedTV', 'CineTve'];
            
            // Encontra as categorias reais que matcham com a prioridade (ignorando case)
            const priorityItems = [];
            priorityOrder.forEach(p => {
                const found = allCategories.find(c => c.toLowerCase() === p.toLowerCase());
                if (found) priorityItems.push(found);
            });

            // Demais itens alfabeticamente
            const restItems = allCategories
                .filter(c => !priorityOrder.some(p => p.toLowerCase() === c.toLowerCase()))
                .sort((a, b) => a.localeCompare(b));

            const orderedCategories = [...priorityItems, ...restItems];

            // Botão TODOS
            categoriesContainer.innerHTML = '<button class="cat-btn active" onclick="filterByCategory(\'all\', this)">TODOS</button>';

            // Botão JOGOS DE HOJE
            const jogosBtn = document.createElement('button');
            jogosBtn.className = 'cat-btn';
            jogosBtn.innerText = 'JOGOS DE HOJE';
            jogosBtn.onclick = () => {
                renderMainGridJogos(allJogosProcessed, jogosBtn);
                window.scrollTo({ top: 0, behavior: 'smooth' });
                closeSidebar();
            };
            categoriesContainer.appendChild(jogosBtn);

            // Demais categorias na ordem definida
            orderedCategories.forEach(cat => {
                const btn = document.createElement('button');
                btn.className = 'cat-btn';
                btn.innerText = cat.toUpperCase();
                btn.onclick = () => { filterByCategory(cat, btn); closeSidebar(); };
                categoriesContainer.appendChild(btn);
            });
        }


        function renderBrasileirao(btn) {
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Filtra jogos do Brasileirão (Série A e B)
            const brasileiraoGames = allJogos.filter(j => {
                const league = slugify(j.data?.league || '');
                return league.includes('brasileiro') || league.includes('brasileirao') || league.includes('seriea') || league.includes('serieb');
            });

            const grid = document.getElementById('channels-grid');
            const horizontalSection = document.getElementById('jogos-section');
            horizontalSection.style.display = 'none';
            grid.innerHTML = '';
            grid.style.display = 'block';
            document.querySelector('.section-title').innerText = 'Brasileirão';

            if (brasileiraoGames.length === 0) {
                grid.innerHTML = '<div style="padding: 40px; text-align: center; color: var(--text-muted); width: 100%;">Nenhum jogo do Brasileirão hoje ⚽</div>';
                return;
            }

            const scored = matchGameScores(brasileiraoGames, lastScrapedScores);
            const live = scored.filter(j => j.status_label === 'Ao Vivo');
            const upcoming = scored.filter(j => j.status_label === 'Agendado');
            const finished = scored.filter(j => j.status_label === 'Encerrado');

            const renderSection = (title, list) => {
                if (list.length === 0) return '';
                return `
                    <div class="section-group-premium">
                        <div class="section-header-premium">
                            <h2>${title}</h2>
                            <span class="count-badge-premium">${list.length}</span>
                        </div>
                        <div class="jogos-grid">
                            ${list.map(j => createGameCardHTML(j)).join('')}
                        </div>
                    </div>
                `;
            };

            const renderCarousel = (title, list) => {
                if (list.length === 0) return '';
                const uid = 'carousel-br-' + Date.now();
                return `
                    <div class="section-group-premium">
                        <div class="section-header-premium">
                            <h2>${title}</h2>
                            <span class="count-badge-premium">${list.length}</span>
                        </div>
                        <div class="carousel-wrapper">
                            <button class="carousel-arrow arrow-left" onclick="scrollCarousel('${uid}', -1)">❮</button>
                            <div class="jogos-carousel" id="${uid}">
                                ${list.map(j => `<div class="carousel-item">${createGameCardHTML(j)}</div>`).join('')}
                            </div>
                            <button class="carousel-arrow arrow-right" onclick="scrollCarousel('${uid}', 1)">❯</button>
                        </div>
                    </div>
                `;
            };

            grid.innerHTML = renderSection('Ao Vivo Agora', live) + 
                            renderSection('Próximos Jogos', upcoming) + 
                            renderCarousel('Jogos Encerrados', finished);
            initCarousels();
        }

        function scrollCarousel(id, direction) {
            const el = document.getElementById(id);
            if (!el) return;
            const scrollAmount = 320;
            el.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
        }

        function initCarousels() {
            document.querySelectorAll('.jogos-carousel').forEach(carousel => {
                let isDown = false, startX, scrollLeft;

                carousel.addEventListener('mousedown', (e) => {
                    isDown = true;
                    carousel.classList.add('dragging');
                    startX = e.pageX - carousel.offsetLeft;
                    scrollLeft = carousel.scrollLeft;
                });

                carousel.addEventListener('mouseleave', () => {
                    isDown = false;
                    carousel.classList.remove('dragging');
                });

                carousel.addEventListener('mouseup', () => {
                    isDown = false;
                    carousel.classList.remove('dragging');
                });

                carousel.addEventListener('mousemove', (e) => {
                    if (!isDown) return;
                    e.preventDefault();
                    const x = e.pageX - carousel.offsetLeft;
                    const walk = (x - startX) * 1.5;
                    carousel.scrollLeft = scrollLeft - walk;
                });
            });
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebar-overlay').classList.toggle('open');
        }

        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebar-overlay').classList.remove('open');
        }

        function renderMainGridJogos(jogos, btn) {
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const grid = document.getElementById('channels-grid');
            const horizontalSection = document.getElementById('jogos-section');
            horizontalSection.style.display = 'none';
            grid.innerHTML = '';
            document.querySelector('.section-title').innerText = 'Jogos de Hoje';
            grid.style.display = 'block';

            if (jogos.length === 0) {
                grid.innerHTML = '<div style="padding: 40px; text-align: center; color: var(--text-muted); width: 100%;">Nenhum jogo encontrado hoje ⚽</div>';
                return;
            }

            const live = jogos.filter(j => j.status_label === 'Ao Vivo');
            const upcoming = jogos.filter(j => j.status_label === 'Agendado');
            const finished = jogos.filter(j => j.status_label === 'Encerrado');

            const renderSection = (title, list) => {
                if (list.length === 0) return '';
                return `
                    <div class="section-group-premium">
                        <div class="section-header-premium">
                            <h2>${title}</h2>
                            <span class="count-badge-premium">${list.length}</span>
                        </div>
                        <div class="jogos-grid">
                            ${list.map(j => createGameCardHTML(j)).join('')}
                        </div>
                    </div>
                `;
            };

            const renderCarouselSection = (title, list) => {
                if (list.length === 0) return '';
                const uid = 'carousel-main-' + Date.now();
                return `
                    <div class="section-group-premium">
                        <div class="section-header-premium">
                            <h2>${title}</h2>
                            <span class="count-badge-premium">${list.length}</span>
                        </div>
                        <div class="carousel-wrapper">
                            <button class="carousel-arrow arrow-left" onclick="scrollCarousel('${uid}', -1)">❮</button>
                            <div class="jogos-carousel" id="${uid}">
                                ${list.map(j => `<div class="carousel-item">${createGameCardHTML(j)}</div>`).join('')}
                            </div>
                            <button class="carousel-arrow arrow-right" onclick="scrollCarousel('${uid}', 1)">❯</button>
                        </div>
                    </div>
                `;
            };

            grid.innerHTML = renderSection('Ao Vivo Agora', live) + 
                             renderSection('Próximos Jogos', upcoming) + 
                             renderCarouselSection('Jogos Encerrados', finished);
            initCarousels();
        }

        function getEpgForChannel(iframeUrl) {
            const id = iframeUrl.split('/').pop();
            return epgData[id] || null;
        }

        function getChannelLogoFallback(channelName, originalLogo) {
            if (originalLogo && String(originalLogo).trim() !== '') return originalLogo;
            return '';
        }


        function renderChannels(channels) {
            const grid = document.getElementById('channels-grid');
            grid.innerHTML = ''; 

            if (channels.length === 0) return;

            channels.forEach(channel => {
                const card = document.createElement('div');
                card.className = 'card';
                
                const logoUrl = channel.logo || '';
                const safeTitle = channel.nome.replace(/'/g, "\\'");

                // Popula Opções 2 e 3 a partir da lista de streams (se disponíveis)
                let urlOpcao2 = '';
                let urlOpcao3 = '';
                if (channel.streams && channel.streams.length > 1) urlOpcao2 = channel.streams[1].url;
                if (channel.streams && channel.streams.length > 2) urlOpcao3 = channel.streams[2].url;

                // Usa stream m3u8 ao invés de iframe_url se disponível (melhor compatibilidade mobile)
                const playerUrl = (channel.streams && channel.streams.length > 0 && channel.streams[0].url.includes('.m3u8')) 
                    ? channel.streams[0].url 
                    : channel.iframe_url;

                // Condensa os streams numa string base64 para evitar quebra de atributos HTML
                let streamsStr = '';
                if (channel.streams && channel.streams.length > 0) {
                    try {
                        // Codificação segura para Unicode (Base64 + URI)
                        streamsStr = btoa(unescape(encodeURIComponent(JSON.stringify(channel.streams))));
                    } catch (e) { console.error("Erro btoa:", e); }
                }

                card.innerHTML = `
                    <div class="card-img-container">
                        <img src="${logoUrl}" alt="${channel.nome}" loading="lazy">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">${channel.nome}</h3>
                        <div class="card-actions">
                            <button onclick="enviarParaPlayer('${playerUrl}', '${safeTitle}', '${logoUrl}', '${urlOpcao2}', '${urlOpcao3}', '${streamsStr}')" class="btn-watch">
                                <span>▶</span> ASSISTIR
                            </button>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        function filterByCategory(category, btn) {
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const grid = document.getElementById('channels-grid');
            const horizontalSection = document.getElementById('jogos-section');
            
            // Se for "all", mostra todo o conteúdo e volta a seção de jogos se houver
            if (category === 'all') {
                horizontalSection.style.display = (allJogosProcessed.length > 0) ? 'block' : 'none';
                grid.style.display = 'grid';
                const mainTitleRow = document.querySelector('.channels-section .section-header-premium');
                if (mainTitleRow) mainTitleRow.innerHTML = `<h2 class="section-title">Todos os Canais</h2><span class="count-badge-premium">${allChannels.length}</span>`;
                renderChannels(allChannels);
                return;
            }

            // Oculta jogos se estiver em categorias
            horizontalSection.style.display = 'none';
            grid.style.display = 'grid';

            // Filtra os canais pela categoria (com fallback para lowercase check para segurança)
            const filtered = allChannels.filter(c => 
                c.categoria === category || 
                (c.categoria && category && c.categoria.toLowerCase() === category.toLowerCase())
            );
            
            renderChannels(filtered);
            
            // Atualiza o título e contador da seção principal
            const mainTitleRow = document.querySelector('.channels-section .section-header-premium');
            if (mainTitleRow) {
                mainTitleRow.innerHTML = `
                    <h2 class="section-title">${category.toUpperCase()}</h2>
                    <span class="count-badge-premium">${filtered.length}</span>
                `;
            }
            
            // Scroll para o topo
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Debounce para busca
        let searchTimeout;
        function handleSearch(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const term = e.target.value.toLowerCase().trim();
                const grid = document.getElementById('channels-grid');
                const horizontalSection = document.getElementById('jogos-section');
                const mainTitleRow = document.querySelector('.section-header-premium:not(.section-group-premium .section-header-premium)');

                if (term.length > 0) {
                // Durante a busca, escondemos o topo e as categorias
                horizontalSection.style.display = 'none';
                if (mainTitleRow) {
                    mainTitleRow.innerHTML = `<h2 class="section-title">Resultados para: "${term}"</h2>`;
                }
                grid.innerHTML = '';
                grid.style.display = 'block';

                const filteredGames = allJogos.filter(j => j.title.toLowerCase().includes(term));
                const filteredChannels = allChannels.filter(c => c.nome.toLowerCase().includes(term));

                if (filteredGames.length === 0 && filteredChannels.length === 0) {
                    grid.innerHTML = '<div style="padding: 40px; text-align: center; color: var(--text-muted); width: 100%;">Nenhum canal ou evento encontrado... 🔎</div>';
                    return;
                }

                // Renderiza Jogos encontrados
                if (filteredGames.length > 0) {
                    const gamesSection = document.createElement('div');
                    gamesSection.className = 'section-group-premium';
                    gamesSection.innerHTML = `
                        <div class="section-header-premium">
                            <h2>Jogos Encontrados</h2>
                            <span class="count-badge-premium">${filteredGames.length}</span>
                        </div>
                        <div class="jogos-grid">
                            ${matchGameScores(filteredGames, lastScrapedScores).map(j => createGameCardHTML(j)).join('')}
                        </div>
                    `;
                    grid.appendChild(gamesSection);
                }

                // Renderiza Canais encontrados
                if (filteredChannels.length > 0) {
                    const channelsTitle = document.createElement('div');
                    channelsTitle.className = 'section-header-premium';
                    channelsTitle.style.marginTop = '30px';
                    channelsTitle.innerHTML = '<h2>Canais Encontrados</h2>';
                    grid.appendChild(channelsTitle);
                    
                    const channelsWrapper = document.createElement('div');
                    channelsWrapper.className = 'grid';
                    channelsWrapper.style.padding = '15px 0';
                    
                    filteredChannels.forEach(channel => {
                        const card = createChannelCardElement(channel);
                        channelsWrapper.appendChild(card);
                    });
                    grid.appendChild(channelsWrapper);
                }
            } else {
                // Restaura o estado normal quando a busca está vazia
                horizontalSection.style.display = 'block';
                grid.style.display = 'grid';
                if (mainTitleRow) {
                    mainTitleRow.innerHTML = `
                        <h2 class="section-title">Todos os Canais</h2>
                        <span class="count-badge-premium">${allChannels.length}</span>
                    `;
                }
                renderChannels(allChannels);
            }
            }, 300);
        }

        document.querySelector('.search-input').addEventListener('input', handleSearch);

        // Helper para criar o elemento de card de canal
        function createChannelCardElement(channel) {
            const card = document.createElement('div');
            card.className = 'card';
            
            const logoUrl = getChannelLogoFallback(channel.nome, channel.logo);
            const safeTitle = channel.nome.replace(/'/g, "\\'");

            let urlOpcao2 = '';
            let urlOpcao3 = '';
            if (channel.streams && channel.streams.length > 1) urlOpcao2 = channel.streams[1].url;
            if (channel.streams && channel.streams.length > 2) urlOpcao3 = channel.streams[2].url;

            // Usa stream m3u8 ao invés de iframe_url se disponível (melhor compatibilidade mobile)
            const playerUrl = (channel.streams && channel.streams.length > 0 && channel.streams[0].url.includes('.m3u8')) 
                ? channel.streams[0].url 
                : channel.iframe_url;

            let streamsStr = '';
            if (channel.streams && channel.streams.length > 0) {
                try {
                    streamsStr = btoa(unescape(encodeURIComponent(JSON.stringify(channel.streams))));
                } catch (e) { console.error("Erro btoa search:", e); }
            }

            card.innerHTML = `
                <div class="card-img-container">
                    <img src="${logoUrl}" alt="${channel.nome}" loading="lazy">
                </div>
                <div class="card-content">
                    <div class="card-title">${channel.nome}</div>
                    <div class="card-actions">
                        <button class="btn-watch" onclick="enviarParaPlayer('${playerUrl}', '${safeTitle}', '${logoUrl}', '${urlOpcao2}', '${urlOpcao3}', '${streamsStr}')">
                            Assistir Agora
                        </button>
                    </div>
                </div>
            `;
            return card;
        }

        // Modificado para receber a 6ª variável (streams dinâmicos codificados em base64)
        function enviarParaPlayer(iframeUrl, title, logo, urlOpcao2 = '', urlOpcao3 = '', streamsBase64 = '') {
            // Salva estado no sessionStorage antes de ir para assistir.php
            sessionStorage.setItem('eliteplay_from_assistir', '1');
            sessionStorage.setItem('eliteplay_scroll_pos', window.scrollY);
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'assistir.php';

            const inputIframe = document.createElement('input');
            inputIframe.type = 'hidden';
            inputIframe.name = 'iframe_url';
            inputIframe.value = iframeUrl;
            form.appendChild(inputIframe);

            const inputIframe2 = document.createElement('input');
            inputIframe2.type = 'hidden';
            inputIframe2.name = 'iframe_url_2';
            inputIframe2.value = urlOpcao2;
            form.appendChild(inputIframe2);

            const inputIframe3 = document.createElement('input');
            inputIframe3.type = 'hidden';
            inputIframe3.name = 'iframe_url_3';
            inputIframe3.value = urlOpcao3;
            form.appendChild(inputIframe3);

            if (streamsBase64) {
                const inputStreams = document.createElement('input');
                inputStreams.type = 'hidden';
                inputStreams.name = 'streams_json';
                inputStreams.value = streamsBase64;
                form.appendChild(inputStreams);
            }

            const inputTitle = document.createElement('input');
            inputTitle.type = 'hidden';
            inputTitle.name = 'title';
            inputTitle.value = title;
            form.appendChild(inputTitle);

            const inputLogo = document.createElement('input');
            inputLogo.type = 'hidden';
            inputLogo.name = 'logo';
            inputLogo.value = logo;
            form.appendChild(inputLogo);

            document.body.appendChild(form);
            form.submit();
        }

        window.onload = () => {
            if (isBackFromAssistir()) {
                // Restaura posição do scroll
                const scrollPos = sessionStorage.getItem('eliteplay_scroll_pos');
                if (scrollPos) window.scrollTo(0, parseInt(scrollPos));
                sessionStorage.removeItem('eliteplay_from_assistir');
            }
            fetchChannels();
        };
    </script>

    <!-- ====================================================
         Modal de Sessão Expirada (outro dispositivo logou)
    ==================================================== -->
    <div id="sessao-modal" style="
        display: none; position: fixed; inset: 0; z-index: 9999;
        background: rgba(5, 7, 10, 0.92);
        backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
        align-items: center; justify-content: center;
    ">
        <div style="
            background: #0f111a; border: 1px solid rgba(239,68,68,0.3);
            border-radius: 20px; padding: 40px 32px; max-width: 400px; width: 90%;
            text-align: center; box-shadow: 0 0 60px rgba(239,68,68,0.15);
            font-family: 'Outfit', sans-serif;
        ">
            <div style="font-size: 44px; margin-bottom: 16px;">🔒</div>
            <h2 style="color:#fff; font-size:20px; margin-bottom: 10px;">Sessão encerrada</h2>
            <p style="color:#94a3b8; font-size:14px; line-height:1.6; margin-bottom:28px;">
                Sua conta foi acessada em outro dispositivo ou navegador.<br>
                Apenas um acesso simultâneo é permitido por conta.
            </p>
            <a href="login.php" style="
                display: inline-block; background: linear-gradient(135deg,#3b82f6,#2563eb);
                color:#fff; padding: 12px 32px; border-radius: 10px; font-weight:700;
                font-size:15px; text-decoration:none; transition: opacity 0.2s;
            ">Fazer Login Novamente</a>
        </div>
    </div>

    <script>
        // ---- Heartbeat: verifica sessão a cada 30 segundos ----
        (function() {
            const INTERVALO = 30000; // 30 segundos
            let heartbeatAtivo = true;

            async function verificarSessao() {
                if (!heartbeatAtivo) return;
                try {
                    const res = await fetch('ping.php', { cache: 'no-store' });
                    if (!res.ok) { mostrarModalSessao(); return; }
                    const data = await res.json();
                    if (!data.valid) mostrarModalSessao();
                } catch (e) {
                    // Falha de rede — não exibe modal, tenta no próximo ciclo
                }
            }

            function mostrarModalSessao() {
                heartbeatAtivo = false;
                const modal = document.getElementById('sessao-modal');
                if (modal) modal.style.display = 'flex';
                clearInterval(intervalo);
            }

            // Também verifica ao voltar para a aba (visibilidade)
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) verificarSessao();
            });

            const intervalo = setInterval(verificarSessao, INTERVALO);
            // Primeiro check imediato após 5s (para não atrasar o carregamento inicial)
            setTimeout(verificarSessao, 5000);
        })();
    </script>
</body>
</html>
