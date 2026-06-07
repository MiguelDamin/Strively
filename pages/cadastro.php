<?php
ob_start();
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
              'email_existente'  => 'Este e-mail já está cadastrado.',
              'senha_curta'      => 'A senha deve ter pelo menos 8 caracteres.',
              'senha_sem_numero' => 'A senha deve conter pelo menos um número.',
              'senha_diferente'  => 'As senhas não coincidem.',
              'campos_vazios'    => 'Preencha todos os campos obrigatórios.',
              'termos_nao_aceitos' => 'Você precisa aceitar os Termos de Uso para criar uma conta.',
            ];
            echo $erros[$_GET['erro']] ?? 'Ocorreu um erro. Tente novamente.';
          ?>
        </div>
      <?php endif; ?>

      <!-- Formulário de cadastro -->
      <form action="../actions/action-cadastro.php" method="POST" class="auth-form" id="formCadastro">

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
            placeholder="mínimo 8 caracteres e 1 número"
            required
            autocomplete="new-password"
            oninput="validarSenha()"
          />
          <!-- Indicador de força -->
          <div id="senha-feedback" class="senha-feedback" style="display:none;">
            <div class="senha-barra-wrap">
              <div class="senha-barra" id="senha-barra"></div>
            </div>
            <ul class="senha-regras">
              <li id="regra-tamanho">Mínimo 8 caracteres</li>
              <li id="regra-numero">Pelo menos 1 número</li>
            </ul>
          </div>
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
            oninput="validarConfirma()"
          />
          <span id="confirma-msg" class="confirma-msg" style="display:none;"></span>
        </div>

        <!-- Checkbox de termos -->
        <div class="form-grupo form-grupo-check">
          <label class="check-label" for="aceitar_termos">
            <input
              type="checkbox"
              id="aceitar_termos"
              name="aceitar_termos"
              value="1"
              required
            />
            <span>
              Li e aceito os
              <a href="/pages/termos" target="_blank" rel="noopener noreferrer">Termos de Uso</a>
              do Strively
            </span>
          </label>
        </div>

        <button type="submit" class="btn-primary btn-full" id="btnCadastro">Criar conta</button>

      </form>

      <!-- Link para login -->
      <p class="auth-link">
        Já tem uma conta?
        <a href="login.php">Entrar</a>
      </p>

    </div>

  </section>

<style>
/* ── Indicador de força de senha ── */
.senha-feedback {
  margin-top: 8px;
}

.senha-barra-wrap {
  height: 5px;
  background: #e0e0e0;
  border-radius: 99px;
  overflow: hidden;
  margin-bottom: 8px;
}

.senha-barra {
  height: 100%;
  width: 0%;
  border-radius: 99px;
  transition: width 0.3s ease, background 0.3s ease;
}

.senha-regras {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.senha-regras li {
  font-size: 0.8rem;
  color: #999;
  padding-left: 18px;
  position: relative;
  transition: color 0.2s;
}

.senha-regras li::before {
  content: '✗';
  position: absolute;
  left: 0;
  color: #ccc;
  font-size: 0.75rem;
  transition: color 0.2s;
}

.senha-regras li.ok {
  color: #2ecc71;
}

.senha-regras li.ok::before {
  content: '✓';
  color: #2ecc71;
}

/* ── Confirmação ── */
.confirma-msg {
  font-size: 0.8rem;
  margin-top: 5px;
  display: block;
}

.confirma-msg.ok    { color: #2ecc71; }
.confirma-msg.erro  { color: #e74c3c; }

/* ── Checkbox termos ── */
.form-grupo-check {
  margin-top: 4px;
}

.check-label {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 0.92rem;
  color: #333;
  cursor: pointer;
  line-height: 1.5;
}

.check-label input[type="checkbox"] {
  width: 17px;
  height: 17px;
  flex-shrink: 0;
  margin-top: 2px;
  accent-color: #111;
  cursor: pointer;
}

.check-label a {
  color: #111;
  font-weight: 600;
  text-decoration: underline;
}

.check-label a:hover {
  opacity: 0.7;
}
</style>

<script>
function validarSenha() {
  const campo = document.getElementById('senha');
  const val = campo.value;
  const feedback = document.getElementById('senha-feedback');
  const barra = document.getElementById('senha-barra');
  const regraTamanho = document.getElementById('regra-tamanho');
  const regraNumero  = document.getElementById('regra-numero');

  if (val.length === 0) {
    feedback.style.display = 'none';
    return;
  }

  feedback.style.display = 'block';

  const temTamanho = val.length >= 8;
  const temNumero  = /\d/.test(val);

  regraTamanho.classList.toggle('ok', temTamanho);
  regraNumero.classList.toggle('ok', temNumero);

  const pontos = [temTamanho, temNumero, val.length >= 12].filter(Boolean).length;

  const cores = ['#e74c3c', '#f39c12', '#2ecc71'];
  const larguras = ['33%', '66%', '100%'];

  barra.style.width      = larguras[pontos - 1] || '10%';
  barra.style.background = pontos === 0 ? '#e0e0e0' : cores[pontos - 1];

  // Também revalida confirmação se já estiver preenchida
  if (document.getElementById('senha_confirma').value) {
    validarConfirma();
  }
}

function validarConfirma() {
  const senha    = document.getElementById('senha').value;
  const confirma = document.getElementById('senha_confirma').value;
  const msg      = document.getElementById('confirma-msg');

  if (confirma.length === 0) {
    msg.style.display = 'none';
    return;
  }

  msg.style.display = 'block';
  if (senha === confirma) {
    msg.textContent = '✓ Senhas coincidem';
    msg.className = 'confirma-msg ok';
  } else {
    msg.textContent = '✗ As senhas não coincidem';
    msg.className = 'confirma-msg erro';
  }
}

// Valida no submit antes de enviar ao servidor
document.getElementById('formCadastro').addEventListener('submit', function(e) {
  const senha    = document.getElementById('senha').value;
  const confirma = document.getElementById('senha_confirma').value;
  const termos   = document.getElementById('aceitar_termos').checked;

  if (senha.length < 8) {
    e.preventDefault();
    alert('A senha deve ter pelo menos 8 caracteres.');
    return;
  }
  if (!/\d/.test(senha)) {
    e.preventDefault();
    alert('A senha deve conter pelo menos um número.');
    return;
  }
  if (senha !== confirma) {
    e.preventDefault();
    alert('As senhas não coincidem.');
    return;
  }
  if (!termos) {
    e.preventDefault();
    alert('Você precisa aceitar os Termos de Uso para criar uma conta.');
    return;
  }
});
</script>

<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>