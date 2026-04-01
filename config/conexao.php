<?php
// ==========================================================
// STRIVELY — config/conexao.php
// Conexão com o banco de dados Supabase (PostgreSQL)
// Usa DATABASE_URL para simplificar as variáveis de ambiente
// ==========================================================

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// ----------------------------------------------------------
// Lê a DATABASE_URL e extrai as partes para o PDO
// Formato: postgresql://user:password@host:port/dbname
// ----------------------------------------------------------
$databaseUrl = $_ENV['DATABASE_URL'];
$parsed      = parse_url($databaseUrl);

$host   = $parsed['host'];
$port   = $parsed['port']   ?? 5432;
$dbname = ltrim($parsed['path'], '/');
$user   = $parsed['user'];
$pass   = $parsed['pass'];

// ----------------------------------------------------------
// CONEXÃO VIA PDO
// ----------------------------------------------------------
try {

  $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ]);

} catch (PDOException $e) {
  die("Erro na conexão: " . $e->getMessage());
}