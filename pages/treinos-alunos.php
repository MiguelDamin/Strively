<?php
$only_session = true;
require_once '../components/header.php';
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
.planilha-item{background:#fff;border-radius:14px;padding:16px 20px;border:1px solid rgba(0,0,0,.07);box-shadow:0 1px 4px rgba(0,0,0,.05);display:flex;align-items:center;gap:16px;transition:box-shadow .18s}
.planilha-item:hover{box-shadow:0 4px 16px rgba(0,0,0,.09)}
.planilha-item.evento-item{border-left:4px solid #DAA520;background:#FFFCF0}
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
  <div class="aluno-header">
    <a href="/pages/alunos.php" class="voltar">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
      Meus alunos
    </a>

    <?php if (!empty($aluno['foto'])): ?>
      <img src="<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="aluno-foto">
    <?php else: ?>
      <div class="aluno-foto-padrao">
        <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
      </div>
    <?php endif; ?>

    <div class="aluno-info">
      <h1><?= htmlspecialchars($aluno['nome']) ?></h1>
      <span><?= htmlspecialchars($aluno['cidade'] ?? 'Sem cidade') ?></span>
    </div>

    <button class="btn-primary" onclick="abrirModalAdicionar()" style="margin-left:auto;">
      + Adicionar Treino
    </button>
  </div>

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
      usort($todos_planilha, fn($a, $b) => ($a['data_treino'] ?? $a['data_evento']) <=> ($b['data_treino'] ?? $b['data_evento']));
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
          <?php $dt = new DateTime($item['data_treino']); ?>
          <div class="planilha-item">
            <div class="planilha-data">
              <div class="dia"><?= $dt->format('d') ?></div>
              <div class="mes"><?= $meses[(int)$dt->format('m') - 1] ?></div>
            </div>
            <div class="planilha-info">
              <?php $partes = explode(' — ', $item['titulo'], 2); $tipo = $partes[0]; ?>
              <div class="planilha-tipo-badge"><?= htmlspecialchars($tipo) ?></div>
              <?php if ($item['_proprio']): ?><span class="badge-proprio">Auto-treino</span><?php endif; ?>
              <div class="planilha-titulo"><?= htmlspecialchars($item['titulo']) ?></div>
              <?php if (!empty($item['descricao'])): ?><div class="planilha-desc"><?= htmlspecialchars($item['descricao']) ?></div><?php endif; ?>
            </div>
            <div class="planilha-acoes">
              <?php if (!$item['_proprio']): ?>
              <form action="/actions/action-remover-treino.php" method="POST" onsubmit="return confirm('Remover este treino?')">
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
  tt.forEach(t => {
    const item = document.createElement('div');
      if (it._tipo_item === 'evento') {
        const nome = it.evento_nome || it.nome_manual || 'Evento';
        div.className='modal-treino-item evento-item';
        div.innerHTML='<div class="modal-treino-tipo evento-badge">🏅 Evento</div><div class="modal-treino-titulo">'+esc(nome)+'</div>'+(it.evento_cidade?'<div class="modal-treino-desc">📍 '+esc(it.evento_cidade)+'</div>':'');
      } else {
        const tipo = it.titulo && it.titulo.includes(' — ') ? it.titulo.split(' — ')[0] : 'Treino';
        const proprio = it._proprio;
        let acoes = '';
        if(!proprio) acoes='<form method="POST" action="/actions/action-remover-treino.php" onsubmit="return confirm(\'Remover este treino?\')"><input type="hidden" name="treino_id" value="'+it.id+'"><input type="hidden" name="aluno_id" value="'+it.aluno_id+'"><input type="hidden" name="aba" value="calendario"><button type="submit" class="btn-remover-treino">Remover</button></form>';
        div.className='modal-treino-item';
        div.innerHTML='<div class="modal-treino-tipo">'+esc(tipo)+'</div>'+(proprio?'<span class="badge-proprio" style="margin-left:0;margin-bottom:6px;display:inline-block">Auto-treino</span>':'')+'<div class="modal-treino-titulo">'+esc(it.titulo)+'</div>'+(it.descricao?'<div class="modal-treino-desc">'+esc(it.descricao)+'</div>':'')+(acoes?'<div class="modal-treino-acoes">'+acoes+'</div>':'');
      }
    body.appendChild(item);
  });
  document.getElementById('modalDia').classList.add('aberto');
}

function abrirModalAdicionar(d) {
  if (d) document.getElementById('f-data').value = d;
  document.getElementById('modalAdicionar').classList.add('aberto');
}

function fecharModal(id) { document.getElementById(id).classList.remove('aberto'); }
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

renderCalendario();
</script>