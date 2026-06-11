<?php

declare(strict_types=1);

namespace App\Service;

use PDO;
use PDOException;

class DatabaseService
{
    private PDO $pdo;
    private string $encryptionKey;
    private string $dbPath;

    public function __construct()
    {
        $this->dbPath = __DIR__ . '/../../data/secure.db';
        $this->encryptionKey = $this->getOrCreateKey();
        $this->initDatabase();
    }

    private function getOrCreateKey(): string
    {
        $keyFile = __DIR__ . '/../../data/.dbkey';
        
        if (file_exists($keyFile)) {
            return hex2bin(file_get_contents($keyFile));
        }

        $key = random_bytes(32);
        file_put_contents($keyFile, bin2hex($key));
        chmod($keyFile, 0600);
        
        return $key;
    }

    private function initDatabase(): void
    {
        $dir = dirname($this->dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $this->pdo = new PDO('sqlite:' . $this->dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS config (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category TEXT NOT NULL,
                key TEXT NOT NULL,
                value TEXT NOT NULL,
                updated_at INTEGER NOT NULL,
                UNIQUE(category, key)
            )
        ');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS ssh_connections (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                host TEXT NOT NULL,
                port INTEGER DEFAULT 22,
                username TEXT NOT NULL,
                password TEXT,
                key_path TEXT,
                log_path TEXT NOT NULL,
                created_at INTEGER NOT NULL,
                updated_at INTEGER NOT NULL
            )
        ');
    }

    public function encrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-GCM', $this->encryptionKey, 0, $iv, $tag);
        
        return base64_encode($iv . $tag . $encrypted);
    }

    public function decrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        $raw = base64_decode($data);
        $iv = substr($raw, 0, 16);
        $tag = substr($raw, 16, 16);
        $ciphertext = substr($raw, 32);
        
        return openssl_decrypt($ciphertext, 'AES-256-GCM', $this->encryptionKey, 0, $iv, $tag) ?: '';
    }

    public function setConfig(string $category, string $key, mixed $value): void
    {
        $json = json_encode($value);
        $encrypted = $this->encrypt($json);
        
        $stmt = $this->pdo->prepare('
            INSERT INTO config (category, key, value, updated_at)
            VALUES (:category, :key, :value, :updated_at)
            ON CONFLICT(category, key) DO UPDATE SET
                value = excluded.value,
                updated_at = excluded.updated_at
        ');
        
        $stmt->execute([
            ':category' => $category,
            ':key' => $key,
            ':value' => $encrypted,
            ':updated_at' => time(),
        ]);
    }

    public function getConfig(string $category, string $key, mixed $default = null): mixed
    {
        $stmt = $this->pdo->prepare('
            SELECT value FROM config WHERE category = :category AND key = :key
        ');
        $stmt->execute([':category' => $category, ':key' => $key]);
        
        $row = $stmt->fetch();
        if (!$row) {
            return $default;
        }
        
        $decrypted = $this->decrypt($row['value']);
        return json_decode($decrypted, true) ?? $default;
    }

    public function getAllConfig(string $category): array
    {
        $stmt = $this->pdo->prepare('
            SELECT key, value FROM config WHERE category = :category
        ');
        $stmt->execute([':category' => $category]);
        
        $result = [];
        while ($row = $stmt->fetch()) {
            $decrypted = $this->decrypt($row['value']);
            $result[$row['key']] = json_decode($decrypted, true);
        }
        
        return $result;
    }

    public function saveSshConnection(array $data): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO ssh_connections 
            (name, host, port, username, password, key_path, log_path, created_at, updated_at)
            VALUES (:name, :host, :port, :username, :password, :key_path, :log_path, :created_at, :updated_at)
            ON CONFLICT(name) DO UPDATE SET
                host = excluded.host,
                port = excluded.port,
                username = excluded.username,
                password = excluded.password,
                key_path = excluded.key_path,
                log_path = excluded.log_path,
                updated_at = excluded.updated_at
        ');

        $stmt->execute([
            ':name' => $data['name'],
            ':host' => $data['host'],
            ':port' => $data['port'] ?? 22,
            ':username' => $data['username'],
            ':password' => $this->encrypt($data['password'] ?? ''),
            ':key_path' => $data['key_path'] ?? '',
            ':log_path' => $data['log_path'],
            ':created_at' => time(),
            ':updated_at' => time(),
        ]);
    }

    public function getSshConnections(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM ssh_connections ORDER BY name');
        $connections = $stmt->fetchAll();

        foreach ($connections as &$conn) {
            $conn['password'] = $this->decrypt($conn['password']);
        }

        return $connections;
    }

    public function getSshConnectionById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ssh_connections WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $conn = $stmt->fetch();

        if (!$conn) {
            return null;
        }

        $conn['password'] = $this->decrypt($conn['password']);
        return $conn;
    }

    public function deleteSshConnection(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM ssh_connections WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function countSshConnections(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM ssh_connections');
        return (int) $stmt->fetchColumn();
    }

    public function canAddSshConnection(): bool
    {
        $limit = $this->getConfig('system', 'ssh_connections_limit', 5);
        return $this->countSshConnections() < $limit;
    }
}
