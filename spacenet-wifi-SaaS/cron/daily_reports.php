<?php
// cron/daily_reports.php - Generate Daily Reports
require_once '../includes/Database.php';
require_once '../includes/EmailService.php';
require_once '../includes/Logger.php';

$logger = new Logger();
$logger->info("Starting daily reports generation");

try {
    $db = Database::getInstance();
    $emailService = new EmailService();
    
    // Get all active tenants
    $tenants = $db->query(
        "SELECT * FROM tenants WHERE status IN ('active', 'trial')"
    )->fetchAll();
    
    foreach ($tenants as $tenant) {
        // Generate daily report for each tenant
        $reportData = generateDailyReport($tenant['id']);
        
        // Email report to tenant (if enabled)
        if ($reportData['email_enabled']) {
            $emailService->sendDailyReport($tenant, $reportData);
        }
        
        $logger->info("Generated daily report for tenant: " . $tenant['company_name']);
    }
    
    $logger->info("Daily reports generation completed successfully");
    
} catch (Exception $e) {
    $logger->error("Daily reports generation failed: " . $e->getMessage());
}

function generateDailyReport($tenantId) {
    $db = Database::getInstance();
    
    // Get yesterday's statistics
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    $stats = $db->query(
        "SELECT 
            COUNT(DISTINCT cs.customer_id) as unique_customers,
            COUNT(cs.id) as total_sessions,
            COALESCE(SUM(CASE WHEN t.status = 'completed' THEN t.amount ELSE 0 END), 0) as revenue,
            AVG(cs.duration_minutes) as avg_session_duration
         FROM customer_sessions cs
         LEFT JOIN transactions t ON cs.payment_reference = t.payment_reference
         WHERE cs.tenant_id = ? AND DATE(cs.created_at) = ?",
        [$tenantId, $yesterday]
    )->fetch();
    
    return [
        'date' => $yesterday,
        'unique_customers' => $stats['unique_customers'] ?? 0,
        'total_sessions' => $stats['total_sessions'] ?? 0,
        'revenue' => $stats['revenue'] ?? 0,
        'avg_session_duration' => $stats['avg_session_duration'] ?? 0,
        'email_enabled' => true // This would come from tenant settings
    ];
}