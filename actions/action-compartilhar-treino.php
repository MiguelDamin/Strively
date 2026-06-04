<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: /pages/treinos.php');
  exit();
}

$treino_id = (int)($_POST['treino_id'] ?? 0);
$aba = $_POST['aba'] ?? 'calendario';

if (!$treino_id) {
  header("Location: /pages/treinos.php?aba={$aba}");
  exit();
}

// Verifica se o treino existe, pertence ao usuário e está realizado
$stmt = $pdo->prepare("
    SELECT t.*, u.nome as nome_treinador, u.foto as foto_treinador 
    FROM treinos t 
    LEFT JOIN usuarios u ON t.treinador_id = u.id 
    WHERE t.id = ? AND t.aluno_id = ? AND t.status = 'realizado'
");
$stmt->execute([$treino_id, $_SESSION['id']]);
$tr = $stmt->fetch();

if ($tr) {
    // Evita duplicatas caso a pessoa clique muitas vezes
    $stmtCheck = $pdo->prepare("SELECT id FROM posts WHERE treino_id = ? AND usuario_id = ? AND tipo = 'treino'");
    $stmtCheck->execute([$treino_id, $_SESSION['id']]);
    if (!$stmtCheck->fetch()) {
        $primeiroNome = explode(' ', $_SESSION['nome'])[0];
        $tituloTreino = $primeiroNome . " concluiu o treino: " . $tr['titulo'];
        
        $stmtP = $pdo->prepare("INSERT INTO posts (usuario_id, tipo, titulo, descricao, treino_id, criado_em) VALUES (?, 'treino', ?, ?, ?, NOW())");
        $stmtP->execute([$_SESSION['id'], $tituloTreino, $tr['descricao'], $treino_id]);
    }
}

header("Location: /pages/treinos.php?aba={$aba}&msg=compartilhado");
exit();
