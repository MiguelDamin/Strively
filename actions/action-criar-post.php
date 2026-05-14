<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: /pages/comunidade.php');
  exit();
}

$id = $_SESSION['id'];
$titulo = trim($_POST['titulo'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$tipo = trim($_POST['tipo'] ?? 'manual');

if (empty($titulo)) {
  header('Location: /pages/comunidade.php?erro=titulo_vazio');
  exit();
}

$fotoUrl = null;

if (!empty($_FILES['foto']['tmp_name'])) {
  $arquivo = $_FILES['foto'];
  $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
  $permitidos = ['jpg', 'jpeg', 'png', 'webp'];
  
  if (in_array($extensao, $permitidos)) {
    $nomeArquivo = 'post-' . $id . '-' . time() . '-' . uniqid() . '.' . $extensao;
    $bucket = 'posts-feed';
    $supabaseUrl = $_ENV['SUPABASE_URL'];
    $serviceKey = $_ENV['SUPABASE_SERVICE_ROLE_KEY'];
    $conteudo = file_get_contents($arquivo['tmp_name']);
    $endpoint = $supabaseUrl . '/storage/v1/object/' . $bucket . '/' . $nomeArquivo;
    
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
    
    $httpCode = 500;
    if (!empty($http_response_header)) {
      preg_match('#HTTP/\d+\.\d+ (\d+)#', $http_response_header[0], $matches);
      if (isset($matches[1])) {
          $httpCode = (int)$matches[1];
      }
    }
    
    if ($httpCode === 200 || $httpCode === 201) {
      $fotoUrl = $supabaseUrl . '/storage/v1/object/public/' . $bucket . '/' . $nomeArquivo;
    }
  }
}

$stmt = $pdo->prepare("INSERT INTO posts (usuario_id, tipo, titulo, descricao, foto, criado_em) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->execute([$id, $tipo, $titulo, $descricao, $fotoUrl]);

header('Location: /pages/comunidade.php');
exit();
