<?php
$plans = [
    [
        'id' => 'mensal',
        'title' => 'Mensal',
        'screens' => '1 Tela',
        'price' => 'R$20,00',
        'checkout' => 'https://checkout.exemplo.com/iptv-mensal',
        'items' => [
            'Todos os canais liberados',
            'Canais de filmes e series',
            'Canais de esportes e eventos ao vivo',
            'NBA Pass incluso',
            'Assista no Smartphone/Tablet',
            'Assista na Smart TV e TV Box',
        ],
    ],
    [
        'id' => 'semestral',
        'title' => 'Semestral',
        'screens' => '1 Tela',
        'price' => 'R$55,00',
        'checkout' => 'https://checkout.exemplo.com/iptv-semestral',
        'items' => [
            'Todos os canais liberados',
            'Canais de filmes e series',
            'Canais de esportes e eventos ao vivo',
            'NBA Pass incluso',
            'Assista no Smartphone/Tablet',
            'Assista na Smart TV e TV Box',
        ],
    ],
    [
        'id' => 'anual',
        'title' => 'Anual',
        'screens' => '1 Tela',
        'price' => 'R$120,00',
        'checkout' => 'https://checkout.exemplo.com/iptv-anual',
        'items' => [
            'Todos os canais liberados',
            'Canais de filmes e series',
            'Canais de esportes e eventos ao vivo',
            'NBA Pass incluso',
            'Assista no Smartphone/Tablet',
            'Assista na Smart TV e TV Box',
        ],
    ],
];

$differentials = [
    ['title' => 'Protecao', 'desc' => 'Estrutura monitorada para manter os canais ativos e estaveis.'],
    ['title' => 'Diversas Formas de Pagamento', 'desc' => 'Pagamento simples para ativar seu acesso rapidamente.'],
    ['title' => 'Suporte Tecnico', 'desc' => 'Equipe pronta para te atender quando precisar.'],
    ['title' => 'Servidores de Qualidade', 'desc' => 'Transmissao fluida com infraestrutura robusta.'],
    ['title' => 'Assista em Qualquer Lugar', 'desc' => 'Smart TV, TV Box, celular, tablet e navegador.'],
    ['title' => 'Preco Justo', 'desc' => 'Planos sem fidelidade, com custo acessivel.'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos IPTV - ElitePLAY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0d16;
            --panel: #111625;
            --panel-soft: #171f34;
            --text: #f8fafc;
            --muted: #a3b0c7;
            --line: rgba(148, 163, 184, 0.22);
            --red: #3b82f6;
            --red-soft: #2563eb;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            color: var(--text);
            background: linear-gradient(180deg, #090c14 0%, #0a0d16 100%);
        }

        .container {
            width: min(1120px, 100%);
            margin: 0 auto;
            padding: 0 16px;
        }

        .topbar {
            padding: 22px 0;
        }

        .topbar-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .brand {
            font-size: 34px;
            font-weight: 900;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .brand span {
            color: var(--red);
        }

        .nav-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .nav-link {
            color: var(--text);
            text-decoration: none;
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .client-btn {
            color: #fff;
            text-decoration: none;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--red), var(--red-soft));
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            align-items: center;
            padding: 18px 0 42px;
        }

        .hero-kicker {
            text-transform: uppercase;
            font-size: 22px;
            line-height: 1.1;
            font-weight: 800;
            margin-bottom: 10px;
            max-width: 460px;
        }

        .hero-title {
            color: var(--red);
            text-transform: uppercase;
            font-size: clamp(42px, 6vw, 72px);
            font-weight: 900;
            line-height: 0.95;
            max-width: 560px;
            margin-bottom: 14px;
        }

        .hero-desc {
            color: #d4dcec;
            font-size: 18px;
            max-width: 460px;
            margin-bottom: 18px;
        }

        .cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--red), var(--red-soft));
            color: #fff;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 800;
            padding: 11px 20px;
        }

        .hero-video {
            border-radius: 22px;
            border: 1px solid rgba(59, 130, 246, 0.4);
            background: radial-gradient(circle at 30% 30%, rgba(59, 130, 246, 0.45), rgba(18, 44, 104, 0.92));
            padding: 14px;
            box-shadow: 0 16px 42px rgba(0, 0, 0, 0.45);
        }

        .video-inner {
            border-radius: 16px;
            background: #090b12;
            border: 1px solid rgba(148, 163, 184, 0.2);
            min-height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }

        .video-inner p {
            text-transform: uppercase;
            font-weight: 800;
            color: #ffd3de;
            font-size: 22px;
            line-height: 1.2;
        }

        .stripe {
            background: #0d111d;
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            padding: 44px 0;
        }

        .assista {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            align-items: center;
        }

        .phone-visual {
            border-radius: 20px;
            background: radial-gradient(circle at 50% 45%, rgba(59, 130, 246, 0.75) 0%, rgba(59, 130, 246, 0) 52%), #0f1527;
            border: 1px solid var(--line);
            min-height: 420px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .phone {
            width: 210px;
            height: 390px;
            border-radius: 26px;
            border: 2px solid rgba(255, 255, 255, 0.16);
            background: linear-gradient(160deg, #090d18, #1a2237);
            padding: 14px;
            display: grid;
            grid-template-rows: 24px repeat(6, 1fr);
            gap: 8px;
            box-shadow: 0 20px 34px rgba(0, 0, 0, 0.45);
        }

        .phone-bar,
        .phone-card {
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.07);
        }

        .assista h2,
        .section-title,
        .plan-title {
            text-transform: uppercase;
            font-weight: 900;
        }

        .assista h2 {
            font-size: clamp(34px, 4.5vw, 56px);
            margin-bottom: 8px;
            line-height: 1;
        }

        .assista p {
            color: #d4dcec;
            margin-bottom: 15px;
            font-size: 16px;
            line-height: 1.45;
        }

        .bullet-list {
            display: grid;
            gap: 12px;
        }

        .bullet-item {
            display: grid;
            grid-template-columns: 36px 1fr;
            gap: 10px;
            align-items: start;
        }

        .icon-dot {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--red), var(--red-soft));
            color: #fff;
            font-size: 16px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bullet-item strong {
            color: var(--red-soft);
            display: block;
            font-size: 18px;
            line-height: 1.15;
        }

        .bullet-item span {
            color: #d4dcec;
            font-size: 14px;
        }

        .section {
            padding: 52px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 18px;
        }

        .section-header .kicker {
            text-transform: uppercase;
            color: #dde5f5;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.8px;
        }

        .section-header h3 {
            font-size: clamp(48px, 6vw, 74px);
            line-height: 0.95;
            font-weight: 900;
            text-transform: uppercase;
        }

        .diff-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .diff-card {
            background: linear-gradient(135deg, var(--red), var(--red-soft));
            color: #fff;
            border-radius: 16px;
            padding: 16px;
            min-height: 126px;
        }

        .diff-card h4 {
            font-size: 20px;
            margin-bottom: 6px;
            font-weight: 800;
        }

        .diff-card p {
            font-size: 14px;
            line-height: 1.35;
            color: rgba(255, 255, 255, 0.92);
        }

        .plans-section {
            padding: 38px 0 48px;
            background: #0d111d;
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }

        .plans-head {
            text-align: center;
            margin-bottom: 18px;
        }

        .plans-head .kicker {
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.7px;
            color: #d9e2f3;
            font-weight: 700;
        }

        .plans-head h3 {
            text-transform: uppercase;
            font-size: clamp(52px, 6vw, 82px);
            line-height: 0.9;
            font-weight: 900;
            margin: 4px 0 7px;
        }

        .plans-head p {
            color: #c2cee3;
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .plan-card {
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.24);
            background: linear-gradient(160deg, #2a3140, #1d2435);
            padding: 16px;
            display: flex;
            flex-direction: column;
            min-height: 100%;
        }

        .plan-card.featured {
            border-color: rgba(59, 130, 246, 0.58);
            background: linear-gradient(160deg, #333b4b, #222a3f);
        }

        .plan-title {
            color: var(--red-soft);
            font-size: 44px;
            line-height: 0.9;
            margin-bottom: 3px;
        }

        .plan-screens {
            color: var(--red-soft);
            font-size: 36px;
            font-weight: 800;
            line-height: 0.9;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .plan-price {
            font-size: 54px;
            line-height: 1;
            font-weight: 900;
            margin-bottom: 12px;
            color: #fff;
        }

        .plan-items {
            list-style: none;
            display: grid;
            gap: 7px;
            margin-bottom: 12px;
        }

        .plan-items li {
            display: grid;
            grid-template-columns: 18px 1fr;
            gap: 8px;
            font-size: 14px;
            color: #eff3fb;
        }

        .check {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--red), var(--red-soft));
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 1px;
        }

        .plan-card .cta {
            margin-top: auto;
            width: 100%;
            text-align: center;
        }

        .custom-plan {
            padding: 50px 0;
        }

        .custom-wrap {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            align-items: center;
        }

        .custom-kicker {
            text-transform: uppercase;
            font-weight: 700;
            color: #f4f8ff;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .custom-title {
            text-transform: uppercase;
            color: var(--red);
            font-size: clamp(46px, 5.5vw, 78px);
            line-height: 0.9;
            font-weight: 900;
            margin-bottom: 12px;
            max-width: 590px;
        }

        .custom-desc {
            color: #d4dcec;
            margin-bottom: 12px;
            max-width: 520px;
        }

        .custom-list {
            list-style: none;
            display: grid;
            gap: 8px;
            margin-bottom: 14px;
        }

        .custom-list li {
            display: grid;
            grid-template-columns: 18px 1fr;
            gap: 8px;
            color: #eff3fb;
            font-size: 14px;
        }

        .price-from {
            font-size: 40px;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .tv-visual {
            border-radius: 22px;
            border: 1px solid var(--line);
            min-height: 360px;
            background: radial-gradient(circle at 45% 50%, rgba(59, 130, 246, 0.68) 0%, rgba(59, 130, 246, 0) 45%), #0f1524;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 18px;
        }

        .tv-screen {
            width: min(430px, 100%);
            height: 250px;
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.15);
            background: linear-gradient(135deg, #0a0f18, #1a2338);
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            padding: 10px;
        }

        .tile {
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
        }

        .footer {
            border-top: 1px solid var(--line);
            padding: 26px 0 30px;
            text-align: center;
            color: #d3dced;
        }

        .footer .brand {
            margin-bottom: 4px;
            font-size: 36px;
        }

        .footer a {
            color: #dce6f8;
            margin: 0 8px;
            text-decoration: none;
            font-size: 13px;
        }

        .copyright {
            margin-top: 8px;
            font-size: 12px;
            color: #97a6bf;
        }

        @media (max-width: 980px) {
            .hero,
            .assista,
            .plans-grid,
            .diff-grid,
            .custom-wrap {
                grid-template-columns: 1fr;
            }

            .hero-title,
            .custom-title,
            .section-header h3,
            .plans-head h3 {
                font-size: clamp(40px, 12vw, 72px);
            }

            .plan-title,
            .plan-screens {
                font-size: 34px;
            }

            .plan-price {
                font-size: 46px;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="container topbar-wrap">
            <div class="brand">Elite<span>PLAY</span></div>
            <div class="nav-actions">
                <a href="#planos" class="nav-link">Planos</a>
                <a href="login.php" class="client-btn">Area do Cliente</a>
            </div>
        </div>
    </header>

    <main>
        <section class="container hero">
            <div>
                <p class="hero-kicker">Sem quedas e sem travamentos garantido</p>
                <h1 class="hero-title">Canais, filmes e series premium sem travamentos.</h1>
                <p class="hero-desc">Quer assistir tudo o que quiser? A ElitePLAY tem a solucao para voce.</p>
                <a href="#planos" class="cta">Comprar agora</a>
            </div>
            <div class="hero-video">
                <div class="video-inner">
                    <p>Conteudo premium<br>ao vivo e on demand</p>
                </div>
            </div>
        </section>

        <section class="stripe">
            <div class="container assista">
                <div class="phone-visual" aria-hidden="true">
                    <div class="phone">
                        <div class="phone-bar"></div>
                        <div class="phone-card"></div>
                        <div class="phone-card"></div>
                        <div class="phone-card"></div>
                        <div class="phone-card"></div>
                        <div class="phone-card"></div>
                        <div class="phone-card"></div>
                    </div>
                </div>
                <div>
                    <h2>Assista onde quiser</h2>
                    <p>A ElitePLAY oferece um aplicativo exclusivo para voce. Basta acessar e assistir tudo o que quiser.</p>
                    <div class="bullet-list">
                        <article class="bullet-item">
                            <span class="icon-dot">+</span>
                            <div><strong>+100 Mil Conteudos</strong><span>Em nosso aplicativo voce assiste filmes, series e canais.</span></div>
                        </article>
                        <article class="bullet-item">
                            <span class="icon-dot">S</span>
                            <div><strong>Tipos de Series</strong><span>Nosso app oferece diversos tipos de series para voce escolher.</span></div>
                        </article>
                        <article class="bullet-item">
                            <span class="icon-dot">TV</span>
                            <div><strong>Canais Abertos e Fechados</strong><span>Conteudo completo com otima qualidade e estabilidade.</span></div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <header class="section-header">
                    <p class="kicker">Voce so encontra aqui</p>
                    <h3>Elite Play</h3>
                </header>
                <div class="diff-grid">
                    <?php foreach ($differentials as $item): ?>
                    <article class="diff-card">
                        <h4><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                        <p><?php echo htmlspecialchars($item['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="plans-section" id="planos">
            <div class="container">
                <header class="plans-head">
                    <p class="kicker">Conheca</p>
                    <h3>Nossos Planos</h3>
                    <p>Faca parte hoje mesmo. Planos sem fidelidade de forma pre-paga e sem surpresas.</p>
                </header>
                <div class="plans-grid">
                    <?php foreach ($plans as $plan): ?>
                    <article class="plan-card<?php echo $plan['id'] === 'semestral' ? ' featured' : ''; ?>">
                        <h4 class="plan-title"><?php echo htmlspecialchars($plan['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                        <p class="plan-screens"><?php echo htmlspecialchars($plan['screens'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="plan-price"><?php echo htmlspecialchars($plan['price'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <ul class="plan-items">
                            <?php foreach ($plan['items'] as $item): ?>
                            <li><span class="check">&#10003;</span><span><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?php echo htmlspecialchars($plan['checkout'], ENT_QUOTES, 'UTF-8'); ?>" class="cta" target="_blank" rel="noopener noreferrer">Assine ja</a>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="custom-plan">
            <div class="container custom-wrap">
                <div>
                    <p class="custom-kicker">Monte seu plano personalizado</p>
                    <h3 class="custom-title">Precisa de mais telas? Escolha voce mesmo!</h3>
                    <p class="custom-desc">Ideal para familias e quem quer assistir em varios dispositivos ao mesmo tempo.</p>
                    <ul class="custom-list">
                        <li><span class="check">&#10003;</span><span>Escolha entre Mensal, Semestral ou Anual</span></li>
                        <li><span class="check">&#10003;</span><span>Selecione de 1 ate 10 telas simultaneas</span></li>
                        <li><span class="check">&#10003;</span><span>Cada pessoa assiste o que quiser</span></li>
                        <li><span class="check">&#10003;</span><span>Divida com familia e amigos</span></li>
                        <li><span class="check">&#10003;</span><span>Liberacao imediata apos pagamento</span></li>
                    </ul>
                    <p class="price-from">A partir de: R$20,00</p>
                    <a href="https://checkout.exemplo.com/iptv-mensal" class="cta" target="_blank" rel="noopener noreferrer">Comprar agora</a>
                </div>
                <div class="tv-visual" aria-hidden="true">
                    <div class="tv-screen">
                        <div class="tile"></div><div class="tile"></div><div class="tile"></div><div class="tile"></div><div class="tile"></div>
                        <div class="tile"></div><div class="tile"></div><div class="tile"></div><div class="tile"></div><div class="tile"></div>
                        <div class="tile"></div><div class="tile"></div><div class="tile"></div><div class="tile"></div><div class="tile"></div>
                        <div class="tile"></div><div class="tile"></div><div class="tile"></div><div class="tile"></div><div class="tile"></div>
                        <div class="tile"></div><div class="tile"></div><div class="tile"></div><div class="tile"></div><div class="tile"></div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="brand">Elite<span>PLAY</span></div>
            <div><a href="#planos">Planos</a><a href="login.php">Area do Cliente</a></div>
            <p class="copyright">Copyright &copy; 2026 - ElitePLAY. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>
