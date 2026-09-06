<?php
// ==========================================================
// STRIVELY — actions/action-editar-evento.php
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: ../pages/eventos.php');
  exit();
}

$evento_id = (int)($_POST['evento_id'] ?? 0);

// Verificar ownership ou admin
$stmt = $pdo->prepare("SELECT usuario_id, banner FROM eventos WHERE id = ?");
$stmt->execute([$evento_id]);
$evento = $stmt->fetch();

if (!$evento || ($evento['usuario_id'] != $_SESSION['id'] && $_SESSION['perfil'] !== 'admin')) {
  header('Location: ../pages/eventos.php?erro=sem_permissao');
  exit();
}

$nome           = trim($_POST['nome']           ?? '');
$data_evento    = $_POST['data_evento']         ?? '';
$cidade         = trim($_POST['cidade']         ?? '');
$distancias_pre = $_POST['distancias_pre']      ?? []; 
$dist_livre     = trim($_POST['distancia_livre'] ?? '');
$descricao      = trim($_POST['descricao']      ?? '');
$link_oficial   = trim($_POST['link_oficial']   ?? '');

if (!empty($link_oficial) && !preg_match('/^https?:\/\//i', $link_oficial)) {
    $link_oficial = 'https://' . $link_oficial;
}
$banner_file    = $_FILES['banner']             ?? null;

if (empty($nome) || empty($data_evento) || empty($cidade) || empty($descricao) || empty($link_oficial)) {
  header("Location: ../pages/editar-evento.php?id={$evento_id}&erro=campos_vazios");
  exit();
}

$distias_livres_limpas = [];
if ($dist_livre !== '') {
  $partes = explode(',', $dist_livre);
  foreach ($partes as $p) {
    $numeric = preg_replace('/[^0-9\.,]/', '', $p);
    $numeric = trim($numeric);
    if ($numeric !== '') {
      $distias_livres_limpas[] = $numeric . 'km';
    }
  }
}

$todasDistancias = array_merge($distancias_pre, $distias_livres_limpas);
$todasDistancias = array_unique(array_map('trim', $todasDistancias));
$distanciasStr   = implode(', ', array_filter($todasDistancias));

if (empty($distanciasStr)) {
  header("Location: ../pages/editar-evento.php?id={$evento_id}&erro=distancias");
  exit();
}

$bannerUrl = $evento['banner']; // mantem o atual por padrão

if ($banner_file && $banner_file['tmp_name']) {
  $ext             = strtolower(pathinfo($banner_file['name'], PATHINFO_EXTENSION));
  $valid_exts      = ['jpg', 'jpeg', 'png', 'webp'];
  if(!in_array($ext, $valid_exts)) {
    header("Location: ../pages/editar-evento.php?id={$evento_id}&erro=formato_imagem");
    exit();
  }

  $supabaseUrl      = $_ENV['SUPABASE_URL'];
  $supabaseKey      = $_ENV['SUPABASE_SERVICE_ROLE_KEY'];
  $bucketName       = "banners-eventos";
  if(true) {
    $nomeUnico       = "evento-" . uniqid() . "." . $ext;
    $fileBinary      = file_get_contents($banner_file['tmp_name']);

    $contentType = 'image/jpeg';
    if ($ext === 'png')  $contentType = 'image/png';
    if ($ext === 'webp') $contentType = 'image/webp';
    if ($ext === 'gif')  $contentType = 'image/gif';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$supabaseUrl}/storage/v1/object/{$bucketName}/{$nomeUnico}");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileBinary);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Authorization: Bearer {$supabaseKey}",
      "Content-Type: {$contentType}",
      "x-upsert: true"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 || $httpCode === 201) {
      $bannerUrl = "{$supabaseUrl}/storage/v1/object/public/{$bucketName}/{$nomeUnico}";
    }
  }
}

try {
  $stmt = $pdo->prepare("
    UPDATE eventos SET nome = ?, cidade = ?, data_evento = ?, distancias = ?, descricao = ?, link_oficial = ?, banner = ?
    WHERE id = ?
  ");
  $stmt->execute([
    $nome,
    $cidade,
    $data_evento,
    $distanciasStr,
    $descricao,
    $link_oficial,
    $bannerUrl,
    $evento_id
  ]);

  header("Location: ../pages/detalhe-evento.php?id={$evento_id}&msg=edit_sucesso");
  exit();

} catch (PDOException $e) {
  header("Location: ../pages/editar-evento.php?id={$evento_id}&erro=db_falhou");
  exit();
}
