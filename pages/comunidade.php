<?php
require_once '../config/conexao.php';
// Requer login
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['id'])) {
    header('Location: /pages/login.php');
    exit();
}

$tituloPagina = "Comunidade";
require_once '../components/header.php';

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

// Get the boneco SVG inline for easy reuse
$runnerIcon = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg>';
?>
<style>
  body { background: #f5f6f5; }
  .comunidade-wrapper { max-width: 650px; margin: 0 auto; padding: 24px 20px 100px; display: flex; flex-direction: column; }
  
  .c-tabs { display: flex; gap: 4px; background: #e9ecef; border-radius: 12px; padding: 4px; margin-bottom: 24px; }
  .c-tab { flex: 1; text-align: center; padding: 12px; font-weight: 700; color: #666; cursor: pointer; border-radius: 8px; transition: 0.2s; font-size: 0.95rem; }
  .c-tab.active { background: #fff; color: var(--green); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

  .post-card { background: #fff; border-radius: var(--radius-lg); padding: 18px; border: 1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 24px; display: flex; flex-direction: column; }
  
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
</style>

<div class="comunidade-wrapper">

  <div class="c-tabs">
    <div class="c-tab active" onclick="switchTab('feed')">Feed</div>
    <div class="c-tab" onclick="switchTab('equipamentos')">Equipamentos</div>
  </div>

  <div id="tab-feed">
    <?php if (empty($posts)): ?>
      <div style="text-align:center; padding: 40px; color:#888;">
        <h2>Vazio!</h2>
        <p>Nenhuma publicação ainda. Seja o primeiro a postar!</p>
      </div>
    <?php else: ?>
      <?php foreach ($posts as $p): ?>
        <?php 
          // Format date softly
          $dt = new DateTime($p['criado_em']); 
          $tempoFormatado = $dt->format('d/m/Y H:i');
        ?>
        <div class="post-card" id="post-<?= $p['id'] ?>">
          
          <div class="post-header">
            <?php if (!empty($p['autor_foto'])): ?>
              <img src="<?= htmlspecialchars(strpos($p['autor_foto'], 'http') === 0 ? $p['autor_foto'] : '/'.$p['autor_foto']) ?>" class="post-avatar" />
            <?php else: ?>
              <div class="post-avatar" style="display:flex;align-items:center;justify-content:center;">
                <svg viewBox="0 0 24 24" style="width:24px;height:24px;fill:#999;"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z"/></svg>
              </div>
            <?php endif; ?>
            <div class="post-author">
              <?= htmlspecialchars($p['autor_nome']) ?>
              <span class="post-date"><?= $tempoFormatado ?></span>
            </div>
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
                <form action="/actions/action-excluir-post.php" method="POST" onsubmit="return confirm('Apagar este post?');">
                  <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                  <button type="submit" title="Excluir">🗑️</button>
                </form>
              </div>
            <?php endif; ?>
          </div>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div id="tab-equipamentos" style="display: none;">
    <!-- Equipamentos Legado Embutido -->
    <div style="background: #fff; padding: 40px 20px; text-align: center; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
        <h2>Em Desenvolvimento</h2>
        <p style="color:#666; margin-top:10px;">Esta sessão de equipamentos está no forno. Fique ligado!</p>
    </div>
  </div>

  <button class="fab-add" onclick="document.getElementById('modalCriar').classList.add('open')">+</button>

</div>

<div class="modal-overlay" id="modalCriar">
  <div class="modal-box">
    <div class="m-close" onclick="document.getElementById('modalCriar').classList.remove('open')">&times;</div>
    <form action="/actions/action-criar-post.php" method="POST" enctype="multipart/form-data">
      <h3>Nova publicação</h3>
      <input type="text" name="titulo" placeholder="Nome da publicação (ex: Desconto na Centauro)" required>
      <input type="file" name="foto" accept="image/*">
      <textarea name="descricao" placeholder="Escreva os detalhes (opcional)"></textarea>
      <select name="tipo">
        <option value="manual">Post geral</option>
        <option value="equipamento">Equipamento/Cupom</option>
      </select>
      <button type="submit" class="btn-submit">Publicar</button>
    </form>
  </div>
</div>

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

// Fechar modal no overlay
document.getElementById('modalCriar').addEventListener('click', function(e) {
  if (e.target === this) this.classList.remove('open');
});
</script>
</body>
</html>
