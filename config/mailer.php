<?php
function enviarEmail(string $para, string $assunto, string $corpo): bool {
    $apiKey = $_ENV['RESEND_API_KEY'];
    $payload = json_encode([
        'from'    => 'Strively <noreply@strively.run>',
        'to'      => [$para],
        'subject' => $assunto,
        'html'    => $corpo,
    ]);
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Authorization: Bearer {$apiKey}\r\nContent-Type: application/json\r\n",
            'content' => $payload,
            'ignore_errors' => true,
            'timeout' => 15,
        ],
    ]);
    $resp = @file_get_contents('https://api.resend.com/emails', false, $ctx);
    if ($resp === false) {
        return false;
    }
    $data = json_decode($resp, true);
    return isset($data['id']);
}
