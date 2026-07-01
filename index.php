<?php
require_once 'config/conexao.php';

// Detectar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// 1. USUÁRIO LOGADO — HOME ANTIGA (NETFLIX STYLE)
// =====================================================
if (isset($_SESSION['id'])):
    $tituloPagina = "Início";
    
    // Buscar dados do usuário logado se não estiverem no header
    if (!isset($me)) {
        $stmtMe = $pdo->prepare("SELECT perfil, treinador_id, status_vinculo FROM usuarios WHERE id = ?");
        $stmtMe->execute([$_SESSION['id']]);
        $me = $stmtMe->fetch();
    }

    // Eventos para o carrossel (antigo)
    $stmt = $pdo->prepare("SELECT id, nome, cidade, data_evento, banner FROM eventos WHERE status = 'ativo' AND data_evento >= CURRENT_DATE ORDER BY data_evento ASC LIMIT 10");
    $stmt->execute();
    $eventos_carrossel = $stmt->fetchAll();

    // WIDGET 1 — Próximo treino
    $stmt = $pdo->prepare("
        SELECT titulo, data_treino, status FROM treinos 
        WHERE aluno_id = ? AND data_treino >= CURRENT_DATE AND status = 'pendente'
        ORDER BY data_treino ASC LIMIT 1
    ");
    $stmt->execute([$_SESSION['id']]);
    $proximoTreino = $stmt->fetch();

    // WIDGET 2 — Km Strava
    $stmt = $pdo->prepare("SELECT strava_km_ano, strava_conectado FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['id']]);
    $stravaData = $stmt->fetch();

    // WIDGET 3 — Próximo evento inscrito
    $stmt = $pdo->prepare("
        SELECT e.nome, e.data_evento, e.cidade 
        FROM usuario_eventos ue 
        JOIN eventos e ON e.id = ue.evento_id 
        WHERE ue.usuario_id = ? AND e.data_evento >= CURRENT_DATE 
        ORDER BY e.data_evento ASC LIMIT 1
    ");
    $stmt->execute([$_SESSION['id']]);
    $proximoEvento = $stmt->fetch();
    
    include 'components/head.php';
    include 'components/header.php';
?>



<div class="home-logado">

  <!-- DASHBOARD DE WIDGETS -->
  <div class="home-widgets">

    <!-- TREINO -->
    <a href="/pages/treinos.php" class="home-widget home-widget-verde">
      <div class="home-widget-icon">
        <svg viewBox="0 0 24 24" width="32" height="32" style="fill:#1DB954"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg>
      </div>
      <div class="home-widget-label">Próximo Treino</div>
      <div class="home-widget-valor">
        <?php if ($proximoTreino): ?>
          <?= htmlspecialchars($proximoTreino['titulo']) ?>
        <?php else: ?>
          Nenhum agendado
        <?php endif; ?>
      </div>
      <div class="home-widget-sub">
        <?php if ($proximoTreino): ?>
          <?php
            $hoje = date('Y-m-d');
            $amanha = date('Y-m-d', strtotime('+1 day'));
            $dt = $proximoTreino['data_treino'];
            if ($dt === $hoje) echo 'Hoje';
            elseif ($dt === $amanha) echo 'Amanhã';
            else echo (new DateTime($dt))->format('d/m');
          ?>
        <?php else: ?>
          Ver agenda →
        <?php endif; ?>
      </div>
    </a>

    <!-- KM / STRAVA -->
    <a href="/pages/perfil.php" class="home-widget">
      <div class="home-widget-icon">
        <svg viewBox="0 0 24 24" width="32" height="32" style="fill:#999"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
      </div>
      <div class="home-widget-label">Km em <?= date('Y') ?></div>
      <div class="home-widget-valor">
        <?php if (!empty($stravaData['strava_conectado'])): ?>
          <?= number_format($stravaData['strava_km_ano'], 0, ',', '.') ?> km
        <?php else: ?>
          — km
        <?php endif; ?>
      </div>
      <div class="home-widget-sub">
        <?= !empty($stravaData['strava_conectado']) ? 'via Strava' : 'Conecte o Strava →' ?>
      </div>
    </a>

    <!-- PRÓXIMO EVENTO -->
    <a href="/pages/eventos.php" class="home-widget">
      <div class="home-widget-icon">
        <svg viewBox="0 0 24 24" width="32" height="32" style="fill:#999"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0 0 11 14.9V17H9v2h6v-2h-2v-2.1a5.01 5.01 0 0 0 3.61-2.96C19.08 11.63 21 9.55 21 7V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>
      </div>
      <div class="home-widget-label">Próximo Evento</div>
      <div class="home-widget-valor">
        <?php if ($proximoEvento): ?>
          <?= htmlspecialchars($proximoEvento['nome']) ?>
        <?php else: ?>
          Nenhum evento
        <?php endif; ?>
      </div>
      <div class="home-widget-sub">
        <?php if ($proximoEvento): ?>
          <?= (new DateTime($proximoEvento['data_evento']))->format('d/m') ?> · <?= htmlspecialchars($proximoEvento['cidade']) ?>
        <?php else: ?>
          Ver corridas →
        <?php endif; ?>
      </div>
    </a>

    <!-- CTA CONDICIONAL -->
    <?php if (isset($me) && $me['perfil'] === 'treinador'): ?>
      <?php
        $stmtAlunos = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE treinador_id = ?");
        $stmtAlunos->execute([$_SESSION['id']]);
        $qtdAlunos = $stmtAlunos->fetchColumn();
      ?>
      <a href="/pages/painel-treinador.php" class="home-widget home-widget-cta">
        <div class="home-widget-icon">
          <svg viewBox="0 0 24 24" width="32" height="32" style="fill:rgba(255,255,255,0.9)"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        </div>
        <div class="home-widget-label">Seus Alunos</div>
        <div class="home-widget-valor"><?= $qtdAlunos ?> aluno<?= $qtdAlunos != 1 ? 's' : '' ?></div>
        <div class="home-widget-sub">Ver painel →</div>
      </a>
    <?php elseif (isset($me) && !empty($me['treinador_id']) && $me['status_vinculo'] === 'aceito'): ?>
      <a href="/pages/treinos.php?aba=planilha" class="home-widget home-widget-cta">
        <div class="home-widget-icon">
          <svg viewBox="0 0 24 24" width="32" height="32" style="fill:rgba(255,255,255,0.9)"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
        </div>
        <div class="home-widget-label">Planilha</div>
        <div class="home-widget-valor">Ver Treinos</div>
        <div class="home-widget-sub">Abrir planilha →</div>
      </a>
    <?php else: ?>
      <a href="/pages/buscar-treinador.php" class="home-widget home-widget-cta">
        <div class="home-widget-icon">
          <svg viewBox="0 0 24 24" width="32" height="32" style="fill:rgba(255,255,255,0.9)"><path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14l1.43 1.43L2 7.71l1.43 1.43L2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22l1.43-1.43L16.29 22l2.14-2.14 1.43 1.43 1.43-1.43-1.43-1.43L22 16.29z"/></svg>
        </div>
        <div class="home-widget-label">Treinador</div>
        <div class="home-widget-valor">Procurar</div>
        <div class="home-widget-sub">Ver treinadores →</div>
      </a>
    <?php endif; ?>

  </div>

  <div class="section-spacer"></div>

  <!-- CARROSSEL EVENTOS (PREMIUM NETFLIX) -->
  <?php if (!empty($eventos_carrossel)): ?>
  <section class="netflix-section">
    <div class="nc-header">
      <h2>Eventos em destaque</h2>
      <p>Descubra corridas que estão acontecendo perto de você</p>
    </div>
    <div class="nc-container">
      <?php if (count($eventos_carrossel) > 1): ?>
        <button class="nc-nav nc-prev" onclick="ncMover(-1)">
          <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
        </button>
      <?php endif; ?>
      <div class="nc-track" id="ncTrack">
        <?php foreach ($eventos_carrossel as $i => $ev): ?>
          <?php 
            if (empty($ev['banner'])) {
              $capa = '';
            } elseif (strpos($ev['banner'], 'http') === 0 || strpos($ev['banner'], '/') === 0) {
              $capa = $ev['banner'];
            } else {
              $capa = '/' . $ev['banner'];
            }
            $dt = new DateTime($ev['data_evento']);
          ?>
          <div class="nc-card" data-index="<?= $i ?>" onclick="ncIrPara(<?= $i ?>)">
            <div class="nc-top" <?= $capa ? "style=\"background-image:url('$capa')\"" : "" ?>>
            </div>
            <div class="nc-base">
              <h4><?= htmlspecialchars($ev['nome']) ?></h4>
              <p class="nc-loc">📍 <?= htmlspecialchars($ev['cidade']) ?></p>
              <p class="nc-dat">📅 <?= $dt->format('d/m/Y') ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if (count($eventos_carrossel) > 1): ?>
        <button class="nc-nav nc-next" onclick="ncMover(1)">
          <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        </button>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>



<?php 
// =====================================================
// 2. VISITANTE — LANDING PAGE NOVA
// =====================================================
else: 
    $tituloPagina = "Corra Mais Longe";
    include 'components/head.php';
?>

<div class="lp2">

  <!-- SEC 1: HEADER ESTÁTICO -->
  <header class="lp2-header">
    <a href="/" class="lp2-brand">
      <img src="/images/logo.png" alt="Strively" class="lp2-brand-logo">
      <span>STRIVELY</span>
    </a>
    <nav class="lp2-nav">
      <a href="/pages/treinos.php" class="lp2-nav-link">Treinos</a>
      <a href="/pages/eventos.php" class="lp2-nav-link">Eventos</a>
      <a href="/pages/comunidade.php" class="lp2-nav-link">Comunidade</a>
    </nav>
    <a href="/pages/login.php" class="lp2-btn-entrar">Entrar</a>
  </header>

  <!-- SEC 2: HERO BRANCO -->
  <section class="lp2-hero">
    <div class="lp2-hero-inner">
      <h1 class="lp2-hero-title">TREINE MELHOR.<br>CORRA MAIS LONGE.</h1>
      <p class="lp2-hero-sub">Conecte-se com treinadores, acompanhe sua evolução e descubra eventos de corrida — tudo no Strively.</p>
      <a href="/pages/cadastro.php" class="lp2-btn-cta">Criar conta grátis</a>
    </div>
  </section>

  <!-- SEC 3: VERDE — TREINADORES + DESKTOP MOCKUP -->
  <section class="lp2-green">
    <div class="lp2-green-inner">
      <div class="lp2-green-text">
        <h2>Treine seus alunos<br>de graça.</h2>
        <p>Monte planilhas, acompanhe a evolução dos seus corredores e centralize toda a comunicação — sem custo algum.</p>
        <a href="/pages/cadastro.php" class="lp2-btn-ghost">Começar agora</a>
        <div class="lp2-badges">
          <div class="lp2-badge">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
            App Store
          </div>
          <div class="lp2-badge">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M3.18 23.76c.3.17.64.22.99.14l12.82-7.41-2.76-2.76-11.05 10.03zm-1.81-20.1c-.23.3-.37.72-.37 1.26v17.16c0 .54.14.96.38 1.26l.07.07 9.61-9.61v-.23L1.44 3.59l-.07.07zm19.55 8.51l-2.74-1.58-3.06 3.06 3.06 3.06 2.75-1.59c.78-.45.78-1.5-.01-1.95zm-17.74 9.98l11.05-10.03-2.76-2.76-8.29 12.79z"/></svg>
            Google Play
          </div>
        </div>
      </div>
      <div class="lp2-green-right">
        <!-- Monitor Desktop em SVG -->
        <svg viewBox="0 0 800 500" xmlns="http://www.w3.org/2000/svg" class="mockup-desktop-svg" style="width: 100%; max-width: 580px; height: auto; display: block; margin: 0 auto;">
          <defs>
            <filter id="monitorShadow" x="-10%" y="-10%" width="120%" height="130%">
              <feDropShadow dx="0" dy="8" stdDeviation="15" flood-color="#000" flood-opacity="0.35"/>
            </filter>
            
            <linearGradient id="monitorGrad" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stop-color="#1a1a1a" />
              <stop offset="100%" stop-color="#111111" />
            </linearGradient>

            <linearGradient id="baseGrad" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stop-color="#2a2a2a" />
              <stop offset="100%" stop-color="#111111" />
            </linearGradient>

            <clipPath id="screenClip">
              <!-- Proporção 1901x867 calculada perfeitamente: 768x350.25 -->
              <rect x="16" y="16" width="768" height="350.25" rx="2" ry="2" />
            </clipPath>
          </defs>

          <g filter="url(#monitorShadow)">
            <!-- Haste/pescoço elegante -->
            <polygon points="360,390 440,390 445,450 355,450" fill="#222222" />
            
            <!-- Base fina (trapézio achatado) -->
            <path d="M 280,450 H 520 L 560,465 Q 565,470 550,470 H 250 Q 235,470 240,465 Z" fill="url(#baseGrad)" />
            
            <!-- Corpo do Monitor (Bezels finos) -->
            <rect x="0" y="0" width="800" height="390" rx="6" ry="6" fill="url(#monitorGrad)" stroke="#2e2e2e" stroke-width="1.5" />
            
            <!-- Câmera sutil superior -->
            <circle cx="400" cy="8" r="3" fill="#2a2a2a" />
            <circle cx="400" cy="8.5" r="1.2" fill="#444" />

            <!-- Área da tela -->
            <g clip-path="url(#screenClip)">
              <!-- Fundo escuro (LCD desligado) -->
              <rect x="16" y="16" width="768" height="350.25" fill="#000" />
              <!-- Gráfico do site interno, não cortado (xMidYMid meet) -->
              <image href="/images/imagens-about/svgdoscria.svg" x="16" y="16" width="768" height="350.25" preserveAspectRatio="xMidYMid meet" />
              
              <!-- Reflexo de luz na tela realista -->
              <polygon points="16,16 350,16 16,366.25" fill="#ffffff" opacity="0.04" />
            </g>

            <!-- Logo inferior leve -->
            <text x="400" y="381" font-family="'Outfit', sans-serif" font-size="9" font-weight="600" fill="#555" text-anchor="middle" letter-spacing="3">STRIVELY</text>
          </g>
        </svg>
      </div>
    </div>
  </section>

  <!-- SEC 4: TREINOS -->
  <section class="lp2-feature lp2-feature--right" style="background-color: #ffffff;">
    <div class="lp2-feature-text">
      <span class="lp2-label">Treinos</span>
      <h2>Treine com sabedoria,<br>treine com STRIVELY.</h2>
      <p>Receba planilhas do seu treinador, acompanhe cada sessão no calendário e marque seu progresso — tudo integrado ao Strava.</p>
    </div>
    <div class="lp2-feature-phone">
      <img class="mockup-phone-single" src="/images/imagens-about/treinos_about_image_completo2.png" alt="Treinos">
    </div>
  </section>

  <!-- SEC 5: EVENTOS -->
  <section class="lp2-feature lp2-feature--left" style="background-color: #ffffff;">
    <div class="lp2-feature-text">
      <span class="lp2-label">Eventos</span>
      <h2>Fique por Dentro<br>de Eventos.</h2>
      <p>Descubra corridas de rua e eventos próximos, adicione-os ao seu calendário e nunca mais perca uma prova.</p>
    </div>
    <div class="lp2-feature-phone">
      <img class="mockup-phone-single" src="/images/imagens-about/eventos_about_image_completo2.png" alt="Eventos">
    </div>
  </section>

  <!-- SEC 6: COMUNIDADE -->
  <section class="lp2-feature lp2-feature--right" style="background-color: #ffffff;">
    <div class="lp2-feature-text">
      <span class="lp2-label">Comunidade</span>
      <h2>Persiga seus amigos e compartilhe seus treinos.</h2>
      <p>Publique conquistas, curta e comente os treinos de outros corredores. A motivação coletiva é o combustível extra que você precisava.</p>
    </div>
    <div class="lp2-feature-phone">
      <img class="mockup-phone-single" src="/images/imagens-about/comunidade_about_image_completo2.png" alt="Comunidade">
    </div>
  </section>

  <?php include_once __DIR__ . '/components/footer.php'; ?>
</div>

<?php endif; // Fim do if/else principal ?>


<style>
/* =============================================
   LPv2 — STRIVELY LANDING PAGE
   ============================================= */
.lp2 {
  font-family: 'Outfit', sans-serif;
  color: #1a1a1a;
  background: #fff;
  overflow-x: hidden;
}

/* HEADER */
.lp2-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 40px;
  background: #fff;
  border-bottom: 1px solid #f0f0f0;
  gap: 16px;
}
/* Override global 'header nav' green background from style.css */
.lp2-header nav {
  background: transparent !important;
  height: auto !important;
  padding: 0 !important;
  box-shadow: none !important;
}
.lp2-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  flex-shrink: 0;
}
.lp2-brand-logo {
  width: 36px;
  height: 36px;
  object-fit: contain;
  border-radius: 9px;
}
.lp2-brand span {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 20px;
  letter-spacing: 3px;
  color: #111;
}
.lp2-nav {
  display: flex;
  gap: 28px;
  align-items: center;
}
.lp2-nav-link {
  color: #555;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: color 0.18s;
}
.lp2-nav-link:hover {
  color: #111;
}
.lp2-btn-entrar {
  background: #1DB954;
  color: #fff;
  text-decoration: none;
  font-size: 14px;
  font-weight: 700;
  padding: 9px 22px;
  border-radius: 50px;
  transition: background 0.2s;
  white-space: nowrap;
  flex-shrink: 0;
}
.lp2-btn-entrar:hover { background: #17a34a; }

/* HERO */
.lp2-hero {
  background: #fff;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 80px 24px;
  text-align: center;
}
.lp2-hero-inner {
  max-width: 600px;
  margin: 0 auto;
}
.lp2-hero-title {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 54px;
  line-height: 1.05;
  letter-spacing: 2px;
  color: #111;
  margin: 0 0 20px;
}
.lp2-hero-sub {
  font-size: 18px;
  color: #666;
  line-height: 1.7;
  max-width: 480px;
  margin: 0 auto 36px;
  font-weight: 300;
}
.lp2-btn-cta {
  display: inline-block;
  background: #1DB954;
  color: #fff;
  text-decoration: none;
  font-size: 16px;
  font-weight: 700;
  padding: 16px 40px;
  border-radius: 50px;
  transition: background 0.2s, transform 0.2s;
  box-shadow: 0 6px 24px rgba(29,185,84,0.28);
}
.lp2-btn-cta:hover {
  background: #17a34a;
  transform: translateY(-2px);
}

/* GREEN BLOCK */
.lp2-green {
  background: #1DB954;
  padding: 120px 5%; /* fully contained */
  overflow: hidden;
}
.lp2-green-inner {
  max-width: 1080px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  gap: 5%;
}
.lp2-green-text {
  flex: 0 1 42%;
  min-width: 0;
}
.lp2-green-text h2 {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 52px;
  color: #fff;
  letter-spacing: 1.5px;
  line-height: 1.05;
  margin: 0 0 16px;
}
.lp2-green-text p {
  color: rgba(255,255,255,0.85);
  font-size: 16px;
  line-height: 1.7;
  margin: 0 0 28px;
  font-weight: 300;
}
.lp2-btn-ghost {
  display: inline-block;
  border: 2px solid rgba(255,255,255,0.7);
  color: #fff;
  text-decoration: none;
  font-size: 14px;
  font-weight: 700;
  padding: 11px 28px;
  border-radius: 50px;
  transition: background 0.2s, border-color 0.2s;
  margin-bottom: 32px;
}
.lp2-btn-ghost:hover {
  background: rgba(255,255,255,0.15);
  border-color: #fff;
}
.lp2-green-right {
  flex: 0 1 53%;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* MOCKUP DESKTOP SINGLE */
.mockup-desktop-single {
  width: 100%;
  max-width: 650px;
  height: auto;
  display: block;
  margin: 0 auto;
}

/* STORE BADGES */
.lp2-badges {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 28px;
}
.lp2-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255,255,255,0.15);
  border: 1px solid rgba(255,255,255,0.3);
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  padding: 9px 18px;
  border-radius: 10px;
  cursor: default;
  transition: background 0.2s;
}
.lp2-badge:hover { background: rgba(255,255,255,0.22); }

/* FEATURE SECTIONS */
.lp2-feature {
  padding: 140px 5%;
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6%;
}
.lp2-feature--left {
  flex-direction: row-reverse;
}
.lp2-feature-text {
  flex: 0 1 45%;
  min-width: 0;
}
.lp2-label {
  display: inline-block;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  color: #1DB954;
  margin-bottom: 14px;
}
.lp2-feature-text h2 {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 48px;
  color: #111;
  line-height: 1.05;
  letter-spacing: 1.5px;
  margin: 0 0 18px;
}
.lp2-feature-text p {
  font-size: 16px;
  color: #666;
  line-height: 1.75;
  font-weight: 300;
}
.lp2-feature-phone {
  flex: 0 1 55%;
  display: flex;
  justify-content: center;
}

/* PHONE MOCKUP SINGLE */
.mockup-phone-single {
  width: 100%;
  max-width: 400px;
  height: auto;
  display: block;
  margin: 0 auto;
  filter: drop-shadow(0 40px 100px rgba(0,0,0,0.15));
}

/* RESPONSIVE */
@media (max-width: 1100px) {
  .lp2-green-right { flex: 0 1 50%; }
  .lp2-feature { padding: 100px 5%; gap: 6%; }
}
@media (max-width: 900px) {
  .lp2-green-inner {
    flex-direction: column;
    gap: 40px;
    text-align: center;
    align-items: center;
  }
  .lp2-green-text { order: 1; }
  .lp2-green-right { order: 2; width: 100%; max-width: 650px; }
  .lp2-badges { justify-content: center; }
  .lp2-feature, .lp2-feature--left {
    flex-direction: column;
    gap: 60px;
    text-align: center;
    padding: 100px 32px 80px;
  }
  .lp2-feature-text p { max-width: 100%; }
  .lp2-feature-text h2 { font-size: 38px; }
  .mockup-phone-single { max-width: 360px; }
}
@media (max-width: 640px) {
  .lp2-header { padding: 14px 20px; }
  .lp2-nav { display: none; }
  .lp2-hero { padding: 80px 20px; min-height: 100svh; }
  .lp2-hero-title { font-size: 42px; }
  .lp2-hero-sub { font-size: 15px; }
  .lp2-green { padding: 52px 20px 0px; }
  .lp2-green-text h2 { font-size: 40px; }
  .mockup-desktop-single { margin-bottom: -40px; }
  .mockup-phone-single { max-width: 340px; }
}

/* Estilo compartilhado do dashboard (usuário logado) */
.home-logado { padding-bottom: 80px; background: #fff; }
.section-spacer { height: 80px; }
.nc-header { text-align: center; margin-bottom: 40px; padding: 0 20px; }
.nc-header h2 { font-family: 'Bebas Neue', sans-serif; font-size: 3rem; letter-spacing: 1.5px; color: #111; margin: 0; }
.nc-header p { color: #888; font-size: 1rem; font-weight: 300; margin-top: 4px; }
.nc-container { position: relative; width: 100%; max-width: 1000px; margin: 0 auto; display: flex; align-items: center; justify-content: center; height: 520px; }
.nc-track { position: relative; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; perspective: 1200px; }
.nc-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 50px; height: 50px; border-radius: 50%; border: none; background: #fff; box-shadow: 0 8px 20px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 30; transition: all 0.3s; }
.nc-nav:hover { background: #1DB954; transform: translateY(-50%) scale(1.1); }
.nc-nav:hover svg { fill: #fff; }
.nc-nav svg { width: 24px; height: 24px; fill: #333; transition: fill 0.2s; }
.nc-prev { left: -20px; }
.nc-next { right: -20px; }
.nc-card { position: absolute; width: 300px; height: 460px; border-radius: 20px; overflow: hidden; background: #fff; display: flex; flex-direction: column; transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.05); opacity: 0; pointer-events: none; transform: scale(0.7); z-index: 1; }
.nc-center { transform: translateX(0) scale(1.1) !important; opacity: 1 !important; filter: none !important; z-index: 20 !important; box-shadow: 0 25px 60px rgba(0,0,0,0.2) !important; pointer-events: auto; }
.nc-left  { transform: translateX(-220px) scale(0.85); opacity: 0.6; filter: blur(3px); z-index: 10; pointer-events: auto; }
.nc-right { transform: translateX(220px)  scale(0.85); opacity: 0.6; filter: blur(3px); z-index: 10; pointer-events: auto; }
.nc-hidden { transform: translateX(0) scale(0.6); opacity: 0; filter: blur(8px); z-index: 1; pointer-events: none; }
.nc-top { flex: 0 0 65%; background-color: #eee; background-size: cover; background-position: center; }
.nc-top::after { content: ''; position: absolute; inset: 0; background: linear-gradient(transparent, rgba(0,0,0,0.2)); }
.nc-base { flex: 0 0 auto; background: #fff; padding: 14px 14px 12px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 2px; min-height: 0; overflow: hidden; }
.nc-base h4 { font-family: 'Outfit', sans-serif; font-size: 0.92rem; font-weight: 700; color: #111; margin: 0 0 4px; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.nc-center .nc-base h4 { font-size: 0.95rem; -webkit-line-clamp: 3; }
.nc-left .nc-base h4, .nc-right .nc-base h4 { -webkit-line-clamp: 1; }
.nc-base .nc-loc { font-size: 0.82rem; color: #777; margin-bottom: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
.nc-base .nc-dat { font-size: 0.82rem; color: #1DB954; font-weight: 600; margin-bottom: 0; white-space: nowrap; }
.home-widgets { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; max-width: 1000px; margin: 0 auto; padding: 40px 24px 0; }
.home-widget { background: #fff; border-radius: 16px; padding: 24px; border: 1px solid #eee; box-shadow: 0 2px 12px rgba(0,0,0,0.04); display: flex; flex-direction: column; gap: 8px; transition: all 0.3s; text-decoration: none; color: inherit; }
.home-widget:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.08); }
.home-widget-icon { display: block; margin-bottom: 4px; }
.home-widget-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #999; }
.home-widget-valor { font-family: 'Bebas Neue', sans-serif; font-size: 1.6rem; letter-spacing: 1px; color: #111; line-height: 1.1; margin: 2px 0; }
.home-widget-sub { font-size: 0.8rem; color: #aaa; }
.home-widget-verde { border-left: 4px solid #1DB954; }
.home-widget-cta { background: #1DB954; color: #fff; border: none; }
.home-widget-cta .home-widget-label { color: rgba(255,255,255,0.7); }
.home-widget-cta .home-widget-valor { color: #fff; }
.home-widget-cta .home-widget-sub { color: rgba(255,255,255,0.8); }
.netflix-section { width: 100%; position: relative; padding: 0 0 40px 0; }
.nc-card { flex-shrink: 0; max-width: 100%; }

@media (max-width: 900px) { .nc-container { height: 480px; } .nc-card { width: 260px; height: 400px; } .nc-left { transform: translateX(-160px) scale(0.83); } .nc-right { transform: translateX(160px) scale(0.83); } }
@media (max-width: 768px) { .home-widgets { grid-template-columns: 1fr; padding: 24px 20px 0; } .home-widget { padding: 20px; } }
@media (max-width: 600px) { .nc-container { height: 480px; } .nc-card { width: 260px; height: 400px; } .nc-left { transform: translateX(-130px) scale(0.80); } .nc-right { transform: translateX(130px) scale(0.80); } .nc-nav { display: none !important; } }
@media (max-width: 380px) { .nc-container { height: 440px; } .nc-card { width: 240px; height: 370px; } .nc-left { transform: translateX(-110px) scale(0.78); } .nc-right { transform: translateX(110px) scale(0.78); } }
@media (min-width: 601px) { .nc-card { width: 300px; height: 460px; } }
</style>

<script>
// Carrossel do dashboard (usuário logado)
let ncIndex = 0;
const ncCards = document.querySelectorAll('.nc-card');
const ncTotal = ncCards.length;
function renderNcCarousel() {
  if (ncTotal === 0) return;
  ncCards.forEach((card, i) => {
    card.className = 'nc-card';
    if (i === ncIndex) card.classList.add('nc-center');
    else if (ncTotal >= 3) {
      if (i === (ncIndex - 1 + ncTotal) % ncTotal) card.classList.add('nc-left');
      else if (i === (ncIndex + 1) % ncTotal) card.classList.add('nc-right');
      else card.classList.add('nc-hidden');
    } else if (ncTotal === 2) {
      card.classList.add(i !== ncIndex ? 'nc-right' : 'nc-center');
    }
  });
}
let autoPlayTimer, pauseTimer;
function startAutoPlay() { if (ncTotal > 1) autoPlayTimer = setInterval(() => ncMover(1), 4000); }
function pauseAutoPlay() { clearInterval(autoPlayTimer); clearTimeout(pauseTimer); pauseTimer = setTimeout(startAutoPlay, 6000); }
function ncMover(dir) { if (ncTotal > 1) { ncIndex = (ncIndex + dir + ncTotal) % ncTotal; renderNcCarousel(); } }
function ncIrPara(i) {
  if (i === ncIndex) {
    <?php if (!isset($_SESSION['id'])): ?>
      window.location.href = '/pages/login.php?msg=cadastre';
    <?php else: ?>
      window.location.href = '/pages/eventos.php';
    <?php endif; ?>
  } else { ncIndex = i; renderNcCarousel(); }
}
document.addEventListener('DOMContentLoaded', () => {
  if (ncTotal > 0) {
    renderNcCarousel(); startAutoPlay();
    document.querySelectorAll('[id="ncTrack"]').forEach(track => {
      let startX = 0, startY = 0;
      track.addEventListener('touchstart', e => { startX = e.changedTouches[0].screenX; startY = e.changedTouches[0].screenY; pauseAutoPlay(); }, { passive: true });
      track.addEventListener('touchmove', e => { const dx = Math.abs(e.touches[0].screenX - startX); const dy = Math.abs(e.touches[0].screenY - startY); if (dx > dy && dx > 10) e.preventDefault(); }, { passive: false });
      track.addEventListener('touchend', e => { const dx = e.changedTouches[0].screenX - startX; const dy = Math.abs(e.changedTouches[0].screenY - startY); if (dy < 60) { if (dx < -40) ncMover(1); else if (dx > 40) ncMover(-1); } }, { passive: true });
    });
  }
});
</script>
<?php include_once __DIR__ . '/components/footer.php'; ?>
</body>
</html>