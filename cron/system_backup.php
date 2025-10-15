<?php
// cron/system_backup.php - Automated System Backup
require_once '../includes/Database.php';
require_once '../includes/Logger.php';

$logger = new Logger();
$logger->info("Starting system backup");

try {
    $backupDir = '../backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    
    // Database backup
    $dbBackupFile = $backupDir . '/database_' . $timestamp . '.sql';
    $dbConfig = new DatabaseConfig();
    
    $command = sprintf(
        'mysqldump -h %s -u %s -p%s %s > %s 2>&1',
        DatabaseConfig::HOST,
        DatabaseConfig::USERNAME,
        DatabaseConfig::PASSWORD,
        DatabaseConfig::DB_NAME,
        $dbBackupFile
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0) {
        $logger->info("Database backup created: " . $dbBackupFile);
    } else {
        throw new Exception("Database backup failed: " . implode("\n", $output));
    }
    
    // File system backup (excluding logs and backups)
    $filesBackupFile = $backupDir . '/files_' . $timestamp . '.tar.gz';
    $excludeDirs = '--exclude=logs --exclude=backups --exclude=uploads';
    
    $command = sprintf(
        'tar -czf %s %s ../ 2>&1',
        $filesBackupFile,
        $excludeDirs
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0) {
        $logger->info("Files backup created: " . $filesBackupFile);
    } else {
        $logger->warning("Files backup failed: " . implode("\n", $output));
    }
    
    // Clean up old backups (keep last 7 days)
    $oldBackups = glob($backupDir . '/*');
    foreach ($oldBackups as $backup) {
        if (filemtime($backup) < strtotime('-7 days')) {
            unlink($backup);
            $logger->info("Removed old backup: " . basename($backup));
        }
    }
    
    $logger->info("System backup completed successfully");
    
} catch (Exception $e) {
    $logger->error("System backup failed: " . $e->getMessage());
}

echo "Backup process completed. Check logs for details.\n";<?php
// customer/login.php - Customer Account System
session_start();
require_once '../includes/Database.php';

$error = '';
$success = '';

// Handle customer login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $db = Database::getInstance();
    $customer = $db->query(
        "SELECT c.*, t.subdomain FROM customers c 
         JOIN tenants t ON c.tenant_id = t.id 
         WHERE c.username = ? AND c.password_hash IS NOT NULL",
        [$username]
    )->fetch();
    
    if ($customer && password_verify($password, $customer['password_hash'])) {
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_logged_in'] = true;
        header('Location: profile.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}

// Handle account creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_account') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $error = 'Passwords do not match';
    } else {
        $db = Database::getInstance();
        
        // Check if customer exists
        $existing = $db->query("SELECT id FROM customers WHERE username = ?", [$username])->fetch();
        
        if ($existing) {
            // Update existing customer with account info
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $db->query(
                "UPDATE customers SET email = ?, phone = ?, password_hash = ? WHERE username = ?",
                [$email, $phone, $passwordHash, $username]
            );