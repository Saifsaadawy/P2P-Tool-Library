<?php
// ── Guard: prevent re-execution ──
if (isset($GLOBALS['pdo'])) return;

$host = $_ENV['DB_HOST']     ?? 'localhost';
$db   = $_ENV['DB_NAME']     ?? 'tool_library';
$user = $_ENV['DB_USER']     ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';

try {
    $GLOBALS['pdo'] = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}