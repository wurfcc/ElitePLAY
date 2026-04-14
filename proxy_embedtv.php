<?php
declare(strict_types=1);

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Metodo nao permitido'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$resource = $_GET['resource'] ?? 'jogos';

$allowed = [
    'jogos' => 'https://embedtv.cv/jogos2.php',
    'channels' => 'https://embedtv.cv/api/channels',
    'epgs' => 'https://embedtv.cv/api/epgs',
];

$url = $allowed[$resource] ?? null;
if ($url === null) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Recurso invalido'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$cacheTtl = 20 * 60;
$cacheKey = hash('sha256', $url);
$cacheFile = rtrim(sys_get_temp_dir(), '/\\') . '/eliteplay_embedtv_cache_' . $cacheKey . '.json';

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) <= $cacheTtl) {
    $cached = @file_get_contents($cacheFile);
    if ($cached !== false && $cached !== '') {
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
    CURLOPT_USERAGENT => 'ElitePLAY/1.0 (+embedtv-proxy)',
]);

$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode >= 400) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Falha ao consultar EmbedTV', 'status' => $httpCode], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Normaliza links legados do domínio antigo
$response = str_replace('embedtv.best', 'embedtv.cv', $response);

@file_put_contents($cacheFile, $response, LOCK_EX);

header('Content-Type: application/json; charset=utf-8');
echo $response;
