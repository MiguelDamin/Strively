<?php $tituloPagina = "Início"; ?>
<?php include('components/head.php'); ?>
<?php include('components/header.php'); ?>

<body>

  <!-- =====================================================
       HERO — seção principal centralizada
       ===================================================== -->
  <section class="hero">

    <h1>Corra <span>Mais Longe</span><br>Com o Strively</h1>

    <p>Conecte-se com treinadores, descubra eventos de corrida perto de você e compartilhe equipamentos com a comunidade.</p>

    <div class="hero-buttons">
      <?php if (isset($_SESSION['id'])): ?>
        <a href="pages/eventos.php" class="btn-secondary">Ver eventos</a>
        <a href="/Strively/pages/treinos.php" class="btn-primary">Ver treinos</a>
      <?php else: ?>
        <a href="pages/cadastro.php" class="btn-primary">Criar conta grátis</a>
        <a href="pages/eventos.php" class="btn-secondary">Ver eventos</a>
      <?php endif; ?>
    </div>

  </section>


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

</body>
</html>