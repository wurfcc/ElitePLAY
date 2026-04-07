<?php
set_time_limit(0);

$source = isset($_GET['url']) ? trim((string)$_GET['url']) : '';
if ($source === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Parametro url ausente.';
    exit;
}

if (!preg_match('#^https?://#i', $source)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'URL invalida.';
    exit;
}

$parts = parse_url($source);
$host = strtolower((string)($parts['host'] ?? ''));
if ($host === '' || strpos($host, 'xc1.live') === false) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Host nao permitido.';
    exit;
}

$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';
$referer = 'http://' . $host . '/';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Range, Origin, X-Requested-With, Content-Type, Accept');
header('Accept-Ranges: bytes');

$rangeHeader = isset($_SERVER['HTTP_RANGE']) ? trim((string)$_SERVER['HTTP_RANGE']) : '';

if (function_exists('curl_init')) {
    $ch = curl_init($source);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 12);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    curl_setopt($ch, CURLOPT_BUFFERSIZE, 8192);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: */*',
        'Connection: keep-alive',
        'Referer: ' . $referer,
    ]);

    if ($rangeHeader !== '') {
        curl_setopt($ch, CURLOPT_RANGE, preg_replace('/^bytes=/i', '', $rangeHeader));
    }

    curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function ($curl, $headerLine) {
        $len = strlen($headerLine);
        $header = trim($headerLine);
        if ($header === '' || stripos($header, 'HTTP/') === 0) {
            return $len;
        }

        $allowed = [
            'content-type',
            'content-length',
            'content-range',
            'accept-ranges',
            'cache-control',
            'expires',
            'last-modified',
        ];

        $pos = strpos($header, ':');
        if ($pos !== false) {
            $name = strtolower(trim(substr($header, 0, $pos)));
            if (in_array($name, $allowed, true)) {
                header($header, true);
            }
        }

        return $len;
    });

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function ($curl, $chunk) {
        echo $chunk;
        flush();
        return strlen($chunk);
    });

    $ok = curl_exec($ch);
    if ($ok === false) {
        if (!headers_sent()) {
            http_response_code(502);
            header('Content-Type: text/plain; charset=utf-8');
        }
        echo 'Falha ao carregar stream.';
    }
    curl_close($ch);
    exit;
}

$headers =
    'User-Agent: ' . $userAgent . "\r\n" .
    'Accept: */*' . "\r\n" .
    'Connection: keep-alive' . "\r\n" .
    'Referer: ' . $referer . "\r\n";

if ($rangeHeader !== '') {
    $headers .= 'Range: ' . $rangeHeader . "\r\n";
}

$ctx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => $headers,
        'timeout' => 30,
        'ignore_errors' => true,
    ],
]);

$fp = @fopen($source, 'rb', false, $ctx);
if (!$fp) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Falha ao abrir stream.';
    exit;
}

header('Content-Type: video/mp2t');
while (!feof($fp)) {
    echo fread($fp, 8192);
    flush();
}
fclose($fp);
