<?php
// includes/User.php  
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function createCustomer($tenantId, $data) {
        $sql = "INSERT INTO customers (tenant_id, username, email, phone, password_hash, mac_address, device_info)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $passwordHash = isset($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
        $deviceInfo = json_encode($data['device_info'] ?? []);
        
        $params = [
            $tenantId,
            $data['username'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $passwordHash,
            $data['mac_address'] ?? null,
            $deviceInfo
        ];
        
        $stmt = $this->db->query($sql, $params);
        return $stmt ? $this->db->lastInsertId() : false;
    }
    
    public function getCustomer($id, $tenantId = null) {
        $sql = "SELECT * FROM customers WHERE id = ?";
        $params = [$id];
        
        if ($tenantId) {
            $sql .= " AND tenant_id = ?";
            $params[] = $tenantId;
        }
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function getTenantCustomers($tenantId, $limit = 50, $offset = 0) {
        $sql = "SELECT c.*, 
                       COUNT(cs.id) as total_sessions,
                       MAX(cs.start_time) as last_session
                FROM customers c
                LEFT JOIN customer_sessions cs ON c.id = cs.customer_id
                WHERE c.tenant_id = ?
                GROUP BY c.id
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->query($sql, [$tenantId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function updateCustomerStatus($id, $status, $tenantId = null) {
        $sql = "UPDATE customers SET status = ? WHERE id = ?";
        $params = [$status, $id];
        
        if ($tenantId) {
            $sql .= " AND tenant_id = ?";
            $params[] = $tenantId;
        }
        
        return $this->db->query($sql, $params);
    }
}