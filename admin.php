<?php
// ============================================================
//  admin.php — Painel de administração ElitePLAY
// ============================================================
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/security.php';
configurar_sessao();

// Autenticação
$sessao = validar_sessao_cookie();
if (!$sessao) { header('Location: login.php'); exit; }

// Verifica se é admin
$meAdmin = db()->prepare('SELECT is_admin, email FROM usuarios WHERE id = ? AND ativo = 1 LIMIT 1');
$meAdmin->execute([$sessao['usuario_id']]);
$me = $meAdmin->fetch();
if (!$me || !$me['is_admin']) {
    header('Location: index.php');
    exit;
}

$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ElitePLAY — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #05070a;
            --bg-card: rgba(255,255,255,0.03);
            --bg-sidebar: #080b12;
            --primary: #3b82f6;
            --primary-dark: #1d4ed8;
            --red: #ef4444;
            --green: #22c55e;
            --text: #ffffff;
            --text-muted: #94a3b8;
            --border: rgba(255,255,255,0.06);
            --sidebar-w: 240px;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family:'Outfit',sans-serif; }

        body {
            background: var(--bg-dark);
            color: var(--text);
            min-height: 100vh;
            display: flex;
        }

        /* ---- Sidebar ---- */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-logo {
            padding: 24px 20px 16px;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-logo .logo-text {
            font-size: 20px;
            font-weight: 800;
        }

        .sidebar-logo .logo-text span { color: var(--text-muted); font-weight:300; }

        .admin-badge {
            display: inline-block;
            margin-top: 6px;
            font-size: 10px;
            font-weight: 700;
            background: rgba(59,130,246,0.15);
            color: var(--primary);
            border: 1px solid rgba(59,130,246,0.2);
            padding: 2px 8px;
            border-radius: 4px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .sidebar-nav {
            padding: 16px 12px;
            flex: 1;
        }

        .nav-section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            padding: 12px 8px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            text-decoration: none;
        }

        .nav-item:hover { background: var(--bg-card); color: var(--text); }
        .nav-item.active { background: rgba(59,130,246,0.12); color: var(--primary); }
        .nav-item svg { flex-shrink: 0; }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--border);
            font-size: 12px;
            color: var(--text-muted);
        }

        .sidebar-footer a { color: var(--text-muted); text-decoration: none; }
        .sidebar-footer a:hover { color: var(--text); }

        /* ---- Main ---- */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            padding: 32px;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 28px;
        }

        .page-header h1 { font-size: 24px; font-weight: 700; }
        .page-header p  { font-size: 14px; color: var(--text-muted); margin-top: 4px; }

        .mobile-sidebar-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: rgba(255,255,255,0.06);
            color: var(--text);
            cursor: pointer;
            flex-shrink: 0;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 70;
        }

        .sidebar-backdrop.open { display: block; }

        .section { display: none; }
        .section.active { display: block; }

        /* ---- Cards/Tables ---- */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .card-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .card-header h2 { font-size: 16px; font-weight: 700; }

        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            padding: 12px 22px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 14px 22px;
            font-size: 14px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .badge-green  { background: rgba(34,197,94,0.1);  color: #4ade80; border: 1px solid rgba(34,197,94,0.2); }
        .badge-red    { background: rgba(239,68,68,0.1);  color: #f87171; border: 1px solid rgba(239,68,68,0.2); }
        .badge-blue   { background: rgba(59,130,246,0.12); color: var(--primary); border: 1px solid rgba(59,130,246,0.2); }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
            font-family: 'Outfit', sans-serif;
        }

        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }

        .btn-ghost { background: rgba(255,255,255,0.05); color: var(--text-muted); border-color: var(--border); }
        .btn-ghost:hover { background: rgba(255,255,255,0.09); color: var(--text); }

        .btn-danger { background: rgba(239,68,68,0.1); color: #f87171; border-color: rgba(239,68,68,0.2); }
        .btn-danger:hover { background: rgba(239,68,68,0.2); }

        .btn-sm { padding: 5px 10px; font-size: 12px; }

        /* ---- Jogos ---- */
        .jogos-list { 
            padding: 20px; 
            display: flex; 
            flex-direction: column; 
            gap: 20px; 
        }

        .jogo-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid transparent;
            transition: all 0.2s;
            cursor: pointer;
        }

        .jogo-row:hover { background: rgba(59,130,246,0.06); border-color: rgba(59,130,246,0.15); }
        .jogo-row.has-override { border-color: rgba(34,197,94,0.2); background: rgba(34,197,94,0.04); }

        .jogo-time { 
            font-size: 20px; 
            font-weight: 700; 
            color: #20ff77; 
            min-width: 44px;
            text-align: center; 
        }

        .jogo-title { 
            flex: 1; 
            font-size: 18px; 
            font-weight: 600; 
        }

        .jogo-league { 
            font-size: 14px; 
            color: var(--text-muted); 
        }

        .jogo-canal-tags {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }

        .canal-tag { 
            font-size: 14px; 
            font-weight: 700; 
            padding: 6px 10px; 
            border-radius: 8px; 
            background: rgba(255, 255, 255, 0.06); 
            color: var(--text-muted); 
        }

        .canal-tag.override {
            background: rgba(34,197,94,0.12);
            color: #4ade80;
        }

        .canal-tag.api {
            background: #dc2626;
            color: #ffffff;
        }

        .edit-icon {
            color: var(--text-muted);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .jogo-row:hover .edit-icon { opacity: 1; }

        /* ---- Modal ---- */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(5,7,10,0.85);
            backdrop-filter: blur(10px);
            z-index: 500;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.open { display: flex; }

        .modal {
            background: #0d1117;
            border: 1px solid var(--border);
            border-radius: 20px;
            width: 100%;
            max-width: 560px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 24px 60px rgba(0,0,0,0.6);
            animation: modal-in 0.2s ease;
        }

        @keyframes modal-in {
            from { opacity:0; transform: scale(0.96) translateY(8px); }
        }

        .modal-header {
            padding: 22px 24px 16px;
            border-bottom: 1px solid var(--border);
        }

        .modal-header h3 { font-size: 17px; font-weight: 700; }
        .modal-header p  { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

        .modal-body { padding: 20px 24px; }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .canais-overrides-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 14px; }

        .canal-override-item {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
        }

        .canal-override-item input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text);
            font-size: 14px;
            font-family: 'Outfit', sans-serif;
            outline: none;
            list-style: none;
        }

        .canal-override-item input::placeholder { color: rgba(148,163,184,0.4); }

        .canal-override-item button {
            background: none; border: none; cursor: pointer; color: var(--text-muted);
            display: flex; align-items: center; transition: color 0.2s;
        }

        .canal-override-item button:hover { color: #f87171; }

        .ovr-suggestions {
            display: none;
            background: #0b0f16;
            border: 1px solid var(--border);
            border-radius: 8px;
            max-height: 160px;
            overflow-y: auto;
            padding: 6px;
        }

        .ovr-suggestion-item {
            padding: 8px 10px;
            border-radius: 6px;
            font-size: 12px;
            color: var(--text-muted);
            cursor: pointer;
        }

        .ovr-suggestion-item:hover { background: rgba(59,130,246,0.15); color: #fff; }

        .quality-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid rgba(148,163,184,0.35);
            font-size: 11px;
            color: var(--text-muted);
            cursor: pointer;
            user-select: none;
        }

        .quality-chip input { display: none; }
        .quality-chip.active {
            border-color: rgba(34,197,94,0.6);
            color: #86efac;
            background: rgba(34,197,94,0.12);
        }

        .api-canais-info {
            background: rgba(59,130,246,0.05);
            border: 1px solid rgba(59,130,246,0.12);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 13px;
        }

        .api-canais-info strong { color: var(--primary); display: block; margin-bottom: 4px; }
        .api-canais-info span  { color: var(--text-muted); }

        .form-input-inline {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 14px;
            color: var(--text);
            font-size: 14px;
            font-family: 'Outfit', sans-serif;
            outline: none;
            margin-bottom: 10px;
            transition: border-color 0.2s;
        }

        .form-input-inline:focus { border-color: var(--primary); }
        .form-input-inline::placeholder { color: rgba(148,163,184,0.4); }

        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-muted);
        }

        .empty-state svg { opacity: 0.3; margin-bottom: 12px; }
        .empty-state p { font-size: 14px; }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
        }

        .stat-card .stat-label { font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .stat-value { font-size: 28px; font-weight: 800; margin-top: 6px; }

        .spinner-sm {
            display: inline-block;
            width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,0.2);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 768px) {
            .sidebar {
                width: 88vw;
                max-width: 320px;
                left: -100%;
                transform: none;
                transition: left 0.3s ease;
                z-index: 300;
            }
            .sidebar.open { left: 0; }
            .sidebar-backdrop { z-index: 290; }
            .sidebar-nav { padding: 8px; }
            .nav-item { font-size: 14px; padding: 12px; }

            .main { margin-left: 0; padding: 16px 12px; }
            .page-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 10px;
            }
            .page-header h1 { font-size: 20px; }
            .mobile-sidebar-toggle { display: inline-flex; }

            .card-header {
                padding: 14px 14px;
                align-items: stretch;
                flex-direction: column;
                gap: 10px;
            }
            .card-header h2 { font-size: 18px; }
            .card-header .btn { width: 100%; justify-content: center; }

            .jogos-list {
                padding: 8px;
                gap: 10px;
            }
            .jogo-row {
                flex-wrap: nowrap;
                align-items: flex-start;
                gap: 10px;
                padding: 12px 32px 12px 10px;
                border-radius: 10px;
                position: relative;
            }
            .jogo-time {
                font-size: 18px;
                min-width: 42px;
                line-height: 1.1;
                text-align: left;
                padding-top: 2px;
                flex-shrink: 0;
            }
            .jogo-info {
                flex: 1;
                min-width: 0;
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            .jogo-title {
                font-size: 14px;
                line-height: 1.25;
                word-break: break-word;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .jogo-league {
                font-size: 11px;
                line-height: 1.2;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                color: var(--text-muted);
            }
            .jogo-canais {
                flex-shrink: 0;
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 4px;
                padding-top: 2px;
            }
            .jogo-canal-tags {
                justify-content: flex-end;
                margin-top: 0;
                row-gap: 4px;
            }
            .canal-tag {
                font-size: 9px;
                padding: 3px 6px;
                white-space: nowrap;
            }
            .edit-icon {
                opacity: 1;
                position: absolute;
                right: 8px;
                top: 50%;
                transform: translateY(-50%);
                flex-shrink: 0;
            }

            .modal-overlay { padding: 10px; }
            .modal { max-height: 94vh; }
            .modal-header, .modal-body, .modal-footer { padding-left: 14px; padding-right: 14px; }
            .modal-body { padding-top: 14px; }

            .modal-header h3 { font-size: 18px; }
            .modal-header p { font-size: 13px; }

            .api-canais-info { padding: 12px 14px; }
            .api-canais-info strong { font-size: 14px; }
            .api-canais-info span { font-size: 13px; }

            .canais-overrides-list { gap: 10px; }
            .canal-override-item {
                padding: 14px 12px;
                gap: 10px;
            }
            .canal-override-item input { font-size: 15px; }

            .quality-chip {
                padding: 6px 12px;
                font-size: 12px;
            }

            .modal-footer {
                flex-direction: column-reverse;
                gap: 8px;
            }
            .modal-footer .btn { width: 100%; justify-content: center; }

            .ovr-suggestions { max-height: 180px; }
            .ovr-suggestion-item { font-size: 14px; padding: 10px 12px; }
        }
    </style>
</head>
<body>

<!-- ============ SIDEBAR ============ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-text">Elite<span>PLAY</span></div>
        <div class="admin-badge">Painel Admin</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Geral</div>
        <button class="nav-item active" onclick="showSection('dashboard', this)" id="nav-dashboard">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </button>

        <div class="nav-section-label">Conteúdo</div>
        <button class="nav-item" onclick="showSection('jogos', this)" id="nav-jogos">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
            Jogos do Dia
        </button>

        <div class="nav-section-label">Administração</div>
        <button class="nav-item" onclick="showSection('usuarios', this)" id="nav-usuarios">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Usuários
        </button>

        <a href="index.php" class="nav-item" style="margin-top:8px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Ir para o Site
        </a>

        <a href="logout.php" class="nav-item btn-danger" style="margin-top:4px; color:#f87171;">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sair
        </a>
    </nav>

    <div class="sidebar-footer">
        Logado como <strong><?php echo htmlspecialchars($me['email']); ?></strong>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebar-backdrop" onclick="closeSidebarAdmin()"></div>

<!-- ============ MAIN ============ -->
<main class="main">

    <!-- --- DASHBOARD --- -->
    <section class="section active" id="section-dashboard">
        <div class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p>Visão geral do ElitePLAY</p>
            </div>
            <button class="mobile-sidebar-toggle" onclick="toggleSidebarAdmin()" aria-label="Abrir menu">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
        </div>
        <div class="stats-row" id="stats-row">
            <div class="stat-card">
                <div class="stat-label">Online Agora</div>
                <div class="stat-value" id="stat-online">—</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Usuários Cadastrados</div>
                <div class="stat-value" id="stat-usuarios">—</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Sessões Ativas</div>
                <div class="stat-value" id="stat-sessoes">—</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Jogos Hoje</div>
                <div class="stat-value" id="stat-jogos">—</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Overrides Ativos</div>
                <div class="stat-value" id="stat-overrides">—</div>
            </div>
        </div>
    </section>

    <!-- --- JOGOS DO DIA --- -->
    <section class="section" id="section-jogos">
        <div class="page-header">
            <h1>Jogos do Dia</h1>
            <p>Clique em um jogo para editar os canais onde está passando</p>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>📅 <?php echo date('d/m/Y'); ?></h2>
                <button class="btn btn-ghost btn-sm" onclick="carregarJogos(); this.disabled=true; setTimeout(()=>this.disabled=false,2000)">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    Atualizar
                </button>
            </div>
            <div class="jogos-list" id="jogos-list">
                <div class="empty-state"><p>Carregando jogos...</p></div>
            </div>
        </div>
    </section>

    <!-- --- USUÁRIOS --- -->
    <section class="section" id="section-usuarios">
        <div class="page-header">
            <h1>Usuários</h1>
            <p>Gerencie quem tem acesso à plataforma</p>
        </div>
        <div class="card">
            <div class="card-header">
                <h2>Lista de Usuários</h2>
                <button class="btn btn-primary btn-sm" onclick="abrirModalNovoUsuario()">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Novo Usuário
                </button>
            </div>
            <div id="usuarios-table-wrap">
                <div class="empty-state"><p>Carregando usuários...</p></div>
            </div>
        </div>
    </section>

</main>

<!-- ============ MODAL EDITAR JOGO ============ -->
<div id="modal-jogo" onclick="if(event.target===this)fecharModalJogo()" style="display:none; position:fixed; inset:0; background:rgba(5,7,10,0.9); backdrop-filter:blur(8px); z-index:1000; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#0d1117; border:1px solid #30363d; border-radius:16px; width:100%; max-width:580px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,0.7);">
        <div style="padding:20px 24px 16px; border-bottom:1px solid #30363d; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 id="modal-jogo-title" style="margin:0; font-size:17px; font-weight:700;">Editar Canais do Jogo</h3>
                <p id="modal-jogo-subtitle" style="margin:4px 0 0; font-size:13px; color:#8b949e;"></p>
            </div>
            <button onclick="fecharModalJogo()" style="background:none; border:none; color:#8b949e; font-size:24px; cursor:pointer; padding:4px 8px;">&times;</button>
        </div>
        <div style="padding:20px 24px;">
            <div id="api-canais-info" style="background:#161b22; border:1px solid #30363d; border-radius:8px; padding:12px 14px; margin-bottom:14px;">
                <strong>Canais na API:</strong>
                <span id="api-canais-lista" style="color:#8b949e;">—</span>
            </div>
            <p style="font-size:13px; color:#8b949e; margin:0 0 14px;">
                📝 O primeiro item representa o canal original da API. Você pode remover o canal inteiro ou só qualidades.
            </p>
            <div id="canais-overrides-list"></div>
            <button class="btn btn-ghost btn-sm" onclick="adicionarCanalInput()" style="margin-top:12px; display:inline-flex; align-items:center; gap:6px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Adicionar Canal
            </button>
        </div>
        <div style="padding:16px 24px; border-top:1px solid #30363d; display:flex; gap:12px; justify-content:flex-end;">
            <button class="btn btn-ghost" onclick="fecharModalJogo()">Cancelar</button>
            <button class="btn btn-primary" onclick="salvarOverride()" id="btn-salvar-override">Salvar Alterações</button>
        </div>
    </div>
</div>

<!-- ============ MODAL NOVO USUARIO ============ -->
<div class="modal-overlay" id="modal-usuario">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <h3>Adicionar Usuário</h3>
            <p>O usuário poderá acessar o site com este e-mail</p>
        </div>
        <div class="modal-body">
            <label style="font-size:12px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:8px;">E-mail</label>
            <input type="email" id="novo-usuario-email" class="form-input-inline" placeholder="email@exemplo.com">
            <p id="novo-usuario-erro" style="color:#f87171; font-size:13px; display:none;"></p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="document.getElementById('modal-usuario').classList.remove('open')">Cancelar</button>
            <button class="btn btn-primary" onclick="salvarNovoUsuario()">Adicionar</button>
        </div>
    </div>
</div>

<script>
    const CSRF_TOKEN = '<?php echo $csrfToken; ?>';
    // ====================================================
    // Navegação entre seções
    // ====================================================
    function toggleSidebarAdmin() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (!sidebar || !backdrop) return;
        sidebar.classList.toggle('open');
        backdrop.classList.toggle('open');
    }

    function closeSidebarAdmin() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (!sidebar || !backdrop) return;
        sidebar.classList.remove('open');
        backdrop.classList.remove('open');
    }

    function showSection(name, btn) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
        document.getElementById('section-' + name).classList.add('active');
        if (btn) btn.classList.add('active');

        if (window.innerWidth <= 768) closeSidebarAdmin();

        if (name === 'usuarios') { carregarUsuarios(); carregarStats(); }
        if (name === 'jogos')    carregarJogos();
        if (name === 'dashboard') carregarStats();
    }

    // ====================================================
    // Dashboard - Stats
    // ====================================================
    async function carregarStats() {
        const [users, sessRes, j, ovr, onlineRes] = await Promise.all([
            fetch('admin_api.php?action=list_users').then(r=>r.json()).catch(()=>[]),
            fetch('admin_api.php?action=active_sessions').then(r=>r.json()).catch(()=>({count: '?'})),
            fetch('https://embedtv.best/api/jogos').then(r=>r.json()).catch(()=>[]),
            fetch('admin_api.php?action=get_overrides').then(r=>r.json()).catch(()=>({})),
            fetch('admin_api.php?action=online_count').then(r=>r.json()).catch(()=>({online_count: '?'}))
        ]);
        document.getElementById('stat-online').textContent = onlineRes.online_count || '0';
        document.getElementById('stat-usuarios').textContent = Array.isArray(users) ? users.length : '?';
        document.getElementById('stat-jogos').textContent = Array.isArray(j) ? j.length : '?';
        document.getElementById('stat-overrides').textContent = typeof ovr === 'object' ? Object.keys(ovr).length : '?';
        document.getElementById('stat-sessoes').textContent = sessRes.count;
    }

    // ====================================================
    // Usuários
    // ====================================================
    async function carregarUsuarios() {
        const wrap = document.getElementById('usuarios-table-wrap');
        wrap.innerHTML = '<div class="empty-state"><p>Carregando...</p></div>';
        const users = await fetch('admin_api.php?action=list_users').then(r=>r.json()).catch(()=>[]);

        if (!users.length) {
            wrap.innerHTML = '<div class="empty-state"><p>Nenhum usuário cadastrado.</p></div>';
            return;
        }

        let rows = users.map(u => `
            <tr data-id="${u.id}">
                <td>
                    ${u.email}
                    ${u.is_admin ? '<span class="badge badge-blue" style="margin-left:8px;">ADMIN</span>' : ''}
                    ${u.is_online ? '<span class="badge badge-green" style="margin-left:8px;">● ONLINE</span>' : ''}
                </td>
                <td>
                    <span class="badge ${u.ativo ? 'badge-green' : 'badge-red'}">
                        ${u.ativo ? '● Ativo' : '○ Bloqueado'}
                    </span>
                </td>
                <td>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <input type="number" min="0" value="${u.dias_acesso ?? ''}" 
                               placeholder="∞" style="width:70px; padding:6px 8px; background:var(--bg-secondary); border:1px solid var(--border); border-radius:6px; color:var(--text); font-size:13px;"
                               onchange="salvarDiasAcesso(${u.id}, this.value, this)"
                               ${u.is_admin ? 'disabled' : ''}>
                        <span style="font-size:12px; color:var(--text-muted);">dias</span>
                    </div>
                </td>
                <td style="font-size:12px; color:var(--text-muted);">${u.created_at?.slice(0,10) || '—'}</td>
                <td>
                    ${!u.is_admin ? `
                        <button class="btn btn-ghost btn-sm" onclick="toggleUsuario(${u.id}, this)">
                            ${u.ativo ? 'Bloquear' : 'Ativar'}
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deletarUsuario(${u.id}, '${u.email}', this)" style="margin-left:6px;">Remover</button>
                    ` : '<span style="color:var(--text-muted); font-size:12px;">—</span>'}
                </td>
            </tr>
        `).join('');

        wrap.innerHTML = `
            <table>
                <thead>
                    <tr>
                        <th>E-mail</th>
                        <th>Status</th>
                        <th>Dias de Acesso</th>
                        <th>Cadastrado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        `;
    }

    async function toggleUsuario(id, btn) {
        btn.disabled = true;
        const res = await fetch('admin_api.php?action=toggle_user', {
            method: 'POST',
            headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN},
            body: JSON.stringify({id})
        }).then(r=>r.json()).catch(()=>null);
        if (res?.ok) carregarUsuarios();
        else { alert(res?.error || 'Erro.'); btn.disabled = false; }
    }

    async function deletarUsuario(id, email, btn) {
        if (!confirm(`Remover o usuário "${email}"? Esta ação não pode ser desfeita.`)) return;
        btn.disabled = true;
        const res = await fetch('admin_api.php?action=delete_user', {
            method: 'POST',
            headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN},
            body: JSON.stringify({id})
        }).then(r=>r.json()).catch(()=>null);
        if (res?.ok) carregarUsuarios();
        else { alert(res?.error || 'Erro.'); btn.disabled = false; }
    }

    async function salvarDiasAcesso(id, dias, input) {
        const diasInt = dias === '' ? null : parseInt(dias);
        if (diasInt !== null && isNaN(diasInt)) {
            alert('Valor inválido');
            return;
        }
        try {
            const res = await fetch('admin_api.php?action=update_dias_acesso', {
                method: 'POST',
                headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN},
                body: JSON.stringify({ id, dias: diasInt })
            }).then(r=>r.json()).catch(()=>null);
            
            if (res?.ok) {
                input.style.borderColor = '#22c55e';
                setTimeout(() => input.style.borderColor = '', 1000);
            } else {
                alert(res?.error || 'Erro ao salvar.');
            }
        } catch(e) {
            alert('Erro ao salvar dias de acesso.');
        }
    }

    function abrirModalNovoUsuario() {
        document.getElementById('novo-usuario-email').value = '';
        document.getElementById('novo-usuario-erro').style.display = 'none';
        document.getElementById('modal-usuario').classList.add('open');
        setTimeout(() => document.getElementById('novo-usuario-email').focus(), 100);
    }

    async function salvarNovoUsuario() {
        const email = document.getElementById('novo-usuario-email').value.trim();
        const erroEl = document.getElementById('novo-usuario-erro');
        erroEl.style.display = 'none';
        if (!email) return;

        const res = await fetch('admin_api.php?action=add_user', {
            method: 'POST',
            headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN},
            body: JSON.stringify({email})
        }).then(r=>r.json()).catch(()=>null);

        if (res?.ok) {
            document.getElementById('modal-usuario').classList.remove('open');
            carregarUsuarios();
        } else {
            erroEl.textContent = res?.error || 'Erro ao adicionar.';
            erroEl.style.display = 'block';
        }
    }

    // ====================================================
    // Jogos do Dia
    // ====================================================
    let todosOsJogos  = [];
    let todosOverrides = {};
    let jogoAtual     = null;
    let catalogoCanais = {};
    const OVERRIDE_REMOVE_ORIGINAL_KEY = '__ORIGINAL_API__';
    const localDateYmd = (d = new Date()) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const normalizeName = (name = '') => {
        let n = String(name).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[\s\-]/g, '').trim();
        if (n === 'espn' || /^espn0?1$/.test(n)) n = 'espn1';
        if (/premiereclubes|premiereserie/i.test(n)) n = 'premiere1';
        return n;
    };
    function parseNomeQualidade(fullName = '') {
        const original = String(fullName).trim();
        const regex = /^(.*?)\s*((?:FHD|HD|SD|4K|1080P|720P|ALT|\*|\[ALT\]|\(ALT\)|\s|-)+)$/i;
        const m = original.match(regex);
        if (m && m[1]) return { baseName: m[1].trim(), quality: m[2].trim().toUpperCase() };
        return { baseName: original, quality: 'PRINCIPAL' };
    }

    function upsertCanalCatalogo(nomeCanal, qualidade) {
        const baseName = String(nomeCanal || '').trim();
        if (!baseName) return;
        const key = normalizeName(baseName);
        // Canonical names for unified channels
        let canonicalName = baseName;
        if (key === 'espn1') canonicalName = 'ESPN 1';
        if (key === 'premiere1') canonicalName = 'PREMIERE CLUBES';
        if (!catalogoCanais[key]) catalogoCanais[key] = { name: canonicalName, qualities: new Set() };
        if (!catalogoCanais[key]) catalogoCanais[key] = { name: canonicalName, qualities: new Set() };
        if (qualidade) catalogoCanais[key].qualities.add(String(qualidade).trim().toUpperCase());
    }

    function getCanalCatalogoByName(nome) {
        const key = normalizeName(nome);
        return catalogoCanais[key] || null;
    }

    function getCanalSugestoes(query) {
        const q = normalizeName(query);
        if (!q) return [];
        return Object.values(catalogoCanais)
            .filter(c => normalizeName(c.name).includes(q))
            .sort((a, b) => a.name.localeCompare(b.name, 'pt-BR'))
            .slice(0, 12);
    }

    async function carregarCatalogoCanais() {
        catalogoCanais = {};
        const [res70, resEmbed] = await Promise.all([
            fetch(`https://embed.70noticias.com.br/?api=1&t=live&c=all&_t=${Date.now()}`, { cache: 'no-store' }).then(r=>r.json()).catch(()=>({})),
            fetch(`https://embedtv.best/api/channels?_t=${Date.now()}`, { cache: 'no-store' }).then(r=>r.json()).catch(()=>null)
        ]);

        Object.keys(res70 || {}).forEach(cat => {
            const canais = res70[cat];
            if (!Array.isArray(canais)) return;
            canais.forEach(c => {
                const parsed = parseNomeQualidade(c.nome || '');
                upsertCanalCatalogo(parsed.baseName, parsed.quality || 'PRINCIPAL');
            });
        });

        if (resEmbed && Array.isArray(resEmbed.channels)) {
            resEmbed.channels.forEach(c => {
                const channelName = String(c.name || '').trim();
                // Armazena com qualidade ELITE + nome do canal
                upsertCanalCatalogo(channelName, 'ELITE ' + channelName.toUpperCase());
            });
        }
    }

    async function carregarJogos() {
        const lista = document.getElementById('jogos-list');
        lista.innerHTML = '<div class="empty-state"><p>Carregando jogos da API...</p></div>';

        const [jogos, overrides] = await Promise.all([
            fetch(`https://embedtv.best/api/jogos?_t=${Date.now()}`, { cache: 'no-store' }).then(r=>r.json()).catch(()=>[]),
            fetch(`admin_api.php?action=get_overrides&data=${localDateYmd()}&_t=${Date.now()}`, { cache: 'no-store' }).then(r=>r.json()).catch(()=>({}))
        ]);

        let jogosFetch = Array.isArray(jogos) ? jogos : [];
        todosOsJogos   = jogosFetch;
        todosOverrides = overrides || {};

        if (!todosOsJogos.length) {
            lista.innerHTML = '<div class="empty-state"><svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg><p>Nenhum jogo disponível para hoje.</p></div>';
            return;
        }

        lista.innerHTML = todosOsJogos.map((j, idx) => {
            const hora = j.time || (j.data?.timer?.start ? (() => {
                const d = new Date(j.data.timer.start * 1000);
                return `${String(d.getHours()).padStart(2,'0')}h${String(d.getMinutes()).padStart(2,'0')}`;
            })() : '—');

            const jid       = (j.id !== undefined && j.id !== null && String(j.id).trim() !== '')
                ? String(j.id)
                : (j.data?.timer?.start ? `idx_${j.data.timer.start}` : `idx_${normalizeName(j.title || idx)}`);
            const hasOvr    = !!todosOverrides[jid];
            const canaisApi = (j.players || []).map(p => {
                const parts = p.split('/');
                return parts[parts.length - 1] || p;
            }).join(', ') || '—';

            const ovrTags = hasOvr
                ? (todosOverrides[jid] || [])
                    .filter(c => String(c?.name || '') !== OVERRIDE_REMOVE_ORIGINAL_KEY)
                    .map(c => `<span class="canal-tag override">${c.name}</span>`).join('')
                : '';

            return `
                <div class="jogo-row ${hasOvr ? 'has-override' : ''}" onclick="abrirModalJogo(${idx}, '${jid}')">
                    <span class="jogo-time">${hora}</span>
                    <div class="jogo-info">
                        <div class="jogo-title">${j.title}</div>
                        <div class="jogo-league">${j.data?.league || ''}</div>
                    </div>
                    <div class="jogo-canais">
                        <div class="jogo-canal-tags">
                            ${hasOvr ? `<span class="canal-tag api">${canaisApi}</span>${ovrTags}` : `<span class="canal-tag">${canaisApi}</span>`}
                        </div>
                    </div>
                    <svg class="edit-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </div>
            `;
        }).join('');

        // Atualiza o counter no dashboard
        document.getElementById('stat-jogos').textContent = todosOsJogos.length;
        document.getElementById('stat-overrides').textContent = Object.keys(todosOverrides).length;
    }

    function abrirModalJogo(idx, jid) {
        console.log('abrirModalJogo chamado', idx, jid);
        const j = todosOsJogos[idx];
        jogoAtual = { jid, j, idx };

        document.getElementById('modal-jogo-title').textContent = j.title;
        document.getElementById('modal-jogo-subtitle').textContent =
            `${j.data?.league || ''} • Hoje`.trim();

        const apiIds = (j.players || []).map(p => {
            const parts = decodeURIComponent(p).split('/').pop() || '';
            return parts.split('?')[0];
        }).filter(Boolean);
        const canaisApi = apiIds.join(' | ') || '—';
        document.getElementById('api-canais-lista').textContent = canaisApi;

        const originalRaw = apiIds[0] || '';
        console.log('originalRaw:', originalRaw);
        const originalCanal = getCanalCatalogoByName(originalRaw) || (originalRaw ? { name: originalRaw, qualities: new Set(['PRINCIPAL']) } : null);
        console.log('originalCanal:', originalCanal);

        const existentes = todosOverrides[jid] || [];
        const originalCfg = existentes.find(c => String(c?.name || '') === OVERRIDE_REMOVE_ORIGINAL_KEY) || {};

        const lista = document.getElementById('canais-overrides-list');
        lista.innerHTML = '';

        console.log('apiIds completos:', apiIds);
        console.log('tem jogoAtual.j.players?', j.players);

        if (originalCanal) {
            adicionarCanalInput({
                name: originalCanal.name,
                __system_name: OVERRIDE_REMOVE_ORIGINAL_KEY,
                __is_original: true,
                remove_channel: !!originalCfg.remove_channel,
                remove_qualities: Array.isArray(originalCfg.remove_qualities) ? originalCfg.remove_qualities : []
            });
        }

        existentes
            .filter(c => String(c?.name || '') !== OVERRIDE_REMOVE_ORIGINAL_KEY)
            .forEach(c => adicionarCanalInput(c));

        document.getElementById('modal-jogo').style.display = 'flex';
    }

    function fecharModalJogo() {
        document.getElementById('modal-jogo').style.display = 'none';
        jogoAtual = null;
    }

    function renderQualidadesCanal(item, selectedQualities = []) {
        const nameInput = item.querySelector('.ovr-name');
        const wrap = item.querySelector('.quality-options');
        const canal = getCanalCatalogoByName(nameInput.value);

        if (!canal || !canal.qualities || canal.qualities.size === 0) {
            wrap.innerHTML = '<span style="font-size:12px;color:var(--text-muted);">Selecione um canal válido para carregar qualidades.</span>';
            return;
        }

        const qualities = [...canal.qualities].sort((a,b) => a.localeCompare(b, 'pt-BR'));
        // Se selectedQualities vazio, marca todos; senão usa os selecionados
        const toSelect = selectedQualities.length > 0 ? selectedQualities.map(q => String(q).toUpperCase()) : qualities.map(q => q.toUpperCase());

        wrap.innerHTML = qualities.map(q => {
            const isSelected = toSelect.includes(q.toUpperCase());
            return `<label class="quality-chip ${isSelected ? 'active' : ''}"><input type="checkbox" class="ovr-quality" value="${q}" ${isSelected ? 'checked' : ''}>${q}</label>`;
        }).join('');

        wrap.querySelectorAll('.quality-chip').forEach(chip => {
            const cb = chip.querySelector('input');
            chip.addEventListener('click', () => setTimeout(() => chip.classList.toggle('active', cb.checked), 0));
        });
    }

    function renderSugestoesCanal(item) {
        const nameInput = item.querySelector('.ovr-name');
        const box = item.querySelector('.ovr-suggestions');
        const sugestoes = getCanalSugestoes(nameInput.value);
        if (!nameInput.value.trim() || sugestoes.length === 0) { box.style.display = 'none'; box.innerHTML = ''; return; }

        box.innerHTML = sugestoes.map(c => `<div class="ovr-suggestion-item" data-name="${c.name.replace(/"/g, '&quot;')}">${c.name}</div>`).join('');
        box.style.display = 'block';
        box.querySelectorAll('.ovr-suggestion-item').forEach(el => {
            el.addEventListener('mousedown', (e) => {
                e.preventDefault();
                nameInput.value = el.dataset.name;
                box.style.display = 'none';
                renderQualidadesCanal(item);
            });
        });
    }

    function adicionarCanalInput(valor = '') {
        const itemData = (valor && typeof valor === 'object') ? valor : { name: valor };
        const isOriginal = !!itemData.__is_original;
        const systemName = String(itemData.__system_name || '');
        const nomeRaw = String(itemData.name || '').trim();
        const canal = getCanalCatalogoByName(nomeRaw);
        const nome = (canal ? canal.name : nomeRaw).replace(/"/g,'&quot;');
        const removeChannel = !!itemData.remove_channel;

        // Support both new 'qualities' (full control) and old 'remove_qualities' (backward compat)
        let qualitiesToLoad = [];
        if (Array.isArray(itemData.qualities) && itemData.qualities.length > 0) {
            qualitiesToLoad = itemData.qualities.map(q => String(q).toUpperCase());
        } else if (Array.isArray(itemData.remove_qualities) && itemData.remove_qualities.length > 0) {
            // Backward compat: convert remove_qualities to qualities for display
            const canalQualities = canal ? [...canal.qualities].map(q => q.toUpperCase()) : [];
            qualitiesToLoad = canalQualities.filter(q => !itemData.remove_qualities.map(rq => rq.toUpperCase()).includes(q));
        }

        const lista = document.getElementById('canais-overrides-list');
        const item = document.createElement('div');
        item.className = 'canal-override-item';
        item.innerHTML = `
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--text-muted);flex-shrink:0;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <div style="flex:1;display:flex;flex-direction:column;gap:6px;">
                <input class="ovr-name" type="text" ${isOriginal ? 'readonly' : ''} placeholder="Digite para buscar canal..." value="${nome}" style="background:transparent;border:none;color:var(--text);font-size:14px;outline:none;">
                <input type="hidden" class="ovr-system-name" value="${systemName}">
                <div class="ovr-suggestions" style="${isOriginal ? 'display:none;' : ''}"></div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                    <label style="font-size:12px;color:var(--text-muted);display:flex;gap:6px;align-items:center;"><input class="ovr-remove-channel" type="checkbox" ${removeChannel ? 'checked' : ''}> ${isOriginal ? 'Remover canal original da API deste jogo' : 'Remover canal inteiro'}</label>
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Selecione as qualidades que devem aparecer (deixe todas marcadas para usar todas)</div>
                <div class="ovr quality-options" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
            </div>
            <button onclick="this.parentElement.remove()" title="Remover" ${isOriginal ? 'style="display:none;"' : ''}>
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        `;

        const nameInput = item.querySelector('.ovr-name');
        if (!isOriginal) {
            nameInput.addEventListener('input', () => { renderSugestoesCanal(item); renderQualidadesCanal(item); });
            nameInput.addEventListener('focus', () => { if (nameInput.value.trim()) renderSugestoesCanal(item); });
            nameInput.addEventListener('blur', () => setTimeout(() => { const box = item.querySelector('.ovr-suggestions'); box.style.display = 'none'; }, 120));
        }

        lista.appendChild(item);
        renderQualidadesCanal(item, qualitiesToLoad);
    }

    async function salvarOverride() {
        if (!jogoAtual) return;
        const btn = document.getElementById('btn-salvar-override');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-sm"></span>';

        const itens = [...document.querySelectorAll('#canais-overrides-list .canal-override-item')];
        const invalidos = [];
        const canais = itens.map(item => {
            const rawName = item.querySelector('.ovr-name')?.value?.trim() || '';
            const systemName = item.querySelector('.ovr-system-name')?.value?.trim() || '';
            if (!rawName) return null;

            const allQualities = [...item.querySelectorAll('.ovr-quality')].map(el => String(el.value || '').toUpperCase());
            const checkedQualities = [...item.querySelectorAll('.ovr-quality:checked')].map(el => String(el.value || '').toUpperCase());
            const remove_channel = !!item.querySelector('.ovr-remove-channel')?.checked;

            if (systemName === OVERRIDE_REMOVE_ORIGINAL_KEY) {
                // Para ORIGINAL, se todas marcadas = não remover nada; se alguma desmarcada = remover as desmarcadas
                const remove_qualities = allQualities.filter(q => !checkedQualities.includes(q));
                // Original SEMPRE deve ser enviado para manter o canal da API
                if (remove_channel) {
                    return { name: OVERRIDE_REMOVE_ORIGINAL_KEY, remove_channel: true, remove_qualities: [] };
                } else if (remove_qualities.length > 0) {
                    return { name: OVERRIDE_REMOVE_ORIGINAL_KEY, remove_channel: false, remove_qualities };
                } else {
                    // Se não quer remover nada, envia remove_channel: false para manter o original
                    return { name: OVERRIDE_REMOVE_ORIGINAL_KEY, remove_channel: false, remove_qualities: [] };
                }
            }

            const canal = getCanalCatalogoByName(rawName);
            if (!canal) {
                invalidos.push(rawName);
                return null;
            }

            // CONTROLE TOTAL: se todas qualidades marcadas, não especifica qualities (usa todos)
            // se algumas desmarcadas, 저장 apenas as marcadas (qualities = controle total)
            const qualities = (checkedQualities.length > 0 && checkedQualities.length < allQualities.length)
                ? checkedQualities
                : [];

            return { name: canal.name, remove_channel, qualities };
        }).filter(Boolean);

        if (invalidos.length > 0) {
            btn.disabled = false;
            btn.innerHTML = 'Salvar Alterações';
            alert(`Selecione canais válidos da lista: ${invalidos.join(', ')}`);
            return;
        }

        const j = jogoAtual.j;
        const d = j.data?.timer?.start ? new Date(j.data.timer.start * 1000) : new Date();
        const dataJogo = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;

        const res = await fetch('admin_api.php?action=save_override', {
            method: 'POST',
            headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN},
            body: JSON.stringify({
                jogo_id:   jogoAtual.jid,
                titulo:    j.title,
                data_jogo: dataJogo,
                canais
            })
        }).then(r=>r.json()).catch(()=>null);

        console.log('[ADMIN] Salvando override:', jogoAtual.jid, canais);

        btn.disabled = false;
        btn.innerHTML = 'Salvar Alterações';

        if (res?.ok) {
            fecharModalJogo();
            carregarJogos();
        } else {
            alert(res?.error || 'Erro ao salvar.');
        }
    }

    // ====================================================
    // Inicialização
    // ====================================================
    carregarCatalogoCanais().then(() => {
        carregarStats();
    });
</script>

</body>
</html>
