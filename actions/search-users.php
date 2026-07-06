<?php
require_once __DIR__ . '/../config/conexao.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode([]);
    exit;
}

$q = $_GET['q'] ?? '';
$q = trim($q);

if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

try {
    // Search for users matching the query
    // LIKE query% to match only start of the name
    $stmt = $pdo->prepare("
        SELECT id, nome, foto, perfil 
        FROM usuarios 
        WHERE nome ILIKE ? 
        ORDER BY nome ASC 
        LIMIT 10
    ");
    $stmt->execute([
        $q . '%'
    ]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);
} catch (Exception $e) {
    echo json_encode([]);
}
