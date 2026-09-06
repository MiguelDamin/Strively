<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id'])) {
    header('Location: /pages/login.php');
    exit();
}

// Ao acessar a página, marcar todas como lidas
$pdo->prepare("UPDATE notificacoes SET lida = true WHERE usuario_id = ?")->execute([$_SESSION['id']]);

unset($only_session);
$tituloPagina = "Notificações";
include '../components/head.php';
include '../components/header.php';

// Busca as notificacoes
try {
    $stmt = $pdo->prepare("
        SELECT n.*, u.foto as foto_remetente, u.nome as nome_remetente,
               s.seguidor_id as am_i_following
        FROM notificacoes n
        LEFT JOIN usuarios u ON n.remetente_id = u.id
        LEFT JOIN seguidores s ON s.seguidor_id = n.usuario_id AND s.seguido_id = n.remetente_id
        WHERE n.usuario_id = ?
        ORDER BY n.id DESC
        LIMIT 50
    ");
    $stmt->execute([$_SESSION['id']]);
    $notificacoes = $stmt->fetchAll();
} catch(Exception $e) {
    $notificacoes = [];
}
?>

<style>
.notif-container { max-width: 600px; margin: 40px auto; padding: 0 20px; min-height: 70vh; }
.notif-title { font-family: 'Bebas Neue', sans-serif; font-size: 2.2rem; color: var(--text-primary); margin-bottom: 24px; letter-spacing: 1px; }
.notif-card { background: #fff; border-radius: var(--radius-lg); padding: 16px 20px; box-shadow: var(--shadow-sm); margin-bottom: 12px; border: 1px solid var(--border); display: flex; align-items: center; gap: 14px; transition: transform 0.2s; text-decoration: none; color: inherit; }
.notif-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: rgba(29, 185, 84, 0.2); }
.notif-icon { flex-shrink: 0; width: 44px; height: 44px; background: var(--green-tint); color: var(--green-dark); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
.notif-content { flex: 1; }
.notif-info { font-weight: 500; font-size: 0.95rem; color: var(--text-primary); line-height: 1.4; }
.notif-time { font-size: 0.8rem; color: var(--text-tertiary); margin-top: 4px; }
.notif-empty { text-align: center; padding: 60px 20px; background: #fff; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
</style>

<div class="notif-container">
    <h1 class="notif-title">Notificações</h1>

    <?php if (empty($notificacoes)): ?>
        <div class="notif-empty">
            <span style="font-size: 3rem; display:block; margin-bottom: 12px;">📭</span>
            <h3 style="font-family: 'Bebas Neue', sans-serif; font-size: 1.5rem; letter-spacing: 1px;">Sua caixa está vazia</h3>
            <p style="color: var(--text-secondary);">Você ainda não recebeu nenhuma curtida ou interação.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notificacoes as $n): ?>
            <a href="<?= htmlspecialchars($n['link']) ?>" class="notif-card">
                <div class="notif-avatar" style="flex-shrink:0;">
                    <?php if (!empty($n['foto_remetente'])): ?>
                        <img src="<?= htmlspecialchars(strpos($n['foto_remetente'], 'http') === 0 ? $n['foto_remetente'] : '/'.$n['foto_remetente']) ?>" 
                             alt="<?= htmlspecialchars($n['nome_remetente'] ?? '') ?>"
                             style="width:44px;height:44px;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                        <div style="width:44px;height:44px;border-radius:50%;background:#1DB954;display:flex;align-items:center;justify-content:center;font-size:20px;">💪</div>
                    <?php endif; ?>
                </div>
                <div class="notif-info">
                    <div class="notif-info-text" style="font-weight: 500; font-size: 0.95rem; color: var(--text-primary); line-height: 1.4;"><?= htmlspecialchars($n['texto']) ?></div>
                    
                    <?php if (strpos(strtolower($n['texto']), 'perseguir') !== false && empty($n['am_i_following'])): ?>
                        <div style="margin-top:8px;">
                            <button onclick="event.preventDefault(); followBackFast(<?= htmlspecialchars($n['remetente_id'], ENT_QUOTES, 'UTF-8') ?>, this)" style="background:var(--green);color:#fff;border:none;padding:6px 14px;border-radius:100px;font-size:0.8rem;font-weight:700;cursor:pointer;transition:transform 0.2s;">Persiga de volta</button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($n['data_criacao'])): ?>
                        <?php 
                            $dt = new DateTime($n['data_criacao']);
                            $tempo = $dt->format('d/m/Y H:i');
                        ?>
                        <div class="notif-time"><?= htmlspecialchars($tempo, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php else: ?>
                        <div class="notif-time">Agora mesmo</div>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
async function followBackFast(alvoId, btnElem) {
    try {
        btnElem.innerText = 'Perseguindo...';
        btnElem.style.opacity = '0.7';
        btnElem.style.pointerEvents = 'none';

        const formData = new FormData();
        formData.append('alvo_id', alvoId);
        
        const response = await fetch('/actions/action-perseguir.php', {
            method: 'POST', body: formData
        });

        if (response.ok) {
            btnElem.innerText = 'Perseguindo';
            btnElem.style.background = 'transparent';
            btnElem.style.color = '#777';
            btnElem.style.border = '1px solid #ddd';
        } else {
            throw new Error('Falha no request');
        }
    } catch(err) {
        console.error(err);
        btnElem.innerText = 'Persiga de volta';
        btnElem.style.opacity = '1';
        btnElem.style.pointerEvents = 'auto';
    }
}
</script>

<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>
