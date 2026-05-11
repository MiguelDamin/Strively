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
    $livres[] = $d;
  }
}
$distanciaLivreStr = implode(', ', $livres);

$tituloPagina = "Editar Evento";
include '../components/head.php';
include '../components/header.php';
?>

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
            echo $erros[$_GET['erro']] ?? 'Ocorreu um erro ao processar o formulário.';
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
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
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
          <div style="display: flex; gap: 16px; flex-wrap: wrap; margin: 8px 0;">
            <?php foreach ($opcoesPre as $opcao): ?>
            <label style="display: flex; align-items: center; gap: 6px; font-weight: 400; cursor: pointer;">
              <input type="checkbox" name="distancias_pre[]" value="<?= $opcao ?>" <?= in_array($opcao, $preMarcadas) ? 'checked' : '' ?>> <?= $opcao ?>
            </label>
            <?php endforeach; ?>
          </div>
          <input type="text" name="distancia_livre" value="<?= htmlspecialchars($distanciaLivreStr) ?>" placeholder="Outra distância (ex: 8km, 15km)">
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

        <button type="submit" class="btn-primary btn-full" style="margin-top: 16px;">Salvar Alterações</button>
        <a href="detalhe-evento.php?id=<?= htmlspecialchars($evento['id']) ?>" class="btn-secondary" style="width: 100%; text-align: center; padding: 12px 0;">Cancelar</a>

      </form>

    </div>

  </section>

</body>
</html>
