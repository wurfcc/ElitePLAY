<?php

const HOME_BANNERS_FILE = __DIR__ . '/data/home_banners.json';
const HOME_BANNERS_SETTINGS_FILE = __DIR__ . '/data/home_banners_settings.json';
const HOME_BANNERS_UPLOAD_DIR = __DIR__ . '/uploads/home_banners';
const HOME_BANNERS_WEB_PATH = 'uploads/home_banners';

function home_write_json_file(string $targetPath, string $json): bool
{
    $dir = dirname($targetPath);

    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }

    if (!is_writable($dir)) {
        @chmod($dir, 0777);
    }

    if (is_file($targetPath) && !is_writable($targetPath)) {
        @chmod($targetPath, 0666);
    }

    $tmpPath = @tempnam($dir, 'hb_');
    if ($tmpPath === false) {
        $direct = @file_put_contents($targetPath, $json, LOCK_EX) !== false;
        if ($direct) {
            @chmod($targetPath, 0666);
        }
        return $direct;
    }

    $written = @file_put_contents($tmpPath, $json, LOCK_EX) !== false;
    if (!$written) {
        @unlink($tmpPath);
        return false;
    }

    @chmod($tmpPath, 0666);

    clearstatcache(true, $targetPath);
    if (is_file($targetPath) && !@rename($tmpPath, $targetPath)) {
        @unlink($targetPath);
    }

    if (!@rename($tmpPath, $targetPath)) {
        if (!@copy($tmpPath, $targetPath)) {
            $direct = @file_put_contents($targetPath, $json, LOCK_EX) !== false;
            @unlink($tmpPath);
            if ($direct) {
                @chmod($targetPath, 0666);
            }
            return $direct;
        }
        if (!@unlink($tmpPath)) {
            @unlink($tmpPath);
        }
    }

    @chmod($targetPath, 0666);
    return true;
}

function home_banners_default_settings(): array
{
    return [
        'enabled' => true,
    ];
}

function load_home_banners_settings(): array
{
    $defaults = home_banners_default_settings();
    if (!is_file(HOME_BANNERS_SETTINGS_FILE)) {
        return $defaults;
    }

    $json = @file_get_contents(HOME_BANNERS_SETTINGS_FILE);
    if ($json === false || $json === '') {
        return $defaults;
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return $defaults;
    }

    return [
        'enabled' => isset($decoded['enabled']) ? (bool)$decoded['enabled'] : true,
    ];
}

function save_home_banners_settings(array $settings): bool
{
    $payload = [
        'enabled' => isset($settings['enabled']) ? (bool)$settings['enabled'] : true,
    ];

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }

    return home_write_json_file(HOME_BANNERS_SETTINGS_FILE, $json);
}

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

    $json = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }

    return home_write_json_file(HOME_BANNERS_FILE, $json);
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
    $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'A imagem excede upload_max_filesize do PHP.',
            UPLOAD_ERR_FORM_SIZE => 'A imagem excede o limite do formulário.',
            UPLOAD_ERR_PARTIAL => 'Upload parcial. Tente novamente.',
            UPLOAD_ERR_NO_FILE => 'Nenhuma imagem foi enviada.',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária de upload ausente no servidor.',
            UPLOAD_ERR_CANT_WRITE => 'Não foi possível escrever no disco durante o upload.',
            UPLOAD_ERR_EXTENSION => 'Uma extensão do PHP bloqueou o upload da imagem.',
        ];
        $msg = $messages[$uploadError] ?? 'Falha no upload da imagem do banner.';
        throw new RuntimeException($msg);
    }

    if (!isset($file['tmp_name']) || (!is_uploaded_file($file['tmp_name']) && !is_file($file['tmp_name']))) {
        throw new RuntimeException('Arquivo de upload inválido.');
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = (string)@finfo_file($finfo, $file['tmp_name']);
            @finfo_close($finfo);
        }
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

    if ($mime !== '' && strpos($mime, 'image/') !== 0) {
        throw new RuntimeException('Envie apenas arquivos de imagem válidos.');
    }

    if (!is_dir(HOME_BANNERS_UPLOAD_DIR) && !@mkdir(HOME_BANNERS_UPLOAD_DIR, 0775, true) && !is_dir(HOME_BANNERS_UPLOAD_DIR)) {
        throw new RuntimeException('Não foi possível criar o diretório de banners.');
    }

    if (!is_writable(HOME_BANNERS_UPLOAD_DIR)) {
        @chmod(HOME_BANNERS_UPLOAD_DIR, 0775);
    }

    if (!is_writable(HOME_BANNERS_UPLOAD_DIR)) {
        throw new RuntimeException('Sem permissão de escrita em uploads/home_banners.');
    }

    $safeVariant = $variant === 'mobile' ? 'mobile' : 'desktop';
    $safeBannerId = home_sanitize_banner_id($bannerId);
    if ($safeBannerId === '') {
        $safeBannerId = home_generate_banner_id();
    }
    $fileName = sprintf('slide_%s_%s_%d_%s.%s', $safeBannerId, $safeVariant, time(), bin2hex(random_bytes(4)), $ext);
    $target = HOME_BANNERS_UPLOAD_DIR . '/' . $fileName;

    if (!@move_uploaded_file($file['tmp_name'], $target)) {
        if (!@copy($file['tmp_name'], $target)) {
            throw new RuntimeException('Falha ao salvar a imagem do banner. Verifique permissões da pasta uploads/home_banners.');
        }
    }

    return HOME_BANNERS_WEB_PATH . '/' . $fileName;
}
