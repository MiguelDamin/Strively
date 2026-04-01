<?php
// ==========================================================
// STRIVELY — actions/action-cadastro.php
// Processa o formulário de cadastro
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

// Só aceita requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../pages/cadastro.php');
  exit();
}

// Pega e limpa os dados do formulário
$nome           = trim($_POST['nome']           ?? '');
$email          = trim($_POST['email']          ?? '');
$cidade         = trim($_POST['cidade']         ?? '');
$senha          = $_POST['senha']               ?? '';
$senha_confirma = $_POST['senha_confirma']      ?? '';

// Valida campos obrigatórios
if (empty($nome) || empty($email) || empty($senha)) {
  header('Location: ../pages/cadastro.php?erro=campos_vazios');
  exit();
}

// Valida tamanho da senha
if (strlen($senha) < 6) {
  header('Location: ../pages/cadastro.php?erro=senha_curta');
  exit();
}

// Valida se as senhas coincidem
if ($senha !== $senha_confirma) {
  header('Location: ../pages/cadastro.php?erro=senha_diferente');
  exit();
}

// Verifica se o email já está cadastrado
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
  header('Location: ../pages/cadastro.php?erro=email_existente');
  exit();
}

// Gera o hash da senha — nunca salva senha pura no banco
$senhaHash = password_hash($senha, PASSWORD_BCRYPT);

// Insere o novo usuário no banco
$stmt = $pdo->prepare("
  INSERT INTO usuarios (nome, email, cidade, senha, perfil, status)
  VALUES (?, ?, ?, ?, 'corredor', 'ativo')
");
$stmt->execute([$nome, $email, $cidade, $senhaHash]);

// Redireciona para login com mensagem de sucesso
header('Location: ../pages/login.php?msg=cadastrado');
exit();