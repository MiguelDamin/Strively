<?php
ob_start();
$only_session = true;
require_once '../components/header.php';
require_once '../components/strava-progress-bar.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id']) || $_SESSION['perfil'] !== 'treinador') {
  header('Location: /index.php');
  exit();
}

$aluno_id = (int)($_GET['aluno_id'] ?? 0);
if (!$aluno_id) { header('Location: /pages/alunos.php'); exit(); }

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND treinador_id = ?");
$stmt->execute([$aluno_id, $_SESSION['id']]);
$aluno = $stmt->fetch();
if (!$aluno) { header('Location: /pages/alunos.php'); exit(); }

// Treinos do treinador + auto-treinos do aluno
$stmt = $pdo->prepare("SELECT * FROM treinos WHERE aluno_id = ? ORDER BY data_treino ASC");
$stmt->execute([$aluno_id]);
$treinos = $stmt->fetchAll();

$treinos_por_data = [];
foreach ($treinos as $t) {
  $t['_tipo_item'] = 'treino';
  $t['_proprio'] = ((int)$t['treinador_id'] === $aluno_id);
  $treinos_por_data[$t['data_treino']][] = $t;
}

// Eventos do aluno
$stmt = $pdo->prepare("
  SELECT ue.id AS ue_id, ue.evento_id, ue.nome_manual, ue.data_evento,
         e.nome AS evento_nome, e.cidade AS evento_cidade, e.distancias
  FROM usuario_eventos ue
  LEFT JOIN eventos e ON e.id = ue.evento_id
  WHERE ue.usuario_id = ?
  ORDER BY ue.data_evento ASC
");
$stmt->execute([$aluno_id]);
$eventos_aluno = $stmt->fetchAll();

foreach ($eventos_aluno as $ev) {
  $ev['_tipo_item'] = 'evento';
  $treinos_por_data[$ev['data_evento']][] = $ev;
}

$treinos_json = json_encode($treinos_por_data);

$aba = $_GET['aba'] ?? 'calendario';

unset($only_session);
$tituloPagina = "Treinos de " . htmlspecialchars($aluno['nome']);
include '../components/head.php';
include '../components/header.php';
?>
<style>
.treinos-page{max-width:1000px;margin:0 auto;padding:40px 24px 100px}

/* Header do aluno */
.aluno-header{display:flex;align-items:center;gap:16px;margin-bottom:32px;flex-wrap:wrap}
.aluno-header a.voltar{color:var(--text-secondary,#555);font-size:.85rem;display:flex;align-items:center;gap:5px;text-decoration:none;transition:color .2s;margin-right:4px}
.aluno-header a.voltar:hover{color:var(--green)}
.aluno-foto{width:52px;height:52px;border-radius:50%;object-fit:cover;border:2.5px solid var(--green);flex-shrink:0;box-shadow:0 0 0 4px rgba(29,185,84,.1)}
.aluno-foto-padrao{width:52px;height:52px;border-radius:50%;background:#f0f0f0;border:2.5px solid var(--green);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 0 0 4px rgba(29,185,84,.1)}
.aluno-foto-padrao svg{width:28px;height:28px;fill:#aaa}
.aluno-info{flex:1;min-width:0}
.aluno-info h1{font-family:'Bebas Neue',sans-serif;font-size:1.9rem;letter-spacing:2px;line-height:1;margin:0;color:var(--text-primary,#111)}
.aluno-info span{font-size:.82rem;color:var(--text-secondary,#555)}

/* Abas */
.abas{display:flex;gap:4px;background:#fff;border-radius:12px;padding:5px;box-shadow:0 1px 4px rgba(0,0,0,.08);border:1px solid rgba(0,0,0,.06);margin-bottom:24px;width:fit-content}
.aba-btn{display:flex;align-items:center;gap:7px;padding:9px 20px;border-radius:9px;border:none;background:transparent;font-family:'Outfit',sans-serif;font-size:.875rem;font-weight:600;color:#777;cursor:pointer;text-decoration:none;transition:all .18s;white-space:nowrap}
.aba-btn:hover{background:#f5f5f5;color:#111}
.aba-btn.ativa{background:var(--green);color:#fff;box-shadow:0 2px 8px rgba(29,185,84,.25)}
.aba-btn svg{width:16px;height:16px;fill:currentColor;flex-shrink:0}

/* Mensagem */
.msg-sucesso{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;font-size:.86rem;color:#166534;margin-bottom:20px}

/* Calendário */
.calendario-wrap{background:#fff;border-radius:16px;border:1px solid rgba(0,0,0,.07);box-shadow:0 2px 12px rgba(0,0,0,.06);padding:28px}
.cal-nav{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px}
.cal-nav h2{font-family:'Bebas Neue',sans-serif;font-size:1.5rem;letter-spacing:1.5px;margin:0;color:var(--text-primary,#111)}
.cal-nav-btn{width:34px;height:34px;border-radius:8px;border:1.5px solid #e0e0e0;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .18s}
.cal-nav-btn:hover{border-color:var(--green);background:rgba(29,185,84,.06)}
.cal-nav-btn svg{width:17px;height:17px;fill:#555}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:5px}
.cal-dia-nome{text-align:center;font-size:.7rem;font-weight:700;color:#aaa;padding:6px 0;text-transform:uppercase;letter-spacing:.6px}
.cal-dia{aspect-ratio:1;border-radius:10px;padding:5px;cursor:pointer;transition:all .16s;display:flex;flex-direction:column;align-items:center;min-height:48px;border:1.5px solid transparent}
.cal-dia:hover{background:#f5f5f5;border-color:#ddd}
.cal-dia.vazio{cursor:default;pointer-events:none}
.cal-dia.hoje{border-color:var(--green);background:rgba(29,185,84,.06)}
.cal-dia.tem-treino{background:#eff8ff;border-color:#bae0fb}
.cal-dia.tem-treino:hover{background:#dff0fd;border-color:#7dcbf5}
.cal-dia-num{font-size:.82rem;font-weight:600;line-height:1;margin-bottom:3px;color:#111}
.cal-dia.vazio .cal-dia-num{color:#ddd}
.cal-bolinhas{display:flex;gap:3px;flex-wrap:wrap;justify-content:center;margin-top:1px}
.cal-bolinha{width:6px;height:6px;border-radius:50%;background:var(--green)}
.cal-bolinha.evento{background:#DAA520}
.cal-dia.tem-evento{background:rgba(218,165,32,.07);border-color:#DAA520}
.cal-dia.tem-evento:hover{background:rgba(218,165,32,.12);border-color:#B8860B}
.cal-dia.tem-treino.tem-evento{background:linear-gradient(135deg,#eff8ff 50%,rgba(218,165,32,.1) 50%);border-color:#DAA520}
.cal-legenda{display:flex;gap:16px;margin-top:14px;font-size:.78rem;color:#888}
.cal-legenda span{display:flex;align-items:center;gap:5px}
.cal-legenda i{display:inline-block;width:8px;height:8px;border-radius:50%}

/* Modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:500;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;visibility:hidden;transition:opacity .2s,visibility .2s}
.modal-overlay.aberto{opacity:1;visibility:visible}
.modal-box{background:#fff;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.2);width:100%;max-width:480px;max-height:90vh;overflow-y:auto;transform:translateY(10px) scale(.98);transition:transform .2s}
.modal-overlay.aberto .modal-box{transform:translateY(0) scale(1)}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:20px 22px 16px;border-bottom:1px solid #f0f0f0;position:sticky;top:0;background:#fff;border-radius:18px 18px 0 0;z-index:1}
.modal-header h3{font-family:'Bebas Neue',sans-serif;font-size:1.35rem;letter-spacing:1px;margin:0;color:#111}
.modal-fechar{width:30px;height:30px;border-radius:8px;border:none;background:#f5f5f5;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .18s}
.modal-fechar:hover{background:#e8e8e8}
.modal-fechar svg{width:15px;height:15px;fill:#777}
.modal-body{padding:18px 22px 22px}
.modal-treino-item{background:#f8f8f8;border-radius:12px;padding:14px 16px;margin-bottom:10px;border-left:4px solid #e0e0e0}
.modal-treino-item.evento-item{border-left-color:#DAA520;background:#FFFCF0}
.modal-treino-item:last-child{margin-bottom:0}
.modal-treino-tipo{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#166534;background:#dcfce7;padding:3px 8px;border-radius:5px;display:inline-block;margin-bottom:6px}
.modal-treino-tipo.evento-badge{color:#B8860B;background:#FFF8DC}
.modal-treino-titulo{font-weight:700;font-size:.94rem;margin-bottom:4px;color:#111}
.modal-treino-desc{font-size:.82rem;color:#666;line-height:1.5;margin-bottom:10px}
.modal-treino-acoes{display:flex;justify-content:flex-end}
.btn-remover-treino{background:#fff0f0;border:none;color:#c0392b;border-radius:7px;padding:6px 14px;font-size:.8rem;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;transition:background .18s}
.btn-remover-treino:hover{background:#ffd5d5}

/* Planilha */
.planilha-wrap{display:flex;flex-direction:column;gap:10px}
.planilha-item{background:#fff;border-radius:14px;padding:16px 20px;border:1px solid rgba(0,0,0,.07);box-shadow:0 1px 4px rgba(0,0,0,.05);display:flex;align-items:center;gap:16px;transition:box-shadow .18s;border-left:4px solid transparent}
.planilha-item:hover{box-shadow:0 4px 16px rgba(0,0,0,.09)}
.planilha-item.realizado{background:#f0fff4;border-left-color:var(--green)}
.planilha-item.evento-item{border-left-color:#DAA520;background:#FFFCF0}
.planilha-data{min-width:58px;text-align:center;background:#f5f5f5;border-radius:10px;padding:8px 6px;flex-shrink:0}
.planilha-data .dia{font-family:'Bebas Neue',sans-serif;font-size:1.7rem;line-height:1;color:var(--green)}
.planilha-item.evento-item .planilha-data .dia{color:#DAA520}
.planilha-data .mes{font-size:.68rem;font-weight:700;text-transform:uppercase;color:#999;letter-spacing:.5px}
.planilha-info{flex:1;min-width:0}
.planilha-tipo-badge{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#166534;background:#dcfce7;padding:2px 8px;border-radius:5px;display:inline-block;margin-bottom:4px}
.planilha-tipo-badge.evento-badge{color:#B8860B;background:#FFF8DC}
.planilha-titulo{font-weight:700;font-size:.93rem;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.planilha-desc{font-size:.8rem;color:#888;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.badge-proprio{background:#e0f2fe;color:#0369a1;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:5px;display:inline-block;margin-left:4px}
.planilha-acoes{flex-shrink:0}
.vazio-wrap{text-align:center;padding:60px 24px;color:#aaa}
.vazio-wrap .vazio-icone{font-size:2.8rem;margin-bottom:12px}
.vazio-wrap p{font-size:.9rem;line-height:1.6;color:#999}

/* Modal de adicionar treino */
.modal-form .form-grupo{margin-bottom:14px}
.modal-form label{font-size:.82rem;font-weight:600;display:block;margin-bottom:5px;color:#333}
.modal-form input,.modal-form select,.modal-form textarea{width:100%;background:#f5f6f5;border:1.5px solid #e0e0e0;border-radius:10px;padding:10px 13px;font-family:'Outfit',sans-serif;font-size:.9rem;outline:none;transition:border-color .18s,box-shadow .18s;box-sizing:border-box;color:#111}
.modal-form input:focus,.modal-form select:focus,.modal-form textarea:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(29,185,84,.12);background:#fff}
.modal-form textarea{resize:vertical;min-height:80px}
.modal-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.campo-outro{display:none;margin-top:8px}
.campo-outro.visivel{display:block}

/* Responsivo */
@media(max-width:640px){
  .treinos-page{padding:20px 14px 100px}
  .aluno-header{gap:10px;margin-bottom:20px}
  .aluno-header .btn-primary{width:100%;justify-content:center;order:3}
  .abas{width:100%}
  .aba-btn{flex:1;justify-content:center;padding:9px 8px;font-size:.78rem;gap:4px}
  .aba-btn svg{display:none}
  .calendario-wrap{padding:14px 10px}
  .cal-nav h2{font-size:1.2rem}
  .cal-dia{min-height:36px;padding:3px;border-radius:7px}
  .cal-dia-num{font-size:.73rem}
  .cal-bolinha{width:5px;height:5px}
  .planilha-item{flex-wrap:wrap;padding:13px 14px;gap:10px}
  .planilha-acoes{width:100%;display:flex;justify-content:flex-end}
  .modal-form-grid{grid-template-columns:1fr}
  .modal-box{border-radius:14px}
}
</style>

<section class="treinos-page">

  <!-- CABEÇALHO DO ALUNO -->
  <?php 
  $header_link_voltar = '/pages/alunos.php';
  $header_label_voltar = 'Meus alunos';
  $header_foto = $aluno['foto'] ?? '';
  $header_nome = $aluno['nome'] ?? '';
  $header_cidade = $aluno['cidade'] ?? 'Sem cidade';
  $header_foto_link = '/pages/perfil-publico.php?id=' . (int)$aluno['id'];
  $header_acoes_html = '
    <button class="btn-primary" onclick="abrirModalAdicionar()" style="margin-left:auto;">
      + Adicionar Treino
    </button>
  ';
  include '../components/header-perfil-nome.php';
  ?>

  <?php if (isset($_GET['msg'])): ?>
    <div class="msg-sucesso">
      <?php
        if ($_GET['msg'] === 'criado')   echo '✅ Treino adicionado com sucesso!';
        if ($_GET['msg'] === 'removido') echo '🗑️ Treino removido.';
      ?>
    </div>
  <?php endif; ?>

  <!-- ABAS -->
  <div class="abas">
    <a href="?aba=calendario&aluno_id=<?= $aluno_id ?>" class="aba-btn <?= $aba === 'calendario' ? 'ativa' : '' ?>">
      <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5C3.9 3 3 3.9 3 5v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
      Calendário
    </a>
    <a href="?aba=planilha&aluno_id=<?= $aluno_id ?>" class="aba-btn <?= $aba === 'planilha' ? 'ativa' : '' ?>">
      <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
      Planilha
    </a>
  </div>

  <!-- ABA: CALENDÁRIO -->
  <?php if ($aba === 'calendario'): ?>
  <div class="calendario-wrap">
    <div class="cal-nav">
      <button class="cal-nav-btn" onclick="mudarMes(-1)">
        <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
      </button>
      <h2 id="cal-titulo"></h2>
      <button class="cal-nav-btn" onclick="mudarMes(1)">
        <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
      </button>
    </div>
    <div class="cal-grid">
      <?php foreach(['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $dn): ?>
        <div class="cal-dia-nome"><?= $dn ?></div>
      <?php endforeach; ?>
    </div>
    <div class="cal-grid" id="cal-dias"></div>
    <div class="cal-legenda">
      <span><i style="background:var(--green)"></i> Com treino</span>
      <span><i style="background:#DAA520"></i> Evento</span>
      <span><i style="background:transparent;border:1.5px solid var(--green)"></i> Hoje</span>
    </div>
  </div>
  <?php endif; ?>

  <!-- ABA: PLANILHA -->
  <?php if ($aba === 'planilha'): ?>
  <div class="planilha-wrap">
    <?php
      $todos_planilha = [];
      foreach ($treinos as $t) { $t['_tipo_item'] = 'treino'; $t['_proprio'] = ((int)$t['treinador_id'] === $aluno_id); $todos_planilha[] = $t; }
      foreach ($eventos_aluno as $ev) { $ev['_tipo_item'] = 'evento'; $todos_planilha[] = $ev; }
      usort($todos_planilha, fn($a, $b) => ($b['data_treino'] ?? $b['data_evento']) <=> ($a['data_treino'] ?? $a['data_evento']));
    ?>
    <?php if (empty($todos_planilha)): ?>
      <div class="vazio-wrap">
        <div class="vazio-icone">📋</div>
        <p>Nenhum treino cadastrado ainda.<br>Clique em <strong>+ Adicionar Treino</strong> para começar.</p>
      </div>
    <?php else: ?>
      <?php $meses = ["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"]; ?>
      <?php foreach ($todos_planilha as $item): ?>
        <?php if ($item['_tipo_item'] === 'evento'): ?>
          <?php $dt = new DateTime($item['data_evento']); ?>
          <div class="planilha-item evento-item">
            <div class="planilha-data">
              <div class="dia"><?= $dt->format('d') ?></div>
              <div class="mes"><?= $meses[(int)$dt->format('m') - 1] ?></div>
            </div>
            <div class="planilha-info">
              <div class="planilha-tipo-badge evento-badge">🏅 Evento</div>
              <div class="planilha-titulo"><?= htmlspecialchars($item['evento_nome'] ?? $item['nome_manual'] ?? 'Evento') ?></div>
              <?php if (!empty($item['evento_cidade'])): ?><div class="planilha-desc">📍 <?= htmlspecialchars($item['evento_cidade']) ?></div><?php endif; ?>
            </div>
          </div>
        <?php else: ?>
          <?php
            $dt = new DateTime($item['data_treino']);
            $realizado = ($item['status'] === 'realizado');
            $temStravaLink = !empty($item['strava_activity_id']);
            $temPlanejado = !empty($item['distancia_planejada_km']);
            $eStravaSemPlanejado = ($item['tipo'] === 'strava' && !$temPlanejado && !empty($item['km_realizado_strava']));
            $mostrarComparacao = $temPlanejado && $realizado;
            if ($mostrarComparacao) {
              $planejado  = (float)$item['distancia_planejada_km'];
              $realizado_ = !empty($item['km_realizado_strava']) ? (float)$item['km_realizado_strava'] : $planejado;
            }
          ?>
          <div class="planilha-item<?= $realizado ? ' realizado' : '' ?>"
               onclick="abrirDetalhesTreino(
                   '<?= htmlspecialchars($item['titulo'] ?? '', ENT_QUOTES) ?>',
                   '<?= htmlspecialchars($item['descricao'] ?? '', ENT_QUOTES) ?>',
                   '<?= htmlspecialchars($item['tipo'] ?? '') ?>',
                   '<?= date('d/m/Y', strtotime($item['data_treino'])) ?>',
                   '<?= $item['status'] ?>',
                   <?= !empty($item['treinador_id']) ? 'true' : 'false' ?>,
                   '<?= !empty($item['distancia_planejada_km']) ? (float)$item['distancia_planejada_km'] : '' ?>',
                   '<?= !empty($item['km_realizado_strava']) ? (float)$item['km_realizado_strava'] : '' ?>',
                   '<?= htmlspecialchars($item['strava_activity_id'] ?? '') ?>',
                   '<?= htmlspecialchars($item['rpe'] ?? '') ?>',
                   '<?= htmlspecialchars($item['duracao_minutos'] ?? '') ?>'
               )"
               style="cursor: pointer;">
            <div class="planilha-data">
              <div class="dia"><?= $dt->format('d') ?></div>
              <div class="mes"><?= $meses[(int)$dt->format('m') - 1] ?></div>
            </div>
            <div class="planilha-info">
              <?php if (isset($item['tipo']) && $item['tipo'] === 'strava'): ?>
                <div style="background:#FC4C02;color:#fff;font-size:0.72rem;font-weight:700;padding:3px 9px;border-radius:20px;letter-spacing:0.5px;font-family:'Outfit',sans-serif;display:inline-block;margin-bottom:5px;">STRAVA</div>
              <?php else: ?>
                <?php $partes = explode(' — ', $item['titulo'], 2); ?>
                <div class="planilha-tipo-badge"><?= htmlspecialchars($partes[0]) ?></div>
              <?php endif; ?>
              <?php if ($item['_proprio']): ?><span class="badge-proprio">Auto-treino</span><?php endif; ?>
              <div class="planilha-titulo"><?= htmlspecialchars($item['titulo']) ?></div>
              <?php if (!empty($item['descricao'])): ?><div class="planilha-desc"><?= htmlspecialchars($item['descricao']) ?></div><?php endif; ?>

              <?php if ($mostrarComparacao): ?>
              <?= getStravaProgressBarHtml($planejado, $realizado_) ?>
              <?php elseif ($eStravaSemPlanejado): ?>
              <div style="display:inline-flex;align-items:center;gap:6px;background:#fff3f0;border-radius:20px;padding:5px 12px;margin-top:8px;font-size:0.78rem;font-weight:700;color:#FC4C02;">
                📍 <?= rtrim(rtrim(number_format((float)$item['km_realizado_strava'], 1, ',', '.'), '0'), ',') ?>km realizados
              </div>
              <?php elseif ($realizado): ?>
              <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(29,185,84,0.1);border-radius:20px;padding:5px 12px;margin-top:8px;font-size:0.78rem;font-weight:700;color:#166534;">
                ✅ Realizado
              </div>
              <?php endif; ?>

              <?php if ($temStravaLink): ?>
              <a href="https://www.strava.com/activities/<?= $item['strava_activity_id'] ?>" target="_blank"
                 style="display:inline-flex;align-items:center;gap:6px;background:#FC4C02;color:#fff;border-radius:20px;padding:6px 13px;font-size:0.76rem;font-weight:700;text-decoration:none;font-family:'Outfit',sans-serif;margin-top:8px;">
                <svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:#fff;"><path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7 13.828h4.169"/></svg>
                Ver no Strava
              </a>
              <?php endif; ?>
            </div>
            <div class="planilha-acoes">
              <?php if (!$item['_proprio']): ?>
              <form action="/actions/action-remover-treino.php" method="POST" onsubmit="removerTreinoAjax(event, this); return false;">
                <input type="hidden" name="treino_id" value="<?= (int)$item['id'] ?>">
                <input type="hidden" name="aluno_id"  value="<?= $aluno_id ?>">
                <input type="hidden" name="aba"        value="planilha">
                <button type="submit" class="btn-remover-treino">Remover</button>
              </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</section>

<!-- MODAL: TREINOS DO DIA -->
<div class="modal-overlay" id="modalDia" onclick="fecharModalSeFora(event,'modalDia')">
  <div class="modal-box">
    <div class="modal-header">
      <h3 id="modalDia-titulo">Treinos do dia</h3>
      <button class="modal-fechar" onclick="fecharModal('modalDia')">
        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
    </div>
    <div class="modal-body" id="modalDia-body"></div>
  </div>
</div>

<!-- MODAL: ADICIONAR TREINO -->
<div class="modal-overlay" id="modalAdicionar" onclick="fecharModalSeFora(event,'modalAdicionar')">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Adicionar Treino</h3>
      <button class="modal-fechar" onclick="fecharModal('modalAdicionar')">
        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form action="/actions/action-adicionar-treino.php" method="POST" class="modal-form">
        <input type="hidden" name="aluno_id" value="<?= $aluno_id ?>">
        <input type="hidden" name="aba"      value="<?= htmlspecialchars($aba) ?>">

        <div class="modal-form-grid">
          <div class="form-grupo">
            <label for="f-data">Data *</label>
            <input type="date" id="f-data" name="data_treino" required>
          </div>
          <div class="form-grupo">
            <label for="f-tipo">Tipo *</label>
            <select id="f-tipo" name="tipo_treino" onchange="toggleOutro(this)" required>
              <option value="">Selecione...</option>
              <option>Corrida Leve</option>
              <option>Intervalado</option>
              <option>Longão</option>
              <option>Regenerativo</option>
              <option>Aquec / Educativos</option>
              <option>Força</option>
              <option>Descanso</option>
              <option value="outro">Outro...</option>
            </select>
          </div>
        </div>

        <div class="form-grupo campo-outro" id="campo-outro">
          <label>Descreva o tipo</label>
          <input type="text" id="f-tipo-outro" name="tipo_outro" placeholder="Ex: Cross Training, Natação...">
        </div>

        <div class="form-grupo">
          <label for="f-titulo">Título *</label>
          <input type="text" id="f-titulo" name="titulo" placeholder="Ex: 3x2km pace 5:20" required>
        </div>

        <div class="form-grupo">
          <label for="f-desc">Descrição / Instruções</label>
          <textarea id="f-desc" name="descricao" placeholder="Detalhe o treino..."></textarea>
        </div>

        <div class="form-grupo">
          <label for="f-distancia">Distância planejada (km) <span style="color:#bbb;font-weight:400;">— opcional</span></label>
          <input type="number" id="f-distancia" name="distancia_planejada_km" step="0.1" min="0" placeholder="Ex: 14 (deixe vazio para treinos sem distância fixa, como tiros)">
        </div>

        <button type="submit" class="btn-primary btn-full" style="margin-top:6px">
          Salvar Treino
        </button>
      </form>
    </div>
  </div>
</div>

<script>
const treinosPorData = <?= $treinos_json ?>;
let anoAtual = new Date().getFullYear(), mesAtual = new Date().getMonth();
const mesesNomes = ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"];

function renderCalendario() {
  const titulo = document.getElementById('cal-titulo');
  const grid   = document.getElementById('cal-dias');
  if (!titulo || !grid) return;
  titulo.textContent = mesesNomes[mesAtual] + ' ' + anoAtual;
  grid.innerHTML = '';
  const primeiro = new Date(anoAtual, mesAtual, 1).getDay();
  const dias     = new Date(anoAtual, mesAtual + 1, 0).getDate();
  const hoje     = new Date();

  for (let i = 0; i < primeiro; i++) {
    const v = document.createElement('div');
    v.className = 'cal-dia vazio';
    v.innerHTML = '<span class="cal-dia-num"></span>';
    grid.appendChild(v);
  }

  for (let d = 1; d <= dias; d++) {
    const cell = document.createElement('div');
    cell.className = 'cal-dia';
    const ds = anoAtual + '-' + String(mesAtual + 1).padStart(2,'0') + '-' + String(d).padStart(2,'0');

    if (d === hoje.getDate() && mesAtual === hoje.getMonth() && anoAtual === hoje.getFullYear())
      cell.classList.add('hoje');

    const items = treinosPorData[ds];
    let hasTreino=false, hasEvento=false;
    if (items) {
      items.forEach(it=>{if(it._tipo_item==='evento')hasEvento=true;else hasTreino=true;});
      cell.addEventListener('click', () => abrirModalDia(ds, items));
    }
    if(hasTreino)cell.classList.add('tem-treino');
    if(hasEvento)cell.classList.add('tem-evento');

    const num = document.createElement('span');
    num.className = 'cal-dia-num';
    num.textContent = d;
    cell.appendChild(num);

    if (items) {
      const bl = document.createElement('div');
      bl.className = 'cal-bolinhas';
      items.forEach(it=>{
        const bo = document.createElement('div');
        bo.className = 'cal-bolinha'+(it._tipo_item==='evento'?' evento':'');
        bl.appendChild(bo);
      });
      cell.appendChild(bl);
    }

    grid.appendChild(cell);
  }
}

function mudarMes(d) {
  mesAtual += d;
  if (mesAtual > 11) { mesAtual = 0; anoAtual++; }
  if (mesAtual < 0)  { mesAtual = 11; anoAtual--; }
  renderCalendario();
}

function abrirModalDia(ds, tt) {
  const [a, m, d] = ds.split('-');
  const mn = ["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"];
  document.getElementById('modalDia-titulo').textContent = d + ' de ' + mn[parseInt(m) - 1] + ' de ' + a;
  const body = document.getElementById('modalDia-body');
  body.innerHTML = '';
  tt.forEach(it => {
    const div = document.createElement('div');
    if (it._tipo_item === 'evento') {
      const nome = it.evento_nome || it.nome_manual || 'Evento';
      div.className = 'modal-treino-item evento-item';
      div.innerHTML = '<div class="modal-treino-tipo evento-badge">🏅 Evento</div><div class="modal-treino-titulo">'+esc(nome)+'</div>'+(it.evento_cidade?'<div class="modal-treino-desc">📍 '+esc(it.evento_cidade)+'</div>':'');
    } else {
      const tipo = it.titulo && it.titulo.includes(' — ') ? it.titulo.split(' — ')[0] : (it.tipo === 'strava' ? 'STRAVA' : 'Treino');
      const proprio = it._proprio;
      const realizado = it.status === 'realizado';

      // Badge tipo
      const badgeTipo = it.tipo === 'strava'
        ? '<div style="background:#FC4C02;color:#fff;font-size:0.72rem;font-weight:700;padding:3px 8px;border-radius:20px;letter-spacing:0.5px;display:inline-block;margin-bottom:6px;">STRAVA</div>'
        : '<div class="modal-treino-tipo">'+esc(tipo)+'</div>';

      // Distância / aderência
      let distHtml = '';
      if (realizado) {
        const planejado = parseFloat(it.distancia_planejada_km) || 0;
        let kmReal      = parseFloat(it.km_realizado_strava);
        if (isNaN(kmReal) || kmReal === 0) kmReal = planejado;

        const eStravaSemPlan = (it.tipo === 'strava' && !planejado && kmReal > 0);
        if (planejado > 0) {
          distHtml = getStravaProgressBarHtmlJS(planejado, kmReal);
        } else if (eStravaSemPlan) {
          const fR = kmReal.toFixed(1).replace('.0','').replace('.',',');
          distHtml = `<div style="display:inline-flex;align-items:center;gap:6px;background:#fff3f0;border-radius:20px;padding:5px 12px;margin-top:8px;font-size:0.78rem;font-weight:700;color:#FC4C02;">📍 ${fR}km realizados</div>`;
        }
      }

      // Link Strava
      const stravaHtml = it.strava_activity_id ? `<a href="https://www.strava.com/activities/${it.strava_activity_id}" target="_blank" style="display:inline-flex;align-items:center;gap:6px;background:#FC4C02;color:#fff;border-radius:20px;padding:5px 12px;font-size:0.75rem;font-weight:700;text-decoration:none;margin-top:8px;"><svg viewBox="0 0 24 24" style="width:12px;height:12px;fill:#fff;"><path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7 13.828h4.169"/></svg>Ver no Strava</a>` : '';

      // Botão remover
      let acoes = '';
      if (!proprio) acoes = '<form method="POST" action="/actions/action-remover-treino.php" onsubmit="removerTreinoAjax(event, this); return false;" style="display:inline"><input type="hidden" name="treino_id" value="'+it.id+'"><input type="hidden" name="aluno_id" value="'+it.aluno_id+'"><input type="hidden" name="aba" value="calendario"><button type="submit" class="btn-remover-treino">Remover</button></form>';

      div.className = 'modal-treino-item' + (realizado ? ' realizado' : '');
      div.innerHTML = badgeTipo
        + (proprio ? '<span class="badge-proprio" style="margin-left:0;margin-bottom:6px;display:inline-block;">Auto-treino</span>' : '')
        + '<div class="modal-treino-titulo">'+esc(it.titulo)+'</div>'
        + (it.descricao ? '<div class="modal-treino-desc">'+esc(it.descricao)+'</div>' : '')
        + distHtml + stravaHtml
        + (acoes ? '<div class="modal-treino-acoes" style="margin-top:10px;">'+acoes+'</div>' : '');
    }
    body.appendChild(div);
  });
  document.getElementById('modalDia').classList.add('aberto');
  lockScroll();
}

function abrirModalAdicionar(d) {
  if (d) document.getElementById('f-data').value = d;
  document.getElementById('modalAdicionar').classList.add('aberto');
  lockScroll();
}

function fecharModal(id) { document.getElementById(id).classList.remove('aberto'); unlockScroll(); }
function fecharModalSeFora(e, id) { if (e.target.id === id) fecharModal(id); }

function toggleOutro(s) {
  const c = document.getElementById('campo-outro');
  const o = document.getElementById('f-tipo-outro');
  if (s.value === 'outro') { c.classList.add('visivel'); o.required = true; }
  else { c.classList.remove('visivel'); o.required = false; }
}

function esc(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { fecharModal('modalDia'); fecharModal('modalAdicionar'); }
});

window.removerTreinoAjax = function(e, form) {
    e.preventDefault();
    if (!confirm('Remover este treino?')) return false;

    const formData = new FormData(form);
    const treinoId = formData.get('treino_id');

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(res => {
        // Remover do objeto global
        for (const date in treinosPorData) {
            if (Array.isArray(treinosPorData[date])) {
                treinosPorData[date] = treinosPorData[date].filter(it => parseInt(it.id) !== parseInt(treinoId) || it._tipo_item === 'evento');
            }
        }
        renderCalendario();
        
        // Remover fisicamente do DOM se estiver na aba planilha
        document.querySelectorAll('.planilha-item').forEach(item => {
            const btnRemove = item.querySelector('.btn-remover-treino');
            let isItemToRemove = false;
            
            if (btnRemove) {
                const parentForm = btnRemove.closest('form');
                if (parentForm && parentForm.querySelector('input[name="treino_id"]') && parentForm.querySelector('input[name="treino_id"]').value == treinoId) {
                    isItemToRemove = true;
                }
            }
            if (isItemToRemove) {
                item.remove();
            }
        });
        
        // Fechar modal do dia se não houver mais treinos lá dentro ou apenas mostrar mensagem
        const diaModalBody = document.getElementById('modalDia-body');
        if (document.getElementById('modalDia').classList.contains('aberto') && diaModalBody) {
             const formInModal = diaModalBody.querySelector('input[name="treino_id"][value="'+treinoId+'"]');
             if (formInModal) {
                 formInModal.closest('.modal-treino-item').remove();
                 if (diaModalBody.children.length === 0) {
                     fecharModal('modalDia');
                 }
             }
        }

        // Mostrar mensagem
        let msg = document.querySelector('.msg-sucesso');
        if (!msg) {
            msg = document.createElement('div');
            msg.className = 'msg-sucesso';
            const abasRow = document.querySelector('.abas');
            if (abasRow) {
                abasRow.parentNode.insertBefore(msg, abasRow);
            }
        }
        msg.innerHTML = '🗑️ Treino removido.';
        msg.style.display = 'block';
        setTimeout(() => msg.style.display = 'none', 3000);
    });
    return false;
};

function abrirDetalhesTreino(titulo, descricao, tipo, data, status, temTreinador, distanciaPlanejada, kmRealizado, stravaId, rpe, duracao) {
    const modal = document.getElementById('modal-treino-detalhe');
    
    const corBadge = tipo === 'strava' ? '#FC4C02' : '#1DB954';
    const textoBadge = tipo === 'strava' ? 'STRAVA' : tipo.toUpperCase();
    document.getElementById('modal-treino-badge').innerHTML = `
        <span style="background:${corBadge};color:#fff;font-size:0.7rem;font-weight:700;padding:4px 10px;border-radius:20px;">${textoBadge}</span>
        ${temTreinador ? '<span style="background:#e8f5e9;color:#1DB954;font-size:0.7rem;font-weight:600;padding:4px 10px;border-radius:20px;margin-left:6px;">Do treinador (você)</span>' : ''}
    `;
    
    document.getElementById('modal-treino-titulo').textContent = titulo;
    document.getElementById('modal-treino-data').textContent = '📅 ' + data;
    
    // Adicionais (RPE/Duração)
    const adicWrapper = document.getElementById('modal-treino-adicionais-wrapper');
    if (!adicWrapper) {
        document.getElementById('modal-treino-data').insertAdjacentHTML('afterend', '<div id="modal-treino-adicionais-wrapper" style="display:flex;gap:12px;margin:10px 0 16px;"></div>');
    }
    const wrap = document.getElementById('modal-treino-adicionais-wrapper');
    wrap.innerHTML = '';
    
    let temAdicional = false;
    if (duracao) {
        temAdicional = true;
        wrap.innerHTML += `<div style="background:#f5f6f5;padding:6px 12px;border-radius:8px;font-size:0.8rem;color:#555;">⏱️ <strong>${duracao} min</strong></div>`;
    }
    if (rpe) {
        temAdicional = true;
        const cores = {1:'#4CAF50',2:'#66BB6A',3:'#9CCC65',4:'#D4E157',5:'#FFEE58', 6:'#FFA726',7:'#FF7043',8:'#EF5350',9:'#E53935',10:'#B71C1C'};
        const corRPE = cores[rpe] || '#8a8a8a';
        wrap.innerHTML += `<div style="background:#f5f6f5;padding:6px 12px;border-radius:8px;font-size:0.8rem;color:#555;">💪 RPE: <strong style="color:${corRPE};">${rpe}/10</strong></div>`;
    }
    wrap.style.display = temAdicional ? 'flex' : 'none';

    const wrapper = document.getElementById('modal-treino-descricao-wrapper');
    if (descricao && descricao.trim() !== '') {
        document.getElementById('modal-treino-descricao').textContent = descricao;
        wrapper.style.display = 'block';
    } else {
        wrapper.style.display = 'none';
    }
    
    let statusHtml = '';
    if (status === 'realizado') {
        const planejado  = parseFloat(distanciaPlanejada) || 0;
        let realizado    = parseFloat(kmRealizado);
        if (isNaN(realizado) || realizado === 0) realizado = planejado;

        const eStravaSemPlan = (tipo === 'strava' && !planejado && realizado > 0);

        if (planejado > 0) {
            statusHtml = getStravaProgressBarHtmlJS(planejado, realizado);
        } else if (eStravaSemPlan) {
            statusHtml = `<div style="display:inline-flex;align-items:center;gap:6px;background:#fff3f0;border-radius:20px;padding:5px 12px;font-size:0.78rem;font-weight:700;color:#FC4C02;margin-bottom:8px;">📍 ${realizado.toFixed(1).replace('.0','').replace('.',',')}km realizados</div>`;
        } else {
            statusHtml = `<div style="display:inline-flex;align-items:center;gap:6px;background:rgba(29,185,84,0.1);border-radius:20px;padding:5px 12px;font-size:0.78rem;font-weight:700;color:#166534;margin-bottom:8px;">✅ Treino realizado</div>`;
        }
    } else {
        statusHtml = '<span style="color:#8a8a8a;font-size:0.9rem;">⏳ Pendente</span>';
    }

    const linkStrava = stravaId ? `<a href="https://www.strava.com/activities/${stravaId}" target="_blank" style="display:inline-flex;align-items:center;gap:6px;background:#FC4C02;color:#fff;border-radius:20px;padding:6px 13px;font-size:0.76rem;font-weight:700;text-decoration:none;font-family:'Outfit',sans-serif;margin-top:4px;"><svg viewBox="0 0 24 24" style="width:13px;height:13px;fill:#fff;"><path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7 13.828h4.169"/></svg>Ver no Strava</a>` : '';

    document.getElementById('modal-treino-status').innerHTML = statusHtml + linkStrava;
    
    modal.style.display = 'flex';
    lockScroll();
}

function fecharDetalhesTreino() {
    document.getElementById('modal-treino-detalhe').style.display = 'none';
    unlockScroll();
}

document.getElementById('modal-treino-detalhe')?.addEventListener('click', function(e) {
    if (e.target === this) fecharDetalhesTreino();
});

renderCalendario();
</script>

<!-- Modal detalhes do treino -->
<div id="modal-treino-detalhe" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:16px; box-sizing:border-box;">
    <div class="modal-box-detalhes" style="background:#fff; border-radius:20px; padding:28px 24px; max-width:420px; width:100%; position:relative; box-shadow:0 8px 32px rgba(0,0,0,0.2);">
        <button onclick="fecharDetalhesTreino()" style="position:absolute; top:16px; right:16px; background:none; border:none; font-size:1.4rem; cursor:pointer; color:#8a8a8a; line-height:1;">✕</button>
        <div id="modal-treino-badge" style="margin-bottom:12px;"></div>
        <h2 id="modal-treino-titulo" style="font-family:'Bebas Neue', sans-serif; font-size:1.6rem; margin:0 0 8px; color:#0d0d0d; padding-right:32px;"></h2>
        <p id="modal-treino-data" style="color:#8a8a8a; font-size:0.85rem; margin:0 0 16px;"></p>
        <div id="modal-treino-descricao-wrapper" style="background:#f5f6f5; border-radius:12px; padding:16px; margin-bottom:16px;">
            <p style="font-size:0.75rem; color:#8a8a8a; margin:0 0 6px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Descrição</p>
            <p id="modal-treino-descricao" style="margin:0; color:#0d0d0d; font-size:0.95rem; white-space:pre-line; line-height:1.6;"></p>
        </div>
        <div id="modal-treino-status"></div>
    </div>
</div>

<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>