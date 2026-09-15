<?php
// ==========================================================
// STRIVELY — actions/action-cadastro.php
// Processa o formulário de cadastro
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

// Só aceita requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../pages/cadastro.php');
  exit();
}

// Pega e limpa os dados do formulário
$nome                    = trim($_POST['nome']                    ?? '');
$email                   = trim($_POST['email']                   ?? '');
$cidade_manual           = trim($_POST['cidade_manual']           ?? '');
$cidade                  = !empty($cidade_manual) ? $cidade_manual : trim($_POST['cidade'] ?? '');
$lat                     = isset($_POST['lat']) && is_numeric($_POST['lat']) ? (float)$_POST['lat'] : null;
$lng                     = isset($_POST['lng']) && is_numeric($_POST['lng']) ? (float)$_POST['lng'] : null;
$compartilha_localizacao = !empty($_POST['compartilha_localizacao']);
$senha                   = $_POST['senha']                        ?? '';
$senha_confirma          = $_POST['senha_confirma']               ?? '';
$aceitar_termos          = $_POST['aceitar_termos']               ?? '';

// Valida campos obrigatórios
if (empty($nome) || empty($email) || empty($senha)) {
  header('Location: ../pages/cadastro.php?erro=campos_vazios');
  exit();
}

// Verifica se tem número no nome
if (preg_match('/\d/', $nome)) {
  header('Location: ../pages/cadastro.php?erro=nome_invalido');
  exit();
}

// Verifica formato do e-mail
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header('Location: ../pages/cadastro.php?erro=email_invalido');
  exit();
}

// Valida aceite dos Termos de Uso
if ($aceitar_termos !== '1') {
  header('Location: ../pages/cadastro.php?erro=termos_nao_aceitos');
  exit();
}

// Valida tamanho da senha (mínimo 8 caracteres)
if (strlen($senha) < 8) {
  header('Location: ../pages/cadastro.php?erro=senha_curta');
  exit();
}

// Valida se a senha contém pelo menos um número
if (!preg_match('/\d/', $senha)) {
  header('Location: ../pages/cadastro.php?erro=senha_sem_numero');
  exit();
}

// Valida se as senhas coincidem
if ($senha !== $senha_confirma) {
  header('Location: ../pages/cadastro.php?erro=senha_diferente');
  exit();
}

// Verifica se o email já está cadastrado
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
  header('Location: ../pages/cadastro.php?erro=email_existente');
  exit();
}

// Gera o hash da senha — nunca salva senha pura no banco
$senhaHash = password_hash($senha, PASSWORD_BCRYPT);

// Se usuário digitou cidade manualmente e não tem lat/lng, geocodifica
if (($lat === null || $lng === null) && !empty($cidade_manual)) {
  require_once __DIR__ . '/../scripts/scraper-eventos.php';
  [$geoLat, $geoLng] = geocodificarCidade($cidade_manual);
  if ($geoLat !== null && $geoLng !== null) {
    $lat = $geoLat;
    $lng = $geoLng;
  }
}

// Insere o novo usuário no banco
$stmt = $pdo->prepare("
  INSERT INTO usuarios (nome, email, cidade, senha, perfil, status, lat, lng, cidade_manual, compartilha_localizacao)
  VALUES (?, ?, ?, ?, 'corredor', 'ativo', ?, ?, ?, ?)
");
$stmt->execute([
  $nome,
  $email,
  $cidade,
  $senhaHash,
  $lat,
  $lng,
  !empty($cidade_manual) ? $cidade_manual : null,
  $compartilha_localizacao ? 'true' : 'false'
]);

// Redireciona para login com mensagem de sucesso
header('Location: ../pages/login.php?msg=cadastrado');
exit();