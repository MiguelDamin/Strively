<?php ob_start(); ?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Strively — <?php echo $tituloPagina ?? 'Início'; ?></title>
  <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>" />
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  
  <!-- PWA iOS e Manifest -->
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="Strively">
  <link rel="icon" href="/images/icon-192.png" />
  <link rel="apple-touch-icon" href="/images/icon-192.png">
  <link rel="manifest" href="/manifest.json">
  
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/service-worker.js')
        .then(() => console.log('Service Worker Registrado'))
        .catch(err => console.error('Erro no Service Worker', err));
    }
  </script>
</head>