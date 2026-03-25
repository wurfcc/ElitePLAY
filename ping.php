<?php
// ============================================================
//  ping.php — Verificação periódica de sessão (heartbeat)
//  Chamado pelo frontend a cada 30 segundos via fetch()
//  Retorna: { "valid": bool, "reason": "..." }
// ============================================================
require_once __DIR__ . '/security.php';

configurar_sessao();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');
header('X-Content-Type-Options: nosniff');

// Só aceita GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'reason' => 'method_not_allowed']);
    exit;
}

$sessao = validar_sessao_cookie();

if ($sessao === null) {
    // Sessão inválida, expirada ou revogada (pode ser que outro dispositivo logou)
    echo json_encode([
        'valid'  => false,
        'reason' => 'session_invalid',
    ]);
    exit;
}

// Sessão válida — retorna OK com tempo restante para o frontend saber quando renovar
$expires = strtotime($sessao['expires_at']);
$restante = max(0, $expires - time());

echo json_encode([
    'valid'            => true,
    'expires_in'       => $restante,
]);
exit;
