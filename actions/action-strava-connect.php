<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

$clientId    = $_ENV['STRAVA_CLIENT_ID'];
$redirectUri = $_ENV['STRAVA_REDIRECT_URI'];
$scope       = 'read,activity:read_all,profile:read_all';
$state       = bin2hex(random_bytes(16)); // proteção CSRF

// Salva o state no banco (sessão PHP pode ser perdida no Render entre requests)
try {
  // Cria a tabela se não existir
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS strava_states (
      state       VARCHAR(64) PRIMARY KEY,
      usuario_id  INTEGER NOT NULL,
      criado_em   TIMESTAMP DEFAULT NOW()
    )
  ");
  // Limpa states antigos (> 10 minutos)
  $pdo->exec("DELETE FROM strava_states WHERE criado_em < NOW() - INTERVAL '10 minutes'");
  // Salva o novo state
  $stmt = $pdo->prepare("INSERT INTO strava_states (state, usuario_id) VALUES (?, ?)");
  $stmt->execute([$state, $_SESSION['id']]);
} catch (Exception $e) {
  // fallback: guarda na sessão mesmo
  $_SESSION['strava_state'] = $state;
}

$encodedRedirect = urlencode($redirectUri);

$queryStr = "client_id={$clientId}"
  . "&redirect_uri={$encodedRedirect}"
  . "&response_type=code"
  . "&approval_prompt=auto"
  . "&scope={$scope}"
  . "&state={$state}";

$webUrl = "https://www.strava.com/oauth/authorize?{$queryStr}";

header("Location: {$webUrl}");
exit();
