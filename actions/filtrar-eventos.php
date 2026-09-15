<?php
// ==========================================================
// STRIVELY — actions/filtrar-eventos.php
// Endpoint AJAX para filtragem de eventos por proximidade e paginação infinita
// ==========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../scripts/scraper-eventos.php';

$modo   = trim($_GET['modo'] ?? ($_POST['modo'] ?? 'localizacao'));
$lat    = isset($_REQUEST['lat']) && is_numeric($_REQUEST['lat']) ? (float)$_REQUEST['lat'] : null;
$lng    = isset($_REQUEST['lng']) && is_numeric($_REQUEST['lng']) ? (float)$_REQUEST['lng'] : null;
$cidade = trim($_GET['cidade'] ?? ($_POST['cidade'] ?? ''));
$raio   = isset($_REQUEST['raio']) && is_numeric($_REQUEST['raio']) ? (float)$_REQUEST['raio'] : 200.0;
$limite = isset($_REQUEST['limite']) && is_numeric($_REQUEST['limite']) ? max(1, min(100, (int)$_REQUEST['limite'])) : 30;
$offset = isset($_REQUEST['offset']) && is_numeric($_REQUEST['offset']) ? max(0, (int)$_REQUEST['offset']) : 0;

$cidadeNome = $cidade;

// Se modo for localização e lat/lng não vieram na requisição, tenta pegar do usuário logado
if ($modo === 'localizacao' && ($lat === null || $lng === null)) {
    if (isset($_SESSION['id'])) {
        $stUser = $pdo->prepare("SELECT lat, lng, cidade_manual, cidade FROM usuarios WHERE id = ?");
        $stUser->execute([$_SESSION['id']]);
        $uRow = $stUser->fetch();
        if ($uRow && !empty($uRow['lat']) && !empty($uRow['lng'])) {
            $lat = (float)$uRow['lat'];
            $lng = (float)$uRow['lng'];
            $cidadeNome = $uRow['cidade_manual'] ?: $uRow['cidade'];
        }
    }
}

// Se modo for cidade e lat/lng não vieram, geocodifica a cidade
if ($modo === 'cidade' && ($lat === null || $lng === null)) {
    if (empty($cidade)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => 'Por favor, digite o nome de uma cidade.'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    [$geoLat, $geoLng] = geocodificarCidade($cidade);
    if ($geoLat === null || $geoLng === null) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error'   => 'Não encontramos a localização para "' . htmlspecialchars($cidade) . '". Tente outra cidade.'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    $lat = $geoLat;
    $lng = $geoLng;
}

$eventos = [];
$total = 0;

try {
    if (($modo === 'localizacao' || $modo === 'cidade') && $lat !== null && $lng !== null) {
        // 1. Contagem total de eventos dentro do raio
        $countStmt = $pdo->prepare("
            SELECT count(*) FROM (
                SELECT e.id,
                    (6371 * acos(
                        LEAST(1.0, GREATEST(-1.0,
                            cos(radians(:lat)) * cos(radians(e.lat)) *
                            cos(radians(e.lng) - radians(:lng)) +
                            sin(radians(:lat)) * sin(radians(e.lat))
                        ))
                    )) AS distancia_km
                FROM eventos e
                WHERE e.status = 'ativo'
                  AND e.lat IS NOT NULL
                  AND e.lng IS NOT NULL
                  AND e.data_evento > (CURRENT_TIMESTAMP AT TIME ZONE 'America/Sao_Paulo')::date
            ) sub
            WHERE distancia_km <= :raio
        ");
        $countStmt->execute([
            ':lat'  => $lat,
            ':lng'  => $lng,
            ':raio' => $raio
        ]);
        $total = (int)$countStmt->fetchColumn();

        // 2. Query paginada com fórmula de Haversine
        $stmt = $pdo->prepare("
            SELECT * FROM (
                SELECT e.*, u.nome AS autor_nome, u.foto AS autor_foto,
                    (6371 * acos(
                        LEAST(1.0, GREATEST(-1.0,
                            cos(radians(:lat)) * cos(radians(e.lat)) *
                            cos(radians(e.lng) - radians(:lng)) +
                            sin(radians(:lat)) * sin(radians(e.lat))
                        ))
                    )) AS distancia_km
                FROM eventos e
                LEFT JOIN usuarios u ON e.usuario_id = u.id
                WHERE e.status = 'ativo'
                  AND e.lat IS NOT NULL
                  AND e.lng IS NOT NULL
                  AND e.data_evento > (CURRENT_TIMESTAMP AT TIME ZONE 'America/Sao_Paulo')::date
            ) sub
            WHERE distancia_km <= :raio
            ORDER BY distancia_km ASC, data_evento ASC
            LIMIT " . (int)$limite . " OFFSET " . (int)$offset . "
        ");
        $stmt->execute([
            ':lat'  => $lat,
            ':lng'  => $lng,
            ':raio' => $raio
        ]);
        $eventos = $stmt->fetchAll();

    } else {
        // Modo 'todos' ou fallback quando localização não estiver disponível
        $modo = ($modo === 'localizacao') ? 'localizacao_sem_gps' : 'todos';

        // 1. Contagem total
        $countStmt = $pdo->query("
            SELECT count(*) FROM eventos e
            WHERE e.status = 'ativo'
              AND e.data_evento > (CURRENT_TIMESTAMP AT TIME ZONE 'America/Sao_Paulo')::date
        ");
        $total = (int)$countStmt->fetchColumn();

        // 2. Query paginada de todos
        $stmt = $pdo->prepare("
            SELECT e.*, u.nome AS autor_nome, u.foto AS autor_foto
            FROM eventos e
            LEFT JOIN usuarios u ON e.usuario_id = u.id
            WHERE e.status = 'ativo'
              AND e.data_evento > (CURRENT_TIMESTAMP AT TIME ZONE 'America/Sao_Paulo')::date
            ORDER BY e.data_evento ASC
            LIMIT " . (int)$limite . " OFFSET " . (int)$offset . "
        ");
        $stmt->execute();
        $eventos = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erro ao consultar banco de dados: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Renderiza o HTML dos cards para devolução pronta ao front
ob_start();
if (!empty($eventos)):
    $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    foreach ($eventos as $evento):
        $distKm = isset($evento['distancia_km']) ? round((float)$evento['distancia_km']) : null;
        $dataEv = new DateTime($evento['data_evento']);
        $dataFormatada = $dataEv->format('d') . ' de ' . $meses[(int)$dataEv->format('m') - 1] . ' de ' . $dataEv->format('Y');
        $distanciasArr = array_filter(array_map('trim', explode(',', $evento['distancias'] ?? '')));
        $autorEventoId = (int)($evento['usuario_id'] ?? 0);
        $linkAutor = (isset($_SESSION['id']) && $_SESSION['id'] === $autorEventoId)
            ? '/pages/perfil.php'
            : '/pages/perfil-publico.php?id=' . $autorEventoId;
        $autorPrimeiroNome = !empty($evento['autor_nome']) ? htmlspecialchars(explode(' ', $evento['autor_nome'])[0]) : '';
        ?>
        <div class="evento-card" onclick="confirmarRedirecionamento('<?= htmlspecialchars(addslashes($evento['link_oficial'])) ?>')">
            <div class="evento-banner-wrap">
                <?php if (!empty($evento['banner'])): ?>
                    <img
                        src="<?= htmlspecialchars($evento['banner']) ?>"
                        alt="<?= htmlspecialchars($evento['nome']) ?>"
                        class="evento-banner"
                        loading="lazy"
                        onerror="this.parentElement.innerHTML='<div class=\'evento-banner-placeholder\'><?= htmlspecialchars(addslashes($evento['nome'])) ?></div>'"
                    >
                <?php else: ?>
                    <div class="evento-banner-placeholder"><?= htmlspecialchars($evento['nome']) ?></div>
                <?php endif; ?>
            </div>

            <div class="evento-body">
                <h3 class="evento-nome"><?= htmlspecialchars($evento['nome']) ?></h3>

                <div class="evento-meta">
                    <div class="evento-info" style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                        <span><span style="font-size:14px;">📍</span> <?= htmlspecialchars($evento['cidade']) ?></span>
                        <?php if ($distKm !== null): ?>
                            <span class="evento-dist-badge">
                                <?= $distKm === 0 ? 'Na mesma cidade' : "a ~{$distKm} km" ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="evento-info">
                        <span style="font-size:14px;">📅</span> <?= $dataFormatada ?>
                    </div>
                </div>

                <div class="evento-distancias">
                    <?php foreach ($distanciasArr as $dist): ?>
                        <span class="distancia-badge"><?= htmlspecialchars($dist) ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="evento-divider"></div>

                <div class="card-footer">
                    <?php if (!empty($evento['autor_nome'])): ?>
                        <a href="<?= htmlspecialchars($linkAutor, ENT_QUOTES, 'UTF-8') ?>" 
                           class="card-autor" 
                           style="text-decoration:none;color:inherit;transition:opacity 0.2s;" 
                           onclick="event.stopPropagation()"
                           onmouseover="this.style.opacity='0.75'"
                           onmouseout="this.style.opacity='1'">
                            <?php if (!empty($evento['autor_foto'])): ?>
                                <img src="<?= htmlspecialchars($evento['autor_foto']) ?>" alt="autor"
                                     style="width:28px;height:28px;border-radius:50%;object-fit:cover;">
                            <?php else: ?>
                                <span style="font-size:16px;">👤</span>
                            <?php endif; ?>
                            <span><?= $autorPrimeiroNome ?></span>
                        </a>
                    <?php endif; ?>

                    <div style="display:flex; justify-content:center; gap:8px; width:100%;">
                        <?php if (isset($_SESSION['id']) && ($_SESSION['id'] == $evento['usuario_id'] || ($_SESSION['perfil'] ?? '') === 'admin')): ?>
                            <a href="/pages/editar-evento.php?id=<?= htmlspecialchars($evento['id'], ENT_QUOTES, 'UTF-8') ?>" 
                               class="btn-secondary" 
                               style="flex:1; padding:8px 10px; font-size:0.85rem; background:#f5f5f5; border-color:#ccc; color:#333; text-align:center; max-width:140px; display:inline-block" 
                               onclick="event.stopPropagation()">Editar</a>
                        <?php endif; ?>
                        <a href="javascript:void(0)" 
                           onclick="event.stopPropagation(); confirmarRedirecionamento('<?= htmlspecialchars(addslashes($evento['link_oficial'])) ?>')" 
                           class="btn-secondary" 
                           style="flex:1; padding:8px 10px; font-size:0.85rem; text-align:center; max-width:140px; display:inline-block">Detalhes</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php elseif ($offset === 0): ?>
    <div class="eventos-vazio" style="grid-column: 1 / -1;">
        <div class="eventos-vazio-icone">🏃</div>
        <h2>Nenhuma corrida encontrada</h2>
        <?php if ($modo === 'localizacao' || $modo === 'cidade'): ?>
            <p>Não encontramos eventos em um raio de até <strong><?= (int)$raio ?> km</strong> <?= !empty($cidadeNome) ? 'de ' . htmlspecialchars($cidadeNome) : 'da sua localização' ?>.</p>
            <div style="display:flex; gap:10px; justify-content:center; margin-top:16px; flex-wrap:wrap;">
                <button type="button" class="btn-primary" onclick="window.strivelyFiltro.verTodos()">Ver todas as corridas</button>
                <button type="button" class="btn-secondary" onclick="window.strivelyFiltro.abrirBuscaCidade()">Buscar outra cidade</button>
            </div>
        <?php else: ?>
            <?php 
                $adminId = (int)($_ENV['ADMIN_ID'] ?? getenv('ADMIN_ID') ?: 2);
                if (isset($_SESSION['id']) && (int)$_SESSION['id'] === $adminId): 
            ?>
                <a href="/pages/divulgar-evento.php" class="btn-primary" style="margin-top:16px;">Divulgar primeiro evento</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif;

$html = ob_get_clean();

echo json_encode([
    'success'  => true,
    'modo'     => $modo,
    'total'    => $total,
    'count'    => count($eventos),
    'offset'   => $offset,
    'limite'   => $limite,
    'has_more' => ($offset + count($eventos)) < $total,
    'lat'      => $lat,
    'lng'      => $lng,
    'cidade'   => $cidadeNome,
    'raio'     => $raio,
    'html'     => $html
], JSON_UNESCAPED_UNICODE);
exit();
