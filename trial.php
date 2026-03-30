<?php
// ============================================================
//  trial.php — Ativa teste gratuito de 10 minutos (uma vez só)
// ============================================================
require_once __DIR__ . '/security.php';

configurar_sessao();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf  = trim($input['csrf'] ?? '');

// --- CSRF ---
$origin       = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
$host         = trim((string)($_SERVER['HTTP_HOST']   ?? ''));
$hostSemPorta = preg_replace('/:\d+$/', '', $host);
$originHost   = $origin !== '' ? (string)(parse_url($origin, PHP_URL_HOST) ?? '') : '';
$isSameOrigin = ($origin === '') || ($originHost !== '' && hash_equals(strtolower($hostSemPorta), strtolower($originHost)));

if (!validar_csrf($csrf) && !$isSameOrigin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit;
}

// --- Lê todos os tokens do cookie (lida com duplicatas) ---
$tokenRaw  = '';
$rawHeader = $_SERVER['HTTP_COOKIE'] ?? '';
foreach (explode(';', $rawHeader) as $part) {
    $part = trim($part);
    $eq   = strpos($part, '=');
    if ($eq === false) continue;
    $name = trim(substr($part, 0, $eq));
    $val  = trim(substr($part, $eq + 1));
    if ($name === AUTH_COOKIE_NAME && $val !== '') {
        $tokenRaw = $val;
        break;
    }
}

if (empty($tokenRaw)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessão inválida.', 'redirect' => 'login.php']);
    exit;
}

try {
    $pdo       = db();
    $tokenHash = hash('sha256', $tokenRaw);

    // Busca sessão ativa (mesmo que diasAcesso=0, sessão existe no BD)
    $stmt = $pdo->prepare('
        SELECT s.usuario_id, u.trial_usado, u.is_admin, u.acesso_expira_em, u.dias_acesso
        FROM sessoes s
        JOIN usuarios u ON u.id = s.usuario_id
        WHERE s.token_hash = ? AND s.revogada = 0 AND s.expires_at > NOW() AND u.ativo = 1
        LIMIT 1
    ');
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Sessão expirada. Faça login novamente.', 'redirect' => 'login.php']);
        exit;
    }

    $userId = (int)$row['usuario_id'];

    // Admins não precisam de trial
    if ((int)$row['is_admin'] === 1) {
        echo json_encode(['success' => false, 'message' => 'Conta admin não precisa de trial.', 'redirect' => 'index.php']);
        exit;
    }

    // Trial já utilizado
    if ((int)$row['trial_usado'] !== 0) {
        echo json_encode(['success' => false, 'message' => 'Você já utilizou o teste gratuito.']);
        exit;
    }

    // Já possui acesso ativo
    if (!empty($row['acesso_expira_em']) && strtotime($row['acesso_expira_em']) > time()) {
        echo json_encode(['success' => false, 'message' => 'Você já possui acesso ativo.', 'redirect' => 'index.php']);
        exit;
    }

    // --- Ativa 10 minutos de trial ---
    $trialExpira = date('Y-m-d H:i:s', time() + (10 * 60));

    $pdo->prepare('
        UPDATE usuarios SET trial_usado = 1, dias_acesso = 1, acesso_expira_em = ? WHERE id = ?
    ')->execute([$trialExpira, $userId]);

    // Revoga sessões antigas e cria nova com a nova validade
    $pdo->prepare('UPDATE sessoes SET revogada = 1 WHERE usuario_id = ? AND revogada = 0')->execute([$userId]);

    session_regenerate_id(true);
    $ip    = ip_cliente();
    $token = criar_sessao($userId, $ip, $trialExpira);

    $cookieExpires = time() + SESSION_TTL;
    $cookieOptions = function_exists('auth_cookie_options')
        ? auth_cookie_options($cookieExpires)
        : ['expires' => $cookieExpires, 'path' => '/', 'domain' => '', 'secure' => PRODUCAO, 'httponly' => true, 'samesite' => 'Lax'];
    setcookie(AUTH_COOKIE_NAME, $token, $cookieOptions);

    unset($_SESSION[CSRF_TOKEN_KEY]);

    log_acesso($userId, $ip, true, 'trial_ativado');

    echo json_encode([
        'success'    => true,
        'redirect'   => 'index.php',
        'expires_at' => $trialExpira,
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
}
exit;
