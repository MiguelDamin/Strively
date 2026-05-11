<?php
ob_start();
// ==========================================================
// STRIVELY — pages/detalhe-evento.php
// Exibe detalhes completos de um evento específico
// ==========================================================

require_once '../config/conexao.php';

$id = $_GET['id'] ?? 0;

if (!is_numeric($id) || $id <= 0) {
  header('Location: eventos.php');
  exit();
}

// Busca o evento
$stmt = $pdo->prepare("
  SELECT e.*, u.nome AS autor_nome, u.foto AS autor_foto 
  FROM eventos e 
  LEFT JOIN usuarios u ON e.usuario_id = u.id 
  WHERE e.id = ?
");
$stmt->execute([$id]);
$evento = $stmt->fetch();

if (!$evento) {
  header('Location: eventos.php');
  exit();
}

$tituloPagina = $evento['nome'];
include '../components/head.php';
include '../components/header.php';
?>

<body>

  <section class="detalhe-section">

    <div class="detalhe-body">
      
      <!-- BANNER -->
      <?php if (!empty($evento['banner'])): ?>
        <img src="<?= htmlspecialchars($evento['banner']) ?>" alt="<?= htmlspecialchars($evento['nome']) ?>" class="detalhe-banner">
      <?php else: ?>
        <div class="detalhe-banner-placeholder">
          <?= htmlspecialchars($evento['nome']) ?>
        </div>
      <?php endif; ?>

      <h1 class="detalhe-titulo"><?= htmlspecialchars($evento['nome']) ?></h1>
      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'edit_sucesso'): ?>
        <div style="background:#f0fff4;border:1px solid #b2f5c8;border-radius:10px;padding:12px 16px;font-size:0.88rem;color:#166534;margin-bottom:20px;text-align:center">
          ✅ Evento atualizado com sucesso!
        </div>
      <?php endif; ?>

      <?php if (!empty($evento['autor_nome'])): ?>
      <div style="display:flex; align-items:center; gap:8px; margin-top:-4px; margin-bottom:12px; color:var(--text-muted); font-size:0.95rem;">
        <?php if (!empty($evento['autor_foto'])): ?>
          <img src="<?= htmlspecialchars($evento['autor_foto']) ?>" alt="Foto" style="width:26px;height:26px;border-radius:50%;object-fit:cover;">
        <?php else: ?>
          <span>👤</span>
        <?php endif; ?>
        <span>Criado por <strong><?= htmlspecialchars($evento['autor_nome']) ?></strong></span>
      </div>
      <?php endif; ?>

      <div class="evento-meta" style="font-size: 1.1rem; margin: 16px 0;">
        <div class="evento-info">
          <span>📍</span> <?= htmlspecialchars($evento['cidade']) ?>
        </div>
        <div class="evento-info">
          <span>📅</span> 
          <?php
            $data = new DateTime($evento['data_evento']);
            $meses = ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"];
            echo $data->format('d') . ' de ' . $meses[(int)$data->format('m') - 1] . ' de ' . $data->format('Y');
          ?>
        </div>
      </div>

      <!-- DISTÂNCIAS -->
      <div class="evento-distancias" style="margin: 24px 0;">
        <?php
          $distanciasArr = explode(',', $evento['distancias']);
          foreach ($distanciasArr as $dist):
            if (trim($dist) === '') continue;
        ?>
          <span class="distancia-badge" style="padding: 6px 16px; font-size: 0.9rem;"><?= htmlspecialchars(trim($dist)) ?></span>
        <?php endforeach; ?>
      </div>

      <!-- DESCRIÇÃO -->
      <div class="detalhe-descricao">
        <?= nl2br(htmlspecialchars($evento['descricao'])) ?>
      </div>

      <!-- AÇÕES -->
      <div class="detalhe-acoes">
        <?php if (isset($_SESSION['id']) && ($_SESSION['id'] == $evento['usuario_id'] || $_SESSION['perfil'] === 'admin')): ?>
          <a href="/pages/editar-evento.php?id=<?= $evento['id'] ?>" class="btn-secondary" style="background:#f5f5f5; color:#333; border-color:#ccc;">✏️ Editar</a>
        <?php endif; ?>
        <?php if (!empty($evento['link_oficial'])): ?>
          <a href="<?= htmlspecialchars($evento['link_oficial']) ?>" target="_blank" class="btn-primary">Acessar Site Oficial</a>
        <?php endif; ?>
        
        <a href="eventos.php" class="btn-secondary">← Voltar para eventos</a>
      </div>

    </div>

  </section>

</body>
</html>