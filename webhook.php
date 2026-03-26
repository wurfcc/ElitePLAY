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
if (!isset($payload['event']) || $payload['event'] !== 'payment.approved') {
    echo json_encode(['status' => 'ignored']);
    exit;
}

// Extrai dados do payload
$email = $payload['data']['customer']['email'] ?? null;
$orderId = $payload['data']['order']['id'] ?? null;
$total = intval($payload['data']['order']['total'] ?? 0);

// Validações básicas
if (!$email || !$orderId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing email or orderId']);
    exit;
}

// Identifica o plano pelo valor (em centavos)
// Mensal: R$10 = 1000 centavos
// Semestral: R$47 = 4700 centavos
$planos = [
    1000 => ['nome' => 'Mensal', 'dias' => 30],
    4700 => ['nome' => 'Semestral', 'dias' => 180],
];

if (!isset($planos[$total])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Unknown plan value']);
    exit;
}

$plano = $planos[$total];
$dias = $plano['dias'];
$nomePlano = $plano['nome'];

try {
    $pdo = db();

    // Verifica se usuário já existe
    $stmt = $pdo->prepare('SELECT id, dias_acesso FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // Usuário existe - soma os dias
        $novosDias = $usuario['dias_acesso'] + $dias;
        $stmt = $pdo->prepare('UPDATE usuarios SET dias_acesso = ? WHERE id = ?');
        $stmt->execute([$novosDias, $usuario['id']]);

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'action' => 'renewed',
            'email' => $email,
            'plan' => $nomePlano,
            'days_added' => $dias,
            'total_days' => $novosDias
        ]);
    } else {
        // Novo usuário - cria com dias de acesso
        $stmt = $pdo->prepare('INSERT INTO usuarios (email, ativo, dias_acesso) VALUES (?, 1, ?)');
        $stmt->execute([$email, $dias]);

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'action' => 'created',
            'email' => $email,
            'plan' => $nomePlano,
            'days' => $dias
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}

exit;
