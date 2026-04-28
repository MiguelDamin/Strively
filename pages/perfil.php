<?php
// ==========================================================
// STRIVELY — pages/perfil.php
// Página de perfil do usuário logado (Novo Layout Duas Colunas)
// ==========================================================

$only_session = true;
require_once '../components/header.php';

// Protege a página — só usuários logados
if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

unset($only_session); // OBRIGATÓRIO

require_once '../config/conexao.php';

// Busca dados atualizados do banco
$stmt = $pdo->prepare("
  SELECT id, nome, email, foto, cidade, perfil,
         treinador_id, status_vinculo,
         bio, tipo_corredor, nivel,
         strava_conectado, strava_km_total, strava_km_ano,
         strava_atividades_total, strava_sincronizado_em
  FROM usuarios WHERE id = ?
");
$stmt->execute([$_SESSION['id']]);
$usuario = $stmt->fetch();

$_SESSION['nome']  = $usuario['nome'];
$_SESSION['foto']  = $usuario['foto'];

// -------------------------------------------------------------
// Queries para Stats Cards
// -------------------------------------------------------------
// 1. Treinos realizados
$stmt_t_realizados = $pdo->prepare("SELECT COUNT(*) FROM treinos WHERE aluno_id = ? AND status = 'realizado'");
$stmt_t_realizados->execute([$_SESSION['id']]);
$count_treinos = $stmt_t_realizados->fetchColumn();

// 2. Semanas com treinos (PostgreSQL)
$stmt_t_semanas = $pdo->prepare("SELECT COUNT(DISTINCT DATE_TRUNC('week', data_treino)) FROM treinos WHERE aluno_id = ? AND status = 'realizado'");
$stmt_t_semanas->execute([$_SESSION['id']]);
$count_semanas = $stmt_t_semanas->fetchColumn();

// 3. Eventos participados/divulgados
// O comando do usuario: SELECT COUNT(*) FROM eventos WHERE usuario_id = ?
// Se caso der erro na tabela eventos por falta da coluna (tentaremos com a de usuario_eventos também)
try {
    $stmt_e_partic = $pdo->prepare("SELECT COUNT(*) FROM eventos WHERE usuario_id = ?");
    $stmt_e_partic->execute([$_SESSION['id']]);
    $count_eventos = $stmt_e_partic->fetchColumn();
} catch (Exception $e) {
    // Fallback se não tiver a coluna usuario_id na table eventos e usar a junction table
    $stmt_e_partic = $pdo->prepare("SELECT COUNT(*) FROM usuario_eventos WHERE usuario_id = ?");
    $stmt_e_partic->execute([$_SESSION['id']]);
    $count_eventos = $stmt_e_partic->fetchColumn();
}

// -------------------------------------------------------------
// Consultar dados do treinador vinculado (se houver)
// -------------------------------------------------------------
$treinador = null;
if (!empty($usuario['treinador_id']) && $usuario['status_vinculo'] === 'aceito') {
    $stmt_trein_vinculo = $pdo->prepare("SELECT u.id, u.nome, u.foto, t.especialidade FROM usuarios u LEFT JOIN treinadores t ON t.usuario_id = u.id WHERE u.id = ?");
    $stmt_trein_vinculo->execute([$usuario['treinador_id']]);
    $treinador = $stmt_trein_vinculo->fetch();
}

// -------------------------------------------------------------
// Consultar Eventos (Filtro mantido)
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
$params = [':uid' => $_SESSION['id']];
if ($filtro !== 'todos') $params[':hoje'] = $hoje;
$stmt_ev->execute($params);
$eventos_perfil = $stmt_ev->fetchAll();

// -------------------------------------------------------------
// Últimos 10 treinos
// -------------------------------------------------------------
$stmt_treinos = $pdo->prepare("SELECT data_treino, titulo, status FROM treinos WHERE aluno_id = ? ORDER BY data_treino DESC LIMIT 10");
$stmt_treinos->execute([$_SESSION['id']]);
$ultimos_treinos = $stmt_treinos->fetchAll();

$tituloPagina = "Meu Perfil";
include '../components/head.php';
include '../components/header.php';
?>

<style>
/* ------------------------------------------------ */
/* LAYOUT BASE E GRID */
/* ------------------------------------------------ */
body {
    background-color: var(--bg);
}
.perfil-layout {
    display: flex;
    max-width: 1200px;
    margin: 40px auto 100px auto;
    padding: 0 24px;
    gap: 40px;
    align-items: flex-start;
}

/* ------------------------------------------------ */
/* SIDEBAR (ESQUERDA) */
/* ------------------------------------------------ */
.perfil-sidebar {
    width: 300px;
    flex-shrink: 0;
    position: sticky;
    top: 120px;
    background: var(--surface);
    border-radius: var(--radius-lg);
    padding: 40px 24px;
    text-align: center;
    box-shadow: var(--shadow-md);
}

.ps-avatar-wrapper {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 20px auto;
}

.ps-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--green);
    box-shadow: 0 0 0 5px rgba(29, 185, 84, 0.12);
    background: var(--bg);
}

.ps-avatar-padrao {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid var(--green);
    box-shadow: 0 0 0 5px rgba(29, 185, 84, 0.12);
}
.ps-avatar-padrao svg { width: 50px; height: 50px; fill: #aaa; }

.ps-nome-view {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.8rem;
    color: var(--text-primary);
    margin: 0;
    line-height: 1.1;
    letter-spacing: 1px;
}

.ps-cidade-view {
    font-size: 0.9rem;
    color: var(--text-tertiary);
    margin: 4px 0 16px 0;
    font-weight: 500;
}

.badges-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    margin-bottom: 24px;
}

.ps-runner-badge {
    display: inline-block;
    background: var(--green);
    color: #fff;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 6px 16px;
    border-radius: 100px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ps-nivel-badge {
    display: inline-block;
    border: 1px solid var(--green);
    color: var(--green-dark);
    font-weight: 600;
    font-size: 0.75rem;
    padding: 4px 12px;
    border-radius: 100px;
}

.btn-editar-perfil {
    background: #f0f0f0;
    color: var(--text-secondary);
    border: none;
    border-radius: 12px;
    width: 100%;
    padding: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
    margin-bottom: 16px;
}
.btn-editar-perfil:hover {
    background: #e4e4e4;
    color: #111;
}

.card-treinador {
    background: rgba(29, 185, 84, 0.06);
    border-left: 3px solid var(--green);
    border-radius: 8px;
    padding: 12px;
    text-align: left;
    margin-top: 16px;
    cursor: pointer;
    transition: transform 0.2s;
}
.card-treinador:hover {
    transform: translateY(-2px);
}
.card-treinador-title {
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--green-dark);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.card-treinador-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.card-treinador-foto {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    background: #ccc;
    flex-shrink: 0;
}
.card-treinador-nome {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}
.card-treinador-esp {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin: 0;
}

/* ------------------------------------------------ */
/* CONTEÚDO PRINCIPAL */
/* ------------------------------------------------ */
.perfil-conteudo {
    flex: 1;
    min-width: 0; /* previne overflow */
}

/* Stats */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 30px;
}
.stat-card {
    background: var(--surface);
    border-radius: var(--radius-lg);
    padding: 24px 16px;
    text-align: center;
    box-shadow: var(--shadow-md);
}
.stat-numero {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2.5rem;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 8px;
}
.stat-label {
    font-size: 0.85rem;
    color: var(--text-tertiary);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Tabs */
.tabs-container {
    display: flex;
    gap: 24px;
    margin-bottom: 30px;
    border-bottom: 2px solid #eaeaea;
}
.tab-button {
    background: none;
    border: none;
    padding: 12px 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-tertiary);
    cursor: pointer;
    position: relative;
    font-family: inherit;
    transition: color 0.2s;
}
.tab-button:hover { color: var(--text-primary); }
.tab-button.active { color: var(--text-primary); }
.tab-button.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--green);
    border-radius: 3px 3px 0 0;
}

.tab-content { display: none; animation: fadeIn 0.3s; }
.tab-content.active { display: block; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

/* Bio */
.secao-titulo {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2rem;
    color: var(--text-primary);
    margin-bottom: 16px;
    letter-spacing: 0.5px;
}
.bio-texto {
    font-size: 1.05rem;
    color: var(--text-secondary);
    line-height: 1.6;
    background: var(--surface);
    padding: 24px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 40px;
}
.bio-placeholder {
    color: var(--text-tertiary);
    font-style: italic;
}

/* Lista de Treinos */
.treino-item {
    background: var(--surface);
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.treino-info-data {
    font-size: 0.85rem;
    color: var(--text-tertiary);
    font-weight: 600;
}
.treino-info-titulo {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-top: 4px;
}
.treino-status-icon {
    font-size: 1.2rem;
}

.link-ver-todos {
    display: inline-block;
    color: var(--green-dark);
    font-weight: 600;
    text-decoration: none;
    margin-top: 10px;
}
.link-ver-todos:hover { text-decoration: underline; }

/* Eventos Reutilizados */
.ps-eventos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}
.ps-filter-select {
    padding: 8px 16px;
    border-radius: 100px;
    border: 1.5px solid #ddd;
    background: #fff;
    font-family: inherit;
    font-size: 0.9rem;
    font-weight: 600;
    color: #444;
    cursor: pointer;
    outline: none;
}
.ps-filter-select:focus { border-color: var(--green); }

.ps-evento-card {
    display: flex;
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: transform 0.2s, box-shadow 0.2s;
    margin-bottom: 16px;
}
.ps-evento-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
.ps-evento-img {
    width: 180px;
    background-color: #f5f5f5;
    background-size: cover;
    background-position: center;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ps-evento-info {
    padding: 24px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.ps-evento-info h3 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.8rem;
    letter-spacing: 0.5px;
    margin: 0 0 6px 0;
    color: #111;
    line-height: 1.1;
}
.ps-evento-detalhes {
    font-size: 0.9rem;
    color: #666;
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ------------------------------------------------ */
/* MODAL EDIÇÃO */
/* ------------------------------------------------ */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}
.modal-overlay.show {
    display: flex;
    opacity: 1;
}
.modal-content {
    background: var(--surface);
    width: 90%;
    max-width: 500px;
    border-radius: 24px;
    padding: 30px;
    max-height: 90vh;
    overflow-y: auto;
    transform: translateY(20px);
    transition: transform 0.3s;
    position: relative;
}
.modal-overlay.show .modal-content {
    transform: translateY(0);
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.modal-header h2 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2rem;
    margin: 0;
}
.btn-close-modal {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-tertiary);
}
.form-grupo { margin-bottom: 16px; }
.form-grupo label {
    font-weight: 600;
    font-size: 0.85rem;
    color: #333;
    margin-bottom: 6px;
    display: block;
}
.form-grupo input, .form-grupo textarea, .form-grupo select {
    width: 100%;
    background: #f8f9fa;
    border: 1.5px solid #e5e5e5;
    border-radius: 12px;
    padding: 12px 16px;
    font-family: inherit;
    font-size: 0.95rem;
    transition: all 0.2s;
}
.form-grupo input:focus, .form-grupo textarea:focus, .form-grupo select:focus {
    border-color: var(--green);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(29,185,84,0.1);
    outline: none;
}
.char-counter {
    font-size: 0.75rem;
    color: var(--text-tertiary);
    text-align: right;
    display: block;
    margin-top: 4px;
}
.btn-salvar {
    background: var(--green);
    color: #fff;
    border: none;
    border-radius: 12px;
    width: 100%;
    padding: 14px;
    font-weight: 700;
    font-size: 1.05rem;
    cursor: pointer;
    transition: opacity 0.2s;
    margin-top: 10px;
}
.btn-salvar:hover { opacity: 0.9; }

/* Upload customizado no modal*/
.upload-wrapper {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}
.preview-modal-foto {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--green);
}
.btn-mudar-foto {
    background: rgba(29, 185, 84, 0.1);
    color: var(--green-dark);
    padding: 8px 16px;
    border-radius: 100px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
}

/* ------------------------------------------------ */
/* RESPONSIVO */
/* ------------------------------------------------ */
@media(max-width: 900px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media(max-width: 768px) {
    .perfil-layout { flex-direction: column; }
    .perfil-sidebar {
        width: 100%;
        position: static;
    }
    .ps-evento-card { flex-direction: column; }
    .ps-evento-img { width: 100%; height: 160px; }
}

/* Alertas de sucesso e erro */
.alert-msg {
    padding: 16px 24px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-weight: 600;
    text-align: center;
}
.alert-success { background: rgba(29, 185, 84, 0.1); color: var(--green-dark); }
.alert-error { background: #ffebee; color: #c62828; }

/* ------------------------------------------------ */
/* ESTILOS DO STRAVA */
/* ------------------------------------------------ */
.strava-bloco {
  background: #fff;
  border: 1px solid rgba(0,0,0,0.07);
  border-radius: 16px;
  padding: 20px;
  margin-top: 16px;
}

.strava-conectado {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: .85rem;
  font-weight: 600;
  color: #FC4C02; /* laranja Strava */
  margin-bottom: 16px;
}

.strava-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  text-align: center;
}

.stat-item {
  background: #f5f6f5;
  border-radius: 12px;
  padding: 12px 8px;
}

.stat-numero {
  display: block;
  font-family: 'Bebas Neue', sans-serif;
  font-size: 1.6rem;
  letter-spacing: 1px;
  color: #0d0d0d;
  line-height: 1;
}

.stat-label {
  display: block;
  font-size: .72rem;
  color: #888;
  margin-top: 4px;
  font-weight: 500;
}

/* Botão laranja do Strava */
.btn-strava {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  background: #FC4C02;
  color: #fff;
  border-radius: 999px;
  padding: 12px 24px;
  font-weight: 700;
  font-size: .9rem;
  text-decoration: none;
  transition: background .2s, transform .15s;
  width: 100%;
}

.btn-strava:hover {
  background: #e04400;
  transform: translateY(-1px);
}
</style>

<div class="perfil-layout">
    
    <!-- MENSAGENS -->
    <div style="position: absolute; top: 80px; left: 0; right: 0; max-width: 600px; margin: 0 auto; z-index:100; padding: 0 20px;">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert-msg alert-success">
                <?php
                $mensagens = [
                    'atualizado' => 'Perfil atualizado com sucesso!',
                    'strava_conectado'    => '✅ Strava conectado com sucesso! Seus dados foram sincronizados.',
                    'strava_sincronizado' => '🔄 Dados do Strava atualizados!',
                    'strava_desconectado' => 'Strava desconectado.',
                ];
                echo $mensagens[$_GET['msg']] ?? 'Ação realizada com sucesso!';
                ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['erro'])): ?>
            <div class="alert-msg alert-error">
                <?php
                $erros = [
                    'nome_vazio' => 'O nome não pode ficar vazio.',
                    'foto_invalida' => 'Formato de imagem inválido. Use JPG, PNG ou WebP.',
                    'upload_falhou' => 'Não foi possível enviar a foto.',
                    'strava_negado'       => 'Você negou a autorização do Strava.',
                    'strava_token'        => 'Erro ao conectar com o Strava. Tente novamente.',
                    'strava_refresh'      => 'Sessão do Strava expirada. Reconecte sua conta.',
                    'strava_sync'         => 'Erro ao sincronizar dados. Tente novamente mais tarde.',
                    'strava_state'        => 'Erro de segurança. Tente novamente.'
                ];
                echo $erros[$_GET['erro']] ?? 'Ocorreu um erro.';
                ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- SIDEBAR (Esquerda) -->
    <aside class="perfil-sidebar">
        <div class="ps-avatar-wrapper">
            <?php if (!empty($usuario['foto'])): ?>
                <img src="<?= htmlspecialchars($usuario['foto']) ?>" alt="Avatar" class="ps-avatar"/>
            <?php else: ?>
                <div class="ps-avatar-padrao">
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                </div>
            <?php endif; ?>
        </div>

        <h1 class="ps-nome-view"><?= htmlspecialchars($usuario['nome']) ?></h1>
        <p class="ps-cidade-view">📍 <?= !empty($usuario['cidade']) ? htmlspecialchars($usuario['cidade']) : 'Cidade não informada' ?></p>
        
        <div class="badges-container">
            <?php if (!empty($usuario['tipo_corredor'])): ?>
                <div class="ps-runner-badge"><?= htmlspecialchars($usuario['tipo_corredor']) ?></div>
            <?php endif; ?>
            <?php if (!empty($usuario['nivel'])): ?>
                <div class="ps-nivel-badge"><?= htmlspecialchars($usuario['nivel']) ?></div>
            <?php endif; ?>
        </div>

        <button class="btn-editar-perfil" onclick="abrirModal()">Editar Perfil</button>

        <?php if ($treinador): ?>
            <div class="card-treinador" onclick="window.location.href='/pages/perfil-publico.php?id=<?= $treinador['id'] ?>'">
                <div class="card-treinador-title">🏋️ Seu Treinador</div>
                <div class="card-treinador-info">
                    <?php if (!empty($treinador['foto'])): ?>
                        <img src="<?= htmlspecialchars($treinador['foto']) ?>" class="card-treinador-foto" alt="Treinador">
                    <?php else: ?>
                        <div class="card-treinador-foto"></div>
                    <?php endif; ?>
                    <div>
                        <p class="card-treinador-nome"><?= htmlspecialchars($treinador['nome']) ?></p>
                        <p class="card-treinador-esp"><?= htmlspecialchars($treinador['especialidade']) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- BLOCO STRAVA -->
        <div class="strava-bloco">
          <?php if ($usuario['strava_conectado']): ?>
            <!-- CONECTADO -->
            <div class="strava-conectado">
              <img src="/assets/img/strava-logo.svg" alt="Strava" style="height:20px;">
              <span>Conectado ao Strava</span>
            </div>

            <!-- STATS VINDOS DO STRAVA -->
            <div class="strava-stats">
              <div class="stat-item">
                <span class="stat-numero"><?= number_format($usuario['strava_km_total'], 0, ',', '.') ?></span>
                <span class="stat-label">km no total</span>
              </div>
              <div class="stat-item">
                <span class="stat-numero"><?= number_format($usuario['strava_km_ano'], 0, ',', '.') ?></span>
                <span class="stat-label">km em <?= date('Y') ?></span>
              </div>
              <div class="stat-item">
                <span class="stat-numero"><?= $usuario['strava_atividades_total'] ?></span>
                <span class="stat-label">atividades</span>
              </div>
            </div>

            <!-- AÇÕES -->
            <div style="display:flex; gap:8px; margin-top:12px;">
              <a href="/actions/action-strava-sync.php" class="btn-secondary" style="font-size:.82rem; padding:8px 16px;">
                🔄 Atualizar dados
              </a>
              <a href="/actions/action-strava-disconnect.php"
                 onclick="return confirm('Desconectar o Strava? Seus dados de km serão zerados.')"
                 style="font-size:.82rem; padding:8px 16px; color:#cc0000; border:1.5px solid #cc0000; border-radius:999px; display:inline-flex; align-items:center;">
                Desconectar
              </a>
            </div>

            <?php if ($usuario['strava_sincronizado_em']): ?>
              <p style="font-size:.75rem; color:#aaa; margin-top:8px;">
                Última sync: <?= date('d/m/Y H:i', strtotime($usuario['strava_sincronizado_em'])) ?>
              </p>
            <?php endif; ?>

          <?php else: ?>
            <!-- NÃO CONECTADO -->
            <a href="/actions/action-strava-connect.php" class="btn-strava">
              <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#fff;flex-shrink:0;">
                <path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7 13.828h4.169"/>
              </svg>
              Conectar com Strava
            </a>
            <p style="font-size:.78rem; color:#aaa; margin-top:8px; text-align:center;">
              Sincronize seus km e atividades automaticamente
            </p>
          <?php endif; ?>
        </div>
    </aside>

    <!-- CONTEÚDO PRINCIPAL (Direita) -->
    <main class="perfil-conteudo">
        
        <!-- Stats Cards -->
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
                <div class="stat-numero">—</div> <!-- Futuro -->
                <div class="stat-label">Km rodados</div>
            </div>
            <div class="stat-card">
                <div class="stat-numero"><?= intval($count_semanas) ?></div>
                <div class="stat-label">Semanas de treino</div>
            </div>
        </div>

        <!-- Abas -->
        <div class="tabs-container">
            <button class="tab-button active" onclick="switchTab('sobre', this)">Sobre</button>
            <button class="tab-button" onclick="switchTab('treinos', this)">Treinos</button>
        </div>

        <!-- ABA CONTEÚDO: SOBRE -->
        <div id="tab-sobre" class="tab-content active">
            <h2 class="secao-titulo">Sobre mim</h2>
            <div class="bio-texto">
                <?php if (!empty($usuario['bio'])): ?>
                    <?= nl2br(htmlspecialchars($usuario['bio'])) ?>
                <?php else: ?>
                    <span class="bio-placeholder">Nenhuma bio ainda. Edite seu perfil para adicionar.</span>
                <?php endif; ?>
            </div>

            <div class="ps-eventos-header">
                <h2 class="secao-titulo" style="margin:0;">Eventos deste corredor</h2>
                <select class="ps-filter-select" onchange="window.location.href='?filtro_evento='+this.value">
                    <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos os eventos</option>
                    <option value="proximos" <?= $filtro === 'proximos' ? 'selected' : '' ?>>Próximos</option>
                    <option value="participados" <?= $filtro === 'participados' ? 'selected' : '' ?>>Já participados</option>
                </select>
            </div>

            <?php if (empty($eventos_perfil)): ?>
                <p style="color: #888; background: #fff; padding: 24px; border-radius: 16px; text-align: center; box-shadow: var(--shadow-md);">Nenhum evento encontrado.</p>
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
        </div>

        <!-- ABA CONTEÚDO: TREINOS -->
        <div id="tab-treinos" class="tab-content">
            <h2 class="secao-titulo">Últimos Treinos</h2>
            
            <?php if (empty($ultimos_treinos)): ?>
                <p style="color: #888; background: #fff; padding: 24px; border-radius: 16px; text-align: center; box-shadow: var(--shadow-md);">Você ainda não tem treinos. Encontre um treinador ou adicione você mesmo!</p>
            <?php else: ?>
                <?php foreach ($ultimos_treinos as $treino): 
                    $dtTreino = new DateTime($treino['data_treino']);
                    $isDone = $treino['status'] === 'realizado';
                ?>
                    <div class="treino-item">
                        <div>
                            <div class="treino-info-data"><?= $dtTreino->format('d/m/Y') ?></div>
                            <div class="treino-info-titulo"><?= htmlspecialchars($treino['titulo']) ?></div>
                        </div>
                        <div class="treino-status-icon">
                            <?= $isDone ? '✅' : '⏳' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <a href="/pages/treinos.php" class="link-ver-todos">Ver todos os treinos →</a>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- MODAL DE EDIÇÃO -->
<div class="modal-overlay" id="modalEdicao">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Editar Perfil</h2>
            <button class="btn-close-modal" onclick="fecharModal()">&times;</button>
        </div>

        <form action="/actions/action-perfil.php" method="POST" enctype="multipart/form-data">
            
            <div class="upload-wrapper">
                <?php if (!empty($usuario['foto'])): ?>
                    <img src="<?= htmlspecialchars($usuario['foto']) ?>" alt="Preview" class="preview-modal-foto" id="imgPreviewModal"/>
                <?php else: ?>
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23aaa'%3E%3Cpath d='M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z'/%3E%3C/svg%3E" alt="Preview" class="preview-modal-foto" id="imgPreviewModal"/>
                <?php endif; ?>
                <div>
                    <label for="inputFotoModal" class="btn-mudar-foto">Alterar foto</label>
                    <input type="file" id="inputFotoModal" name="foto" style="display:none;" accept="image/*" onchange="atualizarPreviewModal(this)">
                </div>
            </div>

            <div class="form-grupo">
                <label>E-mail (Não editável)</label>
                <input type="email" value="<?= htmlspecialchars($usuario['email']) ?>" disabled />
            </div>

            <div class="form-grupo">
                <label for="nome">Nome completo *</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required />
            </div>

            <div class="form-grupo">
                <label for="cidade">Cidade</label>
                <input type="text" id="cidade" name="cidade" value="<?= htmlspecialchars($usuario['cidade'] ?? '') ?>" placeholder="Ex: São Paulo, SP" />
            </div>

            <div class="form-grupo">
                <label for="tipo_corredor">Tipo de corredor</label>
                <input type="text" id="tipo_corredor" name="tipo_corredor" list="opcoesTipoCorredor" value="<?= htmlspecialchars($usuario['tipo_corredor'] ?? '') ?>" placeholder="Ex: Trail Runner, Maratonista..." />
                <datalist id="opcoesTipoCorredor">
                    <option value="Trail Runner">
                    <option value="Maratonista">
                    <option value="Velocista">
                    <option value="Fundista">
                    <option value="Triatleta">
                    <option value="Ciclista">
                </datalist>
            </div>

            <div class="form-grupo">
                <label for="nivel">Nível</label>
                <select id="nivel" name="nivel">
                    <option value="" <?= empty($usuario['nivel']) ? 'selected' : '' ?>>Selecione...</option>
                    <option value="Iniciante" <?= ($usuario['nivel'] ?? '') === 'Iniciante' ? 'selected' : '' ?>>Iniciante</option>
                    <option value="Amador" <?= ($usuario['nivel'] ?? '') === 'Amador' ? 'selected' : '' ?>>Amador</option>
                    <option value="Competitivo" <?= ($usuario['nivel'] ?? '') === 'Competitivo' ? 'selected' : '' ?>>Competitivo</option>
                    <option value="Elite" <?= ($usuario['nivel'] ?? '') === 'Elite' ? 'selected' : '' ?>>Elite</option>
                </select>
            </div>

            <div class="form-grupo">
                <label for="bio">Bio</label>
                <textarea id="bio" name="bio" rows="4" maxlength="300" oninput="updateCharCount(this)" placeholder="Ex: 4xUltramaratonista, 6xMaratonista"><?= htmlspecialchars($usuario['bio'] ?? '') ?></textarea>
                <span class="char-counter" id="bioCounter">0/300</span>
            </div>

            <button type="submit" class="btn-salvar">Salvar alterações</button>
        </form>
    </div>
</div>

<script>
// --- ALERTS ---
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert-msg');
    alerts.forEach(a => {
        a.style.transition = 'opacity 0.4s';
        a.style.opacity = '0';
        setTimeout(() => a.remove(), 400);
    });
}, 4000);

// --- TABS LOGIC ---
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
    
    document.getElementById('tab-' + tabId).classList.add('active');
    btn.classList.add('active');
}

// --- MODAL LOGIC ---
const modal = document.getElementById('modalEdicao');

function abrirModal() {
    modal.classList.add('show');
    // Força o contador atualizar ao abrir se houver texto
    updateCharCount(document.getElementById('bio'));
}
function fecharModal() {
    modal.classList.remove('show');
}

// Fechar com ESC e click fora
window.addEventListener('keydown', e => {
    if (e.key === 'Escape') fecharModal();
});
modal.addEventListener('click', e => {
    if (e.target === modal) fecharModal();
});

// --- CHAR COUNTER ---
function updateCharCount(el) {
    const count = el.value.length;
    document.getElementById('bioCounter').innerText = count + '/300';
}
// Init count if needed
document.addEventListener("DOMContentLoaded", () => {
    const bioTextarea = document.getElementById('bio');
    if(bioTextarea) updateCharCount(bioTextarea);
});

// --- PREVIEW FOTO MODAL ---
function atualizarPreviewModal(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        const preview = document.getElementById('imgPreviewModal');
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>