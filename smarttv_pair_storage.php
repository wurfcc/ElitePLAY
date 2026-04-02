<?php
declare(strict_types=1);

const SMARTTV_PAIRS_FILE = __DIR__ . '/data/smarttv_pairs.json';
const SMARTTV_PENDING_TTL = 600;
const SMARTTV_AUTH_TTL = 2592000;

function smarttv_now(): int
{
    return time();
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
    return is_array($decoded) ? $decoded : [];
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

function smarttv_generate_pair_id(): string
{
    return 'tv_' . bin2hex(random_bytes(5));
}

function smarttv_generate_auth_token(): string
{
    return bin2hex(random_bytes(32));
}

function smarttv_generate_pair_code(): string
{
    return strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}
