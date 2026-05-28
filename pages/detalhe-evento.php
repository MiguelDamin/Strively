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

<style>
.detalhe-body {
  max-width: 700px;
  margin: 0 auto;
}
.detalhe-banner {
  border-radius: 12px;
  width: 100%;
}
.detalhe-acoes {
  display: flex;
  gap: 16px;
}
.detalhe-acoes a {
  flex: 1;
  text-align: center;
}

@media (max-width: 768px) {
  .detalhe-body {
    padding: 16px;
    max-width: 100%;
  }
  .detalhe-titulo {
    font-size: 1.4rem;
    margin-top: 16px;
  }
  .detalhe-acoes {
    flex-direction: column;
    gap: 10px;
    margin-top: 20px;
  }
  .detalhe-acoes a {
    width: 100%;
    padding: 14px;
    font-size: 15px;
    border-radius: 10px;
  }
  .evento-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    font-size: 0.95rem;
  }
  .evento-distancias {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 12px 0;
  }
}
</style>

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
        <?php
          $autorDetId = (int)$evento['usuario_id'];
          $linkAutorDet = (isset($_SESSION['id']) && $_SESSION['id'] === $autorDetId)
            ? '/pages/perfil.php'
            : '/pages/perfil-publico.php?id=' . $autorDetId;
        ?>
        <a href="<?= $linkAutorDet ?>" 
           style="display:flex;align-items:center;gap:8px;margin-top:-4px;margin-bottom:12px; color:var(--text-muted); font-size:0.95rem;text-decoration:none;transition:color 0.2s;width:fit-content;"
           onmouseover="this.style.color='var(--green,#1DB954)'"
           onmouseout="this.style.color='var(--text-muted)'">
          <?php if (!empty($evento['autor_foto'])): ?>
            <img src="<?= htmlspecialchars($evento['autor_foto']) ?>" alt="Foto" style="width:26px;height:26px;border-radius:50%;object-fit:cover;">
          <?php else: ?>
            <span>👤</span>
          <?php endif; ?>
          <span>Criado por <strong><?= htmlspecialchars($evento['autor_nome']) ?></strong></span>
        </a>
      <?php endif; ?>

      <div class="evento-meta">
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
        <a href="eventos.php" class="btn-secondary">← Voltar para eventos</a>
        <?php if (isset($_SESSION['id']) && ($_SESSION['id'] == $evento['usuario_id'] || $_SESSION['perfil'] === 'admin')): ?>
          <a href="/pages/editar-evento.php?id=<?= $evento['id'] ?>" class="btn-secondary" style="background:#f5f5f5; color:#333; border-color:#ccc;">✏️ Editar</a>
        <?php endif; ?>
        <?php if (!empty($evento['link_oficial'])): ?>
          <a href="#" 
             class="link-externo-evento btn-primary"
             onclick="return redirecionarExterno('<?= htmlspecialchars($evento['link_oficial']) ?>')">
             Acessar Site Oficial
          </a>
        <?php endif; ?>
      </div>

    </div>

  </section>

  <div id="toast-redirect" style="
      display: none;
      position: fixed;
      top: 24px;
      left: 50%;
      transform: translateX(-50%);
      background: #0d0d0d;
      color: #fff;
      padding: 12px 20px;
      border-radius: 50px;
      font-size: 13px;
      font-weight: 500;
      z-index: 99999;
      white-space: nowrap;
      box-shadow: 0 4px 16px rgba(0,0,0,0.25);
      font-family: 'Outfit', sans-serif;
      max-width: 90vw;
      text-align: center;
      white-space: normal;
  ">
  </div>

  <script>
  function redirecionarExterno(url) {
      const toast = document.getElementById('toast-redirect');
      // Extrair domínio da URL
      let dominio = url;
      try { dominio = new URL(url).hostname; } catch(e) {}
      
      toast.textContent = 'Você está sendo redirecionado para ' + dominio + '...';
      toast.style.display = 'block';
      
      setTimeout(() => {
          window.location.href = url;
      }, 2000);
      
      return false; // previne navegação imediata
  }
  </script>

</body>
</html>