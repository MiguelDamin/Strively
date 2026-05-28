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

// Logo oficial do Sesc como fallback de banner
define('SESC_LOGO', 'https://www.sesc-rs.com.br/wp-content/themes/sescrs/img/logos/sesc.png');

echo "=== Scraper Sesc RS Corridas ===\n";
echo "Iniciando: " . date('d/m/Y H:i:s') . "\n";
echo "Autor ID: $usuario_id\n\n";

// ----------------------------------------------------------
// PASSO 1 — Baixa o HTML do site do Sesc (Usando cURL do sistema via shell_exec)
// ----------------------------------------------------------
function fetchHtml($url) {
    $command = 'curl -sL -A "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36" ' . escapeshellarg($url);
    $html = shell_exec($command);
    return $html;
}

$html = fetchHtml('https://www.sesc-rs.com.br/esporte/corridas/');

if (!$html) {
  echo "ERRO: Não foi possível acessar o site do Sesc via shell_exec(curl).\n";
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

function extrairCidade(string $nome): string {
    // Tenta achar cidade no nome (padrão: "em Cidade", "Sesc Cidade", "– Cidade")
    if (preg_match('/em\s+([A-Z][a-zÀ-ÿ]+(?:\s+[A-Z][a-zÀ-ÿ]+)*)/i', $nome, $m)) return trim($m[1]) . '/RS';
    if (preg_match('/Sesc\s+([A-Z][a-zÀ-ÿ]+(?:\s+[A-Z][a-zÀ-ÿ]+)*)/i', $nome, $m)) return trim($m[1]) . '/RS';
    
    // Lista comum
    $cidades = ['Porto Alegre', 'Caxias do Sul', 'Bento Gonçalves', 'Pelotas', 'Santa Maria', 'Passo Fundo', 'Erechim', 'Cruz Alta', 'Gramado', 'Canela', 'Lajeado', 'Torres', 'Capão da Canoa'];
    foreach ($cidades as $c) {
        if (stripos($nome, $c) !== false) return "$c/RS";
    }
    
    return 'Rio Grande do Sul/RS';
}

function extrairKms(string $texto): string {
    // Procura padrões como 3k, 5km, 10 km, 21km
    preg_match_all('/(\d+)\s*k/i', $texto, $matches);
    if (!empty($matches[0])) {
        $nums = array_unique(array_map(function($m) {
            preg_match('/\d+/', $m, $n);
            return $n[0] . 'km';
        }, $matches[0]));
        sort($nums, SORT_NATURAL);
        return implode(', ', $nums);
    }
    if (stripos($texto, 'Meia Maratona') !== false) return '21km';
    if (stripos($texto, 'Maratona') !== false && stripos($texto, 'Meia') === false) return '42km';
    return '';
}

// ----------------------------------------------------------
// PASSO 3 — Extração dos Eventos
// Os eventos estão dentro de blocos <blockquote> ou <p> com <strong>
// ----------------------------------------------------------
$eventosExtraidos = [];

// Busca todos os <blockquote> que contêm um <strong>
$blocos = $xpath->query('//blockquote[.//strong]');

foreach ($blocos as $bloco) {
    $textoCompleto = trim($bloco->textContent);
    
    // O primeiro <strong> costuma ter o Nome e a Data
    $primeiroStrong = $xpath->query('.//strong', $bloco)->item(0);
    if (!$primeiroStrong) continue;
    
    $nomeEData = trim($primeiroStrong->textContent);
    
    // Tenta separar Nome e Data
    // Caso 1: "3ª MEIA MARATONA... \n Data: 07/11/2026"
    $nome = $nomeEData;
    $dataSql = null;
    
    // Adicionado modificador /s para suportar quebras de linha no strong
    if (preg_match('/(.*)Data:\s*(\d{2})\/(\d{2})(\/\d{4})?/is', $nomeEData, $m)) {
        $nome = trim(str_ireplace(['Data:', 'Etapa:'], '', $m[1]));
        $dia = $m[2];
        $mes = $m[3];
        $ano = isset($m[4]) ? str_replace('/', '', $m[4]) : date('Y');
        
        $dataTeste = "$ano-$mes-$dia";
        if (!isset($m[4]) && strtotime($dataTeste) < strtotime('today')) {
            $ano++;
            $dataTeste = "$ano-$mes-$dia";
        }
        $dataSql = $dataTeste;
    }
    
    if (strlen($nome) < 5 || !$dataSql) continue;

    // Link de inscrições
    $linkOficial = 'https://www.sesc-rs.com.br/esporte/corridas/';
    $links = $xpath->query('.//a', $bloco);
    foreach ($links as $l) {
        $href = $l->getAttribute('href');
        $anchorText = strtolower($l->textContent);
        if (strpos($href, 'ecommerce.sesc-rs') !== false || strpos($anchorText, 'inscri') !== false) {
            $linkOficial = $href;
            break;
        }
    }
    
    $eventosExtraidos[] = [
        'nome'         => $nome,
        'data_evento'  => $dataSql,
        'cidade'       => extrairCidade($nome),
        'distancias'   => extrairKms($textoCompleto),
        'link_oficial' => $linkOficial,
        'banner'       => SESC_LOGO,
        'descricao'    => "Circuito de corridas Sesc/RS. Participe da etapa " . $nome . ". Inscrições e regulamento disponíveis no link oficial."
    ];
}

echo "Eventos encontrados: " . count($eventosExtraidos) . "\n";

// ----------------------------------------------------------
// PASSO 4 — Salvar no Banco (Deduplicação)
// ----------------------------------------------------------
$inseridos = 0;
$ignorados = 0;

foreach ($eventosExtraidos as $ev) {
    // Pula passados
    if (strtotime($ev['data_evento']) < strtotime('today')) {
        $ignorados++;
        continue;
    }

    // Verifica duplicata por Nome + Data
    $check = $pdo->prepare("SELECT id FROM eventos WHERE LOWER(nome) = LOWER(?) AND data_evento = ?");
    $check->execute([$ev['nome'], $ev['data_evento']]);
    
    if ($check->fetch()) {
        echo "  - Ignorado (já existe): {$ev['nome']} ({$ev['data_evento']})\n";
        $ignorados++;
        continue;
    }

    // Insere
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
            $ev['banner']
        ]);
        
        echo "  + Inserido: {$ev['nome']} | {$ev['data_evento']} | {$ev['cidade']}\n";
        $inseridos++;
    } catch (Exception $e) {
        echo "  ! Erro ao inserir {$ev['nome']}: " . $e->getMessage() . "\n";
    }
}

echo "\nFim do processamento.\n";
echo "Inseridos: $inseridos\n";
echo "Ignorados: $ignorados\n";
