<?php
// ==========================================================
// STRIVELY — pages/cadastro.php
// Página de cadastro de novo usuário
// ==========================================================

$only_session = true;
require_once '../components/header.php';

// Se já estiver logado, redireciona para home
if (isset($_SESSION['id'])) {
  header('Location: ../index.php');
  exit();
}
?>

<?php $tituloPagina = "Cadastro"; ?>
<?php include('../components/head.php'); ?>
<?php include('../components/header.php'); ?>

<body>

  <!-- =====================================================
       SEÇÃO DE CADASTRO
       ===================================================== -->
  <section class="auth-section">

    <div class="auth-card">

      <!-- Título -->
      <h1 class="auth-titulo">Criar conta</h1>
      <p class="auth-subtitulo">Junte-se à comunidade Strively</p>

      <!-- Mensagem de erro -->
      <?php if (isset($_GET['erro'])): ?>
        <div class="auth-erro">
          <?php
            $erros = [
              'email_existente' => 'Este e-mail já está cadastrado.',
              'senha_curta'     => 'A senha deve ter pelo menos 6 caracteres.',
              'campos_vazios'   => 'Preencha todos os campos obrigatórios.',
            ];
            echo $erros[$_GET['erro']] ?? 'Ocorreu um erro. Tente novamente.';
          ?>
        </div>
      <?php endif; ?>

      <!-- Formulário de cadastro -->
      <form action="../actions/action-cadastro.php" method="POST" class="auth-form">

        <div class="form-grupo">
          <label for="nome">Nome completo</label>
          <input
            type="text"
            id="nome"
            name="nome"
            placeholder="Seu nome"
            required
            autocomplete="name"
          />
        </div>

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
          <label for="cidade">Cidade</label>
          <input
            type="text"
            id="cidade"
            name="cidade"
            placeholder="Ex: São Paulo, SP"
          />
        </div>

        <div class="form-grupo">
          <label for="senha">Senha</label>
          <input
            type="password"
            id="senha"
            name="senha"
            placeholder="mínimo 6 caracteres"
            required
            autocomplete="new-password"
          />
        </div>

        <div class="form-grupo">
          <label for="senha_confirma">Confirmar senha</label>
          <input
            type="password"
            id="senha_confirma"
            name="senha_confirma"
            placeholder="repita a senha"
            required
            autocomplete="new-password"
          />
        </div>

        <button type="submit" class="btn-primary btn-full">Criar conta</button>

      </form>

      <!-- Link para login -->
      <p class="auth-link">
        Já tem uma conta?
        <a href="login.php">Entrar</a>
      </p>

    </div>

  </section>

</body>
</html>