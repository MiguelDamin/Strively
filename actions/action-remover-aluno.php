<?php
// ==========================================================
// STRIVELY — actions/action-remover-aluno.php
// Remove a vinculação de um corredor com o treinador logado
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

// Remove a vinculação apenas se o aluno pertence a este treinador
// treinador_id na tabela usuarios aponta para usuarios.id do treinador
$stmt = $pdo->prepare("
  UPDATE usuarios
  SET treinador_id = NULL
  WHERE id = ? AND treinador_id = ?
");
$stmt->execute([$aluno_id, $treinador_usuario_id]);

header('Location: /pages/alunos.php?msg=removido');
exit();
