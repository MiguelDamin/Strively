<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

$post_id = $_POST['post_id'] ?? $_GET['post_id'] ?? null;
if (!$post_id || !isset($_SESSION['id'])) {
    header('Location: /pages/comunidade.php');
    exit();
}

$stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND usuario_id = ?");
$stmt->execute([$post_id, $_SESSION['id']]);

header('Location: /pages/comunidade.php');
exit();
