<?php
// includes/Package.php
class Package {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getTenantPackages($tenantId) {
        $sql = "SELECT tp.*, pt.description 
                FROM tenant_packages tp
                LEFT JOIN package_templates pt ON tp.template_id = pt.id
                WHERE tp.tenant_id = ? AND tp.is_active = 1
                ORDER BY tp.price ASC";
        
        $stmt = $this->db->query($sql, [$tenantId]);
        return $stmt->fetchAll();
    }
    
    public function getPackage($id, $tenantId = null) {
        $sql = "SELECT * FROM tenant_packages WHERE id = ?";
        $params = [$id];
        
        if ($tenantId) {
            $sql .= " AND tenant_id = ?";
            $params[] = $tenantId;
        }
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function updatePackage($id, $data, $tenantId) {
        $sql = "UPDATE tenant_packages SET 
                name = ?, price = ?, duration_type = ?, duration_value = ?, 
                speed_limit = ?, device_limit = ?, updated_at = NOW()
                WHERE id = ? AND tenant_id = ?";
        
        $params = [
            $data['name'],
            $data['price'],
            $data['duration_type'],
            $data['duration_value'],
            $data['speed_limit'],
            $data['device_limit'],
            $id,
            $tenantId
        ];
        
        return $this->db->query($sql, $params);
    }
    
    public function calculatePrice($packageId, $deviceCount = 1) {
        $package = $this->getPackage($packageId);
        if (!$package) {
            return false;
        }
        
        $basePrice = $package['price'];
        $multipliers = json_decode($package['device_multiplier'], true);
        
        if (isset($multipliers[$deviceCount])) {
            return $basePrice * $multipliers[$deviceCount];
        }
        
        return $basePrice;
    }
}