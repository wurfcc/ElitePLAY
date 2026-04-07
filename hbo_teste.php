<?php
$m3uPath = __DIR__ . '/LISTA M3U TESTE/HBO.m3u';
$channels = [];
$readError = '';

if (!is_file($m3uPath)) {
    $readError = 'Arquivo HBO.m3u nao encontrado no caminho esperado.';
} elseif (!is_readable($m3uPath)) {
    $readError = 'Sem permissao de leitura para HBO.m3u (ou para a pasta LISTA M3U TESTE).';
} else {
    $lines = @file($m3uPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        $readError = 'Falha ao ler o arquivo HBO.m3u.';
    } else {
        $lastMeta = null;
        foreach ($lines as $rawLine) {
            $line = trim((string)$rawLine);
            if ($line === '') {
                continue;
            }

            if (stripos($line, '#EXTINF:') === 0) {
                $attrs = [];
                if (preg_match_all('/([a-zA-Z0-9_-]+)="([^"]*)"/', $line, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $m) {
                        $attrs[strtolower($m[1])] = trim($m[2]);
                    }
                }

                $name = '';
                $commaPos = strrpos($line, ',');
                if ($commaPos !== false) {
                    $name = trim(substr($line, $commaPos + 1));
                }

                $lastMeta = [
                    'name' => $name,
                    'logo' => (string)($attrs['tvg-logo'] ?? ''),
                ];
                continue;
            }

            if ($line[0] === '#') {
                continue;
            }

            if (!preg_match('/^https?:\/\//i', $line)) {
                continue;
            }

            if (stripos($line, 'cdn-') === false) {
                continue;
            }

            $channels[] = [
                'name' => ($lastMeta['name'] ?? '') !== '' ? $lastMeta['name'] : ('Canal ' . (count($channels) + 1)),
                'logo' => (string)($lastMeta['logo'] ?? ''),
                'url' => $line,
            ];

            $lastMeta = null;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HBO M3U Teste - ElitePLAY</title>
    <script src="https://ssl.p.jwpcdn.com/player/v/8.6.3/jwplayer.js"></script>
    <script>jwplayer.key = "64HPbvSQorQcd52B8XFuhMtEoitbvY/EXJmMBfKcXZQU2Rnn";</script>
    <script src="https://cdn.jsdelivr.net/npm/mpegts.js@latest/dist/mpegts.min.js"></script>
    <style>
        :root {
            --bg: #05070a;
            --card: #111827;
            --line: rgba(148, 163, 184, 0.28);
            --text: #f8fafc;
            --muted: #94a3b8;
            --accent: #3b82f6;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Outfit, sans-serif; }
        body { background: var(--bg); color: var(--text); }
        .page { width: min(1200px, 100%); margin: 0 auto; padding: 20px 14px 30px; }
        h1 { font-size: 30px; margin-bottom: 6px; }
        .sub { color: var(--muted); margin-bottom: 14px; }
        .layout { display: grid; grid-template-columns: 1.2fr 1fr; gap: 14px; }
        .panel { border: 1px solid var(--line); border-radius: 14px; background: #0b1220; }
        .player-wrap { padding: 12px; }
        #jw-test-player,
        #ts-test-player { width: 100%; aspect-ratio: 16/9; border-radius: 10px; overflow: hidden; background: #000; }
        #ts-test-player { display: none; }
        .now-playing { margin-top: 10px; color: #dbeafe; font-size: 14px; }
        .list-wrap { max-height: 78vh; overflow: auto; padding: 10px; }
        .channel-btn {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--card);
            color: var(--text);
            text-align: left;
            padding: 10px;
            margin-bottom: 8px;
            display: grid;
            grid-template-columns: 42px 1fr;
            gap: 10px;
            align-items: center;
            cursor: pointer;
        }
        .channel-btn:hover { border-color: var(--accent); }
        .channel-btn.active { border-color: var(--accent); box-shadow: 0 0 0 2px rgba(59,130,246,.25) inset; }
        .logo { width: 40px; height: 40px; border-radius: 8px; object-fit: contain; background: #0f172a; }
        .name { font-weight: 700; font-size: 14px; }
        .url { color: var(--muted); font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .empty { color: var(--muted); padding: 16px; }
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .list-wrap { max-height: unset; }
        }
    </style>
</head>
<body>
    <main class="page">
        <h1>HBO M3U - Página de Teste</h1>
        <p class="sub">Clique em qualquer canal para abrir o player e reproduzir a stream.</p>

        <div class="layout">
            <section class="panel player-wrap">
                <div id="jw-test-player"></div>
                <video id="ts-test-player" controls autoplay playsinline></video>
                <p class="now-playing" id="now-playing">Nenhum canal selecionado.</p>
            </section>

            <aside class="panel list-wrap">
                <?php if (empty($channels)): ?>
                    <p class="empty">
                        Nenhum canal encontrado em <code>LISTA M3U TESTE/HBO.m3u</code>.
                        <?php if ($readError !== ''): ?><br><?php echo htmlspecialchars($readError, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                    </p>
                <?php else: ?>
                    <?php foreach ($channels as $index => $channel): ?>
                        <button
                            type="button"
                            class="channel-btn"
                            data-url="<?php echo htmlspecialchars($channel['url'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-name="<?php echo htmlspecialchars($channel['name'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <img class="logo" src="<?php echo htmlspecialchars($channel['logo'] ?: 'imagens/elitelogo.webp', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($channel['name'], ENT_QUOTES, 'UTF-8'); ?>" onerror="this.src='imagens/elitelogo.webp'">
                            <div>
                                <div class="name"><?php echo htmlspecialchars($channel['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="url"><?php echo htmlspecialchars($channel['url'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </aside>
        </div>
    </main>

    <script>
        (function () {
            const buttons = Array.from(document.querySelectorAll('.channel-btn'));
            const nowPlaying = document.getElementById('now-playing');
            const tsVideo = document.getElementById('ts-test-player');
            const jwContainer = document.getElementById('jw-test-player');
            let tsPlayer = null;

            function resetPlayers() {
                try { jwplayer('jw-test-player').remove(); } catch (e) {}

                if (tsPlayer) {
                    try { tsPlayer.destroy(); } catch (e) {}
                    tsPlayer = null;
                }

                if (tsVideo) {
                    tsVideo.pause();
                    tsVideo.removeAttribute('src');
                    tsVideo.load();
                    tsVideo.style.display = 'none';
                }

                if (jwContainer) {
                    jwContainer.style.display = 'block';
                }
            }

            function playStream(url, name) {
                if (!url) return;

                resetPlayers();

                const lowerUrl = String(url).toLowerCase();
                const isHls = lowerUrl.includes('.m3u8');

                if (isHls) {
                    jwplayer('jw-test-player').setup({
                        file: url,
                        type: 'hls',
                        width: '100%',
                        aspectratio: '16:9',
                        autostart: true,
                        mute: false,
                        primary: 'html5'
                    });
                } else {
                    if (jwContainer) {
                        jwContainer.style.display = 'none';
                    }
                    if (tsVideo) {
                        tsVideo.style.display = 'block';
                    }

                    if (window.mpegts && window.mpegts.getFeatureList && window.mpegts.getFeatureList().mseLivePlayback) {
                        tsPlayer = window.mpegts.createPlayer({
                            type: 'mse',
                            isLive: true,
                            url: url,
                        });
                        tsPlayer.attachMediaElement(tsVideo);
                        tsPlayer.load();
                        tsPlayer.play();
                    } else if (tsVideo) {
                        tsVideo.src = url;
                        tsVideo.play().catch(() => {});
                    }
                }

                if (nowPlaying) {
                    nowPlaying.textContent = 'Reproduzindo: ' + name;
                }
            }

            buttons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    buttons.forEach((b) => b.classList.remove('active'));
                    btn.classList.add('active');
                    playStream(btn.dataset.url || '', btn.dataset.name || 'Canal');
                });
            });

            if (buttons[0]) {
                buttons[0].click();
            }
        })();
    </script>
</body>
</html>
