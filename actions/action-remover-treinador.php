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

// Busca info do treinador antes para notificação (opcional, vamos apenas remover)
$stmt = $pdo->prepare("
  UPDATE usuarios
  SET treinador_id = NULL, status_vinculo = NULL
  WHERE id = ?
");
$stmt->execute([$usuario_id]);

header('Location: /pages/treinos.php?msg=treinador_removido');
exit();
