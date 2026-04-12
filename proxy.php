<?php
// proxy.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/vnd.apple.mpegurl");

if (isset($_GET['url'])) {
    $url = $_GET['url'];
    $options = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n" .
                        "Referer: https://mr.cloudfronte.lat/\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $content = @file_get_contents($url, false, $context);
    
    if ($content) {
        $baseUrl = substr($url, 0, strrpos($url, '/') + 1);
        $lines = explode("\n", $content);
        foreach ($lines as &$line) {
            $line = trim($line);
            if (!empty($line) && strpos($line, '#') !== 0 && strpos($line, 'http') !== 0) {
                $line = $baseUrl . $line;
            }
        }
        echo implode("\n", $lines);
    } else {
        http_response_code(404);
        echo "#EXTM3U\n# Erro ao carregar o stream.";
    }
}
?>
