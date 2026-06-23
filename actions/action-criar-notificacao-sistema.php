<?php
// ==========================================================
// STRIVELY — actions/action-criar-notificacao-sistema.php
// Cria um novo aviso do sistema (admin only — id == 2)
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

// Só aceita POST e usuário logado
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: /pages/configuracoes.php?secao=notificacoes_sistema');
  exit();
}

// CHECAGEM DE ADMIN — server-side, independente do front-end
if ((int)$_SESSION['id'] !== 2) {
  http_response_code(403);
  header('Location: /pages/configuracoes.php?secao=notificacoes_sistema&erro=nao_autorizado');
  exit();
}

$titulo   = trim($_POST['titulo']   ?? '');
$mensagem = trim($_POST['mensagem'] ?? '');

if (empty($titulo) || empty($mensagem)) {
  header('Location: /pages/configuracoes.php?secao=notificacoes_sistema&erro=campos_vazios');
  exit();
}

try {
  $stmt = $pdo->prepare("
    INSERT INTO notificacoes_sistema (titulo, mensagem, criado_por)
    VALUES (?, ?, ?)
  ");
  $stmt->execute([$titulo, $mensagem, $_SESSION['id']]);

  header('Location: /pages/configuracoes.php?secao=notificacoes_sistema&msg=criado');
  exit();
} catch (PDOException $e) {
  error_log("Erro ao criar notificacao_sistema: " . $e->getMessage());
  header('Location: /pages/configuracoes.php?secao=notificacoes_sistema&erro=falha_criacao');
  exit();
}
