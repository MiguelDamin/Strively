<?php
// ==========================================================
// STRIVELY — actions/action-adicionar-aluno.php
// Vincula um corredor ao treinador logado
// ==========================================================

require_once '../components/header.php';

if (!isset($_SESSION['id']) || $_SESSION['perfil'] !== 'treinador') {
  header('Location: /pages/login.php');
  exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /pages/alunos.php');
  exit();
}

$aluno_id = (int) ($_POST['aluno_id'] ?? 0);

if (!$aluno_id) {
  header('Location: /pages/alunos.php');
  exit();
}

require_once '../config/conexao.php';

$treinador_usuario_id = $_SESSION['id'];

// Verifica que o aluno existe, é corredor e ainda não tem treinador
$stmt = $pdo->prepare("SELECT id, treinador_id FROM usuarios WHERE id = ? AND perfil = 'corredor'");
$stmt->execute([$aluno_id]);
$aluno = $stmt->fetch();

if (!$aluno) {
  header('Location: /pages/alunos.php');
  exit();
}

if ($aluno['treinador_id']) {
  // Já tem treinador — redireciona sem fazer nada
  header('Location: /pages/alunos.php');
  exit();
}

// Vincula o aluno ao treinador (treinador_id aponta para usuarios.id do treinador)
$stmt = $pdo->prepare("UPDATE usuarios SET treinador_id = ? WHERE id = ?");
$stmt->execute([$treinador_usuario_id, $aluno_id]);

header('Location: /pages/alunos.php?msg=adicionado');
exit();
