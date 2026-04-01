<?php
// ==========================================================
// STRIVELY — pages/configuracoes.php
// Painel de Configurações do Usuário
// ==========================================================

$only_session = true;
require_once '../components/header.php';

// Protege a página — só usuários logados
if (!isset($_SESSION['id'])) {
  header('Location: /Strively/pages/login.php');
  exit();
}

$secao = $_GET['secao'] ?? 'seguranca'; // Padrão será Segurança
$etapa = $_GET['etapa'] ?? 'inicio';

?>

<?php $tituloPagina = "Configurações"; ?>
<?php include '../components/head.php'; ?>
<?php include '../components/header.php'; ?>

<style>
  /* Layout do Dashboard de Configurações (Estilo Claro / Minimalista) */
  .settings-container {
    display: flex;
    max-width: 1000px;
    margin: 40px auto;
    gap: 30px;
    padding: 0 24px;
    align-items: flex-start;
  }
  
  .settings-sidebar {
    width: 260px;
    flex-shrink: 0;
    position: sticky;
    top: 90px;
    background: #fff;
    border-radius: var(--radius);
    padding: 24px 16px;
    box-shadow: var(--shadow);
  }
  
  .settings-title {
    font-family: 'Bebas Neue', sans-serif;
    color: var(--text-main);
    font-size: 1.8rem;
    margin-bottom: 20px;
    padding-left: 12px;
    letter-spacing: 1px;
  }

  .settings-menu {
    list-style: none;
  }
  
  .settings-menu li {
    margin-bottom: 4px;
  }
  
  .settings-menu a {
    display: flex;
    align-items: center;
    padding: 12px 14px;
    color: var(--text-muted);
    text-decoration: none;
    border-radius: 10px;
    font-weight: 500;
    font-size: 0.95rem;
    transition: all 0.2s ease;
  }
  
  .settings-menu a:hover {
    background: var(--bg);
    color: var(--text-main);
  }
  
  .settings-menu a.ativo {
    background: rgba(29, 185, 84, 0.1);
    color: var(--green-dark);
    font-weight: 600;
  }
  
  .settings-content {
    flex: 1;
    min-width: 0;
  }
  
  .settings-pane {
    background: #fff;
    padding: 40px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
  }
  
  .settings-pane h2 {
    font-family: 'Bebas Neue', sans-serif;
    color: var(--text-main);
    margin-bottom: 24px;
    font-size: 2rem;
    border-bottom: 1px solid #eee;
    padding-bottom: 16px;
    letter-spacing: 1px;
  }

  .settings-pane p {
    color: var(--text-muted);
    font-size: 0.95rem;
    margin-bottom: 24px;
    line-height: 1.6;
  }

  @media (max-width: 768px) {
    .settings-container {
      flex-direction: column;
    }
    .settings-sidebar {
      width: 100%;
      position: static;
    }
  }
</style>

<body>

  <section class="settings-container">

    <!-- MENU LATERAL -->
    <aside class="settings-sidebar">
      <h1 class="settings-title">Configurações</h1>
      <ul class="settings-menu">
        <li>
          <a href="/Strively/pages/perfil.php">
            Conta e Perfil
          </a>
        </li>
        <li>
          <a href="?secao=seguranca" class="<?= $secao === 'seguranca' ? 'ativo' : '' ?>">
            Segurança
          </a>
        </li>
        <li>
          <a href="?secao=notificacoes" class="<?= $secao === 'notificacoes' ? 'ativo' : '' ?>">
            Notificações
          </a>
        </li>
      </ul>
    </aside>

    <!-- CONTEÚDO CENTRAL -->
    <main class="settings-content">
      
      <?php if ($secao === 'seguranca'): ?>
        
        <div class="settings-pane">
          <h2>Trocar a Senha</h2>

          <?php if ($etapa === 'inicio'): ?>
            <!-- PASSO 1: SOLICITAR NOVA SENHA -->
            <p>
              Para proteger sua conta de forma segura, enviaremos um código de verificação para o e-mail atrelado ao seu cadastro. O código tem validade de 10 minutos.
            </p>

            <form action="/Strively/actions/action-enviar-codigo.php" method="POST">

              <?php if (isset($_GET['erro'])): ?>
                <div class="auth-erro" style="margin-bottom: 20px;">
                  <?php
                    $erros = [
                      'senhas_diferentes' => 'As senhas não coincidem.',
                      'senha_curta'       => 'A nova senha deve ter pelo menos 6 caracteres.',
                      'email_falhou'      => 'Falha ao enviar e-mail. Tente novamente.',
                      'codigo_invalido'   => 'Código incorreto ou expirado. Tente novamente.'
                    ];
                    echo $erros[$_GET['erro']] ?? 'Ocorreu um erro.';
                  ?>
                </div>
              <?php endif; ?>

              <?php if (isset($_GET['msg']) && $_GET['msg'] === 'senha_alterada'): ?>
                <div class="auth-sucesso" style="margin-bottom: 20px;">Sua senha foi alterada com sucesso!</div>
              <?php endif; ?>

              <div class="form-grupo" style="margin-bottom: 16px;">
                <label for="nova_senha">Nova Senha</label>
                <input
                  type="password"
                  id="nova_senha"
                  name="nova_senha"
                  placeholder="Mínimo 6 caracteres"
                  required
                  minlength="6"
                />
              </div>

              <div class="form-grupo" style="margin-bottom: 24px;">
                <label for="confirma_senha">Confirmar Nova Senha</label>
                <input
                  type="password"
                  id="confirma_senha"
                  name="confirma_senha"
                  placeholder="Repita a nova senha"
                  required
                  minlength="6"
                />
              </div>

              <button type="submit" class="btn-primary" style="width: 100%; font-size: 1rem; padding: 14px 0;">Enviar Código de Verificação</button>

            </form>

          <?php elseif ($etapa === 'verificacao'): ?>
            <!-- PASSO 2: DIGITAR O CÓDIGO -->
            <p>
              Enviamos um código exclusivo de 6 dígitos para o seu e-mail. Digite-o abaixo para confirmar e finalizar a alteração de sua senha.
            </p>

            <form action="/Strively/actions/action-confirmar-senha.php" method="POST">

              <?php if (isset($_GET['erro'])): ?>
                <div class="auth-erro" style="margin-bottom: 20px;">
                  <?php
                    $erros = [
                      'codigo_invalido' => 'O código inserido está incorreto.',
                      'codigo_expirado' => 'O código expirou após 10 minutos. Solicite novamente.',
                    ];
                    echo $erros[$_GET['erro']] ?? 'Ocorreu um erro.';
                  ?>
                </div>
              <?php endif; ?>

              <div class="form-grupo" style="margin-bottom: 32px;">
                <label for="codigo">Código Numérico</label>
                <input
                  type="text"
                  id="codigo"
                  name="codigo"
                  placeholder="000000"
                  required
                  maxlength="6"
                  style="font-size: 24px; letter-spacing: 6px; font-weight: 600; font-family: monospace; text-align: center; max-width: 250px;"
                />
              </div>

              <div style="display: flex; gap: 16px; align-items: center;">
                <button type="submit" class="btn-primary" style="flex: 1; padding: 14px 0; font-size: 1rem;">Confirmar e Salvar</button>
                <a href="?secao=seguranca" style="color: var(--text-muted); text-decoration: underline; padding: 10px;">Cancelar</a>
              </div>

            </form>
          <?php endif; ?>
        </div>

      <?php elseif ($secao === 'notificacoes'): ?>
        
        <div class="settings-pane">
          <h2>Notificações</h2>
          <p>Gerencie quais alertas você deseja receber no seu celular ou por e-mail da nossa plataforma.</p>
          <div style="background: rgba(29, 185, 84, 0.1); color: var(--green-dark); padding: 16px; border-radius: 8px; font-weight: 500;">
             🚧 Em desenvolvimento. Em breve você poderá personalizar tudo por aqui!
          </div>
        </div>

      <?php endif; ?>

    </main>

  </section>

</body>
</html>
