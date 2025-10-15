<?php
// api/active-sessions.php - Active Sessions API
header('Content-Type: application/json');

require_once '../includes/Database.php';
require_once '../includes/Auth.php';

try {
    // This would typically require API authentication
    $tenantId = $_GET['tenant_id'] ?? null;
    
    if (!$tenantId) {
        throw new Exception('Tenant ID is required');
    }
    
    $db = Database::getInstance();
    
    // Get active sessions count
    $activeCount = $db->query(
        "SELECT COUNT(*) as count FROM customer_sessions 
         WHERE tenant_id = ? AND status = 'active' AND end_time > NOW()",
        [$tenantId]
    )->fetch()['count'];
    
    // Get detailed session info (limited for performance)
    $sessions = $db->query(
        "SELECT cs.username, cs.start_time, cs.end_time, tp.name as package_name, tp.speed_limit
         FROM customer_sessions cs
         JOIN tenant_packages tp ON cs.package_id = tp.id
         WHERE cs.tenant_id = ? AND cs.status = 'active' AND cs.end_time > NOW()
         ORDER BY cs.start_time DESC
         LIMIT 10",
        [$tenantId]
    )->fetchAll();
    
    echo json_encode([
        'success' => true,
        'count' => (int)$activeCount,
        'sessions' => $sessions,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}