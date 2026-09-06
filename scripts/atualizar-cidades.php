<?php
// ==========================================================
// STRIVELY — scripts/atualizar-cidades.php
// Baixa lista de cidades de RS e SC do IBGE e salva no config/cidades.json
// ==========================================================

$cidades = [
  'RS' => [],
  'SC' => []
];

echo "=== Sincronizando cidades via IBGE ===\n";

// RS = 43, SC = 42
$ufs = ['RS' => '43', 'SC' => '42'];

foreach ($ufs as $uf => $id) {
  $url = "https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$id}/municipios";
  echo "Baixando cidades de {$uf}... ";
  
  $ctx = stream_context_create([
    'http' => [
      'timeout' => 15,
      'user_agent' => 'Mozilla/5.0 (compatible; StrivelyScraper/1.0)'
    ]
  ]);
  
  $response = @file_get_contents($url, false, $ctx);
  if (!$response) {
    echo "ERRO ao baixar de {$url}.\n";
    exit(1);
  }
  
  $list = json_decode($response, true);
  if (!is_array($list)) {
    echo "ERRO ao decodificar JSON do IBGE.\n";
    exit(1);
  }
  
  foreach ($list as $mun) {
    if (isset($mun['nome'])) {
      $cidades[$uf][] = trim($mun['nome']);
    }
  }
  
  // Ordena decrescente por tamanho de caractere (UTF-8 safe)
  // IMPORTANTE: Evita correspondência parcial precoce (ex: "Bento" em "Bento Gonçalves")
  usort($cidades[$uf], function($a, $b) {
    return strlen($b) <=> strlen($a);
  });
  
  echo count($cidades[$uf]) . " cidades carregadas.\n";
}

$destPath = __DIR__ . '/../config/cidades.json';
$dir = dirname($destPath);
if (!is_dir($dir)) {
  mkdir($dir, 0755, true);
}

if (file_put_contents($destPath, json_encode($cidades, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
  echo "Salvo com sucesso em: " . realpath($destPath) . "\n";
} else {
  echo "ERRO ao gravar arquivo.\n";
  exit(1);
}
