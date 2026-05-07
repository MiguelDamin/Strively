<?php
// ==========================================================
// STRIVELY — actions/action-strava-webhook.php
// Endpoint que recebe eventos push do Strava Webhooks
// ==========================================================

require_once '../config/conexao.php';

// 1. Verificação do Webhook (Strava envia GET com hub.challenge na criação da subscription)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $hubChallenge = $_GET['hub.challenge'] ?? '';
    $hubVerifyToken = $_GET['hub.verify_token'] ?? '';
    
    // Podemos verificar o hubVerifyToken se tivermos definido ao criar a subscription,
    // Mas o mais importante é responder o hub.challenge
    header('Content-Type: application/json');
    echo json_encode(['hub.challenge' => $hubChallenge]);
    exit();
}

// 2. Processamento dos Eventos (Strava notifica via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lê o body do request
    $jsonBase = file_get_contents('php://input');
    $evento = json_decode($jsonBase, true);
    
    // Sempre responder 200 OK rapidamente pro Strava até uns 2 segs
    http_response_code(200);
    echo "OK";
    
    if (!$evento) exit();

    $object_type = $evento['object_type'] ?? ''; // ex: 'activity' ou 'athlete'
    $aspect_type = $evento['aspect_type'] ?? ''; // ex: 'create', 'update', 'delete'
    $owner_id    = $evento['owner_id'] ?? '';    // strava_id do atleta

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
            $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $payload]]);
            $resp = @file_get_contents('https://www.strava.com/oauth/token', false, $ctx);
            $data = json_decode((string)$resp, true);
            
            if (!empty($data['access_token'])) {
                $accessToken = $data['access_token'];
                $stmtUpd = $pdo->prepare("UPDATE usuarios SET strava_access_token=?, strava_refresh_token=?, strava_token_expira=? WHERE id=?");
                $stmtUpd->execute([$accessToken, $data['refresh_token'], $data['expires_at'], $user['id']]);
            } else {
                exit();
            }
        }
        
        $ctxAuth = stream_context_create(['http' => ['method' => 'GET', 'header' => "Authorization: Bearer {$accessToken}\r\nAccept: application/json\r\n"]]);
        
        // Busca dados ESPECÍFICOS dessa atividade para colocar no calendário
        if ($aspect_type === 'create') {
            $actResp = @file_get_contents("https://www.strava.com/api/v3/activities/{$activityId}", false, $ctxAuth);
            if ($actResp) {
                $act = json_decode($actResp, true);
                $tipoAtiv = $act['type'] ?? $act['sport_type'] ?? 'Run';
                $dataTreino = date('Y-m-d', strtotime($act['start_date_local']));
                $distanciaKm = round(($act['distance'] ?? 0) / 1000, 2);
                $distDesc = "Distância percorrida: {$distanciaKm} km";

                // Integra com o treinos do banco
                $stmtCheck = $pdo->prepare("SELECT id, descricao FROM treinos WHERE aluno_id = ? AND data_treino = ? LIMIT 1");
                $stmtCheck->execute([$user['id'], $dataTreino]);
                $treinoExistente = $stmtCheck->fetch();

                if ($treinoExistente) {
                    $novaDesc = $treinoExistente['descricao'];
                    if (strpos($novaDesc, 'Distância percorrida:') === false) {
                        $novaDesc .= "\n\n" . $distDesc . " (Strava Webhook)";
                        $stmtTrein = $pdo->prepare("UPDATE treinos SET status = 'realizado', descricao = ? WHERE id = ?");
                        $stmtTrein->execute([$novaDesc, $treinoExistente['id']]);
                    }
                } else {
                    $stmtIn = $pdo->prepare("
                        INSERT INTO treinos (aluno_id, treinador_id, titulo, descricao, data_treino, tipo, status)
                        VALUES (?, NULL, 'Corrida via Strava', ?, ?, ?, 'realizado')
                    ");
                    $stmtIn->execute([$user['id'], $distDesc, $dataTreino, $tipoAtiv]);
                }
            }
        }
        
        // Agora busca os STATS TOTAIS do atleta para atualizar os paineis (km total, etc)
        $statsResp = @file_get_contents("https://www.strava.com/api/v3/athletes/{$owner_id}/stats", false, $ctxAuth);
        if ($statsResp) {
            $stats = json_decode($statsResp, true);
            $kmTotal         = round(($stats['all_run_totals']['distance'] ?? 0) / 1000, 2);
            $kmAno           = round(($stats['ytd_run_totals']['distance'] ?? 0) / 1000, 2);
            $atividadesTotal = $stats['all_run_totals']['count'] ?? 0;

            $stmtUpdateStats = $pdo->prepare("
                UPDATE usuarios SET
                    strava_km_total         = ?,
                    strava_km_ano           = ?,
                    strava_atividades_total = ?,
                    strava_sincronizado_em  = NOW()
                WHERE id = ?
            ");
            $stmtUpdateStats->execute([$kmTotal, $kmAno, $atividadesTotal, $user['id']]);
        }
    }
}
