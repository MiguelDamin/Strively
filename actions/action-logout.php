<?php
// ==========================================================
// STRIVELY — actions/action-logout.php
// Encerra a sessão do usuário e redireciona para home
// ==========================================================

$only_session = true;
require_once '../components/header.php';

// Limpar cookie remember_me
setcookie('remember_token', '', time() - 3600, '/', '', true, true);
if (isset($_SESSION['id'])) {
    $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = NULL, remember_expira = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['id']]);
}

// Destroi todos os dados da sessão
session_destroy();

// Redireciona para a página inicial
header('Location: /index.php');
exit();