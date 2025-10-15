// includes/Logger.php - Advanced Logging System
<?php
class Logger {
    private $logDir;
    private $logFile;
    private $maxFileSize;
    private $maxFiles;
    
    public function __construct($logDir = '../logs', $logFile = 'app.log') {
        $this->logDir = $logDir;
        $this->logFile = $logFile;
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        $this->maxFiles = 5;
        
        $this->ensureLogDirectory();
    }
    
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    public function debug($message, $context = []) {
        if (($_ENV['APP_DEBUG'] ?? false) === 'true') {
            $this->log('DEBUG', $message, $context);
        }
    }
    
    public function payment($message, $context = []) {
        $this->logToFile('payment.log', 'PAYMENT', $message, $context);
    }
    
    public function security($message, $context = []) {
        $this->logToFile('security.log', 'SECURITY', $message, $context);
    }
    
    public function api($message, $context = []) {
        $this->logToFile('api.log', 'API', $message, $context);
    }
    
    private function log($level, $message, $context = []) {
        $this->logToFile($this->logFile, $level, $message, $context);
    }
    
    private function logToFile($filename, $level, $message, $context = []) {
        $logPath = $this->logDir . '/' . $filename;
        
        // Rotate log if too large
        if (file_exists($logPath) && filesize($logPath) > $this->maxFileSize) {
            $this->rotateLog($logPath);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' | Context: ' . json_encode($context);
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
        
        file_put_contents($logPath, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function rotateLog($logPath) {
        $dir = dirname($logPath);
        $filename = basename($logPath, '.log');
        $extension = '.log';
        
        // Move existing rotated logs
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $oldFile = $dir . '/' . $filename . '.' . $i . $extension;
            $newFile = $dir . '/' . $filename . '.' . ($i + 1) . $extension;
            
            if (file_exists($oldFile)) {
                if ($i === $this->maxFiles - 1) {
                    unlink($oldFile); // Delete oldest log
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        // Move current log to .1
        rename($logPath, $dir . '/' . $filename . '.1' . $extension);
    }
    
    private function ensureLogDirectory() {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    public function getLogFiles() {
        $files = glob($this->logDir . '/*.log*');
        $logFiles = [];
        
        foreach ($files as $file) {
            $logFiles[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'modified' => filemtime($file),
                'path' => $file
            ];
        }
        
        return $logFiles;
    }
    
    public function cleanOldLogs($days = 30) {
        $files = glob($this->logDir . '/*.log*');
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $deletedFiles = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deletedFiles++;
            }
        }
        
        return $deletedFiles;
    }
}