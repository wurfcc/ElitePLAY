<?php
// ============================================================
//  middleware.php — Proteção de páginas autenticadas
//  Incluir no INÍCIO de qualquer página que exige login
//
//  Uso:
//      require_once __DIR__ . '/middleware.php';
//      // $usuario_logado['email'] e $usuario_logado['usuario_id'] disponíveis
// ============================================================
require_once __DIR__ . '/security.php';

configurar_sessao();

// Cabeçalhos de segurança para todas as páginas autenticadas
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (PRODUCAO) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$usuario_logado = validar_sessao_cookie();

if ($usuario_logado === null) {
    // Destrói a sessão PHP e o cookie para forçar nova autenticação
    if (isset($_COOKIE[SESSION_NAME])) {
        setcookie(SESSION_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure'   => PRODUCAO,
        ]);
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

// Verifica se os dias de acesso expiraram ou se é 0/nulo
if (isset($usuario_logado['expired']) && $usuario_logado['expired'] === true) {
    header('Location: pagamento.php');
    exit;
}

// Bloqueia usuário com dias zerados ou nulos (exceto admins)
if (!isset($usuario_logado['dias_acesso']) || $usuario_logado['dias_acesso'] <= 0) {
    if (!isset($usuario_logado['is_admin']) || $usuario_logado['is_admin'] != 1) {
        header('Location: pagamento.php');
        exit;
    }
}
