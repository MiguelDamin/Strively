<?php
// ==========================================================
// STRIVELY — actions/action-remover-evento-usuario.php
// Corredor remove evento do seu calendário
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: /pages/treinos.php');
  exit();
}

$evento_usuario_id = (int)($_POST['evento_usuario_id'] ?? 0);
$aba               = $_POST['aba']                     ?? 'calendario';

$stmt = $pdo->prepare("DELETE FROM usuario_eventos WHERE id = ? AND usuario_id = ?");
$stmt->execute([$evento_usuario_id, $_SESSION['id']]);

header("Location: /pages/treinos.php?aba={$aba}&msg=evento_removido");
exit();
