<?php
require_once __DIR__ . '/security.php';

configurar_sessao();

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo nao permitido'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$usuario = validar_sessao_cookie();
if ($usuario === null || (!empty($usuario['expired']) && $usuario['expired'] === true)) {
    http_response_code(401);
    echo json_encode(['error' => 'Nao autenticado'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

const SNAPSHOT_TTL = 15;
const CONNECT_TIMEOUT = 5;
const REQUEST_TIMEOUT = 12;

$cacheFile = rtrim(sys_get_temp_dir(), '/\\') . '/eliteplay_assist_games_snapshot.json';
$lockFile = rtrim(sys_get_temp_dir(), '/\\') . '/eliteplay_assist_games_snapshot.lock';

function json_out(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_cache_file(string $cacheFile): ?array {
    if (!is_file($cacheFile)) {
        return null;
    }

    $raw = @file_get_contents($cacheFile);
    if ($raw === false || $raw === '') {
        return null;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return null;
    }

    return $decoded;
}

function is_fresh_cache(?array $cache, int $ttl): bool {
    if (!is_array($cache) || !isset($cache['generated_at'])) {
        return false;
    }

    $generatedAt = (int)$cache['generated_at'];
    return $generatedAt > 0 && (time() - $generatedAt) <= $ttl;
}

function fetch_url_text(string $url): ?string {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => CONNECT_TIMEOUT,
        CURLOPT_TIMEOUT => REQUEST_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'ElitePLAY/1.0 (+assist-games-snapshot)',
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode >= 400) {
        return null;
    }

    return (string)$response;
}

function fetch_json_array(string $url): ?array {
    $raw = fetch_url_text($url);
    if ($raw === null) {
        return null;
    }

    $decoded = json_decode(str_replace('embedtv.best', 'embedtv.cv', $raw), true);
    return is_array($decoded) ? $decoded : null;
}

function slugify_name(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if ($ascii !== false) {
        $text = mb_strtolower($ascii, 'UTF-8');
    }

    $text = preg_replace('/\b(fc|cf|de munique|united|atletico)\b/u', '', $text) ?? $text;
    $text = preg_replace('/[^a-z0-9]/', '', $text) ?? $text;
    return trim($text);
}

function parse_placar_html(string $html): array {
    if ($html === '') {
        return [];
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $loaded = $doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();

    if (!$loaded) {
        return [];
    }

    $xpath = new DOMXPath($doc);
    $anchors = $xpath->query('//a[@href]');
    if ($anchors === false) {
        return [];
    }

    $result = [];

    foreach ($anchors as $a) {
        if (!($a instanceof DOMElement)) {
            continue;
        }

        $href = (string)$a->getAttribute('href');
        $hrefLower = mb_strtolower($href, 'UTF-8');
        $blockedByHref = str_contains($hrefLower, 'sub-20') || str_contains($hrefLower, 'sub20');
        if ($blockedByHref || !str_contains($href, '.html')) {
            continue;
        }

        $statusNode = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' status-name ')]", $a);
        $homeNode = $xpath->query(".//h5[contains(concat(' ', normalize-space(@class), ' '), ' text-right ')]", $a);
        $awayNode = $xpath->query(".//h5[contains(concat(' ', normalize-space(@class), ' '), ' text-left ')]", $a);
        $scoreNodes = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' match-score ')]//*[contains(concat(' ', normalize-space(@class), ' '), ' badge ')]", $a);
        $leagueNode = $xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' match-card-league-name ')]", $a);

        if ($statusNode === false || $homeNode === false || $awayNode === false || $scoreNodes === false || $leagueNode === false) {
            continue;
        }

        if ($statusNode->length === 0 || $homeNode->length === 0 || $awayNode->length === 0) {
            continue;
        }

        $homeTeam = trim($homeNode->item(0)?->textContent ?? '');
        $awayTeam = trim($awayNode->item(0)?->textContent ?? '');
        if ($homeTeam === '' || $awayTeam === '') {
            continue;
        }

        $homeScore = trim($scoreNodes->item(0)?->textContent ?? '0');
        $awayScore = trim($scoreNodes->item(1)?->textContent ?? '0');
        $statusText = trim($statusNode->item(0)?->textContent ?? '');
        $leagueName = trim($leagueNode->item(0)?->textContent ?? '');

        $result[] = [
            'homeTeam' => $homeTeam,
            'awayTeam' => $awayTeam,
            'homeScore' => $homeScore === '' ? '0' : $homeScore,
            'awayScore' => $awayScore === '' ? '0' : $awayScore,
            'statusText' => $statusText,
            'leagueName' => $leagueName,
        ];
    }

    return $result;
}

function merge_games_with_scores(array $apiGames, array $scrapedScores): array {
    $merged = [];
    $todayYmd = date('Y-m-d');

    foreach ($apiGames as $game) {
        if (!is_array($game)) {
            continue;
        }

        $homeName = (string)($game['data']['teams']['home']['name'] ?? '');
        $awayName = (string)($game['data']['teams']['away']['name'] ?? '');
        $homeSlug = slugify_name($homeName);
        $awaySlug = slugify_name($awayName);
        $startTs = (int)($game['data']['timer']['start'] ?? 0);
        $gameYmd = $startTs > 0 ? date('Y-m-d', $startTs) : '';
        $isTodayGame = $gameYmd !== '' && $gameYmd === $todayYmd;

        $match = null;
        if ($isTodayGame) {
            foreach ($scrapedScores as $row) {
                $lsHome = slugify_name((string)($row['homeTeam'] ?? ''));
                $lsAway = slugify_name((string)($row['awayTeam'] ?? ''));
                $homeOk = strlen($homeSlug) > 2 && $lsHome !== '' && ($lsHome === $homeSlug || str_contains($lsHome, $homeSlug) || str_contains($homeSlug, $lsHome));
                $awayOk = strlen($awaySlug) > 2 && $lsAway !== '' && ($lsAway === $awaySlug || str_contains($lsAway, $awaySlug) || str_contains($awaySlug, $lsAway));
                if ($homeOk && $awayOk) {
                    $match = $row;
                    break;
                }
            }
        }

        $apiStatusLabel = mb_strtolower((string)($game['status_label'] ?? ''), 'UTF-8');
        $apiTimeText = mb_strtolower((string)($game['data']['time'] ?? ''), 'UTF-8');

        $isFinished = str_contains($apiStatusLabel, 'enc') || str_contains($apiTimeText, 'fim') || str_contains($apiTimeText, 'enc');
        $isLive = !$isFinished && (
            str_contains($apiStatusLabel, 'vivo') ||
            str_contains($apiTimeText, 'vivo') ||
            str_contains($apiTimeText, 'andamento') ||
            str_contains($apiTimeText, '1t') ||
            str_contains($apiTimeText, '2t') ||
            str_contains((string)($game['data']['time'] ?? ''), "'")
        );

        $homeScore = $game['homeScore'] ?? '';
        $awayScore = $game['awayScore'] ?? '';
        $statusText = (string)($game['statusText'] ?? ($game['data']['time'] ?? 'HOJE'));

        if (is_array($match)) {
            $statusText = (string)($match['statusText'] ?? $statusText);
            $homeScore = $match['homeScore'] ?? $homeScore;
            $awayScore = $match['awayScore'] ?? $awayScore;

            $statusLow = mb_strtolower($statusText, 'UTF-8');
            $scraperFinished = str_contains($statusLow, 'fin') || str_contains($statusLow, 'fim') || str_contains($statusLow, 'enc');
            $scraperLive = str_contains($statusText, "'") ||
                str_contains($statusLow, 'min') ||
                str_contains($statusLow, 'int') ||
                str_contains($statusLow, 'andamento') ||
                str_contains($statusLow, 'vivo') ||
                str_contains($statusLow, '2t') ||
                str_contains($statusLow, '1t') ||
                str_contains($statusLow, 'acresc') ||
                str_contains($statusLow, 'penal');

            $isScheduled = preg_match('/(\d{1,2}):(\d{2})/', $statusText) === 1 && !$scraperLive && !$scraperFinished;

            if ($scraperFinished) {
                $isFinished = true;
                $isLive = false;
            } elseif ($scraperLive) {
                $isLive = true;
                $isFinished = false;
            } elseif ($isScheduled) {
                $isLive = false;
                $isFinished = false;
            }
        }

        $game['homeScore'] = $homeScore;
        $game['awayScore'] = $awayScore;
        $game['statusText'] = $statusText;
        $game['status_label'] = $isLive ? 'Ao Vivo' : ($isFinished ? 'Encerrado' : 'Agendado');
        $game['scrapedLeague'] = is_array($match) ? ((string)($match['leagueName'] ?? '')) : '';

        $merged[] = $game;
    }

    return $merged;
}

function build_snapshot(): ?array {
    $jogos = fetch_json_array('https://embedtv.cv/jogos2.php');
    $placarHtml = fetch_url_text('https://www.placardefutebol.com.br/jogos-de-hoje');

    if (!is_array($jogos) || $placarHtml === null) {
        return null;
    }

    $scores = parse_placar_html($placarHtml);
    $games = merge_games_with_scores($jogos, $scores);

    return [
        'generated_at' => time(),
        'games' => $games,
    ];
}

$cached = read_cache_file($cacheFile);
if (is_fresh_cache($cached, SNAPSHOT_TTL)) {
    json_out(200, [
        'generated_at' => (int)$cached['generated_at'],
        'games' => is_array($cached['games'] ?? null) ? $cached['games'] : [],
    ]);
}

$lockHandle = @fopen($lockFile, 'c');
if ($lockHandle === false) {
    if (is_array($cached)) {
        json_out(200, [
            'generated_at' => (int)($cached['generated_at'] ?? time()),
            'games' => is_array($cached['games'] ?? null) ? $cached['games'] : [],
        ]);
    }
    json_out(503, ['error' => 'Falha ao obter lock de atualizacao']);
}

try {
    if (!flock($lockHandle, LOCK_EX)) {
        if (is_array($cached)) {
            json_out(200, [
                'generated_at' => (int)($cached['generated_at'] ?? time()),
                'games' => is_array($cached['games'] ?? null) ? $cached['games'] : [],
            ]);
        }
        json_out(503, ['error' => 'Falha ao bloquear cache']);
    }

    $cachedAfterLock = read_cache_file($cacheFile);
    if (is_fresh_cache($cachedAfterLock, SNAPSHOT_TTL)) {
        json_out(200, [
            'generated_at' => (int)$cachedAfterLock['generated_at'],
            'games' => is_array($cachedAfterLock['games'] ?? null) ? $cachedAfterLock['games'] : [],
        ]);
    }

    $snapshot = build_snapshot();
    if (is_array($snapshot)) {
        @file_put_contents($cacheFile, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        json_out(200, [
            'generated_at' => (int)$snapshot['generated_at'],
            'games' => is_array($snapshot['games'] ?? null) ? $snapshot['games'] : [],
        ]);
    }

    if (is_array($cachedAfterLock)) {
        json_out(200, [
            'generated_at' => (int)($cachedAfterLock['generated_at'] ?? time()),
            'games' => is_array($cachedAfterLock['games'] ?? null) ? $cachedAfterLock['games'] : [],
        ]);
    }

    if (is_array($cached)) {
        json_out(200, [
            'generated_at' => (int)($cached['generated_at'] ?? time()),
            'games' => is_array($cached['games'] ?? null) ? $cached['games'] : [],
        ]);
    }

    json_out(502, ['error' => 'Nao foi possivel atualizar snapshot']);
} finally {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
}
