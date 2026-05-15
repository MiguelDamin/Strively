<?php
// ==========================================================
// STRIVELY — actions/action-strava-webhook.php
// Endpoint que recebe eventos push do Strava Webhooks
// ==========================================================

require_once '../config/conexao.php';

// Helper: processa uma atividade e cria/atualiza treino + post
function processarAtividadeStrava(PDO $pdo, int $usuarioId, array $act): void
{
    $tipoMap = [
        'Run'        => 'Corrida',
        'TrailRun'   => 'Trail Run',
        'VirtualRun' => 'Corrida Virtual',
    ];
    $tiposCorreda = array_keys($tipoMap);

    $tipoAtivStr = $act['type'] ?? $act['sport_type'] ?? '';
    if (!in_array($tipoAtivStr, $tiposCorreda)) return;

    $activityId  = (int)($act['id'] ?? 0);
    $dataTreino  = date('Y-m-d', strtotime($act['start_date_local']));
    $km          = round(($act['distance'] ?? 0) / 1000, 2);
    $kmFormatado = number_format($km, 2, '.', '') . 'km';
    $tipo        = $tipoMap[$tipoAtivStr] ?? 'Corrida';
    // Formato: "Corrida — 5.20km" (badge no calendário lê o tipo pelo split em ' — ')
    $titulo      = "{$tipo} — {$kmFormatado}";
    $descricao   = "{$tipo} {$kmFormatado} no Strava.";

    // Verificar duplicata por strava_activity_id
    $stmtVerifica = $pdo->prepare("SELECT id FROM treinos WHERE strava_activity_id = ?");
    $stmtVerifica->execute([$activityId]);
    if ($stmtVerifica->fetch()) return;

    // Verificar se já há treino do treinador nessa data
    $stmtTreino = $pdo->prepare("
        SELECT id FROM treinos
        WHERE aluno_id = ?
          AND data_treino = ?
          AND treinador_id IS NOT NULL
        LIMIT 1
    ");
    $stmtTreino->execute([$usuarioId, $dataTreino]);
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
        $stmtIn->execute([$usuarioId, $titulo, $descricao, $dataTreino, $tipo, $activityId]);
        $novoTreinoId = $pdo->lastInsertId();

        // Criar post automático na comunidade
        $stmtPost = $pdo->prepare("
            INSERT INTO posts (usuario_id, tipo, titulo, descricao, treino_id, criado_em)
            VALUES (?, 'treino', ?, ?, ?, NOW())
        ");
        $stmtPost->execute([$usuarioId, $titulo, $descricao, $novoTreinoId]);
    }
}

// 1. Verificação do Webhook (Strava envia GET com hub.challenge na criação da subscription)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $hubChallenge = $_GET['hub.challenge'] ?? '';
    header('Content-Type: application/json');
    echo json_encode(['hub.challenge' => $hubChallenge]);
    exit();
}

// 2. Processamento dos Eventos (Strava notifica via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonBase = file_get_contents('php://input');
    $evento   = json_decode($jsonBase, true);

    // Sempre responder 200 OK rapidamente pro Strava
    http_response_code(200);
    echo "OK";

    if (!$evento) exit();

    $object_type = $evento['object_type'] ?? '';
    $aspect_type = $evento['aspect_type'] ?? '';
    $owner_id    = $evento['owner_id']    ?? '';

    if ($object_type === 'activity' && ($aspect_type === 'create' || $aspect_type === 'update')) {
        $activityId = $evento['object_id'] ?? '';

        // Busca o usuário no banco pelo Strava ID
        $stmt = $pdo->prepare("
            SELECT id, strava_access_token, strava_refresh_token, strava_token_expira
            FROM usuarios WHERE strava_id = ? AND strava_conectado = true
        ");
        $stmt->execute([$owner_id]);
        $user = $stmt->fetch();

        if (!$user) exit();

        $accessToken = $user['strava_access_token'];

        // Renova o token caso expirado
        if (time() > (int)$user['strava_token_expira']) {
            $payload = json_encode([
                'client_id'     => $_ENV['STRAVA_CLIENT_ID'],
                'client_secret' => $_ENV['STRAVA_CLIENT_SECRET'],
                'refresh_token' => $user['strava_refresh_token'],
                'grant_type'    => 'refresh_token',
            ]);
            $ctx  = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $payload, 'ignore_errors' => true, 'timeout' => 15]]);
            $resp = @file_get_contents('https://www.strava.com/oauth/token', false, $ctx);
            $data = json_decode((string)$resp, true);

            if (!empty($data['access_token'])) {
                $accessToken = $data['access_token'];
                $pdo->prepare("UPDATE usuarios SET strava_access_token=?, strava_refresh_token=?, strava_token_expira=? WHERE id=?")
                    ->execute([$accessToken, $data['refresh_token'], $data['expires_at'], $user['id']]);
            } else {
                exit();
            }
        }

        $ctxAuth = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'header'        => "Authorization: Bearer {$accessToken}\r\nAccept: application/json\r\n",
                'ignore_errors' => true,
                'timeout'       => 15,
            ],
        ]);

        // Busca dados específicos da atividade e cria treino
        if ($aspect_type === 'create') {
            $actResp = @file_get_contents("https://www.strava.com/api/v3/activities/{$activityId}", false, $ctxAuth);
            if ($actResp) {
                $act = json_decode($actResp, true);
                if (is_array($act)) {
                    processarAtividadeStrava($pdo, (int)$user['id'], $act);
                }
            }
        }

        // Atualiza stats totais do atleta
        $statsResp = @file_get_contents("https://www.strava.com/api/v3/athletes/{$owner_id}/stats", false, $ctxAuth);
        if ($statsResp) {
            $stats           = json_decode($statsResp, true);
            $kmTotal         = round(($stats['all_run_totals']['distance'] ?? 0) / 1000, 2);
            $kmAno           = round(($stats['ytd_run_totals']['distance'] ?? 0) / 1000, 2);
            $atividadesTotal = $stats['all_run_totals']['count'] ?? 0;

            $pdo->prepare("
                UPDATE usuarios SET
                    strava_km_total         = ?,
                    strava_km_ano           = ?,
                    strava_atividades_total = ?,
                    strava_sincronizado_em  = NOW()
                WHERE id = ?
            ")->execute([$kmTotal, $kmAno, $atividadesTotal, $user['id']]);
        }
    }
}
