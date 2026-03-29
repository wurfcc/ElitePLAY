<?php
require_once __DIR__ . '/security.php';

configurar_sessao();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (PRODUCAO) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

function api_response(int $status, array $payload): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    api_response(405, ['error' => 'Metodo nao permitido']);
}

$usuario = validar_sessao_cookie();
if ($usuario === null || (!empty($usuario['expired']) && $usuario['expired'] === true)) {
    api_response(401, ['error' => 'Nao autenticado']);
}

$resource = $_GET['resource'] ?? '';
$source = $_GET['source'] ?? '';

$resourceMap = [
    'channels' => [
        'noticias70' => 'https://embed.70noticias.com.br/?api=1&t=live&c=all',
        'bugoumods' => 'https://embed.bugoumods.com/?api=1&t=live&c=all',
    ],
    'placar_hoje' => 'https://www.placardefutebol.com.br/jogos-de-hoje',
];

$url = null;
if ($resource === 'channels') {
    $url = $resourceMap['channels'][$source] ?? null;
} else {
    $url = $resourceMap[$resource] ?? null;
}

if ($url === null) {
    api_response(400, ['error' => 'Recurso invalido']);
}

$cacheTtl = [
    'channels' => 20,
    'placar_hoje' => 10,
];

$ttl = $cacheTtl[$resource] ?? 15;
$cacheKey = hash('sha256', $resource . '|' . $source);
$cacheFile = rtrim(sys_get_temp_dir(), '/\\') . '/eliteplay_api_cache_' . $cacheKey . '.tmp';

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) <= $ttl) {
    $cached = @file_get_contents($cacheFile);
    if ($cached !== false && $cached !== '') {
        if ($resource === 'placar_hoje') {
            header('Content-Type: text/html; charset=utf-8');
            echo $cached;
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo $cached;
        exit;
    }
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT => 'ElitePLAY/1.0 (+backend-proxy)',
]);

$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode >= 400) {
    api_response(502, [
        'error' => 'Falha ao consultar API externa',
        'status' => $httpCode,
        'detail' => $curlError ?: null,
    ]);
}

@file_put_contents($cacheFile, $response, LOCK_EX);

if ($resource === 'placar_hoje') {
    header('Content-Type: text/html; charset=utf-8');
    echo $response;
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo $response;
