<?php
$only_session = true;
require_once 'components/header.php';

if (isset($_SESSION['id'])) {
    header('Location: /pages/perfil.php');
    exit();
}

unset($only_session);
$tituloPagina = "Corra Mais Longe";
include 'components/head.php';
?>

<?php
require_once 'config/conexao.php';

// Próximos 3 eventos futuros aprovados
$stmtEventos = $pdo->prepare("
    SELECT nome, cidade, data_evento, distancias, banner
    FROM eventos
    WHERE status = 'aprovado'
    AND data_evento >= CURRENT_DATE
    ORDER BY data_evento ASC
    LIMIT 3
");
$stmtEventos->execute();
$eventosHome = $stmtEventos->fetchAll();

// Treinadores aprovados (até 4)
$stmtTreinadores = $pdo->prepare("
    SELECT u.nome, u.foto, u.cidade, t.especialidade
    FROM treinadores t
    JOIN usuarios u ON u.id = t.usuario_id
    WHERE t.status = 'aprovado'
    ORDER BY t.id ASC
    LIMIT 4
");
$stmtTreinadores->execute();
$treinadoresHome = $stmtTreinadores->fetchAll();

// Stats gerais
$totalCorredores = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE perfil = 'corredor'")->fetchColumn();
$totalEventos = $pdo->query("SELECT COUNT(*) FROM eventos WHERE status = 'aprovado'")->fetchColumn();
$totalTreinadores = $pdo->query("SELECT COUNT(*) FROM treinadores WHERE status = 'aprovado'")->fetchColumn();
?>

<style>
/* ========== RESET & BASE ========== */
.lp * { box-sizing: border-box; margin: 0; padding: 0; }
.lp { font-family: 'Outfit', sans-serif; background: #fafafa; width: 100%; overflow-x: hidden; color: #1a1a1a; }

/* ========== HERO — Premium gradient + depth ========== */
.hero {
    background: linear-gradient(160deg, #1DB954 0%, #17a34a 40%, #0f8a3e 100%);
    padding: 72px 32px 84px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.hero::before {
    content: '';
    position: absolute;
    top: -120px;
    right: -80px;
    width: 380px;
    height: 380px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.hero::after {
    content: '';
    position: absolute;
    bottom: -60px;
    left: -40px;
    width: 260px;
    height: 260px;
    background: radial-gradient(circle, rgba(0,0,0,0.06) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

/* Nav dentro do hero */
.hero-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 680px;
    margin: 0 auto 56px;
    position: relative;
    z-index: 2;
}
.hero-nav-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}
.hero-nav-brand img {
    width: 40px;
    height: 40px;
    object-fit: contain;
    border-radius: 10px;
}
.hero-nav-brand span {
    color: #fff;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 22px;
    letter-spacing: 3px;
}
.hero-nav-links {
    display: flex;
    gap: 8px;
    align-items: center;
}
.hero-nav-link {
    color: rgba(255,255,255,0.85);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 50px;
    transition: all 0.2s;
}
.hero-nav-link:hover {
    background: rgba(255,255,255,0.12);
    color: #fff;
}
.hero-nav-login {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    border: 1px solid rgba(255,255,255,0.25);
    color: #fff;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    padding: 8px 20px;
    border-radius: 50px;
    transition: all 0.25s;
}
.hero-nav-login:hover {
    background: #fff;
    color: #1DB954;
    border-color: #fff;
}

/* Hero content */
.hero-content {
    position: relative;
    z-index: 2;
    max-width: 560px;
    margin: 0 auto;
}
.hero-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.13);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 50px;
    padding: 6px 16px;
    margin-bottom: 28px;
}
.hero-chip-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #fff;
    animation: chipPulse 2s ease-in-out infinite;
}
@keyframes chipPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
.hero-chip span {
    color: rgba(255,255,255,0.9);
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

.hero-h1 {
    color: #fff;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 62px;
    line-height: 1.02;
    margin-bottom: 6px;
    letter-spacing: 1.5px;
}
.hero-h1-destaque {
    color: #0d0d0d;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 62px;
    line-height: 1.02;
    letter-spacing: 1.5px;
    display: block;
}
.hero-sub {
    color: rgba(255,255,255,0.85);
    font-size: 17px;
    line-height: 1.75;
    max-width: 480px;
    margin: 20px auto 40px;
    font-weight: 300;
}
.hero-btns {
    display: flex;
    gap: 14px;
    justify-content: center;
    flex-wrap: wrap;
}
.btn-white {
    background: #fff;
    color: #1DB954;
    border: none;
    border-radius: 50px;
    padding: 15px 32px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    font-family: 'Outfit', sans-serif;
    text-decoration: none;
    display: inline-block;
    transition: all 0.25s;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}
.btn-white:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}
.btn-outline-white {
    background: transparent;
    color: #fff;
    border: 1.5px solid rgba(255,255,255,0.45);
    border-radius: 50px;
    padding: 15px 32px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    font-family: 'Outfit', sans-serif;
    text-decoration: none;
    display: inline-block;
    transition: all 0.25s;
}
.btn-outline-white:hover {
    background: rgba(255,255,255,0.12);
    border-color: rgba(255,255,255,0.7);
    transform: translateY(-2px);
}

/* ========== STATS ========== */
.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    max-width: 680px;
    margin: -32px auto 0;
    position: relative;
    z-index: 3;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.08);
    overflow: hidden;
}
.stat {
    padding: 28px 16px;
    text-align: center;
    position: relative;
}
.stat:not(:last-child)::after {
    content: '';
    position: absolute;
    right: 0;
    top: 20%;
    height: 60%;
    width: 1px;
    background: #eee;
}
.stat-num {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 38px;
    color: #1DB954;
    line-height: 1;
}
.stat-label {
    font-size: 13px;
    color: #999;
    margin-top: 4px;
    font-weight: 500;
    letter-spacing: 0.3px;
}

/* ========== SECTIONS ========== */
.section {
    padding: 72px 28px;
    background: #fff;
}
.section-alt {
    padding: 72px 28px;
    background: #f5f6f5;
}
.section-header {
    text-align: center;
    max-width: 540px;
    margin: 0 auto 36px;
}
.section-label {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: #1DB954;
    margin-bottom: 10px;
    display: block;
}
.section-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 38px;
    color: #0d0d0d;
    margin-bottom: 12px;
    letter-spacing: 1px;
    line-height: 1.1;
}
.section-sub {
    font-size: 16px;
    color: #666;
    line-height: 1.7;
    font-weight: 300;
}

/* ========== FEATURES ========== */
.features {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    max-width: 640px;
    margin: 0 auto;
}
.feat {
    background: #f9faf9;
    border-radius: 18px;
    padding: 28px 22px;
    border: 1px solid #eee;
    transition: all 0.3s ease;
}
.feat:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.06);
    border-color: rgba(29,185,84,0.25);
}
.feat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(29,185,84,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}
.feat-icon svg { width: 24px; height: 24px; fill: #1DB954; }
.feat-title {
    font-size: 16px;
    font-weight: 700;
    color: #0d0d0d;
    margin-bottom: 6px;
}
.feat-desc {
    font-size: 14px;
    color: #777;
    line-height: 1.6;
}

/* ========== EVENTOS ========== */
.eventos-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 580px;
    margin: 0 auto;
}
.evento-row {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #eee;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.25s;
}
.evento-row:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.05);
    border-color: rgba(29,185,84,0.2);
}
.evento-date { text-align: center; min-width: 48px; flex-shrink: 0; }
.evento-date-day {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 30px;
    color: #1DB954;
    line-height: 1;
}
.evento-date-mon {
    font-size: 11px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}
.evento-sep { width: 1px; height: 44px; background: #eee; flex-shrink: 0; }
.evento-info { flex: 1; min-width: 0; }
.evento-name {
    font-size: 15px;
    font-weight: 700;
    color: #1a1a1a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.evento-city { font-size: 13px; color: #999; margin-top: 3px; }
.evento-dists { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 8px; }
.dist-badge {
    font-size: 11px;
    background: rgba(29,185,84,0.08);
    color: #15923e;
    border-radius: 20px;
    padding: 3px 10px;
    font-weight: 600;
}
.ver-todos {
    display: block;
    text-align: center;
    margin-top: 28px;
    color: #1DB954;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: opacity 0.2s;
}
.ver-todos:hover { opacity: 0.75; }

/* ========== TREINADORES ========== */
.treinadores-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    max-width: 540px;
    margin: 0 auto;
}
.treinador-card {
    background: #f9faf9;
    border-radius: 18px;
    border: 1px solid #eee;
    padding: 28px 18px;
    text-align: center;
    transition: all 0.3s ease;
}
.treinador-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.06);
    border-color: rgba(29,185,84,0.25);
}
.treinador-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    border: 2.5px solid #1DB954;
    overflow: hidden;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(29,185,84,0.06);
}
.treinador-avatar img { width: 100%; height: 100%; object-fit: cover; }
.treinador-avatar svg { width: 28px; height: 28px; fill: #1DB954; }
.treinador-nome { font-size: 15px; font-weight: 700; color: #1a1a1a; }
.treinador-cidade { font-size: 13px; color: #999; margin-top: 3px; }
.treinador-esp { font-size: 13px; color: #555; margin-top: 4px; }
.treinador-badge {
    display: inline-block;
    margin-top: 10px;
    font-size: 11px;
    background: rgba(29,185,84,0.08);
    color: #15923e;
    border-radius: 20px;
    padding: 4px 12px;
    font-weight: 600;
}

/* ========== CTA ========== */
.cta {
    background: linear-gradient(160deg, #111 0%, #0a0a0a 100%);
    padding: 72px 28px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.cta::before {
    content: '';
    position: absolute;
    top: -60px;
    left: 50%;
    transform: translateX(-50%);
    width: 400px;
    height: 200px;
    background: radial-gradient(ellipse, rgba(29,185,84,0.12) 0%, transparent 70%);
    pointer-events: none;
}
.cta-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 42px;
    color: #fff;
    margin-bottom: 12px;
    letter-spacing: 1px;
    position: relative;
    z-index: 1;
}
.cta-sub {
    font-size: 16px;
    color: rgba(255,255,255,0.5);
    margin-bottom: 32px;
    font-weight: 300;
    position: relative;
    z-index: 1;
}
.btn-green {
    background: #1DB954;
    color: #fff;
    border: none;
    border-radius: 50px;
    padding: 16px 38px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    font-family: 'Outfit', sans-serif;
    text-decoration: none;
    display: inline-block;
    transition: all 0.25s;
    box-shadow: 0 4px 20px rgba(29,185,84,0.3);
    position: relative;
    z-index: 1;
}
.btn-green:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(29,185,84,0.4);
}

/* ========== FOOTER ========== */
.footer {
    padding: 28px 28px;
    border-top: 1px solid #eee;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    flex-wrap: wrap;
    gap: 12px;
    max-width: 100%;
}
.footer-brand {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 20px;
    letter-spacing: 2.5px;
    color: #1a1a1a;
}
.footer-links { display: flex; gap: 24px; }
.footer-link {
    font-size: 14px;
    color: #999;
    text-decoration: none;
    transition: color 0.2s;
}
.footer-link:hover { color: #1DB954; }

/* ========== MOBILE ========== */
@media (max-width: 640px) {
    .hero { padding: 40px 20px 64px; }
    .hero-nav { margin-bottom: 40px; }
    .hero-nav-links { display: none; }
    .hero-h1, .hero-h1-destaque { font-size: 44px; }
    .hero-sub { font-size: 15px; margin: 16px auto 32px; }

    .stats { margin: -24px 16px 0; }
    .stat-num { font-size: 32px; }

    .section, .section-alt { padding: 56px 20px; }
    .section-title { font-size: 32px; }
    .section-sub { font-size: 15px; }

    .features { grid-template-columns: 1fr; max-width: 100%; }
    .treinadores-grid { grid-template-columns: 1fr; max-width: 100%; }
    .eventos-list { max-width: 100%; }

    .cta { padding: 56px 20px; }
    .cta-title { font-size: 34px; }

    .footer { flex-direction: column; align-items: flex-start; }
}
</style>

<body>
<div class="lp">

<!-- HERO -->
<div class="hero">
    <nav class="hero-nav">
        <a href="/" class="hero-nav-brand">
            <img src="/images/logo_branca.webp" alt="Strively">
            <span>Strively</span>
        </a>
        <div class="hero-nav-links">
            <a href="/pages/eventos.php" class="hero-nav-link">Eventos</a>
            <a href="/pages/comunidade.php" class="hero-nav-link">Comunidade</a>
            <a href="/pages/login.php" class="hero-nav-login">Entrar</a>
        </div>
    </nav>

    <div class="hero-content">
        <div class="hero-chip">
            <div class="hero-chip-dot"></div>
            <span>Plataforma para corredores</span>
        </div>
        <h1 class="hero-h1">Corra mais longe<span class="hero-h1-destaque">com quem entende</span></h1>
        <p class="hero-sub">Conecte-se com treinadores, acompanhe sua evolução, descubra eventos perto de você e faça parte de uma comunidade de corredores.</p>
        <div class="hero-btns">
            <a href="/pages/cadastro.php" class="btn-white">Criar conta grátis</a>
            <a href="/pages/eventos.php" class="btn-outline-white">Ver eventos</a>
        </div>
    </div>
</div>

<!-- STATS -->
<div class="stats">
    <div class="stat">
        <div class="stat-num">+<?= $totalCorredores ?></div>
        <div class="stat-label">Corredores</div>
    </div>
    <div class="stat">
        <div class="stat-num">+<?= $totalEventos ?></div>
        <div class="stat-label">Eventos</div>
    </div>
    <div class="stat">
        <div class="stat-num">+<?= $totalTreinadores ?></div>
        <div class="stat-label">Treinadores</div>
    </div>
</div>

<!-- FEATURES -->
<div class="section" style="padding-top: 88px;">
    <div class="section-header">
        <span class="section-label">O que é o Strively</span>
        <h2 class="section-title">Tudo que um corredor precisa</h2>
        <p class="section-sub">Do treino ao evento, do treinador à comunidade — organize sua vida de corredor em um só lugar.</p>
    </div>
    <div class="features">
        <div class="feat">
            <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5C3.9 3 3 3.9 3 5v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg></div>
            <div class="feat-title">Calendário de treinos</div>
            <div class="feat-desc">Receba treinos do seu treinador e acompanhe sua evolução semana a semana.</div>
        </div>
        <div class="feat">
            <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0 0 11 14.9V17H9v2h6v-2h-2v-2.1a5.01 5.01 0 0 0 3.61-2.96C19.08 11.63 21 9.55 21 7V7c0-1.1-.9-2-2-2z"/></svg></div>
            <div class="feat-title">Eventos de corrida</div>
            <div class="feat-desc">Descubra provas perto de você e adicione ao seu calendário com um clique.</div>
        </div>
        <div class="feat">
            <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg></div>
            <div class="feat-title">Treinadores</div>
            <div class="feat-desc">Encontre o treinador ideal e receba planilhas personalizadas para você.</div>
        </div>
        <div class="feat">
            <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg></div>
            <div class="feat-title">Comunidade</div>
            <div class="feat-desc">Compartilhe seus treinos, conquistas e se inspire com outros corredores.</div>
        </div>
    </div>
</div>

<!-- EVENTOS -->
<div class="section-alt">
    <div class="section-header">
        <span class="section-label">Próximas corridas</span>
        <h2 class="section-title">Eventos perto de você</h2>
    </div>
    <div class="eventos-list">
        <?php
        $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        foreach ($eventosHome as $ev):
            $dt = new DateTime($ev['data_evento']);
            $distancias = array_filter(array_map('trim', explode(',', $ev['distancias'] ?? '')));
        ?>
        <div class="evento-row">
            <div class="evento-date">
                <div class="evento-date-day"><?= $dt->format('d') ?></div>
                <div class="evento-date-mon"><?= $meses[(int)$dt->format('m')-1] ?></div>
            </div>
            <div class="evento-sep"></div>
            <div class="evento-info">
                <div class="evento-name"><?= htmlspecialchars($ev['nome']) ?></div>
                <div class="evento-city">📍 <?= htmlspecialchars($ev['cidade'] ?? '') ?></div>
                <?php if (!empty($distancias)): ?>
                <div class="evento-dists">
                    <?php foreach ($distancias as $d): ?>
                        <span class="dist-badge"><?= htmlspecialchars($d) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <a href="/pages/eventos.php" class="ver-todos">Ver todos os eventos →</a>
</div>

<!-- TREINADORES -->
<div class="section">
    <div class="section-header">
        <span class="section-label">Treinadores</span>
        <h2 class="section-title">Encontre seu treinador ideal</h2>
        <p class="section-sub">Profissionais verificados prontos para montar sua planilha personalizada.</p>
    </div>
    <div class="treinadores-grid">
        <?php foreach ($treinadoresHome as $tr): ?>
        <div class="treinador-card">
            <div class="treinador-avatar">
                <?php if (!empty($tr['foto'])): ?>
                    <img src="<?= strpos($tr['foto'], 'http') === 0 ? htmlspecialchars($tr['foto']) : '/' . htmlspecialchars($tr['foto']) ?>" alt="<?= htmlspecialchars($tr['nome']) ?>">
                <?php else: ?>
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                <?php endif; ?>
            </div>
            <div class="treinador-nome"><?= htmlspecialchars(explode(' ', $tr['nome'])[0] . ' ' . (explode(' ', $tr['nome'])[1] ?? '')) ?></div>
            <?php if (!empty($tr['cidade'])): ?>
            <div class="treinador-cidade">📍 <?= htmlspecialchars($tr['cidade']) ?></div>
            <?php endif; ?>
            <?php if (!empty($tr['especialidade'])): ?>
            <div class="treinador-esp"><?= htmlspecialchars($tr['especialidade']) ?></div>
            <?php endif; ?>
            <span class="treinador-badge">✓ Verificado</span>
        </div>
        <?php endforeach; ?>
    </div>
    <a href="/pages/buscar-treinador.php" class="ver-todos">Ver todos os treinadores →</a>
</div>

<!-- CTA -->
<div class="cta">
    <h2 class="cta-title">Pronto para correr mais longe?</h2>
    <p class="cta-sub">Crie sua conta grátis e comece hoje mesmo.</p>
    <a href="/pages/cadastro.php" class="btn-green">Criar conta grátis</a>
</div>

<!-- FOOTER -->
<div class="footer">
    <span class="footer-brand">Strively</span>
    <div class="footer-links">
        <a href="/pages/eventos.php" class="footer-link">Eventos</a>
        <a href="/pages/buscar-treinador.php" class="footer-link">Treinadores</a>
        <a href="/pages/comunidade.php" class="footer-link">Comunidade</a>
        <a href="/pages/login.php" class="footer-link">Login</a>
    </div>
</div>

</div><!-- /.lp -->
</body>
</html>