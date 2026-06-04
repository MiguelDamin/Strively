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
.detalhe-wrapper {
  padding: 40px 20px;
  min-height: calc(100vh - 70px);
}
.detalhe-card {
  max-width: 760px;
  margin: 0 auto;
  background: #fff;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 12px 36px rgba(0,0,0,0.06);
  border: 1px solid rgba(0,0,0,0.04);
}
.detalhe-banner-container {
  width: 100%;
  height: 280px;
  position: relative;
  background: #f8f9fa;
}
.detalhe-banner {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
}
.detalhe-content {
  padding: 40px;
}
.detalhe-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 24px;
  margin-bottom: 24px;
}
.detalhe-titulo {
  font-size: 2.2rem;
  font-weight: 800;
  color: #111;
  margin: 0;
  line-height: 1.2;
  letter-spacing: -0.5px;
}
.evento-meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
  background: #f8f9fa;
  border-radius: 16px;
  padding: 20px;
}
.meta-item {
  display: flex;
  align-items: center;
  gap: 12px;
}
.meta-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}
.meta-text {
  display: flex;
  flex-direction: column;
}
.meta-label {
  font-size: 0.85rem;
  color: #666;
  font-weight: 500;
  margin-bottom: 2px;
}
.meta-value {
  font-size: 1rem;
  color: #111;
  font-weight: 600;
}
.distancias-wrapper {
  margin-bottom: 32px;
}
.distancias-title {
  font-size: 1rem;
  font-weight: 700;
  color: #111;
  margin-bottom: 12px;
}
.distancias-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.distancia-badge {
  background: rgba(29, 185, 84, 0.1);
  color: #15873e;
  padding: 8px 16px;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.95rem;
  border: 1px solid rgba(29, 185, 84, 0.2);
}
.detalhe-descricao {
  font-size: 1.05rem;
  line-height: 1.6;
  color: #444;
  margin-bottom: 40px;
}
.autor-badge {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 6px 16px 6px 6px;
  background: #f8f9fa;
  border-radius: 50px;
  text-decoration: none;
  color: #444;
  font-weight: 500;
  font-size: 0.9rem;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}
.autor-badge:hover {
  background: #fff;
  border-color: #ddd;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  color: #1DB954;
}
.autor-foto {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
}
.detalhe-acoes {
  display: flex;
  gap: 16px;
  border-top: 1px solid #eee;
  padding-top: 32px;
}
.btn-acao {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 1rem;
  text-decoration: none;
  transition: all 0.2s ease;
}
.btn-primario {
  background: #1DB954;
  color: #fff;
  border: none;
}
.btn-primario:hover {
  background: #15873e;
  transform: translateY(-2px);
}
.btn-secundario {
  background: #fff;
  color: #111;
  border: 1px solid #ddd;
}
.btn-secundario:hover {
  background: #f8f9fa;
  border-color: #ccc;
  transform: translateY(-2px);
}

@media (max-width: 768px) {
  .detalhe-wrapper {
    padding: 20px 16px;
  }
  .detalhe-banner-container {
    height: 220px;
  }
  .detalhe-content {
    padding: 24px;
  }
  .detalhe-header {
    flex-direction: column;
    gap: 16px;
  }
  .detalhe-titulo {
    font-size: 1.8rem;
  }
  .evento-meta-grid {
    grid-template-columns: 1fr;
  }
  .detalhe-acoes {
    flex-direction: column;
  }
  .btn-acao {
    width: 100%;
  }
}
</style>

<body>

  <section class="detalhe-wrapper">
    <div class="detalhe-card">
      
      <!-- BANNER -->
      <div class="detalhe-banner-container">
        <?php if (!empty($evento['banner'])): ?>
          <img src="<?= htmlspecialchars($evento['banner']) ?>" alt="<?= htmlspecialchars($evento['nome']) ?>" class="detalhe-banner">
        <?php else: ?>
          <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); color:#666; font-weight:700; font-size:1.5rem;">
            <?= htmlspecialchars($evento['nome']) ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="detalhe-content">
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'edit_sucesso'): ?>
          <script>
            window.addEventListener('DOMContentLoaded', () => {
              if (window.Strively && Strively.toast) {
                Strively.toast('Evento atualizado com sucesso!', 'success');
                const url = new URL(window.location);
                url.searchParams.delete('msg');
                window.history.replaceState({}, '', url);
              }
            });
          </script>
        <?php endif; ?>

        <div class="detalhe-header">
          <div>
            <h1 class="detalhe-titulo"><?= htmlspecialchars($evento['nome']) ?></h1>
            <?php if (!empty($evento['autor_nome'])): ?>
              <?php
                $autorDetId = (int)$evento['usuario_id'];
                $linkAutorDet = (isset($_SESSION['id']) && $_SESSION['id'] === $autorDetId)
                  ? '/pages/perfil.php'
                  : '/pages/perfil-publico.php?id=' . $autorDetId;
              ?>
              <div style="margin-top: 16px;">
                <a href="<?= $linkAutorDet ?>" class="autor-badge">
                  <?php if (!empty($evento['autor_foto'])): ?>
                    <img src="<?= htmlspecialchars($evento['autor_foto']) ?>" alt="Foto" class="autor-foto">
                  <?php else: ?>
                    <div style="width:32px;height:32px;border-radius:50%;background:#ddd;display:flex;align-items:center;justify-content:center;">👤</div>
                  <?php endif; ?>
                  <span>Criado por <strong><?= htmlspecialchars(explode(' ', $evento['autor_nome'])[0]) ?></strong></span>
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="evento-meta-grid">
          <div class="meta-item">
            <div class="meta-icon">📍</div>
            <div class="meta-text">
              <span class="meta-label">Local</span>
              <span class="meta-value"><?= htmlspecialchars($evento['cidade']) ?></span>
            </div>
          </div>
          <div class="meta-item">
            <div class="meta-icon">📅</div>
            <div class="meta-text">
              <span class="meta-label">Data do Evento</span>
              <span class="meta-value">
                <?php
                  $data = new DateTime($evento['data_evento']);
                  $meses = ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"];
                  echo $data->format('d') . ' de ' . $meses[(int)$data->format('m') - 1] . ', ' . $data->format('Y');
                ?>
              </span>
            </div>
          </div>
        </div>

        <?php 
          $distanciasArr = array_filter(array_map('trim', explode(',', $evento['distancias'])));
          if (!empty($distanciasArr)):
        ?>
        <div class="distancias-wrapper">
          <div class="distancias-title">Percursos Disponíveis</div>
          <div class="distancias-list">
            <?php foreach ($distanciasArr as $dist): ?>
              <span class="distancia-badge"><?= htmlspecialchars($dist) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="detalhe-descricao">
          <?= nl2br(htmlspecialchars($evento['descricao'])) ?>
        </div>

        <!-- AÇÕES -->
        <div class="detalhe-acoes">
          <a href="eventos.php" class="btn-acao btn-secundario">← Voltar</a>
          <?php if (isset($_SESSION['id']) && ($_SESSION['id'] == $evento['usuario_id'] || $_SESSION['perfil'] === 'admin')): ?>
            <a href="/pages/editar-evento.php?id=<?= $evento['id'] ?>" class="btn-acao btn-secundario">✏️ Editar Evento</a>
          <?php endif; ?>
          <?php if (!empty($evento['link_oficial'])): ?>
            <a href="#" 
               class="btn-acao btn-primario"
               onclick="return redirecionarExterno('<?= htmlspecialchars($evento['link_oficial']) ?>')">
               Inscrição Oficial ↗
            </a>
          <?php endif; ?>
        </div>

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

<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>