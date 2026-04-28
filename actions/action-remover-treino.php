<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id']) || $_SESSION['perfil'] !== 'treinador') {
  header('Location: /pages/alunos.php');
  exit();
}

$treino_id = (int)($_POST['treino_id'] ?? 0);
$aluno_id  = (int)($_POST['aluno_id']  ?? 0);
$aba       = $_POST['aba']             ?? 'calendario';

$stmt = $pdo->prepare("DELETE FROM treinos WHERE id = ? AND treinador_id = ?");
$stmt->execute([$treino_id, $_SESSION['id']]);

header("Location: /pages/treinos-alunos.php?aluno_id={$aluno_id}&aba={$aba}&msg=removido");
exit();
