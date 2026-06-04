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
$stmt = $pdo->prepare("SELECT status FROM treinos WHERE id = ? AND aluno_id = ?");
$stmt->execute([$treino_id, $_SESSION['id']]);
$treino = $stmt->fetch();

$novoStatus = 'pendente';
if ($treino) {
  $novoStatus = ($treino['status'] === 'realizado') ? 'pendente' : 'realizado';
  $stmt = $pdo->prepare("UPDATE treinos SET status = ? WHERE id = ? AND aluno_id = ?");
  $stmt->execute([$novoStatus, $treino_id, $_SESSION['id']]);
  // Removed automatic community feed trigger post creation
}

$msg = ($novoStatus === 'realizado') ? 'realizado' : 'desmarcado';

header("Location: /pages/treinos.php?aba={$aba}&msg={$msg}");
exit();
