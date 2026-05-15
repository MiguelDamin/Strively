<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

// 1. Valida state CSRF — primeiro busca no banco (mais confiável no Render),
//    depois verifica sessão como fallback
$stateRecebido = $_GET['state'] ?? '';
$usuarioId     = $_SESSION['id'];
$stateValido   = false;

try {
  $stmtState = $pdo->prepare(
    "DELETE FROM strava_states WHERE state = ? AND usuario_id = ? AND criado_em > NOW() - INTERVAL '10 minutes' RETURNING state"
  );
  $stmtState->execute([$stateRecebido, $usuarioId]);
  $deletado = $stmtState->fetch();
  $stateValido = !empty($deletado);
} catch (Exception $e) {
  // Tabela pode nao existir — usa fallback de sessao
  $stateValido = ($stateRecebido === ($_SESSION['strava_state'] ?? ''));
}

unset($_SESSION['strava_state']);

if (!$stateValido) {
  header('Location: /pages/perfil.php?erro=strava_state');
  exit();
}

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

// 6. Importa as últimas atividades de corrida para o calendário
$ctxAct = stream_context_create([
  'http' => [
    'method'        => 'GET',
    'header'        => "Authorization: Bearer {$accessToken}\r\nAccept: application/json\r\n",
    'ignore_errors' => true,
    'timeout'       => 15,
  ],
]);
$actResp = @file_get_contents(
  'https://www.strava.com/api/v3/athlete/activities?per_page=10',
  false,
  $ctxAct
);
if ($actResp) {
    $activities = json_decode($actResp, true);
    $tipoMap = [
        'Run'        => 'Corrida',
        'TrailRun'   => 'Trail Run',
        'VirtualRun' => 'Corrida Virtual',
    ];
    $tiposCorreda = array_keys($tipoMap);

    if (is_array($activities)) {
        foreach ($activities as $act) {
            $tipoAtivStr = $act['type'] ?? $act['sport_type'] ?? '';
            if (!in_array($tipoAtivStr, $tiposCorreda)) continue;

            $activityId  = (int)($act['id'] ?? 0);
            $dataTreino  = date('Y-m-d', strtotime($act['start_date_local']));
            $km          = round(($act['distance'] ?? 0) / 1000, 2);
            $kmFormatado = number_format($km, 2, '.', '') . 'km';
            $tipo        = $tipoMap[$tipoAtivStr] ?? 'Corrida';
            $titulo      = "{$tipo} — {$kmFormatado}";
            $descricao   = "{$tipo} {$kmFormatado} no Strava.";

            // Evitar duplicatas por strava_activity_id
            $stmtVerifica = $pdo->prepare("SELECT id FROM treinos WHERE strava_activity_id = ?");
            $stmtVerifica->execute([$activityId]);
            if ($stmtVerifica->fetch()) continue;

            // Verificar treino do treinador na data
            $stmtTreino = $pdo->prepare("
                SELECT id FROM treinos
                WHERE aluno_id = ? AND data_treino = ? AND treinador_id IS NOT NULL
                LIMIT 1
            ");
            $stmtTreino->execute([$_SESSION['id'], $dataTreino]);
            $treinoExistente = $stmtTreino->fetch();

            if ($treinoExistente) {
                $pdo->prepare("UPDATE treinos SET status = 'realizado', strava_activity_id = ? WHERE id = ?")
                    ->execute([$activityId, $treinoExistente['id']]);
            } else {
                $stmtIn = $pdo->prepare("
                    INSERT INTO treinos (aluno_id, treinador_id, titulo, descricao, data_treino, tipo, status, strava_activity_id)
                    VALUES (?, NULL, ?, ?, ?, ?, 'realizado', ?)
                ");
                $stmtIn->execute([$_SESSION['id'], $titulo, $descricao, $dataTreino, $tipo, $activityId]);
                $novoTreinoId = $pdo->lastInsertId();

                $stmtPost = $pdo->prepare("
                    INSERT INTO posts (usuario_id, tipo, titulo, descricao, treino_id, criado_em)
                    VALUES (?, 'treino', ?, ?, ?, NOW())
                ");
                $stmtPost->execute([$_SESSION['id'], $titulo, $descricao, $novoTreinoId]);
            }
        }
    }
}

header('Location: /pages/perfil.php?msg=strava_conectado');
exit();
