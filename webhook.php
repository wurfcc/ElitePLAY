<?php
// ============================================================
//  webhook.php — Recebe notificações de pagamento do Lowify
// ============================================================

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Recebe o payload
$rawPayload = file_get_contents('php://input');
$payload = json_decode($rawPayload, true);

// Log para debug
$logFile = __DIR__ . '/webhook_log.txt';
$log = date('Y-m-d H:i:s') . ' | ' . $rawPayload . PHP_EOL;
file_put_contents($logFile, $log, FILE_APPEND);

// Resposta padrão de sucesso
http_response_code(200);
echo json_encode(['status' => 'received']);

exit;
