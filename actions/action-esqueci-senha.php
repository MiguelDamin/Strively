<?php
// ==========================================================
// STRIVELY — actions/action-esqueci-senha.php
// Dispara o e-mail de recuperação de senha com código de 6 dígitos
// ==========================================================

$only_session = true;
require_once '../components/header.php';

require_once '../config/conexao.php'; // Carrega o vendor/autoload.php e Dotenv
require_once __DIR__ . '/../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/esqueci-senha.php');
    exit();
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    header('Location: /pages/esqueci-senha.php?erro=nao_encontrado');
    exit();
}

// Verifica se o usuário com este e-mail existe
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
if (!$stmt->fetch()) {
    // Por segurança e UX, retorna o mesmo erro genérico
    header('Location: /pages/esqueci-senha.php?erro=nao_encontrado');
    exit();
}

// 1. Gera codigo randômico de 6 dígitos
$codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// 2. Salva o contexto seguro na sessão
$_SESSION['recuperacao_email'] = $email;
$_SESSION['recuperacao_codigo'] = $codigo;
$_SESSION['recuperacao_expira'] = time() + 600; // Validade de 10 min

// 3. Enviar e-mail com código
$assunto = 'Recuperação de Senha - Strively';
$htmlBody = "
<div style='font-family: Arial, sans-serif; background-color: #121212; color: #ffffff; padding: 40px; border-radius: 8px;'>
    <h2 style='color: #1DB954; text-align: center;'>Recuperação de Senha</h2>
    <p style='font-size: 16px; color: #b3b3b3;'>Olá,</p>
    <p style='font-size: 16px; color: #b3b3b3;'>Recebemos um pedido de recuperação de senha vinculado à sua conta no Strively.</p>
    <div style='margin: 30px 0; text-align: center;'>
        <span style='background-color: #282828; color: #ffffff; font-size: 32px; font-weight: bold; padding: 10px 20px; border-radius: 6px; letter-spacing: 4px;'>{$codigo}</span>
    </div>
    <p style='font-size: 14px; color: #b3b3b3;'>Acesse o site novamente e insira este código para continuar com o processo. Ele irá expirar em 10 minutos.</p>
    <p style='font-size: 12px; color: #535353; margin-top: 40px;'>Se você não solicitou recuperação de conta recentemente, basta ignorar este e-mail. Não passe este código para ninguém.</p>
</div>";

$enviado = enviarEmail($email, $assunto, $htmlBody);

if ($enviado) {
    // Sucesso envia para a etapa do código
    header('Location: /pages/esqueci-senha.php?etapa=codigo');
    exit();
} else {
    error_log('STRIVELY MAIL ERROR: Resend API falhou');
    header('Location: /pages/esqueci-senha.php?erro=falha_email');
    exit();
}

