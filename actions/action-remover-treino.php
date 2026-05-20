<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id'])) {
    header('Location: /pages/login.php');
    exit();
}

$treino_id = (int)($_POST['treino_id'] ?? 0);

// Verificar que o treino pertence ao usuário logado
$stmt = $pdo->prepare("SELECT id FROM treinos WHERE id = ? AND aluno_id = ?");
$stmt->execute([$treino_id, $_SESSION['id']]);
if (!$stmt->fetch()) {
    header('Location: /pages/treinos.php?erro=sem_permissao');
    exit();
}

// Deletar post associado se existir
$pdo->prepare("DELETE FROM posts WHERE treino_id = ? AND usuario_id = ?")
    ->execute([$treino_id, $_SESSION['id']]);

// Deletar treino
$pdo->prepare("DELETE FROM treinos WHERE id = ? AND aluno_id = ?")
    ->execute([$treino_id, $_SESSION['id']]);

header('Location: /pages/treinos.php?msg=removido');
exit();
