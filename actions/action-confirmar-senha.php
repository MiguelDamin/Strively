<?php
// ==========================================================
// STRIVELY — actions/action-confirmar-senha.php
// Valida o PIN e salva a nova senha no banco de dados
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
    header('Location: /pages/configuracoes.php');
    exit();
}

$codigoDigitado = trim($_POST['codigo'] ?? '');

$codigoCorreto = $_SESSION['codigo_verificacao'] ?? '';
$codigoExpira = $_SESSION['codigo_expira'] ?? 0;
$novaSenhaTemp = $_SESSION['nova_senha_temp'] ?? '';

// 1. Validar se o código bate
if (empty($codigoDigitado) || $codigoDigitado !== $codigoCorreto) {
    header('Location: /pages/configuracoes.php?etapa=verificacao&erro=codigo_invalido');
    exit();
}

// 2. Validar se expirou (limite de 10 min configurados em action-enviar-codigo)
if (time() > $codigoExpira) {
    // Por segurança, força ele a solicitar de novo
    unset($_SESSION['codigo_verificacao'], $_SESSION['codigo_expira'], $_SESSION['nova_senha_temp']);
    
    header('Location: /pages/configuracoes.php?etapa=verificacao&erro=codigo_expirado');
    exit();
}

// 3. Tudo Certo! Salva no banco de dados
$stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
$stmt->execute([$novaSenhaTemp, $_SESSION['id']]);

// 4. Limpa sessões temporárias relativas à senha
unset($_SESSION['codigo_verificacao'], $_SESSION['codigo_expira'], $_SESSION['nova_senha_temp']);

// 5. Retorna com mensagem de sucesso
header('Location: /pages/configuracoes.php?msg=senha_alterada');
exit();
