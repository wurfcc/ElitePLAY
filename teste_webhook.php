<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook Receiver</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #000;
            color: #0f0;
            min-height: 100vh;
            padding: 20px;
        }
        #output {
            white-space: pre-wrap;
            font-size: 14px;
            line-height: 1.5;
        }
        .waiting { color: #333; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #ff0; }
    </style>
</head>
<body>
    <div id="output" class="waiting">Aguardando webhook...</div>

    <script>
        let lastLog = '';

        async function checkWebhook() {
            try {
                const res = await fetch('webhook_log.txt');
                const text = await res.text();

                if (text.trim() === '') {
                    document.getElementById('output').innerHTML = '<span class="waiting">Aguardando webhook...</span>';
                    return;
                }

                const lines = text.trim().split('\n');
                const lastLine = lines[lines.length - 1];

                if (lastLine !== lastLog) {
                    lastLog = lastLine;
                    document.getElementById('output').innerHTML = '<span class="info">' + lastLine + '</span>';
                }
            } catch (err) {
                document.getElementById('output').innerHTML = '<span class="error">Erro: ' + err.message + '</span>';
            }
        }

        // Verifica a cada 1 segundo
        setInterval(checkWebhook, 1000);
        checkWebhook();
    </script>
</body>
</html>
