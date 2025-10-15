<?php
// cron/billing.php - Process Subscription Billing
require_once '../includes/Database.php';
require_once '../includes/Billing.php';

$billing = new Billing();
$processedCount = $billing->processSubscriptionBilling();

echo "Processed {$processedCount} subscription billings\n";
