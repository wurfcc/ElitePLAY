<?php
// ============================================================
//  logout.php — Encerramento seguro da sessão
// ============================================================
require_once __DIR__ . '/security.php';

configurar_sessao();

// Invalida o token no banco (revoga a sessão ativa)
$token_raw = $_COOKIE[AUTH_COOKIE_NAME] ?? '';
if (!empty($token_raw)) {
    $token_hash = hash('sha256', $token_raw);
    try {
        db()->prepare('
            UPDATE sessoes SET revogada = 1 WHERE token_hash = ?
        ')->execute([$token_hash]);
    } catch (\Throwable $e) {
        // Falha silenciosa — o cookie será apagado de qualquer forma
    }

    // Remove o cookie do navegador
    setcookie(AUTH_COOKIE_NAME, '', auth_cookie_options(time() - 3600));
}

// Destrói a sessão PHP
$_SESSION = [];
session_destroy();

// Redireciona para o login
header('Location: login.php');
exit;
