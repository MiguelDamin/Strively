<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

// Treinadores vão para alunos.php
if ($_SESSION['perfil'] === 'treinador') {
  header('Location: /pages/alunos.php');
  exit();
}

// Treinos do corredor
$stmt = $pdo->prepare("SELECT * FROM treinos WHERE aluno_id = ? ORDER BY data_treino ASC");
$stmt->execute([$_SESSION['id']]);
$treinos = $stmt->fetchAll();

$treinos_por_data = [];
foreach ($treinos as $t) {
  $t['_tipo_item'] = 'treino';
  $t['_proprio'] = ((int)$t['treinador_id'] === (int)$_SESSION['id']);
  $treinos_por_data[$t['data_treino']][] = $t;
}

// Eventos do corredor
$stmt = $pdo->prepare("
  SELECT ue.id AS ue_id, ue.evento_id, ue.nome_manual, ue.data_evento,
         e.nome AS evento_nome, e.cidade AS evento_cidade, e.distancias
  FROM usuario_eventos ue
  LEFT JOIN eventos e ON e.id = ue.evento_id
  WHERE ue.usuario_id = ?
  ORDER BY ue.data_evento ASC
");
$stmt->execute([$_SESSION['id']]);
$eventos_usuario = $stmt->fetchAll();

$eventos_por_data = [];
foreach ($eventos_usuario as $ev) {
  $ev['_tipo_item'] = 'evento';
  $treinos_por_data[$ev['data_evento']][] = $ev;
  $eventos_por_data[$ev['data_evento']][] = $ev;
}

// Eventos ativos do Strively para o select
$stmt = $pdo->prepare("SELECT id, nome, data_evento, cidade FROM eventos WHERE status = 'ativo' ORDER BY data_evento ASC");
$stmt->execute();
$eventos_strive = $stmt->fetchAll();

$treinos_json = json_encode($treinos_por_data);
$aba = $_GET['aba'] ?? 'calendario';

unset($only_session);
$tituloPagina = "Meus Treinos";
include '../components/head.php';
include '../components/header.php';
?>
<style>
.treinos-page{max-width:900px;margin:0 auto;padding:40px 24px 100px}
.treinos-titulo{font-family:'Bebas Neue',sans-serif;font-size:2.2rem;letter-spacing:2px;margin-bottom:4px}
.treinos-sub{font-size:.9rem;color:var(--text-muted);margin-bottom:28px}
.treinos-header{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:24px}
.treinos-header h1{margin:0}
.treinos-btns{display:flex;gap:8px;flex-wrap:wrap}
.abas{display:flex;gap:4px;background:#fff;border-radius:14px;padding:6px;box-shadow:0 2px 8px rgba(0,0,0,.08);margin-bottom:28px;width:fit-content}
.aba-btn{display:flex;align-items:center;gap:8px;padding:10px 22px;border-radius:10px;border:none;background:transparent;font-family:'Outfit',sans-serif;font-size:.9rem;font-weight:600;color:var(--text-muted);cursor:pointer;text-decoration:none;transition:all .2s}
.aba-btn:hover{background:var(--bg,#f5f5f5);color:var(--text-main)}
.aba-btn.ativa{background:var(--green);color:#fff}
.aba-btn svg{width:17px;height:17px;fill:currentColor}
.msg-sucesso{background:#f0fff4;border:1px solid #b2f5c8;border-radius:10px;padding:12px 16px;font-size:.88rem;color:#166534;margin-bottom:20px}
.msg-erro{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;font-size:.88rem;color:#991b1b;margin-bottom:20px}
/* Calendário */
.calendario-wrap{background:#fff;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:28px}
.cal-nav{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px}
.cal-nav h2{font-family:'Bebas Neue',sans-serif;font-size:1.6rem;letter-spacing:1.5px;margin:0}
.cal-nav-btn{width:36px;height:36px;border-radius:8px;border:2px solid #e5e5e5;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s}
.cal-nav-btn:hover{border-color:var(--green)}
.cal-nav-btn svg{width:18px;height:18px;fill:var(--text-main,#111)}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:6px}
.cal-dia-nome{text-align:center;font-size:.75rem;font-weight:700;color:var(--text-muted);padding:6px 0;text-transform:uppercase;letter-spacing:.5px}
.cal-dia{aspect-ratio:1;border-radius:10px;padding:6px;cursor:pointer;transition:all .18s;display:flex;flex-direction:column;align-items:center;min-height:52px;border:2px solid transparent}
.cal-dia:hover{background:var(--bg,#f5f5f5);border-color:#ddd}
.cal-dia.vazio{cursor:default;pointer-events:none}
.cal-dia.hoje{border-color:var(--green);background:rgba(29,185,84,.07)}
.cal-dia.tem-treino{background:#f0f9ff;border-color:#bde0f7}
.cal-dia.tem-treino:hover{background:#e0f2fe;border-color:#7dc8f0}
.cal-dia.tem-evento{background:rgba(218,165,32,.07);border-color:#DAA520}
.cal-dia.tem-evento:hover{background:rgba(218,165,32,.12);border-color:#B8860B}
.cal-dia.tem-treino.tem-evento{background:linear-gradient(135deg,#f0f9ff 50%,rgba(218,165,32,.1) 50%);border-color:#DAA520}
.cal-dia-num{font-size:.85rem;font-weight:600;line-height:1;margin-bottom:3px;color:var(--text-main,#111)}
.cal-dia.vazio .cal-dia-num{color:#ccc}
.cal-bolinhas{display:flex;gap:3px;flex-wrap:wrap;justify-content:center;margin-top:2px}
.cal-bolinha{width:7px;height:7px;border-radius:50%}
.cal-bolinha.pendente{background:#aaa}
.cal-bolinha.realizado{background:var(--green)}
.cal-bolinha.evento{background:#DAA520}
/* Modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:500;display:flex;align-items:center;justify-content:center;padding:24px;opacity:0;visibility:hidden;transition:all .2s}
.modal-overlay.aberto{opacity:1;visibility:visible}
.modal-box{background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.25);width:100%;max-width:480px;max-height:90vh;overflow-y:auto;transform:translateY(12px) scale(.97);transition:transform .2s}
.modal-overlay.aberto .modal-box{transform:translateY(0) scale(1)}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px 16px;border-bottom:1px solid #f0f0f0}
.modal-header h3{font-family:'Bebas Neue',sans-serif;font-size:1.4rem;letter-spacing:1px;margin:0}
.modal-fechar{width:32px;height:32px;border-radius:8px;border:none;background:var(--bg,#f5f5f5);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s}
.modal-fechar:hover{background:#e0e0e0}
.modal-fechar svg{width:16px;height:16px;fill:var(--text-muted)}
.modal-body{padding:20px 24px 24px}
.modal-treino-item{background:var(--bg,#f5f5f5);border-radius:10px;padding:14px 16px;margin-bottom:10px;border-left:4px solid #ddd}
.modal-treino-item.realizado{border-left-color:var(--green);background:#f0fff4}
.modal-treino-item.evento-item{border-left-color:#DAA520;background:#FFFCF0}
.modal-treino-item:last-child{margin-bottom:0}
.modal-treino-tipo{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#166534;background:#dcfce7;padding:3px 8px;border-radius:6px;display:inline-block;margin-bottom:6px}
.modal-treino-tipo.evento-badge{color:#B8860B;background:#FFF8DC}
.modal-treino-titulo{font-weight:700;font-size:.97rem;margin-bottom:4px}
.modal-treino-desc{font-size:.84rem;color:var(--text-muted);line-height:1.5;margin-bottom:10px}
.badge-realizado{background:rgba(29,185,84,.2);color:#166534;font-size:.78rem;font-weight:700;padding:4px 10px;border-radius:6px;display:inline-block}
.badge-proprio{background:#e0f2fe;color:#0369a1;font-size:.72rem;font-weight:700;padding:3px 8px;border-radius:6px;display:inline-block;margin-left:6px}
.btn-marcar{background:var(--green);color:#fff;border:none;border-radius:8px;padding:8px 16px;font-family:'Outfit',sans-serif;font-size:.85rem;font-weight:700;cursor:pointer;transition:opacity .15s}
.btn-marcar:hover{opacity:.85}
.btn-remover-treino{background:#fff0f0;border:none;color:#c0392b;border-radius:7px;padding:6px 14px;font-size:.8rem;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;transition:background .18s;margin-left:8px}
.btn-remover-treino:hover{background:#ffd5d5}
/* Planilha */
.planilha-wrap{display:flex;flex-direction:column;gap:12px}
.planilha-item{background:#fff;border-radius:14px;padding:18px 22px;box-shadow:0 2px 8px rgba(0,0,0,.08);display:flex;align-items:center;gap:16px;border-left:4px solid transparent}
.planilha-item.realizado{background:#f0fff4;border-left-color:var(--green)}
.planilha-item.evento-item{border-left-color:#DAA520;background:#FFFCF0}
.planilha-data{min-width:64px;text-align:center;background:var(--bg,#f5f5f5);border-radius:10px;padding:8px 6px;flex-shrink:0}
.planilha-data .dia{font-family:'Bebas Neue',sans-serif;font-size:1.8rem;line-height:1;color:var(--green)}
.planilha-item.evento-item .planilha-data .dia{color:#DAA520}
.planilha-data .mes{font-size:.72rem;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.5px}
.planilha-info{flex:1;min-width:0}
.planilha-tipo-badge{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#166534;background:#dcfce7;padding:3px 9px;border-radius:6px;display:inline-block;margin-bottom:5px}
.planilha-tipo-badge.evento-badge{color:#B8860B;background:#FFF8DC}
.planilha-titulo{font-weight:700;font-size:.97rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.planilha-item.realizado .planilha-titulo{text-decoration:line-through;color:var(--text-muted)}
.planilha-desc{font-size:.82rem;color:var(--text-muted);margin-top:2px}
.planilha-acoes{flex-shrink:0;display:flex;align-items:center;gap:6px}
.vazio-wrap{text-align:center;padding:60px 24px;color:var(--text-muted)}
.vazio-wrap .vazio-icone{font-size:3rem;margin-bottom:12px}
/* Modal form */
.modal-form .form-grupo{margin-bottom:14px}
.modal-form label{font-size:.82rem;font-weight:600;display:block;margin-bottom:5px;color:#333}
.modal-form input,.modal-form select,.modal-form textarea{width:100%;background:#f5f6f5;border:1.5px solid #e0e0e0;border-radius:10px;padding:10px 13px;font-family:'Outfit',sans-serif;font-size:.9rem;outline:none;transition:border-color .18s,box-shadow .18s;box-sizing:border-box;color:#111}
.modal-form input:focus,.modal-form select:focus,.modal-form textarea:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(29,185,84,.12);background:#fff}
.modal-form textarea{resize:vertical;min-height:80px}
.modal-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.campo-outro{display:none;margin-top:8px}
.campo-outro.visivel{display:block}
/* Tabs evento modal */
.evento-tabs{display:flex;gap:4px;margin-bottom:16px;background:#f5f5f5;border-radius:10px;padding:4px}
.evento-tab{flex:1;padding:9px 8px;border:none;border-radius:8px;font-family:'Outfit',sans-serif;font-size:.82rem;font-weight:600;cursor:pointer;background:transparent;color:#888;transition:all .18s;text-align:center}
.evento-tab.ativo{background:#DAA520;color:#fff}
@media(max-width:640px){.abas{width:100%}.aba-btn{flex:1;justify-content:center;padding:10px 12px;font-size:.82rem}.cal-dia{min-height:40px;padding:4px}.cal-dia-num{font-size:.78rem}.planilha-item{flex-wrap:wrap}.treinos-btns{width:100%}.treinos-btns .btn-primary,.treinos-btns .btn-secondary{flex:1;text-align:center;justify-content:center;font-size:.82rem;padding:10px 8px}.modal-form-grid{grid-template-columns:1fr}}
</style>

<section class="treinos-page">

  <div class="treinos-header">
    <div>
      <h1 class="treinos-titulo">Meus Treinos</h1>
      <p class="treinos-sub" style="margin-bottom:0">Acompanhe e registre seus treinos e eventos.</p>
    </div>
    <div class="treinos-btns">
      <button class="btn-primary" onclick="abrirModal('modalAdicionar')" style="font-size:.85rem;padding:10px 20px">
        + Treino
      </button>
      <button class="btn-secondary" onclick="abrirModal('modalEvento')" style="font-size:.85rem;padding:10px 20px;border-color:#DAA520;color:#DAA520">
        🏅 Evento
      </button>
    </div>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="msg-sucesso">
      <?php
        $msgs = [
          'realizado'        => '✅ Treino marcado como realizado!',
          'criado'           => '✅ Treino adicionado com sucesso!',
          'removido'         => '🗑️ Treino removido.',
          'evento_adicionado'=> '🏅 Evento adicionado ao calendário!',
          'evento_removido'  => '🗑️ Evento removido do calendário.',
        ];
        echo $msgs[$_GET['msg']] ?? 'Ação realizada.';
      ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['erro'])): ?>
    <div class="msg-erro">
      <?php
        $erros = [
          'campos'         => 'Preencha todos os campos obrigatórios.',
          'evento_invalido'=> 'Evento não encontrado ou inativo.',
          'data_errada'    => 'A data do evento deve ser ' . htmlspecialchars($_GET['data_correta'] ?? '') . '.',
          'ja_adicionado'  => 'Você já adicionou este evento ao seu calendário.',
        ];
        echo $erros[$_GET['erro']] ?? 'Ocorreu um erro.';
      ?>
    </div>
  <?php endif; ?>

  <div class="abas">
    <a href="?aba=calendario" class="aba-btn <?= $aba==='calendario'?'ativa':'' ?>">
      <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5C3.9 3 3 3.9 3 5v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
      Calendário
    </a>
    <a href="?aba=planilha" class="aba-btn <?= $aba==='planilha'?'ativa':'' ?>">
      <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
      Planilha
    </a>
  </div>

  <!-- ABA: CALENDÁRIO -->
  <?php if ($aba === 'calendario'): ?>
  <div class="calendario-wrap">
    <div class="cal-nav">
      <button class="cal-nav-btn" onclick="mudarMes(-1)"><svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg></button>
      <h2 id="cal-titulo"></h2>
      <button class="cal-nav-btn" onclick="mudarMes(1)"><svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg></button>
    </div>
    <div class="cal-grid">
      <?php foreach(['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $dn): ?><div class="cal-dia-nome"><?=$dn?></div><?php endforeach; ?>
    </div>
    <div class="cal-grid" id="cal-dias"></div>
    <div style="display:flex;gap:16px;margin-top:16px;font-size:.82rem;color:var(--text-muted)">
      <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#aaa;margin-right:5px"></span>Pendente</span>
      <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--green);margin-right:5px"></span>Realizado</span>
      <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#DAA520;margin-right:5px"></span>Evento</span>
    </div>
  </div>
  <?php endif; ?>

  <!-- ABA: PLANILHA -->
  <?php if ($aba === 'planilha'): ?>
  <div class="planilha-wrap">
    <?php
      // Merge treinos + eventos into one sorted list
      $todos = [];
      foreach ($treinos as $t) {
        $t['_tipo_item'] = 'treino';
        $t['_proprio'] = ((int)$t['treinador_id'] === (int)$_SESSION['id']);
        $todos[] = $t;
      }
      foreach ($eventos_usuario as $ev) {
        $ev['_tipo_item'] = 'evento';
        $todos[] = $ev;
      }
      usort($todos, fn($a, $b) => ($a['data_treino'] ?? $a['data_evento']) <=> ($b['data_treino'] ?? $b['data_evento']));
    ?>

    <?php if (empty($todos)): ?>
      <div class="vazio-wrap"><div class="vazio-icone">🏃</div><p>Nenhum treino ou evento cadastrado.<br>Use os botões acima para começar!</p></div>
    <?php else: ?>
      <?php $meses=["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"]; ?>
      <?php foreach($todos as $item): ?>
        <?php if ($item['_tipo_item'] === 'treino'): ?>
          <?php $dt=new DateTime($item['data_treino']); $realizado=($item['status']==='realizado'); ?>
          <div class="planilha-item <?= $realizado?'realizado':'' ?>">
            <div class="planilha-data">
              <div class="dia"><?=$dt->format('d')?></div>
              <div class="mes"><?=$meses[(int)$dt->format('m')-1]?></div>
            </div>
            <div class="planilha-info">
              <div class="planilha-tipo-badge"><?php
                $partes = explode(' — ', $item['titulo'], 2);
                echo htmlspecialchars($partes[0]);
              ?></div>
              <?php if ($item['_proprio']): ?><span class="badge-proprio">Meu treino</span><?php endif; ?>
              <div class="planilha-titulo"><?=htmlspecialchars($item['titulo'])?></div>
              <?php if(!empty($item['descricao'])): ?><div class="planilha-desc"><?=htmlspecialchars($item['descricao'])?></div><?php endif; ?>
            </div>
            <div class="planilha-acoes">
              <?php if($realizado): ?>
                <span class="badge-realizado">✅ Realizado</span>
              <?php else: ?>
                <form action="/actions/action-marcar-realizado.php" method="POST" style="display:inline">
                  <input type="hidden" name="treino_id" value="<?=(int)$item['id']?>">
                  <input type="hidden" name="aba" value="planilha">
                  <button type="submit" class="btn-marcar">Realizado</button>
                </form>
              <?php endif; ?>
              <?php if ($item['_proprio']): ?>
                <form action="/actions/action-remover-treino-proprio.php" method="POST" style="display:inline" onsubmit="return confirm('Remover este treino?')">
                  <input type="hidden" name="treino_id" value="<?=(int)$item['id']?>">
                  <input type="hidden" name="aba" value="planilha">
                  <button type="submit" class="btn-remover-treino">✕</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php else: ?>
          <?php $dt=new DateTime($item['data_evento']); ?>
          <div class="planilha-item evento-item">
            <div class="planilha-data">
              <div class="dia"><?=$dt->format('d')?></div>
              <div class="mes"><?=$meses[(int)$dt->format('m')-1]?></div>
            </div>
            <div class="planilha-info">
              <div class="planilha-tipo-badge evento-badge">🏅 Evento</div>
              <div class="planilha-titulo"><?= htmlspecialchars($item['evento_nome'] ?? $item['nome_manual'] ?? 'Evento') ?></div>
              <?php if (!empty($item['evento_cidade'])): ?>
                <div class="planilha-desc">📍 <?= htmlspecialchars($item['evento_cidade']) ?></div>
              <?php endif; ?>
              <?php if (!empty($item['distancias'])): ?>
                <div class="planilha-desc"><?= htmlspecialchars($item['distancias']) ?></div>
              <?php endif; ?>
            </div>
            <div class="planilha-acoes">
              <form action="/actions/action-remover-evento-usuario.php" method="POST" style="display:inline" onsubmit="return confirm('Remover este evento?')">
                <input type="hidden" name="evento_usuario_id" value="<?=(int)$item['ue_id']?>">
                <input type="hidden" name="aba" value="planilha">
                <button type="submit" class="btn-remover-treino">✕</button>
              </form>
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
      <button class="modal-fechar" onclick="fecharModal('modalDia')"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
    <div class="modal-body" id="modalDia-body"></div>
  </div>
</div>

<!-- MODAL: ADICIONAR TREINO -->
<div class="modal-overlay" id="modalAdicionar" onclick="fecharModalSeFora(event,'modalAdicionar')">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Adicionar Treino</h3>
      <button class="modal-fechar" onclick="fecharModal('modalAdicionar')"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
    <div class="modal-body">
      <form action="/actions/action-adicionar-treino-proprio.php" method="POST" class="modal-form">
        <input type="hidden" name="aba" value="<?= htmlspecialchars($aba) ?>">

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
          <label for="f-desc">Descrição</label>
          <textarea id="f-desc" name="descricao" placeholder="Detalhe o treino..."></textarea>
        </div>

        <button type="submit" class="btn-primary btn-full" style="margin-top:6px">
          Salvar Treino
        </button>
      </form>
    </div>
  </div>
</div>

<!-- MODAL: ADICIONAR EVENTO -->
<div class="modal-overlay" id="modalEvento" onclick="fecharModalSeFora(event,'modalEvento')">
  <div class="modal-box">
    <div class="modal-header">
      <h3>🏅 Adicionar Evento</h3>
      <button class="modal-fechar" onclick="fecharModal('modalEvento')"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>
    <div class="modal-body">
      <div class="evento-tabs">
        <button class="evento-tab ativo" onclick="trocarAbaEvento('strive',this)">Eventos Strively</button>
        <button class="evento-tab" onclick="trocarAbaEvento('manual',this)">Evento Manual</button>
      </div>

      <!-- TAB: Strively -->
      <form action="/actions/action-adicionar-evento-usuario.php" method="POST" class="modal-form" id="formEventoStrive">
        <input type="hidden" name="aba" value="<?= htmlspecialchars($aba) ?>">
        <div class="form-grupo">
          <label for="e-select">Selecione um evento *</label>
          <select id="e-select" name="evento_id" onchange="preencherDataEvento(this)" required>
            <option value="">Escolha...</option>
            <?php foreach ($eventos_strive as $ev): ?>
              <option value="<?= (int)$ev['id'] ?>" data-data="<?= htmlspecialchars($ev['data_evento']) ?>">
                <?= htmlspecialchars($ev['nome']) ?> — <?= htmlspecialchars($ev['cidade'] ?? '') ?> (<?= (new DateTime($ev['data_evento']))->format('d/m/Y') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-grupo">
          <label>Data do evento</label>
          <input type="date" id="e-data" name="data_evento" readonly required style="background:#eee;cursor:not-allowed">
        </div>
        <button type="submit" class="btn-primary btn-full" style="margin-top:6px;background:#DAA520">
          Adicionar ao Calendário
        </button>
      </form>

      <!-- TAB: Manual -->
      <form action="/actions/action-adicionar-evento-usuario.php" method="POST" class="modal-form" id="formEventoManual" style="display:none">
        <input type="hidden" name="aba" value="<?= htmlspecialchars($aba) ?>">
        <input type="hidden" name="evento_id" value="0">
        <div class="form-grupo">
          <label for="em-nome">Nome do evento *</label>
          <input type="text" id="em-nome" name="nome_manual" placeholder="Ex: Maratona da Cidade" required>
        </div>
        <div class="form-grupo">
          <label for="em-data">Data *</label>
          <input type="date" id="em-data" name="data_evento" required>
        </div>
        <button type="submit" class="btn-primary btn-full" style="margin-top:6px;background:#DAA520">
          Adicionar ao Calendário
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
  const titulo=document.getElementById('cal-titulo'), grid=document.getElementById('cal-dias');
  if(!titulo||!grid) return;
  titulo.textContent=mesesNomes[mesAtual]+' '+anoAtual;
  grid.innerHTML='';
  const primeiro=new Date(anoAtual,mesAtual,1).getDay(), dias=new Date(anoAtual,mesAtual+1,0).getDate(), hoje=new Date();
  for(let i=0;i<primeiro;i++){const v=document.createElement('div');v.className='cal-dia vazio';v.innerHTML='<span class="cal-dia-num"></span>';grid.appendChild(v);}
  for(let d=1;d<=dias;d++){
    const cell=document.createElement('div');cell.className='cal-dia';
    const ds=anoAtual+'-'+String(mesAtual+1).padStart(2,'0')+'-'+String(d).padStart(2,'0');
    if(d===hoje.getDate()&&mesAtual===hoje.getMonth()&&anoAtual===hoje.getFullYear())cell.classList.add('hoje');
    const items=treinosPorData[ds];
    let hasTreino=false, hasEvento=false;
    if(items){
      items.forEach(it=>{if(it._tipo_item==='evento')hasEvento=true;else hasTreino=true;});
      cell.addEventListener('click',()=>abrirModalDia(ds,items));
    }
    if(hasTreino)cell.classList.add('tem-treino');
    if(hasEvento)cell.classList.add('tem-evento');
    const num=document.createElement('span');num.className='cal-dia-num';num.textContent=d;cell.appendChild(num);
    if(items){
      const bl=document.createElement('div');bl.className='cal-bolinhas';
      items.forEach(it=>{
        const bo=document.createElement('div');bo.className='cal-bolinha';
        if(it._tipo_item==='evento')bo.classList.add('evento');
        else bo.classList.add(it.status==='realizado'?'realizado':'pendente');
        bl.appendChild(bo);
      });
      cell.appendChild(bl);
    }
    grid.appendChild(cell);
  }
}
function mudarMes(d){mesAtual+=d;if(mesAtual>11){mesAtual=0;anoAtual++;}if(mesAtual<0){mesAtual=11;anoAtual--;}renderCalendario();}

function abrirModalDia(ds,items){
  const [a,m,d]=ds.split('-');
  const mn=["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"];
  document.getElementById('modalDia-titulo').textContent=d+' de '+mn[parseInt(m)-1]+' de '+a;
  const body=document.getElementById('modalDia-body');body.innerHTML='';
  items.forEach(it=>{
    const div=document.createElement('div');
    if(it._tipo_item==='evento'){
      div.className='modal-treino-item evento-item';
      const nome=it.evento_nome||it.nome_manual||'Evento';
      div.innerHTML=
        '<div class="modal-treino-tipo evento-badge">🏅 Evento</div>'+
        '<div class="modal-treino-titulo">'+esc(nome)+'</div>'+
        (it.evento_cidade?'<div class="modal-treino-desc">📍 '+esc(it.evento_cidade)+'</div>':'')+
        '<div style="margin-top:10px"><form method="POST" action="/actions/action-remover-evento-usuario.php" onsubmit="return confirm(\'Remover este evento?\')"><input type="hidden" name="evento_usuario_id" value="'+it.ue_id+'"><input type="hidden" name="aba" value="calendario"><button type="submit" class="btn-remover-treino">Remover</button></form></div>';
    } else {
      const tipo=it.titulo&&it.titulo.includes(' — ')?it.titulo.split(' — ')[0]:'Treino';
      const realizado=it.status==='realizado';
      const proprio=it._proprio;
      let acoes='';
      if(realizado){
        acoes='<span class="badge-realizado">✅ Realizado</span>';
      } else {
        acoes='<form method="POST" action="/actions/action-marcar-realizado.php" style="display:inline"><input type="hidden" name="treino_id" value="'+it.id+'"><input type="hidden" name="aba" value="calendario"><button type="submit" class="btn-marcar">Marcar Realizado</button></form>';
      }
      if(proprio){
        acoes+=' <form method="POST" action="/actions/action-remover-treino-proprio.php" style="display:inline" onsubmit="return confirm(\'Remover este treino?\')"><input type="hidden" name="treino_id" value="'+it.id+'"><input type="hidden" name="aba" value="calendario"><button type="submit" class="btn-remover-treino">Remover</button></form>';
      }
      div.className='modal-treino-item'+(realizado?' realizado':'');
      div.innerHTML=
        '<div class="modal-treino-tipo">'+esc(tipo)+'</div>'+
        (proprio?'<span class="badge-proprio">Meu treino</span>':'')+
        '<div class="modal-treino-titulo">'+esc(it.titulo)+'</div>'+
        (it.descricao?'<div class="modal-treino-desc">'+esc(it.descricao)+'</div>':'')+
        '<div style="margin-top:10px">'+acoes+'</div>';
    }
    body.appendChild(div);
  });
  document.getElementById('modalDia').classList.add('aberto');
}

function abrirModal(id){document.getElementById(id).classList.add('aberto');}
function fecharModal(id){document.getElementById(id).classList.remove('aberto');}
function fecharModalSeFora(e,id){if(e.target.id===id)fecharModal(id);}
function esc(s){if(!s)return'';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

function toggleOutro(s){
  const c=document.getElementById('campo-outro'),o=document.getElementById('f-tipo-outro');
  if(s.value==='outro'){c.classList.add('visivel');o.required=true;}else{c.classList.remove('visivel');o.required=false;}
}

function preencherDataEvento(sel){
  const opt=sel.options[sel.selectedIndex];
  document.getElementById('e-data').value=opt.dataset.data||'';
}

function trocarAbaEvento(tab,btn){
  document.querySelectorAll('.evento-tab').forEach(t=>t.classList.remove('ativo'));
  btn.classList.add('ativo');
  document.getElementById('formEventoStrive').style.display=tab==='strive'?'':'none';
  document.getElementById('formEventoManual').style.display=tab==='manual'?'':'none';
}

document.addEventListener('keydown',e=>{if(e.key==='Escape'){fecharModal('modalDia');fecharModal('modalAdicionar');fecharModal('modalEvento');}});
renderCalendario();
</script>
