<!-- components/header.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (isset($only_session) && $only_session === true) {
  return;
}
?>

<header>
  <nav>

    <!-- Logo -->
    <a class="nav-brand" href="/Strively/index.php">

      <div class="logo-icon">
        <svg width="30" height="30" viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg">
          <polyline points="2,15 6,15 8,8 11,22 14,12 17,18 19,15 28,15" fill="none" stroke="#1DB954" stroke-width="2.2"
            stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </div>
      <!-- Nome da marca -->
      <span>Strively</span>

    </a>

    <!-- Links -->
    <ul class="nav-links">
      <li><a href="/Strively/index.php">Início</a></li>
      <li><a href="/Strively/pages/eventos.php">Eventos</a></li>
      <li><a href="/Strively/pages/divulgar-evento.php">Divulgue Eventos</a></li>
      <li><a href="/Strively/pages/equipamentos.php">Equipamentos</a></li>


      <?php if (isset($_SESSION['id'])): ?>

      <!-- USUÁRIO LOGADO — avatar + dropdown -->
      <li class="nav-usuario">

        <button class="nav-avatar-btn" onclick="toggleDropdown()">

          <?php if (!empty($_SESSION['foto'])): ?>
          <img
            src="<?= strpos($_SESSION['foto'], 'http') === 0 ? htmlspecialchars($_SESSION['foto']) : '/Strively/' . htmlspecialchars($_SESSION['foto'])?>"
            alt="Foto de perfil" class="nav-avatar-img" />
          <?php
  else: ?>
          <div class="nav-avatar-padrao">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path
                d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z" />
            </svg>
          </div>
          <?php
  endif; ?>

          <span class="nav-usuario-nome">
            <?= htmlspecialchars(explode(' ', $_SESSION['nome'])[0])?>
          </span>

          <svg class="nav-seta" viewBox="0 0 24 24">
            <path d="M7 10l5 5 5-5z" />
          </svg>

        </button>

        <!-- Dropdown menu -->
        <div class="nav-dropdown" id="navDropdown">

          <a href="/Strively/pages/perfil.php" class="dropdown-item">
            <svg viewBox="0 0 24 24">
              <path
                d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z" />
            </svg>
            Ver perfil
          </a>

          <a href="/Strively/pages/configuracoes.php" class="dropdown-item">
            <svg viewBox="0 0 24 24">
              <path
                d="M19.14 12.94c.04-.3.06-.61.06-.94s-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.49.49 0 0 0-.59-.22l-2.39.96a7.01 7.01 0 0 0-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96a.48.48 0 0 0-.59.22L2.74 8.87a.47.47 0 0 0 .12.61l2.03 1.58c-.05.3-.07.62-.07.94s.02.64.07.94l-2.03 1.58a.47.47 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.37 1.04.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.57 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.47.47 0 0 0-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z" />
            </svg>
            Configurações
          </a>

          <?php if ($_SESSION['perfil'] === 'corredor'): ?>
          <a href="/Strively/pages/virar-treinador.php" class="dropdown-item">
            <svg viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z" />
            </svg>
            Modo treinador
          </a>
          <?php
  endif; ?>

          <div class="dropdown-divider"></div>

          <a href="/Strively/actions/action-logout.php" class="dropdown-item dropdown-sair">
            <svg viewBox="0 0 24 24">
              <path
                d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" />
            </svg>
            Sair
          </a>

        </div>
      </li>

      <?php
else: ?>

      <!-- VISITANTE -->
      <li><a href="/Strively/pages/cadastro.php">Cadastro</a></li>
      <li><a href="/Strively/pages/login.php" class="nav-login">Login</a></li>

      <?php
endif; ?>

    </ul>
  </nav>
</header>

<!-- Fecha dropdown ao clicar fora -->
<script>
  function toggleDropdown() {
    document.getElementById('navDropdown').classList.toggle('aberto');
  }

document.addEventListener('click', function(e) {
    const usuario = document.querySelector('.nav-usuario');
    if (usuario && !usuario.contains(e.target)) {
      document.getElementById('navDropdown').classList.remove('aberto');
    }
  });
</script>