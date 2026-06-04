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

    $respostaAtiv = @file_get_contents(
        "https://www.strava.com/api/v3/athlete/activities?per_page=30&page=1",
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

            // VERIFICAR DUPLICATA por strava_activity_id
            $stmtDup = $pdo->prepare("SELECT id FROM treinos WHERE strava_activity_id = ?");
            $stmtDup->execute([$stravaId]);
            if ($stmtDup->fetch()) continue; // já importado

            $treinoIdFinal = null;

            // VERIFICAR se treinador setou treino nesse dia ainda não realizado
            $stmtTreinador = $pdo->prepare("
                SELECT id FROM treinos 
                WHERE aluno_id = ? 
                AND data_treino = ? 
                AND treinador_id IS NOT NULL 
                AND status != 'realizado'
                LIMIT 1
            ");
            $stmtTreinador->execute([$usuarioId, $dataLocal]);
            $treinoDoTreinador = $stmtTreinador->fetch();

            if ($treinoDoTreinador) {
                // Marcar treino do treinador como realizado
                $pdo->prepare("
                    UPDATE treinos 
                    SET status = 'realizado', strava_activity_id = ?
                    WHERE id = ?
                ")->execute([$stravaId, $treinoDoTreinador['id']]);
                $treinoIdFinal = $treinoDoTreinador['id'];

            } else {
                // Verificar treino próprio não realizado nesse dia
                $stmtProprio = $pdo->prepare("
                    SELECT id FROM treinos 
                    WHERE aluno_id = ? 
                    AND data_treino = ? 
                    AND treinador_id IS NULL
                    AND tipo IN ('unico', 'planilha')
                    AND status != 'realizado'
                    LIMIT 1
                ");
                $stmtProprio->execute([$usuarioId, $dataLocal]);
                $treinoProprio = $stmtProprio->fetch();

                if ($treinoProprio) {
                    // Marcar treino próprio como realizado
                    $pdo->prepare("
                        UPDATE treinos 
                        SET status = 'realizado', strava_activity_id = ?
                        WHERE id = ?
                    ")->execute([$stravaId, $treinoProprio['id']]);
                    $treinoIdFinal = $treinoProprio['id'];

                } else {
                    // Criar novo treino tipo 'strava'
                    $stmtCria = $pdo->prepare("
                        INSERT INTO treinos 
                        (aluno_id, treinador_id, titulo, descricao, data_treino, tipo, status, strava_activity_id)
                        VALUES (?, NULL, ?, ?, ?, 'strava', 'realizado', ?)
                    ");
                    $stmtCria->execute([$usuarioId, $titulo, $descricao, $dataLocal, $stravaId]);
                    $treinoIdFinal = $pdo->lastInsertId();
                }
            }

            // Post automático removido — usuário pode compartilhar manualmente via botão "Compartilhar"
        }
    }
} catch (Exception $e) {
    error_log("Erro ao importar atividades Strava: " . $e->getMessage());
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

header('Location: /pages/perfil.php?msg=strava_sincronizado');
exit();
