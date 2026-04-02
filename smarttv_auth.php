<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/smarttv_pair_storage.php';

configurar_sessao();

$pairId = trim((string)($_GET['pair'] ?? ''));

function smarttv_auth_safe_next(string $pairId): string
{
    return 'smarttv_auth.php?pair=' . rawurlencode($pairId);
}

if ($pairId === '') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Autorizar Smart TV</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#020617;color:#e2e8f0;font-family:Arial,sans-serif}.card{width:min(560px,92vw);border:1px solid rgba(148,163,184,.35);border-radius:16px;padding:24px;background:rgba(15,23,42,.85);text-align:center}p{color:#cbd5e1}</style></head><body><div class="card"><h1>Pareamento não encontrado</h1><p>Abra <strong>/s</strong> na Smart TV e escaneie o QR Code para iniciar a autorização.</p></div></body></html>
    <?php
    exit;
}

$viewer = validar_sessao_cookie();
if ($viewer === null || (!empty($viewer['expired']) && $viewer['expired'] === true)) {
    header('Location: login.php?next=' . rawurlencode(smarttv_auth_safe_next($pairId)));
    exit;
}

$pairs = smarttv_cleanup_pairs(smarttv_load_pairs());
$idx = smarttv_find_pair_index($pairs, $pairId);

$ok = false;
$message = 'Falha ao autorizar Smart TV.';

if ($idx < 0) {
    $message = 'Pareamento inválido ou expirado.';
} else {
    if (($pairs[$idx]['status'] ?? '') !== 'authorized') {
        $pairs[$idx]['status'] = 'authorized';
        $pairs[$idx]['user_id'] = (int)($viewer['usuario_id'] ?? 0);
        $pairs[$idx]['authorized_at'] = smarttv_now();
        $pairs[$idx]['expires_at'] = smarttv_now() + SMARTTV_AUTH_TTL;
        if (!smarttv_save_pairs($pairs)) {
            $message = 'Não foi possível salvar a autorização.';
        } else {
            $ok = true;
            $message = 'Smart TV autorizada com sucesso.';
        }
    } else {
        $ok = true;
        $message = 'Esta Smart TV já estava autorizada.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autorizar Smart TV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin:0; min-height:100vh; display:grid; place-items:center; background:#020617; color:#e2e8f0; font-family:'Outfit',sans-serif; }
        .card { width:min(560px,92vw); border:1px solid rgba(148,163,184,.35); border-radius:16px; padding:24px; background:rgba(15,23,42,.85); text-align:center; }
        h1 { margin:0 0 10px; font-size:26px; }
        p { margin:0; color:#cbd5e1; font-size:15px; line-height:1.45; }
        .ok { color:#86efac; }
        .err { color:#fda4af; }
        .btn { display:inline-block; margin-top:16px; padding:10px 14px; border-radius:10px; border:1px solid rgba(59,130,246,.4); background:rgba(59,130,246,.18); color:#dbeafe; text-decoration:none; }
    </style>
</head>
<body>
    <div class="card">
        <h1><?php echo $ok ? 'Smart TV autorizada' : 'Falha na autorização'; ?></h1>
        <p class="<?php echo $ok ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <a class="btn" href="index.php">Voltar para ElitePLAY</a>
    </div>
</body>
</html>
