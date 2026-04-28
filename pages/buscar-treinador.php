<?php
// ==========================================================
// STRIVELY — pages/buscar-treinador.php
// Listagem de treinadores aprovados para corredores
// ==========================================================

$only_session = true;
require_once '../components/header.php';

// Somente corredores logados
if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}
if (isset($me) && $me['perfil'] === 'treinador') {
  header('Location: /index.php');
  exit();
}

require_once '../config/conexao.php';

// Busca treinadores aprovados
$stmt = $pdo->prepare("
  SELECT
    u.id,
    u.nome,
    u.foto,
    u.cidade,
    t.cref,
    t.especialidade,
    t.assessoria,
    (SELECT COUNT(*) FROM usuarios a WHERE a.treinador_id = u.id AND a.status_vinculo = 'aceito') AS total_alunos
  FROM usuarios u
  INNER JOIN treinadores t ON t.usuario_id = u.id
  WHERE u.perfil = 'treinador'
    AND u.status = 'ativo'
    AND t.status = 'aprovado'
  ORDER BY total_alunos ASC, u.nome ASC
");
$stmt->execute();
$treinadores = $stmt->fetchAll();

unset($only_session);
$tituloPagina = "Buscar Treinador";
include '../components/head.php';
include '../components/header.php';
?>

<style>
  .bt-wrap {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 24px 100px;
  }

  .bt-titulo {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2.2rem;
    letter-spacing: 2px;
    margin-bottom: 4px;
  }

  .bt-sub {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-bottom: 28px;
  }

  .bt-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 18px;
  }

  .bt-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 24px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 10px;
    box-shadow: var(--shadow-xs);
    transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition);
  }

  .bt-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
    border-color: rgba(29,185,84,0.2);
  }

  .bt-foto {
    width: 72px; height: 72px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--green);
    box-shadow: 0 0 0 4px var(--green-tint);
  }

  .bt-avatar {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: var(--surface-2);
    border: 3px solid var(--green);
    box-shadow: 0 0 0 4px var(--green-tint);
    display: flex; align-items: center; justify-content: center;
  }

  .bt-avatar svg { width: 36px; height: 36px; fill: var(--text-tertiary); }

  .bt-nome {
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--text-primary);
  }

  .bt-cidade {
    font-size: 0.82rem;
    color: var(--text-secondary);
  }

  .bt-meta {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 6px;
    margin-top: 2px;
  }

  .bt-badge {
    background: var(--green-tint);
    color: var(--green-deeper);
    font-size: 0.72rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: var(--radius-full);
    letter-spacing: 0.3px;
    border: 1px solid rgba(29,185,84,0.15);
  }

  .bt-alunos {
    font-size: 0.78rem;
    color: var(--text-tertiary);
    margin-top: 2px;
  }

  .bt-divider {
    width: 100%;
    height: 1px;
    background: var(--border);
    margin: 4px 0;
  }

  .bt-card .btn-primary,
  .bt-card .btn-secondary {
    width: 100%;
    padding: 9px 0;
    font-size: 0.85rem;
  }

  .bt-status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    width: 100%;
    padding: 9px 0;
    font-size: 0.85rem;
    font-weight: 600;
    font-family: 'Outfit', sans-serif;
    border-radius: var(--radius-full);
    cursor: default;
  }

  .bt-status-badge.seu-treinador {
    background: var(--green);
    color: #fff;
  }

  .bt-status-badge.pendente {
    background: #fffbea;
    color: #92610e;
    border: 1px solid #f5d87a;
  }

  .bt-status-badge.vinculado {
    background: var(--surface-2);
    color: var(--text-tertiary);
    border: 1px solid var(--border);
  }

  .bt-vazio {
    grid-column: 1 / -1;
    text-align: center;
    padding: 80px 24px;
    color: var(--text-secondary);
  }

  .bt-vazio-icone { font-size: 3rem; margin-bottom: 14px; }

  /* Modal */
  .modal-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 500;
    align-items: center; justify-content: center;
  }

  .modal-overlay.ativo { display: flex; }

  .modal-box {
    background: var(--surface);
    border-radius: var(--radius-xl);
    border: 1px solid var(--border);
    padding: 32px 28px;
    max-width: 400px;
    width: 90%;
    box-shadow: var(--shadow-md);
    text-align: center;
  }

  .modal-box h3 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.5rem;
    letter-spacing: 1px;
    margin-bottom: 8px;
  }

  .modal-box p {
    font-size: 0.88rem;
    color: var(--text-secondary);
    line-height: 1.6;
    margin-bottom: 20px;
  }

  .modal-btns {
    display: flex;
    gap: 10px;
  }

  .modal-btns .btn-primary,
  .modal-btns .btn-secondary {
    flex: 1;
    padding: 10px 0;
  }

  .msg-sucesso {
    background: #e8f8ee;
    color: #166534;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 0.92rem;
  }

  .msg-erro {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 0.92rem;
  }

  @media (max-width: 640px) {
    .bt-wrap { padding: 0 14px 100px; }
    .bt-titulo { font-size: 1.8rem; }
    .bt-grid { grid-template-columns: 1fr; }
  }
</style>

<body>

<div class="bt-wrap">

  <h1 class="bt-titulo">Encontre seu Treinador</h1>
  <p class="bt-sub">Treinadores verificados prontos para te ajudar a evoluir.</p>

  <?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'solicitado'): ?>
      <div class="msg-sucesso">✅ Solicitação enviada com sucesso! O treinador será notificado por e-mail.</div>
    <?php elseif ($_GET['msg'] === 'ja_vinculado'): ?>
      <div class="msg-erro">Você já possui um vínculo ativo com um treinador.</div>
    <?php elseif ($_GET['msg'] === 'erro'): ?>
      <div class="msg-erro">Ocorreu um erro ao enviar a solicitação. Tente novamente.</div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="bt-grid">

    <?php if (empty($treinadores)): ?>
      <div class="bt-vazio">
        <div class="bt-vazio-icone">🏋️</div>
        <h2 style="font-family:'Bebas Neue',sans-serif; font-size:1.7rem; letter-spacing:1.5px; margin-bottom:8px;">
          Nenhum treinador disponível
        </h2>
        <p>Em breve teremos treinadores verificados aqui. Volte depois!</p>
      </div>

    <?php else: ?>
      <?php foreach ($treinadores as $tr): ?>
        <div class="bt-card">

          <!-- Foto -->
          <?php if (!empty($tr['foto'])): ?>
            <img src="<?= strpos($tr['foto'], 'http') === 0 ? htmlspecialchars($tr['foto']) : '/' . htmlspecialchars($tr['foto']) ?>"
                 class="bt-foto" alt="Foto">
          <?php else: ?>
            <div class="bt-avatar">
              <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
            </div>
          <?php endif; ?>

          <!-- Nome + cidade -->
          <div class="bt-nome"><?= htmlspecialchars($tr['nome']) ?></div>
          <?php if (!empty($tr['cidade'])): ?>
            <div class="bt-cidade">📍 <?= htmlspecialchars($tr['cidade']) ?></div>
          <?php endif; ?>

          <!-- Badges: especialidade, assessoria, CREF -->
          <div class="bt-meta">
            <?php if (!empty($tr['especialidade'])): ?>
              <span class="bt-badge"><?= htmlspecialchars($tr['especialidade']) ?></span>
            <?php endif; ?>
            <?php if (!empty($tr['assessoria'])): ?>
              <span class="bt-badge"><?= htmlspecialchars($tr['assessoria']) ?></span>
            <?php endif; ?>
            <?php if (!empty($tr['cref'])): ?>
              <span class="bt-badge">CREF <?= htmlspecialchars($tr['cref']) ?></span>
            <?php endif; ?>
          </div>

          <!-- Contador de alunos -->
          <div class="bt-alunos">
            <?= (int)$tr['total_alunos'] ?> aluno<?= (int)$tr['total_alunos'] !== 1 ? 's' : '' ?> ativo<?= (int)$tr['total_alunos'] !== 1 ? 's' : '' ?>
          </div>

          <div class="bt-divider"></div>

          <!-- Botão condicional -->
          <?php if (isset($me['treinador_id']) && (int)$me['treinador_id'] === (int)$tr['id']): ?>
            <?php if ($me['status_vinculo'] === 'aceito'): ?>
              <div class="bt-status-badge seu-treinador">✓ Seu treinador</div>
            <?php else: ?>
              <div class="bt-status-badge pendente">⏳ Solicitação enviada</div>
            <?php endif; ?>
          <?php elseif (!empty($me['treinador_id'])): ?>
            <div class="bt-status-badge vinculado">Você já tem um vínculo</div>
          <?php else: ?>
            <button class="btn-primary" style="width:100%; padding:9px 0; font-size:0.85rem;"
                    onclick="abrirModal(<?= (int)$tr['id'] ?>, '<?= htmlspecialchars(addslashes($tr['nome']), ENT_QUOTES) ?>')">
              Contratar
            </button>
          <?php endif; ?>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>

</div>

<!-- Modal de confirmação -->
<div class="modal-overlay" id="modalContratar">
  <div class="modal-box">
    <h3>Confirmar solicitação</h3>
    <p>Deseja enviar uma solicitação de vínculo para <strong id="modalNome"></strong>? O treinador será notificado por e-mail.</p>
    <form method="POST" action="/actions/action-solicitar-treinador.php" id="formContratar">
      <input type="hidden" name="treinador_id" id="modalTreinadorId" value="">
      <div class="modal-btns">
        <button type="button" class="btn-secondary" onclick="fecharModal()">Cancelar</button>
        <button type="submit" class="btn-primary">Enviar solicitação</button>
      </div>
    </form>
  </div>
</div>

<script>
  function abrirModal(id, nome) {
    document.getElementById('modalTreinadorId').value = id;
    document.getElementById('modalNome').textContent = nome;
    document.getElementById('modalContratar').classList.add('ativo');
  }
  function fecharModal() {
    document.getElementById('modalContratar').classList.remove('ativo');
  }
  // Fechar ao clicar no overlay
  document.getElementById('modalContratar').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
  });
</script>

</body>
</html>
