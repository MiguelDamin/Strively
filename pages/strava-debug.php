<?php
// ============================================================
// STRIVELY — pages/strava-debug.php
// Página temporária de diagnóstico da integração Strava
// REMOVER após resolver o problema!
// ============================================================
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

// Proteção básica — só admin ou logado
if (!isset($_SESSION['id'])) {
    die('<h2>Faça login primeiro</h2>');
}

$clientId    = $_ENV['STRAVA_CLIENT_ID']   ?? 'NÃO DEFINIDO';
$redirectUri = $_ENV['STRAVA_REDIRECT_URI'] ?? 'NÃO DEFINIDO';
$secret      = $_ENV['STRAVA_CLIENT_SECRET'] ?? 'NÃO DEFINIDO';
$scope       = 'read,activity:read_all,profile:read_all';
$state       = 'DEBUG_STATE_123';

$urlGerada = "https://www.strava.com/oauth/authorize"
    . "?client_id={$clientId}"
    . "&redirect_uri=" . $redirectUri
    . "&response_type=code"
    . "&approval_prompt=auto"
    . "&scope={$scope}"
    . "&state={$state}";

// Verifica se a tabela strava_states existe
$tabelaExiste = false;
try {
    $pdo->query("SELECT 1 FROM strava_states LIMIT 1");
    $tabelaExiste = true;
} catch (Exception $e) {
    $tabelaExiste = false;
}

$sessionId = session_id();
$sessionData = $_SESSION;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Strava Debug — Strively</title>
    <style>
        body { font-family: monospace; background: #0f0f0f; color: #e0e0e0; padding: 20px; line-height: 1.6; }
        h1 { color: #FC4C02; font-family: sans-serif; }
        h2 { color: #FC4C02; font-size: 1rem; margin-top: 24px; border-bottom: 1px solid #333; padding-bottom: 4px; }
        .ok   { color: #4caf50; }
        .erro { color: #f44336; }
        .aviso { color: #ff9800; }
        .box { background: #1a1a1a; border: 1px solid #333; border-radius: 6px; padding: 14px; margin: 8px 0; word-break: break-all; }
        a { color: #FC4C02; }
        table { border-collapse: collapse; width: 100%; }
        td { padding: 4px 8px; border: 1px solid #333; }
        td:first-child { color: #888; width: 220px; }
    </style>
</head>
<body>
<h1>🔧 Strava Debug</h1>
<p class="aviso">⚠️ Página temporária de diagnóstico. Remover após resolver.</p>

<h2>1. Variáveis de ambiente</h2>
<table>
    <tr>
        <td>STRAVA_CLIENT_ID</td>
        <td class="<?= $clientId !== 'NÃO DEFINIDO' ? 'ok' : 'erro' ?>"><?= htmlspecialchars($clientId) ?></td>
    </tr>
    <tr>
        <td>STRAVA_REDIRECT_URI</td>
        <td class="<?= $redirectUri !== 'NÃO DEFINIDO' ? 'ok' : 'erro' ?>"><?= htmlspecialchars($redirectUri) ?></td>
    </tr>
    <tr>
        <td>STRAVA_CLIENT_SECRET</td>
        <td class="<?= $secret !== 'NÃO DEFINIDO' ? 'ok' : 'erro' ?>"><?= substr(htmlspecialchars($secret), 0, 6) ?>... (<?= strlen($secret) ?> chars)</td>
    </tr>
</table>

<h2>2. URL OAuth que seria gerada</h2>
<div class="box">
    <?= htmlspecialchars($urlGerada) ?>
</div>
<p><a href="<?= htmlspecialchars($urlGerada) ?>" target="_blank">👉 Testar essa URL</a></p>

<h2>3. Sessão PHP</h2>
<table>
    <tr>
        <td>session_id()</td>
        <td><?= htmlspecialchars($sessionId) ?></td>
    </tr>
    <tr>
        <td>$_SESSION['id']</td>
        <td class="ok"><?= htmlspecialchars($_SESSION['id'] ?? 'não definido') ?></td>
    </tr>
    <tr>
        <td>$_SESSION['strava_state']</td>
        <td class="<?= isset($_SESSION['strava_state']) ? 'ok' : 'aviso' ?>"><?= htmlspecialchars($_SESSION['strava_state'] ?? '— não existe') ?></td>
    </tr>
    <tr>
        <td>session_save_path()</td>
        <td><?= htmlspecialchars(session_save_path()) ?></td>
    </tr>
</table>

<h2>4. Tabela strava_states (fix CSRF)</h2>
<p class="<?= $tabelaExiste ? 'ok' : 'erro' ?>">
    <?= $tabelaExiste ? '✅ Tabela strava_states existe' : '❌ Tabela strava_states NÃO existe — precisa criar!' ?>
</p>

<h2>5. User-Agent (mobile detection)</h2>
<div class="box"><?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'vazio') ?></div>

<?php
$isMobile = preg_match('/Mobile|Android|BlackBerry|iPhone|iPod|iPad|Windows Phone/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
echo '<p>Detectado como: <strong class="' . ($isMobile ? 'aviso' : 'ok') . '">' . ($isMobile ? '📱 MOBILE' : '🖥️ DESKTOP') . '</strong></p>';
?>

</body>
</html>
