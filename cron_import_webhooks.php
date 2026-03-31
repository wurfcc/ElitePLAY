<?php
declare(strict_types=1);

require_once __DIR__ . '/webhook_processor.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function cron_out(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    cron_out(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$drainUrl = trim((string)($_GET['drain_url'] ?? getenv('CF_WEBHOOK_DRAIN_URL') ?? ''));
$drainKey = trim((string)($_GET['key'] ?? getenv('CF_WEBHOOK_DRAIN_KEY') ?? ''));
$limit = (int)($_GET['limit'] ?? 25);

if ($limit < 1) {
    $limit = 1;
}
if ($limit > 100) {
    $limit = 100;
}

if ($drainUrl === '' || $drainKey === '') {
    cron_out([
        'ok' => false,
        'error' => 'missing_drain_config',
        'help' => 'Use ?drain_url=...&key=... ou configure CF_WEBHOOK_DRAIN_URL/CF_WEBHOOK_DRAIN_KEY',
    ], 400);
}

$sep = strpos($drainUrl, '?') === false ? '?' : '&';
$url = $drainUrl . $sep . 'key=' . rawurlencode($drainKey) . '&limit=' . $limit;

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT => 'ElitePLAY/1.0 (+cron-webhook-importer)',
]);

$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode >= 400) {
    cron_out([
        'ok' => false,
        'error' => 'drain_request_failed',
        'status' => $httpCode,
        'detail' => $curlError ?: null,
        'drain_url' => $drainUrl,
    ], 502);
}

$decoded = json_decode($response, true);
if (!is_array($decoded) || empty($decoded['ok'])) {
    cron_out(['ok' => false, 'error' => 'invalid_drain_response', 'raw' => $response], 502);
}

$items = $decoded['items'] ?? [];
if (!is_array($items)) {
    $items = [];
}

$processed = 0;
$success = 0;
$ignored = 0;
$errors = 0;
$results = [];

foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }

    $eventId = (string)($item['id'] ?? '');
    $payload = $item['payload'] ?? null;
    if (!is_array($payload)) {
        $payload = [];
    }

    $processed++;
    $res = processar_webhook_lowify($payload);
    $status = (int)($res['http'] ?? 500);
    $body = $res['body'] ?? [];
    $action = (string)($body['action'] ?? $body['status'] ?? 'unknown');

    if ($status >= 200 && $status < 300) {
        if ($action === 'ignored') {
            $ignored++;
        } else {
            $success++;
        }
    } else {
        $errors++;
    }

    $results[] = [
        'id' => $eventId,
        'status' => $status,
        'action' => $action,
        'email' => $body['email'] ?? null,
        'sale_id' => $body['sale_id'] ?? null,
        'message' => $body['message'] ?? null,
    ];
}

$logLine = json_encode([
    'ts' => date('c'),
    'fetched' => count($items),
    'processed' => $processed,
    'success' => $success,
    'ignored' => $ignored,
    'errors' => $errors,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
@file_put_contents(__DIR__ . '/webhook_cron.log', $logLine, FILE_APPEND | LOCK_EX);

cron_out([
    'ok' => true,
    'fetched' => count($items),
    'processed' => $processed,
    'success' => $success,
    'ignored' => $ignored,
    'errors' => $errors,
    'results' => $results,
]);
