<?php
$tituloPagina = "Privacidade";
include '../components/head.php';
require_once '../config/conexao.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../components/header.php';
?>
<div style="max-width: 800px; margin: 60px auto; padding: 0 24px; font-family: 'Outfit', sans-serif; line-height: 1.6; color: #333;">
    <h1 style="font-family: 'Bebas Neue', sans-serif; font-size: 3rem; color: #111; letter-spacing: 1px; margin-bottom: 24px;">Política de Privacidade</h1>
    <p>Sua privacidade é muito importante para nós. Esta página descreve como coletamos e protegemos seus dados.</p>
    <h3 style="margin-top: 32px; color: #1a1a1a;">1. Coleta de Dados</h3>
    <p>Coletamos nome, e-mail e dados de sincronização do Strava caso você faça essa integração. Esses dados são estritamente utilizados para o funcionamento da plataforma.</p>
    <h3 style="margin-top: 32px; color: #1a1a1a;">2. Compartilhamento</h3>
    <p>Não vendemos nem compartilhamos seus dados com terceiros. Apenas seu treinador (se vinculado) poderá ver seus dados de treino.</p>
    <h3 style="margin-top: 32px; color: #1a1a1a;">3. Integração com o Strava</h3>
    <p>A sincronização com o Strava respeita os limites de rate e os termos de acesso exigidos pela API do Strava. Seus tokens são criptografados.</p>
</div>
<?php include_once dirname(__DIR__) . '/components/footer.php'; ?>
</body>
</html>
