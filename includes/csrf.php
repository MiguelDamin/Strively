<?php
// ==========================================================
// STRIVELY — includes/csrf.php
// Gerenciamento e validação de tokens CSRF (Cross-Site Request Forgery)
// ==========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Obtém ou gera o token CSRF da sessão atual do usuário.
 */
function getCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Retorna o campo hidden HTML pronto para formulários POST.
 */
function csrfField(): string {
    $token = htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Valida o token CSRF fornecido via POST ou cabeçalho HTTP X-CSRF-TOKEN.
 */
function validateCsrfToken(?string $token = null): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionToken)) {
        return false;
    }

    if ($token === null) {
        $token = $_POST['csrf_token'] 
              ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
              ?? '';
    }

    return hash_equals($sessionToken, (string)$token);
}

/**
 * Exige um token CSRF válido. Se for inválido, interrompe o script com 403 ou redireciona.
 */
function requireCsrfToken(?string $redirectUrl = null): void {
    if (!validateCsrfToken()) {
        http_response_code(403);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'error',
                'sucesso' => false,
                'erro'    => 'Token CSRF inválido ou ausente. Por favor, recarregue a página.'
            ]);
            exit();
        }

        if ($redirectUrl) {
            $separator = (strpos($redirectUrl, '?') !== false) ? '&' : '?';
            header("Location: {$redirectUrl}{$separator}erro=csrf_invalido");
            exit();
        }

        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Erro de Segurança (CSRF)</title></head>'
           . '<body style="font-family:sans-serif;padding:40px;text-align:center;background:#0d0e12;color:#f0f0f0;">'
           . '<div style="max-width:500px;margin:auto;padding:30px;background:#181a20;border:1px solid #e53935;border-radius:12px;">'
           . '<h2 style="color:#e53935;margin-top:0;">Requisição Bloqueada por Segurança</h2>'
           . '<p style="color:#aaa;">Token CSRF inválido ou ausente. Esta ação foi interrompida para proteger sua conta contra requisições não autorizadas.</p>'
           . '<p><a href="javascript:history.back()" style="color:#00e599;text-decoration:none;font-weight:bold;">← Voltar e recarregar a página</a></p>'
           . '</div></body></html>';
        exit();
    }
}
