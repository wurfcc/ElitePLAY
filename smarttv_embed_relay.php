<?php
declare(strict_types=1);

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Metodo nao permitido';
    exit;
}

$rawUrl = trim((string)($_GET['url'] ?? ''));
if ($rawUrl === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'URL obrigatoria';
    exit;
}

$url = filter_var($rawUrl, FILTER_VALIDATE_URL);
if ($url === false) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'URL invalida';
    exit;
}

$parts = parse_url($url);
$host = strtolower((string)($parts['host'] ?? ''));
$allowedHosts = ['embed.70noticias.com.br'];
if (!in_array($host, $allowedHosts, true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Host nao permitido';
    exit;
}

$origin = 'https://embed.70noticias.com.br';
$referer = $origin . '/';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER => [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
        'Referer: ' . $referer,
        'Origin: ' . $origin,
    ],
]);

$html = curl_exec($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ctype = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if (!is_string($html) || $html === '' || $code >= 400) {
    header('Content-Type: text/html; charset=utf-8');
    $safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;background:#000;color:#fff;font-family:Arial,sans-serif;display:grid;place-items:center;min-height:100vh"><div><p>Falha no relay. Abrindo origem...</p><script>location.replace(' . json_encode($url) . ');</script><noscript><a href="' . $safe . '" style="color:#fff">Abrir player</a></noscript></div></body></html>';
    exit;
}

if (stripos($ctype, 'text/html') === false && stripos($html, '<html') === false) {
    header('Content-Type: ' . ($ctype !== '' ? $ctype : 'text/html; charset=utf-8'));
    echo $html;
    exit;
}

// Auto-avanca no fluxo da 70 (index -> player) no servidor,
// evitando depender de clique/script no iframe da TV.
$requestedPath = strtolower((string)($parts['path'] ?? ''));
if (strpos($requestedPath, 'index.php') !== false) {
    if (preg_match('/href="([^"]*player\.php[^"]*)"/i', $html, $m)) {
        $next = trim((string)$m[1]);
        if ($next !== '') {
            if (stripos($next, 'http://') !== 0 && stripos($next, 'https://') !== 0) {
                $next = $origin . '/' . ltrim($next, '/');
            }
            $selfPath = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
            if ($selfPath === '' || $selfPath === '.') {
                $selfPath = '';
            }
            $redirectTo = $selfPath . '/smarttv_embed_relay.php?url=' . rawurlencode($next);
            header('Location: ' . $redirectTo);
            exit;
        }
    }
}

$relayPath = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
if ($relayPath === '' || $relayPath === '.') {
    $relayPath = '';
}
$relayBase = $relayPath . '/smarttv_embed_relay.php?url=';
$relayBaseJs = json_encode($relayBase !== '' ? $relayBase : '/smarttv_embed_relay.php?url=');

$inject = <<<HTML
<base href="https://embed.70noticias.com.br/">
<script>
(function(){
  const relayBase = {$relayBaseJs};
  const originBase = 'https://embed.70noticias.com.br';

  function toAbs(u){
    try { return new URL(u, originBase + '/').toString(); } catch(e){ return u; }
  }
  function toRelay(u){
    return relayBase + encodeURIComponent(toAbs(u));
  }

  function patchLinks(){
    document.querySelectorAll('a[href]').forEach(a => {
      const h = a.getAttribute('href') || '';
      if (!h || h.startsWith('#')) return;
      if (h.includes('player.php') || h.includes('index.php') || h.startsWith('/')) {
        a.setAttribute('href', toRelay(h));
      }
    });
  }

  function autoPlayClick(){
    let done = false;
    const run = () => {
      if (done) return;
      patchLinks();

      const bp = document.querySelector('.bp, [onclick*="go()"], .pb');
      if (bp) {
        try { bp.click(); done = true; return; } catch(e) {}
      }

      const goFn = window.go;
      if (typeof goFn === 'function') {
        try { goFn(); done = true; return; } catch(e) {}
      }

      const playerLink = document.querySelector('a[href*="player.php"]');
      if (playerLink) {
        location.replace(playerLink.href);
        done = true;
      }
    };

    let i = 0;
    const t = setInterval(() => {
      i += 1;
      run();
      if (done || i > 40) clearInterval(t);
    }, 220);

    window.addEventListener('load', run);
    setTimeout(run, 80);
    setTimeout(run, 500);
    setTimeout(run, 1300);
  }

  autoPlayClick();
})();
</script>
HTML;

if (stripos($html, '</head>') !== false) {
    $html = preg_replace('/<\/head>/i', $inject . '</head>', $html, 1);
} else {
    $html = $inject . $html;
}

header('Content-Type: text/html; charset=utf-8');
echo $html;
