<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Webhook - ElitePLAY</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a1a2e; 
            color: #fff; 
            padding: 20px;
            min-height: 100vh;
        }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #00ff88; margin-bottom: 20px; }
        .card {
            background: #16213e;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        h2 { color: #e94560; margin-bottom: 15px; font-size: 1.1rem; }
        pre {
            background: #0f3460;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.9rem;
            max-height: 300px;
            overflow-y: auto;
        }
        .btn {
            background: #00ff88;
            color: #1a1a2e;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
        }
        .btn:hover { background: #00cc6a; }
        .btn-test {
            background: #e94560;
            color: #fff;
        }
        .btn-test:hover { background: #c73e54; }
        input, textarea {
            width: 100%;
            background: #0f3460;
            border: 1px solid #333;
            color: #fff;
            padding: 12px;
            border-radius: 8px;
            font-family: monospace;
            margin-bottom: 10px;
        }
        textarea { min-height: 150px; }
        .log-entry {
            background: #0f3460;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 6px;
            font-size: 0.85rem;
            border-left: 3px solid #00ff88;
        }
        .log-time { color: #888; font-size: 0.75rem; }
        .empty { color: #666; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔗 Teste Webhook - ElitePLAY</h1>
        
        <div class="card">
            <h2>📡 Enviar Payload para Webhook</h2>
            <form id="webhookForm">
                <textarea id="payloadInput" placeholder='Cole o payload JSON aqui...' required>{
    "event": "payment.approved",
    "data": {
        "customer": {
            "email": "cliente@exemplo.com"
        },
        "order": {
            "id": "123456",
            "total": 1000
        }
    }
}</textarea>
                <button type="submit" class="btn">Enviar para Webhook</button>
            </form>
            <div id="response" style="margin-top: 15px; display: none;"></div>
        </div>

        <div class="card">
            <h2>📋 Últimos Logs Recebidos</h2>
            <button onclick="loadLogs()" class="btn btn-test" style="margin-bottom: 15px;">🔄 Atualizar Logs</button>
            <div id="logsContainer">
                <p class="empty">Nenhum log ainda. Envie um payload acima.</p>
            </div>
        </div>

        <div class="card">
            <h2>🎯 Payload Simulado (Recomendado para Teste)</h2>
            <pre id="samplePayload">// Aguardando Payload do Lowify...
// Cole aqui o payload que o Lowify envia</pre>
            <button onclick="useSample()" class="btn" style="margin-top: 10px;">Usar como Payload</button>
        </div>

        <div class="card">
            <h2>⚠️ Configuração Lowify</h2>
            <p style="color: #888; font-size: 0.9rem;">
                No painel Lowify, configure o Webhook URL para:<br>
                <code style="color: #00ff88; font-size: 1rem;">
                    https://SEU_NGROK_URL/eliteplay/webhook.php
                </code>
            </p>
        </div>
    </div>

    <script>
        document.getElementById('webhookForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = document.getElementById('payloadInput').value;
            const responseDiv = document.getElementById('response');
            
            try {
                const res = await fetch('webhook.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: payload
                });
                
                const text = await res.text();
                responseDiv.style.display = 'block';
                responseDiv.style.color = res.ok ? '#00ff88' : '#e94560';
                responseDiv.innerHTML = `<strong>Status ${res.status}:</strong> ${text}`;
                
                loadLogs();
            } catch (err) {
                responseDiv.style.display = 'block';
                responseDiv.style.color = '#e94560';
                responseDiv.innerHTML = `<strong>Erro:</strong> ${err.message}`;
            }
        });

        async function loadLogs() {
            try {
                const res = await fetch('webhook_log.txt');
                const text = await res.text();
                const logsContainer = document.getElementById('logsContainer');
                
                if (text.trim() === '') {
                    logsContainer.innerHTML = '<p class="empty">Nenhum log ainda.</p>';
                    return;
                }
                
                const lines = text.trim().split('\n').reverse().slice(0, 20);
                logsContainer.innerHTML = lines.map(line => {
                    const [dateTime, ...rest] = line.split(' | ');
                    return `<div class="log-entry"><span class="log-time">${dateTime}</span><br>${rest.join(' | ')}</div>`;
                }).join('');
            } catch (err) {
                console.error('Erro ao carregar logs:', err);
            }
        }

        function useSample() {
            const sample = {
                event: "payment.approved",
                data: {
                    customer: { email: "cliente@exemplo.com" },
                    order: { id: "123456", total: 1000 }
                }
            };
            document.getElementById('payloadInput').value = JSON.stringify(sample, null, 2);
        }

        loadLogs();
    </script>
</body>
</html>
