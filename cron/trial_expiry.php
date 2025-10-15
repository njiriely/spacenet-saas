<?php 
// cron/trial_expiry.php - Check and Handle Trial Expirations
require_once '../includes/Database.php';
require_once '../includes/Tenant.php';

$tenant = new Tenant();
$expiredCount = $tenant->checkTrialExpiry();

echo "Processed {$expiredCount} expired trials\n";