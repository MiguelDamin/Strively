<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

// Busca dados do usuário
$stmt = $pdo->prepare("
  SELECT strava_access_token, strava_refresh_token, strava_token_expira, strava_id, strava_conectado
  FROM usuarios WHERE id = ?
");
$stmt->execute([$_SESSION['id']]);
$user = $stmt->fetch();

if (!$user || !$user['strava_conectado']) {
  header('Location: /pages/perfil.php?erro=strava_nao_conectado');
  exit();
}

$accessToken = $user['strava_access_token'];

// 1. Renova token se expirado (tokens duram 6 horas)
if (time() > (int)$user['strava_token_expira']) {
  $payload = json_encode([
    'client_id'     => $_ENV['STRAVA_CLIENT_ID'],
    'client_secret' => $_ENV['STRAVA_CLIENT_SECRET'],
    'refresh_token' => $user['strava_refresh_token'],
    'grant_type'    => 'refresh_token',
  ]);

  $ctx = stream_context_create([
    'http' => [
      'method'        => 'POST',
      'header'        => "Content-Type: application/json\r\n",
      'content'       => $payload,
      'ignore_errors' => true,
      'timeout'       => 15,
    ],
  ]);

  $resp = @file_get_contents('https://www.strava.com/oauth/token', false, $ctx);
  $data = json_decode($resp, true);

  if (empty($data['access_token'])) {
    header('Location: /pages/perfil.php?erro=strava_refresh');
    exit();
  }

  $accessToken = $data['access_token'];

  $stmt = $pdo->prepare("
    UPDATE usuarios SET
      strava_access_token  = ?,
      strava_refresh_token = ?,
      strava_token_expira  = ?
    WHERE id = ?
  ");
  $stmt->execute([
    $data['access_token'],
    $data['refresh_token'],
    $data['expires_at'],
    $_SESSION['id'],
  ]);
}

// 2. Busca stats atualizados
$stravaId = $user['strava_id'];

$ctx = stream_context_create([
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
  $ctx
);

if (!$statsResp) {
  header('Location: /pages/perfil.php?erro=strava_sync');
  exit();
}

$stats           = json_decode($statsResp, true);
$kmTotal         = round(($stats['all_run_totals']['distance'] ?? 0) / 1000, 2);
$kmAno           = round(($stats['ytd_run_totals']['distance'] ?? 0) / 1000, 2);
$atividadesTotal = $stats['all_run_totals']['count'] ?? 0;

$stmt = $pdo->prepare("
  UPDATE usuarios SET
    strava_km_total         = ?,
    strava_km_ano           = ?,
    strava_atividades_total = ?,
    strava_sincronizado_em  = NOW()
  WHERE id = ?
");
$stmt->execute([$kmTotal, $kmAno, $atividadesTotal, $_SESSION['id']]);

header('Location: /pages/perfil.php?msg=strava_sincronizado');
exit();
