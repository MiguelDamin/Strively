<?php
ob_start();
require_once '../config/conexao.php';

try {
  $pdo->exec("UPDATE eventos SET status = 'inativo' WHERE data_evento <= (CURRENT_TIMESTAMP AT TIME ZONE 'America/Sao_Paulo')::date AND status = 'ativo'");
} catch (PDOException $e) {}

$user_id = $_SESSION['id'] ?? null;
$user_lat = null;
$user_lng = null;
$user_cidade = null;
$filtro_modo = 'localizacao'; // Padrão: Perto de mim sempre

if ($user_id) {
  $stmtU = $pdo->prepare("SELECT lat, lng, cidade_manual, compartilha_localizacao, cidade FROM usuarios WHERE id = ?");
  $stmtU->execute([$user_id]);
  $uData = $stmtU->fetch();
  if ($uData) {
    $user_lat = isset($uData['lat']) && is_numeric($uData['lat']) ? (float)$uData['lat'] : null;
    $user_lng = isset($uData['lng']) && is_numeric($uData['lng']) ? (float)$uData['lng'] : null;
    $user_cidade = $uData['cidade_manual'] ?: $uData['cidade'];
  }
}

// Inicial: carrega de 30 em 30
$limite_inicial = 30;
$total_eventos = 0;

if ($user_lat !== null && $user_lng !== null) {
  // Usuário possui coordenadas no banco
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
    WHERE distancia_km <= 200
  ");
  $countStmt->execute([':lat' => $user_lat, ':lng' => $user_lng]);
  $total_eventos = (int)$countStmt->fetchColumn();

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
    WHERE distancia_km <= 200
    ORDER BY distancia_km ASC, data_evento ASC
    LIMIT {$limite_inicial} OFFSET 0
  ");
  $stmt->execute([':lat' => $user_lat, ':lng' => $user_lng]);
  $eventos = $stmt->fetchAll();
} else {
  // Sem coordenadas no banco: renderiza primeiras 30 corridas e o front tenta GPS / localStorage
  $countStmt = $pdo->query("
    SELECT count(*) FROM eventos e 
    WHERE e.status = 'ativo' 
      AND e.data_evento > (CURRENT_TIMESTAMP AT TIME ZONE 'America/Sao_Paulo')::date
  ");
  $total_eventos = (int)$countStmt->fetchColumn();

  $stmt = $pdo->prepare("
    SELECT e.*, u.nome AS autor_nome, u.foto AS autor_foto 
    FROM eventos e 
    LEFT JOIN usuarios u ON e.usuario_id = u.id 
    WHERE e.status = 'ativo' 
      AND e.data_evento > (CURRENT_TIMESTAMP AT TIME ZONE 'America/Sao_Paulo')::date
    ORDER BY e.data_evento ASC
    LIMIT {$limite_inicial} OFFSET 0
  ");
  $stmt->execute();
  $eventos = $stmt->fetchAll();
}

$tituloPagina = "Próximos Eventos";
include '../components/head.php';
include '../components/header.php';
?>

<script>
function confirmarRedirecionamento(url) {
    if (!url || url === '#' || url === '') return;
    let displayUrl = url.replace(/^https?:\/\//, '');
    if (displayUrl.length > 50) displayUrl = displayUrl.substring(0, 47) + '...';
    if (confirm("Você está sendo redirecionado para a página:\n" + displayUrl)) {
        window.open(url, '_blank');
    }
}
</script>

<style>
/* ── Barra Superior e Filtro Compacto ── */
.eventos-toolbar {
  max-width: 1200px;
  margin: 0 auto 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  position: relative;
}

.filtro-dropdown-wrap {
  position: relative;
  display: inline-block;
}

.btn-filtro-compacto {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: var(--radius-full);
  background: var(--surface);
  border: 1.5px solid var(--border-strong);
  font-family: 'Outfit', sans-serif;
  font-size: 0.88rem;
  font-weight: 500;
  color: var(--text-primary);
  cursor: pointer;
  box-shadow: var(--shadow-xs);
  transition: all 0.2s ease;
  user-select: none;
}

.btn-filtro-compacto:hover {
  border-color: var(--green);
  background: var(--surface-2);
}

.btn-filtro-compacto.aberto {
  border-color: var(--green);
  box-shadow: 0 0 0 3px var(--green-glow);
}

.btn-filtro-compacto .icon-filtro {
  color: var(--green);
  flex-shrink: 0;
}

.btn-filtro-texto {
  color: var(--text-tertiary);
  font-size: 0.82rem;
}

.btn-filtro-valor {
  font-weight: 600;
  color: var(--green-deeper);
}

.btn-filtro-compacto .icon-seta {
  color: var(--text-tertiary);
  transition: transform 0.2s ease;
  margin-left: 2px;
}

.btn-filtro-compacto.aberto .icon-seta {
  transform: rotate(180deg);
}

/* ── Menu Dropdown Flutuante Compacto ── */
.filtro-dropdown-popover {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  z-index: 100;
  width: 270px;
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-md);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
  padding: 8px;
  animation: fadeInDown 0.18s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: translateY(0); }
}

.popover-opcoes {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.popover-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: var(--radius-sm);
  background: transparent;
  border: none;
  font-family: 'Outfit', sans-serif;
  font-size: 0.88rem;
  color: var(--text-primary);
  cursor: pointer;
  text-align: left;
  transition: background 0.15s ease;
}

.popover-item:hover {
  background: var(--surface-2);
}

.popover-item.ativo {
  background: var(--green-tint);
  color: var(--green-deeper);
  font-weight: 600;
}

.popover-item .item-icone {
  font-size: 1.05rem;
  flex-shrink: 0;
}

.popover-item .item-nome {
  flex: 1;
}

.popover-item .item-check {
  display: none;
  font-size: 0.9rem;
  color: var(--green);
  font-weight: bold;
}

.popover-item.ativo .item-check {
  display: inline-block;
}

/* ── Campo de Busca por Cidade Embutido ── */
.popover-busca-cidade {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid var(--border);
}

.input-inline-wrap {
  display: flex;
  gap: 6px;
  align-items: center;
}

.input-inline-wrap input {
  flex: 1;
  padding: 8px 10px;
  border-radius: var(--radius-sm);
  border: 1.5px solid var(--border-strong);
  font-family: 'Outfit', sans-serif;
  font-size: 0.85rem;
  outline: none;
  background: var(--surface);
}

.input-inline-wrap input:focus {
  border-color: var(--green);
}

.btn-popover-buscar {
  padding: 8px 12px;
  background: var(--green);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  font-family: 'Outfit', sans-serif;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease;
  white-space: nowrap;
}

.btn-popover-buscar:hover {
  background: var(--green-dark);
}

/* ── Resumo / Status à Direita ── */
.filtro-resumo-tag {
  font-size: 0.84rem;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 6px;
}

.evento-dist-badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--green-deeper);
  background: rgba(29, 185, 84, 0.12);
  border: 1px solid rgba(29, 185, 84, 0.3);
  padding: 2px 7px;
  border-radius: var(--radius-full);
}

.eventos-grid.carregando {
  opacity: 0.4;
  pointer-events: none;
  transition: opacity 0.2s ease;
}

/* ── Spinner e Fim do Infinite Scroll ── */
.infinite-scroll-loading {
  max-width: 1200px;
  margin: 32px auto;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  font-size: 0.88rem;
  color: var(--text-secondary);
}

.spinner-mini {
  width: 18px;
  height: 18px;
  border: 2.5px solid var(--border-strong);
  border-top-color: var(--green);
  border-radius: 50%;
  animation: girar 0.6s linear infinite;
}

@keyframes girar {
  to { transform: rotate(360deg); }
}

.infinite-scroll-end {
  max-width: 1200px;
  margin: 28px auto 40px;
  text-align: center;
  font-size: 0.82rem;
  color: var(--text-tertiary);
}

/* ── Estilos existentes dos cards de eventos ── */
.card-footer {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  width: 100%;
  padding-top: 8px;
}
.card-autor {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 0.85rem;
  color: var(--text-muted, #666);
}
.card-autor img {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  object-fit: cover;
}
.evento-distancias {
  min-height: 38px;
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: 6px;
}

.evento-card {
    display: flex;
    flex-direction: column;
    height: 100%;
    border-radius: var(--radius-lg);
    overflow: hidden;
    background: var(--surface);
    box-shadow: var(--shadow-md);
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.evento-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg, 0 8px 24px rgba(0,0,0,0.08));
}

.evento-card .evento-banner {
    width: 100%;
    aspect-ratio: 3/4;
    object-fit: cover;
    display: block;
}

.evento-card .evento-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 16px;
}

.evento-card .evento-nome {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1rem;
    margin: 0 0 8px;
    min-height: 2.5em;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.evento-card .evento-info {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.evento-card .evento-distancias {
    min-height: 28px;
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin: 8px 0;
}

.evento-card .card-footer {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}

.evento-card .card-autor {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: var(--text-tertiary);
}

.evento-card .card-autor img {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
}

.eventos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 20px;
    align-items: stretch;
}
</style>

<body>

  <section class="eventos-section">

    <div class="eventos-header">
      <?php 
        $adminId = (int)($_ENV['ADMIN_ID'] ?? getenv('ADMIN_ID') ?: 2);
        if (isset($_SESSION['id']) && (int)$_SESSION['id'] === $adminId): 
      ?>
        <a href="/pages/divulgar-evento.php" class="btn-primary">+ Divulgar um evento</a>
      <?php endif; ?>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'enviado'): ?>
      <div class="auth-sucesso" style="max-width:1200px; margin:0 auto 24px;">
        ✅ Evento divulgado com sucesso! Já está visível para toda a comunidade.
      </div>
    <?php endif; ?>

    <!-- Toolbar com Filtro Compacto -->
    <div class="eventos-toolbar">
      <div class="filtro-dropdown-wrap" id="filtro-dropdown-wrap">
        <button type="button" class="btn-filtro-compacto" id="btn-filtro-trigger" onclick="window.strivelyFiltro.toggleMenu(event)" aria-expanded="false">
          <svg class="icon-filtro" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
          </svg>
          <span class="btn-filtro-texto">Filtro:</span>
          <span class="btn-filtro-valor" id="btn-filtro-valor">Perto de mim</span>
          <svg class="icon-seta" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </button>

        <!-- Dropdown Popover Compacto -->
        <div class="filtro-dropdown-popover" id="filtro-dropdown-popover" style="display:none;">
          <div class="popover-opcoes">
            <button type="button" class="popover-item <?= $filtro_modo === 'localizacao' ? 'ativo' : '' ?>" id="item-localizacao" onclick="window.strivelyFiltro.usarMinhaLocalizacao()">
              <span class="item-icone">📍</span>
              <span class="item-nome">Perto de mim</span>
              <span class="item-check">✓</span>
            </button>

            <button type="button" class="popover-item <?= $filtro_modo === 'cidade' ? 'ativo' : '' ?>" id="item-cidade" onclick="window.strivelyFiltro.abrirBuscaCidade(event)">
              <span class="item-icone">🏙️</span>
              <span class="item-nome">Buscar por cidade</span>
              <span class="item-check">✓</span>
            </button>

            <button type="button" class="popover-item <?= $filtro_modo === 'todos' ? 'ativo' : '' ?>" id="item-todos" onclick="window.strivelyFiltro.verTodos()">
              <span class="item-icone">🌍</span>
              <span class="item-nome">Ver todos</span>
              <span class="item-check">✓</span>
            </button>
          </div>

          <!-- Campo de Busca por Cidade Embutido -->
          <div class="popover-busca-cidade" id="popover-busca-cidade" style="display:none;">
            <div class="input-inline-wrap">
              <input 
                type="text" 
                id="input-cidade-popover" 
                placeholder="Ex: Porto Alegre, Passo Fundo..."
                autocomplete="off"
                onkeydown="if(event.key==='Enter') window.strivelyFiltro.executarBuscaCidade()"
              >
              <button type="button" class="btn-popover-buscar" id="btn-popover-buscar" onclick="window.strivelyFiltro.executarBuscaCidade()">
                Buscar
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Resumo / Status Compacto -->
      <div class="filtro-resumo-tag" id="filtro-resumo-tag">
        <span>📍 Corridas próximas</span>
        <span>•</span>
        <strong id="contador-eventos"><?= $total_eventos ?> <?= $total_eventos === 1 ? 'corrida' : 'corridas' ?></strong>
      </div>
    </div>

    <!-- Grid de Eventos -->
    <div class="eventos-grid">

      <?php if (!empty($eventos)): ?>
        <?php foreach ($eventos as $evento): ?>
          <?php
            $distKm = isset($evento['distancia_km']) ? round((float)$evento['distancia_km']) : null;
            $data  = new DateTime($evento['data_evento']);
            $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
            $dataFormatada = $data->format('d') . ' de ' . $meses[(int)$data->format('m') - 1] . ' de ' . $data->format('Y');
            $distanciasArr = array_filter(array_map('trim', explode(',', $evento['distancias'] ?? '')));
            $autorEventoId = (int)($evento['usuario_id'] ?? 0);
            $linkAutorEvento = (isset($_SESSION['id']) && $_SESSION['id'] === $autorEventoId)
              ? '/pages/perfil.php'
              : '/pages/perfil-publico.php?id=' . $autorEventoId;
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
                  <a href="<?= htmlspecialchars($linkAutorEvento, ENT_QUOTES, 'UTF-8') ?>" 
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
                    <span><?= htmlspecialchars(explode(' ', $evento['autor_nome'])[0]) ?></span>
                  </a>
                <?php endif; ?>

                <div style="display:flex; justify-content:center; gap:8px; width:100%;">
                  <?php if (isset($_SESSION['id']) && ($_SESSION['id'] == $evento['usuario_id'] || ($_SESSION['perfil'] ?? '') === 'admin')): ?>
                    <a href="/pages/editar-evento.php?id=<?= htmlspecialchars($evento['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary" style="flex:1; padding:8px 10px; font-size:0.85rem; background:#f5f5f5; border-color:#ccc; color:#333; text-align:center; max-width:140px; display:inline-block" onclick="event.stopPropagation()">Editar</a>
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

      <?php else: ?>

        <div class="eventos-vazio" style="grid-column: 1 / -1;">
          <div class="eventos-vazio-icone">🏃</div>
          <h2>Nenhuma corrida encontrada</h2>
          <p>Não encontramos corridas próximas a até 200 km da sua localização atual.</p>
          <div style="display:flex; gap:10px; justify-content:center; margin-top:16px; flex-wrap:wrap;">
            <button type="button" class="btn-primary" onclick="window.strivelyFiltro.verTodos()">Ver todas as corridas</button>
            <button type="button" class="btn-secondary" onclick="window.strivelyFiltro.abrirBuscaCidade()">Buscar outra cidade</button>
          </div>
        </div>

      <?php endif; ?>

    </div>

    <!-- Sentinel e Elementos do Infinite Scroll -->
    <div id="scroll-sentinel" style="height: 20px;"></div>

    <div id="infinite-scroll-loading" class="infinite-scroll-loading" style="display:none;">
      <div class="spinner-mini"></div>
      <span>Carregando mais corridas...</span>
    </div>

    <div id="infinite-scroll-end" class="infinite-scroll-end" style="display:none;">
      <span>✓ Você já visualizou todas as corridas deste filtro.</span>
    </div>

  </section>

<script>
window.strivelyConfig = {
  hasSavedLocation: <?= ($user_lat !== null && $user_lng !== null) ? 'true' : 'false' ?>,
  lat: <?= $user_lat !== null ? $user_lat : 'null' ?>,
  lng: <?= $user_lng !== null ? $user_lng : 'null' ?>,
  modoAtual: "localizacao",
  cidade: "<?= htmlspecialchars($user_cidade ?? '', ENT_QUOTES, 'UTF-8') ?>",
  total: <?= $total_eventos ?>,
  offset: <?= count($eventos) ?>,
  limite: 30,
  hasMore: <?= ($limite_inicial < $total_eventos) ? 'true' : 'false' ?>,
  isLoading: false
};

window.strivelyFiltro = {
  modoAtual: "localizacao",
  cidadeAtual: "",

  toggleMenu(e) {
    if (e) e.stopPropagation();
    const popover = document.getElementById('filtro-dropdown-popover');
    const btn = document.getElementById('btn-filtro-trigger');
    const aberto = popover.style.display !== 'none';
    if (aberto) {
      this.fecharMenu();
    } else {
      popover.style.display = 'block';
      btn.classList.add('aberto');
      btn.setAttribute('aria-expanded', 'true');
    }
  },

  fecharMenu() {
    const popover = document.getElementById('filtro-dropdown-popover');
    const btn = document.getElementById('btn-filtro-trigger');
    if (popover) popover.style.display = 'none';
    if (btn) {
      btn.classList.remove('aberto');
      btn.setAttribute('aria-expanded', 'false');
    }
  },

  setMenuAtivo(modo, nomeExibicao) {
    document.querySelectorAll('.popover-item').forEach(el => el.classList.remove('ativo'));
    const item = document.getElementById('item-' + (modo === 'localizacao' ? 'localizacao' : (modo === 'cidade' ? 'cidade' : 'todos')));
    if (item) item.classList.add('ativo');

    const label = document.getElementById('btn-filtro-valor');
    if (label) label.textContent = nomeExibicao;

    this.modoAtual = modo;
  },

  atualizarResumo(texto, total) {
    const resumo = document.getElementById('filtro-resumo-tag');
    if (resumo) {
      const plural = total === 1 ? 'corrida' : 'corridas';
      resumo.innerHTML = `<span>${texto}</span><span>•</span><strong>${total} ${plural}</strong>`;
    }
  },

  async buscarEventos(params, substituir = true) {
    const grid = document.querySelector('.eventos-grid');
    if (substituir && grid) grid.classList.add('carregando');

    try {
      const url = '/actions/filtrar-eventos.php?' + new URLSearchParams(params).toString();
      const res = await fetch(url);
      const data = await res.json();

      if (grid) {
        if (substituir) {
          grid.innerHTML = data.html;
          window.strivelyConfig.offset = data.count;
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else if (data.html) {
          grid.insertAdjacentHTML('beforeend', data.html);
          window.strivelyConfig.offset += data.count;
        }
        grid.classList.remove('carregando');
      }

      window.strivelyConfig.total = data.total;
      window.strivelyConfig.hasMore = data.has_more;

      // Mensagem de fim do infinite scroll
      const endEl = document.getElementById('infinite-scroll-end');
      if (endEl) {
        endEl.style.display = (!data.has_more && window.strivelyConfig.offset >= 30) ? 'block' : 'none';
      }

      return data;
    } catch (err) {
      if (grid) grid.classList.remove('carregando');
      console.error('Erro ao buscar eventos:', err);
      throw err;
    }
  },

  async usarMinhaLocalizacao() {
    this.setMenuAtivo('localizacao', 'Perto de mim');
    this.fecharMenu();
    document.getElementById('popover-busca-cidade').style.display = 'none';

    // Se já temos lat/lng na config
    if (window.strivelyConfig.lat && window.strivelyConfig.lng) {
      const data = await this.buscarEventos({
        modo: 'localizacao',
        lat: window.strivelyConfig.lat,
        lng: window.strivelyConfig.lng,
        limite: window.strivelyConfig.limite,
        offset: 0
      }, true);
      this.atualizarResumo('📍 Corridas próximas', data.total);
      return;
    }

    // Se temos no localStorage
    const savedLat = localStorage.getItem('strively_geo_lat');
    const savedLng = localStorage.getItem('strively_geo_lng');
    if (savedLat && savedLng) {
      window.strivelyConfig.lat = parseFloat(savedLat);
      window.strivelyConfig.lng = parseFloat(savedLng);
      const data = await this.buscarEventos({
        modo: 'localizacao',
        lat: window.strivelyConfig.lat,
        lng: window.strivelyConfig.lng,
        limite: window.strivelyConfig.limite,
        offset: 0
      }, true);
      this.atualizarResumo('📍 Corridas próximas', data.total);
      return;
    }

    // Pede ao navegador
    if (!navigator.geolocation) {
      this.atualizarResumo('🌍 Navegador sem suporte a GPS', window.strivelyConfig.total);
      return;
    }

    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        window.strivelyConfig.lat = pos.coords.latitude;
        window.strivelyConfig.lng = pos.coords.longitude;
        localStorage.setItem('strively_geo_lat', pos.coords.latitude);
        localStorage.setItem('strively_geo_lng', pos.coords.longitude);

        const data = await this.buscarEventos({
          modo: 'localizacao',
          lat: pos.coords.latitude,
          lng: pos.coords.longitude,
          limite: window.strivelyConfig.limite,
          offset: 0
        }, true);
        this.atualizarResumo('📍 Corridas próximas', data.total);
      },
      (err) => {
        this.atualizarResumo('⚠️ GPS desativado (mostrando todas)', window.strivelyConfig.total);
      },
      { timeout: 8000, enableHighAccuracy: false }
    );
  },

  abrirBuscaCidade(e) {
    if (e) e.stopPropagation();
    const buscaBox = document.getElementById('popover-busca-cidade');
    buscaBox.style.display = 'block';
    const input = document.getElementById('input-cidade-popover');
    setTimeout(() => input.focus(), 60);
  },

  async executarBuscaCidade() {
    const input = document.getElementById('input-cidade-popover');
    const cidade = (input.value || '').trim();
    if (!cidade) {
      input.focus();
      return;
    }

    this.cidadeAtual = cidade;
    this.setMenuAtivo('cidade', cidade);
    this.fecharMenu();

    const btn = document.getElementById('btn-popover-buscar');
    if (btn) btn.disabled = true;

    try {
      const data = await this.buscarEventos({
        modo: 'cidade',
        cidade: cidade,
        limite: window.strivelyConfig.limite,
        offset: 0
      }, true);
      this.atualizarResumo(`📍 Perto de ${data.cidade || cidade}`, data.total);
    } catch (err) {
      alert('Não encontramos a cidade digitada. Tente outra cidade.');
    } finally {
      if (btn) btn.disabled = false;
    }
  },

  async verTodos() {
    this.setMenuAtivo('todos', 'Ver todos');
    this.fecharMenu();
    document.getElementById('popover-busca-cidade').style.display = 'none';

    const data = await this.buscarEventos({
      modo: 'todos',
      limite: window.strivelyConfig.limite,
      offset: 0
    }, true);
    this.atualizarResumo('🌍 Todas as corridas', data.total);
  }
};

// ── Fechar dropdown ao clicar fora ──
document.addEventListener('click', function(e) {
  const wrap = document.getElementById('filtro-dropdown-wrap');
  if (wrap && !wrap.contains(e.target)) {
    window.strivelyFiltro.fecharMenu();
  }
});

// ── Infinite Scroll com IntersectionObserver ──
async function carregarProximaPagina() {
  if (!window.strivelyConfig.hasMore || window.strivelyConfig.isLoading) return;

  window.strivelyConfig.isLoading = true;
  const loadingEl = document.getElementById('infinite-scroll-loading');
  if (loadingEl) loadingEl.style.display = 'flex';

  const params = {
    modo: window.strivelyFiltro.modoAtual,
    limite: window.strivelyConfig.limite,
    offset: window.strivelyConfig.offset
  };

  if (window.strivelyFiltro.modoAtual === 'localizacao' && window.strivelyConfig.lat && window.strivelyConfig.lng) {
    params.lat = window.strivelyConfig.lat;
    params.lng = window.strivelyConfig.lng;
  } else if (window.strivelyFiltro.modoAtual === 'cidade' && window.strivelyFiltro.cidadeAtual) {
    params.cidade = window.strivelyFiltro.cidadeAtual;
  }

  try {
    await window.strivelyFiltro.buscarEventos(params, false);
  } catch (e) {
    console.error('Erro no infinite scroll:', e);
  } finally {
    window.strivelyConfig.isLoading = false;
    if (loadingEl) loadingEl.style.display = 'none';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  // Observador de rolagem infinita
  const sentinel = document.getElementById('scroll-sentinel');
  if (sentinel && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && window.strivelyConfig.hasMore && !window.strivelyConfig.isLoading) {
        carregarProximaPagina();
      }
    }, { rootMargin: '350px' });
    observer.observe(sentinel);
  }

  // Se o usuário não tinha coordenadas no banco, tenta geolocation do navegador automaticamente
  if (!window.strivelyConfig.hasSavedLocation) {
    const cachedLat = localStorage.getItem('strively_geo_lat');
    const cachedLng = localStorage.getItem('strively_geo_lng');

    if (cachedLat && cachedLng) {
      window.strivelyConfig.lat = parseFloat(cachedLat);
      window.strivelyConfig.lng = parseFloat(cachedLng);
      window.strivelyFiltro.usarMinhaLocalizacao();
    } else if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          window.strivelyConfig.lat = pos.coords.latitude;
          window.strivelyConfig.lng = pos.coords.longitude;
          localStorage.setItem('strively_geo_lat', pos.coords.latitude);
          localStorage.setItem('strively_geo_lng', pos.coords.longitude);
          window.strivelyFiltro.usarMinhaLocalizacao();
        },
        (err) => {
          // Mantém as corridas gerais carregadas
        },
        { timeout: 7000, enableHighAccuracy: false }
      );
    }
  }
});
</script>

<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>