<?php
// ==========================================================
// STRIVELY — pages/eventos.php
// Listagem de eventos com limpeza automática de antigos
// ==========================================================

require_once '../config/conexao.php';
// session_start() já está no header.php que será incluído abaixo

// 1. LIMPEZA AUTOMÁTICA — Deleta eventos passados (mais de 2 dias)
try {
  $queryLimpeza = "DELETE FROM eventos WHERE data_evento < CURRENT_DATE - INTERVAL '2 days'";
  $pdo->exec($queryLimpeza);
} catch (PDOException $e) {
  // Ignora silenciadamente ou loga o erro
}

// 2. BUSCA EVENTOS ATIVOS
$stmt = $pdo->prepare("SELECT * FROM eventos WHERE status = 'ativo' ORDER BY data_evento ASC");
$stmt->execute();
$eventos = $stmt->fetchAll();

$tituloPagina = "Próximos Eventos";
include '../components/head.php';
include '../components/header.php';
?>

<body>

  <section class="eventos-section">

    <!-- CABEÇALHO DA PÁGINA -->
    <div class="eventos-header">
      <h1>Próximas Corridas</h1>

      <?php if (isset($_SESSION['id'])): ?>
        <a href="/Strively/pages/divulgar-evento.php" class="btn-primary">Divulgar um evento</a>
      <?php endif; ?>
    </div>

    <!-- MENSAGENS DE FEEDBACK -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'enviado'): ?>
      <div class="auth-sucesso" style="max-width: 1200px; margin: 0 auto 24px;">
        Evento divulgado com sucesso! Já está visível para toda a comunidade.
      </div>
    <?php endif; ?>

    <!-- GRID DE EVENTOS -->
    <div class="eventos-grid">

      <?php if (!empty($eventos)): ?>
        <?php foreach ($eventos as $evento): ?>
          <div class="evento-card" onclick="location.href='detalhe-evento.php?id=<?= $evento['id'] ?>'">

            <!-- BANNER -->
            <?php if (!empty($evento['banner'])): ?>
              <img src="<?= htmlspecialchars($evento['banner']) ?>" alt="<?= htmlspecialchars($evento['nome']) ?>" class="evento-banner">
            <?php else: ?>
              <div class="evento-banner-placeholder">
                <?= htmlspecialchars($evento['nome']) ?>
              </div>
            <?php endif; ?>

            <!-- CONTEÚDO -->
            <div class="evento-body">
              <h3 class="evento-nome"><?= htmlspecialchars($evento['nome']) ?></h3>

              <div class="evento-meta">
                <div class="evento-info">
                  <span>📍</span> <?= htmlspecialchars($evento['cidade']) ?>
                </div>
                <div class="evento-info">
                  <span>📅</span>
                  <?php
                    // Formata a data: 15 de Mar de 2025
                    $data = new DateTime($evento['data_evento']);
                    $meses = ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"];
                    echo $data->format('d') . ' de ' . $meses[(int)$data->format('m') - 1] . ' de ' . $data->format('Y');
                  ?>
                </div>
              </div>

              <!-- DISTÂNCIAS -->
              <div class="evento-distancias">
                <?php
                  $distanciasArr = explode(',', $evento['distancias']);
                  foreach ($distanciasArr as $dist):
                    if (trim($dist) === '') continue;
                ?>
                  <span class="distancia-badge"><?= htmlspecialchars(trim($dist)) ?></span>
                <?php endforeach; ?>
              </div>

              <a href="detalhe-evento.php?id=<?= $evento['id'] ?>" class="btn-secondary" style="width: 100%; text-align: center; padding: 10px 0;">Informações</a>
            </div>

          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="eventos-vazio">
          <div class="eventos-vazio-icone">🏃</div>
          <h2>Nenhuma corrida por aqui ainda</h2>
          <p>Seja o primeiro a divulgar um evento para a comunidade!</p>
          <?php if (isset($_SESSION['id'])): ?>
            <a href="/Strively/pages/divulgar-evento.php" class="btn-primary" style="margin-top:16px;">
              Divulgar primeiro evento
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div>

  </section>

</body>
</html>