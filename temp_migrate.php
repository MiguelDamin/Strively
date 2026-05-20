<?php
require_once __DIR__ . '/config/conexao.php';
try {
    $pdo->exec("ALTER TABLE notificacoes ADD COLUMN IF NOT EXISTS remetente_id INT REFERENCES usuarios(id) ON DELETE SET NULL;");
    echo "Migration Success\n";
} catch(Exception $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
