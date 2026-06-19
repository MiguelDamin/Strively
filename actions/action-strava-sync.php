<?php
header('Content-Type: application/json');
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id'])) {
  echo json_encode(['success' => false, 'erro' => 'nao_autenticado']);
  exit();
}

// Busca dados do usuário
$stmt = $pdo->prepare("
  SELECT strava_access_token, strava_refresh_token, strava_token_expira, strava_id, strava_conectado, strava_sincronizado_em
  FROM usuarios WHERE id = ?
");
$stmt->execute([$_SESSION['id']]);
$user = $stmt->fetch();

if (!$user || !$user['strava_conectado']) {
  echo json_encode(['success' => false, 'erro' => 'strava_nao_conectado']);
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
    echo json_encode(['success' => false, 'erro' => 'strava_refresh']);
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
  echo json_encode(['success' => false, 'erro' => 'strava_sync']);
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
$pendentesConfirmacao = [];
$criadosSincronizados = [];

try {
    $usuarioId = $_SESSION['id'];
    
    // ============================================================
    // IMPORTAR ATIVIDADES PARA O CALENDÁRIO
    // ============================================================
    $ctxAtiv = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer {$accessToken}\r\n",
            'ignore_errors' => true,
            'timeout' => 15,
        ],
    ]);

    $modo = $_GET['modo'] ?? 'rotina';
    
    // Se o modo for 30dias puxa 30 atividades, se não puxa as 10 mais recentes
    $perPage = ($modo === '30dias') ? 30 : 10;
    
    $respostaAtiv = @file_get_contents(
        "https://www.strava.com/api/v3/athlete/activities?per_page={$perPage}&page=1",
        false, $ctxAtiv
    );

    $atividades = json_decode($respostaAtiv, true);

    if (is_array($atividades)) {
        foreach ($atividades as $ativ) {

            // Só processar tipos de corrida
            $tipoStrava = $ativ['sport_type'] ?? $ativ['type'] ?? '';
            if (!in_array($tipoStrava, ['Run', 'TrailRun', 'VirtualRun', 'Hike'])) continue;

            $stravaId    = (int)$ativ['id'];
            $distanciaM  = (float)($ativ['distance'] ?? 0);
            $km          = round($distanciaM / 1000, 2);
            $kmTexto     = ($km == floor($km)) 
                           ? number_format($km, 0) . 'KM' 
                           : number_format($km, 2, '.', '') . 'KM';
            $dataLocal   = date('Y-m-d', strtotime($ativ['start_date_local']));
            $titulo      = $kmTexto;         // ex: "5KM" ou "10.50KM"
            $descricao   = '';               // descrição vazia conforme solicitado

            // VERIFICAR DUPLICATA por strava_activity_id (em qualquer tipo de treino)
            $stmtDup = $pdo->prepare("SELECT id FROM treinos WHERE strava_activity_id = ? AND aluno_id = ?");
            $stmtDup->execute([$stravaId, $usuarioId]);
            if ($stmtDup->fetch()) continue; // já importado

            // VERIFICAR TREINO PLANEJADO PENDENTE PARA A MESMA DATA
            $stmtPendente = $pdo->prepare("
                SELECT id, titulo, distancia_planejada_km, treinador_id
                FROM treinos 
                WHERE aluno_id = ? 
                AND data_treino = ? 
                AND status = 'pendente'
                AND tipo NOT IN ('strava', 'evento')
                AND strava_activity_id IS NULL
                LIMIT 1
            ");
            $stmtPendente->execute([$usuarioId, $dataLocal]);
            $treinoPendente = $stmtPendente->fetch();

            if ($treinoPendente) {
                // Planejado -> Pendente Confirmação
                $pendentesConfirmacao[] = [
                    'strava_activity_id'  => $stravaId,
                    'strava_km'           => $km,
                    'strava_data'         => date('d/m/Y', strtotime($dataLocal)),
                    'strava_data_iso'     => $dataLocal,
                    'treino_id'           => $treinoPendente['id'],
                    'treino_titulo'       => $treinoPendente['titulo'],
                    'distancia_planejada' => $treinoPendente['distancia_planejada_km'],
                ];
            } else {
                // Sem planejamento -> Criar novo diretamente
                $stmtCria = $pdo->prepare("
                    INSERT INTO treinos 
                    (aluno_id, treinador_id, titulo, descricao, data_treino, tipo, status, strava_activity_id, km_realizado_strava)
                    VALUES (?, NULL, ?, ?, ?, 'strava', 'realizado', ?, ?)
                ");
                $stmtCria->execute([$usuarioId, $titulo, $descricao, $dataLocal, $stravaId, $km]);
                $criadosSincronizados[] = $pdo->lastInsertId();
            }
        }
    }
} catch (Exception $e) {
    error_log("Erro ao importar atividades Strava: " . $e->getMessage());
    echo json_encode(['success' => false, 'erro' => 'falha_importacao', 'msg' => $e->getMessage()]);
    exit();
}

try {
    // ============================================================
    // VERIFICAR ATIVIDADES DELETADAS NO STRAVA
    // ============================================================

    // 1. Buscar todos os strava_activity_ids importados desse usuário
    $stmtIds = $pdo->prepare("
        SELECT id, strava_activity_id 
        FROM treinos 
        WHERE aluno_id = ? 
        AND tipo = 'strava'
        AND strava_activity_id IS NOT NULL
    ");
    $stmtIds->execute([$usuarioId]);
    $treinosImportados = $stmtIds->fetchAll();

    if (!empty($treinosImportados)) {
        // 2. Para cada treino importado, verificar se ainda existe no Strava
        foreach ($treinosImportados as $treinoLocal) {
            $activityId = $treinoLocal['strava_activity_id'];
            
            $ctxCheck = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Authorization: Bearer {$accessToken}\r\n",
                    'ignore_errors' => true,
                    'timeout' => 10,
                ],
            ]);
            
            $respostaCheck = @file_get_contents(
                "https://www.strava.com/api/v3/activities/{$activityId}",
                false, $ctxCheck
            );
            
            $dadosAtividade = json_decode($respostaCheck, true);
            
            // Se a atividade não existir mais (404) ou retornar erro, deletar do Strively
            $atividadeDeletada = (
                $dadosAtividade === null ||
                isset($dadosAtividade['errors']) ||
                (isset($dadosAtividade['message']) && str_contains(strtolower($dadosAtividade['message'] ?? ''), 'not found'))
            );
            
            if ($atividadeDeletada) {
                $treinoId = $treinoLocal['id'];
                
                // Deletar post do feed associado
                $pdo->prepare("
                    DELETE FROM posts 
                    WHERE treino_id = ? AND usuario_id = ?
                ")->execute([$treinoId, $usuarioId]);
                
                // Deletar o treino do calendário/planilha
                $pdo->prepare("
                    DELETE FROM treinos 
                    WHERE id = ? AND aluno_id = ? AND tipo = 'strava'
                ")->execute([$treinoId, $usuarioId]);
            }
        }
    }
    // ============================================================
} catch (Exception $e) {
    error_log("Erro ao verificar exclusões Strava: " . $e->getMessage());
}

echo json_encode([
    'success' => true, 
    'pendentes' => $pendentesConfirmacao, 
    'criados' => count($criadosSincronizados)
]);
exit();

