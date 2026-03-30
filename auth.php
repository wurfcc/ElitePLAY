<?php
// ============================================================
//  auth.php — Endpoint de autenticação (POST JSON)
//  Recebe: { "email": "...", "csrf": "..." }
//  Retorna: { "success": bool, "message": "..." }
// ============================================================
require_once __DIR__ . '/security.php';

configurar_sessao();

// Cabeçalhos de segurança
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store, no-cache');

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Parse do JSON recebido
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
    exit;
}

$email      = trim(strtolower($input['email'] ?? ''));
$csrf       = trim($input['csrf'] ?? '');
$ip         = ip_cliente();

// --- 1. Validação de CSRF ---
if (!validar_csrf($csrf)) {
    log_acesso(null, $ip, false, 'csrf_invalido');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido. Recarregue a página.']);
    exit;
}

// --- 2. Validação básica do e-mail ---
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    log_acesso(null, $ip, false, 'email_invalido');
    // Mesmo delay que uma tentativa real (evita enumeração por tempo)
    usleep(random_int(80000, 200000));
    echo json_encode(['success' => false, 'message' => 'E-mail não encontrado ou sem acesso.']);
    exit;
}

// --- 3. Rate limiting (IP + email hash) ---
$bloqueado = verificar_rate_limit($ip, $email);
if ($bloqueado !== false) {
    $minutos = ceil($bloqueado / 60);
    log_acesso(null, $ip, false, 'rate_limit');
    echo json_encode([
        'success' => false,
        'message' => "Muitas tentativas. Aguarde {$minutos} minuto(s) antes de tentar novamente."
    ]);
    exit;
}

// --- 4. Busca o usuário no banco (Prepared Statement) ---
// Delay aleatório ANTES da consulta para evitar timing attacks
usleep(random_int(80000, 200000));

try {
    $stmt = db()->prepare('
        SELECT id, ativo, is_admin, dias_acesso, acesso_expira_em
        FROM usuarios
        WHERE email = ?
        LIMIT 1
    ');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
} catch (\Throwable $e) {
    // Nunca vaze detalhes do erro ao cliente
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
    exit;
}

// --- 5. Verifica resultado — MESMA MENSAGEM para email inválido e desativado ---
//    Isso evita que um atacante descubra quais emails existem no sistema
if (!$usuario || !$usuario['ativo']) {
    registrar_tentativa($ip, $email);
    log_acesso(null, $ip, false, $usuario ? 'usuario_inativo' : 'email_nao_encontrado');
    echo json_encode(['success' => false, 'message' => 'E-mail não encontrado ou sem acesso.']);
    exit;
}

// --- 5.1 Controle definitivo por usuarios.acesso_expira_em ---
$userExpiresAt = null;
$redirectAfterLogin = 'index.php';
$expiredAccess = false;

if ((int)$usuario['is_admin'] !== 1) {
    $diasAcesso = $usuario['dias_acesso'];
    $acessoExpiraEm = $usuario['acesso_expira_em'];

    if (!empty($acessoExpiraEm)) {
        $userExpiresAt = $acessoExpiraEm;

        if (strtotime($acessoExpiraEm) <= time()) {
            db()->prepare('UPDATE usuarios SET dias_acesso = 0 WHERE id = ?')->execute([(int)$usuario['id']]);
            $expiredAccess = true;
            $redirectAfterLogin = 'pagamento.php';
        }
    } elseif ($diasAcesso !== null) {
        // Migração de legado: converte dias antigos em data absoluta de expiração
        if ((int)$diasAcesso <= 0) {
            $expiredAccess = true;
            $redirectAfterLogin = 'pagamento.php';
            $userExpiresAt = date('Y-m-d H:i:s', time() - 60);
            db()->prepare('UPDATE usuarios SET dias_acesso = 0, acesso_expira_em = ? WHERE id = ?')->execute([$userExpiresAt, (int)$usuario['id']]);
        } else {
            $userExpiresAt = date('Y-m-d H:i:s', time() + ((int)$diasAcesso * 24 * 60 * 60));
            db()->prepare('UPDATE usuarios SET acesso_expira_em = ? WHERE id = ?')->execute([$userExpiresAt, (int)$usuario['id']]);
        }
    }
}

// --- 6. Login bem-sucedido ---
// Previne session fixation: gera novo ID antes de gravar dados
session_regenerate_id(true);

// *** SESSÃO ÚNICA: Revoga TODAS as sessões anteriores deste usuário ***
// Isso garante que nenhum outro dispositivo permaneça logado
try {
    db()->prepare('
        UPDATE sessoes SET revogada = 1
        WHERE usuario_id = ? AND revogada = 0
    ')->execute([(int)$usuario['id']]);
} catch (\Throwable $e) {
    // Não bloqueia o login se a revogação falhar (tentará novamente no ping)
}

// Cria a nova sessão (única e exclusiva)
$token = criar_sessao((int)$usuario['id'], $ip, $userExpiresAt);

// Seta o cookie de autenticacao com o token puro
$cookie_options = [
    'expires'  => time() + SESSION_TTL,
    'path'     => '/',
    'domain'   => '',
    'secure'   => PRODUCAO,
    'httponly' => true,
    'samesite' => 'Strict',
];
setcookie(AUTH_COOKIE_NAME, $token, $cookie_options);

// Zera os contadores de tentativa deste IP/email
resetar_tentativas($ip, $email);
log_acesso((int)$usuario['id'], $ip, true, $expiredAccess ? 'ok_expirado' : 'ok');

// Regenera o CSRF token após login (invalida o anterior)
unset($_SESSION[CSRF_TOKEN_KEY]);

echo json_encode([
    'success' => true,
    'message' => $expiredAccess ? 'Seu plano expirou. Redirecionando para renovação...' : 'Acesso liberado!',
    'redirect' => $redirectAfterLogin,
    'expired' => $expiredAccess
]);
exit;
