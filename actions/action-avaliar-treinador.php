<?php
// ==========================================================
// STRIVELY — actions/action-avaliar-treinador.php
// Processa aprovação ou reprovação de treinador pelo admin
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Proteção: só o admin
$adminId = (int)($_ENV['ADMIN_ID'] ?? 0);

if (!isset($_SESSION['id']) || $_SESSION['id'] !== $adminId) {
  http_response_code(403);
  die('Acesso negado.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /pages/admin/treinadores.php');
  exit();
}

$treinadorId = (int)($_POST['treinador_id'] ?? 0);
$usuarioId   = (int)($_POST['usuario_id']   ?? 0);
$decisao     = $_POST['decisao'] ?? '';
$motivo      = trim($_POST['motivo'] ?? '');

if (!$treinadorId || !$usuarioId || !in_array($decisao, ['aprovar', 'reprovar'])) {
  header('Location: /pages/admin/treinadores.php?erro=dados_invalidos');
  exit();
}

if ($decisao === 'reprovar' && empty($motivo)) {
  header('Location: /pages/admin/treinadores.php?erro=motivo_vazio');
  exit();
}

// Busca dados do usuário para o email
$stmtUser = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
$stmtUser->execute([$usuarioId]);
$usuario = $stmtUser->fetch();

if (!$usuario) {
  header('Location: /pages/admin/treinadores.php?erro=usuario_nao_encontrado');
  exit();
}

try {
  if ($decisao === 'aprovar') {

    // 1. Atualiza status do treinador para aprovado
    $pdo->prepare("UPDATE treinadores SET status = 'aprovado', motivo_reprovacao = NULL WHERE id = ?")
        ->execute([$treinadorId]);

    // 2. Muda o perfil do usuário para treinador
    $pdo->prepare("UPDATE usuarios SET perfil = 'treinador' WHERE id = ?")
        ->execute([$usuarioId]);

    // 3. Notificação interna
    $pdo->prepare("
      INSERT INTO notificacoes (usuario_id, texto, link)
      VALUES (?, '🎉 Parabéns! Sua solicitação de treinador foi aprovada. Você já pode criar treinos para seus alunos!', '/index.php')
    ")->execute([$usuarioId]);

    // 4. Email de aprovação para o usuário
    $assunto  = '✅ Solicitação de treinador aprovada — Strively';
    $htmlBody = "
    <div style='font-family: Arial, sans-serif; background: #f4f4f4; padding: 32px;'>
      <div style='max-width: 520px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden;'>
        <div style='background: #1DB954; padding: 24px 32px; text-align: center;'>
          <div style='font-size: 40px;'>🎉</div>
          <h2 style='color: #fff; margin: 8px 0 0; font-size: 22px;'>Você é um treinador verificado!</h2>
        </div>
        <div style='padding: 32px;'>
          <p style='font-size: 15px; color: #333;'>Olá, <strong>" . htmlspecialchars($usuario['nome']) . "</strong>!</p>
          <p style='font-size: 14px; color: #555; line-height: 1.7;'>
            Sua solicitação para o <strong>Modo Treinador</strong> no Strively foi <strong style='color: #1DB954;'>aprovada</strong>! 
            A partir de agora você tem acesso ao painel de treinador e pode criar treinos personalizados para seus alunos.
          </p>
          <div style='background: #f0fff4; border-radius: 8px; padding: 16px; margin: 20px 0;'>
            <p style='margin: 0; font-size: 13px; color: #1a7a40;'>
              🏃 Crie planilhas de treino personalizadas<br>
              👥 Gerencie seus alunos<br>
              🏅 Exiba seu badge de treinador verificado
            </p>
          </div>
          <a href='https://" . $_SERVER['HTTP_HOST'] . "/index.php' 
             style='display: block; background: #1DB954; color: #fff; text-align: center; padding: 14px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 15px;'>
            Acessar o Strively
          </a>
          <p style='font-size: 12px; color: #aaa; margin-top: 24px; text-align: center;'>Strively — Corra Mais Longe</p>
        </div>
      </div>
    </div>";
    $altBody = "Olá {$usuario['nome']}! Sua solicitação de treinador no Strively foi aprovada. Acesse: https://{$_SERVER['HTTP_HOST']}/index.php";

  } else {

    // 1. Atualiza status para reprovado com motivo
    $pdo->prepare("UPDATE treinadores SET status = 'reprovado', motivo_reprovacao = ? WHERE id = ?")
        ->execute([$motivo, $treinadorId]);

    // 2. Notificação interna
    $pdo->prepare("
      INSERT INTO notificacoes (usuario_id, texto, link)
      VALUES (?, ?, '/pages/virar-treinador.php')
    ")->execute([$usuarioId, "Sua solicitação de treinador foi reprovada. Motivo: {$motivo}. Você pode enviar uma nova solicitação corrigindo as informações."]);

    // 3. Email de reprovação para o usuário
    $assunto  = 'Atualização da sua solicitação de treinador — Strively';
    $htmlBody = "
    <div style='font-family: Arial, sans-serif; background: #f4f4f4; padding: 32px;'>
      <div style='max-width: 520px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden;'>
        <div style='background: #cc0000; padding: 24px 32px; text-align: center;'>
          <h2 style='color: #fff; margin: 0; font-size: 20px;'>Solicitação não aprovada</h2>
        </div>
        <div style='padding: 32px;'>
          <p style='font-size: 15px; color: #333;'>Olá, <strong>" . htmlspecialchars($usuario['nome']) . "</strong>.</p>
          <p style='font-size: 14px; color: #555; line-height: 1.7;'>
            Infelizmente, sua solicitação para o Modo Treinador não pôde ser aprovada neste momento.
          </p>
          <div style='background: #fff0f0; border: 1px solid #ffcccc; border-radius: 8px; padding: 16px; margin: 20px 0;'>
            <p style='margin: 0 0 4px; font-size: 12px; color: #cc0000; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;'>Motivo</p>
            <p style='margin: 0; font-size: 14px; color: #aa0000;'>" . htmlspecialchars($motivo) . "</p>
          </div>
          <p style='font-size: 14px; color: #555; line-height: 1.7;'>
            Você pode enviar uma nova solicitação com as correções necessárias acessando sua conta.
          </p>
          <a href='https://" . $_SERVER['HTTP_HOST'] . "/pages/virar-treinador.php' 
             style='display: block; background: #333; color: #fff; text-align: center; padding: 14px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 15px;'>
            Reenviar solicitação
          </a>
          <p style='font-size: 12px; color: #aaa; margin-top: 24px; text-align: center;'>Strively — Corra Mais Longe</p>
        </div>
      </div>
    </div>";
    $altBody = "Olá {$usuario['nome']}. Sua solicitação de treinador foi reprovada. Motivo: {$motivo}. Acesse para reenviar: https://{$_SERVER['HTTP_HOST']}/pages/virar-treinador.php";
  }

} catch (PDOException $e) {
  error_log("Erro ao avaliar treinador: " . $e->getMessage());
  header('Location: /pages/admin/treinadores.php?erro=db_falhou');
  exit();
}

// ── Envia email para o usuário ────────────────────────────
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

  $mail->setFrom($_ENV['MAIL_USER'], $_ENV['MAIL_FROM_NAME']);
  $mail->addAddress($usuario['email']);
  $mail->isHTML(true);
  $mail->Subject = $assunto;
  $mail->Body    = $htmlBody;
  $mail->AltBody = $altBody;
  $mail->send();
} catch (Exception $e) {
  error_log("Email avaliação falhou: " . $mail->ErrorInfo);
}

header("Location: /pages/admin/treinadores.php?filtro=pendente&msg={$decisao}");
exit();