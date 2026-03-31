<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function processar_webhook_lowify(array $payload): array {
    $event = strtolower(trim((string)($payload['event'] ?? '')));
    if ($event !== 'sale.paid') {
        return ['http' => 200, 'body' => ['status' => 'ignored', 'reason' => 'event_not_supported', 'event' => $event]];
    }

    $email = strtolower(trim((string)($payload['customer']['email'] ?? '')));
    $productId = (string)($payload['product']['id'] ?? '');
    $productName = trim((string)($payload['product']['name'] ?? ''));
    $saleId = trim((string)($payload['sale_id'] ?? ''));
    $nome = trim((string)($payload['customer']['name'] ?? ''));
    $whatsapp = trim((string)($payload['customer']['phone'] ?? ''));
    $isTest = !empty($payload['is_test']);

    if ($email === '') {
        return ['http' => 400, 'body' => ['status' => 'error', 'message' => 'Missing customer.email']];
    }

    // Testes nao devem alterar base de usuarios em producao.
    if ($isTest || $productId === '0' || stripos($productName, 'teste') !== false) {
        return [
            'http' => 200,
            'body' => [
                'status' => 'ignored',
                'reason' => 'test_payload',
                'email' => $email,
                'sale_id' => $saleId,
            ],
        ];
    }

    $planos = [
        '31315' => ['nome' => 'Pocket', 'dias' => 2],
        '30453' => ['nome' => 'Mensal', 'dias' => 30],
        '30456' => ['nome' => 'Semestral', 'dias' => 180],
    ];

    $plano = null;
    $nomeLower = strtolower($productName);
    if (isset($planos[$productId])) {
        $plano = $planos[$productId];
    } elseif (strpos($nomeLower, 'semestral') !== false) {
        $plano = ['nome' => 'Semestral', 'dias' => 180];
    } elseif (strpos($nomeLower, 'mensal') !== false) {
        $plano = ['nome' => 'Mensal', 'dias' => 30];
    } elseif (strpos($nomeLower, 'pocket') !== false) {
        $plano = ['nome' => 'Pocket', 'dias' => 2];
    }

    if ($plano === null) {
        return [
            'http' => 400,
            'body' => [
                'status' => 'error',
                'message' => 'Unknown product ID',
                'product_id' => $productId,
                'product_name' => $productName,
                'sale_id' => $saleId,
            ],
        ];
    }

    $dias = (int)$plano['dias'];
    $nomePlano = (string)$plano['nome'];

    try {
        $pdo = db();

        $stmt = $pdo->prepare('SELECT id, acesso_expira_em FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $agora = new DateTime();
            $base = $agora;
            if (!empty($usuario['acesso_expira_em'])) {
                $expAtual = new DateTime((string)$usuario['acesso_expira_em']);
                if ($expAtual > $agora) {
                    $base = $expAtual;
                }
            }

            $novaExpiracao = clone $base;
            $novaExpiracao->modify('+' . $dias . ' days');
            $novosDias = (int)ceil(($novaExpiracao->getTimestamp() - time()) / 86400);
            if ($novosDias < 0) {
                $novosDias = 0;
            }

            $stmt = $pdo->prepare('UPDATE usuarios SET dias_acesso = ?, acesso_expira_em = ? WHERE id = ?');
            $stmt->execute([$novosDias, $novaExpiracao->format('Y-m-d H:i:s'), (int)$usuario['id']]);

            // Revoga sessoes para aplicar nova validade imediatamente.
            $pdo->prepare('UPDATE sessoes SET revogada = 1 WHERE usuario_id = ? AND revogada = 0')->execute([(int)$usuario['id']]);

            return [
                'http' => 200,
                'body' => [
                    'status' => 'success',
                    'action' => 'renewed',
                    'email' => $email,
                    'sale_id' => $saleId,
                    'plan' => $nomePlano,
                    'days_added' => $dias,
                    'total_days' => $novosDias,
                    'expires_at' => $novaExpiracao->format('Y-m-d H:i:s'),
                ],
            ];
        }

        $expiraEm = new DateTime();
        $expiraEm->modify('+' . $dias . ' days');

        $stmt = $pdo->prepare('INSERT INTO usuarios (email, nome, whatsapp, ativo, dias_acesso, acesso_expira_em) VALUES (?, ?, ?, 1, ?, ?)');
        $stmt->execute([$email, $nome !== '' ? $nome : null, $whatsapp !== '' ? $whatsapp : null, $dias, $expiraEm->format('Y-m-d H:i:s')]);

        return [
            'http' => 200,
            'body' => [
                'status' => 'success',
                'action' => 'created',
                'email' => $email,
                'sale_id' => $saleId,
                'plan' => $nomePlano,
                'days' => $dias,
                'expires_at' => $expiraEm->format('Y-m-d H:i:s'),
            ],
        ];
    } catch (Throwable $e) {
        return ['http' => 500, 'body' => ['status' => 'error', 'message' => 'Database error', 'sale_id' => $saleId]];
    }
}
