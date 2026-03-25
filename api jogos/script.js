const API_URL = 'https://corsproxy.io/?' + encodeURIComponent('https://castro.x44bet.com/api/games/today');

const elements = {
    gamesContainer: document.getElementById('games-container'),
    loading: document.getElementById('loading'),
    error: document.getElementById('error-message'),
    dateDisplay: document.getElementById('current-date')
};

// Global state to store games and compare changes
let lastGamesData = [];

// 1. Scraper with cache busters
async function fetchLiveScores() {
    const PROXY = 'https://corsproxy.io/?';
    // Use em-andamento for faster live updates + cache buster
    const TARGET = 'https://www.placardefutebol.com.br/jogos-de-hoje?t=' + Date.now();
    
    try {
        const response = await fetch(PROXY + encodeURIComponent(TARGET));
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        const matches = Array.from(doc.querySelectorAll('a[href*="/campeonato-"]'))
            .filter(match => match.querySelector('.status-name'));
        
        return matches.map(match => {
            const homeTeam = match.querySelector('h5.text-right.team_link')?.innerText?.trim() || 
                             match.querySelector('h5.text-right')?.innerText?.trim();
            const awayTeam = match.querySelector('h5.text-left.team_link')?.innerText?.trim() ||
                             match.querySelector('h5.text-left')?.innerText?.trim();
            
            const scoreElements = match.querySelectorAll('.match-score .badge');
            const homeScore = scoreElements[0]?.innerText?.trim() || '0';
            const awayScore = scoreElements[1]?.innerText?.trim() || '0';
            
            // Extract the actual time (e.g. 45') from the status element or its children
            const statusElement = match.querySelector('.status-name');
            const statusText = statusElement?.innerText?.trim() || '';
            const link = 'https://www.placardefutebol.com.br' + match.getAttribute('href');

            return { homeTeam, awayTeam, homeScore, awayScore, statusText, link };
        });
    } catch (error) {
        console.error('Erro no scraping:', error);
        return [];
    }
}

function slugify(text) {
    if (!text) return '';
    return text
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\bfc\b|\bcf\b|\bde munique\b|\bunited\b|\batletico\b|\batlético\b/g, '')
        .replace(/[^a-z0-9]/g, '')
        .trim();
}

function matchGameScores(apiGames, scrapedScores) {
    return apiGames.map(game => {
        const homeSlug = slugify(game.home);
        const awaySlug = slugify(game.away);
        
        let match = scrapedScores.find(ls => {
            const lsHomeSlug = slugify(ls.homeTeam);
            const lsAwaySlug = slugify(ls.awayTeam);
            return (lsHomeSlug.includes(homeSlug) || homeSlug.includes(lsHomeSlug)) &&
                   (lsAwaySlug.includes(awaySlug) || awaySlug.includes(lsAwaySlug));
        });

        if (!match) {
            match = scrapedScores.find(ls => {
                const lsHomeSlug = slugify(ls.homeTeam);
                const lsAwaySlug = slugify(ls.awayTeam);
                const homeMatch = homeSlug.length > 3 && (lsHomeSlug.includes(homeSlug) || homeSlug.includes(lsHomeSlug));
                const awayMatch = awaySlug.length > 3 && (lsAwaySlug.includes(awaySlug) || awaySlug.includes(lsAwaySlug));
                return homeMatch || awayMatch;
            });
        }
        
        if (match) {
            const statusText = match.statusText.toLowerCase();
            const isFinished = statusText.includes('fin') || statusText.includes('fim') || statusText.includes('enc');
            const isLive = match.statusText.includes("'") || statusText.includes('int');

            return {
                ...game,
                homeScore: match.homeScore,
                awayScore: match.awayScore,
                statusText: match.statusText,
                status: isLive ? 'Ao Vivo' : (isFinished ? 'Encerrado' : game.status),
                externalLink: match.link
            };
        }
        return game;
    });
}

async function fetchGames() {
    try {
        const targetWithCache = 'https://castro.x44bet.com/api/games/today' + '?t=' + Date.now();
        const proxiedUrl = 'https://corsproxy.io/?' + encodeURIComponent(targetWithCache);
        
        const response = await fetch(proxiedUrl);
        if (!response.ok) throw new Error('Falha API');
        let games = await response.json();
        
        const scrapedScores = await fetchLiveScores();
        if (scrapedScores.length > 0) {
            games = matchGameScores(games, scrapedScores);
        }
        
        // Deep comparison to avoid unnecessary re-renders
        const dataChanged = JSON.stringify(games) !== JSON.stringify(lastGamesData);
        if (dataChanged || lastGamesData.length === 0) {
            renderGames(games);
            lastGamesData = games;
        }
    } catch (error) {
        console.error('Erro ao carregar:', error);
        // Only show error if we have no data at all
        if (lastGamesData.length === 0) {
            elements.loading.classList.add('hidden');
            elements.error.classList.remove('hidden');
        }
    }
}

function formatTime(timestamp) {
    if (!timestamp) return '--:--';
    const date = new Date(timestamp * 1000);
    return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

function createGameCard(game) {
    const homeInitials = getInitials(game.home);
    const awayInitials = getInitials(game.away);
    const homeLogo = getTeamLogoUrl(game.home);
    const awayLogo = getTeamLogoUrl(game.away);
    
    const isLive = game.status === 'Ao Vivo';
    const isFinished = game.status === 'Encerrado' || game.status === 'Finalizado';
    
    const statusLabel = (isLive && game.statusText) ? game.statusText : (isFinished ? 'Encerrado' : game.status);
    const homeScore = game.homeScore !== undefined ? game.homeScore : (isLive || isFinished ? '0' : '');
    const awayScore = game.awayScore !== undefined ? game.awayScore : (isLive || isFinished ? '0' : '');
    const watchLink = game.externalLink || 'https://www.placardefutebol.com.br';

    // Banner logic for competition
    const competitionSlug = game.competition.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const isBrasileirao = competitionSlug.includes('brasileiro') || 
                          competitionSlug.includes('brasileirao') ||
                          competitionSlug.includes('serie a');
    const bannerStyle = isBrasileirao ? `style="background-image: url('brasileiro.webp');"` : '';

    return `
        <div class="game-card" data-id="${game.home}-${game.away}">
            <div class="card-banner" ${bannerStyle}>
                <div class="banner-overlay"></div>
                <span class="banner-title">${game.competition}</span>
            </div>
            
            <div class="card-content">
                <div class="card-header">
                    <span class="status-badge ${isLive ? 'live' : (isFinished ? 'finished' : '')}">${statusLabel}</span>
                    <span class="kickoff-time">${isLive ? '<span class="live-indicator">• AO VIVO</span>' : formatTime(game.kickoff)}</span>
                </div>
                
                <div class="teams-container">
                    <div class="team">
                        <div class="team-logo">
                            <img src="${homeLogo}" alt="${game.home}" onerror="handleImageError(this, '${homeInitials}')">
                        </div>
                        <span class="team-name">${game.home}</span>
                    </div>

                    ${(isLive || isFinished) ? `
                        <div class="score-container" ${isFinished ? 'style="border-color: rgba(245, 158, 11, 0.4)"' : ''}>
                            <div class="score-display">
                                <span>${homeScore}</span>
                                <span class="score-divider">-</span>
                                <span>${awayScore}</span>
                            </div>
                            <span class="score-label">${isFinished ? 'Final' : 'Placar'}</span>
                        </div>
                    ` : `
                        <div class="score-container" style="opacity: 0.3; border: none; background: transparent;">
                            <div class="score-display" style="font-size: 1rem;">VS</div>
                        </div>
                    `}

                    <div class="team">
                        <div class="team-logo">
                            <img src="${awayLogo}" alt="${game.away}" onerror="handleImageError(this, '${awayInitials}')">
                        </div>
                        <span class="team-name">${game.away}</span>
                    </div>
                </div>
                
                <div class="card-footer">
                    <a href="${watchLink}" target="_blank" class="watch-button">Assistir Agora</a>
                </div>
            </div>
        </div>
    `;
}

function renderGames(games) {
    elements.loading.classList.add('hidden');
    if (games.length === 0) {
        elements.gamesContainer.innerHTML = '<p class="no-games">Nenhum jogo encontrado na API para hoje.</p>';
        return;
    }

    const liveGames = games.filter(g => g.status === 'Ao Vivo');
    const finishedGames = games.filter(g => g.status === 'Encerrado' || g.status === 'Finalizado');
    const upcomingGames = games.filter(g => !liveGames.includes(g) && !finishedGames.includes(g));

    let html = '';
    if (liveGames.length > 0) html += renderSection('Ao Vivo Agora', liveGames);
    if (upcomingGames.length > 0) html += renderSection('Próximos Jogos', upcomingGames);
    if (finishedGames.length > 0) html += renderSection('Jogos Encerrados', finishedGames);

    // Save scroll position
    const scrollPos = window.scrollY;
    elements.gamesContainer.innerHTML = html;
    window.scrollTo(0, scrollPos);
}

function renderSection(title, list) {
    return `
        <div class="section-group">
            <div class="section-header">
                <h2>${title}</h2>
                <span class="count-badge">${list.length}</span>
            </div>
            <div class="games-grid">
                ${list.map(game => createGameCard(game)).join('')}
            </div>
        </div>
    `;
}

function updateDate() {
    const now = new Date();
    const options = { day: 'numeric', month: 'long', year: 'numeric' };
    elements.dateDisplay.textContent = now.toLocaleDateString('pt-BR', options);
}

function getInitials(name) {
    if (!name) return '??';
    return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
}

function getTeamLogoUrl(name) {
    if (!name) return '';
    const slug = name.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9\s-]/g, '').replace(/[\s.]+/g, '-').replace(/-+/g, '-').trim();
    return `https://d1muf25xaso8hp.cloudfront.net/https://futemax.today/assets/uploads/teams/${slug}.webp`;
}

function handleImageError(img, initials) {
    const parent = img.parentElement;
    if (parent) parent.innerHTML = initials;
}

function startPolling() {
    // Poll every 15 seconds for near real-time updates
    setInterval(() => fetchGames(), 15000);
}

// Init
updateDate();
fetchGames();
startPolling();
