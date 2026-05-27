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
* { box-sizing: border-box; margin: 0; padding: 0; }
.lp { font-family: 'Outfit', sans-serif; background: #f5f6f5; width: 100%; overflow: hidden; }

/* HERO */
.hero { background: #1DB954; padding: 60px 40px 70px; text-align: center; }
.hero-logo { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 48px; }
.hero-logo img { width: 42px; height: 42px; object-fit: contain; border-radius: 10px; }
.hero-logo-name { color: #fff; font-family: 'Bebas Neue', sans-serif; font-size: 24px; letter-spacing: 3px; }
.hero-eyebrow { color: rgba(255,255,255,0.8); font-size: 12px; font-weight: 500; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 16px; }
.hero-h1 { color: #fff; font-family: 'Bebas Neue', sans-serif; font-size: 52px; line-height: 1.05; margin-bottom: 8px; letter-spacing: 1px; }
.hero-h1-destaque { color: #0d0d0d; font-family: 'Bebas Neue', sans-serif; font-size: 52px; line-height: 1.05; letter-spacing: 1px; }
.hero-sub { color: rgba(255,255,255,0.88); font-size: 16px; line-height: 1.7; max-width: 460px; margin: 16px auto 36px; font-weight: 300; }
.hero-btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.btn-white { background: #fff; color: #1DB954; border: none; border-radius: 50px; padding: 14px 30px; font-size: 15px; font-weight: 700; cursor: pointer; font-family: 'Outfit', sans-serif; text-decoration: none; display: inline-block; }
.btn-outline-white { background: transparent; color: #fff; border: 1.5px solid rgba(255,255,255,0.55); border-radius: 50px; padding: 14px 30px; font-size: 15px; font-weight: 600; cursor: pointer; font-family: 'Outfit', sans-serif; text-decoration: none; display: inline-block; }

/* STATS */
.stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: #e0e0e0; border-top: 1px solid #e0e0e0; border-bottom: 1px solid #e0e0e0; }
.stat { background: #fff; padding: 24px 16px; text-align: center; }
.stat-num { font-family: 'Bebas Neue', sans-serif; font-size: 34px; color: #1DB954; line-height: 1; }
.stat-label { font-size: 12px; color: #8a8a8a; margin-top: 4px; font-weight: 500; }

/* FEATURES */
.section { padding: 52px 28px; background: #fff; }
.section-alt { padding: 52px 28px; background: #f5f6f5; }
.section-label { font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #1DB954; margin-bottom: 8px; }
.section-title { font-family: 'Bebas Neue', sans-serif; font-size: 32px; color: #0d0d0d; margin-bottom: 10px; letter-spacing: 1px; line-height: 1.1; }
.section-sub { font-size: 15px; color: #4a4a4a; line-height: 1.7; max-width: 480px; font-weight: 300; }
.features { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 28px; }
.feat { background: #f5f6f5; border-radius: 16px; padding: 20px 18px; border: 1px solid #ebebeb; }
.feat-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(29,185,84,0.12); display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
.feat-icon svg { width: 22px; height: 22px; fill: #1DB954; }
.feat-title { font-size: 14px; font-weight: 700; color: #0d0d0d; margin-bottom: 5px; }
.feat-desc { font-size: 13px; color: #6a6a6a; line-height: 1.55; }

/* EVENTOS */
.eventos-list { display: flex; flex-direction: column; gap: 10px; margin-top: 24px; }
.evento-row { background: #fff; border-radius: 16px; border: 1px solid #ebebeb; padding: 14px 16px; display: flex; align-items: center; gap: 14px; }
.evento-date { text-align: center; min-width: 44px; flex-shrink: 0; }
.evento-date-day { font-family: 'Bebas Neue', sans-serif; font-size: 26px; color: #1DB954; line-height: 1; }
.evento-date-mon { font-size: 11px; color: #8a8a8a; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
.evento-sep { width: 1px; height: 40px; background: #ebebeb; flex-shrink: 0; }
.evento-info { flex: 1; min-width: 0; }
.evento-name { font-size: 14px; font-weight: 700; color: #0d0d0d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.evento-city { font-size: 12px; color: #8a8a8a; margin-top: 2px; }
.evento-dists { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 6px; }
.dist-badge { font-size: 11px; background: rgba(29,185,84,0.1); color: #15923e; border-radius: 20px; padding: 2px 9px; font-weight: 600; }
.ver-todos { display: block; text-align: center; margin-top: 20px; color: #1DB954; font-size: 14px; font-weight: 600; text-decoration: none; }

/* TREINADORES */
.treinadores-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 24px; }
.treinador-card { background: #fff; border-radius: 16px; border: 1px solid #ebebeb; padding: 20px 16px; text-align: center; }
.treinador-avatar { width: 58px; height: 58px; border-radius: 50%; border: 2.5px solid #1DB954; overflow: hidden; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; background: rgba(29,185,84,0.08); }
.treinador-avatar img { width: 100%; height: 100%; object-fit: cover; }
.treinador-avatar svg { width: 28px; height: 28px; fill: #1DB954; }
.treinador-nome { font-size: 14px; font-weight: 700; color: #0d0d0d; }
.treinador-cidade { font-size: 12px; color: #8a8a8a; margin-top: 2px; }
.treinador-esp { font-size: 12px; color: #4a4a4a; margin-top: 3px; }
.treinador-badge { display: inline-block; margin-top: 8px; font-size: 11px; background: rgba(29,185,84,0.1); color: #15923e; border-radius: 20px; padding: 3px 10px; font-weight: 600; }

/* CTA */
.cta { background: #0d0d0d; padding: 56px 28px; text-align: center; }
.cta-title { font-family: 'Bebas Neue', sans-serif; font-size: 36px; color: #fff; margin-bottom: 10px; letter-spacing: 1px; }
.cta-sub { font-size: 15px; color: rgba(255,255,255,0.55); margin-bottom: 28px; font-weight: 300; }
.btn-green { background: #1DB954; color: #fff; border: none; border-radius: 50px; padding: 14px 34px; font-size: 15px; font-weight: 700; cursor: pointer; font-family: 'Outfit', sans-serif; text-decoration: none; display: inline-block; }

/* FOOTER */
.footer { padding: 24px 28px; border-top: 1px solid #ebebeb; display: flex; align-items: center; justify-content: space-between; background: #fff; flex-wrap: wrap; gap: 12px; }
.footer-brand { font-family: 'Bebas Neue', sans-serif; font-size: 18px; letter-spacing: 2px; color: #0d0d0d; }
.footer-links { display: flex; gap: 20px; }
.footer-link { font-size: 13px; color: #8a8a8a; text-decoration: none; }

/* MOBILE */
@media (max-width: 640px) {
    .hero { padding: 40px 24px 52px; }
    .hero-h1, .hero-h1-destaque { font-size: 38px; }
    .features { grid-template-columns: 1fr; }
    .treinadores-grid { grid-template-columns: 1fr; }
    .section, .section-alt { padding: 40px 20px; }
    .cta { padding: 44px 20px; }
    .footer { flex-direction: column; align-items: flex-start; }
}
</style>

<body>
<div class="lp">

<!-- HERO -->
<div class="hero">
    <div class="hero-logo">
        <img src="/images/logo_branca.webp" alt="Strively">
        <span class="hero-logo-name">Strively</span>
    </div>
    <p class="hero-eyebrow">Plataforma para corredores</p>
    <h1 class="hero-h1">Corra mais longe<br><span class="hero-h1-destaque">com quem entende</span></h1>
    <p class="hero-sub">Conecte-se com treinadores, acompanhe sua evolução, descubra eventos perto de você e faça parte de uma comunidade de corredores.</p>
    <div class="hero-btns">
        <a href="/pages/cadastro.php" class="btn-white">Criar conta grátis</a>
        <a href="/pages/eventos.php" class="btn-outline-white">Ver eventos</a>
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
<div class="section">
    <p class="section-label">O que é o Strively</p>
    <h2 class="section-title">Tudo que um corredor precisa</h2>
    <p class="section-sub">Do treino ao evento, do treinador à comunidade — organize sua vida de corredor.</p>
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
    <p class="section-label">Próximas corridas</p>
    <h2 class="section-title">Eventos perto de você</h2>
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
    <p class="section-label">Treinadores</p>
    <h2 class="section-title">Encontre seu treinador ideal</h2>
    <p class="section-sub">Profissionais verificados prontos para montar sua planilha personalizada.</p>
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