<?php
ob_start();
// ==========================================================
// STRIVELY — pages/login.php
// Página de login do usuário
// ==========================================================

$only_session = true;
require_once '../components/header.php';

// Se já estiver logado, redireciona para home
if (isset($_SESSION['id'])) {
  header('Location: ../index.php');
  exit();
}
?>

<?php $tituloPagina = "Login"; ?>
<?php include('../components/head.php'); ?>
<?php include('../components/header.php'); ?>

<body>

  <!-- =====================================================
       SEÇÃO DE LOGIN
       ===================================================== -->
  <section class="auth-section">

    <div class="auth-card">

      <!-- Título -->
      <h1 class="auth-titulo">Entrar</h1>
      <p class="auth-subtitulo">Bem-vindo de volta ao Strively</p>

      <!-- Mensagem de erro (preenchida pelo PHP depois) -->
      <?php if (isset($_GET['erro'])): ?>
        <div class="auth-erro">
          <?php
            // Mensagens de erro amigáveis
            $erros = [
              'credenciais'    => 'E-mail ou senha incorretos.',
              'inativo'        => 'Sua conta está inativa. Entre em contato.',
              'email_invalido' => 'Digite um e-mail válido (ex: nome@dominio.com).'
            ];
            echo htmlspecialchars($erros[$_GET['erro']] ?? 'Ocorreu um erro. Tente novamente.', ENT_QUOTES, 'UTF-8');
          ?>
        </div>
      <?php endif; ?>

      <!-- Mensagens de sucesso -->
      <?php if (isset($_GET['msg'])): ?>
        <div class="auth-sucesso">
          <?php
            if ($_GET['msg'] === 'cadastrado') echo 'Conta criada com sucesso! Faça login para continuar.';
            if ($_GET['msg'] === 'conta_excluida') echo 'Sua conta foi excluída permanentemente. Sentiremos sua falta!';
            if ($_GET['msg'] === 'senha_redefinida') echo 'Sua senha foi redefinida com sucesso! Faça login para continuar.';
          ?>
        </div>
      <?php endif; ?>

      <!-- Formulário de login -->
      <form action="../actions/action-login.php" method="POST" class="auth-form">

        <div class="form-grupo">
          <label for="email">E-mail</label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="seu@email.com"
            required
            autocomplete="email"
            onblur="validarEmail(this)"
          />
          <span id="email-erro" style="display:none; color: #e74c3c; font-size: 0.8rem; margin-top: 5px;">Digite um e-mail válido (ex: nome@dominio.com).</span>
        </div>

        <div class="form-grupo">
          <label for="senha">Senha</label>
          <div style="position: relative;">
            <input
              type="password"
              id="senha"
              name="senha"
              placeholder="sua senha"
              required
              autocomplete="current-password"
              style="padding-right: 44px; width: 100%; box-sizing: border-box;"
            />
            <span class="toggle-password" onclick="togglePassword('senha', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; display: flex;">
              <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-closed" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8 a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8 a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </span>
          </div>
          <a href="esqueci-senha.php" style="display: block; text-align: right; font-size: 13px; color: #1DB954; text-decoration: none; margin-top: 8px;">Esqueceu a senha?</a>
        </div>

        <button type="submit" class="btn-primary btn-full">Entrar</button>

      </form>

      <!-- Link para cadastro -->
      <p class="auth-link">
        Não tem uma conta?
        <a href="cadastro.php">Criar conta grátis</a>
      </p>

    </div>

  </section>

<script>
function togglePassword(inputId, iconSpan) {
  const input = document.getElementById(inputId);
  const openIcon = iconSpan.querySelector('.eye-open');
  const closedIcon = iconSpan.querySelector('.eye-closed');
  
  if (input.type === 'password') {
    input.type = 'text';
    openIcon.style.display = 'none';
    closedIcon.style.display = 'block';
  } else {
    input.type = 'password';
    openIcon.style.display = 'block';
    closedIcon.style.display = 'none';
  }
}

function validarEmail(campo) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
  const erroSpan = document.getElementById(campo.id + '-erro');
  if (campo.value && !emailRegex.test(campo.value)) {
    erroSpan.style.display = 'block';
  } else {
    erroSpan.style.display = 'none';
  }
}
</script>

<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>