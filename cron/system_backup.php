<?php
// cron/system_backup.php - Automated System Backup
require_once '../includes/database.php';
require_once '../includes/logger.php';
require_once '../includes/DatabaseConfig.php';

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
        escapeshellarg(DatabaseConfig::HOST),
        escapeshellarg(DatabaseConfig::USERNAME),
        escapeshellarg(DatabaseConfig::PASSWORD),
        escapeshellarg(DatabaseConfig::DB_NAME),
        escapeshellarg($dbBackupFile)
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
        escapeshellarg($filesBackupFile),
        escapeshellarg($excludeDirs)
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

echo "Backup process completed. Check logs for details.\n";