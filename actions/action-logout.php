<?php
// ==========================================================
// STRIVELY — actions/action-logout.php
// Encerra a sessão do usuário e redireciona para home
// ==========================================================

$only_session = true;
require_once '../components/header.php';

// Destroi todos os dados da sessão
session_destroy();

// Redireciona para a página inicial
header('Location: /Strively/index.php');
exit();