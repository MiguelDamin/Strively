<?php
// ==========================================================
// STRIVELY — actions/action-login.php
// Processa o formulário de login
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

// Só aceita requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../pages/login.php');
  exit();
}

// Pega e limpa os dados do formulário
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

// Busca o usuário pelo email
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

// Verifica se o usuário existe e a senha está correta
if (!$usuario || !password_verify($senha, $usuario['senha'])) {
  header('Location: ../pages/login.php?erro=credenciais');
  exit();
}

// Verifica se a conta está ativa
if ($usuario['status'] !== 'ativo') {
  header('Location: ../pages/login.php?erro=inativo');
  exit();
}

// Salva os dados na sessão
$_SESSION['id']     = $usuario['id'];
$_SESSION['nome']   = $usuario['nome'];
$_SESSION['perfil'] = $usuario['perfil'];
$_SESSION['foto']   = $usuario['foto'];

// Redireciona para home
header('Location: ../index.php');
exit();