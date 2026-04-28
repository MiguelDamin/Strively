<?php
$only_session = true;
require_once '../components/header.php';
require_once '../config/conexao.php';

if (!isset($_SESSION['id'])) {
  header('Location: /pages/login.php');
  exit();
}

$clientId    = $_ENV['STRAVA_CLIENT_ID'];
$redirectUri = urlencode($_ENV['STRAVA_REDIRECT_URI']);
$scope       = 'read,activity:read_all,profile:read_all';
$state       = bin2hex(random_bytes(16)); // proteção CSRF

$_SESSION['strava_state'] = $state;

$isMobile = preg_match('/Mobile|Android|BlackBerry|iPhone|iPod|iPad|Windows Phone/i', $_SERVER['HTTP_USER_AGENT'] ?? '');

$webUrl = "https://www.strava.com/oauth/authorize"
     . "?client_id={$clientId}"
     . "&redirect_uri={$redirectUri}"
     . "&response_type=code"
     . "&approval_prompt=auto"
     . "&scope={$scope}"
     . "&state={$state}";

$mobileAppUrl = "strava://oauth/mobile/authorize"
     . "?client_id={$clientId}"
     . "&redirect_uri={$redirectUri}"
     . "&response_type=code"
     . "&approval_prompt=auto"
     . "&scope={$scope}"
     . "&state={$state}";

$mobileWebUrl = "https://www.strava.com/oauth/mobile/authorize"
     . "?client_id={$clientId}"
     . "&redirect_uri={$redirectUri}"
     . "&response_type=code"
     . "&approval_prompt=auto"
     . "&scope={$scope}"
     . "&state={$state}";

if ($isMobile) {
    echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>Conectando ao Strava...</title>
    <style>
        body { background: #fff; font-family: 'Bebas Neue', Arial, sans-serif; text-align: center; padding-top: 50px; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #FC4C02; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        p { color: #555; margin-top: 20px; font-family: Arial, sans-serif; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class='loader'></div>
    <p>Abrindo o aplicativo do Strava...</p>
    <p style='font-size: 0.8rem; color: #999;' id='fallback-msg'></p>
    <script>
        // Tenta abrir o app pelo deep link e após timeout vai para a web se falhar
        setTimeout(function() {
            document.getElementById('fallback-msg').innerText = 'Caso não tenha o app, redirecionando para o navegador...';
            window.location.href = '{$mobileWebUrl}';
        }, 2000);
        
        window.location.href = '{$mobileAppUrl}';
    </script>
</body>
</html>";
    exit();
} else {
    header("Location: {$webUrl}");
    exit();
}
