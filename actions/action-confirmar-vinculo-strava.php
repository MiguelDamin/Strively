<?php
header('Content-Type: application/json');
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(403);
  echo json_encode(['success' => false, 'erro' => 'nao_autorizado']);
  exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$acao       = $input['acao'] ?? '';
$stravaId   = $input['strava_activity_id'] ?? 0;
$km         = $input['strava_km'] ?? 0;
$dataIso    = $input['strava_data_iso'] ?? date('Y-m-d');
$usuarioId  = $_SESSION['id'];

if (!$stravaId || !in_array($acao, ['vincular', 'separar'])) {
  echo json_encode(['success' => false, 'erro' => 'dados_invalidos']);
  exit();
}

// Verifica se já não importou antes globalmente pra evitar duplo envio
$stmtDup = $pdo->prepare("SELECT id FROM treinos WHERE strava_activity_id = ? AND aluno_id = ?");
$stmtDup->execute([$stravaId, $_SESSION['id']]);
if ($stmtDup->fetch()) {
  echo json_encode(['success' => true]); // Já tá lá
  exit();
}

if ($acao === 'vincular') {
    $treinoId = $input['treino_id'] ?? 0;
    
    $stmtCheck = $pdo->prepare("SELECT id FROM treinos WHERE id = ? AND aluno_id = ? AND status = 'pendente'");
    $stmtCheck->execute([$treinoId, $usuarioId]);
    if (!$stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'erro' => 'treino_nao_encontrado']);
        exit();
    }
    
    $pdo->prepare("
        UPDATE treinos 
        SET status = 'realizado', strava_activity_id = ?, km_realizado_strava = ?
        WHERE id = ?
    ")->execute([$stravaId, $km, $treinoId]);

    echo json_encode(['success' => true, 'treino_id' => $treinoId]);
    exit();
}

if ($acao === 'separar') {
    $kmTexto = ($km == floor($km)) ? number_format($km, 0) . 'KM' : number_format($km, 2, '.', '') . 'KM';
    
    $stmtCria = $pdo->prepare("
        INSERT INTO treinos 
        (aluno_id, treinador_id, titulo, descricao, data_treino, tipo, status, strava_activity_id, km_realizado_strava)
        VALUES (?, NULL, ?, '', ?, 'strava', 'realizado', ?, ?)
    ");
    $stmtCria->execute([$usuarioId, $kmTexto, $dataIso, $stravaId, $km]);
    
    echo json_encode(['success' => true, 'treino_id' => $pdo->lastInsertId()]);
    exit();
}
