<?php
// ==========================================================
// STRIVELY — actions/action-adicionar-treino-proprio.php
// Corredor adiciona treino para si mesmo
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: /pages/treinos.php');
  exit();
}

$titulo      = trim($_POST['titulo']       ?? '');
$data_treino = $_POST['data_treino']       ?? '';
$tipo_sel    = $_POST['tipo_treino']       ?? '';
$tipo_outro  = trim($_POST['tipo_outro']   ?? '');
$descricao   = trim($_POST['descricao']    ?? '');
$aba         = $_POST['aba']               ?? 'calendario';

$tipo_final = ($tipo_sel === 'outro') ? $tipo_outro : $tipo_sel;

if (empty($titulo) || empty($data_treino) || empty($tipo_final)) {
  header("Location: /pages/treinos.php?aba={$aba}&erro=campos");
  exit();
}

$tituloFinal = $tipo_final . ' — ' . $titulo;

// treinador_id = aluno_id marca como auto-treino
$stmt = $pdo->prepare("INSERT INTO treinos (treinador_id, aluno_id, titulo, descricao, data_treino, tipo) VALUES (?, ?, ?, ?, ?, 'unico')");
$stmt->execute([$_SESSION['id'], $_SESSION['id'], $tituloFinal, $descricao ?: '', $data_treino]);

header("Location: /pages/treinos.php?aba={$aba}&msg=criado");
exit();
