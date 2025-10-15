<?php
// cron/cleanup.php - System Cleanup Tasks
require_once '../includes/Database.php';
require_once '../includes/Session.php';

$session = new Session();
$expiredSessionsCount = $session->checkExpiredSessions();

echo "Cleaned up {$expiredSessionsCount} expired sessions\n";