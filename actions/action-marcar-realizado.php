<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: /pages/treinos.php');
  exit();
}

$treino_id = (int)($_POST['treino_id'] ?? 0);
$aba       = $_POST['aba'] ?? 'calendario';

if (!$treino_id) {
  header("Location: /pages/treinos.php?aba={$aba}");
  exit();
}

// Atualiza somente se o treino pertence ao aluno logado
$stmt = $pdo->prepare("SELECT status FROM treinos WHERE id = ? AND aluno_id = ?");
$stmt->execute([$treino_id, $_SESSION['id']]);
$treino = $stmt->fetch();

$novoStatus = 'pendente';
if ($treino) {
  $novoStatus = ($treino['status'] === 'realizado') ? 'pendente' : 'realizado';
  $stmt = $pdo->prepare("UPDATE treinos SET status = ? WHERE id = ? AND aluno_id = ?");
  $stmt->execute([$novoStatus, $treino_id, $_SESSION['id']]);

  // Feed de Comunidade - Gatilhos automáticos
  if ($novoStatus === 'realizado') {
      $stmtT = $pdo->prepare("SELECT t.*, u.nome as nome_treinador, u.foto as foto_treinador FROM treinos t LEFT JOIN usuarios u ON t.treinador_id = u.id WHERE t.id = ?");
      $stmtT->execute([$treino_id]);
      $tr = $stmtT->fetch();
      if ($tr) {
          $primeiroNome = explode(' ', $_SESSION['nome'])[0];
          $tituloTreino = $primeiroNome . " concluiu o treino: " . $tr['titulo'];
          $stmtP = $pdo->prepare("INSERT INTO posts (usuario_id, tipo, titulo, descricao, treino_id, criado_em) VALUES (?, 'treino', ?, ?, ?, NOW())");
          $stmtP->execute([$_SESSION['id'], $tituloTreino, $tr['descricao'], $treino_id]);
      }
  } else {
      $stmtD = $pdo->prepare("DELETE FROM posts WHERE treino_id = ? AND usuario_id = ? AND tipo = 'treino'");
      $stmtD->execute([$treino_id, $_SESSION['id']]);
  }
}

$msg = ($novoStatus === 'realizado') ? 'realizado' : 'desmarcado';

header("Location: /pages/treinos.php?aba={$aba}&msg={$msg}");
exit();
