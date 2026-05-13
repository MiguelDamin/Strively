<?php
require_once __DIR__ . '/../config/conexao.php';

// Auto-login via cookie
if (!isset($_SESSION['id']) && isset($_COOKIE['remember_token'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE remember_token = ? AND remember_expira > NOW()");
    $stmt->execute([$_COOKIE['remember_token']]);
    $u = $stmt->fetch();
    if ($u) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['id']     = $u['id'];
        $_SESSION['nome']   = $u['nome'];
        $_SESSION['perfil'] = $u['perfil'];
        $_SESSION['foto']   = $u['foto'];
        // renovar cookie
        $novoToken = bin2hex(random_bytes(32));
        $novaExpira = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60);
        $stmt2 = $pdo->prepare("UPDATE usuarios SET remember_token = ?, remember_expira = ? WHERE id = ?");
        $stmt2->execute([$novoToken, $novaExpira, $u['id']]);
        setcookie('remember_token', $novoToken, time() + 30 * 24 * 60 * 60, '/', '', true, true);
    }
}

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
 
// Busca dados do usuário logado para menus condicionais
if (isset($_SESSION['id']) && !isset($me)) {
  require_once __DIR__ . '/../config/conexao.php';
  $stmtMe = $pdo->prepare("SELECT perfil, treinador_id, status_vinculo FROM usuarios WHERE id = ?");
  $stmtMe->execute([$_SESSION['id']]);
  $me = $stmtMe->fetch();
}
 
if (isset($only_session) && $only_session === true) {
  return;
}
?>


<header>
  <nav>

    <!-- Logo -->
    <a class="nav-brand" href="/index.php">
      <div class="logo-icon">
        <img src="/images/logo_branca.webp" alt="Strively" style="width:38px;height:38px;object-fit:contain;border-radius:10px;" />
      </div>
      <span>Strively</span>
    </a>

    <!-- Links -->
    <ul class="nav-links">
      <li><a href="/index.php">Início</a></li>
      <li><a href="/pages/eventos.php">Eventos</a></li>
      <li><a href="/pages/divulgar-evento.php">Divulgue Eventos</a></li>
      <li><a href="/pages/equipamentos.php">Equipamentos</a></li>

      <?php if (isset($_SESSION['id']) && isset($me)): ?>
        <?php if ($me['perfil'] === 'treinador'): ?>
          <li><a href="/pages/alunos.php">Alunos</a></li>
          <li><a href="/pages/treinos.php">Meus Treinos</a></li>
        <?php else: ?>
          <li><a href="/pages/treinos.php">Treinos</a></li>
        <?php endif; ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['id'])): ?>

      <!-- USUÁRIO LOGADO — avatar + dropdown -->
      <li class="nav-usuario">

        <button class="nav-avatar-btn" onclick="toggleDropdown()">

          <?php if (!empty($_SESSION['foto'])): ?>
            <img
              src="<?= strpos($_SESSION['foto'], 'http') === 0 ? htmlspecialchars($_SESSION['foto']) : '/' . htmlspecialchars($_SESSION['foto']) ?>"
              alt="Foto de perfil" class="nav-avatar-img" />
          <?php else: ?>
            <div class="nav-avatar-padrao">
              <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z" />
              </svg>
            </div>
          <?php endif; ?>

          <span class="nav-usuario-nome">
            <?= htmlspecialchars(explode(' ', $_SESSION['nome'])[0]) ?>
          </span>

          <svg class="nav-seta" viewBox="0 0 24 24">
            <path d="M7 10l5 5 5-5z" />
          </svg>

        </button>

        <!-- Dropdown menu -->
        <div class="nav-dropdown" id="navDropdown">

          <a href="/pages/perfil.php" class="dropdown-item">
            <svg viewBox="0 0 24 24">
              <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z" />
            </svg>
            Ver perfil
          </a>

          <a href="/pages/configuracoes.php" class="dropdown-item">
            <svg viewBox="0 0 24 24">
              <path d="M19.14 12.94c.04-.3.06-.61.06-.94s-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.49.49 0 0 0-.59-.22l-2.39.96a7.01 7.01 0 0 0-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96a.48.48 0 0 0-.59.22L2.74 8.87a.47.47 0 0 0 .12.61l2.03 1.58c-.05.3-.07.62-.07.94s.02.64.07.94l-2.03 1.58a.47.47 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.37 1.04.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.57 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.47.47 0 0 0-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z" />
            </svg>
            Configurações
          </a>

          <?php if ($_SESSION['perfil'] === 'corredor'): ?>
            <a href="/pages/virar-treinador.php" class="dropdown-item">
              <svg viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z" />
              </svg>
              Modo treinador
            </a>
          <?php endif; ?>

          <div class="dropdown-divider"></div>

          <a href="/actions/action-logout.php" class="dropdown-item dropdown-sair">
            <svg viewBox="0 0 24 24">
              <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" />
            </svg>
            Sair
          </a>

        </div>
      </li>

      <?php else: ?>

      <!-- VISITANTE -->
      <li><a href="/pages/cadastro.php">Cadastro</a></li>
      <li><a href="/pages/login.php" class="nav-login">Login</a></li>

      <?php endif; ?>

    </ul>
  </nav>
</header>

<!-- =====================================================
     MOBILE BOTTOM NAV
     ===================================================== -->

<!-- Overlay para fechar o sheet -->
<div class="sheet-overlay" id="sheetOverlay" onclick="fecharSheet()"></div>

<!-- Nav inferior fixa -->
<nav class="bottom-nav">

  <!-- Início -->
  <a href="/index.php" class="bn-item">
    <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
    <span>Início</span>
  </a>

  <!-- Treinos -->
  <?php if (isset($_SESSION['id'])): ?>
    <a href="/pages/treinos.php" class="bn-item">
      <svg viewBox="0 0 24 24"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg>
      <span>Treinos</span>
    </a>
  <?php endif; ?>

  <!-- Menu / Sheet -->
  <?php if (isset($_SESSION['id'])): ?>
    <button class="bn-item" onclick="abrirSheet()">
      <svg viewBox="0 0 24 24"><path d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
      <!-- Opção de menu sem nome conforme pedido -->
    </button>
  <?php else: ?>
    <!-- Visitantes não possuem sheet menu, mantemos o login simples -->
    <a href="/pages/login.php" class="bn-item">
      <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
      <span>Login</span>
    </a>
  <?php endif; ?>

  <!-- Equipamentos -->
  <a href="/pages/equipamentos.php" class="bn-item">
    <svg viewBox="0 0 24 24"><path d="M22 18v-1.1c0-1-.6-1.9-1.5-2.2l-3.4-.9V11c0-1.1-.9-2-2-2H9L4.8 6.5C4.5 6.2 4 6 3.5 6H2v2h1.5l4 2.3v3.7L3.4 15.2C2.6 15.4 2 16.1 2 17v2h20v-1z"/></svg>
    <span>Equipamentos</span>
  </a>

  <!-- Eventos -->
  <a href="/pages/eventos.php" class="bn-item">
    <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM5 8V6h14v2H5z"/></svg>
    <span>Eventos</span>
  </a>

</nav>

<!-- Bottom Sheet (menu do perfil mobile) -->
<div class="bottom-sheet" id="bottomSheet">
  <div class="sheet-handle"></div>

  <?php if (isset($_SESSION['id'])): ?>
    <!-- Info do usuário -->
    <div class="sheet-user">
      <?php if (!empty($_SESSION['foto'])): ?>
        <img
          src="<?= strpos($_SESSION['foto'], 'http') === 0 ? htmlspecialchars($_SESSION['foto']) : '/' . htmlspecialchars($_SESSION['foto']) ?>"
          alt="Foto" class="sheet-user-foto" />
      <?php else: ?>
        <div class="sheet-user-avatar">
          <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
        </div>
      <?php endif; ?>
      <div class="sheet-user-info">
        <strong><?= htmlspecialchars(explode(' ', $_SESSION['nome'])[0]) ?></strong>
        <span><?= $_SESSION['perfil'] === 'treinador' ? 'Treinador' : 'Corredor' ?></span>
      </div>
    </div>

    <!-- Grid de opções -->
    <div class="sheet-grid">
      <a href="/pages/perfil.php" class="sheet-item">
        <div class="sheet-icon">
          <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
        </div>
        Perfil
      </a>
      <a href="/pages/configuracoes.php" class="sheet-item">
        <div class="sheet-icon">
          <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94s-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.49.49 0 0 0-.59-.22l-2.39.96a7.01 7.01 0 0 0-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96a.48.48 0 0 0-.59.22L2.74 8.87a.47.47 0 0 0 .12.61l2.03 1.58c-.05.3-.07.62-.07.94s.02.64.07.94l-2.03 1.58a.47.47 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.37 1.04.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.57 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.47.47 0 0 0-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
        </div>
        Config.
      </a>
      <a href="/pages/treinos.php" class="sheet-item">
        <div class="sheet-icon">
          <svg viewBox="0 0 24 24"><path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14l1.43 1.43L2 7.71l1.43 1.43L2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22l1.43-1.43L16.29 22l2.14-2.14 1.43 1.43 1.43-1.43-1.43-1.43L22 16.29z"/></svg>
        </div>
        Treinos
      </a>
      <?php if ($_SESSION['perfil'] === 'corredor'): ?>
        <a href="/pages/virar-treinador.php" class="sheet-item">
          <div class="sheet-icon">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
          </div>
          Treinador
        </a>
      <?php endif; ?>
      <a href="/actions/action-logout.php" class="sheet-item sheet-item-sair">
        <div class="sheet-icon sheet-icon-sair">
          <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
        </div>
        Sair
      </a>
    </div>
  <?php else: ?>
    <div class="sheet-grid">
      <a href="/pages/login.php" class="sheet-item">
        <div class="sheet-icon">
          <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
        </div>
        Login
      </a>
      <a href="/pages/cadastro.php" class="sheet-item">
        <div class="sheet-icon">
          <svg viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        </div>
        Cadastro
      </a>
    </div>
  <?php endif; ?>
</div>

<!-- Botão PWA Instalar APP (aparece via JS e só no Mobile usualmente) -->
<button id="btn-instalar" style="display: none; position: fixed; bottom: 85px; right: 20px; z-index: 1000; background-color: var(--green, #1DB954); color: #fff; border: none; border-radius: 50px; padding: 12px 20px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1); cursor: pointer;">
  Instalar App
</button>

<!-- Scripts: dropdown desktop + bottom sheet mobile + PWA -->
<script>
  /* Desktop dropdown */
  function toggleDropdown() {
    document.getElementById('navDropdown').classList.toggle('aberto');
  }
  document.addEventListener('click', function(e) {
    const usuario = document.querySelector('.nav-usuario');
    if (usuario && !usuario.contains(e.target)) {
      document.getElementById('navDropdown').classList.remove('aberto');
    }
  });

  /* Mobile bottom sheet */
  function abrirSheet() {
    document.getElementById('bottomSheet').classList.add('sheet-open');
    document.getElementById('sheetOverlay').classList.add('sheet-overlay-visible');
  }
  function fecharSheet() {
    document.getElementById('bottomSheet').classList.remove('sheet-open');
    document.getElementById('sheetOverlay').classList.remove('sheet-overlay-visible');
  }

  /* Marca aba ativa baseado na URL */
  (function() {
    var path = location.pathname;
    document.querySelectorAll('.bn-item').forEach(function(item) {
      var href = item.getAttribute('href');
      if (!href) return;
      if (path === href || path.endsWith(href.replace('/Strively', ''))) {
        item.classList.add('bn-active');
      }
    });
  })();

  /* PWA Install Prompt */
  let deferredPrompt;
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    document.getElementById('btn-instalar').style.display = 'block';
  });
  document.getElementById('btn-instalar')?.addEventListener('click', () => {
    if (deferredPrompt) {
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then((choiceResult) => {
        if (choiceResult.outcome === 'accepted') {
          console.log('User accepted the A2HS prompt');
        } else {
          console.log('User dismissed the A2HS prompt');
        }
        deferredPrompt = null;
        document.getElementById('btn-instalar').style.display = 'none';
      });
    }
  });
</script>