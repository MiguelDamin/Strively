<?php
// ==========================================================
// STRIVELY — pages/alunos.php
// Painel do treinador — lista de alunos vinculados
// ==========================================================

$only_session = true;
require_once '../components/header.php';

if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

if ($_SESSION['perfil'] !== 'treinador') {
  header('Location: /index.php');
  exit();
}

require_once '../config/conexao.php';

$treinador_usuario_id = $_SESSION['id'];

// Busca alunos vinculados — treinador_id na tabela usuarios aponta para usuarios.id
$stmt = $pdo->prepare("
  SELECT id, nome, email, foto, cidade
  FROM usuarios
  WHERE treinador_id = ?
  ORDER BY nome ASC
");
$stmt->execute([$treinador_usuario_id]);
$alunos = $stmt->fetchAll();

// Busca por e-mail para adicionar
$busca     = null;
$erroBusca = null;
if (isset($_GET['busca']) && $_GET['busca'] !== '') {
  $emailBusca = trim($_GET['busca']);
  $stmt = $pdo->prepare("
    SELECT id, nome, email, foto, cidade, treinador_id
    FROM usuarios
    WHERE email = ? AND perfil = 'corredor'
  ");
  $stmt->execute([$emailBusca]);
  $busca = $stmt->fetch();
  if (!$busca) {
    $erroBusca = 'Nenhum corredor encontrado com esse e-mail.';
  } elseif ($busca['treinador_id'] === $treinador_usuario_id) {
    $erroBusca = 'Este corredor já é seu aluno.';
  } elseif ($busca['treinador_id']) {
    $erroBusca = 'Este corredor já tem um treinador.';
  }
}

unset($only_session);
$tituloPagina = "Meus Alunos";
include '../components/head.php';
include '../components/header.php';
?>

<style>
  .alunos-wrap {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 24px 100px;
  }

  .alunos-titulo {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2.2rem;
    letter-spacing: 2px;
    margin-bottom: 4px;
  }

  .alunos-sub {
    font-size: 0.9rem;
    color: var(--text-muted);
    margin-bottom: 28px;
  }

  .busca-form {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
  }

  .busca-form input {
    flex: 1;
    background: var(--card-bg, #fff);
    border: 2px solid #ddd;
    border-radius: 10px;
    padding: 11px 14px;
    font-family: 'Outfit', sans-serif;
    font-size: 0.92rem;
    outline: none;
    transition: border-color 0.2s;
  }

  .busca-form input:focus {
    border-color: var(--green);
  }

  .busca-resultado {
    background: var(--card-bg, #fff);
    border-radius: var(--radius, 12px);
    box-shadow: var(--shadow, 0 2px 8px rgba(0,0,0,.08));
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 28px;
    flex-wrap: wrap;
  }

  .busca-avatar {
    width: 44px; height: 44px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--green);
    flex-shrink: 0;
  }

  .busca-avatar-padrao {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: var(--bg, #f5f5f5);
    border: 2px solid var(--green);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }

  .busca-avatar-padrao svg { width: 24px; height: 24px; fill: var(--text-muted); }

  .busca-info { flex: 1; }
  .busca-nome { font-weight: 700; font-size: 0.95rem; }
  .busca-email { font-size: 0.82rem; color: var(--text-muted); }

  .aluno-card {
    background: var(--card-bg, #fff);
    border-radius: var(--radius, 12px);
    box-shadow: var(--shadow, 0 2px 8px rgba(0,0,0,.08));
    padding: 18px 22px;
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 12px;
    transition: box-shadow 0.2s;
  }

  .aluno-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.14); }

  .aluno-avatar {
    width: 48px; height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--green);
    flex-shrink: 0;
  }

  .aluno-avatar-padrao {
    width: 48px; height: 48px;
    border-radius: 50%;
    background: var(--bg, #f5f5f5);
    border: 2px solid var(--green);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }

  .aluno-avatar-padrao svg { width: 26px; height: 26px; fill: var(--text-muted); }

  .aluno-info { flex: 1; }
  .aluno-nome  { font-weight: 700; font-size: 0.95rem; color: var(--text-main); }
  .aluno-detalhe { font-size: 0.82rem; color: var(--text-muted); }

  .aluno-acoes { display: flex; gap: 8px; align-items: center; }

  .btn-treinos {
    background: #fff;
    color: var(--text-main);
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 7px 14px;
    font-family: 'Outfit', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: border-color 0.2s, color 0.2s, box-shadow 0.2s;
  }

  .btn-treinos:hover {
    border-color: var(--green);
    color: var(--green);
    box-shadow: 0 2px 8px rgba(29,185,84,.15);
  }

  .btn-treinos svg { width: 15px; height: 15px; fill: currentColor; }

  .btn-ver {
    background: var(--green);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    font-family: 'Outfit', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: opacity 0.15s;
  }

  .btn-ver:hover { opacity: 0.85; }

  .btn-remover {
    background: #fff0f0;
    color: #cc0000;
    border: 2px solid #ffcccc;
    border-radius: 8px;
    padding: 6px 12px;
    font-family: 'Outfit', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s;
  }

  .btn-remover:hover { background: #ffe0e0; }

  .vazio {
    text-align: center;
    padding: 60px 24px;
    color: var(--text-muted);
  }

  .vazio-icone { font-size: 2.5rem; margin-bottom: 12px; }

  .msg-sucesso {
    background: #e8f8ee;
    color: #166534;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 0.92rem;
  }

  .msg-erro {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 0.92rem;
  }

  @media (max-width: 640px) {
    .alunos-wrap { padding: 0 14px 100px; }
    .alunos-titulo { font-size: 1.8rem; }
    .busca-form { flex-direction: column; }
    .aluno-card { flex-wrap: wrap; padding: 14px 16px; gap: 10px; }
    .aluno-acoes {
      width: 100%;
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 6px;
      margin-top: 4px;
    }
    .btn-treinos, .btn-ver, .btn-remover {
      padding: 8px 4px;
      font-size: .78rem;
      justify-content: center;
      text-align: center;
      border-radius: 8px;
    }
    .btn-treinos svg { display: none; }
    .btn-remover { width: 100%; }
    .busca-resultado { flex-wrap: wrap; gap: 10px; }
    .busca-resultado form { width: 100%; }
    .busca-resultado form .btn-ver { width: 100%; }
  }
</style>

<div class="alunos-wrap">

  <h1 class="alunos-titulo">Seus Alunos</h1>
  <p class="alunos-sub">Gerencie os corredores vinculados a você.</p>

  <?php if (isset($_GET['msg'])): ?>
    <div class="msg-sucesso">
      <?php
        $msgs = ['adicionado' => '✅ Aluno adicionado com sucesso!', 'removido' => '✅ Aluno removido.'];
        echo $msgs[$_GET['msg']] ?? 'Ação realizada.';
      ?>
    </div>
  <?php endif; ?>

  <!-- BUSCAR ALUNO POR E-MAIL -->
  <form class="busca-form" method="GET">
    <input
      type="email"
      name="busca"
      placeholder="Buscar corredor por e-mail..."
      value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>"
    />
    <button type="submit" class="btn-primary">Buscar</button>
  </form>

  <?php if ($erroBusca): ?>
    <div class="msg-erro"><?= htmlspecialchars($erroBusca) ?></div>
  <?php elseif ($busca): ?>
    <div class="busca-resultado">
      <?php if (!empty($busca['foto'])): ?>
        <img src="<?= htmlspecialchars($busca['foto']) ?>" class="busca-avatar" alt="Foto">
      <?php else: ?>
        <div class="busca-avatar-padrao">
          <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
        </div>
      <?php endif; ?>
      <div class="busca-info">
        <div class="busca-nome"><?= htmlspecialchars($busca['nome']) ?></div>
        <div class="busca-email"><?= htmlspecialchars($busca['email']) ?></div>
        <?php if (!empty($busca['cidade'])): ?>
          <div class="busca-email"><?= htmlspecialchars($busca['cidade']) ?></div>
        <?php endif; ?>
      </div>
      <form action="/actions/action-adicionar-aluno.php" method="POST">
        <input type="hidden" name="aluno_id" value="<?= (int)$busca['id'] ?>">
        <button type="submit" class="btn-ver">+ Adicionar</button>
      </form>
    </div>
  <?php endif; ?>

  <!-- LISTA DE ALUNOS -->
  <?php if (empty($alunos)): ?>
    <div class="vazio">
      <div class="vazio-icone">🏃</div>
      <p>Você ainda não tem alunos vinculados.<br>Busque um corredor pelo e-mail acima.</p>
    </div>
  <?php else: ?>
    <?php foreach ($alunos as $aluno): ?>
      <div class="aluno-card">

        <?php if (!empty($aluno['foto'])): ?>
          <img src="<?= htmlspecialchars($aluno['foto']) ?>" class="aluno-avatar" alt="Foto">
        <?php else: ?>
          <div class="aluno-avatar-padrao">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
          </div>
        <?php endif; ?>

        <div class="aluno-info">
          <div class="aluno-nome"><?= htmlspecialchars($aluno['nome']) ?></div>
          <div class="aluno-detalhe"><?= htmlspecialchars($aluno['email']) ?></div>
          <?php if (!empty($aluno['cidade'])): ?>
            <div class="aluno-detalhe"><?= htmlspecialchars($aluno['cidade']) ?></div>
          <?php endif; ?>
        </div>

        <div class="aluno-acoes">
          <a href="/pages/treinos-alunos.php?aluno_id=<?= (int)$aluno['id'] ?>" class="btn-treinos">
            <svg viewBox="0 0 24 24"><path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/></svg>
            Treinos
          </a>
          <a href="/pages/aluno-detalhe.php?id=<?= (int)$aluno['id'] ?>" class="btn-ver">Ver</a>
          <form action="/actions/action-remover-aluno.php" method="POST"
                onsubmit="return confirm('Remover <?= htmlspecialchars(addslashes($aluno['nome'])) ?> da sua lista?')">
            <input type="hidden" name="aluno_id" value="<?= (int)$aluno['id'] ?>">
            <button type="submit" class="btn-remover">Remover</button>
          </form>
        </div>

      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>