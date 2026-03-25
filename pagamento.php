<?php
require_once __DIR__ . '/security.php';
configurar_sessao();

$usuario_logado = validar_sessao_cookie();

// Se não está logado, redireciona para login
if (!$usuario_logado) {
    header('Location: login.php');
    exit;
}

// Se não está expirado, redireciona para index
if (!isset($usuario_logado['expired']) || $usuario_logado['expired'] !== true) {
    header('Location: index.php');
    exit;
}

$email = $usuario_logado['email'] ?? 'Usuário';
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
            max-width: 500px;
            width: 100%;
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo h1 {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent), #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .logo p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
        }

        .icon-warning {
            width: 80px;
            height: 80px;
            background: rgba(248, 81, 73, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }

        .icon-warning svg {
            width: 40px;
            height: 40px;
            color: var(--danger);
        }

        h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .email-display {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px 20px;
            font-size: 14px;
            color: var(--text);
            margin-bottom: 24px;
        }

        .email-display span {
            color: var(--accent);
            font-weight: 600;
        }

        .plans {
            display: grid;
            gap: 12px;
            margin-bottom: 24px;
        }

        .plan {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.2s;
        }

        .plan:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        .plan.selected {
            border-color: var(--accent);
            background: rgba(79, 91, 245, 0.1);
        }

        .plan-info {
            text-align: left;
        }

        .plan-name {
            font-weight: 600;
            font-size: 15px;
        }

        .plan-desc {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .plan-price {
            font-size: 20px;
            font-weight: 700;
            color: var(--accent);
        }

        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
            margin-top: 12px;
        }

        .btn-secondary:hover {
            border-color: var(--text-muted);
        }

        .pix-info {
            margin-top: 16px;
            padding: 16px;
            background: var(--bg-secondary);
            border-radius: 12px;
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .pix-info strong {
            color: var(--text);
            display: block;
            margin-bottom: 4px;
        }

        .hidden { display: none; }

        .payment-form {
            text-align: left;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 14px;
            font-family: inherit;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .whatsapp-btn {
            background: #25d366;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            margin-top: 16px;
            transition: all 0.2s;
        }

        .whatsapp-btn:hover {
            background: #20bd5a;
            transform: translateY(-2px);
        }

        .whatsapp-btn svg {
            width: 20px;
            height: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>ElitePLAY</h1>
            <p>Seu IPTV Premium</p>
        </div>

        <div class="card">
            <div class="icon-warning">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            <h2>Acesso Expirado</h2>
            <p class="subtitle">
                Olá, <strong><?php echo htmlspecialchars($email); ?></strong>.<br>
                Seu período de acesso ao ElitePLAY venceu.<br>
                Renove para continuar assistindo seus canais favoritos.
            </p>

            <div class="plans" id="plansSection">
                <div class="plan" onclick="selectPlan(30, this)">
                    <div class="plan-info">
                        <div class="plan-name">Mensal</div>
                        <div class="plan-desc">30 dias de acesso</div>
                    </div>
                    <div class="plan-price">R$ 30</div>
                </div>
                <div class="plan" onclick="selectPlan(90, this)">
                    <div class="plan-info">
                        <div class="plan-name">Trimestral</div>
                        <div class="plan-desc">90 dias de acesso</div>
                    </div>
                    <div class="plan-price">R$ 80</div>
                </div>
                <div class="plan" onclick="selectPlan(180, this)">
                    <div class="plan-info">
                        <div class="plan-name">Semestral</div>
                        <div class="plan-desc">180 dias de acesso</div>
                    </div>
                    <div class="plan-price">R$ 140</div>
                </div>
                <div class="plan selected" onclick="selectPlan(365, this)">
                    <div class="plan-info">
                        <div class="plan-name">Anual</div>
                        <div class="plan-desc">365 dias de acesso</div>
                    </div>
                    <div class="plan-price">R$ 200</div>
                </div>
            </div>

            <button class="btn btn-primary" onclick="showPaymentForm()">
                Continuar com Pix ou Transferência
            </button>

            <div class="payment-form hidden" id="paymentForm">
                <div class="form-group">
                    <label>Plano Selecionado</label>
                    <div class="email-display" id="selectedPlan">Anual - R$ 200</div>
                </div>
                
                <div class="form-group">
                    <label>Seu E-mail</label>
                    <input type="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>" id="paymentEmail">
                </div>

                <div class="pix-info">
                    <strong>Como funciona:</strong>
                    1. Clique no botão abaixo para enviar no WhatsApp<br>
                    2. Faça o pagamento via Pix<br>
                    3. Aguarde a confirmação e seus dias serão creditados automaticamente
                </div>

                <a href="#" class="whatsapp-btn" id="whatsappLink" target="_blank">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    Solicitar Pagamento via WhatsApp
                </a>

                <button class="btn btn-secondary" onclick="hidePaymentForm()">
                    Voltar
                </button>
            </div>

            <button class="btn btn-secondary" onclick="logout()">
                Sair e fazer login com outra conta
            </button>
        </div>
    </div>

    <script>
        let selectedDays = 365;
        const planPrices = { 30: 30, 90: 80, 180: 140, 365: 200 };
        const planNames = { 30: 'Mensal', 90: 'Trimestral', 180: 'Semestral', 365: 'Anual' };

        function selectPlan(days, el) {
            document.querySelectorAll('.plan').forEach(p => p.classList.remove('selected'));
            el.classList.add('selected');
            selectedDays = days;
            document.getElementById('selectedPlan').textContent = `${planNames[days]} - R$ ${planPrices[days]}`;
        }

        function showPaymentForm() {
            document.getElementById('plansSection').classList.add('hidden');
            document.querySelector('.btn-primary').classList.add('hidden');
            document.getElementById('paymentForm').classList.remove('hidden');
            
            const email = encodeURIComponent(document.getElementById('paymentEmail').value);
            const plano = encodeURIComponent(`${planNames[selectedDays]} - R$ ${planPrices[selectedDays]}`);
            const msg = encodeURIComponent(`Olá! Querorenewar meu acesso ao ElitePLAY.\n\nPlano: ${planNames[selectedDays]}\nValor: R$ ${planPrices[selectedDays]}\nE-mail: ${email}`);
            document.getElementById('whatsappLink').href = `https://wa.me/5511999999999?text=${msg}`;
        }

        function hidePaymentForm() {
            document.getElementById('plansSection').classList.remove('hidden');
            document.querySelector('.btn-primary').classList.remove('hidden');
            document.getElementById('paymentForm').classList.add('hidden');
        }

        function logout() {
            window.location.href = 'logout.php';
        }
    </script>
</body>
</html>
