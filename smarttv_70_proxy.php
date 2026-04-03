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

$url = 'https://embed.70noticias.com.br/?api=1&t=live&c=all';
$cacheFile = rtrim(sys_get_temp_dir(), '/\\') . '/eliteplay_smarttv_70_cache.json';
$ttl = 20;

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) <= $ttl) {
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
    CURLOPT_TIMEOUT => 18,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT => 'ElitePLAY/1.0 (+smarttv-70-proxy)',
]);

$response = curl_exec($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $status >= 400 || $response === '') {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Falha ao consultar 70noticias', 'status' => $status], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

@file_put_contents($cacheFile, $response, LOCK_EX);

header('Content-Type: application/json; charset=utf-8');
echo $response;
