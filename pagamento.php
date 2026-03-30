<?php
require_once __DIR__ . '/security.php';
configurar_sessao();

$usuario_logado = validar_sessao_cookie();

if (!$usuario_logado) {
    header('Location: login.php');
    exit;
}

if (!isset($usuario_logado['expired']) || $usuario_logado['expired'] !== true) {
    header('Location: index.php');
    exit;
}

$email = $usuario_logado['email'] ?? 'Usuário';
$userId = (int)($usuario_logado['usuario_id'] ?? 0);

// Verifica se o trial ainda está disponível para este usuário
$trialDisponivel = false;
try {
    $stmtTrial = db()->prepare('SELECT trial_usado FROM usuarios WHERE id = ? LIMIT 1');
    $stmtTrial->execute([$userId]);
    $rowTrial = $stmtTrial->fetch();
    $trialDisponivel = $rowTrial && (int)$rowTrial['trial_usado'] === 0;
} catch (\Throwable $e) {}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renovar Acesso - ElitePLAY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --bg-primary: #07090c;
            --bg-secondary: #0d1117;
            --bg-card: #161b22;
            --border: #30363d;
            --text: #e6edf3;
            --text-muted: #8b949e;
            --accent: #4f5bf5;
            --accent-hover: #6367f5;
            --danger: #f85149;
            --success: #3fb950;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-primary);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 480px;
            width: 100%;
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo h1 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent), #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 6px;
        }

        .logo p {
            color: var(--text-muted);
            font-size: 13px;
        }

        .subtitle {
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        .subtitle strong {
            color: var(--text);
        }

        .plans {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .plan-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .plan-info {
            flex: 1;
        }

        .plan-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .plan-desc {
            font-size: 13px;
            color: var(--text-muted);
        }

        .plan-price {
            text-align: right;
        }

        .price-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--text);
        }

        .price-period {
            font-size: 12px;
            color: var(--text-muted);
        }

        .checkout-btn {
            display: block;
            width: 100%;
            padding: 16px 24px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 24px;
        }

        .checkout-btn:hover {
            background: var(--accent-hover);
        }

        .checkout-btn.semestral {
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
        }

        .checkout-btn.semestral:hover {
            background: linear-gradient(135deg, var(--accent-hover), #7c3aed);
        }

        .logout-link {
            display: block;
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
            text-decoration: none;
            margin-top: 24px;
            transition: color 0.2s;
        }

        .logout-link:hover {
            color: var(--text);
        }

        @media (max-width: 400px) {
            .plan-card {
                flex-direction: column;
                text-align: center;
                padding: 24px 20px;
            }
            
            .plan-price {
                text-align: center;
                margin-top: 12px;
            }
            
            .price-value {
                font-size: 32px;
            }
        }
        /* Trial Banner */
        .trial-banner {
            background: linear-gradient(135deg, rgba(16,185,129,0.12), rgba(5,150,105,0.08));
            border: 1px solid rgba(16,185,129,0.35);
            border-radius: 16px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 8px;
        }
        .trial-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.2px;
            color: #10b981;
            background: rgba(16,185,129,0.15);
            border: 1px solid rgba(16,185,129,0.3);
            padding: 3px 8px;
            border-radius: 50px;
            margin-bottom: 6px;
        }
        .trial-badge::before {
            content: '';
            width: 6px; height: 6px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse-trial 1.5s infinite;
        }
        @keyframes pulse-trial {
            0%,100% { opacity:1; } 50% { opacity:0.4; }
        }
        .trial-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 2px;
        }
        .trial-desc {
            font-size: 12px;
            color: var(--text-muted);
        }
        .trial-timer {
            font-size: 11px;
            color: #10b981;
            font-weight: 600;
            margin-top: 4px;
        }
        .btn-trial {
            flex-shrink: 0;
            padding: 12px 20px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-trial:hover { filter: brightness(1.1); transform: translateY(-1px); }
        .btn-trial:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        @media (max-width: 400px) {
            .trial-banner { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="imagens/elitelogo.webp" alt="ElitePLAY" style="height: 50px;">
            <p>Seu IPTV Premium</p>
        </div>

        <p class="subtitle">
            Olá, <strong><?php echo htmlspecialchars($email); ?></strong>.<br>
            Escolha seu plano para continuar assistindo.
        </p>

        <div class="plans">

            <?php if ($trialDisponivel): ?>
            <div class="trial-banner">
                <div>
                    <div class="trial-badge">GRÁTIS</div>
                    <div class="trial-title">Teste por 10 minutos</div>
                    <div class="trial-desc">Experimente sem precisar de cartão</div>
                    <div class="trial-timer">⏱ 10 min de acesso completo</div>
                </div>
                <button class="btn-trial" id="btn-trial" onclick="ativarTrial()">
                    Testar Agora
                </button>
            </div>
            <?php endif; ?>

            <div class="plan-card">
                <div class="plan-info">
                    <div class="plan-name">Plano Pocket</div>
                    <div class="plan-desc">2 dias de acesso</div>
                </div>
                <div class="plan-price">
                    <div class="price-value">R$ 3</div>
                    <div class="price-period"></div>
                </div>
            </div>

            <a href="#" class="checkout-btn" id="checkout_pocket" target="_blank">
                Assinar Plano Pocket
            </a>

            <div class="plan-card">
                <div class="plan-info">
                    <div class="plan-name">Plano Mensal</div>
                    <div class="plan-desc">30 dias de acesso</div>
                </div>
                <div class="plan-price">
                    <div class="price-value">R$ 10</div>
                    <div class="price-period"></div>
                </div>
            </div>

            <a href="#" class="checkout-btn mensal" id="checkout_mensal" target="_blank">
                Assinar Plano Mensal
            </a>

            <div class="plan-card">
                <div class="plan-info">
                    <div class="plan-name">Plano Semestral</div>
                    <div class="plan-desc">180 dias de acesso</div>
                </div>
                <div class="plan-price">
                    <div class="price-value">R$ 47</div>
                    <div class="price-period">Economia de R$ 13</div>
                </div>
            </div>

            <a href="#" class="checkout-btn semestral" id="checkout_semestral" target="_blank">
                Assinar Plano Semestral
            </a>
        </div>

        <a href="logout.php" class="logout-link">Sair e usar outra conta</a>
    </div>

    <script>
        const CHECKOUT_POCKET    = 'https://pay.lowify.com.br/checkout.php?product_id=JrGQLa';
        const CHECKOUT_MENSAL    = 'https://pay.lowify.com.br/checkout.php?product_id=e1Cpgy';
        const CHECKOUT_SEMESTRAL = 'https://pay.lowify.com.br/checkout.php?product_id=oxmTl0';

        document.getElementById('checkout_pocket').href    = CHECKOUT_POCKET;
        document.getElementById('checkout_mensal').href    = CHECKOUT_MENSAL;
        document.getElementById('checkout_semestral').href = CHECKOUT_SEMESTRAL;

        async function ativarTrial() {
            const btn = document.getElementById('btn-trial');
            if (!btn) return;
            btn.disabled = true;
            btn.textContent = 'Ativando...';

            try {
                const res = await fetch('trial.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf: '<?php echo $csrf; ?>' })
                }).catch(() => null);

                const data = res ? await res.json().catch(() => null) : null;

                if (data && data.success) {
                    btn.textContent = 'Redirecionando...';
                    window.location.href = data.redirect || 'index.php';
                    return;
                }

                btn.textContent = data?.message || 'Erro. Tente novamente.';
                setTimeout(() => { btn.disabled = false; btn.textContent = 'Testar Agora'; }, 3000);
            } catch (e) {
                btn.disabled = false;
                btn.textContent = 'Testar Agora';
            }
        }
    </script>
</body>
</html>
