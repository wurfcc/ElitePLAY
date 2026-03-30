<?php
// ============================================================
//  security.php — Funções utilitárias de segurança
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (!defined('PRODUCAO')) {
    define('PRODUCAO', false);
}
if (!defined('SESSION_NAME')) {
    define('SESSION_NAME', 'eliteplay_sess');
}
if (!defined('AUTH_COOKIE_NAME')) {
    define('AUTH_COOKIE_NAME', 'eliteplay_auth');
}
if (!defined('SESSION_TTL')) {
    define('SESSION_TTL', 60 * 60 * 8);
}
if (!defined('CSRF_TOKEN_KEY')) {
    define('CSRF_TOKEN_KEY', 'eliteplay_csrf');
}
if (!defined('RATE_MAX_TENTATIVAS')) {
    define('RATE_MAX_TENTATIVAS', 5);
}
if (!defined('RATE_JANELA_MINUTOS')) {
    define('RATE_JANELA_MINUTOS', 15);
}
if (!defined('RATE_BLOQUEIO_MINUTOS')) {
    define('RATE_BLOQUEIO_MINUTOS', 30);
}

// --- Configuração de sessão segura (deve ser chamada ANTES de session_start) ---
function configurar_sessao(): void {
    $params = [
        'lifetime' => 0,               // Morre ao fechar o navegador (TTL controlado no BD)
        'path'     => '/',
        'domain'   => '',
        'secure'   => PRODUCAO,        // Só HTTPS em produção
        'httponly' => true,            // Inacessível via JavaScript
        'samesite' => 'Strict',        // Evita CSRF via cookies
    ];

    session_set_cookie_params($params);
    session_name(SESSION_NAME);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function auth_cookie_domain(): string {
    $host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
    $host = preg_replace('/:\\d+$/', '', $host);

    if ($host === '' || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
        return '';
    }

    if (str_starts_with($host, 'www.')) {
        $host = substr($host, 4);
    }

    return '.' . $host;
}

function auth_cookie_options(int $expiresAt): array {
    return [
        'expires' => $expiresAt,
        'path' => '/',
        'domain' => PRODUCAO ? auth_cookie_domain() : '',
        'secure' => PRODUCAO,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

// --- Gera (ou retorna) o CSRF token para o formulário ---
function csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

// --- Valida o CSRF token recebido ---
function validar_csrf(string $token_recebido): bool {
    $esperado = $_SESSION[CSRF_TOKEN_KEY] ?? '';
    // hash_equals() evita timing attacks
    return !empty($esperado) && hash_equals($esperado, $token_recebido);
}

// --- Retorna o IP real do visitante ---
function ip_cliente(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

// --- Verifica se IP ou email_hash estão bloqueados por rate-limit ---
// Retorna false se ok, ou o número de segundos bloqueado
function verificar_rate_limit(string $ip, string $email): int|false {
    $pdo = db();
    $ip_hash    = hash('sha256', $ip);
    $email_hash = hash('sha256', strtolower(trim($email)));

    $stmt = $pdo->prepare('
        SELECT identificador, tipo, tentativas, bloqueado_ate
        FROM tentativas_login
        WHERE (identificador = ? AND tipo = "ip")
           OR (identificador = ? AND tipo = "email")
    ');
    $stmt->execute([$ip_hash, $email_hash]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        if ($row['bloqueado_ate'] !== null) {
            $restante = strtotime($row['bloqueado_ate']) - time();
            if ($restante > 0) {
                return (int)$restante;  // ainda bloqueado
            }
        }
    }

    return false;
}

// --- Registra tentativa de login (incrementa contador e bloqueia se necessário) ---
function registrar_tentativa(string $ip, string $email): void {
    $pdo = db();
    $ip_hash    = hash('sha256', $ip);
    $email_hash = hash('sha256', strtolower(trim($email)));
    $janela     = date('Y-m-d H:i:s', time() - (RATE_JANELA_MINUTOS * 60));

    foreach ([[$ip_hash, 'ip'], [$email_hash, 'email']] as [$hash, $tipo]) {
        // Upsert: incrementa se existir e for recente, senão reseta
        $pdo->prepare('
            INSERT INTO tentativas_login (identificador, tipo, tentativas, bloqueado_ate)
            VALUES (?, ?, 1, NULL)
            ON DUPLICATE KEY UPDATE
                tentativas   = IF(ultima_em < ?, 1, tentativas + 1),
                bloqueado_ate = IF(
                    IF(ultima_em < ?, 1, tentativas + 1) >= ?,
                    DATE_ADD(NOW(), INTERVAL ? MINUTE),
                    NULL
                )
        ')->execute([
            $hash, $tipo,
            $janela,           // para o IF de reset
            $janela,           // para o IF de bloqueio
            RATE_MAX_TENTATIVAS,
            RATE_BLOQUEIO_MINUTOS,
        ]);
    }
}

// --- Zera tentativas após login bem-sucedido ---
function resetar_tentativas(string $ip, string $email): void {
    $pdo = db();
    $ip_hash    = hash('sha256', $ip);
    $email_hash = hash('sha256', strtolower(trim($email)));

    $pdo->prepare('
        DELETE FROM tentativas_login
        WHERE identificador IN (?, ?)
    ')->execute([$ip_hash, $email_hash]);
}

// --- Cria sessão segura no banco após login bem-sucedido ---
function criar_sessao(int $usuario_id, string $ip, ?string $user_expires_at = null): string {
    $pdo = db();

    // Gera token de 64 bytes aleatórios (128 hex chars)
    $token      = bin2hex(random_bytes(64));
    $token_hash = hash('sha256', $token);
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512);

    $pdo->prepare('
        INSERT INTO sessoes (usuario_id, token_hash, ip, user_agent, expires_at, user_expires_at)
        VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), ?)
    ')->execute([$usuario_id, $token_hash, $ip, $user_agent, SESSION_TTL, $user_expires_at]);

    return $token;  // O token puro vai para o cookie
}

// --- Valida o cookie de sessão e retorna os dados do usuário ou null ---
function validar_sessao_cookie(): ?array {
    $token_raw = $_COOKIE[AUTH_COOKIE_NAME] ?? '';
    if (empty($token_raw)) {
        return null;
    }

    $token_hash = hash('sha256', $token_raw);
    $pdo = db();

    $stmt = $pdo->prepare('
        SELECT s.id AS sessao_id, s.usuario_id, s.ip, s.expires_at, s.user_expires_at,
               u.email, u.ativo, u.is_admin, u.dias_acesso, u.acesso_expira_em
        FROM sessoes s
        JOIN usuarios u ON u.id = s.usuario_id
        WHERE s.token_hash = ?
          AND s.revogada   = 0
          AND s.expires_at > NOW()
          AND u.ativo      = 1
        LIMIT 1
    ');
    $stmt->execute([$token_hash]);
    $sessao = $stmt->fetch();

    if (!$sessao) {
        return null;
    }

    // Admin nunca bloqueia por validade de plano
    if ((int)$sessao['is_admin'] !== 1) {
        $acessoExpiraEm = $sessao['acesso_expira_em'] ?? null;

        if (!empty($acessoExpiraEm)) {
            $agora = new DateTime();
            $expira = new DateTime($acessoExpiraEm);

            if ($expira <= $agora) {
                try {
                    db()->prepare('UPDATE usuarios SET dias_acesso = 0 WHERE id = ?')->execute([(int)$sessao['usuario_id']]);
                    db()->prepare('UPDATE sessoes SET revogada = 1 WHERE usuario_id = ? AND revogada = 0')->execute([(int)$sessao['usuario_id']]);
                } catch (\Throwable $e) {
                    // Falha silenciosa: não impede o bloqueio
                }

                return ['expired' => true, 'usuario_id' => $sessao['usuario_id'], 'email' => $sessao['email'], 'dias_acesso' => 0, 'is_admin' => 0];
            }

            // Campo informativo para o frontend/admin: dias restantes
            $restanteSeg = strtotime($acessoExpiraEm) - time();
            $sessao['dias_acesso'] = max(1, (int)ceil($restanteSeg / 86400));
        } else {
            // Compatibilidade: se não há data e dias <= 0, bloqueia.
            if (isset($sessao['dias_acesso']) && $sessao['dias_acesso'] !== null && (int)$sessao['dias_acesso'] <= 0) {
                return ['expired' => true, 'usuario_id' => $sessao['usuario_id'], 'email' => $sessao['email'], 'dias_acesso' => 0, 'is_admin' => 0];
            }

            // Sem data e sem dias definidos => acesso ilimitado
            if (!isset($sessao['dias_acesso']) || $sessao['dias_acesso'] === null) {
                $sessao['dias_acesso'] = null;
            }
        }
    }

    return $sessao;
}

// --- Registra log de acesso ---
function log_acesso(?int $usuario_id, string $ip, bool $sucesso, string $motivo): void {
    try {
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512);
        db()->prepare('
            INSERT INTO log_acessos (usuario_id, ip, user_agent, sucesso, motivo)
            VALUES (?, ?, ?, ?, ?)
        ')->execute([$usuario_id, $ip, $user_agent, $sucesso ? 1 : 0, $motivo]);
    } catch (\Throwable $e) {
        // Falha silenciosa no log não deve derrubar o sistema
    }
}
