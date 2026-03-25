<?php
// ============================================================
//  config.php — Configurações do banco e da aplicação
//  NUNCA suba este arquivo para repositórios públicos (git)!
//  Adicione "config.php" no seu .gitignore
// ============================================================

// --- Banco de Dados ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'eliteplay');
define('DB_USER', 'root');           // Troque pelo usuário do seu servidor
define('DB_PASS', '');               // Troque pela senha do seu servidor
define('DB_CHARSET', 'utf8mb4');

// --- Sessão / Segurança ---
define('SESSION_NAME',    'eliteplay_sess');
define('SESSION_TTL',     60 * 60 * 8);   // 8 horas em segundos
define('CSRF_TOKEN_KEY',  'eliteplay_csrf');

// --- Rate Limiting ---
define('RATE_MAX_TENTATIVAS', 5);          // tentativas antes de bloquear
define('RATE_JANELA_MINUTOS', 15);         // janela de tempo (minutos)
define('RATE_BLOQUEIO_MINUTOS', 30);       // tempo de bloqueio após exceder

// --- Ambiente ---
define('PRODUCAO', false);                 // Mude para TRUE na hospedagem real
