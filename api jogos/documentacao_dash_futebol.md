# Documentação: Dashboard de Futebol Elite

Este documento descreve toda a lógica, arquitetura e Estilização do dashboard de jogos de hoje, desenvolvido para ser consumido via API e enriquecido com dados em tempo real via Web Scraping.

---

## 1. Fluxo de Dados e APIs

O projeto utiliza uma arquitetura híbrida:
- **Fonte Primária (JSON API)**: `https://castro.x44bet.com/api/games/today`
  - Define quais jogos aparecem na lista oficial.
- **Fonte Secundária (Web Scraping)**: `https://www.placardefutebol.com.br/jogos-de-hoje`
  - Utilizada para atualizar placares e a minutagem (tempo) dos jogos dinamicamente.
- **Proxy de CORS**: `https://corsproxy.io/?`
  - Necessário para contornar restrições de segurança do navegador ao fazer requisições de um domínio para outro.

---

## 2. Scraping Inteligente e Cache Buster

Para garantir que os dados estejam sempre atualizados e não fiquem presos no cache do navegador ou do proxy, implementamos um **Cache Buster**:
- Toda requisição adiciona um parâmetro temporal: `?t={Date.now()}`.

### Ciclo de Atualização:
- O dashboard realiza o polling (busca automática) a cada **15 segundos**.
- **Comparação Profunda**: O sistema compara os dados novos com os antigos em memória. Se nada mudou, ele não re-renderiza o DOM, evitando "flickers" visuais.

---

## 3. Lógica de Matching (Inteligência Artificial de Nomes)

Um dos maiores desafios é o nome dos times variar entre a API e o site de placares (ex: *Bayern de Munique* vs *Bayern München*). Criamos uma função de slugificação e um sistema de prioridades:

1. **Slugificação**: Remove acentos, caracteres especiais e termos comuns (FC, CF, United, etc).
2. **Prioridade 1**: Tenta encontrar o jogo que case exatamente os dois times (Home slug + Away slug).
3. **Prioridade 2 (Fallback Inteligente)**: Se não achar os dois, tenta casar apenas o **Mandante** ou apenas o **Visitante**. Isso garante o placar mesmo com nomes traduzidos.

---

## 4. Identidade Visual dos Times (Logos Dinâmicos)

As imagens dos times são carregadas dinamicamente usando uma estrutura baseada no Cloudfront da Futemax:
- **URL Base**: `https://d1muf25xaso8hp.cloudfront.net/https://futemax.today/assets/uploads/teams/{slug}.webp`
- **Fallback**: Caso a imagem não exista (erro 404), o sistema captura o erro e substitui a imagem pelas **Iniciais do Time** (ex: Corinthians -> CO) dentro de um círculo estilizado.

---

## 5. Arquitetura do Layout (Grid & UI)

### Estrutura de Camadas:
O dashboard é organizado verticalmente em categorias:
1. **Ao Vivo Agora**: Destaque total 100% de largura.
2. **Próximos Jogos**: Lista de futuros confrontos.
3. **Jogos Encerrados**: Resultados finais.

### Grid Responsivo:
Dentro de cada categoria, os cards são organizados em um **grid de 3 colunas** que se ajusta automaticamente ao tamanho da tela (usando `repeat(auto-fill, minmax(340px, 1fr))`).

---

## 6. Destaques da Estilização (CSS Premium)

### Badges de Status:
- **Ao Vivo**: Texto verde com um indicador `• AO VIVO` **vermelho pulsante** (animação de glow).
- **Minutagem**: Sempre destacada em **Verde Esmeralda** (`#4ade80`).
- **Encerrado**: Badge **Laranja** (`#f59e0b`) elegante.

### Card Interactive:
- **Banner de Campeonato**: Uma barra de 40px no topo com imagem `brasileiro.webp` (se for Brasileirão) e título alinhado à esquerda.
- **Botão de Ação**: Botão "Assistir Agora" com efeito hover e sombra dinâmica, pronto para conversão.

---

## 7. Funções Principais (Snippets para reuso)

### Slugify
```javascript
function slugify(text) {
    if (!text) return '';
    return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]/g, '').trim();
}
```

### Match de Placares
```javascript
function matchGameScores(apiGames, scrapedScores) {
    // Lógica de prioridade 1 (ambos) e prioridade 2 (um deles)
}
```

### Restauração de Scroll
Para evitar que a página "pule" durante o polling:
```javascript
const scrollPos = window.scrollY;
elements.gamesContainer.innerHTML = html;
window.scrollTo(0, scrollPos);
```

---

*Dashboard desenvolvido com foco em performance, escalabilidade e design premium.* ⚽🔥
