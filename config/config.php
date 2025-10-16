<?php
// config/config.php - Database and Application Configuration

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'spacenet_saas');
define('DB_USER', 'spacenet_user');
define('DB_PASS', 'spacenet_pass');

// Application Settings (Default values, will be updated by installer)
define('APP_URL', 'http://localhost:8080');
define('COMPANY_NAME', 'SPACE NET SaaS');
define('SUPPORT_EMAIL', 'support@spacenet.co.ke');
define('TIMEZONE', 'Africa/Nairobi');
define('CURRENCY', 'KES');

// Security Settings
define('SESSION_TIMEOUT', 7200); // 2 hours
define('MAX_CONCURRENT_SESSIONS', 3);

// M-Pesa Settings
define('MPESA_CONSUMER_KEY', '');
define('MPESA_CONSUMER_SECRET', '');
define('MPESA_SHORTCODE', '174379');
define('MPESA_PASSKEY', '');
define('MPESA_ENVIRONMENT', 'sandbox'); // sandbox or production

// Pesapal Settings
define('PESAPAL_CONSUMER_KEY', '');
define('PESAPAL_CONSUMER_SECRET', '');

// Other Settings
define('TRIAL_DURATION_DAYS', 15);
define('ENABLE_REGISTRATION', true);
define('MAINTENANCE_MODE', false);

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('DATABASE_PATH', ROOT_PATH . '/database');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Autoloading (already handled by Composer, but for direct access)
require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/user.php';
require_once INCLUDES_PATH . '/tenant.php';
require_once INCLUDES_PATH . '/package.php';
define('CONFIG_CREATED', true);
?>
