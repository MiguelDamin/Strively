<?php
$tituloPagina = "Termos de Uso";
include '../components/head.php';
require_once '../config/conexao.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../components/header.php';
?>
<div style="max-width: 800px; margin: 60px auto; padding: 0 24px; font-family: 'Outfit', sans-serif; line-height: 1.6; color: #333;">
    <h1 style="font-family: 'Bebas Neue', sans-serif; font-size: 3rem; color: #111; letter-spacing: 1px; margin-bottom: 24px;">Termos de Uso</h1>
    <p>Bem-vindo ao Strively. Ao utilizar nossa plataforma, você concorda inteiramente com estes termos.</p>
    <h3 style="margin-top: 32px; color: #1a1a1a;">1. Uso da Plataforma</h3>
    <p>Você é integralmente responsável pela veracidade dos dados informados em seu cadastro e em garantir que o e-mail cadastrado seja real. O uso da plataforma para fins ilícitos é vetado.</p>
    <h3 style="margin-top: 32px; color: #1a1a1a;">2. Assinaturas e Treinadores</h3>
    <p>Quaisquer transações, pagamentos ou vínculos com treinadores são responsabilidade exclusiva das partes envolvidas. A plataforma apenas viabiliza sistemas para a gestão de planilhas.</p>
    <h3 style="margin-top: 32px; color: #1a1a1a;">3. Modificações</h3>
    <p>Podemos alterar estes termos a qualquer momento. Os usuários serão notificados pela própria interface da plataforma caso mudanças significativas ocorram.</p>
</div>
<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>
