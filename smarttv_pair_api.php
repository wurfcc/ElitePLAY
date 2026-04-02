<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$action = (string)($_GET['action'] ?? '');

const SMARTTV_PAIRS_FILE = __DIR__ . '/data/smarttv_pairs.json';
const SMARTTV_PENDING_TTL = 600;
const SMARTTV_AUTH_TTL = 2592000;

function smarttv_now(): int
{
    return time();
}

function smarttv_json(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function smarttv_load_pairs(): array
{
    if (!is_file(SMARTTV_PAIRS_FILE)) {
        return [];
    }

    $raw = @file_get_contents(SMARTTV_PAIRS_FILE);
    if ($raw === false || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}

function smarttv_save_pairs(array $pairs): bool
{
    $dir = dirname(SMARTTV_PAIRS_FILE);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }

    $json = json_encode(array_values($pairs), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }

    return @file_put_contents(SMARTTV_PAIRS_FILE, $json, LOCK_EX) !== false;
}

function smarttv_cleanup_pairs(array $pairs): array
{
    $now = smarttv_now();
    return array_values(array_filter($pairs, static function ($item) use ($now) {
        if (!is_array($item)) {
            return false;
        }
        $expiresAt = (int)($item['expires_at'] ?? 0);
        return $expiresAt > $now;
    }));
}

function smarttv_find_pair_index(array $pairs, string $pairId): int
{
    foreach ($pairs as $index => $pair) {
        if (($pair['pair_id'] ?? '') === $pairId) {
            return $index;
        }
    }
    return -1;
}

if ($action === 'create' && $method === 'POST') {
    $pairs = smarttv_cleanup_pairs(smarttv_load_pairs());

    $pairId = 'tv_' . bin2hex(random_bytes(5));
    $authToken = bin2hex(random_bytes(32));
    $now = smarttv_now();

    $pairs[] = [
        'pair_id' => $pairId,
        'auth_token' => $authToken,
        'status' => 'pending',
        'user_id' => null,
        'created_at' => $now,
        'authorized_at' => null,
        'expires_at' => $now + SMARTTV_PENDING_TTL,
    ];

    if (!smarttv_save_pairs($pairs)) {
        smarttv_json(['error' => 'Falha ao criar pareamento da Smart TV.'], 500);
    }

    smarttv_json([
        'ok' => true,
        'pair_id' => $pairId,
        'expires_in' => SMARTTV_PENDING_TTL,
    ]);
}

if ($action === 'status' && $method === 'GET') {
    $pairId = trim((string)($_GET['pair_id'] ?? ''));
    if ($pairId === '') {
        smarttv_json(['error' => 'pair_id obrigatório.'], 400);
    }

    $pairs = smarttv_cleanup_pairs(smarttv_load_pairs());
    $idx = smarttv_find_pair_index($pairs, $pairId);

    if ($idx < 0) {
        smarttv_json(['ok' => true, 'authorized' => false, 'expired' => true]);
    }

    $pair = $pairs[$idx];
    if (($pair['status'] ?? '') === 'authorized') {
        smarttv_json([
            'ok' => true,
            'authorized' => true,
            'auth_token' => (string)$pair['auth_token'],
            'expires_at' => (int)$pair['expires_at'],
        ]);
    }

    smarttv_json(['ok' => true, 'authorized' => false, 'expired' => false]);
}

if ($action === 'validate' && $method === 'GET') {
    $authToken = trim((string)($_GET['auth_token'] ?? ''));
    if ($authToken === '') {
        smarttv_json(['ok' => true, 'authorized' => false]);
    }

    $pairs = smarttv_cleanup_pairs(smarttv_load_pairs());
    foreach ($pairs as $pair) {
        if (($pair['auth_token'] ?? '') === $authToken && ($pair['status'] ?? '') === 'authorized') {
            smarttv_json([
                'ok' => true,
                'authorized' => true,
                'expires_at' => (int)($pair['expires_at'] ?? 0),
            ]);
        }
    }

    smarttv_json(['ok' => true, 'authorized' => false]);
}

if ($action === 'authorize_latest' && $method === 'POST') {
    configurar_sessao();
    $viewer = validar_sessao_cookie();
    if ($viewer === null) {
        smarttv_json(['error' => 'Não autenticado.'], 401);
    }

    $pairs = smarttv_cleanup_pairs(smarttv_load_pairs());
    $pendingIndex = -1;
    $latestCreated = 0;

    foreach ($pairs as $idx => $pair) {
        if (($pair['status'] ?? '') !== 'pending') {
            continue;
        }
        $createdAt = (int)($pair['created_at'] ?? 0);
        if ($createdAt >= $latestCreated) {
            $latestCreated = $createdAt;
            $pendingIndex = $idx;
        }
    }

    if ($pendingIndex < 0) {
        smarttv_json(['error' => 'Nenhuma Smart TV aguardando autorização agora. Abra /s na TV primeiro.'], 404);
    }

    $now = smarttv_now();
    $pairs[$pendingIndex]['status'] = 'authorized';
    $pairs[$pendingIndex]['user_id'] = (int)($viewer['usuario_id'] ?? 0);
    $pairs[$pendingIndex]['authorized_at'] = $now;
    $pairs[$pendingIndex]['expires_at'] = $now + SMARTTV_AUTH_TTL;

    if (!smarttv_save_pairs($pairs)) {
        smarttv_json(['error' => 'Não foi possível autorizar a Smart TV.'], 500);
    }

    smarttv_json([
        'ok' => true,
        'message' => 'Smart TV autorizada com sucesso.',
    ]);
}

smarttv_json(['error' => 'Ação inválida.'], 400);
