<?php
// ============================================================
//  config.php — Configurações do banco e da aplicação
// ============================================================

// --- Detecção de ambiente (local vs produção) ---
// HTTP_HOST é sempre o domínio real (eliteplay.one em prod, localhost aqui)
$_isLocal = in_array(strtolower(parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST) ?? ''), ['localhost', '127.0.0.1', '::1'], true);

// --- Banco de Dados ---
define('DB_HOST',    'localhost');
define('DB_NAME',    $_isLocal ? 'eliteplay'            : 'murilo_eliteplay');
define('DB_USER',    $_isLocal ? 'root'                 : 'murilo_eliteplayuser');
define('DB_PASS',    $_isLocal ? ''                     : 'gpxAmODumpegCn7J');
define('DB_CHARSET', 'utf8mb4');

// --- Sessão / Segurança ---
define('SESSION_NAME',    'eliteplay_sess');
define('AUTH_COOKIE_NAME','eliteplay_auth');
define('SESSION_TTL',     60 * 60 * 8);
define('CSRF_TOKEN_KEY',  'eliteplay_csrf');

// --- Rate Limiting ---
define('RATE_MAX_TENTATIVAS',   5);
define('RATE_JANELA_MINUTOS',  15);
define('RATE_BLOQUEIO_MINUTOS', 30);

// --- Ambiente ---
define('PRODUCAO', !$_isLocal);
