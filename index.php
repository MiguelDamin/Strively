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
      <a href="/pages/alunos.php" class="home-widget home-widget-cta">
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
            $capa = strpos($ev['banner'], 'http') === 0 || empty($ev['banner']) ? $ev['banner'] : '/' . $ev['banner'];
            $dt = new DateTime($ev['data_evento']);
          ?>
          <div class="nc-card" data-index="<?= $i ?>" onclick="ncIrPara(<?= $i ?>)">
            <div class="nc-top" <?= $capa ? "style=\"background-image:url('$capa')\"" : "" ?>>
            </div>
            <div class="nc-base">
              <h4><?= htmlspecialchars($ev['nome']) ?></h4>
              <p class="nc-loc">📍 <?= htmlspecialchars($ev['cidade']) ?></p>
              <p class="nc-dat">📅 <?= $dt->format('d/m/Y') ?></p>
              <a href="/pages/eventos.php" class="nc-btn-v">Ver detalhes</a>
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
// 2. VISITANTE — LANDING PAGE PREMIUM
// =====================================================
else: 
    $tituloPagina = "Corra Mais Longe";
    include 'components/head.php';

    // Stats para LP
    $totalCorredores = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE perfil = 'corredor'")->fetchColumn();
    $totalEventos = $pdo->query("SELECT COUNT(*) FROM eventos WHERE status = 'ativo'")->fetchColumn();
    $totalTreinadores = $pdo->query("SELECT COUNT(*) FROM treinadores WHERE status = 'aprovado'")->fetchColumn();

    // Query para o carrossel de visitantes
    $stmtEventosCarrossel = $pdo->prepare("
        SELECT id, nome, cidade, data_evento, banner 
        FROM eventos 
        WHERE status = 'ativo' AND data_evento >= CURRENT_DATE 
        ORDER BY data_evento ASC 
        LIMIT 10
    ");
    $stmtEventosCarrossel->execute();
    $eventosCarrossel = $stmtEventosCarrossel->fetchAll();

    // Treinadores
    $stmtTreinadoresLP = $pdo->prepare("SELECT u.nome, u.foto, u.cidade, t.especialidade FROM treinadores t JOIN usuarios u ON u.id = t.usuario_id WHERE t.status = 'aprovado' ORDER BY t.id ASC LIMIT 4");
    $stmtTreinadoresLP->execute();
    $treinadoresHome = $stmtTreinadoresLP->fetchAll();
?>




<div class="lp">
    <div class="hero-lp">
        <nav class="hero-nav-lp-custom" style="display:flex;align-items:center;justify-content:space-between;max-width:700px;margin:0 auto 56px;position:relative;z-index:2;">
          <!-- Logo -->
          <a href="/" style="display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0;">
            <img src="/images/icon-192.png" style="width:38px;height:38px;border-radius:10px;object-fit:contain;">
            <span style="color:#fff;font-family:'Bebas Neue',sans-serif;font-size:21px;letter-spacing:3px;">STRIVELY</span>
          </a>
          <!-- Links -->
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:center;">
            <a href="/pages/eventos.php" style="color:rgba(255,255,255,0.85);text-decoration:none;font-size:14px;font-weight:500;padding:8px 16px;border-radius:50px;transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='transparent'">Eventos</a>
            <a href="/pages/comunidade.php" style="color:rgba(255,255,255,0.85);text-decoration:none;font-size:14px;font-weight:500;padding:8px 16px;border-radius:50px;transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.12)'" onmouseout="this.style.background='transparent'">Comunidade</a>
            <a href="/pages/login.php" style="background:rgba(255,255,255,0.15);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,0.25);color:#fff;text-decoration:none;font-size:14px;font-weight:600;padding:8px 20px;border-radius:50px;transition:all 0.25s;" onmouseover="this.style.background='#fff';this.style.color='#1DB954'" onmouseout="this.style.background='rgba(255,255,255,0.15)';this.style.color='#fff'">Entrar</a>
          </div>
        </nav>
        <div class="hero-lp-content">
            <div class="hero-chip"><div class="hero-chip-dot"></div><span>Plataforma para corredores</span></div>
            <h1 class="lp-h1">Corra mais longe<span class="lp-h1-destaque">com quem entende</span></h1>
            <p class="lp-sub">Conecte-se com treinadores, acompanhe sua evolução, descubra eventos perto de você e faça parte de uma comunidade de corredores.</p>
            <div class="hero-btns-lp">
                <a href="/pages/cadastro.php" class="lp-btn-white">Criar conta grátis</a>
                <a href="/pages/eventos.php" class="lp-btn-outline">Ver eventos</a>
            </div>
        </div>
    </div>

    <!-- STATS LP -->
    <div class="lp-stats">
        <div class="lp-stat"><div class="lp-stat-num">+<?= $totalCorredores ?></div><div class="lp-stat-label">Corredores</div></div>
        <div class="lp-stat"><div class="lp-stat-num">+<?= $totalEventos ?></div><div class="lp-stat-label">Eventos</div></div>
        <div class="lp-stat"><div class="lp-stat-num">+<?= $totalTreinadores ?></div><div class="lp-stat-label">Treinadores</div></div>
    </div>

    <!-- FEATURES LP -->
    <div class="section-lp" style="padding-top: 88px;">
        <div class="section-lp-header">
            <span class="lp-section-label">O que é o Strively</span>
            <h2 class="lp-section-title">Tudo que um corredor precisa</h2>
            <p class="lp-section-sub">Do treino ao evento, do treinador à comunidade — organize sua vida de corredor.</p>
        </div>
        <div class="lp-features">
            <div class="lp-feat">
                <div class="lp-feat-icon"><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5C3.9 3 3 3.9 3 5v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg></div>
                <div class="lp-feat-title">Calendário de treinos</div>
                <div class="lp-feat-desc">Receba treinos do seu treinador e acompanhe sua evolução semana a semana.</div>
            </div>
            <div class="lp-feat">
                <div class="lp-feat-icon"><svg viewBox="0 0 24 24"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0 0 11 14.9V17H9v2h6v-2h-2v-2.1a5.01 5.01 0 0 0 3.61-2.96C19.08 11.63 21 9.55 21 7V7c0-1.1-.9-2-2-2z"/></svg></div>
                <div class="lp-feat-title">Eventos de corrida</div>
                <div class="lp-feat-desc">Descubra provas perto de você e adicione ao seu calendário com um clique.</div>
            </div>
            <div class="lp-feat">
                <div class="lp-feat-icon"><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg></div>
                <div class="lp-feat-title">Treinadores</div>
                <div class="lp-feat-desc">Encontre o treinador ideal e receba planilhas personalizadas para você.</div>
            </div>
            <div class="lp-feat">
                <div class="lp-feat-icon"><svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg></div>
                <div class="lp-feat-title">Comunidade</div>
                <div class="lp-feat-desc">Compartilhe seus treinos, conquistas e se inspire com outros corredores.</div>
            </div>
        </div>
    </div>

    <!-- EVENTOS LP (CARROSSEL PREMIUM) -->
    <div class="section-lp-alt" style="background: #fff;">
        <div class="section-lp-header">
            <span class="lp-section-label">Próximas corridas</span>
            <h2 class="lp-section-title">Eventos em destaque</h2>
        </div>
        
        <?php if (!empty($eventosCarrossel)): ?>
        <div class="nc-container">
            <?php if (count($eventosCarrossel) > 1): ?>
                <button class="nc-nav nc-prev" onclick="ncMover(-1)">
                    <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
                </button>
            <?php endif; ?>
            <div class="nc-track" id="ncTrack">
                <?php foreach ($eventosCarrossel as $i => $ev): ?>
                    <?php 
                        $capa = strpos($ev['banner'], 'http') === 0 || empty($ev['banner']) ? $ev['banner'] : '/' . $ev['banner'];
                        $dt = new DateTime($ev['data_evento']);
                    ?>
                    <div class="nc-card" data-index="<?= $i ?>" onclick="ncIrPara(<?= $i ?>)">
                        <div class="nc-top" <?= $capa ? "style=\"background-image:url('$capa')\"" : "" ?>>
                        </div>
                        <div class="nc-base">
                            <h4><?= htmlspecialchars($ev['nome']) ?></h4>
                            <p class="nc-loc">📍 <?= htmlspecialchars($ev['cidade']) ?></p>
                            <p class="nc-dat">📅 <?= $dt->format('d/m/Y') ?></p>
                            <a href="/pages/login.php?msg=cadastre" class="nc-btn-v">Ver detalhes</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($eventosCarrossel) > 1): ?>
                <button class="nc-nav nc-next" onclick="ncMover(1)">
                    <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div style="text-align:center;margin-top:32px;">
            <a href="/pages/eventos.php" style="
                display:inline-flex;align-items:center;gap:8px;
                background:transparent;color:#1DB954;
                border:2px solid #1DB954;border-radius:50px;
                padding:12px 28px;font-family:'Outfit',sans-serif;
                font-size:0.9rem;font-weight:700;text-decoration:none;
                transition:all 0.25s;letter-spacing:0.3px;
            " onmouseover="this.style.background='#1DB954';this.style.color='#fff'" 
               onmouseout="this.style.background='transparent';this.style.color='#1DB954'">
                Ver todos os eventos →
            </a>
        </div>
    </div>

    <!-- TREINADORES LP -->
    <div class="section-lp">
        <div class="section-lp-header">
            <span class="lp-section-label">Treinadores</span>
            <h2 class="lp-section-title">Encontre seu treinador ideal</h2>
        </div>
        <div class="lp-treinadores-grid">
            <?php foreach ($treinadoresHome as $tr): ?>
            <div class="lp-treinador-card">
                <div class="treinador-avatar">
                   <?php if (!empty($tr['foto'])): ?>
                        <img src="<?= strpos($tr['foto'], 'http') === 0 ? htmlspecialchars($tr['foto']) : '/' . htmlspecialchars($tr['foto']) ?>" alt="<?= htmlspecialchars($tr['nome']) ?>">
                    <?php else: ?>
                        <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                    <?php endif; ?>
                </div>
                <div class="treinador-nome"><?= htmlspecialchars(explode(' ', $tr['nome'])[0] . ' ' . (explode(' ', $tr['nome'])[1] ?? '')) ?></div>
                <div class="treinador-esp"><?= htmlspecialchars($tr['especialidade'] ?? '') ?></div>
                <span class="lp-treinador-badge">✓ Verificado</span>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:32px;">
            <a href="/pages/cadastro.php" style="
                display:inline-flex;align-items:center;gap:8px;
                background:transparent;color:#1DB954;
                border:2px solid #1DB954;border-radius:50px;
                padding:12px 28px;font-family:'Outfit',sans-serif;
                font-size:0.9rem;font-weight:700;text-decoration:none;
                transition:all 0.25s;letter-spacing:0.3px;
            " onmouseover="this.style.background='#1DB954';this.style.color='#fff'" 
               onmouseout="this.style.background='transparent';this.style.color='#1DB954'">
                Ver todos os treinadores →
            </a>
        </div>
    </div>

    <!-- CTA LP -->
    <div class="lp-cta">
        <h2 class="lp-cta-title">Pronto para correr mais longe?</h2>
        <p class="lp-cta-sub">Crie sua conta grátis e comece hoje mesmo.</p>
        <a href="/pages/cadastro.php" class="lp-btn-green">Criar conta grátis</a>
    </div>

    <!-- FOOTER LP -->
    <div class="lp-footer">
        <span class="lp-footer-brand">Strively</span>
        <div class="footer-links">
            <a href="/pages/eventos.php" class="lp-footer-link">Eventos</a>
            <a href="/pages/buscar-treinador.php" class="lp-footer-link">Treinadores</a>
            <a href="/pages/login.php" class="lp-footer-link">Login</a>
        </div>
    </div>
</div>

<?php endif; // Fim do if/else principal ?>

<style>
/* ========== LANDING PAGE PREMIUM CSS ========== */
.lp * { box-sizing: border-box; margin: 0; padding: 0; }
.lp { font-family: 'Outfit', sans-serif; background: #fafafa; width: 100%; overflow-x: hidden; color: #1a1a1a; }
.hero-lp { background: linear-gradient(160deg, #1DB954 0%, #17a34a 40%, #0f8a3e 100%); padding: 72px 32px 84px; text-align: center; position: relative; overflow: hidden; }
.hero-lp::before { content: ''; position: absolute; top: -120px; right: -80px; width: 380px; height: 380px; background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%); border-radius: 50%; pointer-events: none; }
.hero-nav-lp-custom { display: flex; align-items: center; justify-content: space-between; max-width: 680px; margin: 0 auto 56px; position: relative; z-index: 2; }
.hero-nav-brand img { width: 40px; height: 40px; object-fit: contain; border-radius: 10px; }
.hero-nav-brand span { color: #fff; font-family: 'Bebas Neue', sans-serif; font-size: 22px; letter-spacing: 3px; }
.hero-nav-links { display: flex; gap: 8px; align-items: center; }
.hero-nav-link { color: rgba(255,255,255,0.85); text-decoration: none; font-size: 14px; font-weight: 500; padding: 8px 16px; border-radius: 50px; transition: all 0.2s; }
.hero-nav-link:hover { background: rgba(255,255,255,0.12); color: #fff; }
.hero-nav-login { background: rgba(255,255,255,0.15); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,0.25); color: #fff; text-decoration: none; font-size: 14px; font-weight: 600; padding: 8px 20px; border-radius: 50px; transition: all 0.25s; }
.hero-nav-login:hover { background: #fff; color: #1DB954; border-color: #fff; }
.hero-lp-content { position: relative; z-index: 2; max-width: 560px; margin: 0 auto; }
.hero-chip { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.13); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.2); border-radius: 50px; padding: 6px 16px; margin-bottom: 28px; }
.hero-chip-dot { width: 7px; height: 7px; border-radius: 50%; background: #fff; animation: chipPulse 2s ease-in-out infinite; }
@keyframes chipPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
.hero-chip span { color: rgba(255,255,255,0.9); font-size: 12px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; }
.lp-h1 { color: #fff; font-family: 'Bebas Neue', sans-serif; font-size: 62px; line-height: 1.02; margin-bottom: 6px; letter-spacing: 1.5px; }
.lp-h1-destaque { color: #0d0d0d; font-family: 'Bebas Neue', sans-serif; font-size: 62px; line-height: 1.02; letter-spacing: 1.5px; display: block; }
.lp-sub { color: rgba(255,255,255,0.85); font-size: 17px; line-height: 1.75; max-width: 480px; margin: 20px auto 40px; font-weight: 300; }
.hero-btns-lp { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
.lp-btn-white { background: #fff; color: #1DB954; border: none; border-radius: 50px; padding: 15px 32px; font-size: 15px; font-weight: 700; cursor: pointer; font-family: 'Outfit', sans-serif; text-decoration: none; display: inline-block; transition: all 0.25s; box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
.lp-btn-outline { background: transparent; color: #fff; border: 1.5px solid rgba(255,255,255,0.45); border-radius: 50px; padding: 15px 32px; font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.25s; }
.lp-btn-white:hover, .lp-btn-outline:hover { transform: translateY(-2px); }

.lp-stats { display: grid; grid-template-columns: repeat(3, 1fr); max-width: 680px; margin: -32px auto 0; position: relative; z-index: 3; background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.08); overflow: hidden; }
.lp-stat { padding: 28px 16px; text-align: center; position: relative; }
.lp-stat:not(:last-child)::after { content: ''; position: absolute; right: 0; top: 20%; height: 60%; width: 1px; background: #eee; }
.lp-stat-num { font-family: 'Bebas Neue', sans-serif; font-size: 38px; color: #1DB954; line-height: 1; }
.lp-stat-label { font-size: 13px; color: #999; margin-top: 4px; font-weight: 500; }

.section-lp { padding: 72px 28px; background: #fff; }
.section-lp-alt { padding: 72px 28px; background: #f5f6f5; }
.section-lp-header { text-align: center; max-width: 540px; margin: 0 auto 36px; }
.lp-section-label { font-size: 12px; font-weight: 700; letter-spacing: 2.5px; text-transform: uppercase; color: #1DB954; margin-bottom: 10px; display: block; }
.lp-section-title { font-family: 'Bebas Neue', sans-serif; font-size: 38px; color: #0d0d0d; margin-bottom: 12px; letter-spacing: 1px; line-height: 1.1; }
.lp-section-sub { font-size: 16px; color: #666; line-height: 1.7; font-weight: 300; }

.lp-features { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; max-width: 640px; margin: 0 auto; }
.lp-feat { background: #f9faf9; border-radius: 18px; padding: 28px 22px; border: 1px solid #eee; transition: all 0.3s ease; }
.lp-feat:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,0.06); border-color: rgba(29,185,84,0.25); }
.lp-feat-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(29,185,84,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
.lp-feat-icon svg { width: 24px; height: 24px; fill: #1DB954; }
.lp-feat-title { font-size: 16px; font-weight: 700; color: #0d0d0d; margin-bottom: 6px; }
.lp-feat-desc { font-size: 14px; color: #777; line-height: 1.6; }

.lp-treinadores-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; max-width: 540px; margin: 0 auto; }
.lp-treinador-card { background: #f9faf9; border-radius: 18px; border: 1px solid #eee; padding: 28px 18px; text-align: center; transition: all 0.3s ease; }
.lp-treinador-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,0.06); border-color: rgba(29,185,84,0.25); }
.lp-treinador-badge { display: inline-block; margin-top: 10px; font-size: 11px; background: rgba(29,185,84,0.08); color: #15923e; border-radius: 20px; padding: 4px 12px; font-weight: 600; }

.lp-cta { background: linear-gradient(160deg, #111 0%, #0a0a0a 100%); padding: 72px 28px; text-align: center; position: relative; overflow: hidden; }
.lp-cta-title { font-family: 'Bebas Neue', sans-serif; font-size: 42px; color: #fff; margin-bottom: 12px; letter-spacing: 1px; position: relative; z-index: 1; }
.lp-cta-sub { font-size: 16px; color: rgba(255,255,255,0.5); margin-bottom: 32px; font-weight: 300; position: relative; z-index: 1; }
.lp-btn-green { background: #1DB954; color: #fff; border: none; border-radius: 50px; padding: 16px 38px; font-size: 15px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.25s; box-shadow: 0 4px 20px rgba(29,185,84,0.3); position: relative; z-index: 1; }

.lp-footer { padding: 28px 28px; border-top: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; background: #fff; flex-wrap: wrap; gap: 12px; }
.lp-footer-brand { font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 2.5px; color: #1a1a1a; }
.lp-footer-link { font-size: 14px; color: #999; text-decoration: none; transition: color 0.2s; }
.lp-footer-link:hover { color: #1DB954; }

/* SHARED STYLES (DASHBOARD & CARROSSEL) */
.home-logado { padding-bottom: 80px; background: #fff; }
.section-spacer { height: 80px; }
.nc-header { text-align: center; margin-bottom: 40px; padding: 0 20px; }
.nc-header h2 { font-family: 'Bebas Neue', sans-serif; font-size: 3rem; letter-spacing: 1.5px; color: #111; margin: 0; }
.nc-header p { color: #888; font-size: 1rem; font-weight: 300; margin-top: 4px; }
.nc-container { position: relative; max-width: 1000px; margin: 0 auto; display: flex; align-items: center; justify-content: center; height: 520px; overflow: visible; }
.nc-track { position: relative; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; perspective: 1200px; }
.nc-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 50px; height: 50px; border-radius: 50%; border: none; background: #fff; box-shadow: 0 8px 20px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 30; transition: all 0.3s; }
.nc-nav:hover { background: #1DB954; transform: translateY(-50%) scale(1.1); }
.nc-nav:hover svg { fill: #fff; }
.nc-nav svg { width: 24px; height: 24px; fill: #333; transition: fill 0.2s; }
.nc-prev { left: -20px; }
.nc-next { right: -20px; }
.nc-card { position: absolute; width: 300px; height: 460px; border-radius: 20px; overflow: hidden; background: #fff; display: flex; flex-direction: column; transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.05); opacity: 0; pointer-events: none; transform: scale(0.7); z-index: 1; }
.nc-center { transform: translateX(0) scale(1.1) !important; opacity: 1 !important; filter: none !important; z-index: 20 !important; box-shadow: 0 25px 60px rgba(0,0,0,0.2) !important; pointer-events: auto; }
.nc-left { transform: translateX(-240px) scale(0.85); opacity: 0.6; filter: blur(4px); z-index: 10; pointer-events: auto; }
.nc-right { transform: translateX(240px) scale(0.85); opacity: 0.6; filter: blur(4px); z-index: 10; pointer-events: auto; }
.nc-hidden { transform: translateX(0) scale(0.6); opacity: 0; filter: blur(8px); z-index: 1; pointer-events: none; }
.nc-top { flex: 0 0 65%; background-color: #eee; background-size: cover; background-position: center; position: relative; display: flex; align-items: center; justify-content: center; }
.nc-top::after { content: ''; position: absolute; inset: 0; background: linear-gradient(transparent, rgba(0,0,0,0.2)); }
.nc-base { flex: 1; background: #fff; padding: 20px; display: flex; flex-direction: column; align-items: center; text-align: center; }
.nc-base h4 { font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 700; color: #111; margin: 0 0 4px 0; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.nc-base .nc-loc { font-size: 0.88rem; color: #777; margin-bottom: 2px; }
.nc-base .nc-dat { font-size: 0.88rem; color: #1DB954; font-weight: 600; margin-bottom: 12px; }
.nc-btn-v { margin-top: auto; padding: 10px 24px; border-radius: 100px; border: 2px solid #1DB954; color: #1DB954; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; text-decoration: none; transition: all 0.2s; }
.nc-center .nc-btn-v { background: #1DB954; color: #fff; box-shadow: 0 4px 12px rgba(29,185,84,0.2); }
.nc-btn-v:hover { background: #199e46 !important; color: #fff !important; border-color: #199e46 !important; }

/* WIDGETS */
.home-widgets { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; max-width: 1000px; margin: 0 auto; padding: 40px 24px 0; }
.home-widget { background: #fff; border-radius: 16px; padding: 24px; border: 1px solid #eee; box-shadow: 0 2px 12px rgba(0,0,0,0.04); display: flex; flex-direction: column; gap: 8px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none; color: inherit; }
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

@media(max-width: 768px) {
  .nc-container { height: 480px; }
  .nc-card { width: 260px; height: 440px; }
  .nc-left { transform: translateX(-150px) scale(0.85); }
  .nc-right { transform: translateX(150px) scale(0.85); }
  .nc-nav { width: 40px; height: 40px; }
  .nc-prev { left: 5px; }
  .nc-next { right: 5px; }
  .home-widgets { grid-template-columns: 1fr; padding: 24px 20px 0; }
  .home-widget { padding: 20px; }
}

@media(max-width: 640px) {
    .hero-lp { padding-top: 40px; }
    .hero-nav-lp-custom { margin-bottom: 32px; gap: 10px; }
    .hero-nav-brand span { font-size: 16px; letter-spacing: 2px; }
    .hero-nav-link { padding: 6px 10px; font-size: 12px; }
    .hero-nav-login { padding: 6px 14px; font-size: 12px; }
    .lp-h1, .lp-h1-destaque { font-size: 38px; }
    .lp-stats { grid-template-columns: repeat(3, 1fr); margin-top: -24px; max-width: 90%; }
    .lp-stat { padding: 16px 8px; }
    .lp-stat-num { font-size: 24px; }
    .lp-stat-label { font-size: 10px; }
    .lp-stat:not(:last-child)::after { height: 40%; }
}
</style>

<script>
// Lógica do Carrossel Netflix Premium
let ncIndex = 0;
const ncCards = document.querySelectorAll('.nc-card');
const ncTotal = ncCards.length;

function renderNcCarousel() {
  if (ncTotal === 0) return;
  ncCards.forEach((card, i) => {
    card.className = 'nc-card'; 
    if (i === ncIndex) {
      card.classList.add('nc-center');
    } else if (ncTotal >= 3) {
      if (i === (ncIndex - 1 + ncTotal) % ncTotal) card.classList.add('nc-left');
      else if (i === (ncIndex + 1) % ncTotal) card.classList.add('nc-right');
      else card.classList.add('nc-hidden');
    } else if (ncTotal === 2) {
      if (i !== ncIndex) card.classList.add('nc-right');
      else card.classList.add('nc-center');
    }
  });
}

function ncMover(dir) {
  if (ncTotal > 1) {
    ncIndex = (ncIndex + dir + ncTotal) % ncTotal;
    renderNcCarousel();
  }
}

function ncIrPara(i) {
  if (i === ncIndex) {
    // Redirecionamento dinâmico dependendo da sessão
    <?php if (!isset($_SESSION['id'])): ?>
      window.location.href = '/pages/login.php?msg=cadastre';
    <?php else: ?>
      window.location.href = '/pages/eventos.php';
    <?php endif; ?>
  } else {
    ncIndex = i;
    renderNcCarousel();
  }
}

document.addEventListener('DOMContentLoaded', () => {
  if (ncTotal > 0) {
    renderNcCarousel();
    const tracks = document.querySelectorAll('[id="ncTrack"]');
    tracks.forEach(track => {
        let startX = 0;
        track.addEventListener('touchstart', e => { startX = e.changedTouches[0].screenX; }, {passive: true});
        track.addEventListener('touchend', e => {
          const endX = e.changedTouches[0].screenX;
          if (endX < startX - 40) ncMover(1);
          if (endX > startX + 40) ncMover(-1);
        }, {passive: true});
    });
  }
});
</script>
</body>
</html>