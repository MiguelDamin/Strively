<?php
ob_start();
// ==========================================================
// STRIVELY — pages/divulgar-evento.php
// Formulário para usuários logados divulgarem eventos
// ==========================================================

// Se não estiver logado, redireciona para login
// session_start() está no header.php, mas precisamos verificar a sessão
// Como header.php tem output HTML, incluímos apenas a lógica de sessão primeiro se necessário, 
// mas o Strively parece carregar o header em todas as páginas direto.

$only_session = true;
include '../components/header.php';
unset($only_session);

if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

$tituloPagina = "Divulgar Evento";
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

      <h1 class="auth-titulo">Divulgar Evento</h1>
      <p class="auth-subtitulo">Preencha os dados abaixo para cadastrar uma nova corrida na plataforma.</p>

      <?php if (isset($_GET['erro'])): ?>
        <div class="auth-erro" style="margin-bottom: 20px;">
          <?php
            $erros = [
              'campos_vazios'   => 'Por favor, preencha todos os campos obrigatórios.',
              'distancias'      => 'Selecione pelo menos uma distância ou preencha o campo livre.',
              'formato_imagem'  => 'Formato de imagem inválido. Use JPG, PNG ou WebP.',
              'upload_falhou'   => 'Ocorreu um erro no envio da imagem. Tente novamente.',
            ];
            echo $erros[$_GET['erro']] ?? 'Ocorreu um erro ao processar o formulário.';
          ?>
        </div>
      <?php endif; ?>

      <form action="../actions/action-divulgar-evento.php" method="POST" enctype="multipart/form-data" class="auth-form">

        <!-- NOME DO EVENTO -->
        <div class="form-grupo">
          <label for="nome">Nome do Evento *</label>
          <input type="text" id="nome" name="nome" placeholder="Ex: Maratona de São Paulo" required>
        </div>

        <!-- DATA E CIDADE -->
        <div class="form-row">
          <div class="form-grupo">
            <label for="data_evento">Data do Evento *</label>
            <input type="date" id="data_evento" name="data_evento" required>
          </div>
          <div class="form-grupo">
            <label for="cidade">Cidade *</label>
            <input type="text" id="cidade" name="cidade" placeholder="Ex: São Paulo, SP" required>
          </div>
        </div>

        <!-- DISTÂNCIAS -->
        <div class="form-grupo">
          <label>Distâncias Disponíveis * (marque pelo menos uma)</label>
          <div class="distancias-grid">
            <label class="distancia-item">
              <input type="checkbox" name="distancias_pre[]" value="5km"> 5km
            </label>
            <label class="distancia-item">
              <input type="checkbox" name="distancias_pre[]" value="10km"> 10km
            </label>
            <label class="distancia-item">
              <input type="checkbox" name="distancias_pre[]" value="21km"> 21km
            </label>
            <label class="distancia-item">
              <input type="checkbox" name="distancias_pre[]" value="42km"> 42km
            </label>
          </div>
          <input type="text" name="distancia_livre" placeholder="Outra distância (ex: 8km, 15km)">
        </div>

        <!-- LINK OFICIAL -->
        <div class="form-grupo">
          <label for="link_oficial">Link Oficial do Evento *</label>
          <input type="url" id="link_oficial" name="link_oficial" placeholder="https://www.siteoficial.com.br" required>
        </div>

        <!-- DESCRIÇÃO -->
        <div class="form-grupo">
          <label for="descricao">Descrição Completa *</label>
          <textarea id="descricao" name="descricao" rows="5" placeholder="Detalhes sobre percurso, retirada de kit, premiação, etc." style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 10px; font-family: inherit; font-size: 0.92rem; outline: none;" required></textarea>
        </div>

        <!-- BANNER -->
        <div class="form-grupo">
          <label for="banner">Banner do Evento * (JPG, PNG, WebP)</label>
          <input type="file" id="banner" name="banner" accept="image/jpeg, image/png, image/webp" required>
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 16px; width: 100%;">Publicar Evento</button>
        <a href="eventos.php" class="btn-secondary" style="display: block; width: 100%; text-align: center; margin-top: 8px;">Cancelar</a>

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
  </script>

</body>
</html>