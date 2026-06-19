<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id'])) {
    header('Location: /pages/login.php');
    exit();
}

$treino_id = (int)($_POST['treino_id'] ?? 0);
$aluno_id  = (int)($_POST['aluno_id'] ?? $_SESSION['id']);
$user_id   = $_SESSION['id'];

// Verificar permissão
if ($user_id == $aluno_id) {
    $stmt = $pdo->prepare("SELECT id FROM treinos WHERE id = ? AND aluno_id = ?");
    $stmt->execute([$treino_id, $aluno_id]);
} else {
    // Treinador apagando treino do aluno
    $stmt = $pdo->prepare("
        SELECT t.id 
        FROM treinos t
        JOIN usuarios a ON a.id = t.aluno_id
        WHERE t.id = ? AND t.aluno_id = ? AND a.treinador_id = ?
    ");
    $stmt->execute([$treino_id, $aluno_id, $user_id]);
}

if (!$stmt->fetch()) {
    header('Location: /pages/treinos.php?erro=sem_permissao');
    exit();
}

$pdo->prepare("DELETE FROM posts WHERE treino_id = ?")->execute([$treino_id]);
$pdo->prepare("DELETE FROM treinos WHERE id = ?")->execute([$treino_id]);

if ($user_id != $aluno_id) {
    header("Location: /pages/treinos-alunos.php?id={$aluno_id}&msg=removido&aba=" . ($_POST['aba'] ?? 'calendario'));
} else {
    header('Location: /pages/treinos.php?msg=removido');
}
exit();
