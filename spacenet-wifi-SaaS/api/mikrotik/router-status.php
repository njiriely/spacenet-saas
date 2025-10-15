<?php
// api/mikrotik/router-status.php - Router Health Check
header('Content-Type: application/json');

require_once '../../includes/Database.php';
require_once '../../includes/MikrotikAPI.php';

try {
    $tenantId = $_GET['tenant_id'] ?? null;
    
    if (!$tenantId) {
        throw new Exception('Tenant ID is required');
    }
    
    $db = Database::getInstance();
    $tenant = $db->query("SELECT * FROM tenants WHERE id = ?", [$tenantId])->fetch();
    
    if (!$tenant || !$tenant['mikrotik_ip']) {
        throw new Exception('Router not configured');
    }
    
    $mikrotik = new MikrotikAPI();
    
    try {
        $mikrotik->connect(
            $tenant['mikrotik_ip'],
            $tenant['mikrotik_username'],
            $tenant['mikrotik_password']
        );
        
        // Get router info
        $routerInfo = $mikrotik->sendCommand(['/system/resource/print']);
        $hotspotUsers = $mikrotik->getHotspotUsers();
        
        echo json_encode([
            'success' => true,
            'status' => 'connected',
            'router_info' => $routerInfo,
            'hotspot_users_count' => count($hotspotUsers),
            'last_checked' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'status' => 'disconnected',
            'error' => $e->getMessage(),
            'last_checked' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}