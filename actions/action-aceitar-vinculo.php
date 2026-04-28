<?php
// ==========================================================
// STRIVELY — actions/action-aceitar-vinculo.php
// Processa aceitar/recusar vínculo via link do e-mail
// Funciona sem login — usa token de segurança
// ==========================================================

require_once __DIR__ . '/../config/conexao.php';

$corredorId = (int)($_GET['corredor_id'] ?? 0);
$token      = $_GET['token'] ?? '';
$acao       = $_GET['acao'] ?? '';

// Variáveis para a página de resultado
$titulo   = '';
$mensagem = '';
$icone    = '';
$sucesso  = false;

// Validação básica
if ($corredorId <= 0 || empty($token) || !in_array($acao, ['aceitar', 'recusar'])) {
  $titulo   = 'Link inválido';
  $mensagem = 'Este link é inválido. Verifique o e-mail original e tente novamente.';
  $icone    = '❌';
} else {
  // Busca o corredor e valida o token
  $stmt = $pdo->prepare("
    SELECT u.id, u.nome, u.treinador_id, u.token_vinculo, u.status_vinculo,
           t.nome AS nome_treinador
    FROM usuarios u
    LEFT JOIN usuarios t ON t.id = u.treinador_id
    WHERE u.id = ?
  ");
  $stmt->execute([$corredorId]);
  $corredor = $stmt->fetch();

  if (!$corredor || empty($corredor['token_vinculo']) || !hash_equals($corredor['token_vinculo'], $token)) {
    $titulo   = 'Link inválido ou expirado';
    $mensagem = 'Este link já foi usado ou é inválido. Se precisar, peça ao corredor que envie uma nova solicitação.';
    $icone    = '⏰';
  } else {

    $nomeCorredor  = htmlspecialchars($corredor['nome']);
    $nomeTreinador = htmlspecialchars($corredor['nome_treinador'] ?? 'Treinador');

    if ($acao === 'aceitar') {
      // Aceita o vínculo
      $stmt = $pdo->prepare("UPDATE usuarios SET status_vinculo = 'aceito', token_vinculo = NULL WHERE id = ?");
      $stmt->execute([$corredorId]);

      // Notifica o corredor
      $textoNotif = "🎉 Seu treinador {$nomeTreinador} aceitou seu pedido!";
      $linkNotif  = "/pages/treinos.php";
      $stmt = $pdo->prepare("INSERT INTO notificacoes (usuario_id, texto, link) VALUES (?, ?, ?)");
      $stmt->execute([$corredorId, $textoNotif, $linkNotif]);

      $titulo   = 'Vínculo aceito!';
      $mensagem = "Você aceitou {$nomeCorredor} como seu aluno. Agora você pode criar treinos personalizados para ele na plataforma.";
      $icone    = '✅';
      $sucesso  = true;

    } else {
      // Recusa o vínculo
      $stmt = $pdo->prepare("UPDATE usuarios SET treinador_id = NULL, status_vinculo = NULL, token_vinculo = NULL WHERE id = ?");
      $stmt->execute([$corredorId]);

      // Notifica o corredor
      $textoNotif = "O treinador {$nomeTreinador} recusou sua solicitação de vínculo.";
      $linkNotif  = "/pages/buscar-treinador.php";
      $stmt = $pdo->prepare("INSERT INTO notificacoes (usuario_id, texto, link) VALUES (?, ?, ?)");
      $stmt->execute([$corredorId, $textoNotif, $linkNotif]);

      $titulo   = 'Solicitação recusada';
      $mensagem = "Você recusou a solicitação de vínculo de {$nomeCorredor}. O corredor será notificado.";
      $icone    = '🚫';
      $sucesso  = false;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Strively — <?= htmlspecialchars($titulo) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Outfit', sans-serif;
      background: #f8f9fa;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }

    .result-card {
      background: #fff;
      border-radius: 20px;
      border: 1px solid #e5e7eb;
      padding: 48px 36px;
      max-width: 440px;
      width: 100%;
      text-align: center;
      box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    }

    .result-icon {
      font-size: 3.5rem;
      margin-bottom: 16px;
    }

    .result-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 2rem;
      letter-spacing: 2px;
      color: #1a1a2e;
      margin-bottom: 12px;
    }

    .result-msg {
      font-size: 0.95rem;
      color: #6b7280;
      line-height: 1.7;
      margin-bottom: 28px;
    }

    .result-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      background: <?= $sucesso ? '#1DB954' : '#6b7280' ?>;
      color: #fff;
      border-radius: 100px;
      padding: 12px 28px;
      font-weight: 600;
      font-size: 0.9rem;
      text-decoration: none;
      transition: opacity 0.15s;
    }

    .result-link:hover { opacity: 0.85; }

    .brand {
      margin-top: 28px;
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.1rem;
      letter-spacing: 2px;
      color: #d1d5db;
    }
  </style>
</head>
<body>
  <div class="result-card">
    <div class="result-icon"><?= $icone ?></div>
    <h1 class="result-title"><?= htmlspecialchars($titulo) ?></h1>
    <p class="result-msg"><?= $mensagem ?></p>
    <a href="/index.php" class="result-link">Ir para o Strively</a>
    <div class="brand">STRIVELY</div>
  </div>
</body>
</html>
