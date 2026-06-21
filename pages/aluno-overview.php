<?php
ob_start();
// ==========================================================
// STRIVELY — pages/aluno-overview.php
// Painel de overview do aluno
// ==========================================================

$only_session = true;
require_once '../components/header.php';

if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

if ($_SESSION['perfil'] !== 'treinador') {
  header('Location: /index.php');
  exit();
}

require_once '../config/conexao.php';
require_once '../includes/calculos-treinador.php';
require_once '../components/strava-progress-bar.php';

$aluno_id = (int)($_GET['aluno_id'] ?? 0);
if (!$aluno_id) { header('Location: /pages/alunos.php'); exit(); }

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND treinador_id = ? AND status_vinculo = 'aceito'");
$stmt->execute([$aluno_id, $_SESSION['id']]);
$aluno = $stmt->fetch();
if (!$aluno) { header('Location: /pages/alunos.php'); exit(); }

// Executar os cálculos
$adesao = getAdesaoSemanal($pdo, $aluno_id);
$streak = getStreakAtual($pdo, $aluno_id);
$rpeMedio = getRpeMedio($pdo, $aluno_id, 14);
$proximaProva = getProximaProva($pdo, $aluno_id);
$alertas = getAlertas($pdo, $aluno_id);
$volume6S = getVolumeSemanal6Semanas($pdo, $aluno_id);
$ultimoTreino = getUltimoTreinoRealizado($pdo, $aluno_id);
$ultimosRpes = getUltimosRpes($pdo, $aluno_id);

unset($only_session);
$tituloPagina = "Overview: " . htmlspecialchars($aluno['nome']);
include '../components/head.php';
include '../components/header.php';
?>

<style>
.overview-wrap { max-width: 1000px; margin: 40px auto; padding: 0 24px 100px; }

/* Header do aluno */
.aluno-header { display: flex; align-items: center; gap: 16px; margin-bottom: 32px; flex-wrap: wrap; }
.aluno-header a.voltar { color: var(--text-secondary, #555); font-size: .85rem; display: flex; align-items: center; gap: 5px; text-decoration: none; transition: color .2s; margin-right: 4px; }
.aluno-header a.voltar:hover { color: var(--green); }
.aluno-foto { width: 52px; height: 52px; border-radius: 50%; object-fit: cover; border: 2.5px solid var(--green); flex-shrink: 0; box-shadow: 0 0 0 4px rgba(29,185,84,.1); }
.aluno-foto-padrao { width: 52px; height: 52px; border-radius: 50%; background: #f0f0f0; border: 2.5px solid var(--green); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 0 0 4px rgba(29,185,84,.1); }
.aluno-foto-padrao svg { width: 28px; height: 28px; fill: #aaa; }
.aluno-info { flex: 1; min-width: 0; }
.aluno-info h1 { font-family: 'Bebas Neue', sans-serif; font-size: 1.9rem; letter-spacing: 2px; line-height: 1; margin: 0; color: var(--text-primary, #111); }
.aluno-info span { font-size: .82rem; color: var(--text-secondary, #555); }

.acoes-topo { display: flex; gap: 8px; margin-left: auto; }
.btn-outline { background: transparent; color: #111; border: 2px solid #ddd; padding: 8px 16px; border-radius: 8px; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: .82rem; text-decoration: none; transition: all .2s; }
.btn-outline:hover { border-color: var(--green); color: var(--green); }
.btn-primary-small { background: var(--green); color: #fff; border: none; padding: 10px 16px; border-radius: 8px; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: .82rem; text-decoration: none; transition: opacity .2s; }
.btn-primary-small:hover { opacity: .85; }

/* Widgets Grid */
.widgets-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.widget-card { background: #fff; border-radius: 16px; padding: 20px; border: 1px solid rgba(0,0,0,.06); box-shadow: 0 4px 12px rgba(0,0,0,.03); display: flex; flex-direction: column; gap: 6px; }
.widget-icon { margin-bottom: 4px; }
.widget-icon svg { width: 26px; height: 26px; fill: #777; }
.widget-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #999; }
.widget-valor { font-family: 'Bebas Neue', sans-serif; font-size: 1.6rem; letter-spacing: 1px; color: #111; line-height: 1.1; margin: 2px 0; }
.widget-sub { font-size: .8rem; color: #777; font-weight: 500; }
.var-up { color: #1DB954; font-weight: 700; }
.var-down { color: #EF5350; font-weight: 700; }

/* Alertas */
.alertas-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; }
.alerta-item { padding: 14px 16px; border-radius: 12px; display: flex; align-items: center; gap: 12px; font-size: .85rem; font-weight: 500; }
.alerta-item svg { width: 22px; height: 22px; flex-shrink: 0; }
.alerta-item.info { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; }
.alerta-item.info svg { fill: #0284c7; }
.alerta-item.atencao { background: #fefce8; color: #b45309; border: 1px solid #fde68a; }
.alerta-item.atencao svg { fill: #b45309; }
.alerta-item.positivo { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.alerta-item.positivo svg { fill: #15803d; }

/* Overview Grid */
.overview-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px; margin-bottom: 32px; }
@media(max-width: 800px) { .overview-grid { grid-template-columns: 1fr; } }
.section-box { background: #fff; border-radius: 16px; padding: 22px; border: 1px solid rgba(0,0,0,.06); box-shadow: 0 4px 12px rgba(0,0,0,.03); }
.section-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; letter-spacing: 1px; margin: 0 0 16px; color: #111; border-bottom: 1px solid #f0f0f0; padding-bottom: 12px; }

/* Gráfico SVG Barras */
.chart-container { display: flex; align-items: flex-end; justify-content: space-between; height: 180px; padding-top: 10px; gap: 6px; }
.chart-bar-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 6px; }
.chart-bar { width: 100%; max-width: 40px; background: rgba(29, 185, 84, 0.15); border-radius: 6px 6px 0 0; position: relative; transition: all .3s; }
.chart-bar.filled { background: var(--green); }
.chart-label { font-size: .7rem; color: #888; font-weight: 600; margin-top: auto; }
.chart-val { font-size: .75rem; font-weight: 700; color: #333; margin-bottom: -2px; }

/* Últimos RPEs */
.rpe-list { display: flex; flex-direction: column; gap: 8px; }
.rpe-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: #fafafa; border-radius: 10px; font-size: .85rem; border: 1px solid #eee; }
.rpe-item-info { display: flex; flex-direction: column; gap: 2px; overflow: hidden; }
.rpe-item-titulo { font-weight: 700; color: #111; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rpe-item-data { color: #888; font-size: .75rem; }
.rpe-badge { padding: 4px 10px; border-radius: 20px; font-weight: 800; font-size: .75rem; color: #fff; }

/* Último Treino Box */
.ultimo-treino-box { background: #fafafa; border: 1px solid #eee; border-radius: 12px; padding: 16px; }
.ut-badge { background: #dcfce7; color: #166534; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 3px 8px; border-radius: 6px; display: inline-block; margin-bottom: 8px; }
.ut-strava { background: #FC4C02; color: #fff; font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 3px 8px; border-radius: 6px; display: inline-block; margin-bottom: 8px; }
.ut-titulo { font-weight: 700; font-size: 1rem; color: #111; margin-bottom: 4px; }
.ut-data { font-size: .8rem; color: #777; margin-bottom: 12px; }

/* Responsividade header */
@media(max-width: 600px) {
  .aluno-header { flex-direction: column; align-items: flex-start; gap: 12px; }
  .acoes-topo { margin-left: 0; width: 100%; display: grid; grid-template-columns: 1fr 1fr; }
  .btn-outline, .btn-primary-small { text-align: center; display: flex; justify-content: center; }
}
</style>

<section class="overview-wrap">

  <!-- CABEÇALHO -->
  <div class="aluno-header">
    <a href="/pages/alunos.php" class="voltar">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
      Meus alunos
    </a>

    <div style="display:flex;align-items:center;gap:16px;width:100%;">
      <?php if (!empty($aluno['foto'])): ?>
        <img src="<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="aluno-foto">
      <?php else: ?>
        <div class="aluno-foto-padrao">
          <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
        </div>
      <?php endif; ?>

      <div class="aluno-info">
        <h1><?= htmlspecialchars($aluno['nome']) ?></h1>
        <span><?= htmlspecialchars($aluno['cidade'] ?? 'Sem cidade registrada') ?></span>
      </div>

      <div class="acoes-topo">
        <a href="/pages/treinos-alunos.php?aba=calendario&aluno_id=<?= $aluno_id ?>" class="btn-outline">Calendário</a>
        <a href="/pages/treinos-alunos.php?aba=planilha&aluno_id=<?= $aluno_id ?>" class="btn-primary-small">Planilha completa</a>
      </div>
    </div>
  </div>

  <!-- ALERTAS -->
  <?php if (!empty($alertas)): ?>
    <div class="alertas-list">
      <?php foreach ($alertas as $a): ?>
        <?php 
          $icone = '';
          if ($a['severidade'] === 'info') $icone = '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>';
          elseif ($a['severidade'] === 'atencao') $icone = '<svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>';
          else $icone = '<svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
        ?>
        <div class="alerta-item <?= $a['severidade'] ?>">
          <?= $icone ?>
          <div>
            <strong><?= htmlspecialchars($a['tipo']) ?>:</strong> <?= htmlspecialchars($a['mensagem']) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- WIDGETS -->
  <div class="widgets-grid">
    <!-- Adesão -->
    <div class="widget-card">
      <div class="widget-icon"><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg></div>
      <div class="widget-label">Treinos na Semana</div>
      <div class="widget-valor">
        <?= $adesao['realizados'] ?> / <?= $adesao['planejados'] ?>
      </div>
      <div class="widget-sub">
        <?php if ($adesao['percentual'] === null): ?>
          Sem treinos na semana
        <?php else: ?>
          Adessão: <?= round($adesao['percentual']) ?>%
          <?php if ($adesao['variacao_vs_semana_anterior'] !== null): ?>
            <?php 
              $var = round($adesao['variacao_vs_semana_anterior']); 
              if ($var > 0) echo '<span class="var-up">(+' . $var . '%)</span>';
              elseif ($var < 0) echo '<span class="var-down">(' . $var . '%)</span>';
              else echo '(0%)';
            ?>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Streak -->
    <div class="widget-card">
      <div class="widget-icon"><svg viewBox="0 0 24 24"><path d="M17.5 10c0-1.63-.82-3.1-2.14-4C13.93 5.02 12.18 2 12.18 2S10 5.46 10 8c0 1.05.35 2 .93 2.76.01.02.03.04.04.05C11.83 11.91 12.5 13.12 12.5 14.5c0 1.93-1.57 3.5-3.5 3.5s-3.5-1.57-3.5-3.5c0-.98.39-1.87 1.03-2.5.01-.01.01-.02.02-.03C7.2 11.23 7 10.63 7 10c0-1.63.82-3.1 2.14-4C7.61 7 6 9.39 6 12c0 3.31 2.69 6 6 6s6-2.69 6-6c0-1.63-.82-3.1-2.14-4-1.32.9-2.14 2.37-2.14 4h3.78z"/></svg></div>
      <div class="widget-label">Sequência (Streak)</div>
      <div class="widget-valor"><?= $streak ?> dia<?= $streak !== 1 ? 's' : '' ?></div>
      <div class="widget-sub">consecutivos</div>
    </div>

    <!-- RPE Médio -->
    <div class="widget-card">
      <div class="widget-icon"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg></div>
      <div class="widget-label">RPE Médio (14 dias)</div>
      <div class="widget-valor">
        <?= $rpeMedio !== null ? number_format($rpeMedio, 1, ',', '') : '—' ?>
      </div>
      <div class="widget-sub"><?= $rpeMedio !== null ? '/ 10' : 'Sem dados' ?></div>
    </div>

    <!-- Próxima Prova -->
    <div class="widget-card">
      <div class="widget-icon"><svg viewBox="0 0 24 24"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0 0 11 14.9V17H9v2h6v-2h-2v-2.1a5.01 5.01 0 0 0 3.61-2.96C19.08 11.63 21 9.55 21 7V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg></div>
      <div class="widget-label">Próxima Prova</div>
      <div class="widget-valor" style="font-size: 1.2rem; margin: 6px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
        <?php if ($proximaProva): ?>
          <?= htmlspecialchars($proximaProva['evento_nome'] ?? $proximaProva['nome_manual']) ?>
        <?php else: ?>
          Nenhuma
        <?php endif; ?>
      </div>
      <div class="widget-sub">
        <?php if ($proximaProva): ?>
          <?php 
            $dtEv = new DateTime($proximaProva['data_evento']);
            $hj = new DateTime();
            $diff = $hj->diff($dtEv)->days; // Absolute diff in full days
            if ($dtEv->format('Y-m-d') === $hj->format('Y-m-d')) echo 'Hoje!';
            elseif ($dtEv < $hj) echo 'Hoje!'; // Already handled by SQL usually
            else echo 'em ' . $diff . ' dia' . ($diff !== 1 ? 's' : '');
          ?>
        <?php else: ?>
          Agendada
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- OVERVIEW GRID -->
  <div class="overview-grid">
    
    <!-- ESQUERDA: Gráfico de Volume -->
    <div class="section-box">
      <h3 class="section-title">Volume (6 semanas)</h3>
      <?php 
        $maxKm = 0;
        foreach ($volume6S as $sem) { if ($sem['km_total'] > $maxKm) $maxKm = $sem['km_total']; }
        if ($maxKm == 0) $maxKm = 1; // previne div zeros
      ?>
      <div class="chart-container">
        <?php foreach ($volume6S as $i => $sem): ?>
          <?php $percentH = ($sem['km_total'] / $maxKm) * 100; ?>
          <div class="chart-bar-wrap">
            <span class="chart-val"><?= round($sem['km_total'], 1) ?></span>
            <div class="chart-bar <?= ($i === count($volume6S)-1) ? 'filled' : '' ?>" style="height: <?= max($percentH, 2) ?>%"></div>
            <span class="chart-label"><?= $sem['label'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- DIREITA: Lista de RPEs -->
    <div class="section-box">
      <h3 class="section-title">Últimos RPEs</h3>
      <?php if (empty($ultimosRpes)): ?>
        <p style="color:#888;font-size:.85rem;text-align:center;margin-top:20px;">Nenhum RPE registrado recentemente.</p>
      <?php else: ?>
        <div class="rpe-list">
          <?php foreach ($ultimosRpes as $rpe): ?>
            <?php 
              $val = (float)$rpe['rpe'];
              if ($val <= 4) $corBg = '#dcfce7'; $corTx = '#166534';
              if ($val >= 5 && $val <= 7) { $corBg = '#fef3c7'; $corTx = '#b45309'; }
              if ($val >= 8) { $corBg = '#fee2e2'; $corTx = '#b91c1c'; }
              $partesTit = explode(' — ', $rpe['titulo']);
              $titulo = $partesTit[0];
            ?>
            <div class="rpe-item">
              <div class="rpe-item-info">
                <span class="rpe-item-titulo" title="<?= htmlspecialchars($rpe['titulo']) ?>"><?= htmlspecialchars($titulo) ?></span>
                <span class="rpe-item-data"><?= (new DateTime($rpe['data_treino']))->format('d/m') ?></span>
              </div>
              <span class="rpe-badge" style="background:<?= $corBg ?>;color:<?= $corTx ?>"><?= $val ?>/10</span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- ÚLTIMO TREINO -->
  <div class="section-box">
    <h3 class="section-title">Último Treino Realizado</h3>
    <?php if ($ultimoTreino): ?>
      <div class="ultimo-treino-box">
        <?php 
          $tipoBadgeClass = ($ultimoTreino['tipo'] === 'strava') ? 'ut-strava' : 'ut-badge';
          $tipoTexto = ($ultimoTreino['tipo'] === 'strava') ? 'STRAVA' : (explode(' — ', $ultimoTreino['titulo'])[0] ?? 'Treino');
        ?>
        <div class="<?= $tipoBadgeClass ?>"><?= htmlspecialchars($tipoTexto) ?></div>
        <div class="ut-titulo"><?= htmlspecialchars($ultimoTreino['titulo']) ?></div>
        <div class="ut-data"><?= (new DateTime($ultimoTreino['data_treino']))->format('d de M') ?></div>
        
        <?php 
          $planejado = (float)$ultimoTreino['distancia_planejada_km'];
          $realizado = !empty($ultimoTreino['km_realizado_strava']) ? (float)$ultimoTreino['km_realizado_strava'] : $planejado;
          $eStravaSemPlanejado = ($ultimoTreino['tipo'] === 'strava' && !$planejado && !empty($ultimoTreino['km_realizado_strava']));
          $mostrarComparacao = $planejado > 0;
        ?>

        <?php if ($mostrarComparacao): ?>
          <?= getStravaProgressBarHtml($planejado, $realizado) ?>
        <?php elseif ($eStravaSemPlanejado): ?>
          <div style="display:inline-flex;align-items:center;gap:6px;background:#fff3f0;border-radius:20px;padding:5px 12px;margin-top:8px;font-size:0.78rem;font-weight:700;color:#FC4C02;">
            📍 <?= rtrim(rtrim(number_format((float)$ultimoTreino['km_realizado_strava'], 1, ',', '.'), '0'), ',') ?>km realizados
          </div>
        <?php else: ?>
          <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(29,185,84,0.1);border-radius:20px;padding:5px 12px;margin-top:8px;font-size:0.78rem;font-weight:700;color:#166534;">
            ✅ Realizado
          </div>
        <?php endif; ?>

        <?php if (!empty($ultimoTreino['strava_activity_id'])): ?>
          <div style="margin-top: 12px;">
            <a href="https://www.strava.com/activities/<?= $ultimoTreino['strava_activity_id'] ?>" target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;background:#FC4C02;color:#fff;border-radius:20px;padding:6px 13px;font-size:0.76rem;font-weight:700;text-decoration:none;font-family:'Outfit',sans-serif;">
              <svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:#fff;"><path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7 13.828h4.169"/></svg>
              Ver no Strava
            </a>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <p style="color:#888;font-size:.9rem;">Nenhum treino realizado ainda.</p>
    <?php endif; ?>
  </div>

</section>

<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>
