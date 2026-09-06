<?php
require_once __DIR__ . '/../config/conexao.php';

// Detectar se é home para o botão voltar mobile
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$isHome = ($uri === '/' || $uri === '/index.php' || strpos($uri, 'index.php') !== false);

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

// Otimizando: Contar notificações não lidas APENAS para os scripts visuais da UI
$notifCount = 0;
if (isset($_SESSION['id']) && isset($pdo)) {
    try {
        $stmt_n = $pdo->prepare("SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = ? AND lida = false");
        $stmt_n->execute([$_SESSION['id']]);
        $notifCount = (int)($stmt_n->fetch()['total'] ?? 0);
    } catch(Exception $e) {}
}
?>


<header>
  <nav style="display: flex; align-items: center;">

    <!-- Botão voltar mobile -->
    <?php if (!$isHome): ?>
    <button 
        onclick="history.back()" 
        aria-label="Voltar"
        style="
            display: none;
            background: none;
            border: none;
            padding: 8px 12px 8px 0;
            cursor: pointer;
            color: #fff;
            font-size: 1.8rem;
            line-height: 1;
            opacity: 0.9;
            -webkit-tap-highlight-color: transparent;
            flex-shrink: 0;
        "
        class="btn-voltar-mobile">
        ‹
    </button>
    <?php endif; ?>

    <!-- Logo -->
    <a class="nav-brand" href="/index.php" style="display: flex; align-items: center; margin-right: 20px;">
      <div class="logo-icon">
        <img src="/images/logo_branca.webp" alt="Strively" style="width:38px;height:38px;object-fit:contain;border-radius:10px;" />
      </div>
      <span>Strively</span>
    </a>

    <script>
      // Inject meId to JS so we can link accordingly
      window.Strively = window.Strively || {};
      window.Strively.meId = <?= json_encode(isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0) ?>;
    </script>

    <!-- Links -->
    <ul class="nav-links">
      <li style="display:flex;align-items:center;gap:12px;">
        <?php if (isset($_SESSION['id'])): ?>
        <div class="desktop-search-container" style="position:relative; display:flex; align-items:center;">
          <div id="desktop-search-expander" style="display:flex; align-items:center; background:rgba(255,255,255,0.15); border-radius:20px; overflow:hidden; width:34px; height:34px; transition:width 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor:pointer;">
            <svg id="desktop-search-icon" viewBox="0 0 24 24" style="width:16px; height:16px; fill:#fff; flex-shrink:0; margin-left:9px;"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            <input type="text" id="desktop-search-input" placeholder="Pesquisar..." autocomplete="off" style="border:none; background:transparent; color:#fff; outline:none; font-family:'Outfit',sans-serif; width:100%; padding-left:10px; padding-right:12px; opacity:0; transition:opacity 0.2s;">
          </div>
          <div id="desktop-search-results" style="display:none; position:absolute; top:calc(100% + 10px); left:0; width:280px; background:#fff; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.2); max-height:400px; overflow-y:auto; z-index:1000; padding:8px 0; border:1px solid rgba(0,0,0,0.05);"></div>
        </div>
        <?php endif; ?>
        <a href="/index.php">Início</a>
      </li>
      <li><a href="/pages/eventos.php">Eventos</a></li>
      <li><a href="/pages/divulgar-evento.php">Divulgue Eventos</a></li>
      <li><a href="/pages/comunidade.php">Comunidade</a></li>

      <?php if (isset($_SESSION['id']) && isset($me)): ?>
        <?php if ($me['perfil'] === 'treinador'): ?>
          <li><a href="/pages/alunos.php">Alunos</a></li>
          <li><a href="/pages/treinos.php">Meus Treinos</a></li>
        <?php else: ?>
          <li><a href="/pages/treinos.php">Treinos</a></li>
        <?php endif; ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['id'])): ?>

      <!-- Notificações Desktop -->
      <li style="display:flex;align-items:center;margin-right:15px;">
        <a href="/pages/notificacoes.php" style="position:relative;display:inline-flex;align-items:center;color:#fff;padding:4px;text-decoration:none;">
          <svg viewBox="0 0 24 24" style="width:24px;height:24px;fill:currentColor;"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-4c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v4z"/></svg>
          <?php if (!empty($notifCount) && $notifCount > 0): ?>
            <span style="position:absolute;top:-4px;right:-4px;background:#e53935;color:#fff;font-size:10px;font-weight:700;border-radius:50%;min-width:18px;height:18px;display:flex;align-items:center;justify-content:center;padding:0 3px;line-height:1;"><?= htmlspecialchars($notifCount, ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </a>
      </li>

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
      <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
      <span>Menu</span>
    </button>
  <?php else: ?>
    <!-- Visitantes não possuem sheet menu, mantemos o login simples -->
    <a href="/pages/login.php" class="bn-item">
      <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
      <span>Login</span>
    </a>
  <?php endif; ?>

  <!-- Comunidade -->
  <a href="/pages/comunidade.php" class="bn-item">
    <svg viewBox="0 0 24 24"><path d="M21 13.5l-3.5-7A2 2 0 0 0 15.7 5.5H12l2.6 5.1h2.6l-1.3-2.6-.4.2-1.4-2.8-4.2 1v1.9l-2 .5v2.8c0 .5-.4 1-1 1H4A1.5 1.5 0 0 0 2.5 14v3.4c0 1.1.9 2 2 2h13a2 2 0 0 0 2-2v-1.4l1.6-1.6c.3-.3.3-.8-.1-1zm-19-4h2.5v1.5H2v-1.5zm1 6h2.5v1.5H3v-1.5z"/></svg>
    <span>Comunidade</span>
  </a>

  <!-- Pesquisar (Mobile App) / Visitantes veem eventos -->
  <?php if (isset($_SESSION['id'])): ?>
    <button class="bn-item" onclick="abrirPesquisaMobile()">
      <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <span>Pesquisar</span>
    </button>
  <?php else: ?>
    <a href="/pages/eventos.php" class="bn-item">
      <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM5 8V6h14v2H5z"/></svg>
      <span>Eventos</span>
    </a>
  <?php endif; ?>

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
      <a href="/pages/eventos.php" class="sheet-item">
        <div class="sheet-icon">
          <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM5 8V6h14v2H5z"/></svg>
        </div>
        Eventos
      </a>
      <a href="/pages/perfil.php" class="sheet-item">
        <div class="sheet-icon">
          <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
        </div>
        Perfil
      </a>
      <a href="/pages/notificacoes.php" class="sheet-item" style="position:relative;">
        <div class="sheet-icon">
          <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-4c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v4z"/></svg>
        </div>
        Avisos
        <?php if (!empty($notifCount) && $notifCount > 0): ?>
            <span style="position:absolute;top:6px;right:10px;background:#e53935;color:#fff;font-size:9px;font-weight:700;border-radius:50%;min-width:16px;height:16px;display:flex;align-items:center;justify-content:center;padding:0 2px;"><?= htmlspecialchars($notifCount, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
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

<style>
@media (max-width: 768px) {
    header nav {
        display: flex !important;
        align-items: center !important;
    }
    .btn-voltar-mobile {
        display: inline-flex !important;
        align-items: center !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
}
@media (min-width: 769px) {
    .btn-voltar-mobile {
        display: none !important;
    }
}

/* Bloquear scroll do fundo quando modal aberto */
body.modal-aberto {
    overflow: hidden !important;
    height: 100% !important;
}

/* Overlay dos modais — garantir que absorve todos os eventos de toque */
.modal-overlay,
[id*="modal"],
.sheet-overlay,
.bottom-sheet-overlay {
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
}

/* O conteúdo interno do modal pode scrollar */
.modal-box,
.modal-body,
.modal-content,
.modal-card,
.modal-box-detalhes,
.modal-box-confirm,
.bottom-sheet {
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
}
</style>

<script>
  /* Desktop dropdown */
  function toggleDropdown() {
    document.getElementById('navDropdown').classList.toggle('aberto');
  }

  // Utilitários globais de scroll lock para modais
  function lockScroll() {
      document.documentElement.style.overflow = 'hidden';
      document.body.style.overflow = 'hidden';
      document.body.classList.add('modal-aberto');
      // Bloquear touchmove no body para iOS
      document.body.addEventListener('touchmove', preventTouch, { passive: false });
  }

  function unlockScroll() {
      document.documentElement.style.overflow = '';
      document.body.style.overflow = '';
      document.body.classList.remove('modal-aberto');
      document.body.removeEventListener('touchmove', preventTouch);
  }

  function preventTouch(e) {
      // Permitir scroll APENAS dentro de elementos de conteúdo do modal
      if (!e.target.closest('.modal-box, .modal-body, .modal-content, .modal-card, .modal-box-detalhes, .modal-box-confirm, .bottom-sheet')) {
          e.preventDefault();
      }
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
    lockScroll();
  }
  function fecharSheet() {
    document.getElementById('bottomSheet').classList.remove('sheet-open');
    document.getElementById('sheetOverlay').classList.remove('sheet-overlay-visible');
    unlockScroll();
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

  /* Global Toast System - Premium Redesign */
  window.Strively = {
    toast: function(message, type = 'success', duration = 4000) {
      let container = document.querySelector('.toast-container');
      if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
      }

      const toast = document.createElement('div');
      toast.className = 'toast-strively';
      
      const icons = {
        success: `<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`,
        error:   `<svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>`,
        info:    `<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>`
      };

      toast.innerHTML = `
        <div class="toast-icon ${type}">${icons[type] || icons.info}</div>
        <div class="toast-content">
          <div class="toast-message">${message}</div>
        </div>
        <div class="toast-progress">
          <div class="toast-progress-fill" style="animation-duration: ${duration}ms"></div>
        </div>
      `;

      container.appendChild(toast);

      // Auto hide
      const timeout = setTimeout(() => {
        dismiss();
      }, duration);

      function dismiss() {
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 450);
      }

      // Click to dismiss
      toast.onclick = () => {
        clearTimeout(timeout);
        dismiss();
      };
    }
  };

  /* Global Search Logic */
  document.addEventListener('DOMContentLoaded', () => {
    // DESKTOP SEARCH
    const dtExpander = document.getElementById('desktop-search-expander');
    const dtInput = document.getElementById('desktop-search-input');
    const dtResults = document.getElementById('desktop-search-results');
    
    if (dtExpander && dtInput && dtResults) {
      let isExpanded = false;
      let desktopTimeout = null;

      dtExpander.addEventListener('click', () => {
        if (!isExpanded) {
          isExpanded = true;
          dtExpander.style.width = '240px';
          dtInput.style.opacity = '1';
          dtInput.focus();
        }
      });

      dtInput.addEventListener('input', (e) => {
        const q = e.target.value.trim();
        clearTimeout(desktopTimeout);
        if (q.length < 1) { dtResults.style.display = 'none'; return; }
        
        desktopTimeout = setTimeout(() => doSearch(q, dtResults), 250);
      });

      document.addEventListener('click', (e) => {
        if (!dtExpander.contains(e.target) && !dtResults.contains(e.target)) {
          dtResults.style.display = 'none';
          if (dtInput.value.trim().length === 0 && isExpanded) {
            isExpanded = false;
            dtExpander.style.width = '34px';
            dtInput.style.opacity = '0';
          }
        }
      });
    }

    // MOBILE SEARCH
    const mInput = document.getElementById('mobile-search-input');
    const mResults = document.getElementById('mobile-search-results');
    
    if (mInput && mResults) {
      let mobileTimeout = null;
      mInput.addEventListener('input', (e) => {
        const q = e.target.value.trim();
        clearTimeout(mobileTimeout);
        if (q.length < 1) { mResults.innerHTML = ''; return; }
        mobileTimeout = setTimeout(() => doSearch(q, mResults), 250);
      });
    }
    
    // Core search fetch definition
    async function doSearch(q, resultsContainer) {
      try {
        const res = await fetch('/actions/search-users.php?q=' + encodeURIComponent(q));
        const users = await res.json();
        
        if (users.length === 0) {
          resultsContainer.innerHTML = '<div style="padding: 16px; color: #777; font-size: 0.95rem; text-align: center;">Nenhum usuário encontrado.</div>';
        } else {
          resultsContainer.innerHTML = users.map(u => {
            const foto = u.foto ? (u.foto.startsWith('http') ? u.foto : '/' + u.foto) : '';
            const link = (window.Strively && window.Strively.meId === u.id) ? '/pages/perfil.php' : '/pages/perfil-publico.php?id=' + u.id;
            const perfilStr = u.perfil === 'treinador' ? 'Treinador' : 'Corredor';
            const regex = new RegExp(`(${q})`, "gi");
            const highlightedName = u.nome.replace(regex, `<span style="color: #1DB954; font-weight: 800;">$1</span>`);
            
            return `
              <a href="${link}" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; text-decoration: none; transition: background 0.2s; border-bottom: 1px solid rgba(0,0,0,0.02);" onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='transparent'">
                ${foto ? 
                  `<img src="${foto}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 1px solid #eee;">` : 
                  `<div style="width: 44px; height: 44px; border-radius: 50%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 16px;">👤</div>`
                }
                <div style="display: flex; flex-direction: column;">
                  <span style="color: #111; font-weight: 600; font-size: 0.95rem; line-height: 1.2;">${highlightedName}</span>
                  <span style="color: #888; font-size: 0.8rem; font-weight: 500; margin-top: 2px;">${perfilStr}</span>
                </div>
              </a>
            `;
          }).join('');
        }
        resultsContainer.style.display = 'block';
      } catch(err) {
        console.error('Search failed', err);
      }
    }
  });

  // Funções overlay Mobile Search
  function abrirPesquisaMobile() {
    const overlay = document.getElementById('mobileSearchOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
        const input = document.getElementById('mobile-search-input');
        if (input) setTimeout(() => input.focus(), 100);
        lockScroll();
    }
  }

  function fecharPesquisaMobile() {
    const overlay = document.getElementById('mobileSearchOverlay');
    if (overlay) {
        overlay.style.display = 'none';
        document.getElementById('mobile-search-input').value = '';
        document.getElementById('mobile-search-results').innerHTML = '';
        unlockScroll();
    }
  }

</script>

<!-- Mobile Fullscreen Search Overlay -->
<div id="mobileSearchOverlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:#fff; z-index:99999; flex-direction:column;">
  <!-- Header Mobile Search -->
  <div style="display:flex; align-items:center; gap:12px; padding: 20px 20px 10px 20px; border-bottom: 1px solid #eee; background: #fff;">
    <button onclick="fecharPesquisaMobile()" style="background:none; border:none; padding: 0; color:#111; display:flex; align-items:center; justify-content:center; width:32px; height:32px;">
      <svg viewBox="0 0 24 24" style="width:24px; height:24px; fill:currentColor;"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    </button>
    <div style="flex:1; background:#f5f6f5; border-radius:12px; display:flex; align-items:center; padding:10px 14px;">
      <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: #999; margin-right: 8px; flex-shrink:0;"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      <input type="text" id="mobile-search-input" placeholder="Pesquisar..." autocomplete="off" style="border:none; background:transparent; width:100%; outline:none; font-family:'Outfit', sans-serif; font-size:1rem; color:#111;">
    </div>
  </div>
  
  <div id="mobile-search-results" style="flex:1; overflow-y:auto; padding: 10px 0; background: #fff;">
  </div>
</div>