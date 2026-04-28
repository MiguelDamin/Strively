<?php
// ==========================================================
// STRIVELY — actions/action-solicitar-treinador.php
// Envia solicitação de vínculo corredor → treinador
// ==========================================================

$only_session = true;
require_once '../components/header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../config/conexao.php';

// Validações básicas
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: /index.php');
  exit();
}

$treinadorId = (int)($_POST['treinador_id'] ?? 0);
$corredorId  = $_SESSION['id'];

if ($treinadorId <= 0) {
  header('Location: /pages/buscar-treinador.php?msg=erro');
  exit();
}

// Verifica que o corredor não tem vínculo ativo
$stmt = $pdo->prepare("SELECT perfil, treinador_id, status_vinculo, nome FROM usuarios WHERE id = ?");
$stmt->execute([$corredorId]);
$corredor = $stmt->fetch();

if (!$corredor || $corredor['perfil'] !== 'corredor') {
  header('Location: /index.php');
  exit();
}

if (!empty($corredor['treinador_id'])) {
  header('Location: /pages/buscar-treinador.php?msg=ja_vinculado');
  exit();
}

// Verifica que o treinador existe e está aprovado
$stmt = $pdo->prepare("
  SELECT u.id, u.nome, u.email
  FROM usuarios u
  INNER JOIN treinadores t ON t.usuario_id = u.id
  WHERE u.id = ?
    AND u.perfil = 'treinador'
    AND u.status = 'ativo'
    AND t.status = 'aprovado'
");
$stmt->execute([$treinadorId]);
$treinador = $stmt->fetch();

if (!$treinador) {
  header('Location: /pages/buscar-treinador.php?msg=erro');
  exit();
}

// Gera token para links de aceitar/recusar
$token = bin2hex(random_bytes(32));

// Atualiza o corredor: vincula ao treinador com status pendente
$stmt = $pdo->prepare("
  UPDATE usuarios
  SET treinador_id = ?, status_vinculo = 'pendente', token_vinculo = ?
  WHERE id = ?
");
$stmt->execute([$treinadorId, $token, $corredorId]);

// Cria notificação para o treinador
$textoNotif = htmlspecialchars($corredor['nome']) . " solicitou vínculo como seu aluno.";
$linkNotif  = "/pages/alunos.php";
$stmt = $pdo->prepare("INSERT INTO notificacoes (usuario_id, texto, link) VALUES (?, ?, ?)");
$stmt->execute([$treinadorId, $textoNotif, $linkNotif]);

// Envia e-mail para o treinador
$baseUrl = rtrim($_ENV['APP_URL'] ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']), '/');

$linkAceitar = $baseUrl . "/actions/action-aceitar-vinculo.php?corredor_id={$corredorId}&token={$token}&acao=aceitar";
$linkRecusar = $baseUrl . "/actions/action-aceitar-vinculo.php?corredor_id={$corredorId}&token={$token}&acao=recusar";

$nomeCorredor  = htmlspecialchars($corredor['nome']);
$nomeTreinador = htmlspecialchars($treinador['nome']);

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
  $mail->addAddress($treinador['email']);

  $mail->isHTML(true);
  $mail->Subject = "Nova solicitação de aluno — {$nomeCorredor} | Strively";

  $htmlBody = "
  <div style='font-family: Arial, sans-serif; background-color: #121212; color: #ffffff; padding: 40px; border-radius: 8px; max-width: 600px;'>
    <h2 style='color: #1DB954; text-align: center;'>Nova solicitação de aluno</h2>
    <p style='font-size: 16px; color: #b3b3b3;'>Olá, {$nomeTreinador}!</p>
    <p style='font-size: 16px; color: #b3b3b3;'>O corredor <strong style='color:#fff;'>{$nomeCorredor}</strong> quer se vincular a você como treinador na plataforma Strively.</p>

    <div style='margin: 30px 0; text-align: center;'>
      <a href='{$linkAceitar}'
         style='display:inline-block; background:#1DB954; color:#fff; padding:14px 32px; border-radius:30px; font-weight:bold; font-size:16px; text-decoration:none; margin-right:12px;'>
        ✅ Aceitar
      </a>
      <a href='{$linkRecusar}'
         style='display:inline-block; background:#333; color:#ff5555; padding:14px 32px; border-radius:30px; font-weight:bold; font-size:16px; text-decoration:none; border:1px solid #555;'>
        ❌ Recusar
      </a>
    </div>

    <p style='font-size: 14px; color: #b3b3b3;'>Ao aceitar, o corredor poderá ver os treinos que você criar para ele.</p>
    <p style='font-size: 12px; color: #535353; margin-top: 40px;'>Se você não reconhece esta solicitação, clique em Recusar ou ignore este e-mail.</p>
  </div>";

  $mail->Body    = $htmlBody;
  $mail->AltBody = "Olá, {$nomeTreinador}! O corredor {$nomeCorredor} solicitou vínculo como seu aluno. Aceitar: {$linkAceitar} | Recusar: {$linkRecusar}";

  $mail->send();

} catch (Exception $e) {
  // E-mail falhou mas o vínculo já foi criado — continua normalmente
  // Para debug: error_log("Mailer Error: " . $mail->ErrorInfo);
}

header('Location: /pages/buscar-treinador.php?msg=solicitado');
exit();
