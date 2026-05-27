<?php
ob_start();
require_once '../config/conexao.php';

try {
  $pdo->exec("DELETE FROM eventos WHERE data_evento < CURRENT_DATE");
} catch (PDOException $e) {}

$stmt = $pdo->prepare("
  SELECT e.*, u.nome AS autor_nome, u.foto AS autor_foto 
  FROM eventos e 
  LEFT JOIN usuarios u ON e.usuario_id = u.id 
  WHERE e.status = 'ativo' 
  AND e.data_evento >= CURRENT_DATE
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

.evento-card {
    display: flex;
    flex-direction: column; /* empilha conteúdo verticalmente */
    height: 100%; /* ocupa altura total do grid */
    border-radius: var(--radius-lg);
    overflow: hidden;
    background: var(--surface);
    box-shadow: var(--shadow-md);
}

.evento-card .evento-banner {
    width: 100%;
    aspect-ratio: 3/4; /* proporção fixa para TODOS os banners */
    object-fit: cover;  /* corta a imagem sem distorcer */
    display: block;
}

.evento-card .evento-body {
    flex: 1; /* cresce para preencher espaço */
    display: flex;
    flex-direction: column;
    padding: 16px;
}

.evento-card .evento-nome {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1rem;
    margin: 0 0 8px;
    min-height: 2.5em; /* reserva espaço mínimo para títulos de 1-2 linhas */
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.evento-card .evento-info {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.evento-card .evento-distancias {
    min-height: 28px; /* altura fixa mesmo quando vazio */
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin: 8px 0;
}

.evento-card .card-footer {
    margin-top: auto; /* EMPURRA para o fundo do card */
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}

.evento-card .card-autor {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: var(--text-tertiary);
}

.evento-card .card-autor img {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
}

/* Grid dos eventos */
.eventos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 20px;
    align-items: stretch; /* IMPORTANTE: faz todos os cards terem mesma altura */
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
                  <?php
                    $autorEventoId = (int)$evento['usuario_id'];
                    $linkAutorEvento = (isset($_SESSION['id']) && $_SESSION['id'] === $autorEventoId)
                      ? '/pages/perfil.php'
                      : '/pages/perfil-publico.php?id=' . $autorEventoId;
                  ?>
                  <a href="<?= $linkAutorEvento ?>" 
                     class="card-autor" 
                     style="text-decoration:none;color:inherit;transition:opacity 0.2s;" 
                     onclick="event.stopPropagation()"
                     onmouseover="this.style.opacity='0.75'"
                     onmouseout="this.style.opacity='1'">
                    <?php if (!empty($evento['autor_foto'])): ?>
                      <img src="<?= htmlspecialchars($evento['autor_foto']) ?>" alt="autor"
                           style="width:28px;height:28px;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                      <span style="font-size:16px;">👤</span>
                    <?php endif; ?>
                    <span><?= htmlspecialchars(explode(' ', $evento['autor_nome'])[0]) ?></span>
                  </a>
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