<?php
ob_start();
// ==========================================================
// STRIVELY — pages/configuracoes.php
// Painel de Configurações do Usuário — Redesign Completo
// ==========================================================

$only_session = true;
require_once '../components/header.php';

if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

unset($only_session);
require_once '../config/conexao.php';

$secao = $_GET['secao'] ?? 'seguranca';
$etapa = $_GET['etapa'] ?? 'inicio';
$showMenuOnlyMobile = empty($_GET['secao']);
$userId = $_SESSION['id'];
$isAdmin = ($userId == 2);

// Busca dados do usuário (para Conexões / Zona de Risco)
$stmtUser = $pdo->prepare("
  SELECT strava_conectado, strava_km_total, strava_km_ano,
         strava_atividades_total, strava_sincronizado_em, perfil, treinador_id, status_vinculo
  FROM usuarios WHERE id = ?
");
$stmtUser->execute([$userId]);
$userConfig = $stmtUser->fetch();

$treinador = null;
if (!empty($userConfig['treinador_id']) && $userConfig['status_vinculo'] === 'aceito') {
  $stmtT = $pdo->prepare("SELECT id, nome, foto, cidade FROM usuarios WHERE id = ?");
  $stmtT->execute([$userConfig['treinador_id']]);
  $treinador = $stmtT->fetch();
}

// Notificações de Sistema
$notifSistema = [];
if ($secao === 'notificacoes_sistema') {
  $stmtNs = $pdo->prepare("
    SELECT id, titulo, mensagem, criado_em
    FROM notificacoes_sistema
    ORDER BY criado_em DESC
  ");
  $stmtNs->execute();
  $notifSistema = $stmtNs->fetchAll();
}

// Função para data relativa
function dataRelativa(string $dtStr): string {
  $dt = new DateTime($dtStr);
  $agora = new DateTime();
  $diff = $agora->diff($dt);
  if ($diff->y >= 1) return 'há ' . $diff->y . ' ano' . ($diff->y > 1 ? 's' : '');
  if ($diff->m >= 1) return 'há ' . $diff->m . ' mês' . ($diff->m > 1 ? 'es' : '');
  if ($diff->d >= 1) return 'há ' . $diff->d . ' dia' . ($diff->d > 1 ? 's' : '');
  if ($diff->h >= 1) return 'há ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
  return 'agora pouco';
}
?>

<?php $tituloPagina = "Configurações"; ?>
<?php include '../components/head.php'; ?>
<?php include '../components/header.php'; ?>

<style>
  /* =====================================================
     SETTINGS LAYOUT
  ===================================================== */
  .settings-container {
    display: flex;
    max-width: 1020px;
    margin: 40px auto 60px;
    gap: 28px;
    padding: 0 20px;
    align-items: flex-start;
  }

  /* ---------- SIDEBAR ---------- */
  .settings-sidebar {
    width: 240px;
    flex-shrink: 0;
    position: sticky;
    top: 84px;
    background: #fff;
    border-radius: 16px;
    padding: 20px 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
  }

  .settings-sidebar-title {
    font-family: 'Bebas Neue', sans-serif;
    color: var(--text-main, #111);
    font-size: 1.6rem;
    letter-spacing: 1px;
    padding: 0 10px;
    margin-bottom: 18px;
  }

  .menu-group {
    margin-bottom: 4px;
  }

  .menu-group-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #aaa;
    padding: 10px 10px 4px;
    display: block;
  }

  .menu-group ul {
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .menu-group li {
    margin-bottom: 1px;
  }

  .menu-group a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 10px;
    border-radius: 10px;
    color: #555;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: background 0.15s, color 0.15s;
    cursor: pointer;
  }

  .menu-group a svg {
    width: 17px;
    height: 17px;
    flex-shrink: 0;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .menu-group a:hover {
    background: #f5f6f5;
    color: #111;
  }

  .menu-group a.ativo {
    background: rgba(29, 185, 84, 0.08);
    color: #16a34a;
    font-weight: 600;
  }

  .menu-group a.ativo svg {
    stroke: #16a34a;
  }

  .menu-link-danger {
    color: #dc2626 !important;
  }
  .menu-link-danger svg {
    stroke: #dc2626 !important;
  }
  .menu-link-danger:hover {
    background: rgba(220, 38, 38, 0.06) !important;
    color: #b91c1c !important;
  }
  .menu-link-danger.ativo {
    background: rgba(220, 38, 38, 0.08) !important;
    color: #b91c1c !important;
  }

  .sidebar-divider {
    border: none;
    border-top: 1px solid #f0f0f0;
    margin: 8px 10px;
  }

  /* ---------- CONTENT PANEL ---------- */
  .settings-content {
    flex: 1;
    min-width: 0;
  }

  .settings-pane {
    background: #fff;
    padding: 36px 40px;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
  }

  .pane-header {
    margin-bottom: 28px;
    padding-bottom: 18px;
    border-bottom: 1px solid #f0f0f0;
  }

  .pane-title {
    font-family: 'Bebas Neue', sans-serif;
    color: var(--text-main, #111);
    font-size: 2rem;
    letter-spacing: 1px;
    margin: 0 0 4px;
    line-height: 1;
  }

  .pane-subtitle {
    color: #888;
    font-size: 0.88rem;
    margin: 0;
  }

  .settings-pane p {
    color: #666;
    font-size: 0.93rem;
    margin-bottom: 20px;
    line-height: 1.65;
  }

  .settings-section-block {
    margin-bottom: 32px;
  }

  .settings-section-block:last-child {
    margin-bottom: 0;
  }

  /* ---------- NOTIFICAÇÕES SISTEMA ---------- */
  .btn-admin-add {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--green, #1DB954);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 9px 16px;
    font-weight: 600;
    font-size: 0.88rem;
    cursor: pointer;
    transition: opacity 0.2s;
    margin-bottom: 24px;
  }
  .btn-admin-add:hover { opacity: 0.85; }

  .aviso-card {
    background: #fafafa;
    border: 1px solid #efefef;
    border-radius: 14px;
    padding: 20px 22px;
    margin-bottom: 12px;
    transition: border-color 0.2s;
  }

  .aviso-card:hover { border-color: #ddd; }

  .aviso-titulo {
    font-weight: 700;
    font-size: 0.97rem;
    color: #111;
    margin-bottom: 6px;
  }

  .aviso-mensagem {
    font-size: 0.9rem;
    color: #555;
    line-height: 1.55;
    margin-bottom: 10px;
  }

  .aviso-meta {
    font-size: 0.78rem;
    color: #bbb;
    font-weight: 500;
  }

  .aviso-empty {
    text-align: center;
    padding: 40px 20px;
    color: #bbb;
    font-size: 0.92rem;
  }

  /* ---------- CONEXÕES ---------- */
  .conexao-card {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px 0;
    border-bottom: 1px solid #f0f0f0;
  }
  .conexao-card:last-child { border-bottom: none; }

  .conexao-logo {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .conexao-info {
    flex: 1;
    min-width: 0;
  }

  .conexao-nome {
    font-weight: 700;
    font-size: 0.97rem;
    color: #111;
    margin-bottom: 3px;
  }

  .conexao-descricao {
    font-size: 0.84rem;
    color: #888;
    line-height: 1.4;
    margin-bottom: 12px;
  }

  .strava-card-cfg {
    border: 1px solid rgba(0,0,0,0.07);
    border-radius: 14px;
    padding: 16px;
    background: #fff;
  }

  .strava-conectado-cfg {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .85rem;
    font-weight: 600;
    color: #FC4C02;
    margin-bottom: 14px;
  }

  .strava-stats-cfg {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    text-align: center;
    margin-bottom: 14px;
  }

  .stat-item-cfg {
    background: #f5f6f5;
    border-radius: 10px;
    padding: 10px 6px;
  }

  .stat-num-cfg {
    display: block;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.5rem;
    letter-spacing: 0.5px;
    color: #111;
    line-height: 1;
  }

  .stat-lbl-cfg {
    display: block;
    font-size: .7rem;
    color: #999;
    margin-top: 3px;
    font-weight: 500;
  }

  .strava-btns-cfg {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
  }

  .strava-btns-cfg a,
  .strava-btns-cfg button,
  .strava-btns-cfg form button {
    flex: 1;
    min-width: 0;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: 8px 12px;
    font-size: 0.85rem;
    border-radius: 20px;
    box-sizing: border-box;
    text-decoration: none;
    font-family: inherit;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
  }

  .btn-strava-connect {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #FC4C02;
    color: #fff;
    border: none;
    border-radius: 22px;
    padding: 10px 20px;
    font-weight: 700;
    font-size: .88rem;
    text-decoration: none;
    transition: background .2s;
    width: 100%;
  }
  .btn-strava-connect:hover { background: #e04400; }

  .badge-em-breve {
    display: inline-block;
    background: #f0f0f0;
    color: #999;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    padding: 3px 9px;
    border-radius: 6px;
  }

  .btn-disabled {
    background: #f0f0f0 !important;
    color: #bbb !important;
    cursor: not-allowed !important;
    border: none !important;
    border-radius: 20px;
    padding: 8px 18px;
    font-size: 0.85rem;
    font-family: inherit;
    font-weight: 600;
  }

  /* ---------- ZONA DE RISCO ---------- */
  .risco-alert {
    background: #fff5f5;
    border: 1px solid #fecaca;
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 24px;
  }

  .risco-alert-title {
    font-weight: 700;
    color: #b91c1c;
    font-size: 0.95rem;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .risco-alert ul {
    margin: 0;
    padding-left: 18px;
    color: #555;
    font-size: 0.88rem;
    line-height: 1.7;
  }

  .risco-form-group {
    margin-bottom: 18px;
  }

  .risco-form-group label {
    display: block;
    font-weight: 600;
    font-size: 0.85rem;
    color: #333;
    margin-bottom: 6px;
  }

  .risco-form-group input {
    width: 100%;
    background: #f8f9fa;
    border: 1.5px solid #e5e5e5;
    border-radius: 10px;
    padding: 11px 14px;
    font-family: inherit;
    font-size: 0.95rem;
    box-sizing: border-box;
    transition: border-color 0.2s;
  }

  .risco-form-group input:focus {
    border-color: #dc2626;
    background: #fff;
    outline: none;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.08);
  }

  .risco-form-group small {
    display: block;
    color: #aaa;
    font-size: 0.78rem;
    margin-top: 4px;
  }

  .btn-excluir-conta {
    background: #dc2626;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 13px 24px;
    font-weight: 700;
    font-size: 0.95rem;
    font-family: inherit;
    cursor: pointer;
    width: 100%;
    transition: background 0.2s, opacity 0.2s;
    margin-top: 8px;
  }

  .btn-excluir-conta:hover:not(:disabled) {
    background: #b91c1c;
  }

  .btn-excluir-conta:disabled {
    opacity: 0.4;
    cursor: not-allowed;
  }

  /* ---------- MODAL ADMIN ---------- */
  .modal-overlay-cfg {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
  }

  .modal-overlay-cfg.aberto {
    display: flex;
  }

  .modal-box-cfg {
    background: #fff;
    border-radius: 18px;
    padding: 32px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.18);
  }

  .modal-box-cfg h3 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.8rem;
    letter-spacing: 1px;
    margin: 0 0 20px;
  }

  .modal-cfg-group {
    margin-bottom: 16px;
  }

  .modal-cfg-group label {
    display: block;
    font-weight: 600;
    font-size: 0.85rem;
    color: #333;
    margin-bottom: 6px;
  }

  .modal-cfg-group input,
  .modal-cfg-group textarea {
    width: 100%;
    background: #f8f9fa;
    border: 1.5px solid #e5e5e5;
    border-radius: 10px;
    padding: 11px 14px;
    font-family: inherit;
    font-size: 0.93rem;
    box-sizing: border-box;
    transition: border-color 0.2s;
  }

  .modal-cfg-group textarea {
    resize: vertical;
    min-height: 100px;
  }

  .modal-cfg-group input:focus,
  .modal-cfg-group textarea:focus {
    border-color: var(--green, #1DB954);
    background: #fff;
    outline: none;
    box-shadow: 0 0 0 4px rgba(29,185,84,0.1);
  }

  .modal-cfg-footer {
    display: flex;
    gap: 12px;
    margin-top: 20px;
  }

  .btn-modal-cancel {
    flex: 1;
    background: #f0f0f0;
    color: #555;
    border: none;
    border-radius: 10px;
    padding: 12px;
    font-weight: 600;
    font-size: 0.93rem;
    font-family: inherit;
    cursor: pointer;
    transition: background 0.15s;
  }
  .btn-modal-cancel:hover { background: #e5e5e5; }

  .btn-modal-submit {
    flex: 2;
    background: var(--green, #1DB954);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 12px;
    font-weight: 700;
    font-size: 0.93rem;
    font-family: inherit;
    cursor: pointer;
    transition: opacity 0.2s;
  }
  .btn-modal-submit:hover { opacity: 0.88; }

  /* ---------- FEEDBACK MSGS ---------- */
  .msg-sucesso {
    background: rgba(29, 185, 84, 0.08);
    color: #16a34a;
    border-radius: 10px;
    padding: 12px 16px;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 20px;
  }

  .msg-erro {
    background: #fff5f5;
    color: #dc2626;
    border-radius: 10px;
    padding: 12px 16px;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 20px;
  }

  /* ---------- RESPONSIVE ---------- */
  @media (max-width: 768px) {
    .settings-container {
      flex-direction: column;
      gap: 24px;
      margin: 0 auto 40px;
      padding: 0;
    }
    .settings-content {
      padding: 0 14px;
    }
    .settings-sidebar {
      width: 100%;
      position: static;
      top: auto;
      padding: 0;
      margin-bottom: 8px;
      background: transparent;
      box-shadow: none;
      border-radius: 0;
      border: none;
    }
    .settings-sidebar-title {
      padding: 0 20px;
      margin-top: 20px;
    }
    .menu-group {
      margin-top: 24px;
      margin-bottom: 0;
    }
    .menu-group-label {
      font-size: 11px;
      padding: 0 20px 6px;
    }
    .menu-group li {
      margin-bottom: 0;
    }
    .menu-group a {
      width: 100%;
      box-sizing: border-box;
      padding: 14px 20px;
      font-size: 1rem;
      background: #ffffff;
      border-radius: 0;
      border-bottom: 1px solid #f0f0f0;
      justify-content: space-between;
    }
    .sidebar-divider {
      display: none;
    }
    .settings-pane {
      padding: 24px 20px;
    }
    .strava-stats-cfg {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 480px) {
    .strava-btns-cfg {
      flex-direction: column;
    }
  }

  /* ---------- MOBILE DRILL-DOWN ---------- */
  .btn-voltar-cfg-mobile {
    display: none;
  }
  .menu-arrow-mobile {
    display: none;
  }
  @media (max-width: 768px) {
    .settings-container.mobile-show-menu .settings-content {
      display: none !important;
    }
    .settings-container.mobile-show-content .settings-sidebar {
      display: none !important;
    }
    .settings-content {
      animation: fadeRightCfg 0.25s ease forwards;
    }
    @keyframes fadeRightCfg {
      from { opacity: 0; transform: translateX(24px); }
      to { opacity: 1; transform: translateX(0); }
    }
    .btn-voltar-cfg-mobile {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #777;
      font-weight: 600;
      font-size: 0.95rem;
      text-decoration: none;
      margin-bottom: 20px;
      transition: color 0.15s;
    }
    .btn-voltar-cfg-mobile:hover {
      color: var(--green);
    }
    .btn-voltar-cfg-mobile svg {
      width: 18px;
      height: 18px;
      fill: currentColor;
    }
    .menu-arrow-mobile {
      display: inline-block;
      margin-left: auto;
      font-size: 1.4rem;
      color: #ccc;
      font-weight: 300;
      line-height: 1;
    }
  }
</style>

<body>

  <section class="settings-container <?= $showMenuOnlyMobile ? 'mobile-show-menu' : 'mobile-show-content' ?>">

    <!-- ═══════════════════════════════════
         MENU LATERAL
    ════════════════════════════════════ -->
    <aside class="settings-sidebar">
      <h1 class="settings-sidebar-title">Configurações</h1>

      <!-- CONTA -->
      <div class="menu-group">
        <span class="menu-group-label">Conta</span>
        <ul>
          <li>
            <a href="/pages/perfil.php">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
              Conta e Perfil <span class="menu-arrow-mobile">›</span>
            </a>
          </li>
          <li>
            <a href="?secao=treinador" class="<?= $secao === 'treinador' ? 'ativo' : '' ?>">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              Meu Treinador <span class="menu-arrow-mobile">›</span>
            </a>
          </li>
        </ul>
      </div>

      <hr class="sidebar-divider">

      <!-- SEGURANÇA -->
      <div class="menu-group">
        <span class="menu-group-label">Segurança</span>
        <ul>
          <li>
            <a href="?secao=seguranca" class="<?= $secao === 'seguranca' ? 'ativo' : '' ?>">
              <svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
              Trocar Senha <span class="menu-arrow-mobile">›</span>
            </a>
          </li>
        </ul>
      </div>

      <hr class="sidebar-divider">

      <!-- PREFERÊNCIAS -->
      <div class="menu-group">
        <span class="menu-group-label">Preferências</span>
        <ul>
          <li>
            <a href="?secao=notificacoes_sistema" class="<?= $secao === 'notificacoes_sistema' ? 'ativo' : '' ?>">
              <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
              Avisos do Strively <span class="menu-arrow-mobile">›</span>
            </a>
          </li>
          <li>
            <a href="?secao=conexoes" class="<?= $secao === 'conexoes' ? 'ativo' : '' ?>">
              <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
              Conexões <span class="menu-arrow-mobile">›</span>
            </a>
          </li>
        </ul>
      </div>

      <hr class="sidebar-divider">

      <!-- ZONA DE RISCO -->
      <div class="menu-group">
        <span class="menu-group-label">Zona de Risco</span>
        <ul>
          <li>
            <a href="?secao=zona-de-risco" class="menu-link-danger <?= $secao === 'zona-de-risco' ? 'ativo' : '' ?>">
              <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
              Excluir Conta <span class="menu-arrow-mobile">›</span>
            </a>
          </li>
        </ul>
      </div>

    </aside>

    <!-- ═══════════════════════════════════
         CONTEÚDO CENTRAL
    ════════════════════════════════════ -->
    <main class="settings-content">
      <a href="/pages/configuracoes.php" class="btn-voltar-cfg-mobile">
        <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg> Configurações
      </a>

      <?php /* ── MEU TREINADOR ── */ ?>
      <?php if ($secao === 'treinador'): ?>
        <div class="settings-pane">
          <div class="pane-header">
            <h2 class="pane-title">Meu Treinador</h2>
            <p class="pane-subtitle">Gerencie seu vínculo com seu treinador ou assessoria.</p>
          </div>
          
          <?php if (isset($_GET['msg']) && $_GET['msg'] === 'treinador_removido'): ?>
            <div class="msg-sucesso">✓ Treinador desvinculado com sucesso!</div>
          <?php endif; ?>

          <?php if ($treinador): ?>
            <div class="conexao-card" style="align-items:center; border: 1px solid #f0f0f0; border-radius: 12px; padding: 20px;">
               <div class="conexao-logo" style="width: 56px; height: 56px; border-radius: 50%; overflow: hidden; background: #f5f5f5;">
                 <?php if (!empty($treinador['foto'])): ?>
                   <img src="<?= htmlspecialchars(strpos($treinador['foto'], 'http')===0 ? $treinador['foto'] : '/'.$treinador['foto']) ?>" style="width:100%; height:100%; object-fit:cover;" alt="Foto do treinador">
                 <?php else: ?>
                   <svg viewBox="0 0 24 24" style="width:30px;height:30px;fill:#ccc;margin-top:13px"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                 <?php endif; ?>
               </div>
               <div class="conexao-info" style="flex:1;">
                 <div class="conexao-nome"><?= htmlspecialchars($treinador['nome']) ?></div>
                 <div class="conexao-descricao" style="margin-bottom:0;"><?= htmlspecialchars($treinador['cidade'] ?? 'Treinador') ?></div>
               </div>
               <div>
                  <button class="btn-cancelar-treinador" onclick="abrirModalCancelarTreinador()" style="background:#fff0f0;color:#dc2626;border:none;padding:10px 18px;border-radius:10px;font-weight:600;font-size:0.85rem;cursor:pointer;">Desvincular</button>
               </div>
            </div>
          <?php else: ?>
            <div class="aviso-empty">
              <svg viewBox="0 0 24 24" style="width:40px;height:40px;stroke:#ddd;fill:none;stroke-width:1.5;margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;"><circle cx="12" cy="12" r="10"/><path d="M8 12h8m-4-4v8"/></svg>
              Você não possui um treinador vinculado.
              <div style="margin-top:16px;">
                <a href="/pages/buscar-treinador.php" class="btn-primary" style="display:inline-block; text-decoration:none; padding:10px 20px; font-size:0.9rem; border-radius:10px;">Buscar Treinador</a>
              </div>
            </div>
          <?php endif; ?>
        </div>
      
      <?php /* ── SEGURANÇA ── */ ?>
      <?php elseif ($secao === 'seguranca'): ?>
        <div class="settings-pane">
          <div class="pane-header">
            <h2 class="pane-title">Trocar Senha</h2>
            <p class="pane-subtitle">Altere a senha da sua conta com verificação por e-mail.</p>
          </div>

          <?php if ($etapa === 'inicio'): ?>
            <?php if (isset($_GET['erro'])): ?>
              <div class="msg-erro">
                <?php
                  $erros = [
                    'senhas_diferentes' => 'As senhas não coincidem.',
                    'senha_curta'       => 'A nova senha deve ter pelo menos 6 caracteres.',
                    'email_falhou'      => 'Falha ao enviar e-mail. Tente novamente.',
                    'codigo_invalido'   => 'Código incorreto ou expirado. Tente novamente.'
                  ];
                  echo $erros[$_GET['erro']] ?? 'Ocorreu um erro.';
                ?>
              </div>
            <?php endif; ?>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'senha_alterada'): ?>
              <div class="msg-sucesso">✓ Sua senha foi alterada com sucesso!</div>
            <?php endif; ?>

            <p>Para proteger sua conta, enviaremos um código de verificação para o e-mail do seu cadastro. O código tem validade de 10 minutos.</p>

            <form action="/actions/action-enviar-codigo.php" method="POST">
              <div class="form-grupo" style="margin-bottom:16px;">
                <label for="nova_senha">Nova Senha</label>
                <input type="password" id="nova_senha" name="nova_senha" placeholder="Mínimo 6 caracteres" required minlength="6">
              </div>
              <div class="form-grupo" style="margin-bottom:24px;">
                <label for="confirma_senha">Confirmar Nova Senha</label>
                <input type="password" id="confirma_senha" name="confirma_senha" placeholder="Repita a nova senha" required minlength="6">
              </div>
              <button type="submit" class="btn-primary" style="width:100%;font-size:1rem;padding:14px 0;">Enviar Código de Verificação</button>
            </form>

          <?php elseif ($etapa === 'verificacao'): ?>
            <p>Enviamos um código de 6 dígitos para o seu e-mail. Digite-o abaixo para confirmar a alteração.</p>

            <?php if (isset($_GET['erro'])): ?>
              <div class="msg-erro">
                <?php
                  $erros = [
                    'codigo_invalido' => 'O código inserido está incorreto.',
                    'codigo_expirado' => 'O código expirou após 10 minutos. Solicite novamente.',
                  ];
                  echo $erros[$_GET['erro']] ?? 'Ocorreu um erro.';
                ?>
              </div>
            <?php endif; ?>

            <form action="/actions/action-confirmar-senha.php" method="POST">
              <div class="form-grupo" style="margin-bottom:32px;">
                <label for="codigo">Código Numérico</label>
                <input type="text" id="codigo" name="codigo" placeholder="000000" required maxlength="6"
                  style="font-size:24px;letter-spacing:6px;font-weight:600;font-family:monospace;text-align:center;max-width:250px;">
              </div>
              <div style="display:flex;gap:16px;align-items:center;">
                <button type="submit" class="btn-primary" style="flex:1;padding:14px 0;font-size:1rem;">Confirmar e Salvar</button>
                <a href="?secao=seguranca" style="color:var(--text-muted,#888);text-decoration:underline;padding:10px;">Cancelar</a>
              </div>
            </form>

          <?php endif; ?>
        </div>

      <?php /* ── AVISOS DO STRIVELY ── */ ?>
      <?php elseif ($secao === 'notificacoes_sistema'): ?>
        <div class="settings-pane">
          <div class="pane-header">
            <h2 class="pane-title">Avisos do Strively</h2>
            <p class="pane-subtitle">Comunicados oficiais da plataforma para todos os usuários.</p>
          </div>

          <?php if (isset($_GET['msg']) && $_GET['msg'] === 'criado'): ?>
            <div class="msg-sucesso">✓ Aviso publicado com sucesso!</div>
          <?php endif; ?>

          <?php if ($isAdmin): ?>
            <button class="btn-admin-add" onclick="abrirModalAviso()">
              <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:#fff;fill:none;stroke-width:2.5;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Adicionar Aviso
            </button>
          <?php endif; ?>

          <?php if (empty($notifSistema)): ?>
            <div class="aviso-empty">
              <svg viewBox="0 0 24 24" style="width:40px;height:40px;stroke:#ddd;fill:none;stroke-width:1.5;margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
              Nenhum aviso publicado ainda.
            </div>
          <?php else: ?>
            <?php foreach ($notifSistema as $aviso): ?>
              <div class="aviso-card">
                <div class="aviso-titulo"><?= htmlspecialchars($aviso['titulo']) ?></div>
                <div class="aviso-mensagem"><?= nl2br(htmlspecialchars($aviso['mensagem'])) ?></div>
                <div class="aviso-meta"><?= dataRelativa($aviso['criado_em']) ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      <?php /* ── CONEXÕES ── */ ?>
      <?php elseif ($secao === 'conexoes'): ?>
        <div class="settings-pane">
          <div class="pane-header">
            <h2 class="pane-title">Conexões</h2>
            <p class="pane-subtitle">Gerencie integrações com plataformas externas.</p>
          </div>

          <!-- STRAVA -->
          <div class="conexao-card">
            <div class="conexao-logo" style="background:rgba(252, 76, 2, 0.08);border:1px solid rgba(252, 76, 2, 0.2);">
              <svg viewBox="0 0 24 24" style="width:24px;height:24px;stroke:#FC4C02;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" /></svg>
            </div>
            <div class="conexao-info">
              <div class="conexao-nome">Strava</div>
              <div class="conexao-descricao">Sincronize seus km, atividades e treinos automaticamente do Strava para o Strively.</div>

              <div class="strava-card-cfg">
                <?php if ($userConfig['strava_conectado']): ?>
                  <div class="strava-conectado-cfg">
                    <img src="/assets/img/strava-logo.svg" alt="Strava" style="height:18px;">
                    <span>Conectado ao Strava</span>
                  </div>

                  <div class="strava-stats-cfg">
                    <div class="stat-item-cfg">
                      <span class="stat-num-cfg"><?= number_format($userConfig['strava_km_total'], 0, ',', '.') ?></span>
                      <span class="stat-lbl-cfg">km no total</span>
                    </div>
                    <div class="stat-item-cfg">
                      <span class="stat-num-cfg"><?= number_format($userConfig['strava_km_ano'], 0, ',', '.') ?></span>
                      <span class="stat-lbl-cfg">km em <?= date('Y') ?></span>
                    </div>
                    <div class="stat-item-cfg">
                      <span class="stat-num-cfg"><?= $userConfig['strava_atividades_total'] ?></span>
                      <span class="stat-lbl-cfg">atividades</span>
                    </div>
                  </div>

                  <div class="strava-btns-cfg">
                    <button type="button" class="btn-secondary" id="btnSyncStravaConfig" onclick="sincronizarStravaConfig('rotina')">
                      🔄 Atualizar
                    </button>
                    <a href="/actions/action-strava-disconnect.php"
                       onclick="return confirm('Desconectar o Strava? Seus dados de km serão zerados.')"
                       style="color:#cc0000;border:1.5px solid #cc0000;">
                      Desconectar
                    </a>
                  </div>

                  <?php if ($userConfig['strava_sincronizado_em']): ?>
                    <p style="font-size:.75rem;color:#aaa;margin-top:8px;margin-bottom:0;">
                      Última sync: <?= date('d/m/Y H:i', strtotime($userConfig['strava_sincronizado_em'])) ?>
                    </p>
                  <?php endif; ?>

                <?php else: ?>
                  <a href="/actions/action-strava-connect.php" class="btn-strava-connect">
                    <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:#fff;flex-shrink:0;">
                      <path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7 13.828h4.169"/>
                    </svg>
                    Conectar com Strava
                  </a>
                  <p style="font-size:.78rem;color:#aaa;margin-top:8px;text-align:center;margin-bottom:0;">
                    Sincronize seus km e atividades automaticamente
                  </p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- POLAR -->
          <div class="conexao-card">
            <div class="conexao-logo" style="background:rgba(0, 114, 206, 0.08);border:1px solid rgba(0, 114, 206, 0.2);">
              <svg viewBox="0 0 24 24" style="width:24px;height:24px;stroke:#0072ce;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            </div>
            <div class="conexao-info">
              <div class="conexao-nome">Polar <span class="badge-em-breve" style="margin-left:6px;">Em breve</span></div>
              <div class="conexao-descricao">Importe seus dados de treino e frequência cardíaca diretamente do Polar Flow.</div>
              <button class="btn-disabled" disabled>Conectar</button>
            </div>
          </div>

          <!-- GARMIN -->
          <div class="conexao-card">
            <div class="conexao-logo" style="background:rgba(0, 168, 89, 0.08);border:1px solid rgba(0, 168, 89, 0.2);">
              <svg viewBox="0 0 24 24" style="width:24px;height:24px;stroke:#00A859;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><circle cx="12" cy="12" r="7"></circle><polyline points="12 9 12 12 13.5 13.5"></polyline></svg>
            </div>
            <div class="conexao-info">
              <div class="conexao-nome">Garmin <span class="badge-em-breve" style="margin-left:6px;">Em breve</span></div>
              <div class="conexao-descricao">Conecte sua conta Garmin Connect para importar treinos, pace e potência.</div>
              <button class="btn-disabled" disabled>Conectar</button>
            </div>
          </div>

          <!-- COROS -->
          <div class="conexao-card">
            <div class="conexao-logo" style="background:rgba(75, 85, 99, 0.08);border:1px solid rgba(75, 85, 99, 0.2);">
              <svg viewBox="0 0 24 24" style="width:24px;height:24px;stroke:#4b5563;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><circle cx="12" cy="12" r="9"></circle><path d="M12 3v18M3 12h18" /></svg>
            </div>
            <div class="conexao-info">
              <div class="conexao-nome">Coros <span class="badge-em-breve" style="margin-left:6px;">Em breve</span></div>
              <div class="conexao-descricao">Sincronize atividades do seu relógio Coros com o Strively.</div>
              <button class="btn-disabled" disabled>Conectar</button>
            </div>
          </div>

        </div>

      <?php /* ── ZONA DE RISCO ── */ ?>
      <?php elseif ($secao === 'zona-de-risco'): ?>
        <div class="settings-pane">
          <div class="pane-header">
            <h2 class="pane-title" style="color:#dc2626;">Excluir Conta</h2>
            <p class="pane-subtitle">Esta ação é permanente e não pode ser desfeita.</p>
          </div>

          <?php if (isset($_GET['erro'])): ?>
            <div class="msg-erro">
              <?php
                $errosMsgs = [
                  'senha_incorreta'  => 'Senha incorreta. Tente novamente.',
                  'confirmacao_invalida' => 'Você deve digitar a palavra EXCLUIR exatamente.',
                  'falha_exclusao'   => 'Erro interno. Nenhum dado foi apagado. Tente novamente.',
                ];
                echo $errosMsgs[$_GET['erro']] ?? 'Ocorreu um erro.';
              ?>
            </div>
          <?php endif; ?>

          <div class="risco-alert">
            <div class="risco-alert-title">
              <svg viewBox="0 0 24 24" style="width:18px;height:18px;stroke:#b91c1c;fill:none;stroke-width:2;flex-shrink:0;">
                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
              </svg>
              O que será apagado permanentemente:
            </div>
            <ul>
              <li>Todos os seus treinos e histórico de atividades</li>
              <li>Posts, curtidas e comentários publicados na comunidade</li>
              <li>Fotos de perfil e fotos de posts no armazenamento</li>
              <li>Vínculo com seu treinador (ou com seus alunos, se for treinador)</li>
              <li>Conexão e dados do Strava sincronizados</li>
              <li>Eventos e notificações pessoais</li>
              <li>Sua conta de login — acesso encerrado permanentemente</li>
            </ul>
          </div>

          <form action="/actions/action-excluir-conta.php" method="POST" id="formExcluirConta">
            <div class="risco-form-group">
              <label for="senha_atual_risco">Confirme sua senha atual</label>
              <input type="password" id="senha_atual_risco" name="senha_atual" placeholder="Digite sua senha" autocomplete="current-password">
            </div>
            <div class="risco-form-group">
              <label for="confirmar_exclusao">Digite <strong>EXCLUIR</strong> para confirmar</label>
              <input type="text" id="confirmar_exclusao" name="confirmar_exclusao" placeholder="EXCLUIR" autocomplete="off">
              <small>Exatamente como mostrado, em letras maiúsculas.</small>
            </div>
            <button type="submit" class="btn-excluir-conta" id="btnExcluirConta" disabled>
              Excluir Conta Permanentemente
            </button>
          </form>
        </div>

      <?php endif; ?>

    </main>

  </section>

  <!-- ═══════════════════════════════════
       MODAL ADMIN — ADICIONAR AVISO
  ════════════════════════════════════ -->
  <?php if ($isAdmin): ?>
    <div class="modal-overlay-cfg" id="modalAdminAviso">
      <div class="modal-box-cfg">
        <h3>Novo Aviso do Sistema</h3>
        <form action="/actions/action-criar-notificacao-sistema.php" method="POST">
          <div class="modal-cfg-group">
            <label for="ns_titulo">Título</label>
            <input type="text" id="ns_titulo" name="titulo" placeholder="Ex: Manutenção programada" required>
          </div>
          <div class="modal-cfg-group">
            <label for="ns_mensagem">Mensagem</label>
            <textarea id="ns_mensagem" name="mensagem" placeholder="Descreva o aviso em detalhes..." required></textarea>
          </div>
          <div class="modal-cfg-footer">
            <button type="button" class="btn-modal-cancel" onclick="fecharModalAviso()">Cancelar</button>
            <button type="submit" class="btn-modal-submit">Publicar Aviso</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- MODAL CANCELAR TREINADOR -->
  <?php if (!empty($treinador)): ?>
    <div class="modal-overlay-cfg" id="modalCancelarTreinador">
      <div class="modal-box-cfg" style="max-width:400px;text-align:center;">
        <h3 style="color:#dc2626;">Desvincular Treinador</h3>
        <p style="color:#555;font-size:0.95rem;margin-bottom:24px;">Tem certeza que deseja desvincular de <?= htmlspecialchars($treinador['nome'] ?? '') ?>? Você perderá acesso aos treinos planejados por ele.</p>
        <form action="/actions/action-remover-treinador.php" method="POST">
          <input type="hidden" name="treinador_id" value="<?= $treinador['id'] ?? '' ?>">
          <input type="hidden" name="return_url" value="/pages/configuracoes.php?secao=treinador&msg=treinador_removido">
          <div class="modal-cfg-footer" style="margin-top:0;">
            <button type="button" class="btn-modal-cancel" onclick="fecharModalCancelarTreinador()">Cancelar</button>
            <button type="submit" class="btn-modal-submit" style="background:#dc2626;">Confirmar Desvínculo</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- Toast para feedback da sync Strava -->
  <div id="cfg-toast" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#111;color:#fff;padding:12px 22px;border-radius:12px;font-size:.9rem;font-weight:600;z-index:9999;white-space:nowrap;box-shadow:0 4px 20px rgba(0,0,0,0.25);"></div>

<script>
  /* ── Zona de Risco — Enable/Disable button ── */
  (function() {
    const senhaInput     = document.getElementById('senha_atual_risco');
    const confirmInput   = document.getElementById('confirmar_exclusao');
    const btnExcluir     = document.getElementById('btnExcluirConta');

    if (!senhaInput || !confirmInput || !btnExcluir) return;

    function verificar() {
      const senhaOk    = senhaInput.value.trim().length >= 1;
      const confirmOk  = confirmInput.value.trim() === 'EXCLUIR';
      btnExcluir.disabled = !(senhaOk && confirmOk);
    }

    senhaInput.addEventListener('input', verificar);
    confirmInput.addEventListener('input', verificar);
  })();

  /* ── Modal Admin Aviso ── */
  function abrirModalAviso() {
    const m = document.getElementById('modalAdminAviso');
    if (m) m.classList.add('aberto');
    if (typeof lockScroll === 'function') lockScroll();
  }

  function fecharModalAviso() {
    const m = document.getElementById('modalAdminAviso');
    if (m) m.classList.remove('aberto');
    if (typeof unlockScroll === 'function') unlockScroll();
  }

  document.getElementById('modalAdminAviso')?.addEventListener('click', function(e) {
    if (e.target === this) fecharModalAviso();
  });

  /* ── Modal Cancelar Treinador ── */
  function abrirModalCancelarTreinador() {
    const m = document.getElementById('modalCancelarTreinador');
    if (m) m.classList.add('aberto');
    if (typeof lockScroll === 'function') lockScroll();
  }

  function fecharModalCancelarTreinador() {
    const m = document.getElementById('modalCancelarTreinador');
    if (m) m.classList.remove('aberto');
    if (typeof unlockScroll === 'function') unlockScroll();
  }

  document.getElementById('modalCancelarTreinador')?.addEventListener('click', function(e) {
    if (e.target === this) fecharModalCancelarTreinador();
  });

  /* ── Strava Sync na página Conexões ── */
  function sincronizarStravaConfig(modo) {
    const btn = document.getElementById('btnSyncStravaConfig');
    if (btn) { btn.innerHTML = '🔄 Sincronizando...'; btn.disabled = true; }

    fetch('/actions/action-strava-sync.php?modo=' + modo)
    .then(r => r.json())
    .then(data => {
      if (btn) { btn.innerHTML = '🔄 Atualizar'; btn.disabled = false; }
      mostrarToastCfg(data.success
        ? (data.criados > 0 ? `${data.criados} atividade(s) importada(s)!` : 'Dados Strava atualizados!')
        : (data.erro || 'Erro ao sincronizar.'));
      if (data.success) setTimeout(() => location.reload(), 1500);
    })
    .catch(() => {
      if (btn) { btn.innerHTML = '🔄 Atualizar'; btn.disabled = false; }
      mostrarToastCfg('Erro ao conectar com o Strava.');
    });
  }

  function mostrarToastCfg(msg) {
    const t = document.getElementById('cfg-toast');
    if (!t) return;
    t.textContent = msg;
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 3000);
  }
</script>

<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>
