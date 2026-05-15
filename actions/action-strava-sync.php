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

// 3. Busca atividades recentes e cria/atualiza treinos no calendário
$actResp = @file_get_contents(
  'https://www.strava.com/api/v3/athlete/activities?per_page=15',
  false,
  $ctx
);

if ($actResp) {
    $activities = json_decode($actResp, true);

    // Mapeamento de tipo Strava → tipo legível
    $tipoMap = [
        'Run'        => 'Corrida',
        'TrailRun'   => 'Trail Run',
        'VirtualRun' => 'Corrida Virtual',
    ];
    $tiposCorreda = array_keys($tipoMap);

    if (is_array($activities)) {
        foreach ($activities as $act) {
            $tipoAtivStr = $act['type'] ?? $act['sport_type'] ?? '';

            // Só processar atividades de corrida
            if (!in_array($tipoAtivStr, $tiposCorreda)) continue;

            $activityId  = (int)($act['id'] ?? 0);
            $dataTreino  = date('Y-m-d', strtotime($act['start_date_local']));
            $km          = round(($act['distance'] ?? 0) / 1000, 2);
            $kmFormatado = number_format($km, 2, '.', '') . 'km';
            $tipo        = $tipoMap[$tipoAtivStr] ?? 'Corrida';
            // Formato: "Corrida — 5.20km" (badge no calendário lê o tipo pelo split em ' — ')
            $titulo      = "{$tipo} — {$kmFormatado}";
            $descricao   = "{$tipo} {$kmFormatado} no Strava.";

            // Verificar duplicata por strava_activity_id (evita re-inserção em syncs repetidos)
            $stmtVerifica = $pdo->prepare("
                SELECT id FROM treinos WHERE strava_activity_id = ?
            ");
            $stmtVerifica->execute([$activityId]);
            if ($stmtVerifica->fetch()) continue;

            // Verificar se já há treino do treinador nessa data
            $stmtTreino = $pdo->prepare("
                SELECT id FROM treinos
                WHERE aluno_id = ?
                  AND data_treino = ?
                  AND treinador_id IS NOT NULL
                LIMIT 1
            ");
            $stmtTreino->execute([$_SESSION['id'], $dataTreino]);
            $treinoExistente = $stmtTreino->fetch();

            if ($treinoExistente) {
                // Marcar treino do treinador como realizado
                $pdo->prepare("UPDATE treinos SET status = 'realizado', strava_activity_id = ? WHERE id = ?")
                    ->execute([$activityId, $treinoExistente['id']]);
            } else {
                // Criar novo treino automático do Strava
                $stmtIn = $pdo->prepare("
                    INSERT INTO treinos (aluno_id, treinador_id, titulo, descricao, data_treino, tipo, status, strava_activity_id)
                    VALUES (?, NULL, ?, ?, ?, ?, 'realizado', ?)
                ");
                $stmtIn->execute([$_SESSION['id'], $titulo, $descricao, $dataTreino, $tipo, $activityId]);
                $novoTreinoId = $pdo->lastInsertId();

                // Criar post automático na comunidade
                $stmtPost = $pdo->prepare("
                    INSERT INTO posts (usuario_id, tipo, titulo, descricao, treino_id, criado_em)
                    VALUES (?, 'treino', ?, ?, ?, NOW())
                ");
                $stmtPost->execute([$_SESSION['id'], $titulo, $descricao, $novoTreinoId]);
            }
        }
    }
}

header('Location: /pages/perfil.php?msg=strava_sincronizado');
exit();
