<?php
// install/test-db-connection.php - Database Connection Tester
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit(json_encode(['success' => false, 'error' => 'Invalid request method']));
}

$host = $_POST['db_host'] ?? 'localhost';
$port = $_POST['db_port'] ?? 3306;
$dbname = $_POST['db_name'] ?? '';
$username = $_POST['db_username'] ?? '';
$password = $_POST['db_password'] ?? '';

try {
    $dsn = "mysql:host={$host};port={$port}";
    if ($dbname) {
        $dsn .= ";dbname={$dbname}";
    }
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    // Test query to get MySQL version
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    
    // Try to create database if it doesn't exist
    if ($dbname) {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    
    echo json_encode([
        'success' => true,
        'version' => $version
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}