# Guia de Integração: Nova API e Player JWPlayer (CORS Proxy)

Este documento descreve os passos precisos para migrar seu projeto da API antiga para a nova API de canais, utilizando o JWPlayer com o sistema de proxy local para garantir o funcionamento em `localhost` e resolver problemas de CORS/Referer.

---

## 1. ⚠️ Remover API Antiga
A primeira etapa é localizar e remover todas as chamadas para a API antiga:
**URL a remover:** `https://cinetvembed.bond/api.php`

---

## 2. 📡 Consumo da Nova API (Catalog)
A nova API utiliza `/api/catalog.php` e suporta paginação.

**Link da Nova API (Canais):**
`https://cinetvembed.bond/api/catalog.php?username=Eliteplay-vods&password=Q0wBhO&type=canais`

### Parâmetros Importantes:
- `username`: Eliteplay-vods
- `password`: Q0wBhO
- `type`: canais (também suporta `movies`, `series`, `animes`)
- `page`: Número da página (ex: `&page=1`)

### Lógica de Consumo Sugerida:
O ideal é buscar a primeira página para saber o total de páginas (`pagination.total_pages`) e então buscar as restantes em paralelo.

---

## 3. 🛠 Implementação do Proxy (CORS Bypass)
Crie um arquivo chamado `proxy.php` na raiz do seu novo projeto para contornar bloqueios de domínio:

```php
<?php
// proxy.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/vnd.apple.mpegurl");

if (isset($_GET['url'])) {
    $url = $_GET['url'];
    $options = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n" .
                        "Referer: https://cinetvembed.bond/\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $content = @file_get_contents($url, false, $context);
    
    if ($content) {
        $baseUrl = substr($url, 0, strrpos($url, '/') + 1);
        $lines = explode("\n", $content);
        foreach ($lines as &$line) {
            $line = trim($line);
            if (!empty($line) && strpos($line, '#') !== 0 && strpos($line, 'http') !== 0) {
                $line = $baseUrl . $line;
            }
        }
        echo implode("\n", $lines);
    } else {
        http_response_code(404);
        echo "#EXTM3U\n# Erro ao carregar o stream.";
    }
}
?>
```

---

## 4. 🎬 Configuração do Player (`assistir.php`)
O seu arquivo de reprodução deve utilizar o **JWPlayer** e apontar para o proxy:

### Scripts Necessários:
```html
<script src="https://ssl.p.jwpcdn.com/player/v/8.6.3/jwplayer.js"></script>
<script>jwplayer.key="64HPbvSQorQcd52B8XFuhMtEoitbvY/EXJmMBfKcXZQU2Rnn";</script>
```

### Inicialização do Player:
```javascript
const originalUrl = "URL_DO_STREAM_M3U8"; // Recebida via POST (field: stream_url)
const proxyUrl = "proxy.php?url=" + encodeURIComponent(originalUrl);

jwplayer("id-do-container").setup({
    file: proxyUrl,
    type: "hls",
    autostart: true,
    width: "100%",
    aspectratio: "16:9"
});
```

---

## 5. 🔗 Conectando o `index.php` ao Player
Ao renderizar seus canais no `index.php`, certifique-se de pegar o campo `stream_url` da API e enviá-lo via POST para o seu `assistir.php`.

**O campo na API agora é:** `channel.stream_url` (contém o link direto arquivo .m3u8).

---

## 📝 Resumo Técnica
1. Busque os canais em `api/catalog.php`.
2. Pegue o campo `stream_url`.
3. Envie para o player.
4. No player, use `proxy.php?url=` antes do link para liberar a reprodução em qualquer domínio.

ElitePLAY Premium - Guia de Integração 2026.
