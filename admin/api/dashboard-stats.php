<?php
// admin/api/dashboard-stats.php
header('Content-Type: application/json');

require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/DatabaseConfig.php';

$auth = new SpaceNet\Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = SpaceNet\Database::getInstance();

try {
    // Get total tenants
    $totalTenants = $db->query("SELECT COUNT(*) as count FROM tenants")->fetch()['count'];
    
    // Get active tenants
    $activeTenants = $db->query("SELECT COUNT(*) as count FROM tenants WHERE status = 'active'")->fetch()['count'];
    
    // Get total customers
    $totalCustomers = $db->query("SELECT COUNT(*) as count FROM customers")->fetch()['count'];
    
    // Get total revenue (sum of all transactions)
    $totalRevenue = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE status = 'completed'")->fetch()['total'];
    
    // Get recent tenants
    $recentTenants = $db->query("SELECT company_name, status, created_at FROM tenants ORDER BY created_at DESC LIMIT 5")->fetchAll();
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_tenants' => $totalTenants,
            'active_tenants' => $activeTenants,
            'total_customers' => $totalCustomers,
            'total_revenue' => number_format($totalRevenue, 2)
        ],
        'recent_tenants' => $recentTenants
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading statistics: ' . $e->getMessage()
    ]);
}

