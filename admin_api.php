<?php
// ============================================================
//  admin_api.php — API JSON exclusiva do painel admin
//  Actions: list_users | toggle_user | list_games | save_override | get_overrides
// ============================================================
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/home_banners_storage.php';

configurar_sessao();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');
header('X-Content-Type-Options: nosniff');

// --- Garante que é admin ---
$sessao = validar_sessao_cookie();
if (!$sessao) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado.']);
    exit;
}

// Verifica is_admin
$meAdmin = db()->prepare('SELECT is_admin FROM usuarios WHERE id = ? AND ativo = 1 LIMIT 1');
$meAdmin->execute([$sessao['usuario_id']]);
$me = $meAdmin->fetch();
if (!$me || !$me['is_admin']) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

// Define ação antes de validar CSRF
$action = $_GET['action'] ?? '';

// --- Verificação CSRF para ações POST ---
$csrf_safe_actions = ['get_overrides', 'online_count', 'list_users', 'active_sessions', 'get_home_banners'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($action, $csrf_safe_actions, true)) {
    $token_recebido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!validar_csrf($token_recebido)) {
        http_response_code(403);
        echo json_encode(['error' => 'Token de segurança inválido.']);
        exit;
    }
}

// Adiciona update_dias_acesso e save_override à lista de ações que precisam de CSRF
// (Já coberto acima, pois não está em csrf_safe_actions)

switch ($action) {

    // ---- Lista todos os usuários ----
    case 'list_users':
        $stmt = db()->query('
            SELECT id, email, ativo, is_admin,
                   CASE
                       WHEN is_admin = 1 THEN NULL
                       WHEN acesso_expira_em IS NULL THEN NULL
                       ELSE GREATEST(0, CEIL(TIMESTAMPDIFF(SECOND, NOW(), acesso_expira_em) / 86400))
                   END AS dias_acesso,
                   acesso_expira_em,
                   created_at,
                   (SELECT MAX(created_at) FROM sessoes WHERE usuario_id = u.id AND revogada = 0 AND expires_at > NOW()) AS ultimo_acesso,
                   (SELECT COUNT(*) FROM sessoes WHERE usuario_id = u.id AND revogada = 0 AND expires_at > NOW() AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)) AS is_online
            FROM usuarios u
            ORDER BY is_admin DESC, created_at ASC
        ');
        echo json_encode($stmt->fetchAll());
        break;

    // ---- Conta usuários online ----
    case 'online_count':
        $stmt = db()->query('
            SELECT COUNT(DISTINCT usuario_id) AS online_count
            FROM sessoes
            WHERE revogada = 0 AND expires_at > NOW() AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ');
        echo json_encode($stmt->fetch());
        break;

    // ---- Ativa/Desativa usuário ----
    case 'toggle_user':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Método inválido.']); exit; }
        $body = json_decode(file_get_contents('php://input'), true);
        $uid  = (int)($body['id'] ?? 0);

        // Não permite que o admin desative a si mesmo
        if ($uid === (int)$sessao['usuario_id']) {
            echo json_encode(['error' => 'Você não pode desativar sua própria conta.']);
            break;
        }

        $stmt = db()->prepare('UPDATE usuarios SET ativo = NOT ativo WHERE id = ?');
        $stmt->execute([$uid]);

        // Se desativou, revoga sessões ativas
        db()->prepare('UPDATE sessoes SET revogada = 1 WHERE usuario_id = ? AND revogada = 0')->execute([$uid]);

        $novo = db()->prepare('SELECT ativo FROM usuarios WHERE id = ?');
        $novo->execute([$uid]);
        echo json_encode(['ok' => true, 'ativo' => (bool)$novo->fetchColumn()]);
        break;

    // ---- Atualiza dias de acesso do usuário ----
    case 'update_dias_acesso':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Método inválido.']); exit; }
        $body = json_decode(file_get_contents('php://input'), true);
        $uid = (int)($body['id'] ?? 0);
        $dias = $body['dias'] ?? null;
        
        if ($uid <= 0) {
            echo json_encode(['error' => 'ID inválido.']);
            break;
        }
        
        // Normaliza: vazio/null = sem limite (vitalício), 0 = bloqueado, >0 = dias de acesso
        if ($dias === '' || $dias === false) {
            $dias = null;
        }
        if (is_string($dias) && is_numeric($dias)) {
            $dias = (int)$dias;
        }

        if ($dias !== null && (!is_int($dias) || $dias < 0)) {
            echo json_encode(['error' => 'Dias inválido.']);
            break;
        }
        
        // Atualiza validade no usuário e revoga sessões para revalidação
        if ($dias === null) {
            // Null = ilimitado
            db()->prepare('UPDATE usuarios SET dias_acesso = NULL, acesso_expira_em = NULL WHERE id = ?')->execute([$uid]);
            db()->prepare('UPDATE sessoes SET revogada = 1 WHERE usuario_id = ? AND revogada = 0')->execute([$uid]);
            db()->prepare('UPDATE sessoes SET user_expires_at = NULL WHERE usuario_id = ?')->execute([$uid]);
        } elseif ($dias >= 0) {
            $acessoExpiraEm = null;
            if ($dias > 0) {
                $acessoExpiraEm = date('Y-m-d H:i:s', time() + ($dias * 24 * 60 * 60));
            } elseif ($dias === 0) {
                $acessoExpiraEm = date('Y-m-d H:i:s');
            }

            db()->prepare('UPDATE usuarios SET dias_acesso = ?, acesso_expira_em = ? WHERE id = ?')->execute([$dias, $acessoExpiraEm, $uid]);

            // Revoga sessões sempre que dias for alterado (reseta validade imediatamente)
            db()->prepare('UPDATE sessoes SET revogada = 1 WHERE usuario_id = ? AND revogada = 0')->execute([$uid]);

            // Se o admin está redefinindo dias, limpa âncoras antigas de expiração
            db()->prepare('UPDATE sessoes SET user_expires_at = NULL WHERE usuario_id = ?')->execute([$uid]);
        }
        
        // Busca o valor atualizado (dias restantes)
        $stmt = db()->prepare('
            SELECT
                CASE
                    WHEN is_admin = 1 THEN NULL
                    WHEN acesso_expira_em IS NULL THEN NULL
                    ELSE GREATEST(0, CEIL(TIMESTAMPDIFF(SECOND, NOW(), acesso_expira_em) / 86400))
                END AS dias_acesso
            FROM usuarios
            WHERE id = ?
        ');
        $stmt->execute([$uid]);
        $novoDias = $stmt->fetchColumn();
        
        echo json_encode(['ok' => true, 'dias_acesso' => $novoDias]);
        break;

    // ---- Adiciona novo usuário ----
    case 'add_user':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Método inválido.']); exit; }
        $body  = json_decode(file_get_contents('php://input'), true);
        $email = trim(strtolower($body['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'E-mail inválido.']);
            break;
        }
        try {
            $stmt = db()->prepare('INSERT INTO usuarios (email, ativo, is_admin) VALUES (?, 1, 0)');
            $stmt->execute([$email]);
            echo json_encode(['ok' => true, 'id' => db()->lastInsertId(), 'email' => $email]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                echo json_encode(['error' => 'E-mail já cadastrado.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao cadastrar.']);
            }
        }
        break;

    // ---- Remove usuário ----
    case 'delete_user':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Método inválido.']); exit; }
        $body = json_decode(file_get_contents('php://input'), true);
        $uid  = (int)($body['id'] ?? 0);
        if ($uid === (int)$sessao['usuario_id']) {
            echo json_encode(['error' => 'Você não pode remover sua própria conta.']); break;
        }
        // Revoga sessões e deleta
        db()->prepare('UPDATE sessoes SET revogada = 1 WHERE usuario_id = ?')->execute([$uid]);
        db()->prepare('DELETE FROM usuarios WHERE id = ? AND is_admin = 0')->execute([$uid]);
        echo json_encode(['ok' => true]);
        break;

    // ---- Salva override de canais para um jogo ----
    case 'save_override':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Método inválido.']); exit; }
        $body      = json_decode(file_get_contents('php://input'), true);
        $jogo_id   = trim($body['jogo_id'] ?? '');
        $titulo    = substr(trim($body['titulo'] ?? ''), 0, 300);
        $data_jogo = $body['data_jogo'] ?? date('Y-m-d');
        $canais    = $body['canais'] ?? [];

        if ($jogo_id === '' || !is_array($canais)) {
            echo json_encode(['error' => 'Dados inválidos.']); break;
        }

        // Valida data
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_jogo)) {
            $data_jogo = date('Y-m-d');
        }

        $canais_limpos = [];
        foreach ($canais as $c) {
            $name = substr(trim((string)($c['name'] ?? '')), 0, 100);
            if ($name === '') continue;

            $item = ['name' => $name];
            if (!empty($c['remove_channel'])) $item['remove_channel'] = 1;

            // Support both 'qualities' (full control - new) and 'remove_qualities' (backward compat)
            if (isset($c['qualities']) && is_array($c['qualities']) && count($c['qualities']) > 0) {
                // New full control: store exact qualities to show
                $qualities = [];
                foreach ($c['qualities'] as $q) {
                    $q = strtoupper(substr(trim((string)$q), 0, 30));
                    if ($q !== '') $qualities[] = $q;
                }
                $qualities = array_values(array_unique($qualities));
                if (!empty($qualities)) $item['qualities'] = $qualities;
            } elseif (isset($c['remove_qualities']) && is_array($c['remove_qualities'])) {
                // Old method: store qualities to remove
                $remove_qualities = [];
                foreach ($c['remove_qualities'] as $q) {
                    $q = strtoupper(substr(trim((string)$q), 0, 30));
                    if ($q !== '') $remove_qualities[] = $q;
                }
                $remove_qualities = array_values(array_unique($remove_qualities));
                if (!empty($remove_qualities)) $item['remove_qualities'] = $remove_qualities;
            }

            $canais_limpos[] = $item;
        }

        if (empty($canais_limpos)) {
            db()->prepare('DELETE FROM jogos_canais_override WHERE jogo_id = ? AND jogo_data = ?')->execute([$jogo_id, $data_jogo]);
            echo json_encode(['ok' => true]);
            break;
        }

        $canais_json = json_encode($canais_limpos, JSON_UNESCAPED_UNICODE);

        db()->prepare('
            INSERT INTO jogos_canais_override (jogo_id, jogo_titulo, jogo_data, canais, editado_por)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                jogo_titulo = VALUES(jogo_titulo),
                canais      = VALUES(canais),
                editado_por = VALUES(editado_por)
        ')->execute([$jogo_id, $titulo, $data_jogo, $canais_json, $sessao['usuario_id']]);

        echo json_encode(['ok' => true]);
        break;

    // ---- Busca overrides do dia (usado pelo frontend público) ----
    case 'get_overrides':
        $data = $_GET['data'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) $data = date('Y-m-d');

        $stmt = db()->prepare('
            SELECT jogo_id, canais FROM jogos_canais_override WHERE jogo_data = ?
        ');
        $stmt->execute([$data]);
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['jogo_id']] = json_decode($row['canais'], true);
        }
        echo json_encode($result);
        break;

    // ---- Sessões Ativas ----
    case 'active_sessions':
        $stmt = db()->query('SELECT COUNT(*) FROM sessoes WHERE revogada = 0 AND expires_at > NOW()');
        echo json_encode(['count' => (int)$stmt->fetchColumn()]);
        break;

    // ---- Banners da home (carrossel) ----
    case 'get_home_banners':
        echo json_encode(load_home_banners(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'save_home_banners':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método inválido.']);
            exit;
        }

        $payloadRaw = (string)($_POST['banners_payload'] ?? '');
        $payload = json_decode($payloadRaw, true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['error' => 'Payload de banners inválido.']);
            break;
        }

        $currentBanners = load_home_banners();
        $currentById = [];
        foreach ($currentBanners as $existing) {
            $existingId = home_sanitize_banner_id((string)($existing['id'] ?? ''));
            if ($existingId !== '') {
                $currentById[$existingId] = $existing;
            }
        }

        $nextBanners = [];
        $seen = [];

        try {
            foreach ($payload as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $bannerId = home_sanitize_banner_id((string)($item['id'] ?? ''));
                if ($bannerId === '' || isset($seen[$bannerId])) {
                    continue;
                }
                $seen[$bannerId] = true;

                $removeBanner = !empty($item['remove_banner']);
                $source = $currentById[$bannerId] ?? [
                    'id' => $bannerId,
                    'link' => '',
                    'desktop_image' => '',
                    'mobile_image' => '',
                ];

                if ($removeBanner) {
                    delete_home_banner_asset((string)($source['desktop_image'] ?? ''));
                    delete_home_banner_asset((string)($source['mobile_image'] ?? ''));
                    continue;
                }

                $current = [
                    'id' => $bannerId,
                    'link' => normalize_home_banner_link((string)($item['link'] ?? '')),
                    'desktop_image' => (string)($source['desktop_image'] ?? ''),
                    'mobile_image' => (string)($source['mobile_image'] ?? ''),
                ];

                if (!empty($item['remove_desktop'])) {
                    delete_home_banner_asset((string)($current['desktop_image'] ?? ''));
                    $current['desktop_image'] = '';
                }

                if (!empty($item['remove_mobile'])) {
                    delete_home_banner_asset((string)($current['mobile_image'] ?? ''));
                    $current['mobile_image'] = '';
                }

                $desktopFileKey = 'desktop_image_' . $bannerId;
                if (isset($_FILES[$desktopFileKey]) && (int)($_FILES[$desktopFileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $oldDesktop = (string)($current['desktop_image'] ?? '');
                    $current['desktop_image'] = store_home_banner_upload($_FILES[$desktopFileKey], $bannerId, 'desktop');
                    delete_home_banner_asset($oldDesktop);
                }

                $mobileFileKey = 'mobile_image_' . $bannerId;
                if (isset($_FILES[$mobileFileKey]) && (int)($_FILES[$mobileFileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $oldMobile = (string)($current['mobile_image'] ?? '');
                    $current['mobile_image'] = store_home_banner_upload($_FILES[$mobileFileKey], $bannerId, 'mobile');
                    delete_home_banner_asset($oldMobile);
                }

                $nextBanners[] = $current;
            }

            foreach ($currentById as $existingId => $existingItem) {
                if (!isset($seen[$existingId])) {
                    delete_home_banner_asset((string)($existingItem['desktop_image'] ?? ''));
                    delete_home_banner_asset((string)($existingItem['mobile_image'] ?? ''));
                }
            }
        } catch (RuntimeException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            break;
        }

        if (!save_home_banners($nextBanners)) {
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível salvar os banners.']);
            break;
        }

        echo json_encode([
            'ok' => true,
            'banners' => load_home_banners(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Ação desconhecida.']);
}
exit;
