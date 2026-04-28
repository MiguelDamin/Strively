<?php
// ==========================================================
// STRIVELY — actions/action-remover-treino-proprio.php
// Corredor remove treino que ele mesmo criou
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: /pages/treinos.php');
  exit();
}

$treino_id = (int)($_POST['treino_id'] ?? 0);
$aba       = $_POST['aba']             ?? 'calendario';

// Só pode remover treinos que o próprio corredor criou (treinador_id = aluno_id = self)
$stmt = $pdo->prepare("DELETE FROM treinos WHERE id = ? AND aluno_id = ? AND treinador_id = ?");
$stmt->execute([$treino_id, $_SESSION['id'], $_SESSION['id']]);

header("Location: /pages/treinos.php?aba={$aba}&msg=removido");
exit();
