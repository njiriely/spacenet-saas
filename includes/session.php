<?php
// includes/Session.php
class Session {
    private $db;
    private $mikrotik;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->mikrotik = new MikrotikAPI();
    }
    
    public function createSession($tenantId, $customerId, $packageId, $paymentReference) {
        $package = new Package();
        $packageData = $package->getPackage($packageId, $tenantId);
        
        if (!$packageData) {
            throw new Exception("Package not found");
        }
        
        // Calculate session duration
        $durationMinutes = $this->calculateDurationMinutes($packageData);
        $endTime = date('Y-m-d H:i:s', strtotime("+{$durationMinutes} minutes"));
        
        $sql = "INSERT INTO customer_sessions (tenant_id, customer_id, package_id, 
                                             username, duration_minutes, end_time, 
                                             payment_reference, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
        
        $customer = new User();
        $customerData = $customer->getCustomer($customerId, $tenantId);
        $username = $customerData['username'];
        
        $params = [
            $tenantId,
            $customerId, 
            $packageId,
            $username,
            $durationMinutes,
            $endTime,
            $paymentReference
        ];
        
        $stmt = $this->db->query($sql, $params);
        if ($stmt) {
            $sessionId = $this->db->lastInsertId();
            
            // Create user in MikroTik
            $this->createMikrotikUser($tenantId, $username, $packageData, $durationMinutes);
            
            return $sessionId;
        }
        
        return false;
    }
    
    public function getActiveSessions($tenantId) {
        $sql = "SELECT cs.*, c.username, c.email, tp.name as package_name, tp.speed_limit
                FROM customer_sessions cs
                JOIN customers c ON cs.customer_id = c.id
                JOIN tenant_packages tp ON cs.package_id = tp.id
                WHERE cs.tenant_id = ? AND cs.status = 'active' AND cs.end_time > NOW()
                ORDER BY cs.start_time DESC";
        
        $stmt = $this->db->query($sql, [$tenantId]);
        return $stmt->fetchAll();
    }
    
    public function expireSession($sessionId) {
        $sql = "UPDATE customer_sessions SET status = 'expired' WHERE id = ?";
        return $this->db->query($sql, [$sessionId]);
    }
    
    public function checkExpiredSessions() {
        $sql = "SELECT cs.*, t.mikrotik_ip, t.mikrotik_username, t.mikrotik_password
                FROM customer_sessions cs
                JOIN tenants t ON cs.tenant_id = t.id
                WHERE cs.status = 'active' AND cs.end_time <= NOW()";
        
        $stmt = $this->db->query($sql);
        $expiredSessions = $stmt->fetchAll();
        
        foreach ($expiredSessions as $session) {
            // Disconnect user from MikroTik
            $this->disconnectMikrotikUser($session);
            
            // Update session status
            $this->expireSession($session['id']);
        }
        
        return count($expiredSessions);
    }
    
    private function calculateDurationMinutes($package) {
        switch ($package['duration_type']) {
            case 'hours':
                return $package['duration_value'] * 60;
            case 'days':
                return $package['duration_value'] * 24 * 60;
            case 'months':
                return $package['duration_value'] * 30 * 24 * 60;
            default:
                return 60; // Default 1 hour
        }
    }
    
    private function createMikrotikUser($tenantId, $username, $package, $durationMinutes) {
        $tenant = new Tenant();
        $tenantData = $tenant->getTenant($tenantId);
        
        if ($tenantData && $tenantData['mikrotik_ip']) {
            $this->mikrotik->connect(
                $tenantData['mikrotik_ip'],
                $tenantData['mikrotik_username'],
                $tenantData['mikrotik_password']
            );
            
            $this->mikrotik->createHotspotUser($username, $package, $durationMinutes);
        }
    }
    
    private function disconnectMikrotikUser($session) {
        // Implementation would disconnect user from MikroTik hotspot
        // This requires MikroTik API integration
    }
}