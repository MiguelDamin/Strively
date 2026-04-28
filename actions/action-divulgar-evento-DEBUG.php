<?php
// ==========================================================
// STRIVELY — actions/action-divulgar-evento.php  [DEBUG]
// Remova este arquivo após identificar o erro!
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

// Exibe todos os erros PHP para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<pre style='background:#111;color:#0f0;padding:20px;font-size:13px;'>";
echo "=== DEBUG ACTION-DIVULGAR-EVENTO ===\n\n";

// -----------------------------------------
// PASSO 1: SESSÃO
// -----------------------------------------
echo "--- SESSÃO ---\n";
echo "REQUEST_METHOD : " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "SESSION id     : " . ($_SESSION['id'] ?? 'NÃO EXISTE') . "\n\n";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
    echo "❌ PAROU AQUI: não é POST ou sessão ausente\n";
    echo "</pre>"; exit();
}

// -----------------------------------------
// PASSO 2: CAMPOS DO FORMULÁRIO
// -----------------------------------------
echo "--- POST DATA ---\n";
$nome        = trim($_POST['nome']            ?? '');
$data_evento = trim($_POST['data_evento']     ?? '');
$cidade      = trim($_POST['cidade']          ?? '');
$dist_pre    = $_POST['distancias_pre']       ?? [];
$dist_livre  = trim($_POST['distancia_livre'] ?? '');
$descricao   = trim($_POST['descricao']       ?? '');
$link        = trim($_POST['link_oficial']    ?? '');

echo "nome        : '$nome'\n";
echo "data_evento : '$data_evento'\n";
echo "cidade      : '$cidade'\n";
echo "dist_pre    : " . implode(', ', $dist_pre) . "\n";
echo "dist_livre  : '$dist_livre'\n";
echo "descricao   : '" . substr($descricao, 0, 60) . "...'\n";
echo "link        : '$link'\n\n";

// -----------------------------------------
// PASSO 3: ARQUIVO ENVIADO
// -----------------------------------------
echo "--- FILES ---\n";
if (isset($_FILES['banner'])) {
    echo "name     : " . $_FILES['banner']['name'] . "\n";
    echo "size     : " . $_FILES['banner']['size'] . " bytes\n";
    echo "tmp_name : " . $_FILES['banner']['tmp_name'] . "\n";
    echo "error    : " . $_FILES['banner']['error'] . " (0 = OK)\n\n";
} else {
    echo "❌ \$_FILES['banner'] NÃO EXISTE\n\n";
}

$banner_file = $_FILES['banner'] ?? null;

if (!isset($banner_file['tmp_name']) || $banner_file['error'] !== UPLOAD_ERR_OK || empty($banner_file['tmp_name'])) {
    echo "❌ PAROU AQUI: arquivo com erro ou ausente (error code: " . ($banner_file['error'] ?? 'N/A') . ")\n";
    echo "</pre>"; exit();
}

// -----------------------------------------
// PASSO 4: EXTENSÃO E MIME
// -----------------------------------------
echo "--- ARQUIVO ---\n";
$ext = strtolower(pathinfo($banner_file['name'], PATHINFO_EXTENSION));
echo "extensão : '$ext'\n";

$mimeMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
if (!array_key_exists($ext, $mimeMap)) {
    echo "❌ PAROU AQUI: extensão não permitida\n";
    echo "</pre>"; exit();
}
$contentType = $mimeMap[$ext];
echo "contentType : '$contentType'\n\n";

// -----------------------------------------
// PASSO 5: VARIÁVEIS DE AMBIENTE
// -----------------------------------------
echo "--- ENV ---\n";
$supabaseUrl = $_ENV['SUPABASE_URL'] ?? null;
$supabaseKey = $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? null;

echo "SUPABASE_URL              : " . ($supabaseUrl ? substr($supabaseUrl, 0, 30) . '...' : '❌ NÃO EXISTE') . "\n";
echo "SUPABASE_SERVICE_ROLE_KEY : " . ($supabaseKey ? substr($supabaseKey, 0, 20) . '...' : '❌ NÃO EXISTE') . "\n\n";

if (!$supabaseUrl || !$supabaseKey) {
    echo "❌ PAROU AQUI: variáveis de ambiente ausentes!\n";
    echo "</pre>"; exit();
}

// -----------------------------------------
// PASSO 6: UPLOAD SUPABASE
// -----------------------------------------
echo "--- UPLOAD SUPABASE ---\n";
$bucketName = 'banners-eventos';
$nomeUnico  = 'evento-' . $_SESSION['id'] . '-' . uniqid() . '.' . $ext;
$endpoint   = "{$supabaseUrl}/storage/v1/object/{$bucketName}/{$nomeUnico}";

echo "bucket   : $bucketName\n";
echo "arquivo  : $nomeUnico\n";
echo "endpoint : $endpoint\n\n";

// Verifica se cURL está disponível
if (!function_exists('curl_init')) {
    echo "❌ PAROU AQUI: extensão cURL não está instalada no PHP!\n";
    echo "</pre>"; exit();
}

$fileBinary = file_get_contents($banner_file['tmp_name']);
echo "Bytes lidos do tmp_name: " . strlen($fileBinary) . "\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $endpoint,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => $fileBinary,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer {$supabaseKey}",
        "Content-Type: {$contentType}",
        "x-upsert: true",
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code  : $httpCode\n";
echo "cURL Error : " . ($curlError ?: 'nenhum') . "\n";
echo "Response   : " . substr($response, 0, 300) . "\n\n";

if ($httpCode !== 200 && $httpCode !== 201) {
    echo "❌ PAROU AQUI: upload falhou com HTTP $httpCode\n";
    echo "</pre>"; exit();
}

$bannerUrl = "{$supabaseUrl}/storage/v1/object/public/{$bucketName}/{$nomeUnico}";
echo "✅ Upload OK! URL: $bannerUrl\n\n";

// -----------------------------------------
// PASSO 7: DISTÂNCIAS
// -----------------------------------------
echo "--- DISTÂNCIAS ---\n";
$todas = array_unique(array_filter(array_map('trim', array_merge(
    $dist_pre,
    $dist_livre !== '' ? explode(',', $dist_livre) : []
))));
$distanciasStr = implode(', ', $todas);
echo "distâncias : '$distanciasStr'\n\n";

if (empty($distanciasStr)) {
    echo "❌ PAROU AQUI: nenhuma distância informada\n";
    echo "</pre>"; exit();
}

// -----------------------------------------
// PASSO 8: INSERT NO BANCO
// -----------------------------------------
echo "--- BANCO DE DADOS ---\n";
try {
    $stmt = $pdo->prepare("
        INSERT INTO eventos (usuario_id, nome, cidade, data_evento, distancias, descricao, link_oficial, banner, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativo')
    ");
    $stmt->execute([
        $_SESSION['id'], $nome, $cidade, $data_evento,
        $distanciasStr, $descricao, $link, $bannerUrl,
    ]);
    $novoId = $pdo->lastInsertId();
    echo "✅ INSERT OK! ID do evento: $novoId\n\n";
} catch (PDOException $e) {
    echo "❌ ERRO NO BANCO: " . $e->getMessage() . "\n";
    echo "</pre>"; exit();
}

echo "=== TUDO OK — redirecionar para eventos.php ===\n";
echo "</pre>";

// Descomente a linha abaixo após confirmar que tudo funciona:
// header('Location: ../pages/eventos.php?msg=enviado'); exit();