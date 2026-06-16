<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id'])) {
    header('Location: /pages/login.php');
    exit();
}

unset($only_session);

$tituloPagina = "Comunidade";
include '../components/head.php';
include '../components/header.php';

// Fetch Posts
$stmtPosts = $pdo->prepare("
  SELECT p.*,
         u.nome as autor_nome, u.foto as autor_foto,
         (SELECT COUNT(*) FROM post_curtidas WHERE post_id = p.id) as curtidas,
         (SELECT true FROM post_curtidas WHERE post_id = p.id AND usuario_id = ?) as curtiu,
         t.treinador_id as t_treinador_id,
         ut.nome as treinador_nome,
         ut.foto as treinador_foto,
         e.banner as evento_banner
  FROM posts p
  JOIN usuarios u ON p.usuario_id = u.id
  LEFT JOIN treinos t ON p.treino_id = t.id
  LEFT JOIN usuarios ut ON t.treinador_id = ut.id
  LEFT JOIN eventos e ON p.evento_id = e.id
  ORDER BY p.criado_em DESC
");
$stmtPosts->execute([$_SESSION['id']]);
$posts = $stmtPosts->fetchAll();

$feedPosts = array_filter($posts, function($p) { return $p['tipo'] !== 'equipamento'; });
$equipPosts = array_filter($posts, function($p) { return $p['tipo'] === 'equipamento'; });

// Get the boneco SVG inline for easy reuse
$runnerIcon = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg>';

function renderPostCard($p, $runnerIcon) {
  $dt = new DateTime($p['criado_em']); 
  $tempoFormatado = $dt->format('d/m/Y H:i');
  
  $autorId = (int)$p['usuario_id'];
  $linkAutor = (isset($_SESSION['id']) && $_SESSION['id'] === $autorId)
    ? '/pages/perfil.php'
    : '/pages/perfil-publico.php?id=' . $autorId;
?>
  <div class="post-card" id="post-<?= $p['id'] ?>" style="width: 100% !important; min-width: 100% !important; box-sizing: border-box !important;">
    <div class="post-header">
      <a href="<?= $linkAutor ?>" style="display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;flex:1;">
        <?php if (!empty($p['autor_foto'])): ?>
          <img src="<?= htmlspecialchars(strpos($p['autor_foto'], 'http') === 0 ? $p['autor_foto'] : '/'.$p['autor_foto']) ?>" 
               class="post-avatar" 
               style="cursor:pointer;transition:opacity 0.2s;flex-shrink:0;"
               onmouseover="this.style.opacity='0.8'"
               onmouseout="this.style.opacity='1'" />
        <?php else: ?>
          <div class="post-avatar" style="cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg viewBox="0 0 24 24" style="width:24px;height:24px;fill:#999;">
              <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z"/>
            </svg>
          </div>
        <?php endif; ?>
        <div class="post-author" style="text-decoration:none;">
          <span style="font-weight:700;color:var(--text-primary,#111);">
            <?= htmlspecialchars($p['autor_nome']) ?>
          </span>
          <span class="post-date"><?= $tempoFormatado ?></span>
        </div>
      </a>
    </div>

    <div class="post-content">
      <h4><?= htmlspecialchars($p['titulo']) ?></h4>
      <?php if (!empty($p['descricao'])): ?>
        <p><?= nl2br(htmlspecialchars($p['descricao'])) ?></p>
      <?php endif; ?>
    </div>

    <?php 
      $mediaTarget = $p['foto'];
      if ($p['tipo'] === 'evento' && empty($mediaTarget)) $mediaTarget = $p['evento_banner'];
    ?>

    <?php if (!empty($mediaTarget)): ?>
      <div class="post-media">
        <img src="<?= htmlspecialchars(strpos($mediaTarget, 'http') === 0 ? $mediaTarget : '/'.$mediaTarget) ?>" alt="Mídia do Post" loading="lazy">
      </div>
    <?php elseif ($p['tipo'] === 'treino'): ?>
      <div class="post-media empty-state">
        <?= $runnerIcon ?>
      </div>
    <?php endif; ?>

    <?php if ($p['tipo'] === 'treino' && !empty($p['t_treinador_id'])): ?>
      <div class="post-trainer">
         <?php if (!empty($p['treinador_foto'])): ?>
            <img src="<?= htmlspecialchars(strpos($p['treinador_foto'], 'http') === 0 ? $p['treinador_foto'] : '/'.$p['treinador_foto']) ?>">
         <?php endif; ?>
         Treino proposto por <?= htmlspecialchars($p['treinador_nome']) ?>
      </div>
    <?php endif; ?>

    <div class="post-footer">
      <button class="like-btn <?= $p['curtiu'] ? 'liked' : '' ?>" onclick="toggleLike(<?= $p['id'] ?>, this)">
        💪 <span class="like-count"><?= $p['curtidas'] ?></span>
      </button>

      <?php if ($p['usuario_id'] === $_SESSION['id']): ?>
        <div class="post-actions">
          <button type="button" title="Editar" onclick="abrirModalEditar(<?= $p['id'] ?>)">✏️</button>
          <form action="/actions/action-excluir-post.php" method="POST" onsubmit="return confirm('Apagar este post?');">
            <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
            <button type="submit" title="Excluir">🗑️</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php
}
?>


<div class="comunidade-wrapper" id="comunidade-wrapper-id" style="width: 100% !important; max-width: 650px !important; margin: 0 auto !important; align-self: stretch !important; flex: 1 1 auto !important; display: flex !important; flex-direction: column !important;">

  <div class="c-tabs">
    <div class="c-tab active" onclick="switchTab('feed')">Feed</div>
    <div class="c-tab" onclick="switchTab('equipamentos')">Equipamentos</div>
  </div>

  <div id="tab-feed">
    <?php if (empty($feedPosts)): ?>
      <div style="text-align:center; padding: 40px; color:#888;">
        <h2>Vazio!</h2>
        <p>Nenhuma publicação ainda. Seja o primeiro a postar!</p>
      </div>
    <?php else: ?>
      <?php foreach ($feedPosts as $p): ?>
        <?php renderPostCard($p, $runnerIcon); ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div id="tab-equipamentos" style="display: none;">
    <?php if (empty($equipPosts)): ?>
      <div style="text-align:center; padding: 40px; color:#888;">
        <h2>Vazio!</h2>
        <p>Nenhuma publicação de equipamento/cupom ainda.</p>
      </div>
    <?php else: ?>
      <?php foreach ($equipPosts as $p): ?>
        <?php renderPostCard($p, $runnerIcon); ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <button class="fab-add" onclick="abrirModalCriarPost()">+</button>
  </div> <!-- .comunidade-wrapper -->

  <!-- Modal Criar -->  <div class="modal-criar-post-overlay" id="modalCriarPost">
    <div class="modal-criar-post">
      <button class="btn-fechar-modal" onclick="fecharModalCriarPost()">✕</button>
      <h3>Nova publicação</h3>
      <form action="/actions/action-criar-post.php" method="POST" enctype="multipart/form-data">
        <label>Nome da publicação</label>
        <input type="text" name="titulo" placeholder="Ex: Cupom de desconto" required>
        
        <label>Foto (opcional)</label>
        <input type="file" name="foto" accept="image/*">
        
        <label>Descrição (opcional)</label>
        <textarea name="descricao" placeholder="Adicione detalhes..."></textarea>
        
        <label>Tipo</label>
        <select name="tipo">
          <option value="manual">Post geral</option>
          <option value="equipamento">Equipamento/Cupom</option>
        </select>
        
        <button type="submit" class="btn-publicar">Publicar</button>
      </form>
    </div>
  </div>

<!-- Modais de edição -->
<?php foreach ($posts as $post): ?>
  <?php if ($post['usuario_id'] === $_SESSION['id']): ?>
    <div id="modal-editar-<?= $post['id'] ?>" class="modal-overlay">
      <div class="modal-box">
        <div class="m-close" onclick="fecharModal(<?= $post['id'] ?>)">&times;</div>
        <h3>Editar publicação</h3>
        <form action="/actions/action-editar-post.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
          <input type="text" name="titulo" value="<?= htmlspecialchars($post['titulo'] ?? '') ?>" placeholder="Título" required>
          <textarea name="descricao" placeholder="Descrição (opcional)"><?= htmlspecialchars($post['descricao'] ?? '') ?></textarea>
          <label style="display:block;margin-bottom:8px;font-size:0.9rem;font-weight:600;color:var(--text-secondary);">Nova foto (opcional):</label>
          <input type="file" name="foto" accept="image/*">
          <?php if (!empty($post['foto'])): ?>
            <img src="<?= htmlspecialchars(strpos($post['foto'], 'http') === 0 ? $post['foto'] : '/'.$post['foto']) ?>" style="width:100px;height:100px;object-fit:cover;border-radius:10px;margin-bottom:12px;border:1px solid #ddd;">
          <?php endif; ?>
          <button type="submit" class="btn-submit">Salvar Alterações</button>
        </form>
      </div>
    </div>
  <?php endif; ?>
<?php endforeach; ?>

<script>
function switchTab(tab) {
  document.querySelectorAll('.c-tab').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-feed').style.display = 'none';
  document.getElementById('tab-equipamentos').style.display = 'none';
  
  if (tab === 'feed') {
    document.querySelector('.c-tab:nth-child(1)').classList.add('active');
    document.getElementById('tab-feed').style.display = 'block';
  } else {
    document.querySelector('.c-tab:nth-child(2)').classList.add('active');
    document.getElementById('tab-equipamentos').style.display = 'block';
  }
}

async function toggleLike(postId, btn) {
  // Otimização visual imediata
  let span = btn.querySelector('.like-count');
  let currentCnt = parseInt(span.innerText);
  let isCurrentlyLiked = btn.classList.contains('liked');
  
  btn.classList.toggle('liked');
  span.innerText = isCurrentlyLiked ? currentCnt - 1 : currentCnt + 1;

  // Chamada Background
  let fd = new FormData();
  fd.append('post_id', postId);
  
  try {
    const res = await fetch('/actions/action-curtir-post.php', {
       method: 'POST',
       body: fd
    });
    const json = await res.json();
    if(json.total !== undefined) {
       span.innerText = json.total; // Fix real from DB server
       if(json.curtido) btn.classList.add('liked');
       else btn.classList.remove('liked');
    }
  } catch(e) {
    console.error("Erro validando curtida", e);
  }
}

// Funções do Modal de Criar
function abrirModalCriarPost() {
    document.getElementById('modalCriarPost').style.display = 'flex';
    lockScroll();
}
function fecharModalCriarPost() {
    document.getElementById('modalCriarPost').style.display = 'none';
    unlockScroll();
}

// Fechar modal no overlay
document.getElementById('modalCriarPost')?.addEventListener('click', function(e) {
  if (e.target === this) fecharModalCriarPost();
});

// Funções do Modal de Edição
function abrirModalEditar(postId) {
    const el = document.getElementById('modal-editar-' + postId);
    if(el) { el.classList.add('open'); lockScroll(); }
}
function fecharModal(postId) {
    const el = document.getElementById('modal-editar-' + postId);
    if(el) { el.classList.remove('open'); unlockScroll(); }
}
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('open');
            unlockScroll();
        }
    });
});
</script>
<style>
  body { background: #f5f6f5; }
  #comunidade-wrapper-id { 
    width: 100% !important; 
    max-width: 650px !important; 
    margin: 0 auto !important; 
    padding: 24px 20px 100px !important; 
    align-self: stretch !important;
    display: flex !important;
    flex-direction: column !important;
    flex: 1 1 auto !important;
  }
  #tab-feed, #tab-equipamentos { 
    width: 100% !important; 
    min-width: 100% !important;
    display: block; /* Ensure it's not inline-block or similar */
  }
  
  .c-tabs { display: flex; gap: 4px; background: #e9ecef; border-radius: 12px; padding: 4px; margin-bottom: 24px; width: 100% !important; }
  .c-tab { flex: 1; text-align: center; padding: 12px; font-weight: 700; color: #666; cursor: pointer; border-radius: 8px; transition: 0.2s; font-size: 0.95rem; }
  .c-tab.active { background: #fff; color: var(--green); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

  .post-card { 
    background: #fff; 
    border-radius: var(--radius-lg); 
    padding: 18px; 
    border: 1px solid var(--border); 
    box-shadow: 0 4px 12px rgba(0,0,0,0.03); 
    margin-bottom: 24px; 
    display: flex; 
    flex-direction: column; 
    width: 100% !important;
    min-width: 100% !important;
    box-sizing: border-box !important;
  }
  
  .post-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
  .post-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; background: #eee; border: 2px solid var(--green-tint); }
  .post-author { font-weight: 700; color: var(--text-primary); font-size: 1rem; line-height: 1.2; display: flex; flex-direction: column; }
  .post-date { font-size: 0.75rem; color: #999; font-weight: 500; }
  
  .post-content h4 { font-family: 'Bebas Neue', sans-serif; font-size: 1.5rem; letter-spacing: 0.5px; margin: 0 0 6px 0; color: var(--text-primary); }
  .post-content p { color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 14px; line-height: 1.5; white-space: pre-wrap; }
  
  .post-media { width: 100%; border-radius: 12px; overflow: hidden; background: #f0f0f0; margin-bottom: 14px; display: flex; justify-content: center; align-items: center; }
  .post-media img { width: 100%; object-fit: cover; max-height: 450px; display: block; }
  .post-media.empty-state { min-height: 160px; }
  .post-media svg { width: 80px; height: 120px; fill: var(--green); }
  
  .post-trainer { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #555; background: #f9f9f9; padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; font-weight: 500; border: 1px solid #eee; }
  .post-trainer img { width: 26px; height: 26px; border-radius: 50%; object-fit: cover; }
  
  .post-footer { display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #efefef; padding-top: 14px; margin-top: auto; }
  .like-btn { background: transparent; border: none; display: flex; align-items: center; gap: 6px; color: #777; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: all 0.2s; padding: 6px 12px; border-radius: 20px; }
  .like-btn:hover { background: #f5f5f5; }
  .like-btn.liked { color: var(--green); }
  .post-actions { display: flex; gap: 10px; }
  .post-actions button { background: none; border: none; font-size: 1.1rem; color: #bbb; cursor: pointer; transition: 0.2s; }
  .post-actions button:hover { color: #d63031; }
  
  .post-header a { transition: all 0.2s; }
  .post-header a:hover .post-avatar,
  .post-header a:hover img.post-avatar {
    opacity: 0.8;
    transform: scale(1.04);
    transition: all 0.2s ease;
  }
  .post-header a:hover .post-author span:first-child {
    color: var(--green, #1DB954);
    transition: color 0.2s;
  }
  
  .fab-add { position: fixed; bottom: 90px; right: 24px; width: 56px; height: 56px; background: var(--green); border-radius: 50%; color: #fff; font-size: 32px; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 16px rgba(29,185,84,0.4); border: none; cursor: pointer; transition: transform 0.2s; z-index: 100; }
  .fab-add:active { transform: scale(0.9); }
  
  .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: none; align-items: center; justify-content: center; z-index: 999; padding: 20px; backdrop-filter: blur(4px); }
  .modal-overlay.open { display: flex; }
  .modal-box { background: #fff; border-radius: 24px; width: 100%; max-width: 400px; padding: 28px; position: relative; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
  .m-close { position: absolute; top: 16px; right: 20px; font-size: 1.6rem; color: #999; cursor: pointer; }
  .modal-box h3 { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; margin: 0 0 20px 0; color: #111; letter-spacing: 1px; }
  .modal-box input[type="text"], .modal-box textarea, .modal-box select { width: 100%; border: 1.5px solid #eaeaea; border-radius: 10px; padding: 14px; font-family: 'Outfit'; font-size: 0.95rem; margin-bottom: 12px; outline: none; transition: border-color 0.2s; }
  .modal-box input:focus, .modal-box textarea:focus, .modal-box select:focus { border-color: var(--green); }
  .modal-box textarea { resize: vertical; min-height: 100px; }
  .modal-box input[type="file"] { width: 100%; padding: 10px 0; margin-bottom: 16px; font-size: 0.9rem; }
  .btn-submit { background: var(--green); color: #fff; border: none; padding: 14px; width: 100%; border-radius: 10px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: 0.2s; }
  .btn-submit:hover { background: var(--green-dark); }

  /* Modal Criar Post - Estilos Novos */
  .modal-criar-post-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 9999;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
      box-sizing: border-box;
  }

  .modal-criar-post {
      background: #fff;
      border-radius: 20px;
      padding: 24px;
      width: 100%;
      max-width: 400px;
      max-height: 85vh;
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
      position: relative;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
  }

  .modal-criar-post h3 {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.4rem;
      letter-spacing: 1px;
      margin: 0 0 16px;
      color: #0d0d0d;
  }

  .modal-criar-post label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 5px;
      font-family: 'Outfit', sans-serif;
  }

  .modal-criar-post input[type="text"],
  .modal-criar-post input[type="file"],
  .modal-criar-post textarea,
  .modal-criar-post select {
      width: 100%;
      box-sizing: border-box;
      background: #f5f6f5;
      border: 1.5px solid #e0e0e0;
      border-radius: 10px;
      padding: 10px 13px;
      font-family: 'Outfit', sans-serif;
      font-size: 0.9rem;
      outline: none;
      margin-bottom: 12px;
      color: #111;
  }

  .modal-criar-post input[type="file"] {
      padding: 8px;
      cursor: pointer;
  }

  .modal-criar-post select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%23666' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 32px;
      -webkit-appearance: none;
      appearance: none;
  }

  .modal-criar-post .btn-publicar {
      width: 100%;
      padding: 14px;
      background: var(--green, #1DB954);
      color: #fff;
      border: none;
      border-radius: 12px;
      font-family: 'Outfit', sans-serif;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      margin-top: 4px;
      transition: background 0.2s;
  }

  .modal-criar-post .btn-publicar:hover {
      background: var(--green-dark);
  }

  .modal-criar-post .btn-fechar-modal {
      position: absolute;
      top: 16px;
      right: 16px;
      background: #f5f5f5;
      border: none;
      border-radius: 8px;
      width: 30px;
      height: 30px;
      cursor: pointer;
      font-size: 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #666;
  }

  @media (max-width: 640px) {
      .modal-criar-post {
          width: 95%;
          padding: 20px 16px;
          max-height: 80vh;
      }
  }
</style>

<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>
