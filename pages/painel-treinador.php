<?php
ob_start();
// ==========================================================
// STRIVELY — pages/painel-treinador.php
// Painel geral do treinador
// ==========================================================

$only_session = true;
require_once '../components/header.php';

if (!isset($_SESSION['id']) || $_SESSION['perfil'] !== 'treinador') {
  header('Location: /index.php');
  exit();
}

require_once '../config/conexao.php';
require_once '../includes/calculos-treinador.php';

$stmt = $pdo->prepare("SELECT id, nome, foto, cidade, email FROM usuarios WHERE treinador_id = ? AND status_vinculo = 'aceito' ORDER BY nome ASC");
$stmt->execute([$_SESSION['id']]);
$alunosBrutos = $stmt->fetchAll();

// Aggregated stats
$totalAtivos = 0;
$totalInativos = 0;
$totalPlanejadosGeral = 0;
$totalRealizadosGeral = 0;
$kmSemanaGeral = 0;
$alunosComAlerta = 0;

$alunosProcessados = [];
$proximasProvas = [];

$hj = new DateTime();
$hj->setTime(0, 0, 0);

foreach ($alunosBrutos as $ab) {
    $a_id = $ab['id'];
    
    // Inatividade
    $inativo = isAlunoInativo($pdo, $a_id);
    if ($inativo) $totalInativos++;
    else $totalAtivos++;
    
    // Adesão Semanal
    $adesao = getAdesaoSemanal($pdo, $a_id);
    $totalPlanejadosGeral += $adesao['planejados'];
    $totalRealizadosGeral += $adesao['realizados'];
    
    // Volume Semanal
    $vol = getVolumeSemanal6Semanas($pdo, $a_id);
    $kmSemanaGeral += $vol[5]['km_total']; // 5 is current week
    
    // Alertas
    $alertas = getAlertas($pdo, $a_id);
    if (!empty($alertas)) {
        $alunosComAlerta++;
    }
    
    // RPE Medio
    $rpe = getRpeMedio($pdo, $a_id, 14);
    
    // Provas
    $prov = getProximaProva($pdo, $a_id);
    if ($prov) {
        $dtEvStr = $prov['data_evento'];
        $dtEvObj = new DateTime($dtEvStr);
        $dtEvObj->setTime(0, 0, 0);
        $diff = $hj->diff($dtEvObj)->days;
        
        if ($dtEvObj >= $hj && $diff <= 14) {
             $proximasProvas[] = [
                 'aluno_nome' => $ab['nome'],
                 'evento_nome' => $prov['evento_nome'] ?? $prov['nome_manual'],
                 'data_evento' => $prov['data_evento'],
                 'dias' => $diff
             ];
        }
    }
    
    // Sorting info
    $hasAlertAtencao = false;
    foreach ($alertas as $al) {
        if ($al['severidade'] === 'atencao') $hasAlertAtencao = true;
    }
    
    $prioridade = 0;
    if ($inativo) $prioridade = 2;
    elseif ($hasAlertAtencao) $prioridade = 1;
    
    $ab['prioridade'] = $prioridade;
    $ab['adesao'] = $adesao;
    $ab['rpe'] = $rpe;
    $ab['alertas'] = $alertas;
    $ab['inativo'] = $inativo;
    $ab['has_alert_atencao'] = $hasAlertAtencao;
    
    $alunosProcessados[] = $ab;
}

// Sort Provas
usort($proximasProvas, function($a, $b) {
    return strcmp($a['data_evento'], $b['data_evento']);
});
if (count($proximasProvas) > 5) {
    $proximasProvas = array_slice($proximasProvas, 0, 5);
}

// Sort Alunos
usort($alunosProcessados, function($a, $b) {
    if ($a['prioridade'] != $b['prioridade']) return $b['prioridade'] <=> $a['prioridade'];
    return strcmp($a['nome'], $b['nome']);
});

$percentualGeral = $totalPlanejadosGeral > 0 ? ($totalRealizadosGeral / $totalPlanejadosGeral) * 100 : null;

unset($only_session);
$tituloPagina = "Painel Geral";
include '../components/head.php';
include '../components/header.php';
?>

<style>
.painel-wrap { max-width: 1200px; margin: 40px auto; padding: 0 24px 100px; }
.painel-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; flex-wrap: wrap; gap: 16px; }
.painel-header h1 { font-family: 'Bebas Neue', sans-serif; font-size: 2.2rem; letter-spacing: 2px; margin: 0; color: #111; }
.painel-header .voltar { display: flex; align-items: center; gap: 6px; color: #555; text-decoration: none; font-size: .85rem; font-weight: 500; transition: color .2s; }
.painel-header .voltar:hover { color: var(--green); }

/* STATS CARDS */
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px; }
@media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .stats-grid { grid-template-columns: 1fr; } }
.stat-card { background: #fff; border-radius: 16px; padding: 22px; border: 1px solid rgba(0,0,0,.06); box-shadow: 0 4px 12px rgba(0,0,0,.03); display: flex; flex-direction: column; gap: 6px; }
.stat-icon { margin-bottom: 4px; }
.stat-icon svg { width: 26px; height: 26px; fill: #777; }
.stat-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #999; }
.stat-valor { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; letter-spacing: 1px; color: #111; line-height: 1.1; margin: 2px 0; }
.stat-sub { font-size: .8rem; color: #777; font-weight: 500; }

/* SECTIONS */
.section-box { background: #fff; border-radius: 16px; padding: 22px; border: 1px solid rgba(0,0,0,.06); box-shadow: 0 4px 12px rgba(0,0,0,.03); margin-bottom: 32px; }
.section-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; letter-spacing: 1px; margin: 0 0 16px; color: #111; border-bottom: 1px solid #f0f0f0; padding-bottom: 12px; }

/* PROVAS LIST */
.provas-list { display: flex; flex-direction: column; gap: 8px; }
.prova-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: #fafafa; border-radius: 10px; border: 1px solid #eee; }
.prova-aluno { font-weight: 700; font-size: .85rem; color: #111; min-width: 120px; }
.prova-nome { font-size: .85rem; color: #555; flex: 1; margin: 0 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.prova-data { font-size: .8rem; color: #1DB954; font-weight: 700; white-space: nowrap; }

/* ALUNOS GRID */
.alunos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
.aluno-card { background: #fff; border-radius: 14px; border: 1px solid #eee; padding: 18px; transition: all .2s; display: flex; flex-direction: column; position: relative; text-decoration: none; color: inherit; }
.aluno-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.06); border-color: #ddd; }
.ac-header { display: flex; align-items: center; gap: 14px; margin-bottom: 12px; }
.ac-foto { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid var(--green); flex-shrink: 0; }
.ac-foto-padrao { width: 44px; height: 44px; border-radius: 50%; background: #f0f0f0; border: 2px solid var(--green); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ac-foto-padrao svg { width: 22px; height: 22px; fill: #aaa; }
.ac-info h3 { font-weight: 700; font-size: 1rem; color: #111; margin: 0 0 2px; }
.ac-info span { font-size: .75rem; color: #777; }

.ac-stats { display: flex; gap: 12px; font-size: .8rem; color: #555; background: #f9f9f9; padding: 10px; border-radius: 8px; margin-bottom: 12px; }
.ac-stat-item { display: flex; flex-direction: column; gap: 2px; }
.ac-stat-val { font-weight: 700; color: #111; }

.ac-badges { display: flex; gap: 6px; flex-wrap: wrap; }
.ac-badge { padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: .7rem; display: inline-flex; align-items: center; gap: 4px; }
.ac-badge.inativo { background: #fee2e2; color: #b91c1c; }
.ac-badge.atencao { background: #fef3c7; color: #b45309; }

@media(max-width: 600px) {
  .prova-item { flex-direction: column; align-items: flex-start; gap: 4px; }
  .prova-aluno { min-width: auto; }
  .prova-nome { margin: 0; white-space: normal; }
  .ac-header { align-items: flex-start; }
}
</style>

<div class="painel-wrap">
  
  <div class="painel-header">
    <a href="/index.php" class="voltar">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
      Home
    </a>
    <h1>Painel Geral</h1>
    <a href="/pages/alunos.php" class="voltar" style="color:#1DB954;border: 1px solid #1DB954;padding:8px 14px;border-radius:20px;">
      Gerenciar alunos →
    </a>
  </div>

  <!-- STATS -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div>
      <div class="stat-label">Alunos Ativos</div>
      <div class="stat-valor"><?= htmlspecialchars($totalAtivos, ENT_QUOTES, 'UTF-8') ?> <span style="font-size: 1rem; color: #999;">/ <?= count($alunosBrutos) ?></span></div>
      <div class="stat-sub"><?= htmlspecialchars($totalInativos, ENT_QUOTES, 'UTF-8') ?> inativos esta semana</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg></div>
      <div class="stat-label">Adesão Geral (Semana)</div>
      <div class="stat-valor">
        <?= $percentualGeral !== null ? round($percentualGeral) . '%' : '—' ?>
      </div>
      <div class="stat-sub"><?= htmlspecialchars($totalRealizadosGeral, ENT_QUOTES, 'UTF-8') ?> treinos realizados</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><svg viewBox="0 0 24 24"><path d="M13.5 5.5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zM9.8 8.9L7 23h2.1l1.8-8 2.1 2v6h2v-7.5l-2.1-2 .6-3C14.8 12 16.8 13 19 13v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1L6 8.3V13h2V9.6l1.8-.7"/></svg></div>
      <div class="stat-label">Volume Geral (Semana)</div>
      <div class="stat-valor"><?= number_format($kmSemanaGeral, 0, ',', '.') ?> km</div>
      <div class="stat-sub">Soma de todos os alunos</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.93 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6C8 8.15 9.51 6 12 6s4 2.15 4 5v6z"/></svg></div>
      <div class="stat-label">Com Alertas</div>
      <div class="stat-valor"><?= htmlspecialchars($alunosComAlerta, ENT_QUOTES, 'UTF-8') ?></div>
      <div class="stat-sub">Exigem atenção</div>
    </div>
  </div>

  <!-- PROVAS -->
  <?php if (!empty($proximasProvas)): ?>
    <div class="section-box">
      <h3 class="section-title">Provas dos Alunos (Próximos 14 dias)</h3>
      <div class="provas-list">
        <?php foreach ($proximasProvas as $p): ?>
          <div class="prova-item">
            <span class="prova-aluno"><?= htmlspecialchars(explode(' ', $p['aluno_nome'])[0]) ?></span>
            <span class="prova-nome"><?= htmlspecialchars($p['evento_nome']) ?></span>
            <span class="prova-data">
              <?php if ($p['dias'] == 0) echo 'Hoje!';
                    else echo 'em ' . $p['dias'] . ' dia(s)'; ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- ALUNOS LISTA -->
  <h3 class="section-title" style="margin-top: 40px; border-bottom: none; margin-bottom: 0;">Situação dos Alunos</h3>
  <p style="color: #666; font-size: .85rem; margin-bottom: 20px;">Ordem por prioridade de atenção e ordem alfabética.</p>
  
  <?php if (empty($alunosProcessados)): ?>
    <div style="text-align:center;padding:40px;color:#888;">Nenhum aluno vinculado ainda.</div>
  <?php else: ?>
    <div class="alunos-grid">
      <?php foreach ($alunosProcessados as $ab): ?>
        <a href="/pages/aluno-overview.php?aluno_id=<?= htmlspecialchars($ab['id'], ENT_QUOTES, 'UTF-8') ?>" class="aluno-card">
          <div class="ac-header">
            <?php if (!empty($ab['foto'])): ?>
              <img src="<?= htmlspecialchars($ab['foto']) ?>" alt="Foto" class="ac-foto">
            <?php else: ?>
              <div class="ac-foto-padrao"><svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg></div>
            <?php endif; ?>
            <div class="ac-info">
              <h3><?= htmlspecialchars($ab['nome']) ?></h3>
              <span><?= htmlspecialchars($ab['cidade'] ?? '') ?></span>
            </div>
          </div>
          
          <div class="ac-stats">
            <div class="ac-stat-item">
              <span class="ac-stat-val"><?= htmlspecialchars($ab['adesao']['realizados'], ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars($ab['adesao']['planejados'], ENT_QUOTES, 'UTF-8') ?></span>
              <span>treinos</span>
            </div>
            <div style="width: 1px; background: #ddd; margin: 0 4px;"></div>
            <div class="ac-stat-item">
              <span class="ac-stat-val"><?= $ab['rpe'] !== null ? number_format($ab['rpe'], 1) : '—' ?></span>
              <span>RPE méd.</span>
            </div>
          </div>

          <div class="ac-badges">
            <?php if ($ab['inativo']): ?>
              <span class="ac-badge inativo">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z"/></svg>
                Inativo
              </span>
            <?php endif; ?>

            <?php if (!empty($ab['alertas']) && $ab['has_alert_atencao']): ?>
              <span class="ac-badge atencao">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                Alerta
              </span>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>
