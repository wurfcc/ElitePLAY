<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/smarttv_pair_storage.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$action = (string)($_GET['action'] ?? '');

function smarttv_json(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function smarttv_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scriptDir = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    if ($scriptDir === '' || $scriptDir === '.') {
        return $scheme . '://' . $host;
    }
    return $scheme . '://' . $host . $scriptDir;
}

if ($action === 'create' && $method === 'POST') {
    $pairs = smarttv_cleanup_pairs(smarttv_load_pairs());

    $pairId = smarttv_generate_pair_id();
    $authToken = smarttv_generate_auth_token();
    $pairCode = smarttv_generate_pair_code();
    $now = smarttv_now();

    $pairs[] = [
        'pair_id' => $pairId,
        'pair_code' => $pairCode,
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

    $authorizeUrl = smarttv_base_url() . '/smarttv_auth.php?pair=' . urlencode($pairId);
    $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' . rawurlencode($authorizeUrl);

    smarttv_json([
        'ok' => true,
        'pair_id' => $pairId,
        'pair_code' => $pairCode,
        'authorize_url' => $authorizeUrl,
        'qr_image' => $qrImage,
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

    smarttv_json([
        'ok' => true,
        'authorized' => false,
        'expired' => false,
        'pair_code' => (string)($pair['pair_code'] ?? ''),
    ]);
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

if ($action === 'authorize' && $method === 'POST') {
    configurar_sessao();
    $viewer = validar_sessao_cookie();
    if ($viewer === null || (!empty($viewer['expired']) && $viewer['expired'] === true)) {
        smarttv_json(['error' => 'Conta não autenticada ou sem acesso ativo.'], 401);
    }

    $input = json_decode((string)file_get_contents('php://input'), true);
    $pairId = trim((string)($input['pair_id'] ?? ''));
    if ($pairId === '') {
        smarttv_json(['error' => 'pair_id obrigatório.'], 400);
    }

    $pairs = smarttv_cleanup_pairs(smarttv_load_pairs());
    $idx = smarttv_find_pair_index($pairs, $pairId);
    if ($idx < 0) {
        smarttv_json(['error' => 'Pareamento inválido ou expirado.'], 404);
    }

    if (($pairs[$idx]['status'] ?? '') === 'authorized') {
        smarttv_json(['ok' => true, 'message' => 'Esta Smart TV já foi autorizada.']);
    }

    $now = smarttv_now();
    $pairs[$idx]['status'] = 'authorized';
    $pairs[$idx]['user_id'] = (int)($viewer['usuario_id'] ?? 0);
    $pairs[$idx]['authorized_at'] = $now;
    $pairs[$idx]['expires_at'] = $now + SMARTTV_AUTH_TTL;

    if (!smarttv_save_pairs($pairs)) {
        smarttv_json(['error' => 'Não foi possível autorizar a Smart TV.'], 500);
    }

    smarttv_json(['ok' => true, 'message' => 'Smart TV autorizada com sucesso.']);
}

smarttv_json(['error' => 'Ação inválida.'], 400);
