<?php
require_once __DIR__ . '/middleware.php';
// Resgata os parâmetros passados via POST
$iframe_url = isset($_POST['iframe_url']) ? $_POST['iframe_url'] : '';
$iframe_url_2 = isset($_POST['iframe_url_2']) ? $_POST['iframe_url_2'] : '';
$iframe_url_3 = isset($_POST['iframe_url_3']) ? $_POST['iframe_url_3'] : '';
$title = isset($_POST['title']) ? $_POST['title'] : 'Assistir Canal';
$logo = isset($_POST['logo']) ? $_POST['logo'] : '';

$streams_json = isset($_POST['streams_json']) ? $_POST['streams_json'] : '';
$dynamic_streams = [];
if (!empty($streams_json)) {
    // Corrige problema comum onde '+' vira ' ' no POST
    $streams_json = str_replace(' ', '+', $streams_json);
    $decoded = base64_decode($streams_json);
    if ($decoded !== false) {
        $parsed = json_decode($decoded, true);
        if (is_array($parsed)) {
            $dynamic_streams = array_filter($parsed, function($s) {
                return !isset($s['name']) || stripos($s['name'], '[H265]') === false;
            });
        }
    }
}

// Tratamento de erro elegante se acessar direto
if (empty($iframe_url)) {
    die("
    <div style='background-color:#05070A; min-height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center; color:white; font-family:sans-serif;'>
        <h2 style='margin-bottom: 20px;'>Link não fornecido ou sessão expirada.</h2>
        <a href='index.php' style='background:#4f5bf5; color:white; text-decoration:none; padding:10px 20px; border-radius:8px; font-weight:bold;'>Voltar ao Início</a>
    </div>
    ");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($title); ?> - ElitePLAY</title>
    <!-- JWPlayer Script -->
    <script src="https://ssl.p.jwpcdn.com/player/v/8.6.3/jwplayer.js"></script>
    <script>jwplayer.key="64HPbvSQorQcd52B8XFuhMtEoitbvY/EXJmMBfKcXZQU2Rnn";</script>
    <style>
        :root {
            --bg-dark: #05070A;
            --bg-header: rgba(11, 13, 20, 0.85);
            --bg-card: #161a2b;
            --primary-blue: #4f5bf5;
            --primary-glow: rgba(79, 91, 245, 0.3);
            --text-light: #ffffff;
            --text-muted: #8b95a5;
            --pad-x: 15px;
        }

        @media (min-width: 768px) {
            :root { --pad-x: 40px; }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Barra de Topo do Player */
        header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            padding: 14px !important;
            background: #0E1019;
            -webkit-backdrop-filter: blur(14px);
            position: sticky;
            top: 0;
            transform: translatey(-1px);
            z-index: 200;
            border: 1px solid rgba(255, 255, 255, 0.08);
            gap: 14px;
            border-radius: 0 0 16px 16px;
            margin: 0px 8px 0px 8px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
            width: 100%;
        }

        .btn-back {
            background-color: var(--bg-card);
            color: var(--text-light);
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            border: 1px solid rgba(255, 255, 255, 0.05);
            flex-shrink: 0;
        }

        .btn-back:active {
            transform: scale(0.95);
        }

        .channel-info {
            display: flex;
            align-items: center;
            gap: 12px;
            overflow: hidden;
        }

        .channel-info img {
            height: 38px;
            width: 38px;
            border-radius: 8px;
            background: #fff;
            padding: 2px;
            object-fit: contain;
            flex-shrink: 0;
        }

        h1 {
            font-size: 16px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .live-badge {
            background: #ff0000;
            color: white;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            animation: pulse 2s infinite;
            flex-shrink: 0;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }

        /* Container do Vídeo */
        .player-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px var(--pad-x);
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .player-container {
            width: 100%;
            aspect-ratio: 16 / 9;
            background-color: #000;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Opções de Player */
        .player-options {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 20px;
            width: 100%;
        }

        .opt-btn {
            background-color: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.05);
            color: #b4becd;
            padding: 12px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.2s;
        }

        .opt-btn.active {
            border: none;
            color: #FFF;
            background: linear-gradient(135deg, var(--primary-blue), #4f5bf5, #2563eb) !important;
        }

        /* Ajustes Específicos para Celular */
        @media (max-width: 768px) {
            .player-wrapper {
                padding: 15px 0 0 0;
            }
            .player-container {
                border-radius: 0;
                border-left: none;
                border-right: none;
            }
            .player-options {
                padding: 0 15px;
            }
            .header-left {
                gap: 10px;
            }
            .channel-info img {
                height: 32px;
                width: 32px;
            }
            h1 {
                font-size: 15px;
            }
        }
    </style>
</head>
<body>

    <header>
        <div class="header-left">
            <a href="index.php" class="btn-back">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </a>
            <div class="channel-info">
                <h1><?php echo htmlspecialchars($title); ?> <span class="live-badge">Ao Vivo</span></h1>
            </div>
        </div>
    </header>

    <div class="player-wrapper">
        <div class="player-container" id="player-container">
            <div id="jw-player-container" style="display: none; width: 100%; height: 100%;"></div>
            <iframe id="main-player" src="<?php echo $iframe_url; ?>" 
                allowfullscreen 
                webkitallowfullscreen 
                mozallowfullscreen 
                allow="autoplay; encrypted-media; fullscreen"
                playsinline
                webkit-playsinline
                style="width: 100%; height: 100%; border: none;"></iframe>
        </div>

        <div class="player-options" style="flex-wrap: wrap;">
            <?php
            // Reordena para EmbedTV ser a primeira opção
            $sorted_streams = [];
            $others = [];
            foreach ($dynamic_streams as $stream) {
                if (stripos($stream['name'], 'embedtv') !== false) {
                    array_unshift($sorted_streams, $stream);
                } else {
                    $others[] = $stream;
                }
            }
            $sorted_streams = array_merge($sorted_streams, $others);
            ?>
            <?php if (!empty($sorted_streams)): ?>
                <?php foreach ($sorted_streams as $index => $stream): ?>
                <button class="opt-btn <?php echo ($index === 0) ? 'active' : ''; ?>"
                                onclick="switchDynamicPlayer('<?php echo htmlspecialchars($stream['url']); ?>', this)">
                        <?php echo ($index === 0) ? 'ELITE 01' : htmlspecialchars(strtoupper($stream['name'])); ?>
                    </button>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback para canais sem metadados dinâmicos -->
                <button class="opt-btn active" onclick="switchPlayer(1, this)">OPÇÃO 1</button>
                <?php if (!empty($iframe_url_2)): ?>
                    <button class="opt-btn" onclick="switchPlayer(2, this)">OPÇÃO 2</button>
                <?php endif; ?>
                <?php if (!empty($iframe_url_3)): ?>
                    <button class="opt-btn" onclick="switchPlayer(3, this)">OPÇÃO 3</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const player1Url = "<?php echo $iframe_url; ?>";
        let player2Url = "<?php echo $iframe_url_2; ?>";
        const player3Url = "<?php echo $iframe_url_3; ?>";
        let activeCredentials = null;

        function isM3U8(url) {
            return url && url.includes('.m3u8');
        }

        function shouldUseProxy(url) {
            if (!url) return false;
            // Streams do s27 devem tocar direto (sem proxy)
            if (url.includes('s27-usa-cloudfront-net.online')) return false;
            return true;
        }

        async function initPlayer(url) {
            const iframe = document.getElementById('main-player');
            const jwDiv = document.getElementById('jw-player-container');

            // Destroi player anterior se existir
            try { jwplayer("jw-player-container").remove(); } catch(e) {}

            if (isM3U8(url)) {
                iframe.style.display = 'none';
                jwDiv.style.display = 'block';
                iframe.src = 'about:blank';

                const streamUrl = shouldUseProxy(url)
                    ? ("proxy.php?url=" + encodeURIComponent(url))
                    : url;

                jwplayer("jw-player-container").setup({
                    file: streamUrl,
                    type: "hls",
                    autostart: true,
                    width: "100%",
                    height: "100%",
                    aspectratio: "16:9",
                    stretching: "uniform"
                });

            } else {
                jwDiv.style.display = 'none';
                iframe.style.display = 'block';

                let finalUrl = url;
                if (activeCredentials) {
                    const sep = finalUrl.includes('?') ? '&' : '?';
                    finalUrl += `${sep}user=${encodeURIComponent(activeCredentials.user)}&pass=${encodeURIComponent(activeCredentials.pass)}`;
                }
                iframe.src = finalUrl;
            }
        }

        async function fetchCredentials() {
            try {
                const response = await fetch('jhghkfju38j.csv');
                if (!response.ok) return null;
                const text = await response.text();
                const lines = text.split('\n');
                for (let i = 1; i < lines.length; i++) {
                    const columns = lines[i].split(';');
                    if (columns.length >= 3) {
                        return { user: columns[1].trim(), pass: columns[2].trim() };
                    }
                }
            } catch (e) { console.error("Erro ao ler credenciais", e); }
            return null;
        }

        async function switchDynamicPlayer(url, btn) {
            document.querySelectorAll('.opt-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            initPlayer(url);
        }

        async function switchPlayer(option, btn) {
            let url = player1Url;
            if (option === 2) url = player2Url;
            if (option === 3) url = player3Url;

            document.querySelectorAll('.opt-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            initPlayer(url);
        }

        window.onload = async () => {
            activeCredentials = await fetchCredentials();

            const dynamicStreams = <?php echo json_encode($sorted_streams); ?>;
            if (dynamicStreams && dynamicStreams.length > 0) {
                // Já vem ordenado com EmbedTV primeiro
                const chosenStream = dynamicStreams[0];
                initPlayer(chosenStream.url);
                return;
            }

            initPlayer(player1Url);
        };
    </script>

</body>

    <!-- Modal de Sessão Expirada -->
    <div id="sessao-modal" style="
        display: none; position: fixed; inset: 0; z-index: 9999;
        background: rgba(5, 7, 10, 0.95);
        backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
        align-items: center; justify-content: center;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    ">
        <div style="
            background: #0f111a; border: 1px solid rgba(239,68,68,0.3);
            border-radius: 20px; padding: 40px 32px; max-width: 400px; width: 90%;
            text-align: center; box-shadow: 0 0 60px rgba(239,68,68,0.15);
        ">
            <div style="font-size: 48px; margin-bottom: 16px;">🔒</div>
            <h2 style="color:#fff; font-size:20px; margin-bottom:10px;">Sessão encerrada</h2>
            <p style="color:#94a3b8; font-size:14px; line-height:1.6; margin-bottom:28px;">
                Sua conta foi acessada em outro dispositivo ou navegador.<br>
                Por segurança, apenas um acesso simultâneo é permitido.
            </p>
            <a href="login.php" style="
                display: inline-block;
                background: linear-gradient(135deg,#3b82f6,#2563eb);
                color:#fff; padding:13px 32px; border-radius:10px;
                font-weight:700; font-size:15px; text-decoration:none;
            ">Fazer Login Novamente</a>
        </div>
    </div>

    <script>
        // ---- Heartbeat no player: verifica sessão a cada 15 segundos ----
        (function() {
            const INTERVALO = 15000; // 15s — mais agressivo pois está em reprodução
            let ativo = true;

            async function verificarSessao() {
                if (!ativo) return;
                try {
                    const res = await fetch('ping.php', { cache: 'no-store' });
                    if (!res.ok) { bloquear(); return; }
                    const data = await res.json();
                    if (!data.valid) bloquear();
                } catch (e) {
                    // Falha de rede — não bloqueia imediatamente
                }
            }

            function bloquear() {
                ativo = false;
                clearInterval(intervalo);

                // Para qualquer player ativo antes de mostrar o modal
                try {
                    const iframe = document.getElementById('main-player');
                    if (iframe) iframe.src = 'about:blank';
                    if (typeof jwplayer === 'function') {
                        try { jwplayer('jw-player-container').stop(); } catch(e) {}
                    }
                } catch(e) {}

                document.getElementById('sessao-modal').style.display = 'flex';
            }

            // Verifica ao voltar para a aba (ex: usuário voltou após usar outra aba)
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) verificarSessao();
            });

            const intervalo = setInterval(verificarSessao, INTERVALO);
            setTimeout(verificarSessao, 5000);
        })();
    </script>
</html>
