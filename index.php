<?php require_once __DIR__ . '/middleware.php';
$isAdmin = isset($usuario_logado['is_admin']) && $usuario_logado['is_admin'] == 1;
$viewerProfile = [
    'email' => $usuario_logado['email'] ?? '',
    'is_admin' => (int)($usuario_logado['is_admin'] ?? 0),
    'dias_acesso' => isset($usuario_logado['dias_acesso']) ? ($usuario_logado['dias_acesso'] === null ? null : (int)$usuario_logado['dias_acesso']) : null,
    'acesso_expira_em' => $usuario_logado['acesso_expira_em'] ?? null,
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ElitePLAY - Canais ao Vivo</title>
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

        .logo {
            display: block;
            width: auto;
            height: 35px;
        }

        .logo span {
            color: var(--text-muted);
            font-weight: normal;
        }

        .header-brand-row {
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
        }

        .header-icons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-link svg, .user-icon svg, .logout-link svg {
            width: 20px;
            height: 20px;
        }

        .api-source-toggle svg {
            width: 20px;
            height: 20px;
        }

        .admin-link, .user-icon {
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
            transition: all 0.2s;
            appearance: none;
        }

        .admin-link:hover, .user-icon:hover {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .logout-link {
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
            transition: all 0.2s;
        }

        .logout-link:hover {
            background-color: #dc2626;
            border-color: #dc2626;
        }

        .api-source-toggle {
            background-color: var(--bg-input);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s;
            color: #ffffff;
        }

        .api-source-toggle:hover {
            border-color: #f5c00b;
            background-color: rgba(245, 192, 11, 0.12);
        }

        .api-source-toggle.active {
            border-color: #f5c00b;
            background-color: rgba(245, 192, 11, 0.18);
            box-shadow: 0 0 0 3px rgba(245, 192, 11, 0.12);
            color: #f5c00b;
        }

        .profile-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9998;
            background: rgba(5, 7, 10, 0.86);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .profile-modal-overlay.open { display: flex; }

        .profile-modal {
            width: min(520px, 100%);
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(180deg, rgba(12, 16, 27, 0.98) 0%, rgba(8, 12, 20, 0.98) 100%);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.55);
            overflow: hidden;
        }

        .profile-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 18px 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .profile-modal-title {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.2px;
        }

        .profile-modal-subtitle {
            margin: 6px 0 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        .profile-modal-close {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-muted);
            cursor: pointer;
            font-size: 20px;
            line-height: 1;
        }

        .profile-modal-body {
            padding: 16px 18px 18px;
            display: grid;
            gap: 10px;
        }

        .profile-info-item {
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.02);
            padding: 12px;
        }

        .profile-info-key {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
            margin-bottom: 7px;
        }

        .profile-info-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-light);
            word-break: break-word;
        }

        .profile-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .profile-status-badge.vitalicio {
            color: #fbbf24;
            background: rgba(245, 158, 11, 0.12);
            border-color: rgba(245, 158, 11, 0.22);
        }

        .profile-status-badge.alerta {
            color: #fca5a5;
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.25);
        }

        .profile-status-badge.ativo {
            color: #4ade80;
            background: rgba(34, 197, 94, 0.1);
            border-color: rgba(34, 197, 94, 0.2);
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
            top: 90px;
            left: 0;
            margin: 0px 0px 8px 8px;
            width: 248px;
            height: calc(100vh - 72px - 34px);
            background: linear-gradient(180deg, rgba(15, 17, 26, 0.97) 0%, rgba(10, 12, 20, 0.97) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            z-index: 100;
            padding: 8px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.14) transparent;
            transition: transform 0.3s ease;
            border-radius: 16px;
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
            color: #ffffff;
            padding: 10px;
            border-radius: 0;
            cursor: pointer;
            white-space: normal;
            font-weight: 600;
            font-size: 15px;
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
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-light);
            border-radius: 8px;
        }

        .sidebar .cat-btn.active {
            background: linear-gradient(135deg, var(--primary-blue), #2563eb);
            color: #ffffff !important;
            /* border-left-color: var(--primary-blue); */
            border-radius: 8px;
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
            header {
                align-items: center;
                gap: 12px;
            }

            .header-brand-row {
                flex: 1;
                gap: 10px;
            }

            .logo {
                width: 75%;
                height: auto;
            }

            .header-icons {
                display: flex;
                align-items: center;
                gap: 6px;
                margin-left: auto;
                flex-shrink: 0;
            }

            .sidebar {
                transform: translateX(-105%);
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
                background: transparent;
                border: none;
                color: white;
                font-size: 30px;
                cursor: pointer;
                padding: 0px;
                order: -1;
                margin-top: -4px;
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
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
            width: 100%;
            padding: 0px; 
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
            padding: 16px 0 !important;
        }

        .card {
            background-color: var(--bg-card);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--card-premium-border);
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
            background: linear-gradient(135deg, var(--primary-blue), #2563eb) !important;
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
            transition: all 0.3s ease;
        }

        .btn-watch:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
            filter: brightness(1.1);
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
            border-radius: 16px;
            backdrop-filter: blur(var(--glass-blur));
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            cursor: default;
        }

        @property --soon-angle {
            syntax: '<angle>';
            initial-value: 0deg;
            inherits: false;
        }

        .game-card.soon-start {
            --soon-angle: 0deg;
            border: 1px solid transparent;
            background:
                linear-gradient(#0D0F12, #0D0F12) padding-box,
                conic-gradient(from var(--soon-angle), rgba(59, 130, 246, 0.05), rgba(59, 130, 246, 0.35), rgba(59, 130, 246, 1), rgba(255, 255, 255, 0.45), rgba(59, 130, 246, 0.05)) border-box;
            box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.28), 0 0 20px rgba(59, 130, 246, 0.22);
            animation: soon-border-rotate 2.6s linear infinite, soon-border-glow 1.8s ease-in-out infinite;
        }

        .game-card.soon-start:hover {
            background:
                linear-gradient(#0D0F12, #0D0F12) padding-box,
                conic-gradient(from var(--soon-angle), rgba(59, 130, 246, 0.08), rgba(59, 130, 246, 0.4), rgba(59, 130, 246, 1), rgba(255, 255, 255, 0.5), rgba(59, 130, 246, 0.08)) border-box;
        }

        @keyframes soon-border-rotate {
            to { --soon-angle: 360deg; }
        }

        @keyframes soon-border-glow {
            0%, 100% {
                box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.28), 0 0 16px rgba(59, 130, 246, 0.18);
            }
            50% {
                box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.42), 0 0 26px rgba(59, 130, 246, 0.34);
            }
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
            text-transform: uppercase;
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
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 8px;
            border-bottom: 1.5px solid var(--card-premium-border);
        }

        .section-header-premium h2 {
            font-size: 1rem;
            text-transform: uppercase;
            background: linear-gradient(to right, #fff, #cdd7e4);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .channels-section > .section-header-premium {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 8px;
            border-bottom: 1.5px solid var(--card-premium-border);
        }

        .channels-section > .section-header-premium {
            margin-top: 48px;
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
            .status-badge, .live-indicator {
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

            .profile-modal-overlay {
                padding: 10px;
            }

            .profile-modal {
                border-radius: 14px;
            }

            .profile-modal-header {
                padding: 14px 14px 12px;
            }

            .profile-modal-title {
                font-size: 19px;
            }

            .profile-modal-body {
                padding: 12px 14px 14px;
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
        <div class="header-brand-row">
            <button class="sidebar-toggle" id="sidebar-toggle" onclick="toggleSidebar()">☰</button>
            <img src="imagens/elitelogo.webp" alt="ElitePLAY" class="logo">
        </div>
        <div class="search-container">
            <input type="text" class="search-input" placeholder="Pesquisar canais ou eventos...">
        </div>
        <div class="header-icons">
            <?php if ($isAdmin): ?>
            <a href="admin.php" class="admin-link" title="Painel Admin">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M12 8.25C9.92894 8.25 8.25 9.92893 8.25 12C8.25 14.0711 9.92894 15.75 12 15.75C14.0711 15.75 15.75 14.0711 15.75 12C15.75 9.92893 14.0711 8.25 12 8.25ZM9.75 12C9.75 10.7574 10.7574 9.75 12 9.75C13.2426 9.75 14.25 10.7574 14.25 12C14.25 13.2426 13.2426 14.25 12 14.25C10.7574 14.25 9.75 13.2426 9.75 12Z" fill="#ffffff"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M11.9747 1.25C11.5303 1.24999 11.1592 1.24999 10.8546 1.27077C10.5375 1.29241 10.238 1.33905 9.94761 1.45933C9.27379 1.73844 8.73843 2.27379 8.45932 2.94762C8.31402 3.29842 8.27467 3.66812 8.25964 4.06996C8.24756 4.39299 8.08454 4.66251 7.84395 4.80141C7.60337 4.94031 7.28845 4.94673 7.00266 4.79568C6.64714 4.60777 6.30729 4.45699 5.93083 4.40743C5.20773 4.31223 4.47642 4.50819 3.89779 4.95219C3.64843 5.14353 3.45827 5.3796 3.28099 5.6434C3.11068 5.89681 2.92517 6.21815 2.70294 6.60307L2.67769 6.64681C2.45545 7.03172 2.26993 7.35304 2.13562 7.62723C1.99581 7.91267 1.88644 8.19539 1.84541 8.50701C1.75021 9.23012 1.94617 9.96142 2.39016 10.5401C2.62128 10.8412 2.92173 11.0602 3.26217 11.2741C3.53595 11.4461 3.68788 11.7221 3.68786 12C3.68785 12.2778 3.53592 12.5538 3.26217 12.7258C2.92169 12.9397 2.62121 13.1587 2.39007 13.4599C1.94607 14.0385 1.75012 14.7698 1.84531 15.4929C1.88634 15.8045 1.99571 16.0873 2.13552 16.3727C2.26983 16.6469 2.45535 16.9682 2.67758 17.3531L2.70284 17.3969C2.92507 17.7818 3.11058 18.1031 3.28089 18.3565C3.45817 18.6203 3.64833 18.8564 3.89769 19.0477C4.47632 19.4917 5.20763 19.6877 5.93073 19.5925C6.30717 19.5429 6.647 19.3922 7.0025 19.2043C7.28833 19.0532 7.60329 19.0596 7.8439 19.1986C8.08452 19.3375 8.24756 19.607 8.25964 19.9301C8.27467 20.3319 8.31403 20.7016 8.45932 21.0524C8.73843 21.7262 9.27379 22.2616 9.94761 22.5407C10.238 22.661 10.5375 22.7076 10.8546 22.7292C11.1592 22.75 11.5303 22.75 11.9747 22.75H12.0252C12.4697 22.75 12.8407 22.75 13.1454 22.7292C13.4625 22.7076 13.762 22.661 14.0524 22.5407C14.7262 22.2616 15.2616 21.7262 15.5407 21.0524C15.686 20.7016 15.7253 20.3319 15.7403 19.93C15.7524 19.607 15.9154 19.3375 16.156 19.1985C16.3966 19.0596 16.7116 19.0532 16.9974 19.2042C17.3529 19.3921 17.6927 19.5429 18.0692 19.5924C18.7923 19.6876 19.5236 19.4917 20.1022 19.0477C20.3516 18.8563 20.5417 18.6203 20.719 18.3565C20.8893 18.1031 21.0748 17.7818 21.297 17.3969L21.3223 17.3531C21.5445 16.9682 21.7301 16.6468 21.8644 16.3726C22.0042 16.0872 22.1135 15.8045 22.1546 15.4929C22.2498 14.7697 22.0538 14.0384 21.6098 13.4598C21.3787 13.1586 21.0782 12.9397 20.7378 12.7258C20.464 12.5538 20.3121 12.2778 20.3121 11.9999C20.3121 11.7221 20.464 11.4462 20.7377 11.2742C21.0783 11.0603 21.3788 10.8414 21.6099 10.5401C22.0539 9.96149 22.2499 9.23019 22.1547 8.50708C22.1136 8.19546 22.0043 7.91274 21.8645 7.6273C21.7302 7.35313 21.5447 7.03183 21.3224 6.64695L21.2972 6.60318C21.0749 6.21825 20.8894 5.89688 20.7191 5.64347C20.5418 5.37967 20.3517 5.1436 20.1023 4.95225C19.5237 4.50826 18.7924 4.3123 18.0692 4.4075C17.6928 4.45706 17.353 4.60782 16.9975 4.79572C16.7117 4.94679 16.3967 4.94036 16.1561 4.80144C15.9155 4.66253 15.7524 4.39297 15.7403 4.06991C15.7253 3.66808 15.686 3.2984 15.5407 2.94762C15.2616 2.27379 14.7262 1.73844 14.0524 1.45933C13.762 1.33905 13.4625 1.29241 13.1454 1.27077C12.8407 1.24999 12.4697 1.24999 12.0252 1.25H11.9747ZM10.5216 2.84515C10.5988 2.81319 10.716 2.78372 10.9567 2.76729C11.2042 2.75041 11.5238 2.75 12 2.75C12.4762 2.75 12.7958 2.75041 13.0432 2.76729C13.284 2.78372 13.4012 2.81319 13.4783 2.84515C13.7846 2.97202 14.028 3.21536 14.1548 3.52165C14.1949 3.61826 14.228 3.76887 14.2414 4.12597C14.271 4.91835 14.68 5.68129 15.4061 6.10048C16.1321 6.51968 16.9974 6.4924 17.6984 6.12188C18.0143 5.9549 18.1614 5.90832 18.265 5.89467C18.5937 5.8514 18.9261 5.94047 19.1891 6.14228C19.2554 6.19312 19.3395 6.27989 19.4741 6.48016C19.6125 6.68603 19.7726 6.9626 20.0107 7.375C20.2488 7.78741 20.4083 8.06438 20.5174 8.28713C20.6235 8.50382 20.6566 8.62007 20.6675 8.70287C20.7108 9.03155 20.6217 9.36397 20.4199 9.62698C20.3562 9.70995 20.2424 9.81399 19.9397 10.0041C19.2684 10.426 18.8122 11.1616 18.8121 11.9999C18.8121 12.8383 19.2683 13.574 19.9397 13.9959C20.2423 14.186 20.3561 14.29 20.4198 14.373C20.6216 14.636 20.7107 14.9684 20.6674 15.2971C20.6565 15.3799 20.6234 15.4961 20.5173 15.7128C20.4082 15.9355 20.2487 16.2125 20.0106 16.6249C19.7725 17.0373 19.6124 17.3139 19.474 17.5198C19.3394 17.72 19.2553 17.8068 19.189 17.8576C18.926 18.0595 18.5936 18.1485 18.2649 18.1053C18.1613 18.0916 18.0142 18.045 17.6983 17.8781C16.9973 17.5075 16.132 17.4803 15.4059 17.8995C14.68 18.3187 14.271 19.0816 14.2414 19.874C14.228 20.2311 14.1949 20.3817 14.1548 20.4784C14.028 20.7846 13.7846 21.028 13.4783 21.1549C13.4012 21.1868 13.284 21.2163 13.0432 21.2327C12.7958 21.2496 12.4762 21.25 12 21.25C11.5238 21.25 11.2042 21.2496 10.9567 21.2327C10.716 21.2163 10.5988 21.1868 10.5216 21.1549C10.2154 21.028 9.97201 20.7846 9.84514 20.4784C9.80512 20.3817 9.77195 20.2311 9.75859 19.874C9.72896 19.0817 9.31997 18.3187 8.5939 17.8995C7.86784 17.4803 7.00262 17.5076 6.30158 17.8781C5.98565 18.0451 5.83863 18.0917 5.73495 18.1053C5.40626 18.1486 5.07385 18.0595 4.81084 17.8577C4.74458 17.8069 4.66045 17.7201 4.52586 17.5198C4.38751 17.314 4.22736 17.0374 3.98926 16.625C3.75115 16.2126 3.59171 15.9356 3.4826 15.7129C3.37646 15.4962 3.34338 15.3799 3.33248 15.2971C3.28921 14.9684 3.37828 14.636 3.5801 14.373C3.64376 14.2901 3.75761 14.186 4.0602 13.9959C4.73158 13.5741 5.18782 12.8384 5.18786 12.0001C5.18791 11.1616 4.73165 10.4259 4.06021 10.004C3.75769 9.81389 3.64385 9.70987 3.58019 9.62691C3.37838 9.3639 3.28931 9.03149 3.33258 8.7028C3.34348 8.62001 3.37656 8.50375 3.4827 8.28707C3.59181 8.06431 3.75125 7.78734 3.98935 7.37493C4.22746 6.96253 4.3876 6.68596 4.52596 6.48009C4.66055 6.27983 4.74468 6.19305 4.81093 6.14222C5.07395 5.9404 5.40636 5.85133 5.73504 5.8946C5.83873 5.90825 5.98576 5.95483 6.30173 6.12184C7.00273 6.49235 7.86791 6.51962 8.59394 6.10045C9.31998 5.68128 9.72896 4.91837 9.75859 4.12602C9.77195 3.76889 9.80512 3.61827 9.84514 3.52165C9.97201 3.21536 10.2154 2.97202 10.5216 2.84515Z" fill="#ffffff"/>
</svg>
            </a>
            <?php endif; ?>
            <button type="button" id="channel-source-toggle" class="api-source-toggle" title="Alternar fonte de canais (70noticias)" onclick="toggleMainApiSource()" aria-pressed="false">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M2.93077 11.2003C3.00244 6.23968 7.07619 2.25 12.0789 2.25C15.3873 2.25 18.287 3.99427 19.8934 6.60721C20.1103 6.96007 20.0001 7.42199 19.6473 7.63892C19.2944 7.85585 18.8325 7.74565 18.6156 7.39279C17.2727 5.20845 14.8484 3.75 12.0789 3.75C7.8945 3.75 4.50372 7.0777 4.431 11.1982L4.83138 10.8009C5.12542 10.5092 5.60029 10.511 5.89203 10.8051C6.18377 11.0991 6.18191 11.574 5.88787 11.8657L4.20805 13.5324C3.91565 13.8225 3.44398 13.8225 3.15157 13.5324L1.47176 11.8657C1.17772 11.574 1.17585 11.0991 1.46759 10.8051C1.75933 10.5111 2.2342 10.5092 2.52824 10.8009L2.93077 11.2003ZM19.7864 10.4666C20.0786 10.1778 20.5487 10.1778 20.8409 10.4666L22.5271 12.1333C22.8217 12.4244 22.8245 12.8993 22.5333 13.1939C22.2421 13.4885 21.7673 13.4913 21.4727 13.2001L21.0628 12.7949C20.9934 17.7604 16.9017 21.75 11.8825 21.75C8.56379 21.75 5.65381 20.007 4.0412 17.3939C3.82366 17.0414 3.93307 16.5793 4.28557 16.3618C4.63806 16.1442 5.10016 16.2536 5.31769 16.6061C6.6656 18.7903 9.09999 20.25 11.8825 20.25C16.0887 20.25 19.4922 16.9171 19.5625 12.7969L19.1546 13.2001C18.86 13.4913 18.3852 13.4885 18.094 13.1939C17.8028 12.8993 17.8056 12.4244 18.1002 12.1333L19.7864 10.4666Z" fill="currentColor"/>
                </svg>
            </button>
            <a href="logout.php" class="logout-link" title="Sair">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M14.9453 1.25C13.5778 1.24998 12.4754 1.24996 11.6085 1.36652C10.7084 1.48754 9.95048 1.74643 9.34857 2.34835C8.82363 2.87328 8.55839 3.51836 8.41916 4.27635C8.28387 5.01291 8.25799 5.9143 8.25196 6.99583C8.24966 7.41003 8.58357 7.74768 8.99778 7.74999C9.41199 7.7523 9.74964 7.41838 9.75194 7.00418C9.75803 5.91068 9.78643 5.1356 9.89448 4.54735C9.99859 3.98054 10.1658 3.65246 10.4092 3.40901C10.686 3.13225 11.0746 2.9518 11.8083 2.85315C12.5637 2.75159 13.5648 2.75 15.0002 2.75H16.0002C17.4356 2.75 18.4367 2.75159 19.1921 2.85315C19.9259 2.9518 20.3144 3.13225 20.5912 3.40901C20.868 3.68577 21.0484 4.07435 21.1471 4.80812C21.2486 5.56347 21.2502 6.56459 21.2502 8V16C21.2502 17.4354 21.2486 18.4365 21.1471 19.1919C21.0484 19.9257 20.868 20.3142 20.5912 20.591C20.3144 20.8678 19.9259 21.0482 19.1921 21.1469C18.4367 21.2484 17.4356 21.25 16.0002 21.25H15.0002C13.5648 21.25 12.5637 21.2484 11.8083 21.1469C11.0746 21.0482 10.686 20.8678 10.4092 20.591C10.1658 20.3475 9.99859 20.0195 9.89448 19.4527C9.78643 18.8644 9.75803 18.0893 9.75194 16.9958C9.74964 16.5816 9.41199 16.2477 8.99778 16.25C8.58357 16.2523 8.24966 16.59 8.25196 17.0042C8.25799 18.0857 8.28387 18.9871 8.41916 19.7236C8.55839 20.4816 8.82363 21.1267 9.34857 21.6517C9.95048 22.2536 10.7084 22.5125 11.6085 22.6335C12.4754 22.75 13.5778 22.75 14.9453 22.75H16.0551C17.4227 22.75 18.525 22.75 19.392 22.6335C20.2921 22.5125 21.0499 22.2536 21.6519 21.6517C22.2538 21.0497 22.5127 20.2919 22.6337 19.3918C22.7503 18.5248 22.7502 17.4225 22.7502 16.0549V7.94513C22.7502 6.57754 22.7503 5.47522 22.6337 4.60825C22.5127 3.70814 22.2538 2.95027 21.6519 2.34835C21.0499 1.74643 20.2921 1.48754 19.392 1.36652C18.525 1.24996 17.4227 1.24998 16.0551 1.25H14.9453Z" fill="#ffffff"/>
<path d="M15 11.25C15.4142 11.25 15.75 11.5858 15.75 12C15.75 12.4142 15.4142 12.75 15 12.75H4.02744L5.98809 14.4306C6.30259 14.7001 6.33901 15.1736 6.06944 15.4881C5.79988 15.8026 5.3264 15.839 5.01191 15.5694L1.51191 12.5694C1.34567 12.427 1.25 12.2189 1.25 12C1.25 11.7811 1.34567 11.573 1.51191 11.4306L5.01191 8.43056C5.3264 8.16099 5.79988 8.19741 6.06944 8.51191C6.33901 8.8264 6.30259 9.29988 5.98809 9.56944L4.02744 11.25H15Z" fill="#ffffff"/>
</svg>
            </a>
            <button type="button" class="user-icon" title="Minha conta" onclick="openProfileModal()" aria-label="Abrir informações da conta">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M12 1.25C9.37666 1.25 7.25001 3.37665 7.25001 6C7.25001 8.62335 9.37666 10.75 12 10.75C14.6234 10.75 16.75 8.62335 16.75 6C16.75 3.37665 14.6234 1.25 12 1.25ZM8.75001 6C8.75001 4.20507 10.2051 2.75 12 2.75C13.7949 2.75 15.25 4.20507 15.25 6C15.25 7.79493 13.7949 9.25 12 9.25C10.2051 9.25 8.75001 7.79493 8.75001 6Z" fill="#ffffff"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M12 12.25C9.68646 12.25 7.55494 12.7759 5.97546 13.6643C4.4195 14.5396 3.25001 15.8661 3.25001 17.5L3.24995 17.602C3.24882 18.7638 3.2474 20.222 4.52642 21.2635C5.15589 21.7761 6.03649 22.1406 7.22622 22.3815C8.41927 22.6229 9.97424 22.75 12 22.75C14.0258 22.75 15.5808 22.6229 16.7738 22.3815C17.9635 22.1406 18.8441 21.7761 19.4736 21.2635C20.7526 20.222 20.7512 18.7638 20.7501 17.602L20.75 17.5C20.75 15.8661 19.5805 14.5396 18.0246 13.6643C16.4451 12.7759 14.3136 12.25 12 12.25ZM4.75001 17.5C4.75001 16.6487 5.37139 15.7251 6.71085 14.9717C8.02681 14.2315 9.89529 13.75 12 13.75C14.1047 13.75 15.9732 14.2315 17.2892 14.9717C18.6286 15.7251 19.25 16.6487 19.25 17.5C19.25 18.8078 19.2097 19.544 18.5264 20.1004C18.1559 20.4022 17.5365 20.6967 16.4762 20.9113C15.4193 21.1252 13.9742 21.25 12 21.25C10.0258 21.25 8.58075 21.1252 7.5238 20.9113C6.46354 20.6967 5.84413 20.4022 5.4736 20.1004C4.79033 19.544 4.75001 18.8078 4.75001 17.5Z" fill="#ffffff"/>
</svg>
            </button>
        </div>
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
        const currentViewer = <?php echo json_encode($viewerProfile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const MAIN_CHANNELS_APIS = {
            noticias70: {
                key: 'noticias70',
                label: '70noticias',
                url: 'external_api.php?resource=channels&source=noticias70'
            },
            bugoumods: {
                key: 'bugoumods',
                label: 'bugoumods',
                url: 'external_api.php?resource=channels&source=bugoumods'
            }
        };
        const CHANNELS_SOURCE_STORAGE_KEY = 'eliteplay_channels_source';
        const epgUrl = 'proxy_embedtv.php?resource=epgs';
        const jogosUrl = 'proxy_embedtv.php?resource=jogos';
        const embedtvChannelsUrl = 'proxy_embedtv.php?resource=channels';
        const localDateYmd = (d = new Date()) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        
        const CACHE_TTL = 5 * 60 * 1000; // 5 minutos
        const GAME_CARDS_CACHE_TTL = 24 * 60 * 60 * 1000; // 24 horas
        const CACHE_KEYS = {
            channels: 'eliteplay_channels',
            jogos: 'eliteplay_jogos',
            gameCardsHTML: 'eliteplay_game_cards_html'
        };

        let allChannels = [];
        let epgData = {};
        let allJogos = [];
        let allJogosProcessed = [];
        let embedtvChannels = [];
        let lastScrapedScores = [];
        let dataLoadedFromCache = false;
        let isCategoryMode = false;
        let gameCardsLoadedFromCache = false;
        let currentChannelsSource = sessionStorage.getItem(CHANNELS_SOURCE_STORAGE_KEY) === MAIN_CHANNELS_APIS.bugoumods.key
            ? MAIN_CHANNELS_APIS.bugoumods.key
            : MAIN_CHANNELS_APIS.noticias70.key;

        function getCurrentChannelsSourceConfig() {
            return currentChannelsSource === MAIN_CHANNELS_APIS.bugoumods.key
                ? MAIN_CHANNELS_APIS.bugoumods
                : MAIN_CHANNELS_APIS.noticias70;
        }

        function parseDateTime(dateStr) {
            if (!dateStr) return null;
            const parsed = new Date(String(dateStr).replace(' ', 'T'));
            return Number.isNaN(parsed.getTime()) ? null : parsed;
        }

        function formatDateTimeBr(dateStr) {
            const d = parseDateTime(dateStr);
            if (!d) return '—';
            return d.toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }

        function getAccountStatusInfo(viewer) {
            if (!viewer) {
                return { type: 'Conta', expiresText: '—', badgeText: '—', badgeClass: 'ativo' };
            }

            if (Number(viewer.is_admin) === 1) {
                return {
                    type: 'Admin',
                    expiresText: 'Vitalício',
                    badgeText: 'Admin',
                    badgeClass: 'vitalicio'
                };
            }

            const expDate = parseDateTime(viewer.acesso_expira_em);
            if (!expDate) {
                return {
                    type: 'Usuário Vitalício',
                    expiresText: 'Vitalício',
                    badgeText: 'Vitalício',
                    badgeClass: 'vitalicio'
                };
            }

            const now = new Date();
            const msDiff = expDate.getTime() - now.getTime();
            const daysLeft = Math.ceil(msDiff / 86400000);

            let reminder = '';
            if (daysLeft > 0 && daysLeft <= 3) {
                reminder = daysLeft === 1 ? ' (Falta 1 dia)' : ` (Faltam ${daysLeft} dias)`;
            }

            if (daysLeft <= 0) {
                return {
                    type: 'Usuário',
                    expiresText: `${formatDateTimeBr(viewer.acesso_expira_em)} (Vencido)`,
                    badgeText: 'Vencido',
                    badgeClass: 'alerta'
                };
            }

            return {
                type: 'Usuário',
                expiresText: `${formatDateTimeBr(viewer.acesso_expira_em)}${reminder}`,
                badgeText: daysLeft <= 3 ? (daysLeft === 1 ? 'Falta 1 dia' : `Faltam ${daysLeft} dias`) : 'Ativo',
                badgeClass: daysLeft <= 3 ? 'alerta' : 'ativo'
            };
        }

        function openProfileModal() {
            const modal = document.getElementById('profile-modal-overlay');
            if (!modal) return;

            const info = getAccountStatusInfo(currentViewer);
            const emailEl = document.getElementById('profile-info-email');
            const typeEl = document.getElementById('profile-info-type');
            const expiresEl = document.getElementById('profile-info-expires');
            const badgeEl = document.getElementById('profile-info-status-badge');
            const subtitleEl = document.getElementById('profile-modal-subtitle');

            if (emailEl) emailEl.textContent = currentViewer?.email || '—';
            if (typeEl) typeEl.textContent = info.type;
            if (expiresEl) expiresEl.textContent = info.expiresText;
            if (subtitleEl) subtitleEl.textContent = 'Informações do seu acesso na plataforma';

            if (badgeEl) {
                badgeEl.className = `profile-status-badge ${info.badgeClass}`;
                badgeEl.textContent = info.badgeText;
            }

            modal.classList.add('open');
        }

        function closeProfileModal() {
            const modal = document.getElementById('profile-modal-overlay');
            if (modal) modal.classList.remove('open');
        }

        function updateChannelsSourceToggleUI() {
            const btn = document.getElementById('channel-source-toggle');
            if (!btn) return;

            const cfg = getCurrentChannelsSourceConfig();
            const isBugou = cfg.key === MAIN_CHANNELS_APIS.bugoumods.key;
            btn.classList.toggle('active', isBugou);
            btn.setAttribute('aria-pressed', isBugou ? 'true' : 'false');
            btn.title = `Alternar fonte de canais (${cfg.label})`;
        }

        async function toggleMainApiSource() {
            currentChannelsSource = currentChannelsSource === MAIN_CHANNELS_APIS.noticias70.key
                ? MAIN_CHANNELS_APIS.bugoumods.key
                : MAIN_CHANNELS_APIS.noticias70.key;

            sessionStorage.setItem(CHANNELS_SOURCE_STORAGE_KEY, currentChannelsSource);
            updateChannelsSourceToggleUI();
            await fetchChannels(true);
        }

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

        // Game Cards HTML Cache (24 hours)
        function saveGameCardsCache(html) {
            try {
                localStorage.setItem(CACHE_KEYS.gameCardsHTML, JSON.stringify({
                    html: html,
                    timestamp: Date.now()
                }));
            } catch {}
        }

        function loadGameCardsCache() {
            try {
                const cached = localStorage.getItem(CACHE_KEYS.gameCardsHTML);
                if (!cached) return null;
                const { html, timestamp } = JSON.parse(cached);
                if (Date.now() - timestamp > GAME_CARDS_CACHE_TTL) {
                    localStorage.removeItem(CACHE_KEYS.gameCardsHTML);
                    return null;
                }
                return html;
            } catch { return null; }
        }

        function clearGameCardsCache() {
            try {
                localStorage.removeItem(CACHE_KEYS.gameCardsHTML);
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
            const scoreUrl = `external_api.php?resource=placar_hoje&_t=${Date.now()}`;
            let html = '';

            try {
                const response = await fetch(scoreUrl, { cache: 'no-store' });
                html = await response.text();
            } catch (e) {
                console.warn('[ElitePLAY Scraper] Falha ao obter placares via backend.');
            }

            if (!html) { 
                console.warn('[ElitePLAY Scraper] Nenhum HTML de placar retornado.'); 
                return []; 
            }

            try {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Pega TODOS os links de jogos (qualquer liga)
                const allLinks = Array.from(doc.querySelectorAll('a[href]')).filter(a => {
                    const href = a.getAttribute('href') || '';
                    const hrefLower = href.toLowerCase();
                    const blockedByHref = hrefLower.includes('sub-20') || hrefLower.includes('sub20');
                    return !blockedByHref && href.includes('.html') && a.querySelector('.status-name') && a.querySelector('h5');
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
            const isInterval = isLive && (jogo.statusText || '').toLowerCase().includes('int');
            
            // Jogos encerrados sempre mostram status encerrado, evitando herdar "INTERVALO"
            const statusLabel = isFinished ? 'ENCERRADO' : (jogo.statusText || jogo.data?.time || 'HOJE');
            
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
            const overrideList = jogoId && Array.isArray(adminOverrides[jogoId]) ? adminOverrides[jogoId] : [];
            const hasAdminOverride = overrideList.length > 0;

            let detectedStreams = [];
            let matchedChannel = null;
            const channelsToUse = (typeof window.channelsForGames !== 'undefined' && window.channelsForGames.length > 0)
                ? window.channelsForGames
                : allChannels;

            if (typeof channelsToUse !== 'undefined' && channelsToUse.length > 0) {
                // Primeiro tenta encontrar pelo embedtv_id (mais confiável)
                if (originalEmbedTvUrl) {
                    const urlId = originalEmbedTvUrl.split('/').pop().split('?')[0];
                    matchedChannel = channelsToUse.find(c => {
                        return c.embedtv_id === urlId;
                    });
                }

                // Se não encontrou, tenta pelo nome do jogo
                if (!matchedChannel) {
                    const gameTitleNorm = normalizeName(jogo.title);
                    matchedChannel = channelsToUse.find(c => {
                        const chanNameNorm = normalizeName(c.nome);
                        return gameTitleNorm.includes(chanNameNorm) || chanNameNorm.includes(gameTitleNorm);
                    });
                }

                // Se ainda não encontrou, tenta pelo ID normalizado
                if (!matchedChannel && originalEmbedTvUrl) {
                    let urlId = originalEmbedTvUrl.split('id=').pop().split('&')[0];
                    if (!urlId || urlId.includes('/') || urlId === originalEmbedTvUrl) {
                        urlId = originalEmbedTvUrl.split('?')[0].replace(/\/$/, '').split('/').pop();
                    }
                    if (urlId) {
                        const idNorm = normalizeName(urlId);
                        matchedChannel = channelsToUse.find(c => {
                            const cn = normalizeName(c.nome);
                            if (cn === idNorm || cn.replace(/1$/) === idNorm || idNorm.replace(/1$/) === cn) return true;
                            // Tratamento especial para SporTV X: "sportoX" = "sportvX"
                            const cnSportv = cn.replace(/^sporto(\d+)$/, 'sportv$1');
                            const idNormSportv = idNorm.replace(/^sporto(\d+)$/, 'sportv$1');
                            return cnSportv === idNorm || cn === idNormSportv || cnSportv === idNormSportv;
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
                        if (cn === idNorm || cn.replace(/1$/) === idNorm || idNorm.replace(/1$/) === cn) return true;
                        // Tratamento especial para SporTV X: "sportoX" = "sportvX"
                        const cnSportv = cn.replace(/^sporto(\d+)$/, 'sportv$1');
                        const idNormSportv = idNorm.replace(/^sporto(\d+)$/, 'sportv$1');
                        return cnSportv === idNorm || cn === idNormSportv || cnSportv === idNormSportv;
                    });
                    if (matchByUrl && matchByUrl.streams) {
                        detectedStreams = matchByUrl.streams.slice();
                    }
                }
            }

            let finalStreams = [...detectedStreams];

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

            // Usa finalStreams para respeitar regras de override (adicionar/remover canais/qualidades)
            if (finalStreams.length > 0) {
                gameStreamsStr = btoa(unescape(encodeURIComponent(JSON.stringify(finalStreams))));
            }

            const initialPlayerUrl = finalStreams.length > 0 ? finalStreams[0].url : playerUrlOpcao1;
            const startTs = Number(jogo.data?.timer?.start || 0);
            const msToStart = startTs > 0 ? (startTs * 1000) - Date.now() : 0;
            const isStartingSoon = !isLive && !isFinished && msToStart > 0 && msToStart <= 60 * 60 * 1000;
            const gameCardClass = `game-card${isStartingSoon ? ' soon-start' : ''}`;

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
                <div class="${gameCardClass}">
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
                            ${!isFinished ? `<button onclick="enviarParaPlayer('${initialPlayerUrl}', '${safeTitle}', '${jogo.image}', '${originalEmbedTvUrl}', '', '${gameStreamsStr}')" class="watch-premium-button" style="flex: 1;">Assistir Agora</button>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        async function fetchChannels(forceRefresh = false) {
            try {
                const channelsSourceConfig = getCurrentChannelsSourceConfig();

                const res70Promise = fetchWithCache(
                    channelsSourceConfig.url,
                    `${CACHE_KEYS.channels}_${channelsSourceConfig.key}`,
                    forceRefresh
                ).catch(() => ({}));

                const resEmbed = await fetchWithCache(
                    embedtvChannelsUrl,
                    CACHE_KEYS.channels + '_embed',
                    forceRefresh
                ).catch(() => null);

                let groupedChannels = {};

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

                const normalizeCombinedChannels = (channelsMap) => {
                    let combined = Object.values(channelsMap)
                        .filter(c => !c.nome.includes('[H265]'))
                        .map(c => ({
                            ...c,
                            streams: c.streams.filter(s => !s.name.includes('[H265]'))
                        }));

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
                        'max': 'FILMES E SERIES',
                        'telecine': 'TELECINE',
                        'paramount': 'FILMES E SERIES',
                        'warner': 'FILMES E SERIES',
                        'axn': 'FILMES E SERIES',
                        'universal': 'FILMES E SERIES',
                        'fox': 'FILMES E SERIES',
                        'star': 'FILMES E SERIES',
                        'prime video': 'PrimeVideo',
                    };

                    combined.forEach(chan => {
                        const lowName = chan.nome.toLowerCase();
                        if (['EmbedTV', 'CineTve', 'Outros', 'GERAL', 'TV', 'FILMES E SERIES'].includes(chan.categoria)) {
                            for (const kw in keywordMap) {
                                if (lowName.includes(kw)) {
                                    chan.categoria = keywordMap[kw];
                                    break;
                                }
                            }
                        }
                        if (lowName.includes('espn') && chan.categoria !== 'ESPN') chan.categoria = 'ESPN';
                        if (lowName.includes('premiere') && chan.categoria !== 'PREMIERE') chan.categoria = 'PREMIERE';
                        if (lowName.includes('telecine') && chan.categoria !== 'TELECINE') chan.categoria = 'TELECINE';
                        if (lowName.includes('globo') && chan.categoria !== 'GLOBO') chan.categoria = 'GLOBO';
                        if ((lowName.includes('hbo') || lowName.includes('max')) && chan.categoria !== 'HBO') chan.categoria = 'HBO';
                    });

                    return combined;
                };

                const renderCombinedChannels = (combined) => {
                    if (!combined.length) return;
                    allChannels = combined;
                    window.channelsForGames = allChannels;
                    renderCategories(allChannels);
                    renderChannels(allChannels);
                    updateMainCounter(allChannels.length);

                    if (allJogosProcessed && allJogosProcessed.length > 0) {
                        renderHorizontalJogos(allJogosProcessed);
                    }
                };

                if (resEmbed && resEmbed.channels) {
                    embedtvChannels = resEmbed.channels;

                    const embedCatMap = {};
                    if (resEmbed.categories) {
                        resEmbed.categories.forEach(ct => embedCatMap[ct.id] = ct.name);
                    }

                    resEmbed.channels.forEach(c => {
                        const norm = normalizeName(c.name);
                        const m3u8Url = `https://mr.s27-usa-cloudfront-net.online/fontes/mr/${c.id}.m3u8`;

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
                            if (!groupedChannels[norm].embedtv_id) {
                                groupedChannels[norm].embedtv_id = c.id;
                            }
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

                renderCombinedChannels(normalizeCombinedChannels(groupedChannels));

                const res70 = await res70Promise;
                for (const category in res70) {
                    const canais = res70[category];
                    if (!Array.isArray(canais)) continue;

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
                        } else {
                            groupedChannels[norm].nome = baseName || groupedChannels[norm].nome;
                            groupedChannels[norm].iframe_url = c.link || groupedChannels[norm].iframe_url;
                            if (is4K) {
                                groupedChannels[norm].categoria = 'CANAIS 4K';
                            } else if (!groupedChannels[norm].categoria || groupedChannels[norm].categoria === 'EmbedTV' || groupedChannels[norm].categoria === 'Outros') {
                                groupedChannels[norm].categoria = channelCategory;
                            }
                            if (c.capa) groupedChannels[norm].logo = c.capa;
                        }

                        if (!groupedChannels[norm].streams.some(s => s.url === c.link)) {
                            const qualityName = parsed.quality ? `${parsed.quality}` : 'Principal';
                            groupedChannels[norm].streams.push({ name: qualityName, url: c.link });
                        }
                    });
                }

                renderCombinedChannels(normalizeCombinedChannels(groupedChannels));

                fetch(epgUrl).then(res => res.json()).then(result => {
                    epgData = result.reduce((acc, item) => { acc[item.id] = item.epg; return acc; }, {});
                }).catch(() => null);

                // 3. Carrega jogos, Overrides do admin e Scores (usa cache se voltar do assistir)
                let jogos, adminOverrides;
                [jogos, adminOverrides] = await Promise.all([
                    fetchWithCache(`${jogosUrl}&_t=${Date.now()}`, CACHE_KEYS.jogos).catch(() => []),
                    fetchWithCache(`admin_api.php?action=get_overrides&data=${localDateYmd()}&_t=${Date.now()}`, CACHE_KEYS.jogos + '_overrides').catch(() => ({}))
                ]);

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

        // Atualização periódica de placares (cada 15s) - só atualiza se não estiver em modo categoria
        setInterval(async () => {
            if (isCategoryMode) return;
            const isSearching = document.querySelector('.search-input').value.trim().length > 0;
            if (allJogos.length > 0 && !isSearching) {
                const scraped = await fetchLiveScores();
                lastScrapedScores = scraped;
                const gamesWithScores = matchGameScores(allJogos, scraped);
                allJogosProcessed = gamesWithScores;
                
                // Se já carregou do cache, atualiza apenas os placares no DOM
                if (gameCardsLoadedFromCache) {
                    updateScoresInDOM(gamesWithScores);
                } else {
                    renderHorizontalJogos(gamesWithScores);
                }
                
                const activeBtn = document.querySelector('.cat-btn.active');
                if (activeBtn && activeBtn.innerText.includes('JOGOS')) {
                    renderMainGridJogos(gamesWithScores, activeBtn);
                }
            }
        }, 30000);

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

        function renderHorizontalJogos(jogos, forceRefresh = false) {
            const container = document.getElementById('jogos-section');
            const wrapper = document.getElementById('jogos-horizontal-wrapper');
            
            // PRIORIDADE: Se o usuário estiver buscando algo, não mexemos no grid/topo
            if (document.querySelector('.search-input').value.trim().length > 0) return;

            if (!jogos || jogos.length === 0) { container.style.display = 'none'; return; }

            // Tenta carregar do cache primeiro (só na primeira vez)
            if (!forceRefresh && !gameCardsLoadedFromCache) {
                const cachedHTML = loadGameCardsCache();
                if (cachedHTML) {
                    container.style.display = 'block';
                    wrapper.innerHTML = cachedHTML;
                    initCarousels();
                    gameCardsLoadedFromCache = true;
                    return;
                }
            }

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

            const html = renderHorizontalSection('Ao Vivo Agora', live) + 
                               renderHorizontalSection('Próximos Jogos', upcoming) + 
                               renderFinishedCarousel('Jogos Encerrados', finished);
            wrapper.innerHTML = html;
            initCarousels();

            // Salva no cache após renderizar (só na primeira vez)
            if (!gameCardsLoadedFromCache) {
                saveGameCardsCache(html);
                gameCardsLoadedFromCache = true;
            }
        }

        // Atualiza apenas os placares no DOM existente (sem re-renderizar)
        function updateScoresInDOM(gamesWithScores) {
            gamesWithScores.forEach(jogo => {
                const isLive = jogo.status_label === 'Ao Vivo';
                const isFinished = jogo.status_label === 'Encerrado';
                
                if (!isLive && !isFinished) return;
                
                // Encontra o card pelo título do jogo
                const cards = document.querySelectorAll('.game-card');
                cards.forEach(card => {
                    const titleEl = card.querySelector('.team-name-premium');
                    if (!titleEl) return;
                    
                    const homeName = jogo.data?.teams?.home?.name || '';
                    const awayName = jogo.data?.teams?.away?.name || '';
                    
                    if (titleEl.textContent.includes(homeName.split(' ')[0])) {
                        // Atualiza score
                        const scoreDisplay = card.querySelector('.score-premium-display');
                        if (scoreDisplay) {
                            const homeScore = jogo.homeScore !== undefined ? jogo.homeScore : (isLive || isFinished ? '0' : '');
                            const awayScore = jogo.awayScore !== undefined ? jogo.awayScore : (isLive || isFinished ? '0' : '');
                            scoreDisplay.innerHTML = `<span>${homeScore}</span><span class="score-divider">-</span><span>${awayScore}</span>`;
                        }
                        
                        // Atualiza status
                        const statusBadge = card.querySelector('.status-badge');
                        if (statusBadge) {
                            const statusTextLower = String(jogo.statusText || '').toLowerCase();
                            const isInterval = isLive && statusTextLower.includes('int');

                            statusBadge.classList.remove('live', 'finished', 'interval');
                            if (isInterval) {
                                statusBadge.classList.add('interval');
                                statusBadge.textContent = 'INTERVALO';
                            } else if (isLive) {
                                statusBadge.classList.add('live');
                                statusBadge.textContent = jogo.statusText || 'AO VIVO';
                            } else {
                                statusBadge.classList.add('finished');
                                statusBadge.textContent = 'ENCERRADO';
                            }
                        }
                    }
                });
            });
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
                isCategoryMode = false;
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
                isCategoryMode = false;
                horizontalSection.style.display = (allJogosProcessed.length > 0) ? 'block' : 'none';
                grid.style.display = 'grid';
                const mainTitleRow = document.querySelector('.channels-section .section-header-premium');
                if (mainTitleRow) mainTitleRow.innerHTML = `<h2 class="section-title">Todos os Canais</h2><span class="count-badge-premium">${allChannels.length}</span>`;
                renderChannels(allChannels);
                return;
            }

            // Oculta jogos se estiver em categorias
            isCategoryMode = true;
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
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeProfileModal();
                }
            });
            updateChannelsSourceToggleUI();
            fetchChannels();
        };
    </script>

    <div id="profile-modal-overlay" class="profile-modal-overlay" onclick="if(event.target===this)closeProfileModal()">
        <div class="profile-modal" role="dialog" aria-modal="true" aria-labelledby="profile-modal-title">
            <div class="profile-modal-header">
                <div>
                    <h3 class="profile-modal-title" id="profile-modal-title">Minha Conta</h3>
                    <p class="profile-modal-subtitle" id="profile-modal-subtitle">Informações de acesso</p>
                </div>
                <button type="button" class="profile-modal-close" onclick="closeProfileModal()" aria-label="Fechar">×</button>
            </div>
            <div class="profile-modal-body">
                <div class="profile-info-item">
                    <span class="profile-info-key">E-mail</span>
                    <span class="profile-info-value" id="profile-info-email"></span>
                </div>
                <div class="profile-info-item">
                    <span class="profile-info-key">Tipo de conta</span>
                    <span class="profile-info-value" id="profile-info-type"></span>
                </div>
                <div class="profile-info-item">
                    <span class="profile-info-key">Vencimento</span>
                    <span class="profile-info-value" id="profile-info-expires"></span>
                </div>
                <div class="profile-info-item">
                    <span class="profile-info-key">Status</span>
                    <span class="profile-status-badge" id="profile-info-status-badge"></span>
                </div>
            </div>
        </div>
    </div>

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
