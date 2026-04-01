<?php
// ==========================================================
// STRIVELY — pages/esqueci-senha.php
// Recuperação de conta via e-mail
// ==========================================================

$only_session = true;
require_once '../components/header.php';

if (isset($_SESSION['id'])) {
  header('Location: ../index.php');
  exit();
}

// Limpa qualquer erro de login acidental
$etapa = $_GET['etapa'] ?? 'email';
?>

<?php $tituloPagina = "Esqueci a Senha"; ?>
<?php include('../components/head.php'); ?>
<?php include('../components/header.php'); ?>

<body>
  <section class="auth-section">
    <div class="auth-card">

      <?php if ($etapa === 'email'): ?>
        <!-- =================================================
             ETAPA 1: DIGITAR E-MAIL
             ================================================= -->
        <h1 class="auth-titulo" style="font-size: 26px;">Recuperar Senha</h1>
        <p class="auth-subtitulo">Informe seu e-mail cadastrado e enviaremos um código de 6 dígitos para você redefinir sua senha.</p>

        <?php if (isset($_GET['erro'])): ?>
          <div class="auth-erro">
            <?php
              $erros = [
                'nao_encontrado' => 'Nenhuma conta encontrada com este e-mail.',
                'falha_email'    => 'Não foi possível enviar o e-mail. Tente mais tarde.'
              ];
              echo $erros[$_GET['erro']] ?? 'Ocorreu um erro. Tente novamente.';
            ?>
          </div>
        <?php endif; ?>

        <form action="../actions/action-esqueci-senha.php" method="POST" class="auth-form">
          <div class="form-grupo">
            <label for="email">E-mail Cadastrado</label>
            <input type="email" id="email" name="email" placeholder="seu@email.com" required autocomplete="email"/>
          </div>
          <button type="submit" class="btn-primary btn-full">Enviar Código de Recuperação</button>
        </form>

        <p class="auth-link" style="margin-top: 24px;">
          Lembrou a senha? <a href="login.php">Voltar para o Login</a>
        </p>

      <?php elseif ($etapa === 'codigo'): ?>
        <!-- =================================================
             ETAPA 2: DIGITAR CÓDIGO DO E-MAIL
             ================================================= -->
        <h1 class="auth-titulo" style="font-size: 26px;">Verificar Código</h1>
        <p class="auth-subtitulo">O código foi enviado para <b><?= htmlspecialchars($_SESSION['recuperacao_email'] ?? 'seu e-mail') ?></b>.</p>

        <?php if (isset($_GET['erro'])): ?>
          <div class="auth-erro">
            <?php
              $erros = [
                'codigo_invalido' => 'Código incorreto. Verifique e tente novamente.',
                'codigo_expirado' => 'O código expirou após 10 minutos. Solicite novamente.'
              ];
              echo $erros[$_GET['erro']] ?? 'Erro ao validar o código.';
            ?>
          </div>
        <?php endif; ?>

        <form action="../actions/action-verificar-codigo-esqueci.php" method="POST" class="auth-form">
          <div class="form-grupo">
            <label for="codigo" style="text-align: center;">Código de 6 dígitos</label>
            <input 
              type="text" 
              id="codigo" 
              name="codigo" 
              placeholder="000000" 
              required 
              maxlength="6"
              style="text-align: center; font-size: 24px; letter-spacing: 4px;"
            />
          </div>
          <button type="submit" class="btn-primary btn-full">Validar Código</button>
        </form>
        
        <p class="auth-link" style="margin-top: 24px;">
          <a href="?etapa=email">Não recebeu? Solicitar novamente.</a>
        </p>

      <?php elseif ($etapa === 'nova_senha'): ?>
        <!-- =================================================
             ETAPA 3: DEFINIR NOVA SENHA
             ================================================= -->
        <h1 class="auth-titulo" style="font-size: 26px;">Criar Nova Senha</h1>
        <p class="auth-subtitulo">Código verificado! Crie sua nova senha de acesso seguro.</p>

        <?php if (!isset($_SESSION['recuperacao_autorizada'])): ?>
            <?php 
              // Proteção: não se pode pular a etapa de código acessando a URL direto
              header('Location: ?etapa=email'); 
              exit(); 
            ?>
        <?php endif; ?>

        <?php if (isset($_GET['erro'])): ?>
          <div class="auth-erro">
            <?php
              $erros = [
                'senhas_diferentes' => 'As senhas não coincidem.',
                'senha_curta'       => 'Sua senha precisa ter pelos menos 6 caracteres.'
              ];
              echo $erros[$_GET['erro']] ?? 'Ocorreu um erro no formulário.';
            ?>
          </div>
        <?php endif; ?>

        <form action="../actions/action-redefinir-senha.php" method="POST" class="auth-form">
          <div class="form-grupo">
            <label for="nova_senha">Nova Senha</label>
            <input type="password" id="nova_senha" name="nova_senha" placeholder="Mínimo de 6 caracteres" required minlength="6"/>
          </div>
          <div class="form-grupo">
            <label for="confirma_senha">Confirmar Nova Senha</label>
            <input type="password" id="confirma_senha" name="confirma_senha" placeholder="Digite a nova senha novamente" required minlength="6"/>
          </div>
          <button type="submit" class="btn-primary btn-full">Salvar e Entrar</button>
        </form>

      <?php endif; ?>

    </div>
  </section>
</body>
</html>
