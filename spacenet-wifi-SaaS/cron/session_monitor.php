<?php
// cron/session_monitor.php - Monitor and Alert on Session Issues
require_once '../includes/Database.php';
require_once '../includes/EmailService.php';
require_once '../includes/Logger.php';

$logger = new Logger();
$db = Database::getInstance();

try {
    // Check for stuck sessions (active but past end time)
    $stuckSessions = $db->query("
        SELECT cs.*, t.company_name, t.email 
        FROM customer_sessions cs
        JOIN tenants t ON cs.tenant_id = t.id
        WHERE cs.status = 'active' AND cs.end_time < DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ")->fetchAll();
    
    if (!empty($stuckSessions)) {
        $logger->warning("Found " . count($stuckSessions) . " stuck sessions");
        
        // Group by tenant and send alerts
        $tenantSessions = [];
        foreach ($stuckSessions as $session) {
            $tenantSessions[$session['tenant_id']][] = $session;
        }
        
        $emailService = new EmailService();
        foreach ($tenantSessions as $tenantId => $sessions) {
            $tenant = $sessions[0]; // Get tenant info from first session
            $logger->info("Alerting tenant: " . $tenant['company_name'] . " about " . count($sessions) . " stuck sessions");
            // Send email alert to tenant
        }
    }
    
    $logger->info("Session monitoring completed successfully");
    
} catch (Exception $e) {
    $logger->error("Session monitoring failed: " . $e->getMessage());
}
