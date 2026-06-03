<?php
require_once __DIR__ . '/../config/conexao.php';
session_start();

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$meu_id = $_SESSION['id'];
$seguido_id = $_POST['alvo_id'] ?? 0;
$seguido_id = (int)$seguido_id;

if (!$seguido_id || $seguido_id === $meu_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

try {
    // Verifica se já segue
    $stmt = $pdo->prepare("SELECT 1 FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?");
    $stmt->execute([$meu_id, $seguido_id]);
    $ja_segue = $stmt->fetch();

    if ($ja_segue) {
        // Deixar de seguir
        $pdo->prepare("DELETE FROM seguidores WHERE seguidor_id = ? AND seguido_id = ?")->execute([$meu_id, $seguido_id]);
        
        // Remove também a notificação para que não fique suja, caso tenham dado unfollow (opcional)
        // $pdo->prepare("DELETE FROM notificacoes WHERE remetente_id = ? AND usuario_id = ? AND texto LIKE '%perseguir%'")->execute([$meu_id, $seguido_id]);
        
        echo json_encode(['status' => 'unfollowed']);
    } else {
        // Seguir
        $pdo->prepare("INSERT INTO seguidores (seguidor_id, seguido_id) VALUES (?, ?)")->execute([$meu_id, $seguido_id]);
        
        // Disparar notificação
        $meu_nome = explode(' ', $_SESSION['nome'])[0];
        $texto_notif = "{$meu_nome} começou a perseguir você";
        $link_notif = "/pages/perfil-publico.php?id=" . $meu_id;
        
        // Verifica se já enviou uma notificação muito similar recente para não spammar se der follow/unfollow rápido
        $stmt_check_notif = $pdo->prepare("SELECT id FROM notificacoes WHERE usuario_id = ? AND remetente_id = ? AND texto = ? LIMIT 1");
        $stmt_check_notif->execute([$seguido_id, $meu_id, $texto_notif]);
        
        if (!$stmt_check_notif->fetch()) {
            $stmt_notif = $pdo->prepare("INSERT INTO notificacoes (usuario_id, texto, link, remetente_id) VALUES (?, ?, ?, ?)");
            $stmt_notif->execute([$seguido_id, $texto_notif, $link_notif, $meu_id]);
        }

        echo json_encode(['status' => 'followed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno banco de dados']);
}
