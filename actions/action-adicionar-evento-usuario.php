<?php
// ==========================================================
// STRIVELY — actions/action-adicionar-evento-usuario.php
// Corredor adiciona evento ao seu calendário
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: /pages/treinos.php');
  exit();
}

$evento_id   = (int)($_POST['evento_id']   ?? 0);
$nome_manual = trim($_POST['nome_manual']  ?? '');
$data_evento = $_POST['data_evento']       ?? '';
$aba         = $_POST['aba']               ?? 'calendario';

if (empty($data_evento)) {
  header("Location: /pages/treinos.php?aba={$aba}&erro=campos");
  exit();
}

if ($evento_id > 0) {
  // Evento do Strively — validar que existe e que a data bate
  $stmt = $pdo->prepare("SELECT data_evento, nome, banner FROM eventos WHERE id = ? AND status = 'ativo'");
  $stmt->execute([$evento_id]);
  $evento = $stmt->fetch();

  if (!$evento) {
    header("Location: /pages/treinos.php?aba={$aba}&erro=evento_invalido");
    exit();
  }

  // Data DEVE bater com a data do evento
  if ($evento['data_evento'] !== $data_evento) {
    $dataCorreta = (new DateTime($evento['data_evento']))->format('d/m/Y');
    header("Location: /pages/treinos.php?aba={$aba}&erro=data_errada&data_correta=" . urlencode($dataCorreta));
    exit();
  }

  // Verifica se já adicionou este evento
  $stmt = $pdo->prepare("SELECT id FROM usuario_eventos WHERE usuario_id = ? AND evento_id = ?");
  $stmt->execute([$_SESSION['id'], $evento_id]);
  if ($stmt->fetch()) {
    header("Location: /pages/treinos.php?aba={$aba}&erro=ja_adicionado");
    exit();
  }

  $stmt = $pdo->prepare("INSERT INTO usuario_eventos (usuario_id, evento_id, data_evento) VALUES (?, ?, ?)");
  $stmt->execute([$_SESSION['id'], $evento_id, $data_evento]);

} else {
  // Evento manual
  if (empty($nome_manual)) {
    header("Location: /pages/treinos.php?aba={$aba}&erro=campos");
    exit();
  }

  $stmt = $pdo->prepare("INSERT INTO usuario_eventos (usuario_id, nome_manual, data_evento) VALUES (?, ?, ?)");
  $stmt->execute([$_SESSION['id'], $nome_manual, $data_evento]);
  
  $evento = ['nome' => $nome_manual, 'banner' => null];
}

// -------- Gatilho Automático Feed --------
$rawNome = $_SESSION['nome'] ?? $_SESSION['id'] ?? 'Usuário';
$primeiroNome = explode(' ', $rawNome)[0];

if (!empty($evento['banner'])) {
    $foto = $evento['banner'];
    $titulo = $primeiroNome . " vai participar de " . $evento['nome'];
} else {
    $foto = null;
    $titulo = $primeiroNome . " vai participar de " . ($evento['nome'] ?? 'um evento');
}

$eventoParam = ($evento_id > 0) ? $evento_id : null;

$stmtPost = $pdo->prepare("INSERT INTO posts (usuario_id, tipo, titulo, foto, evento_id, criado_em) VALUES (?, 'evento', ?, ?, ?, NOW())");
$stmtPost->execute([$_SESSION['id'], $titulo, $foto, $eventoParam]);

header("Location: /pages/treinos.php?aba={$aba}&msg=evento_adicionado");
exit();
