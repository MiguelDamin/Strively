<?php
// ==========================================================
// STRIVELY — actions/action-verificar-codigo-esqueci.php
// Valida o PIN inserido durante recuperação de conta
// ==========================================================

$only_session = true;
require_once '../components/header.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['recuperacao_codigo'])) {
    header('Location: /Strively/pages/esqueci-senha.php');
    exit();
}

$codigoDigitado = trim($_POST['codigo'] ?? '');
$codigoCorreto = $_SESSION['recuperacao_codigo'];
$expiracao = $_SESSION['recuperacao_expira'];

if (empty($codigoDigitado) || $codigoDigitado !== $codigoCorreto) {
    header('Location: /Strively/pages/esqueci-senha.php?etapa=codigo&erro=codigo_invalido');
    exit();
}

if (time() > $expiracao) {
    // Revoga acesso total
    unset($_SESSION['recuperacao_email'], $_SESSION['recuperacao_codigo'], $_SESSION['recuperacao_expira']);
    header('Location: /Strively/pages/esqueci-senha.php?etapa=codigo&erro=codigo_expirado');
    exit();
}

// Senha validada! Libera a etapa de redefinição
$_SESSION['recuperacao_autorizada'] = true;
header('Location: /Strively/pages/esqueci-senha.php?etapa=nova_senha');
exit();
