<?php
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
              'credenciais' => 'E-mail ou senha incorretos.',
              'inativo'     => 'Sua conta está inativa. Entre em contato.',
            ];
            echo $erros[$_GET['erro']] ?? 'Ocorreu um erro. Tente novamente.';
          ?>
        </div>
      <?php endif; ?>

      <!-- Mensagens de sucesso -->
      <?php if (isset($_GET['msg'])): ?>
        <div class="auth-sucesso">
          <?php
            if ($_GET['msg'] === 'cadastrado') echo 'Conta criada com sucesso! Faça login para continuar.';
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
          />
        </div>

        <div class="form-grupo">
          <label for="senha">Senha</label>
          <input
            type="password"
            id="senha"
            name="senha"
            placeholder="sua senha"
            required
            autocomplete="current-password"
          />
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

</body>
</html>