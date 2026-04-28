<?php
// ==========================================================
// STRIVELY — pages/admin/treinadores.php
// Painel para aprovar ou reprovar solicitações de treinadores
// ==========================================================

$only_session = true;
require_once '../../components/header.php';
require_once '../../config/conexao.php';

// Proteção: só o admin acessa
// Coloque seu ID de usuário admin no .env como ADMIN_ID
$adminId = (int)($_ENV['ADMIN_ID'] ?? 0);

if (!isset($_SESSION['id']) || $_SESSION['id'] !== $adminId) {
  http_response_code(403);
  die('<h2 style="font-family:sans-serif;text-align:center;margin-top:80px;">403 — Acesso negado</h2>');
}



// Filtro de status
$filtro = $_GET['filtro'] ?? 'pendente';
$filtrosValidos = ['pendente', 'aprovado', 'reprovado'];
if (!in_array($filtro, $filtrosValidos)) $filtro = 'pendente';

// Busca solicitações com dados do usuário
$stmt = $pdo->prepare("
  SELECT t.*, u.nome, u.email, u.foto, u.cidade,
         t.created_at as solicitado_em
  FROM treinadores t
  JOIN usuarios u ON u.id = t.usuario_id
  WHERE t.status = ?
  ORDER BY t.created_at ASC
");
$stmt->execute([$filtro]);
$solicitacoes = $stmt->fetchAll();

// Contadores para as abas
$counts = [];
foreach (['pendente', 'aprovado', 'reprovado'] as $s) {
  $c = $pdo->prepare("SELECT COUNT(*) FROM treinadores WHERE status = ?");
  $c->execute([$s]);
  $counts[$s] = $c->fetchColumn();
}

unset($only_session);
$tituloPagina = "Admin — Treinadores";
include '../../components/head.php';
include '../../components/header.php';
?>

<style>
  .admin-wrap {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 24px;
  }

  .admin-titulo {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2.2rem;
    letter-spacing: 2px;
    margin-bottom: 4px;
  }

  .admin-sub {
    font-size: 0.9rem;
    color: var(--text-muted);
    margin-bottom: 28px;
  }

  /* Abas de filtro */
  .admin-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 28px;
    flex-wrap: wrap;
  }

  .admin-tab {
    padding: 8px 20px;
    border-radius: 100px;
    font-size: 0.88rem;
    font-weight: 600;
    text-decoration: none;
    border: 2px solid transparent;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .admin-tab-pendente   { background: #fffbea; color: #b58a00; border-color: #f5d87a; }
  .admin-tab-aprovado   { background: #f0fff4; color: var(--green-dark); border-color: #b2f5c8; }
  .admin-tab-reprovado  { background: #fff0f0; color: #cc0000; border-color: #ffcccc; }

  .admin-tab.ativo { filter: brightness(0.92); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }

  .admin-badge {
    background: rgba(0,0,0,0.1);
    border-radius: 100px;
    padding: 1px 8px;
    font-size: 0.78rem;
  }

  /* Card de solicitação */
  .req-card {
    background: #fff;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 24px 28px;
    margin-bottom: 16px;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 16px;
    align-items: start;
  }

  .req-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 14px;
  }

  .req-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--green);
    flex-shrink: 0;
  }

  .req-avatar-padrao {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--card-bg);
    border: 2px solid var(--green);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .req-avatar-padrao svg {
    width: 26px;
    height: 26px;
    fill: var(--text-muted);
  }

  .req-nome {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-main);
  }

  .req-email {
    font-size: 0.82rem;
    color: var(--text-muted);
  }

  .req-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px 24px;
    margin-bottom: 16px;
  }

  .req-campo {
    font-size: 0.85rem;
  }

  .req-label {
    color: var(--text-muted);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    display: block;
    margin-bottom: 2px;
  }

  .req-valor {
    color: var(--text-main);
    font-weight: 500;
  }

  .req-diploma-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--bg);
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    color: var(--text-main);
    text-decoration: none;
    font-weight: 500;
    transition: background 0.15s;
    margin-bottom: 16px;
  }

  .req-diploma-link:hover {
    background: var(--card-hover);
  }

  /* Formulário de ação */
  .req-acoes {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 180px;
  }

  .btn-aprovar {
    background: var(--green);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 16px;
    font-family: 'Outfit', sans-serif;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s;
    width: 100%;
  }

  .btn-aprovar:hover { background: var(--green-dark); }

  .btn-reprovar {
    background: #fff0f0;
    color: #cc0000;
    border: 2px solid #ffcccc;
    border-radius: 8px;
    padding: 8px 16px;
    font-family: 'Outfit', sans-serif;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s;
    width: 100%;
  }

  .btn-reprovar:hover { background: #ffe0e0; }

  .motivo-wrap {
    display: none;
  }

  .motivo-wrap.visivel {
    display: block;
  }

  .motivo-wrap textarea {
    width: 100%;
    padding: 8px 10px;
    border: 2px solid #ffcccc;
    border-radius: 8px;
    font-family: 'Outfit', sans-serif;
    font-size: 0.82rem;
    resize: vertical;
    min-height: 70px;
    outline: none;
    color: var(--text-main);
  }

  .motivo-wrap textarea:focus {
    border-color: #cc0000;
  }

  .btn-confirmar-reprovar {
    background: #cc0000;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    font-family: 'Outfit', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    width: 100%;
    margin-top: 4px;
  }

  .vazio {
    text-align: center;
    padding: 60px 24px;
    color: var(--text-muted);
    font-size: 1rem;
  }

  .vazio-icone { font-size: 2.5rem; margin-bottom: 12px; }

  .req-data {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 4px;
  }

  .motivo-reprovacao-exibido {
    background: #fff0f0;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.82rem;
    color: #aa0000;
    margin-top: 8px;
  }

  @media (max-width: 640px) {
    .req-card {
      grid-template-columns: 1fr;
    }
    .req-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<body>
<div class="admin-wrap">

  <h1 class="admin-titulo">Painel Admin — Treinadores</h1>
  <p class="admin-sub">Gerencie as solicitações de modo treinador enviadas pelos usuários.</p>

  <!-- Mensagens de feedback -->
  <?php if (isset($_GET['msg'])): ?>
    <div class="auth-sucesso" style="margin-bottom: 20px;">
      <?php
        $msgs = [
          'aprovado'  => '✅ Treinador aprovado com sucesso!',
          'reprovado' => '❌ Solicitação reprovada.',
        ];
        echo $msgs[$_GET['msg']] ?? 'Ação realizada.';
      ?>
    </div>
  <?php endif; ?>

  <!-- Abas de filtro -->
  <div class="admin-tabs">
    <a href="?filtro=pendente" class="admin-tab admin-tab-pendente <?= $filtro === 'pendente' ? 'ativo' : '' ?>">
      ⏳ Pendentes <span class="admin-badge"><?= $counts['pendente'] ?></span>
    </a>
    <a href="?filtro=aprovado" class="admin-tab admin-tab-aprovado <?= $filtro === 'aprovado' ? 'ativo' : '' ?>">
      ✅ Aprovados <span class="admin-badge"><?= $counts['aprovado'] ?></span>
    </a>
    <a href="?filtro=reprovado" class="admin-tab admin-tab-reprovado <?= $filtro === 'reprovado' ? 'ativo' : '' ?>">
      ❌ Reprovados <span class="admin-badge"><?= $counts['reprovado'] ?></span>
    </a>
  </div>

  <!-- Lista de solicitações -->
  <?php if (empty($solicitacoes)): ?>
    <div class="vazio">
      <div class="vazio-icone">🎉</div>
      <p>Nenhuma solicitação <?= $filtro ?> no momento.</p>
    </div>
  <?php else: ?>
    <?php foreach ($solicitacoes as $s): ?>
      <div class="req-card">

        <!-- LADO ESQUERDO — dados -->
        <div>
          <div class="req-header">
            <?php if (!empty($s['foto'])): ?>
              <img src="<?= htmlspecialchars($s['foto']) ?>" class="req-avatar" alt="Foto">
            <?php else: ?>
              <div class="req-avatar-padrao">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
              </div>
            <?php endif; ?>
            <div>
              <div class="req-nome"><?= htmlspecialchars($s['nome']) ?></div>
              <div class="req-email"><?= htmlspecialchars($s['email']) ?></div>
              <div class="req-data">Solicitado em: <?= date('d/m/Y H:i', strtotime($s['solicitado_em'])) ?></div>
            </div>
          </div>

          <div class="req-grid">
            <div class="req-campo">
              <span class="req-label">CREF</span>
              <span class="req-valor"><?= htmlspecialchars($s['cref'] ?? '—') ?></span>
            </div>
            <div class="req-campo">
              <span class="req-label">Especialidade</span>
              <span class="req-valor"><?= htmlspecialchars($s['especialidade'] ?? '—') ?></span>
            </div>
            <div class="req-campo">
              <span class="req-label">Faculdade</span>
              <span class="req-valor"><?= htmlspecialchars($s['faculdade'] ?? '—') ?></span>
            </div>
            <div class="req-campo">
              <span class="req-label">Assessoria</span>
              <span class="req-valor"><?= htmlspecialchars($s['assessoria'] ?: '—') ?></span>
            </div>
            <?php if (!empty($s['cidade'])): ?>
            <div class="req-campo">
              <span class="req-label">Cidade</span>
              <span class="req-valor"><?= htmlspecialchars($s['cidade']) ?></span>
            </div>
            <?php endif; ?>
          </div>

          <?php if (!empty($s['diploma_path'])): ?>
           <?php
// Gera URL assinada válida por 1 hora
$nomeArquivo = basename($s['diploma_path']);
$endpoint = $_ENV['SUPABASE_URL'] . '/storage/v1/object/sign/diplomas-treinadores/' . $nomeArquivo;
$opcoes = [
  'http' => [
    'method'  => 'POST',
    'header'  => "Authorization: Bearer " . $_ENV['SUPABASE_SERVICE_ROLE_KEY'] . "\r\nContent-Type: application/json\r\n",
    'content' => json_encode(['expiresIn' => 3600]),
    'ignore_errors' => true
  ]
];
$resposta = json_decode(file_get_contents($endpoint, false, stream_context_create($opcoes)), true);
$urlAssinada = $_ENV['SUPABASE_URL'] . '/storage/v1' . ($resposta['signedURL'] ?? '');
?>

<a href="<?= htmlspecialchars($urlAssinada) ?>" target="_blank" class="req-diploma-link">
  📄 Ver diploma / comprovante CREF
</a>
          <?php endif; ?>

          <?php if ($filtro === 'reprovado' && !empty($s['motivo_reprovacao'])): ?>
            <div class="motivo-reprovacao-exibido">
              <strong>Motivo da reprovação:</strong> <?= htmlspecialchars($s['motivo_reprovacao']) ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- LADO DIREITO — ações (só para pendentes) -->
        <?php if ($filtro === 'pendente'): ?>
        <div class="req-acoes">
          <!-- APROVAR -->
          <form action="/actions/action-avaliar-treinador.php" method="POST">
            <input type="hidden" name="treinador_id" value="<?= $s['id'] ?>">
            <input type="hidden" name="usuario_id"   value="<?= $s['usuario_id'] ?>">
            <input type="hidden" name="decisao"      value="aprovar">
            <button type="submit" class="btn-aprovar">Aprovar</button>
          </form>

          <!-- REPROVAR — com campo de motivo -->
          <form action="/actions/action-avaliar-treinador.php" method="POST" id="form-reprovar-<?= $s['id'] ?>">
            <input type="hidden" name="treinador_id" value="<?= $s['id'] ?>">
            <input type="hidden" name="usuario_id"   value="<?= $s['usuario_id'] ?>">
            <input type="hidden" name="decisao"      value="reprovar">

            <button type="button" class="btn-reprovar" onclick="mostrarMotivo(<?= $s['id'] ?>)">
               Reprovar
            </button>

            <div class="motivo-wrap" id="motivo-<?= $s['id'] ?>">
              <textarea name="motivo" placeholder="Descreva o motivo da reprovação (obrigatório)..." required></textarea>
              <button type="submit" class="btn-confirmar-reprovar">Confirmar reprovação</button>
            </div>
          </form>
        </div>
        <?php endif; ?>

      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<script>
  function mostrarMotivo(id) {
    const wrap = document.getElementById('motivo-' + id);
    wrap.classList.toggle('visivel');
  }
</script>

</body>
</html>