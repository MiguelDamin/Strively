<?php
// ==========================================================
// STRIVELY — scripts/scraper-sesc.php
// Importa automaticamente os próximos eventos de corrida do Sesc/RS
// Fonte: ecommerce.sesc-rs.com.br (catálogo de eventos esportivos)
//
// COMO RODAR:
// Manual:  php /var/www/html/Strively/scripts/scraper-sesc.php
// ==========================================================

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/scraper-eventos.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// ID do usuário autor vindo do .env
$usuario_id = $_ENV['ADMIN_ID'] ?? 2;

// Banner padrão do Sesc
define('SESC_LOGO', '/assets/img/eventos/sesc-banner.png');

// URLs do eCommerce Sesc
define('SESC_LANDING_URL', 'https://ecommerce.sesc-rs.com.br/ecommerce.landingpage.aspx');
define('SESC_CATALOGO_URL', 'https://ecommerce.sesc-rs.com.br/ecommerce.catalogoprodutos.aspx?EVE,0,0,0,,');

echo "=== Scraper Sesc/RS (eCommerce) ===\n";
echo "Iniciando: " . date('d/m/Y H:i:s') . "\n";
echo "Autor ID: $usuario_id\n\n";

// ----------------------------------------------------------
// PASSO 1 — Baixa o catálogo utilizando sessão de cookies
// ----------------------------------------------------------
function fetchSescEcommerceHtml(): string {
    $cookieFile = tempnam(sys_get_temp_dir(), 'sesc_cookie_');
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    // 1. Acessa landing page para coletar cookies de sessão
    $ch = curl_init(SESC_LANDING_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_USERAGENT      => $userAgent,
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    curl_exec($ch);
    curl_close($ch);

    // 2. Acessa o catálogo com os cookies da sessão
    $ch = curl_init(SESC_CATALOGO_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_USERAGENT      => $userAgent,
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
            'Referer: ' . SESC_LANDING_URL,
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    @unlink($cookieFile);
    return $html ?: '';
}

$html = fetchSescEcommerceHtml();

if (!$html) {
    echo "ERRO: Não foi possível acessar o catálogo do Sesc.\n";
    exit(1);
}

echo "HTML baixado: " . number_format(strlen($html)) . " bytes\n";

// ----------------------------------------------------------
// PASSO 2 — Extrai JSON embutido no campo GridfreeContainerDataV
// ----------------------------------------------------------
if (!preg_match('/name="GridfreeContainerDataV"\s+value=\'(.*?)\'\s*\/>/s', $html, $m)) {
    if (!preg_match('/name="GridfreeContainerDataV"\s+value="(.*?)"\s*\/>/s', $html, $m)) {
        echo "ERRO: Campo GridfreeContainerDataV não encontrado no HTML.\n";
        exit(1);
    }
}

$jsonRaw = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
$gridData = json_decode($jsonRaw, true);

if (!is_array($gridData)) {
    echo "ERRO: JSON inválido extraído do catálogo.\n";
    exit(1);
}

echo "Registros no catálogo: " . count($gridData) . "\n\n";

// ----------------------------------------------------------
// FUNÇÕES AUXILIARES
// ----------------------------------------------------------

/**
 * Cria slug URL a partir do nome do evento
 * Ex: "IV SOLDIER NIGHT RUN" → "IV-SOLDIER-NIGHT-RUN"
 */
function slugify(string $nome): string {
    $nome = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome);
    $nome = preg_replace('/[^A-Z0-9\s]/i', '', $nome);
    $nome = preg_replace('/\s+/', '-', trim($nome));
    return strtoupper($nome);
}

/**
 * Extrai a cidade a partir do campo LOCAL do catálogo
 * Formato: "SESC NOME_DA_CIDADE" → "Nome Da Cidade - RS"
 */
function extrairCidadeSesc(string $local): string {
    $cidade = preg_replace('/^SESC\s+/iu', '', trim($local));
    $cidade = mb_convert_case(trim($cidade), MB_CASE_TITLE, 'UTF-8');
    return $cidade . ' - RS';
}

/**
 * Converte data do formato DD/MM/YYYY para YYYY-MM-DD
 */
function parseDateSesc(string $data): ?string {
    if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $data, $m)) {
        return "{$m[3]}-{$m[2]}-{$m[1]}";
    }
    return null;
}

/**
 * Identifica se o evento é de corrida / caminhada
 */
function ehEventoCorrida(string $nome, string $categoria): bool {
    $normNome = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower($nome, 'UTF-8'));
    $normCat  = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower($categoria, 'UTF-8'));

    // Exclusões de modalidades esportivas não-corrida
    $exclusoes = ['futsal', 'futebol', 'basquete', 'voleibol', 'volei', 'canastra', 'ciclismo', 'cicloturismo', 'judo', 'natacao'];
    foreach ($exclusoes as $ex) {
        if (strpos($normNome, $ex) !== false || strpos($normCat, $ex) !== false) {
            return false;
        }
    }

    // Categorias válidas
    $catsAceitas = ['corrida de rua', 'circuito sesc de corridas', 'caminhada', 'maratona', 'rustica'];
    foreach ($catsAceitas as $ca) {
        if (strpos($normCat, $ca) !== false) {
            return true;
        }
    }

    // Termos no título
    $termosCorrida = ['corrida', 'run', 'maratona', 'rustica', 'caminhada', 'circuito', 'etapa'];
    foreach ($termosCorrida as $tc) {
        if (strpos($normNome, $tc) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Normaliza string para comparação de similaridade
 */
function normalizarTextoMatch(string $str): string {
    $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
    $str = strtolower($str);
    $str = preg_replace('/\((lote extra|kids)\)/i', '', $str);
    $str = preg_replace('/[^a-z0-9\s]/', ' ', $str);
    return preg_replace('/\s+/', ' ', trim($str));
}

/**
 * Busca evento existente no banco por nome exato ou similaridade no mesmo dia
 */
function buscarEventoExistente(PDO $pdo, string $nome, string $dataSql): ?array {
    // 1. Busca exata por nome e data
    $stmt = $pdo->prepare("SELECT id, nome, cidade, link_oficial, banner FROM eventos WHERE LOWER(TRIM(nome)) = LOWER(TRIM(?)) AND data_evento = ?");
    $stmt->execute([$nome, $dataSql]);
    $ev = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($ev) return $ev;

    // 2. Busca por proximidade no mesmo dia
    $stmt = $pdo->prepare("SELECT id, nome, cidade, link_oficial, banner FROM eventos WHERE data_evento = ?");
    $stmt->execute([$dataSql]);
    $noMesmoDia = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $normNovo = normalizarTextoMatch($nome);
    $tokensNovo = array_filter(explode(' ', $normNovo), fn($t) => strlen($t) > 2);

    foreach ($noMesmoDia as $cand) {
        $normCand = normalizarTextoMatch($cand['nome']);
        $tokensCand = array_filter(explode(' ', $normCand), fn($t) => strlen($t) > 2);

        $inter = array_intersect($tokensNovo, $tokensCand);
        if (count($inter) >= 2 || $normNovo === $normCand) {
            return $cand;
        }
    }

    return null;
}

// ----------------------------------------------------------
// PASSO 3 — Mapeia campos do grid e filtra corridas
// ----------------------------------------------------------
$eventosExtraidos = [];

foreach ($gridData as $row) {
    $id        = trim($row[7]   ?? '');
    $banner    = trim($row[22]  ?? '');
    $categoria = trim($row[34]  ?? '');
    $nome      = trim($row[56]  ?? '');
    $local     = trim($row[84]  ?? '');
    $dataStr   = trim($row[109] ?? '');

    if (empty($id) || empty($nome) || empty($dataStr)) continue;

    // Converte data
    $dataSql = parseDateSesc($dataStr);
    if (!$dataSql) continue;

    // Filtra apenas modalidades de corrida
    if (!ehEventoCorrida($nome, $categoria)) {
        continue;
    }

    $slug        = slugify($nome);
    $linkOficial = "https://ecommerce.sesc-rs.com.br/ecommerce.paginaprodutoevento.aspx?{$id},{$slug}";
    $cidade      = !empty($local) ? extrairCidadeSesc($local) : 'Rio Grande do Sul - RS';
    $bannerFinal = !empty($banner) ? $banner : SESC_LOGO;

    $eventosExtraidos[] = [
        'id_ecommerce' => $id,
        'nome'         => $nome,
        'data_evento'  => $dataSql,
        'cidade'       => $cidade,
        'distancias'   => '',
        'link_oficial' => $linkOficial,
        'banner'       => $bannerFinal,
        'categoria'    => $categoria,
        'descricao'    => "Evento esportivo Sesc/RS: $nome em $cidade. Inscrições abertas no site oficial.",
    ];
}

echo "Eventos de corrida identificados: " . count($eventosExtraidos) . "\n\n";

// ----------------------------------------------------------
// PASSO 4 — Salvar / Atualizar no Banco de Dados
// ----------------------------------------------------------
$inseridos   = 0;
$atualizados = 0;
$ignorados   = 0;
$passados    = 0;

foreach ($eventosExtraidos as $ev) {
    if (strtotime($ev['data_evento']) < strtotime('today')) {
        echo "  ~ Passado: {$ev['nome']} ({$ev['data_evento']})\n";
        $passados++;
        continue;
    }

    $existente = buscarEventoExistente($pdo, $ev['nome'], $ev['data_evento']);

    if ($existente) {
        $precisaAtualizar = false;
        $updates = [];
        $params = [];

        if ($existente['link_oficial'] !== $ev['link_oficial']) {
            $updates[] = "link_oficial = ?";
            $params[] = $ev['link_oficial'];
            $precisaAtualizar = true;
        }

        // Se a cidade atual for genérica ou contiver barra antiga, atualiza com a cidade precisa do card
        if (strpos($existente['cidade'], '/') !== false || empty($existente['cidade']) || stripos($existente['cidade'], 'Unimed') !== false) {
            $updates[] = "cidade = ?";
            $params[] = $ev['cidade'];
            // Atualiza coordenadas também
            [$lat, $lng] = geocodificarCidade($ev['cidade']);
            if ($lat !== null && $lng !== null) {
                $updates[] = "lat = ?";
                $updates[] = "lng = ?";
                $params[] = $lat;
                $params[] = $lng;
            }
            $precisaAtualizar = true;
        }

        if (!empty($ev['banner']) && $ev['banner'] !== SESC_LOGO && $existente['banner'] !== $ev['banner']) {
            $updates[] = "banner = ?";
            $params[] = $ev['banner'];
            $precisaAtualizar = true;
        }

        if ($precisaAtualizar) {
            $params[] = $existente['id'];
            $sql = "UPDATE eventos SET " . implode(', ', $updates) . " WHERE id = ?";
            $upd = $pdo->prepare($sql);
            $upd->execute($params);

            echo "  ~ Atualizado [#{$existente['id']}]: {$existente['nome']}\n";
            echo "    Link: {$ev['link_oficial']}\n";
            echo "    Cidade: {$ev['cidade']}\n";
            $atualizados++;
        } else {
            echo "  - Já atualizado: {$ev['nome']} ({$ev['data_evento']})\n";
            $ignorados++;
        }
        continue;
    }

    // Evento novo — geocodifica e insere
    [$lat, $lng] = geocodificarCidade($ev['cidade']);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO eventos (usuario_id, nome, cidade, lat, lng, data_evento, distancias, descricao, link_oficial, banner, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo')
        ");
        $stmt->execute([
            $usuario_id,
            $ev['nome'],
            $ev['cidade'],
            $lat,
            $lng,
            $ev['data_evento'],
            $ev['distancias'],
            $ev['descricao'],
            $ev['link_oficial'],
            $ev['banner'],
        ]);
        echo "  + Inserido: {$ev['nome']}\n";
        echo "    Cidade: {$ev['cidade']} | Data: {$ev['data_evento']}\n";
        echo "    Link: {$ev['link_oficial']}\n";
        $inseridos++;
    } catch (Exception $e) {
        echo "  ! Erro ao inserir {$ev['nome']}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Concluído ===\n";
echo "Inseridos: $inseridos\n";
echo "Atualizados: $atualizados\n";
echo "Já atualizados/Duplicados: $ignorados\n";
echo "Eventos passados: $passados\n";

