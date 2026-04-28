<?php
// ==========================================================
// STRIVELY — actions/action-virar-treinador.php
// Processa solicitação de modo treinador
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: /pages/virar-treinador.php');
  exit();
}

// Só corredores podem solicitar
if ($_SESSION['perfil'] === 'treinador') {
  header('Location: /index.php');
  exit();
}

$usuarioId   = $_SESSION['id'];
$cref        = trim($_POST['cref']        ?? '');
$faculdade   = trim($_POST['faculdade']   ?? '');
$assessoria  = trim($_POST['assessoria']  ?? '');
$especialidade = trim($_POST['especialidade'] ?? '');
$diploma     = $_FILES['diploma']         ?? null;

// ── Validações ────────────────────────────────────────────
if (empty($cref) || empty($faculdade) || empty($especialidade)) {
  header('Location: /pages/virar-treinador.php?erro=campos_vazios');
  exit();
}

if (empty($diploma['tmp_name'])) {
  header('Location: /pages/virar-treinador.php?erro=sem_diploma');
  exit();
}

$ext = strtolower(pathinfo($diploma['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
  header('Location: /pages/virar-treinador.php?erro=formato_doc');
  exit();
}

if ($diploma['size'] > 8 * 1024 * 1024) {
  header('Location: /pages/virar-treinador.php?erro=arquivo_grande');
  exit();
}

// Verifica se já existe solicitação pendente
$stmt = $pdo->prepare("SELECT id, status FROM treinadores WHERE usuario_id = ?");
$stmt->execute([$usuarioId]);
$existente = $stmt->fetch();

if ($existente && $existente['status'] === 'pendente') {
  header('Location: /pages/virar-treinador.php?erro=ja_solicitado');
  exit();
}

// ── Upload do diploma para Supabase Storage ───────────────
$supabaseUrl = $_ENV['SUPABASE_URL'];
$supabaseKey = $_ENV['SUPABASE_SERVICE_ROLE_KEY'];
$bucket      = 'diplomas-treinadores';

$nomeArquivo = 'diploma-' . $usuarioId . '-' . uniqid() . '.' . $ext;
$conteudo    = file_get_contents($diploma['tmp_name']);

$contentType = match($ext) {
  'pdf'        => 'application/pdf',
  'png'        => 'image/png',
  'jpg', 'jpeg'=> 'image/jpeg',
  default      => 'application/octet-stream',
};

$endpoint = $supabaseUrl . '/storage/v1/object/' . $bucket . '/' . $nomeArquivo;

$contexto = stream_context_create([
  'http' => [
    'method'  => 'POST',
    'header'  =>
      "Authorization: Bearer {$supabaseKey}\r\n" .
      "Content-Type: {$contentType}\r\n" .
      "x-upsert: true\r\n",
    'content' => $conteudo,
    'ignore_errors' => true,
    'timeout' => 30,
  ],
]);

$resposta = @file_get_contents($endpoint, false, $contexto);

$httpCode = 500;
if (!empty($http_response_header)) {
  preg_match('#HTTP/\d+\.\d+ (\d+)#', $http_response_header[0], $m);
  if (isset($m[1])) $httpCode = (int)$m[1];
}

if ($httpCode !== 200 && $httpCode !== 201) {
  error_log("Upload diploma falhou: HTTP $httpCode — $resposta");
  header('Location: /pages/virar-treinador.php?erro=upload_falhou');
  exit();
}

// URL do arquivo (bucket privado — não precisa ser público)
$diplomaPath = $supabaseUrl . '/storage/v1/object/' . $bucket . '/' . $nomeArquivo;

// ── Salva ou atualiza no banco ────────────────────────────
try {
  if ($existente && $existente['status'] === 'reprovado') {
    // Reenvio após reprovação — atualiza o registro
    $stmt = $pdo->prepare("
      UPDATE treinadores
      SET cref = ?, faculdade = ?, assessoria = ?, especialidade = ?,
          diploma_path = ?, status = 'pendente', motivo_reprovacao = NULL
      WHERE usuario_id = ?
    ");
    $stmt->execute([$cref, $faculdade, $assessoria, $especialidade, $diplomaPath, $usuarioId]);
  } else {
    // Primeira solicitação
    $stmt = $pdo->prepare("
      INSERT INTO treinadores (usuario_id, cref, faculdade, assessoria, especialidade, diploma_path, status)
      VALUES (?, ?, ?, ?, ?, ?, 'pendente')
    ");
    $stmt->execute([$usuarioId, $cref, $faculdade, $assessoria, $especialidade, $diplomaPath]);
  }
} catch (PDOException $e) {
  error_log("Erro ao salvar treinador: " . $e->getMessage());
  header('Location: /pages/virar-treinador.php?erro=db_falhou');
  exit();
}

// ── Notificação interna para o usuário ───────────────────
$pdo->prepare("
  INSERT INTO notificacoes (usuario_id, texto, link)
  VALUES (?, 'Sua solicitação de treinador foi recebida e está em análise. Em breve você receberá uma resposta.', '/pages/virar-treinador.php')
")->execute([$usuarioId]);

// ── Email para o ADMIN ────────────────────────────────────
// Busca dados do usuário solicitante
$stmtUser = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
$stmtUser->execute([$usuarioId]);
$usuario = $stmtUser->fetch();

$adminEmail = $_ENV['MAIL_USER']; // Você recebe no próprio email configurado

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
  $mail->addAddress($adminEmail);
  $mail->isHTML(true);
  $mail->Subject = '🏋️ Nova solicitação de treinador — Strively';

  $linkAdmin  = 'https://' . $_SERVER['HTTP_HOST'] . '/pages/admin/treinadores.php';
  $linkDiploma = htmlspecialchars($diplomaPath);

  $mail->Body = "
  <div style='font-family: Arial, sans-serif; background: #f4f4f4; padding: 32px;'>
    <div style='max-width: 560px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden;'>

      <div style='background: #1DB954; padding: 24px 32px;'>
        <h2 style='color: #fff; margin: 0; font-size: 20px;'>Nova solicitação de treinador</h2>
      </div>

      <div style='padding: 32px;'>
        <table style='width: 100%; border-collapse: collapse; font-size: 14px;'>
          <tr><td style='padding: 8px 0; color: #666; width: 140px;'>Nome</td>         <td style='padding: 8px 0; font-weight: bold;'>" . htmlspecialchars($usuario['nome']) . "</td></tr>
          <tr><td style='padding: 8px 0; color: #666;'>E-mail</td>        <td style='padding: 8px 0;'>" . htmlspecialchars($usuario['email']) . "</td></tr>
          <tr><td style='padding: 8px 0; color: #666;'>CREF</td>          <td style='padding: 8px 0;'>" . htmlspecialchars($cref) . "</td></tr>
          <tr><td style='padding: 8px 0; color: #666;'>Faculdade</td>     <td style='padding: 8px 0;'>" . htmlspecialchars($faculdade) . "</td></tr>
          <tr><td style='padding: 8px 0; color: #666;'>Assessoria</td>    <td style='padding: 8px 0;'>" . (empty($assessoria) ? '—' : htmlspecialchars($assessoria)) . "</td></tr>
          <tr><td style='padding: 8px 0; color: #666;'>Especialidade</td> <td style='padding: 8px 0;'>" . htmlspecialchars($especialidade) . "</td></tr>
        </table>

        <div style='margin: 24px 0;'>
          <a href='{$linkDiploma}' style='display: inline-block; background: #f0f0f0; color: #333; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 14px;'>
            📄 Ver diploma / CREF enviado
          </a>
        </div>

        <a href='{$linkAdmin}' style='display: block; background: #1DB954; color: #fff; text-align: center; padding: 14px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 15px;'>
          Acessar painel de administração
        </a>

        <p style='font-size: 12px; color: #aaa; margin-top: 24px; text-align: center;'>Strively — painel administrativo</p>
      </div>
    </div>
  </div>";

  $mail->AltBody = "Nova solicitação de treinador de {$usuario['nome']} ({$usuario['email']}). CREF: {$cref}. Acesse o painel: {$linkAdmin}";
  $mail->send();

} catch (Exception $e) {
  // Email falhou mas não bloqueia o fluxo — só loga
  error_log("Email admin falhou: " . $mail->ErrorInfo);
}

// ── Redireciona com sucesso ───────────────────────────────
header('Location: /pages/virar-treinador.php?msg=enviado');
exit();