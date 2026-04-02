<?php

const HOME_BANNERS_FILE = __DIR__ . '/data/home_banners.json';
const HOME_BANNERS_UPLOAD_DIR = __DIR__ . '/uploads/home_banners';
const HOME_BANNERS_WEB_PATH = 'uploads/home_banners';

function home_generate_banner_id(): string
{
    try {
        return 'bnr_' . bin2hex(random_bytes(6));
    } catch (Throwable $e) {
        return 'bnr_' . uniqid('', true);
    }
}

function home_sanitize_banner_id(string $id): string
{
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($id));
    if ($id === null) {
        return '';
    }

    return substr($id, 0, 40);
}

function home_banner_is_valid_asset_path(string $path): bool
{
    if ($path === '') {
        return false;
    }

    if (strpos($path, HOME_BANNERS_WEB_PATH . '/') !== 0) {
        return false;
    }

    return strpos($path, '..') === false;
}

function normalize_home_banner_link(string $link): string
{
    $link = trim($link);
    if ($link === '') {
        return '';
    }

    if (strpos($link, '/') === 0) {
        return mb_substr($link, 0, 500);
    }

    $scheme = strtolower((string)parse_url($link, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return '';
    }

    return mb_substr($link, 0, 500);
}

function normalize_home_banners(array $raw): array
{
    $normalized = [];
    $seenIds = [];

    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }

        $id = home_sanitize_banner_id((string)($item['id'] ?? ''));
        if ($id === '') {
            $id = home_generate_banner_id();
        }
        if (isset($seenIds[$id])) {
            $id = home_generate_banner_id();
        }
        $seenIds[$id] = true;

        $link = trim((string)($item['link'] ?? ''));
        $desktopImage = trim((string)($item['desktop_image'] ?? ''));
        $mobileImage = trim((string)($item['mobile_image'] ?? ''));

        $normalized[] = [
            'id' => $id,
            'link' => normalize_home_banner_link($link),
            'desktop_image' => home_banner_is_valid_asset_path($desktopImage) ? $desktopImage : '',
            'mobile_image' => home_banner_is_valid_asset_path($mobileImage) ? $mobileImage : '',
        ];

        if (count($normalized) >= 20) {
            break;
        }
    }

    return $normalized;
}

function load_home_banners(): array
{
    if (!is_file(HOME_BANNERS_FILE)) {
        return [];
    }

    $json = @file_get_contents(HOME_BANNERS_FILE);
    if ($json === false || $json === '') {
        return [];
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return [];
    }

    return normalize_home_banners($decoded);
}

function save_home_banners(array $banners): bool
{
    $normalized = normalize_home_banners($banners);
    $dataDir = dirname(HOME_BANNERS_FILE);

    if (!is_dir($dataDir) && !@mkdir($dataDir, 0775, true) && !is_dir($dataDir)) {
        return false;
    }

    $json = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }

    return @file_put_contents(HOME_BANNERS_FILE, $json, LOCK_EX) !== false;
}

function delete_home_banner_asset(string $path): void
{
    if (!home_banner_is_valid_asset_path($path)) {
        return;
    }

    $fullPath = __DIR__ . '/' . $path;
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

function store_home_banner_upload(array $file, string $bannerId, string $variant): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no upload da imagem do banner.');
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Arquivo de upload inválido.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new RuntimeException('Envie apenas arquivos de imagem válidos.');
    }

    $maxSize = 8 * 1024 * 1024;
    if ((int)($file['size'] ?? 0) > $maxSize) {
        throw new RuntimeException('A imagem excede o limite de 8MB.');
    }

    $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Formato inválido. Use JPG, PNG ou WEBP.');
    }

    if (!is_dir(HOME_BANNERS_UPLOAD_DIR) && !@mkdir(HOME_BANNERS_UPLOAD_DIR, 0775, true) && !is_dir(HOME_BANNERS_UPLOAD_DIR)) {
        throw new RuntimeException('Não foi possível criar o diretório de banners.');
    }

    $safeVariant = $variant === 'mobile' ? 'mobile' : 'desktop';
    $safeBannerId = home_sanitize_banner_id($bannerId);
    if ($safeBannerId === '') {
        $safeBannerId = home_generate_banner_id();
    }
    $fileName = sprintf('slide_%s_%s_%d_%s.%s', $safeBannerId, $safeVariant, time(), bin2hex(random_bytes(4)), $ext);
    $target = HOME_BANNERS_UPLOAD_DIR . '/' . $fileName;

    if (!@move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Falha ao salvar a imagem do banner.');
    }

    return HOME_BANNERS_WEB_PATH . '/' . $fileName;
}
