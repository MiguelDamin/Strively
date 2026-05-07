<?php
class PdoSessionHandler implements SessionHandlerInterface {
    private $pdo;

    public function __construct() {
        require __DIR__ . '/conexao.php';
        $this->pdo = $pdo;
    }

    public function open(string $path, string $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {
        $stmt = $this->pdo->prepare("SELECT dados FROM sessoes WHERE id = ? AND expira_em > NOW()");
        $stmt->execute([$id]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['dados'];
        }
        return '';
    }

    public function write(string $id, string $data): bool {
        $usuario_id = $_SESSION['id'] ?? null;
        $stmt = $this->pdo->prepare("
            INSERT INTO sessoes (id, usuario_id, dados, criado_em, expira_em) 
            VALUES (:id, :usuario_id, :dados, NOW(), NOW() + INTERVAL '30 days')
            ON CONFLICT (id) DO UPDATE SET 
            usuario_id = EXCLUDED.usuario_id,
            dados = EXCLUDED.dados,
            expira_em = NOW() + INTERVAL '30 days'
        ");
        return $stmt->execute([
            ':id' => $id,
            ':usuario_id' => $usuario_id,
            ':dados' => $data
        ]);
    }

    public function destroy(string $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM sessoes WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false {
        $stmt = $this->pdo->prepare("DELETE FROM sessoes WHERE expira_em < NOW()");
        if ($stmt->execute()) {
            return $stmt->rowCount();
        }
        return false;
    }
}

$handler = new PdoSessionHandler();
session_set_save_handler($handler, true);
