<?php
// includes/DatabaseConfig.php - Database Configuration Class

// Check if config file is loaded
if (!defined('CONFIG_CREATED')) {
    // Attempt to load the main config file
    if (file_exists(dirname(__DIR__) . '/config/config.php')) {
        require_once dirname(__DIR__) . '/config/config.php';
    } else {
        // Fallback or error handling if config is missing
        die("Configuration file is missing. Please run the installer.");
    }
}

class DatabaseConfig {
    const HOST = DB_HOST;
    const PORT = DB_PORT;
    const DB_NAME = DB_NAME;
    const USERNAME = DB_USER;
    const PASSWORD = DB_PASS;
    const CHARSET = 'utf8mb4';
}
?>
