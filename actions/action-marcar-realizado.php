<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: /pages/treinos.php');
  exit();
}

$treino_id = (int)($_POST['treino_id'] ?? 0);
$aba       = $_POST['aba'] ?? 'calendario';

if (!$treino_id) {
  header("Location: /pages/treinos.php?aba={$aba}");
  exit();
}

// Atualiza somente se o treino pertence ao aluno logado
$stmt = $pdo->prepare("UPDATE treinos SET status = 'realizado' WHERE id = ? AND aluno_id = ?");
$stmt->execute([$treino_id, $_SESSION['id']]);

header("Location: /pages/treinos.php?aba={$aba}&msg=realizado");
exit();
