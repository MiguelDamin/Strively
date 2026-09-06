<?php
ob_start();
// ==========================================================
// STRIVELY — pages/editar-evento.php
// Formulário para editar evento existente (dono ou admin)
// ==========================================================

$only_session = true;
include '../components/header.php';
unset($only_session);

if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

require_once '../config/conexao.php';

$id = $_GET['id'] ?? 0;
if (!$id || !is_numeric($id)) {
  header('Location: /pages/eventos.php');
  exit();
}

$stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
$stmt->execute([$id]);
$evento = $stmt->fetch();

if (!$evento) {
  header('Location: /pages/eventos.php');
  exit();
}

if ($_SESSION['id'] != $evento['usuario_id'] && $_SESSION['perfil'] !== 'admin') {
  header('Location: /pages/eventos.php?erro=sem_permissao');
  exit();
}

$distanciasCadastradas = array_map('trim', explode(',', $evento['distancias']));
$opcoesPre = ['5km', '10km', '21km', '42km'];
$livres = [];
$preMarcadas = [];
foreach ($distanciasCadastradas as $d) {
  if (in_array($d, $opcoesPre)) {
    $preMarcadas[] = $d;
  } elseif (!empty($d)) {
    $num = preg_replace('/km/i', '', $d);
    $livres[] = trim($num);
  }
}
$distanciaLivreStr = implode(', ', $livres);

$tituloPagina = "Editar Evento";
include '../components/head.php';
include '../components/header.php';
?>

<style>
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.distancias-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin: 8px 0;
}
.distancia-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 400;
  cursor: pointer;
  white-space: nowrap;
}
.distancia-item input[type="checkbox"] {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  cursor: pointer;
}

@media (max-width: 768px) {
  .form-row { display: flex; flex-direction: column; gap: 16px; }
  .form-grupo input, .form-grupo select, .form-grupo textarea {
    font-size: 16px;
    padding: 12px;
    box-sizing: border-box;
  }
  .distancias-grid {
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }
  .distancia-item {
    font-size: 16px;
  }
  .btn-primary, .btn-secondary {
    padding: 14px;
    font-size: 16px;
    width: 100%;
  }
}
</style>

<body>

  <section class="auth-section" style="min-height: auto; padding: 60px 24px;">

    <div class="auth-card" style="max-width: 600px;">

      <h1 class="auth-titulo">Editar Evento</h1>
      <p class="auth-subtitulo">Atualize as informações do evento selecionado.</p>

      <?php if (isset($_GET['erro'])): ?>
        <div class="auth-erro" style="margin-bottom: 20px;">
          <?php
            $erros = [
              'campos_vazios'   => 'Por favor, preencha todos os campos obrigatórios.',
              'distancias'      => 'Selecione pelo menos uma distância ou preencha o campo livre.',
              'formato_imagem'  => 'Formato de imagem inválido. Use JPG, PNG ou WebP.',
              'upload_falhou'   => 'Ocorreu um erro no envio da imagem. Tente novamente.',
              'db_falhou'       => 'Ocorreu um erro ao atualizar no banco de dados.',
            ];
            echo htmlspecialchars($erros[$_GET['erro']] ?? 'Ocorreu um erro ao processar o formulário.', ENT_QUOTES, 'UTF-8');
          ?>
        </div>
      <?php endif; ?>

      <form action="../actions/action-editar-evento.php" method="POST" enctype="multipart/form-data" class="auth-form">
        <input type="hidden" name="evento_id" value="<?= htmlspecialchars($evento['id']) ?>">

        <!-- NOME DO EVENTO -->
        <div class="form-grupo">
          <label for="nome">Nome do Evento *</label>
          <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($evento['nome']) ?>" required>
        </div>

        <!-- DATA E CIDADE -->
        <div class="form-row">
          <div class="form-grupo">
            <label for="data_evento">Data do Evento *</label>
            <input type="date" id="data_evento" name="data_evento" value="<?= htmlspecialchars($evento['data_evento']) ?>" required>
          </div>
          <div class="form-grupo">
            <label for="cidade">Cidade *</label>
            <input type="text" id="cidade" name="cidade" value="<?= htmlspecialchars($evento['cidade']) ?>" required>
          </div>
        </div>

        <!-- DISTÂNCIAS -->
        <div class="form-grupo">
          <label>Distâncias Disponíveis * (marque pelo menos uma)</label>
          <div class="distancias-grid">
            <?php foreach ($opcoesPre as $opcao): ?>
            <label class="distancia-item">
              <input type="checkbox" name="distancias_pre[]" value="<?= htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8') ?>" <?= in_array($opcao, $preMarcadas) ? 'checked' : '' ?>> <?= htmlspecialchars($opcao, ENT_QUOTES, 'UTF-8') ?>
            </label>
            <?php endforeach; ?>
          </div>
          <input type="text" name="distancia_livre" value="<?= htmlspecialchars($distanciaLivreStr) ?>" placeholder="Outras distâncias (ex: 8, 15) — digite apenas números">
        </div>

        <!-- LINK OFICIAL -->
        <div class="form-grupo">
          <label for="link_oficial">Link Oficial do Evento *</label>
          <input type="url" id="link_oficial" name="link_oficial" value="<?= htmlspecialchars($evento['link_oficial']) ?>" required>
        </div>

        <!-- DESCRIÇÃO -->
        <div class="form-grupo">
          <label for="descricao">Descrição Completa *</label>
          <textarea id="descricao" name="descricao" rows="5" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 10px; font-family: inherit; font-size: 0.92rem; outline: none;" required><?= htmlspecialchars($evento['descricao']) ?></textarea>
        </div>

        <!-- BANNER -->
        <div class="form-grupo">
          <label for="banner">Banner do Evento (Deixe vazio para manter o atual)</label>
          <?php if (!empty($evento['banner'])): ?>
            <div style="margin-bottom:8px;">
              <img src="<?= htmlspecialchars($evento['banner']) ?>" style="max-height:80px; border-radius:8px;">
            </div>
          <?php endif; ?>
          <input type="file" id="banner" name="banner" accept="image/jpeg, image/png, image/webp">
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 16px; width: 100%;">Salvar Alterações</button>
        <a href="detalhe-evento.php?id=<?= htmlspecialchars($evento['id']) ?>" class="btn-secondary" style="display: block; width: 100%; text-align: center; margin-top: 8px;">Cancelar</a>

      </form>

    </div>

  </section>

  <script>
  document.getElementById('link_oficial')?.addEventListener('blur', function() {
      var val = this.value.trim();
      if (val && !val.match(/^https?:\/\//i)) {
          this.value = 'https://' + val;
      }
  });

  document.querySelector('input[name="distancia_livre"]')?.addEventListener('input', function() {
      this.value = this.value.replace(/[^0-9., ]/g, '');
  });
  </script>

<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>
