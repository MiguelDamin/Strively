<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

$stmt = $pdo->prepare("
  UPDATE usuarios SET
    strava_id               = NULL,
    strava_access_token     = NULL,
    strava_refresh_token    = NULL,
    strava_token_expira     = NULL,
    strava_km_total         = 0,
    strava_km_ano           = 0,
    strava_atividades_total = 0,
    strava_conectado        = false,
    strava_sincronizado_em  = NULL
  WHERE id = ?
");
$stmt->execute([$_SESSION['id']]);

header('Location: /pages/perfil.php?msg=strava_desconectado');
exit();
