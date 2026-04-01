<?php
// ==========================================================
// STRIVELY — actions/action-enviar-codigo.php
// Dispara o e-mail com código para verificação de senha
// ==========================================================

$only_session = true;
require_once '../components/header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../config/conexao.php'; // Já carrega autoload e Dotenv

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
    header('Location: /Strively/pages/configuracoes.php');
    exit();
}

$novaSenha = $_POST['nova_senha'] ?? '';
$confirmaSenha = $_POST['confirma_senha'] ?? '';

if (strlen($novaSenha) < 6) {
    header('Location: /Strively/pages/configuracoes.php?erro=senha_curta');
    exit();
}

if ($novaSenha !== $confirmaSenha) {
    header('Location: /Strively/pages/configuracoes.php?erro=senhas_diferentes');
    exit();
}

// 1. Pega e-mail do banco de dados do usuário ativo
$stmt = $pdo->prepare("SELECT email FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['id']]);
$user = $stmt->fetch();
$emailUser = $user['email'];

// 2. Gera codigo randômico de 6 dígitos
$codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// 3. Salva estado temporário na sessão
$_SESSION['codigo_verificacao'] = $codigo;
$_SESSION['codigo_expira'] = time() + 600; // expira em 10 minutos
$_SESSION['nova_senha_temp'] = password_hash($novaSenha, PASSWORD_DEFAULT);

// 4. Configurar e disparar e-mail via PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USER'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $_ENV['MAIL_PORT'];
    $mail->CharSet    = 'UTF-8';

    // Rementente e destinatário
    $mail->setFrom($_ENV['MAIL_USER'], $_ENV['MAIL_FROM_NAME']);
    $mail->addAddress($emailUser);

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = 'Código de Verificação de Senha - Strively';
    
    // HTML bonito direto para o usuário
    $htmlBody = "
    <div style='font-family: Arial, sans-serif; background-color: #121212; color: #ffffff; padding: 40px; border-radius: 8px;'>
        <h2 style='color: #1DB954; text-align: center;'>Mudança de Senha - Strively</h2>
        <p style='font-size: 16px; color: #b3b3b3;'>Olá,</p>
        <p style='font-size: 16px; color: #b3b3b3;'>Recebemos uma solicitação para alterar a senha da sua conta.</p>
        <div style='margin: 30px 0; text-align: center;'>
            <span style='background-color: #282828; color: #ffffff; font-size: 32px; font-weight: bold; padding: 10px 20px; border-radius: 6px; letter-spacing: 4px;'>{$codigo}</span>
        </div>
        <p style='font-size: 14px; color: #b3b3b3;'>Digite este código na página de configurações para confirmar. Ele expira em 10 minutos.</p>
        <p style='font-size: 12px; color: #535353; margin-top: 40px;'>Se você não solicitou esta alteração, ignore este e-mail de forma segura.</p>
    </div>";

    $mail->Body = $htmlBody;
    $mail->AltBody = "Olá! O seu código de verificação para alterar a senha é: {$codigo} (Expira em 10 min)";

    $mail->send();
    
    // Redireciona para exibir o input do código na mesma tela de configuracoes
    header('Location: /Strively/pages/configuracoes.php?etapa=verificacao');
    exit();
    
} catch (Exception $e) {
    header('Location: /Strively/pages/configuracoes.php?erro=email_falhou');
    // Para ver o erro detalhado durante desenvolvimento comente a Location e descomente:
    // echo "Mensagem não enviada. Mailer Error: {$mail->ErrorInfo}";
    exit();
}
