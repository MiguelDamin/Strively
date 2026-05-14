<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: /pages/comunidade.php');
  exit();
}

$id = $_SESSION['id'];
$post_id = $_POST['post_id'] ?? null;
$titulo  = trim($_POST['titulo'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');

if (!$post_id) { header('Location: /pages/comunidade.php'); exit(); }

// Verificar ownership SEMPRE
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND usuario_id = ?");
$stmt->execute([$post_id, $_SESSION['id']]);
$post = $stmt->fetch();
if (!$post) { header('Location: /pages/comunidade.php?erro=sem_permissao'); exit(); }

// Upload de nova foto (opcional)
$foto = $post['foto']; // mantém foto atual se não enviar nova
if (!empty($_FILES['foto']['tmp_name'])) {
    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $nomeArquivo = 'post_' . $post_id . '_' . time() . '.' . $ext;
    $fileBinary = file_get_contents($_FILES['foto']['tmp_name']);
    $supabaseUrl = $_ENV['SUPABASE_URL'];
    $key = $_ENV['SUPABASE_SERVICE_ROLE_KEY'];
    $endpoint = "{$supabaseUrl}/storage/v1/object/posts-feed/{$nomeArquivo}";
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer {$key}\r\nContent-Type: image/{$ext}\r\nx-upsert: true\r\n",
            'content' => $fileBinary,
            'ignore_errors' => true,
            'timeout' => 30,
        ],
    ]);
    @file_get_contents($endpoint, false, $ctx);
    $foto = "{$supabaseUrl}/storage/v1/object/public/posts-feed/{$nomeArquivo}";
}

// Atualizar post
$stmt = $pdo->prepare("UPDATE posts SET titulo = ?, descricao = ?, foto = ?, editado_em = NOW() WHERE id = ? AND usuario_id = ?");
$stmt->execute([$titulo, $descricao, $foto, $post_id, $_SESSION['id']]);

header('Location: /pages/comunidade.php?sucesso=post_editado');
exit();

header('Location: /pages/comunidade.php');
exit();
