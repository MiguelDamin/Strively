<?php
require_once 'config/conexao.php';
$tituloPagina = "Início";

// Fetch upcoming active events for the carousel
$stmt = $pdo->prepare("SELECT id, nome, cidade, data_evento, banner FROM eventos WHERE status = 'ativo' ORDER BY data_evento ASC LIMIT 10");
$stmt->execute();
$eventos_carrossel = $stmt->fetchAll();
?>
<?php include('components/head.php'); ?>
<?php include('components/header.php'); ?>

<style>
/* =====================================================
   CARROSSEL ESTILO NETFLIX
   ===================================================== */
.netflix-section {
  padding: 60px 0;
  background: linear-gradient(180deg, #f8f9fa 0%, #fff 100%);
  overflow: hidden;
}

.nc-header {
  text-align: center;
  margin-bottom: 30px;
}
.nc-header h2 {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 2.4rem;
  letter-spacing: 1.5px;
  color: #111;
  margin: 0;
}
.nc-header p {
  color: var(--text-muted, #777);
  font-size: 0.95rem;
}

.nc-container {
  position: relative;
  max-width: 900px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 480px; 
}

.nc-track {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  perspective: 1000px;
}

.nc-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: none;
  background: #fff;
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 20;
  transition: all 0.2s;
}
.nc-nav:hover { background: var(--green); }
.nc-nav:hover svg { fill: #fff; }
.nc-nav svg { width: 22px; height: 22px; fill: #333; transition: fill 0.2s; }

.nc-prev { left: 10px; }
.nc-next { right: 10px; }

/* The Cards */
.nc-card {
  position: absolute;
  width: 280px;
  height: 420px;
  border-radius: 18px;
  overflow: hidden;
  background: #fff;
  display: flex;
  flex-direction: column;
  transition: transform 0.5s cubic-bezier(0.25, 1, 0.5, 1), 
              opacity 0.5s cubic-bezier(0.25, 1, 0.5, 1), 
              filter 0.5s cubic-bezier(0.25, 1, 0.5, 1),
              box-shadow 0.5s cubic-bezier(0.25, 1, 0.5, 1);
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);

  /* Start hidden by default to avoid flash */
  opacity: 0;
  pointer-events: none;
  transform: scale(0.7);
  z-index: 1;
}

/* Card States */
.nc-center {
  transform: translateX(0) scale(1);
  opacity: 1;
  filter: none;
  z-index: 10;
  box-shadow: 0 20px 50px rgba(0,0,0,0.2);
  pointer-events: auto;
  border: 2.5px solid var(--green);
}

.nc-left {
  transform: translateX(-190px) scale(0.85);
  opacity: 0.55;
  filter: blur(2.5px);
  z-index: 5;
  pointer-events: auto;
}

.nc-right {
  transform: translateX(190px) scale(0.85);
  opacity: 0.55;
  filter: blur(2.5px);
  z-index: 5;
  pointer-events: auto;
}

.nc-hidden {
  transform: translateX(0) scale(0.7);
  opacity: 0;
  filter: blur(4px);
  z-index: 1;
  pointer-events: none;
}

/* Card Internals */
.nc-capa {
  flex: 0 0 65%;
  background-color: #f97316; /* Fallback laranja */
  background-size: cover;
  background-position: center;
  position: relative;
  display: flex;
  align-items: flex-end;
  padding: 24px;
}
.nc-capa::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0) 80%);
}
.nc-nome-destaque {
  position: relative;
  z-index: 2;
  color: #fff;
  font-family: 'Bebas Neue', sans-serif;
  font-size: 2rem;
  line-height: 1.1;
  letter-spacing: 1px;
  margin: 0;
  text-shadow: 0 2px 8px rgba(0,0,0,0.5);
}

.nc-info {
  flex: 1;
  background: #fff;
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
}
.nc-info h4 {
  font-family: 'Outfit', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  color: #111;
  margin: 0 0 4px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.nc-local, .nc-data {
  margin: 0 0 2px 0;
  font-size: 0.8rem;
  color: #666;
}
.nc-btn {
  margin-top: auto;
  align-self: flex-start;
  padding: 8px 20px;
  border-radius: 100px;
  border: 1.5px solid var(--green);
  color: var(--green);
  font-weight: 700;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  text-decoration: none;
  transition: all 0.2s;
}
.nc-center .nc-btn {
  background: var(--green);
  color: #fff;
}
.nc-btn:hover {
  background: #199e46 !important;
  color: #fff;
  border-color: #199e46;
}

@media(max-width: 768px) {
  .nc-left { transform: translateX(-120px) scale(0.85); opacity: 0.3; }
  .nc-right { transform: translateX(120px) scale(0.85); opacity: 0.3; }
}
</style>
<body>

  <!-- =====================================================
       HERO — seção principal centralizada
       ===================================================== -->
  <section class="hero">

    <h1>Corra <span>Mais Longe</span><br>Com o Strively</h1>

    <p>Conecte-se com treinadores, descubra eventos de corrida perto de você e compartilhe equipamentos com a comunidade.</p>

    <div class="hero-buttons">
      <?php if (!isset($_SESSION['id'])): ?>
        <!-- VISITANTE -->
        <a href="pages/cadastro.php" class="btn-primary">Criar conta grátis</a>
        <a href="pages/eventos.php" class="btn-secondary">Ver eventos</a>

      <?php elseif (isset($me) && $me['perfil'] === 'treinador'): ?>
        <!-- TREINADOR -->
        <a href="pages/eventos.php" class="btn-secondary">Ver eventos</a>
        <a href="/pages/alunos.php" class="btn-primary">Ver alunos</a>

      <?php elseif (isset($me) && !empty($me['treinador_id']) && $me['status_vinculo'] === 'aceito'): ?>
        <!-- CORREDOR COM TREINADOR ACEITO -->
        <a href="pages/eventos.php" class="btn-secondary">Ver eventos</a>
        <a href="/pages/treinos.php" class="btn-primary">Ver treinos</a>

      <?php else: ?>
        <!-- CORREDOR SEM TREINADOR OU PENDENTE -->
        <a href="pages/eventos.php" class="btn-secondary">Ver eventos</a>
        <a href="/pages/buscar-treinador.php" class="btn-primary">Procurar treinador</a>

      <?php endif; ?>
    </div>

  </section>

  <!-- =====================================================
       CARROSSEL EVENTOS ESTILO NETFLIX
       ===================================================== -->
  <?php if (!empty($eventos_carrossel)): ?>
  <section class="netflix-section">
    <div class="nc-header">
      <h2>Próximas Corridas</h2>
      <p>Eventos em destaque na plataforma</p>
    </div>
    <div class="nc-container">
      
      <?php if (count($eventos_carrossel) > 1): ?>
        <button class="nc-nav nc-prev" onclick="ncMover(-1)">
          <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
        </button>
      <?php endif; ?>

      <div class="nc-track" id="ncTrack">
        <?php foreach ($eventos_carrossel as $i => $ev): ?>
          <?php 
            $capa = strpos($ev['banner'], 'http') === 0 || empty($ev['banner']) ? $ev['banner'] : '/' . $ev['banner'];
            $dt = new DateTime($ev['data_evento']);
          ?>
          <div class="nc-card" data-index="<?= $i ?>" onclick="ncIrPara(<?= $i ?>)">
            <div class="nc-capa" <?= $capa ? "style=\"background-image:url('$capa')\"" : "" ?>>
              <h3 class="nc-nome-destaque"><?= htmlspecialchars($ev['nome']) ?></h3>
            </div>
            <div class="nc-info">
              <h4><?= htmlspecialchars($ev['nome']) ?></h4>
              <p class="nc-local">📍 <?= htmlspecialchars($ev['cidade']) ?></p>
              <p class="nc-data">📅 <?= $dt->format('d/m/Y') ?></p>
              <a href="/pages/eventos.php" class="nc-btn">Ver detalhes</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (count($eventos_carrossel) > 1): ?>
        <button class="nc-nav nc-next" onclick="ncMover(1)">
          <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        </button>
      <?php endif; ?>

    </div>
  </section>
  <?php endif; ?>

  <!-- =====================================================
       SEÇÃO EXPLICATIVA — como funciona o Strively
       ===================================================== -->
  <section class="sobre">

    <h2>Como Funciona</h2>
    <p class="subtitulo">Tudo que um corredor precisa em um só lugar</p>

    <div class="sobre-grid">

      <!-- Card 1 -->
      <div class="sobre-card">
        <div class="icone">🏅</div>
        <h3>Eventos</h3>
        <p>Descubra corridas perto de você e fique por dentro dos próximos eventos da sua região.</p>
      </div>

      <!-- Card 2 -->
      <div class="sobre-card">
        <div class="icone">🏃</div>
        <h3>Treinos</h3>
        <p>Conecte-se com um treinador verificado e receba planilhas de treino personalizadas.</p>
      </div>

      <!-- Card 3 -->
      <div class="sobre-card">
        <div class="icone">👟</div>
        <h3>Equipamentos</h3>
        <p>Compartilhe descontos e reviews de tênis e acessórios com a comunidade de corredores.</p>
      </div>

    </div>

  </section>

<script>
// Lógica do Carrossel Netflix
let ncIndex = 0;
const ncCards = document.querySelectorAll('.nc-card');
const ncTotal = ncCards.length;

function renderNcCarousel() {
  if (ncTotal === 0) return;
  ncCards.forEach((card, i) => {
    // Reseta classes
    card.className = 'nc-card'; 
    if (i === ncIndex) {
      card.classList.add('nc-center');
    } else if (ncTotal >= 3) {
      if (i === (ncIndex - 1 + ncTotal) % ncTotal) card.classList.add('nc-left');
      else if (i === (ncIndex + 1) % ncTotal) card.classList.add('nc-right');
      else card.classList.add('nc-hidden');
    } else if (ncTotal === 2) {
      if (i !== ncIndex) card.classList.add('nc-right'); // com 2 cards, o outro vai pra direita
    }
  });
}

function ncMover(dir) {
  if (ncTotal > 1) {
    ncIndex = (ncIndex + dir + ncTotal) % ncTotal;
    renderNcCarousel();
  }
}

function ncIrPara(i) {
  if (i === ncIndex) {
    // Se clicou no do centro, leva pra página
    window.location.href = '/pages/eventos.php';
  } else {
    // Se clicou no lateral, traz pro centro
    ncIndex = i;
    renderNcCarousel();
  }
}

if (ncTotal > 0) {
  renderNcCarousel();
}
</script>

</body>
</html>