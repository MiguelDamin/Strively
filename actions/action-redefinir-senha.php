<?php
// ==========================================================
// STRIVELY — actions/action-redefinir-senha.php
// Salva a nova senha configurada no esqueci-senha.php
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['recuperacao_autorizada'])) {
    header('Location: /Strively/pages/esqueci-senha.php');
    exit();
}

$novaSenha = $_POST['nova_senha'] ?? '';
$confirmaSenha = $_POST['confirma_senha'] ?? '';

if (strlen($novaSenha) < 6) {
    header('Location: /Strively/pages/esqueci-senha.php?etapa=nova_senha&erro=senha_curta');
    exit();
}

if ($novaSenha !== $confirmaSenha) {
    header('Location: /Strively/pages/esqueci-senha.php?etapa=nova_senha&erro=senhas_diferentes');
    exit();
}

$email = $_SESSION['recuperacao_email'];
$hash = password_hash($novaSenha, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
$stmt->execute([$hash, $email]);

// Limpa toda a sessão de recuperação por segurança
unset(
    $_SESSION['recuperacao_email'], 
    $_SESSION['recuperacao_codigo'], 
    $_SESSION['recuperacao_expira'], 
    $_SESSION['recuperacao_autorizada']
);

// Sucesso! Volta para o login para testar a senha nova
header('Location: /Strively/pages/login.php?msg=senha_redefinida');
exit();
