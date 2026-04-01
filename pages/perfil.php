<?php
// ==========================================================
// STRIVELY — pages/perfil.php
// Página de perfil do usuário logado
// ==========================================================

require_once '../components/header.php';

// Protege a página — só usuários logados
if (!isset($_SESSION['id'])) {
  header('Location: /Strively/pages/login.php');
  exit();
}

// Carrega conexão e busca dados atualizados do banco
require_once '../config/conexao.php';

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['id']]);
$usuario = $stmt->fetch();

// Atualiza a sessão com dados frescos do banco
$_SESSION['nome']  = $usuario['nome'];
$_SESSION['foto']  = $usuario['foto'];
?>

<?php $tituloPagina = "Meu Perfil"; ?>
<?php include '../components/head.php'; ?>

<body>

  <section class="perfil-section">

    <div class="perfil-card">

      <!-- ================================================
           FOTO DE PERFIL
           ================================================ -->
      <div class="perfil-avatar-area">

        <!-- Foto ou boneco padrão -->
        <div class="perfil-avatar">
          <?php if (!empty($usuario['foto'])): ?>
            <img
              src="<?= htmlspecialchars($usuario['foto']) ?>"
              alt="Foto de perfil"
              class="perfil-foto"
              id="previewFoto"
            />
          <?php else: ?>
            <div class="perfil-avatar-padrao" id="avatarPadrao">
              <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
              </svg>
            </div>
            <img
              src=""
              alt="Foto de perfil"
              class="perfil-foto"
              id="previewFoto"
              style="display:none;"
            />
          <?php endif; ?>

          <!-- Botão de editar foto -->
          <label for="inputFoto" class="perfil-editar-foto" title="Alterar foto">
            <svg viewBox="0 0 24 24">
              <path d="M12 15.2A3.2 3.2 0 0 1 8.8 12 3.2 3.2 0 0 1 12 8.8 3.2 3.2 0 0 1 15.2 12 3.2 3.2 0 0 1 12 15.2M12 7a5 5 0 0 0-5 5 5 5 0 0 0 5 5 5 5 0 0 0 5-5 5 5 0 0 0-5-5M2 4h3.5L7 2h10l1.5 2H22a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
            </svg>
          </label>
          <input type="file" id="inputFoto" accept="image/*" style="display:none;" onchange="previewImagem(this)"/>
        </div>

        <!-- Tipo do usuário -->
        <div class="perfil-tipo">
          <?php if ($usuario['perfil'] === 'treinador'): ?>
            <span class="badge-treinador">🏋️ Treinador verificado</span>
          <?php else: ?>
            <span class="badge-corredor">🏃 Corredor</span>
          <?php endif; ?>
        </div>

      </div>


      <!-- ================================================
           FORMULÁRIO DE EDIÇÃO
           ================================================ -->
      <form action="/Strively/actions/action-perfil.php" method="POST" enctype="multipart/form-data" class="perfil-form">

        <!-- Campo oculto para enviar a foto selecionada -->
        <input type="file" name="foto" id="fotoEnvio" style="display:none;" accept="image/*"/>

        <!-- Mensagem de sucesso -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'atualizado'): ?>
          <div class="auth-sucesso">Perfil atualizado com sucesso!</div>
        <?php endif; ?>

        <!-- Mensagem de erro -->
        <?php if (isset($_GET['erro'])): ?>
          <div class="auth-erro">
            <?php
              $erros = [
                'nome_vazio' => 'O nome não pode ficar vazio.',
                'foto_invalida' => 'Formato de imagem inválido. Use JPG, PNG ou WebP.',
                'upload_falhou' => 'Não foi possível enviar a foto. Tente novamente.',
              ];
              echo $erros[$_GET['erro']] ?? 'Ocorreu um erro. Tente novamente.';
            ?>
          </div>
        <?php endif; ?>

        <!-- Nome -->
        <div class="form-grupo">
          <label for="nome">Nome completo</label>
          <input
            type="text"
            id="nome"
            name="nome"
            value="<?= htmlspecialchars($usuario['nome']) ?>"
            required
          />
        </div>

        <!-- Email — somente leitura -->
        <div class="form-grupo">
          <label for="email">E-mail</label>
          <input
            type="email"
            id="email"
            value="<?= htmlspecialchars($usuario['email']) ?>"
            disabled
            class="input-desabilitado"
          />
          <small class="form-hint">O e-mail não pode ser alterado.</small>
        </div>

        <!-- Cidade -->
        <div class="form-grupo">
          <label for="cidade">Cidade</label>
          <input
            type="text"
            id="cidade"
            name="cidade"
            value="<?= htmlspecialchars($usuario['cidade'] ?? '') ?>"
            placeholder="Ex: São Paulo, SP"
          />
        </div>

        <button type="submit" class="btn-primary btn-full">Salvar alterações</button>

      </form>

    </div>

  </section>

</body>

<!-- Script para preview da foto antes de enviar -->
<script>
function previewImagem(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    const preview = document.getElementById('previewFoto');
    const padrao  = document.getElementById('avatarPadrao');
    const envio   = document.getElementById('fotoEnvio');

    reader.onload = function(e) {
      // Mostra preview da imagem selecionada
      preview.src = e.target.result;
      preview.style.display = 'block';
      if (padrao) padrao.style.display = 'none';

      // Copia o arquivo para o input real do formulário
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(input.files[0]);
      envio.files = dataTransfer.files;
    };

    reader.readAsDataURL(input.files[0]);
  }
}
</script>

</html>