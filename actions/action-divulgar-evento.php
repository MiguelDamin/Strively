<?php
// ==========================================================
// STRIVELY — actions/action-divulgar-evento.php
// Processa formulário de evento e faz upload para Supabase
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

// 1. SÓ ACEITA POST E USUÁRIO LOGADO
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: ../pages/eventos.php');
  exit();
}

// 2. PEGA OS DADOS
$nome           = trim($_POST['nome']           ?? '');
$data_evento    = $_POST['data_evento']         ?? '';
$cidade         = trim($_POST['cidade']         ?? '');
$distancias_pre = $_POST['distancias_pre']      ?? []; // Array
$dist_livre     = trim($_POST['distancia_livre'] ?? '');
$descricao      = trim($_POST['descricao']      ?? '');
$link_oficial   = trim($_POST['link_oficial']   ?? '');

if (!empty($link_oficial) && !preg_match('/^https?:\/\//i', $link_oficial)) {
    $link_oficial = 'https://' . $link_oficial;
}
$banner_file    = $_FILES['banner']             ?? null;

// 3. VALIDAÇÃO BÁSICA
if (empty($nome) || empty($data_evento) || empty($cidade) || empty($descricao) || empty($link_oficial) || !$banner_file['tmp_name']) {
  header('Location: ../pages/divulgar-evento.php?erro=campos_vazios');
  exit();
}

// Junta as distâncias
$distancias_livres_limpas = [];
if ($dist_livre !== '') {
  $partes = explode(',', $dist_livre);
  foreach ($partes as $p) {
    $numeric = preg_replace('/[^0-9\.,]/', '', $p);
    $numeric = trim($numeric);
    if ($numeric !== '') {
      $distancias_livres_limpas[] = $numeric . 'km';
    }
  }
}

$todasDistancias = array_merge($distancias_pre, $distancias_livres_limpas);
$todasDistancias = array_unique(array_map('trim', $todasDistancias));
$distanciasStr   = implode(', ', array_filter($todasDistancias));

if (empty($distanciasStr)) {
  header('Location: ../pages/divulgar-evento.php?erro=distancias');
  exit();
}

// 4. UPLOAD PARA SUPABASE STORAGE
$supabaseUrl      = $_ENV['SUPABASE_URL'];
$supabaseKey      = $_ENV['SUPABASE_SERVICE_ROLE_KEY'];
$bucketName       = "banners-eventos";

// Gera nome único e valida formato
$ext             = strtolower(pathinfo($banner_file['name'], PATHINFO_EXTENSION));
$permitidos      = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($ext, $permitidos)) {
  header('Location: ../pages/divulgar-evento.php?erro=formato_imagem');
  exit();
}
$nomeUnico       = "evento-" . uniqid() . "." . $ext;
$fileBinary      = file_get_contents($banner_file['tmp_name']);

// Define Content-Type conforme extensão
$contentType = 'image/jpeg';
if ($ext === 'png')  $contentType = 'image/png';
if ($ext === 'webp') $contentType = 'image/webp';

// cURL para Supabase Storage API
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

if ($httpCode !== 200 && $httpCode !== 201) {
  header('Location: ../pages/divulgar-evento.php?erro=upload_falhou');
  exit();
}

// URL pública previsível
$bannerUrl = "{$supabaseUrl}/storage/v1/object/public/{$bucketName}/{$nomeUnico}";

// 5. SALVA NO BANCO (STATUS = ATIVO DIRETO)
try {
  $stmt = $pdo->prepare("
    INSERT INTO eventos (usuario_id, nome, cidade, data_evento, distancias, descricao, link_oficial, banner, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativo')
  ");
  $stmt->execute([
    $_SESSION['id'],
    $nome,
    $cidade,
    $data_evento,
    $distanciasStr,
    $descricao,
    $link_oficial,
    $bannerUrl
  ]);

  header('Location: ../pages/eventos.php?msg=enviado');
  exit();

} catch (PDOException $e) {
  // Em caso de erro no banco, o ideal seria deletar o arquivo do storage, mas vamos simplificar
  header('Location: ../pages/divulgar-evento.php?erro=db_falhou');
  exit();
}