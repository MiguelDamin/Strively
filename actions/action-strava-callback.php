<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

// 1. Valida state CSRF
if (($_GET['state'] ?? '') !== ($_SESSION['strava_state'] ?? '')) {
  header('Location: /pages/perfil.php?erro=strava_state');
  exit();
}
unset($_SESSION['strava_state']);

// 2. Verifica se o usuário autorizou
if (isset($_GET['error']) || empty($_GET['code'])) {
  header('Location: /pages/perfil.php?erro=strava_negado');
  exit();
}

$code = $_GET['code'];

// 3. Troca o code por access_token via POST
$payload = json_encode([
  'client_id'     => $_ENV['STRAVA_CLIENT_ID'],
  'client_secret' => $_ENV['STRAVA_CLIENT_SECRET'],
  'code'          => $code,
  'grant_type'    => 'authorization_code',
]);

$ctx = stream_context_create([
  'http' => [
    'method'        => 'POST',
    'header'        => "Content-Type: application/json\r\nAccept: application/json\r\n",
    'content'       => $payload,
    'ignore_errors' => true,
    'timeout'       => 15,
  ],
  'ssl' => ['verify_peer' => true],
]);

$resp = @file_get_contents('https://www.strava.com/oauth/token', false, $ctx);

if (!$resp) {
  header('Location: /pages/perfil.php?erro=strava_token');
  exit();
}

$data = json_decode($resp, true);

if (empty($data['access_token'])) {
  header('Location: /pages/perfil.php?erro=strava_token');
  exit();
}

$accessToken  = $data['access_token'];
$refreshToken = $data['refresh_token'];
$expiraEm     = $data['expires_at'];  // timestamp unix
$stravaId     = $data['athlete']['id'];

// 4. Busca stats do atleta
$ctxGet = stream_context_create([
  'http' => [
    'method'        => 'GET',
    'header'        => "Authorization: Bearer {$accessToken}\r\nAccept: application/json\r\n",
    'ignore_errors' => true,
    'timeout'       => 15,
  ],
]);

$statsResp = @file_get_contents(
  "https://www.strava.com/api/v3/athletes/{$stravaId}/stats",
  false,
  $ctxGet
);

$kmTotal = 0;
$kmAno   = 0;
$atividadesTotal = 0;

if ($statsResp) {
  $stats = json_decode($statsResp, true);
  // Converte metros para km
  $kmTotal         = round(($stats['all_run_totals']['distance'] ?? 0) / 1000, 2);
  $kmAno           = round(($stats['ytd_run_totals']['distance'] ?? 0) / 1000, 2);
  $atividadesTotal = $stats['all_run_totals']['count'] ?? 0;
}

// 5. Salva no banco
$stmt = $pdo->prepare("
  UPDATE usuarios SET
    strava_id              = ?,
    strava_access_token    = ?,
    strava_refresh_token   = ?,
    strava_token_expira    = ?,
    strava_km_total        = ?,
    strava_km_ano          = ?,
    strava_atividades_total = ?,
    strava_conectado       = true,
    strava_sincronizado_em = NOW()
  WHERE id = ?
");
$stmt->execute([
  $stravaId,
  $accessToken,
  $refreshToken,
  $expiraEm,
  $kmTotal,
  $kmAno,
  $atividadesTotal,
  $_SESSION['id'],
]);

header('Location: /pages/perfil.php?msg=strava_conectado');
exit();
