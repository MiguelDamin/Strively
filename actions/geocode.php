<?php
// ==========================================================
// STRIVELY — actions/geocode.php
// Geocodificação de cidades via Nominatim (OpenStreetMap)
// ==========================================================

header('Content-Type: application/json; charset=utf-8');

$cidade = trim($_GET['cidade'] ?? ($_POST['cidade'] ?? ''));

if (empty($cidade)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Cidade não informada',
        'lat'     => null,
        'lng'     => null
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Função para geocodificar uma cidade brasileira via Nominatim
 */
function geocodificarNominatim(string $cidade): array {
    // Formata cidades com formato "Cidade/UF" para "Cidade, UF"
    $cidadeLimpa = preg_replace('/\s*\/\s*/', ', ', $cidade);
    $query = urlencode($cidadeLimpa . ', Brasil');
    $url   = "https://nominatim.openstreetmap.org/search?q={$query}&format=json&limit=1";

    $opts = [
        'http' => [
            'header'  => "User-Agent: Strively/1.0 (strivelyrun@gmail.com)\r\nAccept: application/json\r\n",
            'timeout' => 8,
        ]
    ];

    $json = @file_get_contents($url, false, stream_context_create($opts));
    if (!$json) {
        return [null, null, null];
    }

    $data = json_decode($json, true);
    if (!empty($data[0]['lat']) && !empty($data[0]['lon'])) {
        return [
            (float)$data[0]['lat'],
            (float)$data[0]['lon'],
            $data[0]['display_name'] ?? $cidade
        ];
    }

    return [null, null, null];
}

[$lat, $lng, $displayName] = geocodificarNominatim($cidade);

if ($lat !== null && $lng !== null) {
    echo json_encode([
        'success'      => true,
        'cidade'       => $cidade,
        'lat'          => $lat,
        'lng'          => $lng,
        'display_name' => $displayName
    ], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error'   => 'Localização não encontrada para a cidade informada',
        'cidade'  => $cidade,
        'lat'     => null,
        'lng'     => null
    ], JSON_UNESCAPED_UNICODE);
}
exit();
