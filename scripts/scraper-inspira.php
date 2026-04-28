<?php
// ==========================================================
// STRIVELY — scripts/scraper-inspira.php
// Importa automaticamente os próximos eventos da Inspira Eventos
// Site: https://inspiraeventos.com.br
//
// COMO RODAR:
// Manual:  php /var/www/html/scripts/scraper-inspira.php
// Cron:    0 8 * * 1 php /var/www/html/scripts/scraper-inspira.php
//          (roda toda segunda-feira às 08h)
//
// ANTES DE RODAR PELA PRIMEIRA VEZ:
// 1. Limpe eventos duplicados do banco se necessário
// 2. Confirme o USUARIO_BOT_ID correto abaixo
// ==========================================================

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/conexao.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// ID do usuário bot/sistema no banco (usuário "Inspira Eventos" ou similar)
define('USUARIO_BOT_ID', 2);

// Meses em português para conversão de datas
$MESES = [
  'janeiro'=>'01','fevereiro'=>'02','março'=>'03','marco'=>'03',
  'abril'=>'04','maio'=>'05','junho'=>'06','julho'=>'07',
  'agosto'=>'08','setembro'=>'09','outubro'=>'10',
  'novembro'=>'11','dezembro'=>'12',
];

echo "=== Scraper Inspira Eventos ===\n";
echo "Iniciando: " . date('d/m/Y H:i:s') . "\n\n";

// ----------------------------------------------------------
// PASSO 1 — Baixa o HTML do site da Inspira
// ----------------------------------------------------------
$ctx = stream_context_create([
  'http' => [
    'timeout'    => 30,
    'user_agent' => 'Mozilla/5.0 (compatible; StrivelyScraper/1.0)',
  ]
]);

$html = @file_get_contents('https://inspiraeventos.com.br/', false, $ctx);

if (!$html) {
  echo "ERRO: Não foi possível acessar o site da Inspira.\n";
  exit(1);
}

echo "HTML baixado: " . number_format(strlen($html)) . " bytes\n\n";

// ----------------------------------------------------------
// PASSO 2 — Parseia o HTML
// ----------------------------------------------------------
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
libxml_clear_errors();
$xpath = new DOMXPath($dom);

// ----------------------------------------------------------
// PASSO 3 — Encontra onde começa e termina a seção PRÓXIMOS EVENTOS
// A seção de realizados começa com h2 "eventos Realizados"
// Só queremos os eventos ANTES desse h2
// ----------------------------------------------------------
function textoLimpo(DOMNode $node): string {
  return trim(preg_replace('/\s+/', ' ', $node->textContent));
}

function converterData(string $texto, array $meses): ?string {
  $texto = strtolower(trim($texto));
  // Remove "de" entre partes: "19 de abril de 2026" → "19 abril 2026"
  $texto = preg_replace('/\bde\b/', '', $texto);
  $texto = preg_replace('/\s+/', ' ', trim($texto));

  // Padrão: "19 abril 2026"
  if (preg_match('/(\d{1,2})\s+(\w+)\s+(\d{4})/', $texto, $m)) {
    $dia = str_pad($m[1], 2, '0', STR_PAD_LEFT);
    $mes = $meses[$m[2]] ?? null;
    $ano = $m[3];
    if ($mes) return "$ano-$mes-$dia";
  }

  // Padrão: "19 abril" (sem ano)
  if (preg_match('/(\d{1,2})\s+(\w+)/', $texto, $m)) {
    $dia = str_pad($m[1], 2, '0', STR_PAD_LEFT);
    $mes = $meses[$m[2]] ?? null;
    if ($mes) {
      $ano  = date('Y');
      $data = "$ano-$mes-$dia";
      if (strtotime($data) < strtotime('today')) {
        $ano  = (int)date('Y') + 1;
        $data = "$ano-$mes-$dia";
      }
      return $data;
    }
  }

  return null;
}

function extrairCidade(string $nome): string {
  $cidades = [
    'nonoai'           => 'Nonoai/RS',
    'charrua'          => 'Charrua/RS',
    'erechim'          => 'Erechim/RS',
    'getúlio vargas'   => 'Getúlio Vargas/RS',
    'getulio vargas'   => 'Getúlio Vargas/RS',
    'marcelino ramos'  => 'Marcelino Ramos/RS',
    'chapecó'          => 'Chapecó/SC',
    'chapeco'          => 'Chapecó/SC',
    'rodeio bonito'    => 'Rodeio Bonito/RS',
    'estação'          => 'Estação/RS',
    'estacao'          => 'Estação/RS',
    'sertão'           => 'Sertão/RS',
    'sertao'           => 'Sertão/RS',
    'erebango'         => 'Erebango/RS',
    'viadutos'         => 'Viadutos/RS',
    'mariano moro'     => 'Mariano Moro/RS',
    'itá'              => 'Itá/SC',
    'ita'              => 'Itá/SC',
    'sarandi'          => 'Sarandi/RS',
  ];

  $nomeLower = strtolower($nome);
  foreach ($cidades as $chave => $cidade) {
    if (strpos($nomeLower, $chave) !== false) {
      return $cidade;
    }
  }
  return 'Rio Grande do Sul/RS';
}

function extrairDistancias(string $texto): string {
  // Extrai padrões como "5km", "10 km", "21km", "42km", "8k"
  preg_match_all('/(\d+)\s*km?/i', $texto, $matches);
  if (!empty($matches[0])) {
    $distancias = array_unique(array_map(fn($d) => strtolower(trim($d)), $matches[0]));
    // Normaliza para "Xkm"
    $normalizadas = array_map(function($d) {
      preg_match('/(\d+)/', $d, $m);
      return $m[1] . 'km';
    }, $distancias);
    sort($normalizadas, SORT_NATURAL);
    return implode(', ', $normalizadas);
  }
  return '';
}

// ----------------------------------------------------------
// PASSO 4 — Extrai cada evento como um bloco coeso
// Estratégia: itera sobre todos os elementos do body em ordem
// e monta blocos de evento agrupando: imagem + nome + data + link
// ----------------------------------------------------------

// Encontra o nó do h2 "eventos Realizados" para saber onde parar
$todosH2 = $xpath->query('//h2');
$nodoPararEm = null;
foreach ($todosH2 as $h2) {
  if (stripos($h2->textContent, 'realizad') !== false) {
    $nodoPararEm = $h2;
    break;
  }
}

// Pega todos os blocos de figura/imagem com link na seção de próximos eventos
// Cada evento começa com um link contendo uma imagem
$todosLinks = $xpath->query('//a[.//img[contains(@src,"wp-content/uploads")]]');

$eventosExtraidos = [];

foreach ($todosLinks as $link) {
  // Se passamos da seção de realizados, para
  if ($nodoPararEm !== null) {
    // Verifica se este link está depois do h2 de realizados no DOM
    $posLink = $link->getLineNo();
    $posParar = $nodoPararEm->getLineNo();
    if ($posLink > $posParar) {
      break;
    }
  }

  $href = trim($link->getAttribute('href'));

  // Ignora links que não são da Inspira ou são âncoras
  if (empty($href) || $href === '#') continue;
  if (strpos($href, 'fotto.com.br') !== false) continue;
  if (strpos($href, 'focoradical') !== false) continue;
  if (strpos($href, 'drive.google') !== false) continue;
  if (strpos($href, 'facebook') !== false) continue;
  if (strpos($href, 'instagram') !== false) continue;
  if (strpos($href, 'wa.me') !== false) continue;

  // Pega a imagem dentro do link
  $imgNode = $xpath->query('.//img', $link)->item(0);
  if (!$imgNode) continue;

  $bannerSrc = $imgNode->getAttribute('src');

  // Remove sufixo de tamanho do WordPress ex: -1024x1024.jpg → .jpg
  $bannerOriginal = preg_replace('/-\d+x\d+(\.[a-z]+)$/i', '$1', $bannerSrc);

  // Agora busca os h6 irmãos/vizinhos após este link para pegar nome e data
  // O pai do link geralmente contém os h6 no mesmo nível ou próximo
  $parent = $link->parentNode;

  // Sobe até encontrar um container com h6
  $nivelAcima = 0;
  $containerComH6 = null;
  $noAtual = $link;
  while ($nivelAcima < 5 && $noAtual->parentNode) {
    $noAtual = $noAtual->parentNode;
    $h6sNoContainer = $xpath->query('.//h6', $noAtual);
    if ($h6sNoContainer->length > 0) {
      $containerComH6 = $noAtual;
      break;
    }
    $nivelAcima++;
  }

  if (!$containerComH6) continue;

  // Extrai todos os h6 desse container
  $h6sDoBloco = $xpath->query('.//h6', $containerComH6);
  $textos = [];
  foreach ($h6sDoBloco as $h6) {
    $t = textoLimpo($h6);
    if (!empty($t)) $textos[] = $t;
  }

  if (empty($textos)) continue;

  // Identifica qual é nome e qual é data
  $nome      = null;
  $dataTexto = null;
  $mesesNomes = ['janeiro','fevereiro','março','marco','abril','maio','junho',
                 'julho','agosto','setembro','outubro','novembro','dezembro'];

  foreach ($textos as $texto) {
    $ehData = false;
    foreach ($mesesNomes as $mes) {
      if (stripos($texto, $mes) !== false && preg_match('/\d/', $texto)) {
        $ehData = true;
        break;
      }
    }
    if ($ehData) {
      if (!$dataTexto) $dataTexto = $texto;
    } else {
      if (!$nome) $nome = $texto;
    }
  }

  if (!$nome || !$dataTexto) continue;

  // Tenta extrair distâncias do texto completo do bloco
  $textoCompleto = textoLimpo($containerComH6);
  $distancias    = extrairDistancias($textoCompleto);

  // Determina o link de informações
  // Prefere links da própria Inspira sobre smartevento.app
  $linkInfo = $href;
  if (strpos($href, 'smartevento.app') !== false) {
    // Tenta achar outro link de informações no mesmo container
    $outrosLinks = $xpath->query('.//a', $containerComH6);
    foreach ($outrosLinks as $ol) {
      $olHref = $ol->getAttribute('href');
      if (strpos($olHref, 'inspiraeventos.com.br') !== false &&
          strpos(strtolower($ol->textContent), 'informa') !== false) {
        $linkInfo = $olHref;
        break;
      }
    }
    // Se não achou link de informações, usa a home da Inspira
    if (strpos($linkInfo, 'smartevento.app') !== false) {
      $linkInfo = 'https://inspiraeventos.com.br';
    }
  }

  $eventosExtraidos[] = [
    'nome'         => ucwords(strtolower(trim($nome))),
    'data_texto'   => $dataTexto,
    'banner'       => $bannerOriginal,
    'link_oficial' => $linkInfo,
    'distancias'   => $distancias,
    'cidade'       => extrairCidade($nome),
    'descricao'    => 'Evento organizado pela Inspira Eventos — ' . trim($nome) . '. Acesse o site oficial para informações completas, inscrições e detalhes do percurso.',
  ];
}

// Remove duplicatas pelo nome
$nomesVistos = [];
$eventosFinal = [];
foreach ($eventosExtraidos as $ev) {
  $chave = strtolower(trim($ev['nome']));
  if (!in_array($chave, $nomesVistos)) {
    $nomesVistos[] = $chave;
    $eventosFinal[] = $ev;
  }
}

// ----------------------------------------------------------
// PASSO 5 — Converte datas e filtra só eventos futuros
// ----------------------------------------------------------
$eventosParaInserir = [];
foreach ($eventosFinal as $ev) {
  $dataConvertida = converterData($ev['data_texto'], $MESES);
  if (!$dataConvertida) {
    echo "  AVISO: Não conseguiu converter data '{$ev['data_texto']}' do evento '{$ev['nome']}'\n";
    continue;
  }

  // Pula eventos que já passaram
  if (strtotime($dataConvertida) < strtotime('today')) {
    echo "  PASSADO (ignorado): {$ev['nome']} — $dataConvertida\n";
    continue;
  }

  $ev['data'] = $dataConvertida;
  $eventosParaInserir[] = $ev;
}

echo "\nEventos futuros encontrados: " . count($eventosParaInserir) . "\n";
echo str_repeat('-', 50) . "\n";

// ----------------------------------------------------------
// PASSO 6 — Salva no banco evitando duplicatas
// Verifica pelo link_oficial para ser mais preciso que pelo nome
// ----------------------------------------------------------
$inseridos = 0;
$ignorados = 0;
$erros     = 0;

foreach ($eventosParaInserir as $ev) {

  // Verifica duplicata por link_oficial + data (mais preciso que só o nome)
  $checkLink = $pdo->prepare("
    SELECT id FROM eventos
    WHERE link_oficial = ? AND data_evento = ?
  ");
  $checkLink->execute([$ev['link_oficial'], $ev['data']]);

  if ($checkLink->fetch()) {
    echo "  IGNORADO (link já existe): {$ev['nome']} — {$ev['data']}\n";
    $ignorados++;
    continue;
  }

  // Verifica duplicata por nome + data
  $checkNome = $pdo->prepare("
    SELECT id FROM eventos
    WHERE LOWER(TRIM(nome)) = LOWER(TRIM(?)) AND data_evento = ?
  ");
  $checkNome->execute([$ev['nome'], $ev['data']]);

  if ($checkNome->fetch()) {
    echo "  IGNORADO (nome já existe): {$ev['nome']} — {$ev['data']}\n";
    $ignorados++;
    continue;
  }

  // Insere
  try {
    $stmt = $pdo->prepare("
      INSERT INTO eventos
        (usuario_id, nome, cidade, data_evento, distancias, descricao, link_oficial, banner, status)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativo')
    ");
    $stmt->execute([
      USUARIO_BOT_ID,
      $ev['nome'],
      $ev['cidade'],
      $ev['data'],
      $ev['distancias'],
      $ev['descricao'],
      $ev['link_oficial'],
      $ev['banner'],
    ]);

    echo "  INSERIDO ✓ {$ev['nome']}\n";
    echo "    Data:      {$ev['data']}\n";
    echo "    Cidade:    {$ev['cidade']}\n";
    echo "    Distâncias:{$ev['distancias']}\n";
    echo "    Banner:    {$ev['banner']}\n";
    echo "    Link:      {$ev['link_oficial']}\n\n";

    $inseridos++;

  } catch (PDOException $e) {
    echo "  ERRO ao inserir {$ev['nome']}: " . $e->getMessage() . "\n";
    $erros++;
  }
}

// ----------------------------------------------------------
// RESUMO
// ----------------------------------------------------------
echo str_repeat('=', 50) . "\n";
echo "RESULTADO FINAL\n";
echo str_repeat('=', 50) . "\n";
echo "Inseridos:  $inseridos\n";
echo "Ignorados:  $ignorados (já existiam)\n";
echo "Erros:      $erros\n";
echo "Finalizado: " . date('d/m/Y H:i:s') . "\n";