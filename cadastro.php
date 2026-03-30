<?php
// ============================================================
//  cadastro.php — Endpoint de registro de novo usuário (POST JSON)
//  Recebe: { "nome": "...", "email": "...", "whatsapp": "...", "csrf": "..." }
//  Retorna: { "success": bool, "message": "...", "redirect": "..." }
// ============================================================
require_once __DIR__ . '/security.php';

configurar_sessao();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
    exit;
}

$nome     = trim($input['nome']     ?? '');
$email    = trim(strtolower($input['email']    ?? ''));
$whatsapp = trim($input['whatsapp'] ?? '');
$csrf     = trim($input['csrf']     ?? '');
$ip       = ip_cliente();

// --- CSRF ---
$origin     = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
$host       = trim((string)($_SERVER['HTTP_HOST']   ?? ''));
$hostSemPorta = preg_replace('/:\d+$/', '', $host);
$originHost = $origin !== '' ? (string)(parse_url($origin, PHP_URL_HOST) ?? '') : '';
$isSameOrigin = ($origin === '') || ($originHost !== '' && hash_equals(strtolower($hostSemPorta), strtolower($originHost)));

if (!validar_csrf($csrf) && !$isSameOrigin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido. Recarregue a página.']);
    exit;
}

// --- Validações ---
if (empty($nome) || strlen($nome) > 100) {
    echo json_encode(['success' => false, 'message' => 'Informe um nome válido (máx. 100 caracteres).']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    echo json_encode(['success' => false, 'message' => 'Informe um e-mail válido.']);
    exit;
}

// Sanitiza WhatsApp (aceita qualquer padrão, mantém só dígitos + formatação)
$whatsapp = preg_replace('/[^0-9()\-+ ]/', '', $whatsapp);
$whatsapp = substr($whatsapp, 0, 20);

if (empty($whatsapp)) {
    echo json_encode(['success' => false, 'message' => 'Informe um número de WhatsApp.']);
    exit;
}

// --- Rate limit ---
$bloqueado = verificar_rate_limit($ip, $email);
if ($bloqueado !== false) {
    $minutos = ceil($bloqueado / 60);
    echo json_encode(['success' => false, 'message' => "Muitas tentativas. Aguarde {$minutos} minuto(s)."]);
    exit;
}

usleep(random_int(60000, 150000));

try {
    $pdo = db();

    // Verifica duplicata antes de inserir
    $check = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'E-mail já cadastrado. Tente fazer login.']);
        exit;
    }

    // Cria usuário com 0 dias de acesso → webhook ativa após pagamento
    $stmt = $pdo->prepare('
        INSERT INTO usuarios (email, nome, whatsapp, ativo, dias_acesso)
        VALUES (?, ?, ?, 1, 0)
    ');
    $stmt->execute([$email, $nome, $whatsapp]);
    $userId = (int)$pdo->lastInsertId();

    // Auto-login: cria sessão e seta cookie
    session_regenerate_id(true);

    $token = criar_sessao($userId, $ip, null);

    $cookieExpires  = time() + SESSION_TTL;
    $cookieOptions  = function_exists('auth_cookie_options')
        ? auth_cookie_options($cookieExpires)
        : ['expires' => $cookieExpires, 'path' => '/', 'domain' => '', 'secure' => PRODUCAO, 'httponly' => true, 'samesite' => 'Lax'];
    setcookie(AUTH_COOKIE_NAME, $token, $cookieOptions);

    unset($_SESSION[CSRF_TOKEN_KEY]);

    log_acesso($userId, $ip, true, 'cadastro_novo');

    echo json_encode([
        'success'  => true,
        'message'  => 'Conta criada! Escolha seu plano para começar.',
        'redirect' => 'pagamento.php',
    ]);

} catch (\PDOException $e) {
    if ($e->getCode() === '23000') {
        echo json_encode(['success' => false, 'message' => 'E-mail já cadastrado. Tente fazer login.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
    }
}
exit;
