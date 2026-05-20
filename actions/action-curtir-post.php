<?php
require_once '../config/conexao.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

$post_id = $_POST['post_id'] ?? null;
$usuario_id = $_SESSION['id'];

if (!$post_id) {
    echo json_encode(['error' => 'ID inválido']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id FROM post_curtidas WHERE post_id = ? AND usuario_id = ?");
    $stmt->execute([$post_id, $usuario_id]);
    $curtiu = $stmt->fetch();
    
    if ($curtiu) {
        $pdo->prepare("DELETE FROM post_curtidas WHERE id = ?")->execute([$curtiu['id']]);
        $status = false;
    } else {
        $pdo->prepare("INSERT INTO post_curtidas (post_id, usuario_id, criado_em) VALUES (?, ?, NOW())")->execute([$post_id, $usuario_id]);
        $status = true;
        
        $stmtDono = $pdo->prepare("SELECT usuario_id FROM posts WHERE id = ?");
        $stmtDono->execute([$post_id]);
        $dono = $stmtDono->fetchColumn();
        
        if ($dono && $dono !== $usuario_id) {
            $primeiroNome = explode(' ', $_SESSION['nome'])[0];
            $textoAviso = $primeiroNome . " curtiu seu post 💪";
            $linkAviso = "/pages/comunidade.php#post-" . $post_id;
            try {
                $stmtNotif = $pdo->prepare("INSERT INTO notificacoes (usuario_id, texto, link, lida, remetente_id) VALUES (?, ?, ?, false, ?)");
                $stmtNotif->execute([$dono, $textoAviso, $linkAviso, $usuario_id]);
            } catch(Exception $e) {
                // error_log("Failed to insert notification: " . $e->getMessage());
            }
        }
    }
    
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM post_curtidas WHERE post_id = ?");
    $stmtCount->execute([$post_id]);
    $total = $stmtCount->fetchColumn();
    
    echo json_encode(['curtido' => $status, 'total' => $total]);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Erro interno']);
}
