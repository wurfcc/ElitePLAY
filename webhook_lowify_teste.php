<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Metodo nao permitido. Use POST.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false) {
    $rawBody = '';
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'Payload invalido. Envie JSON.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$event = (string)($payload['event'] ?? '');
$saleId = (string)($payload['sale_id'] ?? '');
$productId = (string)($payload['product']['id'] ?? '');
$productName = (string)($payload['product']['name'] ?? '');
$customerName = (string)($payload['customer']['name'] ?? '');
$customerEmail = (string)($payload['customer']['email'] ?? '');
$customerPhone = (string)($payload['customer']['phone'] ?? '');
$isTest = !empty($payload['is_test']);

$required = [
    'event' => $event,
    'sale_id' => $saleId,
    'product.id' => $productId,
    'customer.email' => $customerEmail,
];

$missing = [];
foreach ($required as $field => $value) {
    if (trim($value) === '') {
        $missing[] = $field;
    }
}

$logData = [
    'received_at' => date('c'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'event' => $event,
    'sale_id' => $saleId,
    'product_id' => $productId,
    'product_name' => $productName,
    'customer_name' => $customerName,
    'customer_email' => $customerEmail,
    'customer_phone' => $customerPhone,
    'is_test' => $isTest,
    'tracking' => $payload['tracking'] ?? null,
    'raw' => $payload,
];

@file_put_contents(
    __DIR__ . '/webhook_lowify_teste.log',
    json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

if (!empty($missing)) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'Campos obrigatorios ausentes.',
        'missing' => $missing,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($event !== 'sale.paid') {
    echo json_encode([
        'ok' => true,
        'status' => 'ignored',
        'message' => 'Evento recebido, mas ignorado (esperado: sale.paid).',
        'event' => $event,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'ok' => true,
    'status' => 'received',
    'message' => 'Payload Lowify de teste recebido com sucesso.',
    'data' => [
        'sale_id' => $saleId,
        'customer_email' => $customerEmail,
        'product_id' => $productId,
        'is_test' => $isTest,
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
