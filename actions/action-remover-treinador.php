<?php
// ==========================================================
// STRIVELY — actions/action-remover-treinador.php
// Remove a vinculação de um corredor com o seu treinador
// ==========================================================

$only_session = true;
require_once '../components/header.php';

if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /pages/treinos.php');
  exit();
}

require_once '../config/conexao.php';

$usuario_id = $_SESSION['id'];

// Busca info antes de remover
$stmtInfo = $pdo->prepare("SELECT nome, treinador_id FROM usuarios WHERE id = ?");
$stmtInfo->execute([$usuario_id]);
$alunoInfo = $stmtInfo->fetch();

if ($alunoInfo && !empty($alunoInfo['treinador_id'])) {
    $nomeAluno = htmlspecialchars($alunoInfo['nome']);
    $textoNotif = "O aluno {$nomeAluno} cancelou a vinculação (assinatura) de treinos com você.";
    $linkNotif = "/pages/alunos.php";
    
    $stmtNotif = $pdo->prepare("INSERT INTO notificacoes (usuario_id, texto, link) VALUES (?, ?, ?)");
    $stmtNotif->execute([$alunoInfo['treinador_id'], $textoNotif, $linkNotif]);
}

// Remove a vinculação
$stmt = $pdo->prepare("
  UPDATE usuarios
  SET treinador_id = NULL, status_vinculo = NULL
  WHERE id = ?
");
$stmt->execute([$usuario_id]);

header('Location: /pages/treinos.php?msg=treinador_removido');
exit();
