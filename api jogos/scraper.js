async function scrapePlacarDeFutebol() {
    const PROXY = 'https://corsproxy.io/?';
    const TARGET = 'https://www.placardefutebol.com.br/jogos-em-andamento';
    
    try {
        const response = await fetch(PROXY + encodeURIComponent(TARGET));
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        const matches = Array.from(doc.querySelectorAll('a[href*="/campeonato-"]'));
        
        return matches.map(match => {
            const homeScore = match.querySelectorAll('.badge-default')[0]?.innerText.trim() || '0';
            const awayScore = match.querySelectorAll('.badge-default')[1]?.innerText.trim() || '0';
            const homeTeam = match.querySelector('h5.text-right')?.innerText.trim();
            const awayTeam = match.querySelector('h5.text-left')?.innerText.trim();
            const status = match.querySelector('.status-name')?.innerText.trim();
            
            return {
                title: `${homeTeam} X ${awayTeam}`,
                homeTeam,
                awayTeam,
                homeScore,
                awayScore,
                status
            };
        });
    } catch (error) {
        console.error('Erro ao fazer scraping:', error);
        return [];
    }
}

// Test function
scrapePlacarDeFutebol().then(data => console.log('Scraped Data:', data));
