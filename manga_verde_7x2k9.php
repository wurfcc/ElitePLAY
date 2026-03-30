<?php
// ============================================================
//  webhook.php — Recebe notificações de pagamento do Lowify
// ============================================================

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Recebe o payload
$rawPayload = file_get_contents('php://input');
$payload = json_decode($rawPayload, true);

// Log do payload completo
$logFile = __DIR__ . '/webhook_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . ' | ' . $rawPayload . PHP_EOL, FILE_APPEND);

// Verifica se é evento de pagamento aprovado
if (!isset($payload['event']) || $payload['event'] !== 'sale.paid') {
    echo json_encode(['status' => 'ignored']);
    exit;
}

// Extrai dados do payload (formato Lowify)
$email = $payload['customer']['email'] ?? null;
$productId = $payload['product']['id'] ?? null;
$productName = $payload['product']['name'] ?? '';
$saleId = $payload['sale_id'] ?? null;
$nome = $payload['customer']['name'] ?? null;
$whatsapp = $payload['customer']['phone'] ?? null;

// Validações básicas
if (!$email || !$productId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing email or productId']);
    exit;
}

// Identifica o plano pelo product_id E pelo nome do produto
$planos = [
    '31315' => ['nome' => 'Pocket',    'dias' => 2],
    '30453' => ['nome' => 'Mensal',    'dias' => 30],
    '30456' => ['nome' => 'Semestral', 'dias' => 180],
];

$plano = null;
$nomeLower = strtolower($productName);

// Primeiro tenta pelo ID
if (isset($planos[$productId])) {
    $plano = $planos[$productId];
}
// Se não encontrou pelo ID, tenta pelo nome
elseif (stripos($nomeLower, 'semestral') !== false) {
    $plano = ['nome' => 'Semestral', 'dias' => 180];
} elseif (stripos($nomeLower, 'mensal') !== false) {
    $plano = ['nome' => 'Mensal', 'dias' => 30];
} elseif (stripos($nomeLower, 'pocket') !== false) {
    $plano = ['nome' => 'Pocket', 'dias' => 2];
}

if (!$plano) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Unknown product ID: ' . $productId . ' | Nome: ' . $productName]);
    exit;
}

$dias = $plano['dias'];
$nomePlano = $plano['nome'];

try {
    $pdo = db();

    // Verifica se usuário já existe
    $stmt = $pdo->prepare('SELECT id, dias_acesso, acesso_expira_em FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // Usuário existe - soma no acesso_expira_em (baseado na data atual ou validade futura)
        $agora = new DateTime();
        $base = $agora;
        if (!empty($usuario['acesso_expira_em'])) {
            $expAtual = new DateTime($usuario['acesso_expira_em']);
            if ($expAtual > $agora) {
                $base = $expAtual;
            }
        }

        $novaExpiracao = clone $base;
        $novaExpiracao->modify('+' . (int)$dias . ' days');

        $novosDias = (int)ceil(($novaExpiracao->getTimestamp() - time()) / 86400);
        if ($novosDias < 0) $novosDias = 0;

        $stmt = $pdo->prepare('UPDATE usuarios SET dias_acesso = ?, acesso_expira_em = ? WHERE id = ?');
        $stmt->execute([$novosDias, $novaExpiracao->format('Y-m-d H:i:s'), $usuario['id']]);

        // Revoga sessões para aplicar nova validade imediatamente
        $pdo->prepare('UPDATE sessoes SET revogada = 1 WHERE usuario_id = ? AND revogada = 0')->execute([$usuario['id']]);

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'action' => 'renewed',
            'email' => $email,
            'plan' => $nomePlano,
            'days_added' => $dias,
            'total_days' => $novosDias,
            'expires_at' => $novaExpiracao->format('Y-m-d H:i:s')
        ]);
    } else {
        // Novo usuário - cria com dias de acesso + nome + whatsapp
        $expiraEm = new DateTime();
        $expiraEm->modify('+' . (int)$dias . ' days');

        $stmt = $pdo->prepare('INSERT INTO usuarios (email, nome, whatsapp, ativo, dias_acesso, acesso_expira_em) VALUES (?, ?, ?, 1, ?, ?)');
        $stmt->execute([$email, $nome, $whatsapp, $dias, $expiraEm->format('Y-m-d H:i:s')]);

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'action' => 'created',
            'email' => $email,
            'nome' => $nome,
            'whatsapp' => $whatsapp,
            'plan' => $nomePlano,
            'days' => $dias,
            'expires_at' => $expiraEm->format('Y-m-d H:i:s')
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}

exit;
