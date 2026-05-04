<?php
// Router para o servidor built-in do PHP (Render.com)
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve arquivos estáticos (css, js, imagens, etc.) diretamente
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// Tenta servir o arquivo .php correspondente à URL
$candidate = __DIR__ . $uri;

// Se a URL aponta direto pra um arquivo PHP
if (file_exists($candidate) && pathinfo($candidate, PATHINFO_EXTENSION) === 'php') {
    require $candidate;
    exit;
}

// Se a URL não tem extensão, tenta adicionar .php
if (file_exists($candidate . '.php')) {
    require $candidate . '.php';
    exit;
}

// Se é um diretório, tenta index.php dentro
if (is_dir($candidate) && file_exists($candidate . '/index.php')) {
    require $candidate . '/index.php';
    exit;
}

// Fallback: index.php da raiz (para SPA ou rotas customizadas)
require __DIR__ . '/index.php';
