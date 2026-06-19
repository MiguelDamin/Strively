<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    exit();
}

$treinoId = (int)($_POST['treino_id'] ?? 0);
$rpe      = !empty($_POST['rpe'])              ? (int)$_POST['rpe']              : null;
$duracao  = !empty($_POST['duracao_minutos'])  ? (int)$_POST['duracao_minutos']  : null;

if (!$treinoId) {
    http_response_code(400);
    exit();
}

// Verificar que o treino pertence ao usuário logado
$stmt = $pdo->prepare("SELECT id FROM treinos WHERE id = ? AND aluno_id = ?");
$stmt->execute([$treinoId, $_SESSION['id']]);
if (!$stmt->fetch()) {
    http_response_code(403);
    exit();
}

$stmt = $pdo->prepare("UPDATE treinos SET rpe = ?, duracao_minutos = ? WHERE id = ?");
$stmt->execute([$rpe, $duracao, $treinoId]);

echo json_encode(['ok' => true]);
