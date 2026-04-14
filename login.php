<?php
require_once __DIR__ . '/security.php';
configurar_sessao();

// Se já estiver logado, redireciona direto
if (validar_sessao_cookie() !== null) {
    header('Location: index.php');
    exit;
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ElitePLAY - Entrar</title>
    <link rel="icon" type="image/webp" href="assets/favicon.webp">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #05070a;
            --bg-card: rgba(255, 255, 255, 0.04);
            --bg-input: #0b0d14;
            --primary-blue: #3b82f6;
            --text-light: #ffffff;
            --text-muted: #94a3b8;
            --accent-glow: rgba(59, 130, 246, 0.5);
            --glass-blur: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Background Blobs */
        .background-blobs {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 0;
            overflow: hidden;
            filter: blur(100px);
            opacity: 0.35;
            pointer-events: none;
        }

        .blob { position: absolute; border-radius: 50%; }
        .blob-1 { width: 500px; height: 500px; background: #1d4ed8; top: -150px; right: -100px; }
        .blob-2 { width: 400px; height: 400px; background: #7e22ce; bottom: -100px; left: -100px; }
        .blob-3 { width: 300px; height: 300px; background: #0f172a; top: 40%; left: 40%; }

        /* Login Container */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 440px;
            padding: 20px;
            animation: fadeInUp 0.5s ease both;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Logo */
        .logo-area {
            text-align: center;
            margin-bottom: 36px;
        }

        .logo {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .logo span {
            color: var(--text-muted);
            font-weight: 300;
        }

        /* Card */
        .login-card {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 20px;
            padding: 36px 32px;
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.03);
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            display: flex;
            align-items: center;
        }

        .form-input {
            width: 100%;
            background-color: var(--bg-input);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 12px;
            padding: 15px 16px 15px 44px;
            color: var(--text-light);
            font-size: 15px;
            font-family: 'Outfit', sans-serif;
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s;
        }

        .form-input::placeholder {
            color: rgba(148, 163, 184, 0.5);
        }

        .form-input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
            letter-spacing: 0.3px;
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4);
            filter: brightness(1.1);
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        .btn-submit.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        /* Spinner */
        .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        .btn-submit.loading .spinner { display: block; }
        .btn-submit.loading .btn-text { display: none; }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Info box */
        .info-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 10px;
            padding: 12px 14px;
            margin-top: 20px;
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .info-box svg {
            flex-shrink: 0;
            color: var(--primary-blue);
            margin-top: 1px;
        }

        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 8px;
        }

        .alert.show { display: flex; }
        .alert.error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25); color: #f87171; }
        .alert.success { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.25); color: #4ade80; }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: rgba(148, 163, 184, 0.4);
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: rgba(148, 163, 184, 0.3);
            font-size: 12px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.06);
        }

        /* Live Badge Decoration */
        .live-badge-deco {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            background: rgba(255, 6, 6, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 3px 10px;
            border-radius: 50px;
            margin-left: 10px;
            vertical-align: middle;
            animation: pulse-badge 1.5s infinite;
        }

        .live-badge-deco::before {
            content: '';
            width: 6px;
            height: 6px;
            background: #f87171;
            border-radius: 50%;
        }

        @keyframes pulse-badge {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        @media (max-width: 480px) {
            .login-card { padding: 28px 20px; }
            .tab-btn {
                flex: 1;
                padding: 12px;
                border: none;
                background: transparent;
                color: var(--text-muted);
                border-radius: 7px;
                font-size: 16px;
                font-weight: 600;
                font-family: 'Outfit', sans-serif;
                cursor: pointer;
                transition: all 0.2s;
            }
            .btn-submit {
                width: 100%;
                padding: 15px;
                background: linear-gradient(135deg, #3b82f6, #2563eb);
                color: white;
                border: none;
                border-radius: 12px;
                font-size: 16px;
                text-transform: uppercase;
                font-weight: 700;
                font-family: 'Outfit', sans-serif;
                cursor: pointer;
                transition: all 0.25s;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                letter-spacing: 0.3px;
                position: relative;
                overflow: hidden;
            }
            .form-input {
                width: 100%;
                background-color: var(--bg-input);
                border: 1px solid rgba(255, 255, 255, 0.07);
                border-radius: 12px;
                padding: 18px 16px 18px 44px;
                color: var(--text-light);
                font-size: 18px;
                font-family: 'Outfit', sans-serif;
                outline: none;
                transition: border-color 0.25s, box-shadow 0.25s;
            }
        }

        /* Tabs */
        .auth-tabs {
            display: flex;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            padding: 4px;
            margin-bottom: 24px;
            gap: 4px;
        }
        .tab-btn {
            flex: 1;
            padding: 9px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab-btn.active {
            background: var(--primary-blue);
            color: #fff;
            box-shadow: 0 2px 10px rgba(59,130,246,0.35);
        }
    </style>
</head>
<body>

    <div class="background-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <div class="login-wrapper">

        <div class="logo-area">
            <img src="imagens/elitelogo.webp" alt="ElitePLAY" class="logo" style="height: 50px;">
        </div>

        <div class="login-card">

            <div class="auth-tabs">
                <button class="tab-btn active" id="tab-login" onclick="switchTab('login')">Entrar</button>
                <button class="tab-btn" id="tab-cadastro" onclick="switchTab('cadastro')">Criar Conta</button>
            </div>

            <div class="alert error" id="alert-error">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span id="alert-msg">E-mail não encontrado ou sem acesso.</span>
            </div>

            <div class="alert success" id="alert-success">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>Acesso liberado! Redirecionando...</span>
            </div>

            <form id="login-form" onsubmit="handleLogin(event)" novalidate>
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            placeholder="seuemail@exemplo.com"
                            autocomplete="email"
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="btn-submit">
                    <span class="btn-text">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" style="margin-right:4px;vertical-align:middle;">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                        Acessar Minha Conta
                    </span>
                    <div class="spinner"></div>
                </button>
            </form>

            <!-- Formulário de Cadastro -->
            <form id="register-form" onsubmit="handleCadastro(event)" novalidate style="display:none">
                <div class="form-group">
                    <label for="reg-nome">Nome completo</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input type="text" id="reg-nome" class="form-input" placeholder="Seu nome completo" autocomplete="name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-email">E-mail</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </span>
                        <input type="email" id="reg-email" class="form-input" placeholder="seuemail@exemplo.com" autocomplete="email" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-whatsapp">WhatsApp</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.39 2 2 0 0 1 3.6 1.21h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.86a16 16 0 0 0 6.29 6.29l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </span>
                        <input type="tel" id="reg-whatsapp" class="form-input" placeholder="(11) 99999-9999" autocomplete="tel" required>
                    </div>
                </div>
                <button type="submit" class="btn-submit" id="btn-cadastro">
                    <span class="btn-text">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" style="margin-right:4px;vertical-align:middle;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                        Criar Minha Conta
                    </span>
                    <div class="spinner"></div>
                </button>
            </form>

            <div class="info-box" id="info-box">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span id="info-text">O acesso é exclusivo para assinantes cadastrados. Sem senha necessária — utilizamos apenas o e-mail para verificar sua assinatura.</span>
            </div>
        </div>

        <div class="login-footer">
            ElitePLAY &copy; <?php echo date('Y'); ?> — Todos os direitos reservados
        </div>
    </div>

    <script>
        let isSubmitting = false;
        let isRegSubmitting = false;
        const LOGIN_EMAIL_STORAGE_KEY = 'eliteplay_last_login_email';

        function saveLastLoginEmail(email) {
            const normalized = String(email || '').trim().toLowerCase();
            if (!normalized) return;
            try {
                localStorage.setItem(LOGIN_EMAIL_STORAGE_KEY, normalized);
            } catch (e) {}
        }

        function restoreLastLoginEmail() {
            const input = document.getElementById('email');
            if (!input || String(input.value || '').trim() !== '') return;

            try {
                const stored = localStorage.getItem(LOGIN_EMAIL_STORAGE_KEY) || '';
                if (stored && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(stored)) {
                    input.value = stored;
                }
            } catch (e) {}
        }

        function switchTab(tab) {
            const isLogin = tab === 'login';
            document.getElementById('login-form').style.display    = isLogin ? '' : 'none';
            document.getElementById('register-form').style.display = isLogin ? 'none' : '';
            document.getElementById('tab-login').classList.toggle('active', isLogin);
            document.getElementById('tab-cadastro').classList.toggle('active', !isLogin);
            document.getElementById('alert-error').classList.remove('show');
            document.getElementById('alert-success').classList.remove('show');
            document.getElementById('info-text').textContent = isLogin
                ? 'O acesso é exclusivo para assinantes cadastrados. Sem senha necessária — utilizamos apenas o e-mail para verificar sua assinatura.'
                : 'Após criar sua conta, você será redirecionado para escolher seu plano de acesso.';
        }

        async function handleCadastro(e) {
            e.preventDefault();
            if (isRegSubmitting) return;

            const nome     = document.getElementById('reg-nome').value.trim();
            const email    = document.getElementById('reg-email').value.trim();
            const whatsapp = document.getElementById('reg-whatsapp').value.trim();
            const alertError   = document.getElementById('alert-error');
            const alertMsg     = document.getElementById('alert-msg');
            const alertSuccess = document.getElementById('alert-success');
            const btn          = document.getElementById('btn-cadastro');

            alertError.classList.remove('show');
            alertSuccess.classList.remove('show');

            if (!nome) { alertMsg.textContent = 'Informe seu nome completo.'; alertError.classList.add('show'); return; }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email || !emailRegex.test(email)) { alertMsg.textContent = 'Informe um e-mail válido.'; alertError.classList.add('show'); return; }
            if (!whatsapp) { alertMsg.textContent = 'Informe seu WhatsApp.'; alertError.classList.add('show'); return; }

            isRegSubmitting = true;
            btn.classList.add('loading');
            btn.disabled = true;

            try {
                const response = await fetch('cadastro.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nome, email, whatsapp, csrf: '<?php echo $csrf; ?>' })
                }).catch(() => null);

                if (response) {
                    const data = await response.json().catch(() => null);
                    if (response.ok && data && data.success) {
                        alertSuccess.classList.add('show');
                        setTimeout(() => { window.location.href = data.redirect || 'pagamento.php'; }, 1200);
                        return;
                    }
                    if (data && data.message) {
                        alertMsg.textContent = data.message;
                        alertError.classList.add('show');
                        return;
                    }
                }
                alertMsg.textContent = 'Erro ao conectar. Tente novamente.';
                alertError.classList.add('show');
            } catch (err) {
                alertMsg.textContent = 'Erro ao conectar. Tente novamente.';
                alertError.classList.add('show');
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
                isRegSubmitting = false;
            }
        }

        async function handleLogin(e) {
            e.preventDefault();

            if (isSubmitting) return;

            const email = document.getElementById('email').value.trim();
            const btn = document.getElementById('btn-submit');
            const alertError = document.getElementById('alert-error');
            const alertSuccess = document.getElementById('alert-success');
            const alertMsg = document.getElementById('alert-msg');
            const successMsg = document.querySelector('#alert-success strong');

            // Esconde alertas anteriores
            alertError.classList.remove('show');
            alertSuccess.classList.remove('show');

            // Validação básica de e-mail
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email || !emailRegex.test(email)) {
                alertMsg.textContent = 'Por favor, informe um e-mail válido.';
                alertError.classList.add('show');
                document.getElementById('email').focus();
                return;
            }

            // Ativa loading
            isSubmitting = true;
            btn.classList.add('loading');
            btn.disabled = true;

            try {
                // Exemplo de chamada ao backend (será implementada com banco de dados depois)
                const response = await fetch('auth.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, csrf: '<?php echo $csrf; ?>' })
                }).catch(() => null);

                if (response) {
                    const data = await response.json().catch(() => null);
                    if (response.ok && data && data.success) {
                        saveLastLoginEmail(email);
                        if (successMsg && data.message) successMsg.textContent = data.message;
                        alertSuccess.classList.add('show');
                        const nextUrl = data.redirect || 'index.php';
                        setTimeout(() => { window.location.href = nextUrl; }, 1500);
                        return;
                    }

                    if (data && data.message) {
                        alertMsg.textContent = data.message;
                        alertError.classList.add('show');
                        return;
                    }
                }

                // Fallback: E-mail não encontrado
                alertMsg.textContent = 'E-mail não encontrado ou sem acesso. Verifique e tente novamente.';
                alertError.classList.add('show');

            } catch (err) {
                alertMsg.textContent = 'Erro ao conectar. Tente novamente.';
                alertError.classList.add('show');
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
                isSubmitting = false;
            }
        }

        // Limpa alertas ao digitar
        document.getElementById('email').addEventListener('input', () => {
            document.getElementById('alert-error').classList.remove('show');
            document.getElementById('alert-success').classList.remove('show');
            const successMsg = document.querySelector('#alert-success strong');
            if (successMsg) successMsg.textContent = 'Acesso autorizado! Redirecionando...';
        });

        restoreLastLoginEmail();
    </script>

</body>
</html>
