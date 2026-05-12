<?php
ob_start();
require_once '../config/conexao.php';

try {
  $pdo->exec("DELETE FROM eventos WHERE data_evento < CURRENT_DATE - INTERVAL '2 days'");
} catch (PDOException $e) {}

$stmt = $pdo->prepare("
  SELECT e.*, u.nome AS autor_nome, u.foto AS autor_foto 
  FROM eventos e 
  LEFT JOIN usuarios u ON e.usuario_id = u.id 
  WHERE e.status = 'ativo' 
  ORDER BY e.data_evento ASC
");
$stmt->execute();
$eventos = $stmt->fetchAll();

$tituloPagina = "Próximos Eventos";
include '../components/head.php';
include '../components/header.php';
?>

<style>
.card-footer {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  width: 100%;
  padding-top: 8px;
}
.card-autor {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 0.85rem;
  color: var(--text-muted, #666);
}
.card-autor img {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  object-fit: cover;
}
.evento-distancias {
  min-height: 38px; /* reserva espaco quando nao ha badges */
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: 6px;
}
</style>

<body>

  <section class="eventos-section">

    <div class="eventos-header">
      <h1>Próximas Corridas</h1>
      <?php if (isset($_SESSION['id'])): ?>
        <a href="/pages/divulgar-evento.php" class="btn-primary">+ Divulgar um evento</a>
      <?php endif; ?>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'enviado'): ?>
      <div class="auth-sucesso" style="max-width:1200px; margin:0 auto 24px;">
        ✅ Evento divulgado com sucesso! Já está visível para toda a comunidade.
      </div>
    <?php endif; ?>

    <div class="eventos-grid">

      <?php if (!empty($eventos)): ?>
        <?php foreach ($eventos as $evento): ?>

          <div class="evento-card" onclick="window.open('<?= htmlspecialchars($evento['link_oficial']) ?>', '_blank')">

            <div class="evento-banner-wrap">
              <?php if (!empty($evento['banner'])): ?>
                <img
                  src="<?= htmlspecialchars($evento['banner']) ?>"
                  alt="<?= htmlspecialchars($evento['nome']) ?>"
                  class="evento-banner"
                  loading="lazy"
                  onerror="this.parentElement.innerHTML='<div class=\'evento-banner-placeholder\'><?= htmlspecialchars(addslashes($evento['nome'])) ?></div>'"
                >
              <?php else: ?>
                <div class="evento-banner-placeholder"><?= htmlspecialchars($evento['nome']) ?></div>
              <?php endif; ?>
            </div>

            <div class="evento-body">

              <h3 class="evento-nome"><?= htmlspecialchars($evento['nome']) ?></h3>

              <div class="evento-meta">
                <div class="evento-info">
                  <span style="font-size:14px;">📍</span> <?= htmlspecialchars($evento['cidade']) ?>
                </div>
                <div class="evento-info">
                  <span style="font-size:14px;">📅</span>
                  <?php
                    $data  = new DateTime($evento['data_evento']);
                    $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                    echo $data->format('d') . ' de ' . $meses[(int)$data->format('m') - 1] . ' de ' . $data->format('Y');
                  ?>
                </div>
              </div>

              <div class="evento-distancias">
                <?php foreach (explode(',', $evento['distancias']) as $dist): ?>
                  <?php $dist = trim($dist); if ($dist === '') continue; ?>
                  <span class="distancia-badge"><?= htmlspecialchars($dist) ?></span>
                <?php endforeach; ?>
              </div>

              <div class="evento-divider"></div>

              <div class="card-footer">
                <?php if (!empty($evento['autor_nome'])): ?>
                <div class="card-autor">
                  <?php if (!empty($evento['autor_foto'])): ?>
                    <img src="<?= htmlspecialchars($evento['autor_foto']) ?>" alt="autor">
                  <?php else: ?>
                    <span style="font-size:16px;">👤</span>
                  <?php endif; ?>
                  <span><?= htmlspecialchars(explode(' ', $evento['autor_nome'])[0]) ?></span>
                </div>
                <?php endif; ?>

                <div style="display:flex; justify-content:center; gap:8px; width:100%;">
                  <?php if (isset($_SESSION['id']) && ($_SESSION['id'] == $evento['usuario_id'] || $_SESSION['perfil'] === 'admin')): ?>
                    <a href="/pages/editar-evento.php?id=<?= $evento['id'] ?>" class="btn-secondary" style="flex:1; padding:8px 10px; font-size:0.85rem; background:#f5f5f5; border-color:#ccc; color:#333; text-align:center; max-width:140px; display:inline-block" onclick="event.stopPropagation()">Editar</a>
                  <?php endif; ?>
                  <a href="<?= htmlspecialchars($evento['link_oficial']) ?>" target="_blank" rel="noopener" class="btn-secondary" style="flex:1; padding:8px 10px; font-size:0.85rem; text-align:center; max-width:140px; display:inline-block" onclick="event.stopPropagation()">Detalhes</a>
                </div>
              </div>

            </div>

          </div>

        <?php endforeach; ?>

      <?php else: ?>

        <div class="eventos-vazio">
          <div class="eventos-vazio-icone">🏃</div>
          <h2>Nenhuma corrida por aqui ainda</h2>
          <p>Seja o primeiro a divulgar um evento para a comunidade!</p>
          <?php if (isset($_SESSION['id'])): ?>
            <a href="/pages/divulgar-evento.php" class="btn-primary" style="margin-top:16px;">Divulgar primeiro evento</a>
          <?php endif; ?>
        </div>

      <?php endif; ?>

    </div>

  </section>

</body>
</html>