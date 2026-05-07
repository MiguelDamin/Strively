<?php
class PdoSessionHandler implements SessionHandlerInterface {
    private $pdo;

    public function __construct() {
        global $pdo;
        if (!isset($pdo)) {
            require_once __DIR__ . '/conexao.php';
        }
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
        // Extrair usuario_id dos dados da sessão
        $sessionData = [];
        $decoded = session_decode($data);
        if ($decoded) {
            $sessionData = $_SESSION;
        }
        $usuario_id = $sessionData['id'] ?? null;
        
        // Só salvar no banco se tiver usuario logado
        if ($usuario_id === null) {
            return true; // sessão de visitante, ignora
        }
        
        $expira = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60);
        $stmt = $this->pdo->prepare("
            INSERT INTO sessoes (id, usuario_id, dados, expira_em) 
            VALUES (:id, :usuario_id, :dados, :expira)
            ON CONFLICT (id) DO UPDATE SET 
            usuario_id = EXCLUDED.usuario_id,
            dados = EXCLUDED.dados,
            expira_em = EXCLUDED.expira_em
        ");
        return $stmt->execute([
            ':id' => $id,
            ':usuario_id' => $usuario_id,
            ':dados' => $data,
            ':expira' => $expira
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
