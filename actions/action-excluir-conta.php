<?php
// ==========================================================
// STRIVELY — actions/action-excluir-conta.php
// Exclui permanentemente a conta do usuário logado
// TODA a lógica dentro de uma transação PDO (ACID)
// ==========================================================

$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

// Só aceita POST e usuário logado
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
  header('Location: /pages/configuracoes.php?secao=zona-de-risco');
  exit();
}

$userId        = (int)$_SESSION['id'];
$senhaDigitada = $_POST['senha_atual']         ?? '';
$confirmacao   = trim($_POST['confirmar_exclusao'] ?? '');

// Validar campo de confirmação no backend também
if ($confirmacao !== 'EXCLUIR') {
  header('Location: /pages/configuracoes.php?secao=zona-de-risco&erro=confirmacao_invalida');
  exit();
}

// ----------------------------------------------------------
// Buscar dados do usuário antes de qualquer operação
// ----------------------------------------------------------
$stmtUser = $pdo->prepare("
  SELECT senha, foto, perfil, strava_conectado
  FROM usuarios WHERE id = ?
");
$stmtUser->execute([$userId]);
$usuario = $stmtUser->fetch();

if (!$usuario) {
  header('Location: /pages/configuracoes.php?secao=zona-de-risco&erro=falha_exclusao');
  exit();
}

// ----------------------------------------------------------
// Verificar senha antes de qualquer DELETE
// ----------------------------------------------------------
if (!password_verify($senhaDigitada, $usuario['senha'])) {
  header('Location: /pages/configuracoes.php?secao=zona-de-risco&erro=senha_incorreta');
  exit();
}

// ----------------------------------------------------------
// Função auxiliar: excluir arquivo do Supabase Storage
// Falha silenciosa — nunca bloqueia o fluxo de exclusão
// ----------------------------------------------------------
function deletarArquivoSupabase(string $url): void {
  if (empty($url)) return;

  // Extrai o bucket e path da URL pública do Supabase
  // Formato: https://xxx.supabase.co/storage/v1/object/public/BUCKET/path/arquivo.ext
  $pattern = '#/storage/v1/object/public/([^/]+)/(.+)$#';
  if (!preg_match($pattern, $url, $matches)) return;

  $bucket   = $matches[1];
  $filepath = $matches[2];

  $supabaseUrl = $_ENV['SUPABASE_URL'] ?? '';
  $serviceKey  = $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '';

  if (empty($supabaseUrl) || empty($serviceKey)) return;

  $endpoint = $supabaseUrl . '/storage/v1/object/' . $bucket . '/' . rawurlencode($filepath);

  $ctx = stream_context_create([
    'http' => [
      'method'        => 'DELETE',
      'header'        => "Authorization: Bearer " . $serviceKey . "\r\n",
      'ignore_errors' => true,
      'timeout'       => 10,
    ]
  ]);

  @file_get_contents($endpoint, false, $ctx);
  // Falha silenciosa intencional
}

// ----------------------------------------------------------
// TRANSAÇÃO PRINCIPAL
// ----------------------------------------------------------
try {
  $pdo->beginTransaction();

  // 1. Curtidas do usuário
  $pdo->prepare("DELETE FROM post_curtidas WHERE usuario_id = ?")
      ->execute([$userId]);

  // 2. Fotos dos posts → buscar URLs para deletar do Storage
  $stmtFotos = $pdo->prepare("SELECT foto FROM posts WHERE usuario_id = ? AND foto IS NOT NULL");
  $stmtFotos->execute([$userId]);
  $fotosPosts = $stmtFotos->fetchAll();

  // 3. Posts do usuário (curtidas de outros nos posts do usuário)
  $pdo->prepare("DELETE FROM post_curtidas WHERE post_id IN (SELECT id FROM posts WHERE usuario_id = ?)")
      ->execute([$userId]);
  $pdo->prepare("DELETE FROM posts WHERE usuario_id = ?")
      ->execute([$userId]);

  // 4. Notificações (como destinatário ou remetente)
  $pdo->prepare("DELETE FROM notificacoes WHERE usuario_id = ? OR remetente_id = ?")
      ->execute([$userId, $userId]);

  // 5. Treinos — comportamento diferente por perfil
  if ($usuario['perfil'] === 'treinador') {
    // Treinador: não deletar treinos dos alunos,
    // apenas desvincula o treinador desses registros
    $pdo->prepare("UPDATE treinos SET treinador_id = NULL WHERE treinador_id = ?")
        ->execute([$userId]);
  } else {
    // Aluno: apagar apenas os próprios treinos
    $pdo->prepare("DELETE FROM treinos WHERE aluno_id = ? AND (treinador_id IS NULL OR treinador_id != ?)")
        ->execute([$userId, $userId]);
    // Também apaga quaisquer treinos criados pelo próprio (aluno que se adicionou treino)
    $pdo->prepare("DELETE FROM treinos WHERE aluno_id = ?")
        ->execute([$userId]);
  }

  // 6. Eventos do usuário
  $pdo->prepare("DELETE FROM usuario_eventos WHERE usuario_id = ?")
      ->execute([$userId]);

  // 7. Se for treinador: limpar vínculo dos alunos
  if ($usuario['perfil'] === 'treinador') {
    $pdo->prepare("UPDATE usuarios SET treinador_id = NULL, status_vinculo = NULL WHERE treinador_id = ?")
        ->execute([$userId]);
    $pdo->prepare("DELETE FROM treinadores WHERE usuario_id = ?")
        ->execute([$userId]);
  }

  // 8. Limpar dados Strava locais (sem revogar token na API do Strava)
  $pdo->prepare("
    UPDATE usuarios SET
      strava_id              = NULL,
      strava_access_token    = NULL,
      strava_refresh_token   = NULL,
      strava_token_expira    = NULL,
      strava_km_total        = 0,
      strava_km_ano          = 0,
      strava_atividades_total = 0,
      strava_conectado       = false,
      strava_sincronizado_em = NULL
    WHERE id = ?
  ")->execute([$userId]);

  // 9. Seguidores (follows/unfollows)
  $pdo->prepare("DELETE FROM seguidores WHERE seguidor_id = ? OR seguido_id = ?")
      ->execute([$userId, $userId]);

  // 10. Strava states e sessões
  $pdo->prepare("DELETE FROM strava_states WHERE usuario_id = ?")
      ->execute([$userId]);
  $pdo->prepare("DELETE FROM sessoes WHERE usuario_id = ?")
      ->execute([$userId]);

  // 10. Remember token — limpar coluna antes do DELETE final
  $pdo->prepare("UPDATE usuarios SET remember_token = NULL, remember_expira = NULL WHERE id = ?")
      ->execute([$userId]);

  // 11. Apagar linha do usuário (último step)
  $pdo->prepare("DELETE FROM usuarios WHERE id = ?")
      ->execute([$userId]);

  // COMMIT — banco consistente
  $pdo->commit();

  // ----------------------------------------------------------
  // Agora (fora da transação): deletar arquivos do Storage
  // Falhas aqui não revertam o banco, apenas são logadas
  // ----------------------------------------------------------

  // Foto de perfil
  if (!empty($usuario['foto'])) {
    deletarArquivoSupabase($usuario['foto']);
  }

  // Fotos dos posts
  foreach ($fotosPosts as $row) {
    deletarArquivoSupabase($row['foto']);
  }

  // ----------------------------------------------------------
  // Destruir sessão e limpar cookie
  // ----------------------------------------------------------
  setcookie('remember_token', '', time() - 3600, '/', '', true, true);
  session_unset();
  session_destroy();

  // Redireciona pro login com mensagem
  header('Location: /pages/login.php?msg=conta_excluida');
  exit();

} catch (PDOException $e) {
  // ROLLBACK — nenhum dado foi alterado
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  error_log("ERRO action-excluir-conta.php (userId={$userId}): " . $e->getMessage());
  header('Location: /pages/configuracoes.php?secao=zona-de-risco&erro=falha_exclusao');
  exit();
}
