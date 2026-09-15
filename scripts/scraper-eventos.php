<?php
// ==========================================================
// STRIVELY — scripts/scraper-eventos.php
// Utilitário de Geocodificação e Gestão de Eventos
// ==========================================================

require_once __DIR__ . '/../config/conexao.php';

/**
 * Converte o nome de uma cidade brasileira em [lat, lng] usando Nominatim (OpenStreetMap).
 * Respeita o rate limit do Nominatim com sleep(1) entre requisições externas.
 */
function geocodificarCidade(string $cidade): array {
    static $cache = [];

    $cidadeLimpa = trim($cidade);
    if (empty($cidadeLimpa)) {
        return [null, null];
    }

    // Normaliza cidades no padrão "Cidade/UF" para "Cidade, UF"
    $cidadeQuery = preg_replace('/\s*\/\s*/', ', ', $cidadeLimpa);
    $chaveCache = mb_strtolower($cidadeQuery, 'UTF-8');

    if (isset($cache[$chaveCache])) {
        return $cache[$chaveCache];
    }

    $query = urlencode($cidadeQuery . ', Brasil');
    $url   = "https://nominatim.openstreetmap.org/search?q={$query}&format=json&limit=1";

    $opts = [
        'http' => [
            'header'  => "User-Agent: Strively/1.0 (strivelyrun@gmail.com)\r\nAccept: application/json\r\n",
            'timeout' => 8,
        ]
    ];

    $json = @file_get_contents($url, false, stream_context_create($opts));
    // Rate limit respeitado: 1 segundo entre requisições
    sleep(1);

    if (!$json) {
        $cache[$chaveCache] = [null, null];
        return [null, null];
    }

    $data = json_decode($json, true);
    if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) {
        $coords = [(float)$data[0]['lat'], (float)$data[0]['lon']];
        $cache[$chaveCache] = $coords;
        return $coords;
    }

    $cache[$chaveCache] = [null, null];
    return [null, null];
}

/**
 * Insere ou atualiza um evento com coordenadas geográficas.
 */
function upsertEvent(PDO $pdo, array $evento): bool {
    $cidade = trim($evento['cidade'] ?? '');
    $lat = isset($evento['lat']) ? (float)$evento['lat'] : null;
    $lng = isset($evento['lng']) ? (float)$evento['lng'] : null;

    if (($lat === null || $lng === null) && !empty($cidade)) {
        [$lat, $lng] = geocodificarCidade($cidade);
    }

    // Verifica se evento já existe por nome e data_evento
    $check = $pdo->prepare("
        SELECT id FROM eventos 
        WHERE LOWER(TRIM(nome)) = LOWER(TRIM(?)) AND data_evento = ?
    ");
    $check->execute([$evento['nome'], $evento['data_evento']]);
    $existente = $check->fetch();

    if ($existente) {
        // Atualiza coordenadas se ainda não tiver
        $update = $pdo->prepare("
            UPDATE eventos 
            SET lat = COALESCE(lat, ?), 
                lng = COALESCE(lng, ?),
                cidade = ?,
                distancias = ?,
                descricao = ?,
                link_oficial = ?,
                banner = ?
            WHERE id = ?
        ");
        return $update->execute([
            $lat,
            $lng,
            $cidade,
            $evento['distancias'] ?? '',
            $evento['descricao'] ?? '',
            $evento['link_oficial'] ?? '',
            $evento['banner'] ?? '',
            $existente['id']
        ]);
    }

    // Insere novo evento
    $stmt = $pdo->prepare("
        INSERT INTO eventos 
            (usuario_id, nome, cidade, lat, lng, data_evento, distancias, descricao, link_oficial, banner, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo')
    ");
    return $stmt->execute([
        $evento['usuario_id'] ?? 1,
        $evento['nome'],
        $cidade,
        $lat,
        $lng,
        $evento['data_evento'],
        $evento['distancias'] ?? '',
        $evento['descricao'] ?? '',
        $evento['link_oficial'] ?? '',
        $evento['banner'] ?? ''
    ]);
}

/**
 * Rotina CLI: Geocodifica todos os eventos ativos do banco com coordenadas nulas.
 */
function geocodificarEventosExistentes(PDO $pdo): void {
    echo "=== Iniciando geocodificação de eventos existentes sem coordenadas ===\n";

    $stmt = $pdo->query("
        SELECT id, nome, cidade 
        FROM eventos 
        WHERE (lat IS NULL OR lng IS NULL) 
          AND status = 'ativo'
          AND data_evento > (CURRENT_TIMESTAMP AT TIME ZONE 'America/Sao_Paulo')::date
        ORDER BY data_evento ASC
    ");
    $eventos = $stmt->fetchAll();

    echo "Encontrados " . count($eventos) . " eventos para geocodificar.\n";

    $sucessos = 0;
    $falhas = 0;

    $upd = $pdo->prepare("UPDATE eventos SET lat = ?, lng = ? WHERE id = ?");

    foreach ($eventos as $ev) {
        echo "Geocodificando: [{$ev['id']}] {$ev['nome']} — {$ev['cidade']}... ";
        [$lat, $lng] = geocodificarCidade($ev['cidade']);

        if ($lat !== null && $lng !== null) {
            $upd->execute([$lat, $lng, $ev['id']]);
            echo "OK! ($lat, $lng)\n";
            $sucessos++;
        } else {
            echo "FALHOU.\n";
            $falhas++;
        }
    }

    echo "\n=== Geocodificação Concluída ===\n";
    echo "Sucessos: $sucessos\n";
    echo "Falhas: $falhas\n";
}

// Execução via terminal (CLI)
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    $args = $argv ?? [];
    if (in_array('--run-all', $args)) {
        echo "Executando scrapers...\n";
        include __DIR__ . '/scraper-sesc.php';
        include __DIR__ . '/scraper-inspira.php';
    } else {
        geocodificarEventosExistentes($pdo);
    }
}
