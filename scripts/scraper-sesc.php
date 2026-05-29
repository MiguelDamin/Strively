<?php
// ==========================================================
// STRIVELY — scripts/scraper-sesc.php
// Importa automaticamente os próximos eventos de corrida do Sesc/RS
// Site: https://www.sesc-rs.com.br/esporte/corridas/
//
// COMO RODAR:
// Manual:  php /var/www/html/Strively/scripts/scraper-sesc.php
// ==========================================================

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/conexao.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// ID do usuário autor vindo do .env
$usuario_id = $_ENV['ADMIN_ID'] ?? 2;

// Banner fornecido pelo usuário — caminho absoluto a partir da raiz web
define('SESC_LOGO', '/assets/img/eventos/sesc-banner.png');

echo "=== Scraper Sesc RS Corridas ===\n";
echo "Iniciando: " . date('d/m/Y H:i:s') . "\n";
echo "Autor ID: $usuario_id\n\n";

// ----------------------------------------------------------
// PASSO 1 — Baixa o HTML do site do Sesc
// ----------------------------------------------------------
function fetchHtml(string $url): string {
    $html = shell_exec(
        'curl -sL --max-time 30 -A "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36" ' .
        escapeshellarg($url)
    );
    return $html ?: '';
}

$html = fetchHtml('https://www.sesc-rs.com.br/esporte/corridas/');

if (!$html) {
    echo "ERRO: Não foi possível acessar o site do Sesc.\n";
    exit(1);
}

echo "HTML baixado: " . number_format(strlen($html)) . " bytes\n";

// ----------------------------------------------------------
// PASSO 2 — Parseia o HTML
// ----------------------------------------------------------
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
libxml_clear_errors();
$xpath = new DOMXPath($dom);

// ----------------------------------------------------------
// Funções Auxiliares
// ----------------------------------------------------------

/**
 * Tenta extrair a cidade do nome do evento.
 * Estratégia: keyword mapping → regex no título → busca no PDF URL → dicionário de cidades RS.
 */
function extrairCidade(string $nome, string $pdfUrl = ''): string {
    $nomeLower   = mb_strtolower($nome, 'UTF-8');
    $pdfUrlLower = mb_strtolower($pdfUrl, 'UTF-8');

    // 1. Mapeamento direto por palavra-chave no nome
    $mapping = [
        'caravaggio'         => 'Farroupilha/RS',
        'festiqueijo'        => 'Carlos Barbosa/RS',
        'nevasca'            => 'Caxias do Sul/RS',
        'chuvisca'           => 'Chuvisca/RS',
        'choque'             => 'Passo Fundo/RS',
        'fogo'               => 'Porto Alegre/RS',
        'guaíba'             => 'Guaíba/RS',
        'orla do guaíba'     => 'Porto Alegre/RS',
        'riograndino'        => 'Rio Grande/RS',
        'caita'              => 'Bento Gonçalves/RS',
        'caitá'              => 'Bento Gonçalves/RS',
        'serafinense'        => 'Serafina Corrêa/RS',
        'tribus run'         => 'Rio Grande/RS',
        'contra o frio'      => 'Porto Alegre/RS',
        'desafio farroupilha'=> 'Farroupilha/RS',
        'trilha noturna'     => 'Farroupilha/RS',
        'são léo mooving'    => 'São Leopoldo/RS',
        'sao leo mooving'    => 'São Leopoldo/RS',
        'palmeiras das missões' => 'Palmeiras das Missões/RS',
        'taquaruçu'          => 'Taquaruçu do Sul/RS',
        'capão do cipó'      => 'Capão do Cipó/RS',
        'bom princípio'      => 'Bom Princípio/RS',
        'vicente dutra'      => 'Vicente Dutra/RS',
        'ibiaçá'             => 'Ibiaçá/RS',
        'augusto pestana'    => 'Augusto Pestana/RS',
        'adoção'             => 'Porto Alegre/RS',
        'meio ambiente'      => 'Caxias do Sul/RS',
        'polícia civil'      => 'Porto Alegre/RS',
        'policia civil'      => 'Porto Alegre/RS',
        'bombeiros'          => 'Pelotas/RS',
    ];

    foreach ($mapping as $key => $city) {
        if (mb_stripos($nomeLower, $key, 0, 'UTF-8') !== false) return $city;
    }

    // 2. Extrai cidade de padrões no nome do evento (– CIDADE, EM CIDADE, / CIDADE)
    $patterns = [
        '/[\-–]\s+([A-ZÀ-Ÿ]{3,}(?:\s+[A-ZÀ-Ÿ]{2,}(?:\s+[A-ZÀ-Ÿ]{2,})?)?)\s*$/u', // final: "– PORTO ALEGRE"
        '/\bEM\s+([A-ZÀ-Ÿ]{3,}(?:\s+[A-ZÀ-Ÿ]{2,})?)/u',                            // "EM BENTO"
        '/SESC\s+([A-ZÀ-Ÿ][A-ZÀ-Ÿa-zà-ÿ]+(?:\s+[A-ZÀ-Ÿ][A-ZÀ-Ÿa-zà-ÿ]+)?)/u',      // "SESC CANOAS"
        '/\b([A-ZÀ-Ÿ]{4,}(?:\s+[A-ZÀ-Ÿ]{2,}){0,3})\s*(?:2026|\d{4})$/u',           // "SANTANA DO LIVRAMENTO 2026"
    ];

    $skipWords = ['SESC', 'RS', 'DATA', 'KM', 'DDD', 'ETAPA', 'CIRCUITO', 'CORRIDA', 'CAMINHADA', 'TRILHA', 'RÚSTICA', 'TREINÃO', 'TREINO', 'POLÍCIA', 'CIVIL', 'BOMBEIROS', 'BRIGADA', 'MILITAR', 'BPM'];

    foreach ($patterns as $p) {
        if (preg_match($p, $nome, $m)) {
            $c = trim(preg_replace('/^(Etapa|Circuito|Corrida|Sesc)\s+/iu', '', $m[1]));
            $cUpper = mb_strtoupper($c, 'UTF-8');
            if (!in_array($cUpper, $skipWords) && strlen($c) >= 3 && strlen($c) < 40) {
                return ucwords(mb_strtolower($c, 'UTF-8'), " \t\r\n\f\v-") . '/RS';
            }
        }
    }

    // 3. Minera cidade a partir do nome do arquivo PDF do regulamento
    //    Ex: "Regulamento-Corrida-Noturna-DDD-2026-Rio-Grande-1.pdf" → "Rio Grande"
    if ($pdfUrl) {
        $filename = pathinfo(parse_url($pdfUrl, PHP_URL_PATH), PATHINFO_FILENAME);
        $filename = str_replace(['-', '_'], ' ', $filename);
        // Remove palavras comuns de regulamento
        $filename = preg_replace('/\b(Regulamento|Regul|General|Geral|Percurso|Corrida|Rustica|Rústica|Caminhada|Noturna|DDD|Run|Treinao|Treinão|Meia|Maratona|DDD|2026|20\d\d|Sesc|RS|1|2|3|4|5|6|7|8|9|10|compressed)\b/i', '', $filename);
        $filename = trim(preg_replace('/\s+/', ' ', $filename));

        // Verifica se o que sobrou é uma cidade conhecida
        $cidades = [
            'Porto Alegre', 'Caxias do Sul', 'Pelotas', 'Santa Maria', 'Passo Fundo', 'Rio Grande',
            'Bento Gonçalves', 'Erechim', 'Cruz Alta', 'Canoas', 'Lajeado', 'Santa Cruz do Sul',
            'Uruguaiana', 'Ijuí', 'Bagé', 'Santana do Livramento', 'Carazinho', 'Alegrete',
            'Farroupilha', 'Novo Hamburgo', 'Gravataí', 'Viamão', 'Guaíba', 'Tapejara', 'Paraí',
            'Serafina Corrêa', 'Carlos Barbosa', 'Garibaldi', 'Canela', 'Gramado', 'Torres',
            'Capão da Canoa', 'Tramandaí', 'Nonoai', 'Charrua', 'Caravaggio', 'São Leopoldo',
            'Rio Pardo', 'Cachoeirinha', 'Alvorada', 'Eldorado do Sul', 'Osório', 'Camaquã',
        ];

        foreach ($cidades as $cidade) {
            if (mb_stripos($filename, $cidade, 0, 'UTF-8') !== false) return "$cidade/RS";
            // Tenta com a versão sem acentos do PDF
            $cidadeSimple = iconv('UTF-8', 'ASCII//TRANSLIT', $cidade);
            if (mb_stripos($filename, $cidadeSimple, 0, 'UTF-8') !== false) return "$cidade/RS";
        }

        // Se o restante do filename tem pelo menos 4 chars, pode ser uma cidade menor
        $parts = array_filter(explode(' ', $filename), fn($p) => mb_strlen($p, 'UTF-8') >= 4);
        if (count($parts) >= 1 && count($parts) <= 3) {
            $candidate = ucwords(mb_strtolower(implode(' ', $parts), 'UTF-8'));
            if (mb_strlen($candidate, 'UTF-8') >= 4 && mb_strlen($candidate, 'UTF-8') < 35) {
                return "$candidate/RS";
            }
        }
    }

    // 4. Dicionário de cidades no próprio nome do evento (fallback)
    $cidades = [
        'Porto Alegre', 'Caxias do Sul', 'Pelotas', 'Santa Maria', 'Passo Fundo', 'Rio Grande',
        'Bento Gonçalves', 'Erechim', 'Cruz Alta', 'Canoas', 'Lajeado', 'Santa Cruz do Sul',
        'Uruguaiana', 'Ijuí', 'Bagé', 'Santana do Livramento', 'Carazinho', 'Alegrete',
        'Farroupilha', 'Novo Hamburgo', 'Gravataí', 'Viamão', 'Guaíba', 'Tapejara', 'Paraí',
        'Serafina Corrêa', 'Carlos Barbosa', 'Garibaldi', 'Canela', 'Gramado', 'Torres',
        'Capão da Canoa', 'Tramandaí', 'Nonoai',
    ];

    foreach ($cidades as $c) {
        if (mb_stripos($nomeLower, mb_strtolower($c, 'UTF-8'), 0, 'UTF-8') !== false) return "$c/RS";
    }

    return 'Rio Grande do Sul/RS';
}

function extrairKms(string $texto): string {
    preg_match_all('/(\d+)\s*k(?:m)?/i', $texto, $matches);
    if (!empty($matches[1])) {
        $nums = array_unique($matches[1]);
        $nums = array_filter($nums, fn($n) => $n >= 1 && $n <= 100);
        sort($nums, SORT_NUMERIC);
        return implode(', ', array_map(fn($n) => $n . 'km', $nums));
    }
    if (stripos($texto, 'Meia Maratona') !== false) return '21km';
    if (stripos($texto, 'Maratona') !== false && stripos($texto, 'Meia') === false) return '42km';
    return '';
}

// ----------------------------------------------------------
// PASSO 3 — Extração dos Eventos
// ----------------------------------------------------------
$eventosExtraidos = [];
$blocos = $xpath->query('//blockquote[.//strong]');

foreach ($blocos as $bloco) {
    $textoCompleto = trim($bloco->textContent);

    $primeiroStrong = $xpath->query('.//strong', $bloco)->item(0);
    if (!$primeiroStrong) continue;

    $nomeEData = trim($primeiroStrong->textContent);

    $nome    = '';
    $dataSql = null;

    if (preg_match('/(.+?)Data:\s*(\d{2})\/(\d{2})(\/\d{4})?/is', $nomeEData, $m)) {
        $nome = trim($m[1]);
        $dia  = $m[2];
        $mes  = $m[3];
        $ano  = isset($m[4]) ? str_replace('/', '', $m[4]) : date('Y');

        $dataTeste = "$ano-$mes-$dia";
        if (!isset($m[4]) && strtotime($dataTeste) < strtotime('today')) {
            $ano++;
            $dataTeste = "$ano-$mes-$dia";
        }
        $dataSql = $dataTeste;
    }

    if (strlen($nome) < 5 || !$dataSql) continue;

    // Pega todos os links do bloco
    $linkRegulamento = '';
    $linkInscricao   = '';

    $links = $xpath->query('.//a', $bloco);
    foreach ($links as $l) {
        $href    = trim($l->getAttribute('href'));
        $anchor  = mb_strtolower(trim($l->textContent), 'UTF-8');

        if (empty($href)) continue;

        // PDF de regulamento/percurso — link específico do evento
        if (strpos($href, '.pdf') !== false && empty($linkRegulamento)) {
            $linkRegulamento = $href;
        }

        // Inscrições no e-commerce (genérico, menos útil, mas aqui como fallback)
        if (strpos($anchor, 'inscri') !== false && empty($linkInscricao)) {
            $linkInscricao = $href;
        }
    }

    // O link mais útil para o usuário:
    // 1. Se o link de inscrição não for o genérico de catálogo, usa ele
    // 2. Senão, usa o PDF do regulamento (link ESPECÍFICO do evento)
    // 3. Senão, usa o link genérico do e-commerce
    $linkGenericoEcommerce = 'ecommerce.sesc-rs.com.br/ecommerce.catalogoprodutos.aspx?EVE';
    
    if (!empty($linkInscricao) && strpos($linkInscricao, $linkGenericoEcommerce) === false) {
        $linkOficial = $linkInscricao;
    } elseif (!empty($linkRegulamento)) {
        $linkOficial = $linkRegulamento; // Link direto do PDF (específico do evento!)
    } elseif (!empty($linkInscricao)) {
        $linkOficial = $linkInscricao;
    } else {
        $linkOficial = 'https://www.sesc-rs.com.br/esporte/corridas/';
    }

    // Extrai cidade passando o PDF do regulamento para ajudar na identificação
    $cidade = extrairCidade($nome, $linkRegulamento);

    $eventosExtraidos[] = [
        'nome'         => $nome,
        'data_evento'  => $dataSql,
        'cidade'       => $cidade,
        'distancias'   => extrairKms($textoCompleto),
        'link_oficial' => $linkOficial,
        'banner'       => SESC_LOGO,
        'descricao'    => "Evento de corrida Sesc/RS: $nome. Consulte o regulamento e faça sua inscrição no link oficial.",
    ];
}

echo "Eventos encontrados: " . count($eventosExtraidos) . "\n\n";

// ----------------------------------------------------------
// PASSO 4 — Salvar no Banco com Deduplicação
// ----------------------------------------------------------
$inseridos = 0;
$ignorados = 0;
$passados  = 0;

foreach ($eventosExtraidos as $ev) {
    if (strtotime($ev['data_evento']) < strtotime('today')) {
        $passados++;
        continue;
    }

    $check = $pdo->prepare("SELECT id FROM eventos WHERE LOWER(TRIM(nome)) = LOWER(TRIM(?)) AND data_evento = ?");
    $check->execute([$ev['nome'], $ev['data_evento']]);

    if ($check->fetch()) {
        echo "  - Duplicado: {$ev['nome']} ({$ev['data_evento']})\n";
        $ignorados++;
        continue;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO eventos (usuario_id, nome, cidade, data_evento, distancias, descricao, link_oficial, banner, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativo')
        ");
        $stmt->execute([
            $usuario_id,
            $ev['nome'],
            $ev['cidade'],
            $ev['data_evento'],
            $ev['distancias'],
            $ev['descricao'],
            $ev['link_oficial'],
            $ev['banner'],
        ]);
        echo "  + Inserido: {$ev['nome']} | {$ev['data_evento']} | {$ev['cidade']}\n";
        echo "    Link: {$ev['link_oficial']}\n";
        $inseridos++;
    } catch (Exception $e) {
        echo "  ! Erro ao inserir {$ev['nome']}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Concluído ===\n";
echo "Inseridos: $inseridos\n";
echo "Duplicados ignorados: $ignorados\n";
echo "Eventos passados ignorados: $passados\n";
