<?php
// ==========================================================
// STRIVELY — pages/perfil-publico.php
// Perfil público visualizado por outros usuários
// ==========================================================

$only_session = true;
require_once '../components/header.php';

// Protege a página — só usuários logados
if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

unset($only_session);

require_once '../config/conexao.php';

$visitado_id = $_GET['id'] ?? null;

if (!$visitado_id) {
    echo "ID do usuário não informado.";
    exit();
}

// -------------------------------------------------------------
// Busca dados do usuário visitado
// -------------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$visitado_id]);
$visitado = $stmt->fetch();

if (!$visitado) {
    echo "Usuário não encontrado.";
    exit();
}

$is_treinador = ($visitado['perfil'] === 'treinador');
$dados_treinador = null;
$qtd_alunos_ativos = 0;

if ($is_treinador) {
    $stmt_treinador = $pdo->prepare("SELECT especialidade, assessoria, cref, status FROM treinadores WHERE usuario_id = ?");
    $stmt_treinador->execute([$visitado_id]);
    $dados_treinador = $stmt_treinador->fetch();

    $stmt_alunos = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE treinador_id = ? AND status_vinculo = 'aceito'");
    // id do visitado, não da table treinadores mas da table usuarios. Em nosso banco treinador_id em usuários é o ID da table usuários.
    // Depende da modelagem exata, a instrução diz: "treinador_id integer REFERENCES usuarios(id)". Então ok:
    $stmt_alunos->execute([$visitado_id]);
    $qtd_alunos_ativos = $stmt_alunos->fetchColumn();
}

// -------------------------------------------------------------
// Queries para Stats Cards do Visitado
// -------------------------------------------------------------
// 1. Treinos realizados
$stmt_t_realizados = $pdo->prepare("SELECT COUNT(*) FROM treinos WHERE aluno_id = ? AND status = 'realizado'");
$stmt_t_realizados->execute([$visitado_id]);
$count_treinos = $stmt_t_realizados->fetchColumn();

// 2. Semanas com treinos
$stmt_t_semanas = $pdo->prepare("SELECT COUNT(DISTINCT DATE_TRUNC('week', data_treino)) FROM treinos WHERE aluno_id = ? AND status = 'realizado'");
$stmt_t_semanas->execute([$visitado_id]);
$count_semanas = $stmt_t_semanas->fetchColumn();

// 3. Eventos participados/divulgados
try {
    $stmt_e_partic = $pdo->prepare("SELECT COUNT(*) FROM eventos WHERE usuario_id = ?");
    $stmt_e_partic->execute([$visitado_id]);
    $count_eventos = $stmt_e_partic->fetchColumn();
} catch (Exception $e) {
    $stmt_e_partic = $pdo->prepare("SELECT COUNT(*) FROM usuario_eventos WHERE usuario_id = ?");
    $stmt_e_partic->execute([$visitado_id]);
    $count_eventos = $stmt_e_partic->fetchColumn();
}

// -------------------------------------------------------------
// Consultar Eventos do Visitado
// -------------------------------------------------------------
$filtro = $_GET['filtro_evento'] ?? 'todos';
$hoje = date('Y-m-d');
$query_eventos = "
  SELECT ue.*, e.nome as evento_nome, e.cidade as evento_cidade, e.data_evento as evento_data, e.banner as evento_banner, e.distancias 
  FROM usuario_eventos ue 
  LEFT JOIN eventos e ON e.id = ue.evento_id 
  WHERE ue.usuario_id = :uid";

if ($filtro === 'proximos') {
    $query_eventos .= " AND (e.data_evento >= :hoje OR ue.data_evento >= :hoje)";
} elseif ($filtro === 'participados') {
    $query_eventos .= " AND (e.data_evento < :hoje OR ue.data_evento < :hoje)";
}
$query_eventos .= " ORDER BY COALESCE(e.data_evento, ue.data_evento) ASC";

$stmt_ev = $pdo->prepare($query_eventos);
$params = [':uid' => $visitado_id];
if ($filtro !== 'todos') $params[':hoje'] = $hoje;
$stmt_ev->execute($params);
$eventos_perfil = $stmt_ev->fetchAll();

// -------------------------------------------------------------
// Consultar dados do treinador do Visitado (se não for treinador e tiver vínculo)
// -------------------------------------------------------------
$treinador_descobrir = null;
if (!$is_treinador && !empty($visitado['treinador_id']) && $visitado['status_vinculo'] === 'aceito') {
    // Busca dados do treinador desse cara
    $stmt_trein_vinculo = $pdo->prepare("SELECT u.id, u.nome, u.foto, t.especialidade FROM usuarios u LEFT JOIN treinadores t ON t.usuario_id = u.id WHERE u.id = ?");
    $stmt_trein_vinculo->execute([$visitado['treinador_id']]);
    $treinador_descobrir = $stmt_trein_vinculo->fetch();
}

$tituloPagina = "Perfil de " . htmlspecialchars($visitado['nome']);
include '../components/head.php';
include '../components/header.php';
?>

<style>
/* MESMO CSS GRID DE PERFIL.PHP */
body { background-color: var(--bg); }
.perfil-layout {
    display: flex; max-width: 1200px; margin: 40px auto 100px auto;
    padding: 0 24px; gap: 40px; align-items: flex-start;
}
.perfil-sidebar {
    width: 300px; flex-shrink: 0; position: sticky; top: 120px;
    background: var(--surface); border-radius: var(--radius-lg);
    padding: 40px 24px; text-align: center; box-shadow: var(--shadow-md);
}
.perfil-conteudo { flex: 1; min-width: 0; }

/* Avatar */
.ps-avatar-wrapper { position: relative; width: 120px; height: 120px; margin: 0 auto 20px auto; }
.ps-avatar, .ps-avatar-padrao {
    width: 100%; height: 100%; border-radius: 50%; object-fit: cover;
    border: 3px solid var(--green); box-shadow: 0 0 0 5px rgba(29, 185, 84, 0.12);
}
.ps-avatar-padrao { background: #e0e0e0; display: flex; align-items: center; justify-content: center; }
.ps-avatar-padrao svg { width: 50px; height: 50px; fill: #aaa; }

/* Textos sidebar */
.ps-nome-view { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; color: var(--text-primary); margin: 0; line-height: 1.1; letter-spacing: 1px; }
.ps-cidade-view { font-size: 0.9rem; color: var(--text-tertiary); margin: 4px 0 16px 0; font-weight: 500; }
.badges-container { display: flex; flex-direction: column; align-items: center; gap: 8px; margin-bottom: 24px; }
.ps-runner-badge { display: inline-block; background: var(--green); color: #fff; font-weight: 700; font-size: 0.85rem; padding: 6px 16px; border-radius: 100px; text-transform: uppercase; letter-spacing: 0.5px; }
.ps-nivel-badge { display: inline-block; border: 1px solid var(--green); color: var(--green-dark); font-weight: 600; font-size: 0.75rem; padding: 4px 12px; border-radius: 100px; }

/* Card Treinador Visitado / Proprio info de trainer */
.card-treinador { background: rgba(29, 185, 84, 0.06); border-left: 3px solid var(--green); border-radius: 8px; padding: 12px; text-align: left; margin-top: 16px; cursor: pointer; transition: transform 0.2s; }
.card-treinador:hover { transform: translateY(-2px); }
.card-treinador-title { font-size: 0.8rem; font-weight: 700; color: var(--green-dark); margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
.card-treinador-info { display: flex; align-items: center; gap: 12px; }
.card-treinador-foto { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; background: #ccc; flex-shrink: 0; }
.card-treinador-nome { font-size: 0.9rem; font-weight: 700; color: var(--text-primary); margin: 0; }
.card-treinador-esp { font-size: 0.8rem; color: var(--text-secondary); margin: 0; }

.info-box-treinador { text-align: left; background: #f8f8f8; padding: 16px; border-radius: 12px; margin-top: 20px; }
.info-box-treinador p { margin: 4px 0; font-size: 0.85rem; color: var(--text-secondary); }
.info-box-treinador strong { color: var(--text-primary); }
.btn-contratar { display: block; background: var(--green); color: #fff; text-align: center; font-weight: 700; padding: 12px; border-radius: 12px; text-decoration: none; margin-top: 16px; transition: opacity 0.2s; }
.btn-contratar:hover { opacity: 0.9; }

/* Stats grid */
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 30px; }
.stat-card { background: var(--surface); border-radius: var(--radius-lg); padding: 24px 16px; text-align: center; box-shadow: var(--shadow-md); }
.stat-numero { font-family: 'Bebas Neue', sans-serif; font-size: 2.5rem; color: var(--text-primary); line-height: 1; margin-bottom: 8px; }
.stat-label { font-size: 0.85rem; color: var(--text-tertiary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

/* Secao Bio */
.secao-titulo { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; color: var(--text-primary); margin-bottom: 16px; letter-spacing: 0.5px; }
.bio-texto { font-size: 1.05rem; color: var(--text-secondary); line-height: 1.6; background: var(--surface); padding: 24px; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); margin-bottom: 40px; }
.bio-placeholder { color: var(--text-tertiary); font-style: italic; }

/* Eventos */
.ps-eventos-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
.ps-filter-select { padding: 8px 16px; border-radius: 100px; border: 1.5px solid #ddd; background: #fff; font-family: inherit; font-size: 0.9rem; font-weight: 600; color: #444; cursor: pointer; outline: none; }
.ps-filter-select:focus { border-color: var(--green); }
.ps-evento-card { display: flex; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: var(--shadow-md); transition: transform 0.2s, box-shadow 0.2s; margin-bottom: 16px; }
.ps-evento-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
.ps-evento-img { width: 180px; background-color: #f5f5f5; background-size: cover; background-position: center; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.ps-evento-info { padding: 24px; flex: 1; display: flex; flex-direction: column; justify-content: center; }
.ps-evento-info h3 { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; letter-spacing: 0.5px; margin: 0 0 6px 0; color: #111; line-height: 1.1; }
.ps-evento-detalhes { font-size: 0.9rem; color: #666; margin: 0 0 4px 0; display: flex; align-items: center; gap: 6px; }

@media(max-width: 900px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media(max-width: 768px) {
    .perfil-layout { flex-direction: column; }
    .perfil-sidebar { width: 100%; position: static; }
    .ps-evento-card { flex-direction: column; }
    .ps-evento-img { width: 100%; height: 160px; }
}
</style>

<div class="perfil-layout">

    <!-- SIDEBAR -->
    <aside class="perfil-sidebar">
        <div class="ps-avatar-wrapper">
            <?php if (!empty($visitado['foto'])): ?>
                <img src="<?= htmlspecialchars($visitado['foto']) ?>" alt="Avatar" class="ps-avatar"/>
            <?php else: ?>
                <div class="ps-avatar-padrao">
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                </div>
            <?php endif; ?>
        </div>

        <h1 class="ps-nome-view"><?= htmlspecialchars($visitado['nome']) ?></h1>
        <p class="ps-cidade-view">📍 <?= !empty($visitado['cidade']) ? htmlspecialchars($visitado['cidade']) : 'Cidade não informada' ?></p>
        
        <div class="badges-container">
            <?php if (!empty($visitado['tipo_corredor'])): ?>
                <div class="ps-runner-badge"><?= htmlspecialchars($visitado['tipo_corredor']) ?></div>
            <?php endif; ?>
            <?php if (!empty($visitado['nivel'])): ?>
                <div class="ps-nivel-badge"><?= htmlspecialchars($visitado['nivel']) ?></div>
            <?php endif; ?>
        </div>

        <?php if ($is_treinador && $dados_treinador): ?>
            <!-- INFO TREINADOR -->
            <div class="info-box-treinador">
                <p><strong>Especialidade:</strong> <?= htmlspecialchars($dados_treinador['especialidade']) ?></p>
                <p><strong>Assessoria:</strong> <?= htmlspecialchars($dados_treinador['assessoria']) ?></p>
                <p><strong>CREF:</strong> <?= htmlspecialchars($dados_treinador['cref'] ?? 'Não informado') ?></p>
                <p><strong>Alunos ativos:</strong> <?= intval($qtd_alunos_ativos) ?></p>
            </div>
            
            <?php if ($_SESSION['id'] != $visitado_id): ?>
                <a href="/pages/buscar-treinador.php" class="btn-contratar">Contratar Treinador</a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($treinador_descobrir): ?>
            <!-- TREINADOR DESSE USUARIO -->
            <div class="card-treinador" onclick="window.location.href='/pages/perfil-publico.php?id=<?= $treinador_descobrir['id'] ?>'">
                <div class="card-treinador-title">🏋️ Treinador atual</div>
                <div class="card-treinador-info">
                    <?php if (!empty($treinador_descobrir['foto'])): ?>
                        <img src="<?= htmlspecialchars($treinador_descobrir['foto']) ?>" class="card-treinador-foto" alt="Treinador">
                    <?php else: ?>
                        <div class="card-treinador-foto"></div>
                    <?php endif; ?>
                    <div>
                        <p class="card-treinador-nome"><?= htmlspecialchars($treinador_descobrir['nome']) ?></p>
                        <p class="card-treinador-esp"><?= htmlspecialchars($treinador_descobrir['especialidade']) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </aside>

    <!-- CONTEÚDO -->
    <main class="perfil-conteudo">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-numero"><?= intval($count_treinos) ?></div>
                <div class="stat-label">Treinos realizados</div>
            </div>
            <div class="stat-card">
                <div class="stat-numero"><?= intval($count_eventos) ?></div>
                <div class="stat-label">Eventos participados</div>
            </div>
            <div class="stat-card">
                <div class="stat-numero">—</div>
                <div class="stat-label">Km rodados</div>
            </div>
            <div class="stat-card">
                <div class="stat-numero"><?= intval($count_semanas) ?></div>
                <div class="stat-label">Semanas de treino</div>
            </div>
        </div>

        <!-- ABA ÚNICA (Visualmente sem botão de aba) -->
        <h2 class="secao-titulo">Sobre</h2>
        <div class="bio-texto">
            <?php if (!empty($visitado['bio'])): ?>
                <?= nl2br(htmlspecialchars($visitado['bio'])) ?>
            <?php else: ?>
                <span class="bio-placeholder">Esta pessoa ainda não escreveu uma bio.</span>
            <?php endif; ?>
        </div>

        <div class="ps-eventos-header">
            <h2 class="secao-titulo" style="margin:0;">Eventos desse corredor</h2>
            <select class="ps-filter-select" onchange="window.location.href='?id=<?= $visitado_id ?>&filtro_evento='+this.value">
                <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos os eventos</option>
                <option value="proximos" <?= $filtro === 'proximos' ? 'selected' : '' ?>>Próximos</option>
                <option value="participados" <?= $filtro === 'participados' ? 'selected' : '' ?>>Já participados</option>
            </select>
        </div>

        <?php if (empty($eventos_perfil)): ?>
            <p style="color: #888; background: #fff; padding: 24px; border-radius: 16px; text-align: center; box-shadow: var(--shadow-md);">Nenhum evento encontrado para este usuário.</p>
        <?php else: ?>
            <?php foreach ($eventos_perfil as $ev): ?>
                <?php 
                    $nome = $ev['evento_nome'] ?? $ev['nome_manual'] ?? 'Evento';
                    $cidade = !empty($ev['evento_cidade']) ? '📍 ' . $ev['evento_cidade'] : '';
                    $dataEvento = $ev['evento_data'] ?? $ev['data_evento'];
                    $dt = new DateTime($dataEvento);
                    $capaArray = ['background-image: url(' . (!empty($ev['evento_banner']) && strpos($ev['evento_banner'], 'http') === 0 ? $ev['evento_banner'] : '/' . ($ev['evento_banner'] ?? '')) . ');'];
                    $hasBanner = !empty($ev['evento_banner']);
                ?>
                <div class="ps-evento-card">
                    <div class="ps-evento-img" <?= $hasBanner ? 'style="'.$capaArray[0].'"' : '' ?>>
                        <?php if (!$hasBanner): ?>
                            <svg style="width:50px; height:50px; fill:#ccc;" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="ps-evento-info">
                        <h3><?= htmlspecialchars($nome) ?></h3>
                        <p class="ps-evento-detalhes">📅 <?= $dt->format('d/m/Y') ?> &nbsp;&nbsp;&nbsp; <?= htmlspecialchars($cidade) ?></p>
                        <?php if (!empty($ev['distancias'])): ?>
                            <p class="ps-evento-detalhes">📏 <?= htmlspecialchars($ev['distancias']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

</div>

</body>
</html>
