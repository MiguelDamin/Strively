<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id']) || $_SESSION['perfil'] !== 'treinador') {
  header('Location: /pages/alunos.php');
  exit();
}

$aluno_id    = (int)($_POST['aluno_id']    ?? 0);
$titulo      = trim($_POST['titulo']       ?? '');
$data_treino = $_POST['data_treino']       ?? '';
$tipo_sel    = $_POST['tipo_treino']       ?? '';
$tipo_outro  = trim($_POST['tipo_outro']   ?? '');
$descricao   = trim($_POST['descricao']    ?? '');
$aba         = $_POST['aba']               ?? 'calendario';

$tipo_final = ($tipo_sel === 'outro') ? $tipo_outro : $tipo_sel;

if (empty($titulo) || empty($data_treino) || empty($tipo_final) || !$aluno_id) {
  header("Location: /pages/treinos-alunos.php?aluno_id={$aluno_id}&aba={$aba}");
  exit();
}

// Verifica que o aluno pertence a esse treinador
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND treinador_id = ?");
$stmt->execute([$aluno_id, $_SESSION['id']]);
if (!$stmt->fetch()) {
  header('Location: /pages/alunos.php');
  exit();
}

$tituloFinal = $tipo_final . ' — ' . $titulo;

$stmt = $pdo->prepare("INSERT INTO treinos (treinador_id, aluno_id, titulo, descricao, data_treino, tipo) VALUES (?, ?, ?, ?, ?, 'unico')");
$stmt->execute([$_SESSION['id'], $aluno_id, $tituloFinal, $descricao ?: '', $data_treino]);

header("Location: /pages/treinos-alunos.php?aluno_id={$aluno_id}&aba={$aba}&msg=criado");
exit();
