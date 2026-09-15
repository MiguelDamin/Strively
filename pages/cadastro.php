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
              'email_existente'    => 'Este e-mail já está cadastrado.',
              'senha_curta'        => 'A senha deve ter pelo menos 8 caracteres.',
              'senha_sem_numero'   => 'A senha deve conter pelo menos um número.',
              'senha_diferente'    => 'As senhas não coincidem.',
              'campos_vazios'      => 'Preencha todos os campos obrigatórios.',
              'termos_nao_aceitos' => 'Você precisa aceitar os Termos de Uso para criar uma conta.',
              'nome_invalido'      => 'Nome não pode conter números ou símbolos.',
              'email_invalido'     => 'Digite um e-mail válido (ex: nome@dominio.com).'
            ];
            echo htmlspecialchars($erros[$_GET['erro']] ?? 'Ocorreu um erro. Tente novamente.', ENT_QUOTES, 'UTF-8');
          ?>
        </div>
      <?php endif; ?>

      <!-- Formulário de cadastro -->
      <form action="../actions/action-cadastro.php" method="POST" class="auth-form" id="formCadastro" novalidate>

        <div class="form-grupo">
          <label for="nome">Nome completo</label>
          <input
            type="text"
            id="nome"
            name="nome"
            placeholder="Seu nome"
            required
            autocomplete="name"
            oninput="validarNome(this)"
          />
          <span id="nome-erro" style="display:none; color: #e74c3c; font-size: 0.8rem; margin-top: 5px;">Nome não pode conter números ou símbolos.</span>
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
            onblur="validarEmail(this)"
          />
          <span id="email-erro" style="display:none; color: #e74c3c; font-size: 0.8rem; margin-top: 5px;">Digite um e-mail válido (ex: nome@dominio.com).</span>
        </div>

        <input type="hidden" name="lat" id="lat" value="">
        <input type="hidden" name="lng" id="lng" value="">
        <input type="hidden" name="compartilha_localizacao" id="compartilha_localizacao" value="0">
        <input type="hidden" name="cidade" id="cidade" value="">

        <div class="form-grupo">
          <label for="senha">Senha</label>
          <div style="position: relative;">
            <input
              type="password"
              id="senha"
              name="senha"
              placeholder="mínimo 8 caracteres e 1 número"
              required
              autocomplete="new-password"
              oninput="validarSenha()"
              style="padding-right: 44px; width: 100%; box-sizing: border-box;"
            />
            <span class="toggle-password" onclick="togglePassword('senha', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; display: flex;">
              <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-closed" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8 a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8 a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </span>
          </div>
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
          <div style="position: relative;">
            <input
              type="password"
              id="senha_confirma"
              name="senha_confirma"
              placeholder="repita a senha"
              required
              autocomplete="new-password"
              oninput="validarConfirma()"
              style="padding-right: 44px; width: 100%; box-sizing: border-box;"
            />
            <span class="toggle-password" onclick="togglePassword('senha_confirma', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; display: flex;">
              <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-closed" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8 a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8 a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </span>
          </div>
          <span id="confirma-msg" class="confirma-msg" style="display:none;"></span>
        </div>

        <!-- Pergunta de Localização para Eventos -->
        <div class="form-grupo form-grupo-geo">
          <label class="geo-pergunta-label">
            📍 Deseja compartilhar sua localização para ver eventos perto de você?
          </label>
          <p class="geo-pergunta-sub">Mostraremos corridas e eventos esportivos próximos à sua região.</p>

          <div class="geo-botoes-wrap">
            <button type="button" class="btn-geo-choice" id="btn-geo-sim">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
              Sim, compartilhar
            </button>
            <button type="button" class="btn-geo-choice" id="btn-geo-nao">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              Não, prefiro digitar minha cidade
            </button>
          </div>

          <div id="geo-status-msg" class="geo-status-msg" style="display:none;"></div>

          <div id="bloco-cidade-manual" class="bloco-cidade-manual" style="display:none; margin-top: 14px;">
            <label for="cidade_manual" style="font-size:0.85rem; font-weight:500; color:var(--text-secondary); margin-bottom:6px; display:block;">
              Sua Cidade / Estado
            </label>
            <input
              type="text"
              id="cidade_manual"
              name="cidade_manual"
              placeholder="Ex: Porto Alegre, RS ou Curitiba, PR"
              autocomplete="address-level2"
            />
          </div>
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
              <a href="#" id="link-termos" rel="noopener noreferrer">Termos de Uso</a>
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

/* ── Pergunta de Localização no Cadastro ── */
.form-grupo-geo {
  background: var(--surface-2, #f5f6f5);
  border: 1px solid var(--border, #e5e7eb);
  border-radius: var(--radius-md, 14px);
  padding: 16px;
  margin-bottom: 20px;
}
.geo-pergunta-label {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-primary, #111);
  display: block;
  margin-bottom: 4px;
  line-height: 1.35;
}
.geo-pergunta-sub {
  font-size: 0.82rem;
  color: var(--text-secondary, #666);
  margin: 0 0 14px;
  line-height: 1.4;
}
.geo-botoes-wrap {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.btn-geo-choice {
  flex: 1;
  min-width: 140px;
  padding: 10px 14px;
  background: var(--surface, #fff);
  border: 1.5px solid var(--border-strong, #ccc);
  border-radius: var(--radius-sm, 10px);
  font-family: 'Outfit', sans-serif;
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--text-primary, #222);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s ease;
}
.btn-geo-choice:hover {
  border-color: var(--green, #1DB954);
  background: #fafbfa;
}
.btn-geo-choice.selected {
  border-color: var(--green, #1DB954);
  background: rgba(29, 185, 84, 0.1);
  color: var(--green-deeper, #0f7a34);
  font-weight: 600;
  box-shadow: 0 0 0 1px var(--green, #1DB954);
}
.geo-status-msg {
  margin-top: 10px;
  font-size: 0.82rem;
  padding: 8px 12px;
  border-radius: var(--radius-xs, 6px);
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 500;
}
.geo-status-msg.geo-loading {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}
.geo-status-msg.geo-success {
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
}
.geo-status-msg.geo-warn {
  background: #fffbeb;
  color: #b45309;
  border: 1px solid #fde68a;
}
</style>

<script>
// ── Salvar / restaurar dados do formulário no sessionStorage ──────────────
const FORM_KEY = 'strively_cadastro_draft';

function salvarRascunho() {
  const dados = {
    nome:                    document.getElementById('nome').value,
    email:                   document.getElementById('email').value,
    cidade:                  document.getElementById('cidade').value,
    cidade_manual:           document.getElementById('cidade_manual') ? document.getElementById('cidade_manual').value : '',
    lat:                     document.getElementById('lat').value,
    lng:                     document.getElementById('lng').value,
    compartilha_localizacao: document.getElementById('compartilha_localizacao').value,
    modo_geo:                document.getElementById('btn-geo-sim').classList.contains('selected') ? 'sim' : 
                             (document.getElementById('btn-geo-nao').classList.contains('selected') ? 'nao' : '')
    // senhas não são salvas por segurança
  };
  sessionStorage.setItem(FORM_KEY, JSON.stringify(dados));
}

function restaurarRascunho() {
  const raw = sessionStorage.getItem(FORM_KEY);
  if (!raw) return;
  try {
    const dados = JSON.parse(raw);
    if (dados.nome)   document.getElementById('nome').value   = dados.nome;
    if (dados.email)  document.getElementById('email').value  = dados.email;
    if (dados.cidade) document.getElementById('cidade').value = dados.cidade;
    if (dados.cidade_manual && document.getElementById('cidade_manual')) {
      document.getElementById('cidade_manual').value = dados.cidade_manual;
    }
    if (dados.lat) document.getElementById('lat').value = dados.lat;
    if (dados.lng) document.getElementById('lng').value = dados.lng;
    if (dados.compartilha_localizacao) {
      document.getElementById('compartilha_localizacao').value = dados.compartilha_localizacao;
    }

    if (dados.modo_geo === 'sim' && dados.lat && dados.lng) {
      document.getElementById('btn-geo-sim').classList.add('selected');
      const msg = document.getElementById('geo-status-msg');
      msg.style.display = 'block';
      msg.className = 'geo-status-msg geo-success';
      msg.innerHTML = '✅ Localização salva com sucesso!';
    } else if (dados.modo_geo === 'nao' || dados.cidade_manual) {
      document.getElementById('btn-geo-nao').classList.add('selected');
      document.getElementById('bloco-cidade-manual').style.display = 'block';
    }
  } catch(e) {}
}

// Restaura ao carregar
document.addEventListener('DOMContentLoaded', function() {
  restaurarRascunho();

  const btnSim = document.getElementById('btn-geo-sim');
  const btnNao = document.getElementById('btn-geo-nao');
  const blocoManual = document.getElementById('bloco-cidade-manual');
  const inputCidadeManual = document.getElementById('cidade_manual');
  const inputLat = document.getElementById('lat');
  const inputLng = document.getElementById('lng');
  const inputCompartilha = document.getElementById('compartilha_localizacao');
  const inputCidadeHidden = document.getElementById('cidade');
  const geoStatusMsg = document.getElementById('geo-status-msg');

  btnSim.addEventListener('click', function() {
    btnSim.classList.add('selected');
    btnNao.classList.remove('selected');
    blocoManual.style.display = 'none';

    if (!navigator.geolocation) {
      geoStatusMsg.style.display = 'block';
      geoStatusMsg.className = 'geo-status-msg geo-warn';
      geoStatusMsg.innerHTML = '⚠️ Seu navegador não suporta geolocalização. Por favor, digite sua cidade.';
      blocoManual.style.display = 'block';
      btnNao.classList.add('selected');
      btnSim.classList.remove('selected');
      if (inputCidadeManual) inputCidadeManual.focus();
      return;
    }

    geoStatusMsg.style.display = 'block';
    geoStatusMsg.className = 'geo-status-msg geo-loading';
    geoStatusMsg.innerHTML = '⏳ Obtendo sua localização...';
    btnSim.disabled = true;

    navigator.geolocation.getCurrentPosition(
      function(position) {
        inputLat.value = position.coords.latitude;
        inputLng.value = position.coords.longitude;
        inputCompartilha.value = '1';
        btnSim.disabled = false;
        geoStatusMsg.className = 'geo-status-msg geo-success';
        geoStatusMsg.innerHTML = '✅ Localização compartilhada com sucesso!';
        salvarRascunho();
      },
      function(error) {
        btnSim.disabled = false;
        inputLat.value = '';
        inputLng.value = '';
        inputCompartilha.value = '0';
        btnSim.classList.remove('selected');
        btnNao.classList.add('selected');
        geoStatusMsg.className = 'geo-status-msg geo-warn';
        geoStatusMsg.innerHTML = '⚠️ Não foi possível obter sua localização automaticamente. Por favor, digite sua cidade abaixo:';
        blocoManual.style.display = 'block';
        if (inputCidadeManual) inputCidadeManual.focus();
        salvarRascunho();
      },
      { timeout: 10000, enableHighAccuracy: false }
    );
  });

  btnNao.addEventListener('click', function() {
    btnNao.classList.add('selected');
    btnSim.classList.remove('selected');
    inputCompartilha.value = '0';
    inputLat.value = '';
    inputLng.value = '';
    geoStatusMsg.style.display = 'none';
    blocoManual.style.display = 'block';
    if (inputCidadeManual) inputCidadeManual.focus();
    salvarRascunho();
  });

  if (inputCidadeManual) {
    inputCidadeManual.addEventListener('input', function() {
      if (inputCidadeHidden) inputCidadeHidden.value = inputCidadeManual.value;
      salvarRascunho();
    });
  }
});

// Salva ao digitar em qualquer campo
['nome','email','cidade_manual'].forEach(id => {
  const el = document.getElementById(id);
  if (el) el.addEventListener('input', salvarRascunho);
});

// ── Link Termos de Uso: desktop = nova aba, mobile = mesma aba ────────────
document.getElementById('link-termos').addEventListener('click', function(e) {
  e.preventDefault();
  salvarRascunho();
  const termoUrl = '/pages/termos.php?from=cadastro';
  const isMobile = window.matchMedia('(max-width: 768px)').matches
                || ('ontouchstart' in window && window.innerWidth <= 1024);
  if (isMobile) {
    window.location.href = termoUrl;
  } else {
    window.open(termoUrl, '_blank', 'noopener,noreferrer');
  }
});

// ── Funções de UI ─────────────────────────────────────────────────────────
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

function validarNome(campo) {
  const regexInvalidos = /[^a-zA-ZàáâãäéêëíîïóôõöúûüçÀÁÂÃÄÉÊËÍÎÏÓÔÕÖÚÛÜÇ '\-]/g;
  const erroSpan = document.getElementById(campo.id + '-erro');
  if (regexInvalidos.test(campo.value)) {
    campo.value = campo.value.replace(regexInvalidos, '');
    erroSpan.style.display = 'block';
    clearTimeout(campo.timeoutMsg);
    campo.timeoutMsg = setTimeout(() => { erroSpan.style.display = 'none'; }, 3000);
  }
}

function validarSenha() {
  const campo = document.getElementById('senha');
  const val = campo.value;
  const feedback = document.getElementById('senha-feedback');
  const barra = document.getElementById('senha-barra');
  const regraTamanho = document.getElementById('regra-tamanho');
  const regraNumero  = document.getElementById('regra-numero');

  if (val.length === 0) { feedback.style.display = 'none'; return; }

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

  if (document.getElementById('senha_confirma').value) validarConfirma();
}

function validarConfirma() {
  const senha    = document.getElementById('senha').value;
  const confirma = document.getElementById('senha_confirma').value;
  const msg      = document.getElementById('confirma-msg');
  if (confirma.length === 0) { msg.style.display = 'none'; return; }
  msg.style.display = 'block';
  if (senha === confirma) {
    msg.textContent = '✓ Senhas coincidem';
    msg.className = 'confirma-msg ok';
  } else {
    msg.textContent = '✗ As senhas não coincidem';
    msg.className = 'confirma-msg erro';
  }
}

// ── Validação no submit ───────────────────────────────────────────────────
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
  // Limpa rascunho ao enviar com sucesso
  sessionStorage.removeItem(FORM_KEY);
});
</script>

<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>