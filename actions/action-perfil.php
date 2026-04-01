<?php
// ==========================================================
// STRIVELY — actions/action-perfil.php
// Atualiza dados e foto do perfil no banco
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

// Só aceita POST e usuário logado
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: /Strively/pages/perfil.php');
  exit();
}

$id     = $_SESSION['id'];
$nome   = trim($_POST['nome']   ?? '');
$cidade = trim($_POST['cidade'] ?? '');

// Valida nome obrigatório
if (empty($nome)) {
  header('Location: /Strively/pages/perfil.php?erro=nome_vazio');
  exit();
}

$fotoUrl = null;

// ----------------------------------------------------------
// PASSO 1 — Verifica se uma foto foi enviada
// ----------------------------------------------------------
if (!empty($_FILES['foto']['tmp_name'])) {

  $arquivo  = $_FILES['foto'];
  $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
  $permitidos = ['jpg', 'jpeg', 'png', 'webp'];

  // Valida formato
  if (!in_array($extensao, $permitidos)) {
    header('Location: /Strively/pages/perfil.php?erro=foto_invalida');
    exit();
  }

  // ----------------------------------------------------------
  // PASSO 2 — Cria nome único e envia para o Supabase Storage
  // ----------------------------------------------------------
  $nomeArquivo   = 'usuario-' . $id . '-' . uniqid() . '.' . $extensao;
  $bucket        = 'fotos-perfil';
  $supabaseUrl   = $_ENV['SUPABASE_URL'];
  $serviceKey    = $_ENV['SUPABASE_SERVICE_ROLE_KEY'];

  // Lê o conteúdo binário do arquivo temporário
  $conteudo = file_get_contents($arquivo['tmp_name']);

  // Monta o endpoint da API do Supabase Storage
  $endpoint = $supabaseUrl . '/storage/v1/object/' . $bucket . '/' . $nomeArquivo;

  // Faz o upload usando stream context nativo do PHP (não precisa da extensão cURL)
  $opcoes = [
    'http' => [
      'method'  => 'POST',
      'header'  => "Authorization: Bearer " . $serviceKey . "\r\n" .
                   "Content-Type: image/" . ($extensao === 'jpg' ? 'jpeg' : $extensao) . "\r\n" .
                   "x-upsert: true\r\n",
      'content' => $conteudo,
      'ignore_errors' => true
    ]
  ];
  $contexto = stream_context_create($opcoes);
  $resposta = file_get_contents($endpoint, false, $contexto);

  // Extrai o HTTP Code da variável automática $http_response_header
  $httpCode = 500;
  if (!empty($http_response_header)) {
    preg_match('#HTTP/\d+\.\d+ (\d+)#', $http_response_header[0], $matches);
    if (isset($matches[1])) {
        $httpCode = (int)$matches[1];
    }
  }

  if ($httpCode !== 200 && $httpCode !== 201) {
    header('Location: /Strively/pages/perfil.php?erro=upload_falhou');
    exit();
  }

  // ----------------------------------------------------------
  // PASSO 3 — Gera a URL pública previsível do Supabase
  // Não precisa de outra requisição — a URL é sempre assim:
  // {SUPABASE_URL}/storage/v1/object/public/{bucket}/{arquivo}
  // ----------------------------------------------------------
  $fotoUrl = $supabaseUrl . '/storage/v1/object/public/' . $bucket . '/' . $nomeArquivo;
}

// ----------------------------------------------------------
// PASSO 4 — Salva APENAS a URL no banco, nunca o arquivo
// ----------------------------------------------------------
if ($fotoUrl) {
  $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, cidade = ?, foto = ? WHERE id = ?");
  $stmt->execute([$nome, $cidade, $fotoUrl, $id]);
  $_SESSION['foto'] = $fotoUrl;
} else {
  $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, cidade = ? WHERE id = ?");
  $stmt->execute([$nome, $cidade, $id]);
}

$_SESSION['nome'] = $nome;

// Redireciona de volta para a tela inicial com sucesso
header('Location: /Strively/index.php?msg=atualizado');
exit();