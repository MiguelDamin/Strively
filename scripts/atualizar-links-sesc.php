<?php
// ==========================================================
// STRIVELY — scripts/atualizar-links-sesc.php
// Atualização de links oficiais e higienização de cidades
// para todos os eventos Sesc no banco de dados.
//
// COMO RODAR:
// php /var/www/html/Strively/scripts/atualizar-links-sesc.php
// ==========================================================

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/scraper-eventos.php';
require_once __DIR__ . '/scraper-sesc.php';

echo "\n=== Atualização e Higienização de Cidades / Links Sesc ===\n\n";

// Correções específicas de cidades com erros do scraper antigo
$correcoesEspecificas = [
    273 => 'São Sebastião do Caí - RS', // 14ª CORRIDA E CAMINHADA UNIMED VALE DO CAÍ (era Unimed Vale/RS)
    276 => 'São Sebastião do Caí - RS', // CORRIDA E CAMINHADA – SESC SÃO SEBASTIÃO DO CAÍ (era Sebastiao/RS)
    274 => 'Santo Augusto - RS',        // RÚSTICA DE SANTO AUGUSTO (era Santo Augusto./RS)
    243 => 'Caxias do Sul - RS',        // 2ª RÚSTICA POLÍCIA CIVIL – CAXIAS DO SUL (era Porto Alegre/RS)
    279 => 'Santa Cruz do Sul - RS',    // 2ª CORRIDA DO FOGO – SANTA CRUZ DO SUL (era Porto Alegre/RS)
    278 => 'Jóia - RS',                 // 2ª CORRIDA DAS NASCENTES – JÓIA (era Nascentes/RS)
];

$stmt = $pdo->query("SELECT id, nome, cidade, lat, lng FROM eventos WHERE link_oficial LIKE '%sesc%' OR nome ILIKE '%sesc%'");
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cidadesAtualizadas = 0;
$coordsAtualizadas  = 0;

foreach ($eventos as $ev) {
    $id = (int)$ev['id'];
    $cidadeAtual = trim($ev['cidade']);
    $novaCidade = $cidadeAtual;

    // Aplica correção específica se houver
    if (isset($correcoesEspecificas[$id])) {
        $novaCidade = $correcoesEspecificas[$id];
    } else {
        // Padroniza "Cidade/RS" para "Cidade - RS"
        if (preg_match('/^(.*?)\s*\/\s*RS$/i', $cidadeAtual, $m)) {
            $novaCidade = trim($m[1]) . ' - RS';
        }
    }

    $alterouCidade = ($novaCidade !== $cidadeAtual);
    $lat = $ev['lat'];
    $lng = $ev['lng'];
    $alterouCoord = false;

    if ($alterouCidade || empty($lat) || empty($lng)) {
        echo "Geocodificando [#$id] {$ev['nome']} -> {$novaCidade}... ";
        [$nLat, $nLng] = geocodificarCidade($novaCidade);
        if ($nLat !== null && $nLng !== null) {
            $lat = $nLat;
            $lng = $nLng;
            $alterouCoord = true;
            echo "OK! ($lat, $lng)\n";
        } else {
            echo "Não localizada.\n";
        }
    }

    if ($alterouCidade || $alterouCoord) {
        $upd = $pdo->prepare("UPDATE eventos SET cidade = ?, lat = ?, lng = ? WHERE id = ?");
        $upd->execute([$novaCidade, $lat, $lng, $id]);
        if ($alterouCidade) $cidadesAtualizadas++;
        if ($alterouCoord)  $coordsAtualizadas++;
        echo "  ~ Salvo [#$id]: $novaCidade\n";
    }
}

echo "\n=== Resumo da Higienização ===\n";
echo "Cidades padronizadas/corrigidas: $cidadesAtualizadas\n";
echo "Coordenadas atualizadas: $coordsAtualizadas\n\n";
