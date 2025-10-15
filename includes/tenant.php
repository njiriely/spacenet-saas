<?php
// includes/Tenant.php
class Tenant {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        // Generate unique subdomain
        $subdomain = $this->generateSubdomain($data['company_name']);
        
        // Calculate trial end date
        $trialEndDate = date('Y-m-d H:i:s', strtotime('+' . AppConfig::TRIAL_DAYS . ' days'));
        
        try {
            $this->db->getConnection()->beginTransaction();
            
            // Create tenant
            $sql = "INSERT INTO tenants (company_name, subdomain, contact_person, email, phone, 
                                       subscription_plan, trial_end_date, mikrotik_ip, 
                                       mikrotik_username, mikrotik_password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['company_name'],
                $subdomain,
                $data['contact_person'],
                $data['email'],
                $data['phone'],
                $data['subscription_plan'],
                $trialEndDate,
                $data['mikrotik_ip'] ?? null,
                $data['mikrotik_username'] ?? null,
                $data['mikrotik_password'] ? password_hash($data['mikrotik_password'], PASSWORD_DEFAULT) : null
            ];
            
            $stmt = $this->db->query($sql, $params);
            $tenantId = $this->db->lastInsertId();
            
            // Create default admin user for tenant
            $this->createDefaultAdmin($tenantId, $data);
            
            // Copy default packages to tenant
            $this->createDefaultPackages($tenantId);
            
            $this->db->getConnection()->commit();
            
            // Send welcome email
            $this->sendWelcomeEmail($tenantId);
            
            return $tenantId;
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollback();
            throw new Exception("Failed to create tenant: " . $e->getMessage());
        }
    }
    
    public function getTenant($id) {
        $sql = "SELECT * FROM tenants WHERE id = ?";
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->fetch();
    }
    
    public function getAllTenants($limit = null, $offset = 0) {
        $sql = "SELECT t.*, 
                       COUNT(DISTINCT c.id) as total_customers,
                       SUM(CASE WHEN tr.status = 'completed' AND tr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN tr.amount ELSE 0 END) as monthly_revenue
                FROM tenants t
                LEFT JOIN customers c ON t.id = c.tenant_id
                LEFT JOIN transactions tr ON t.id = tr.tenant_id
                GROUP BY t.id
                ORDER BY t.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $stmt = $this->db->query($sql, [$limit, $offset]);
        } else {
            $stmt = $this->db->query($sql);
        }
        
        return $stmt->fetchAll();
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE tenants SET status = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->query($sql, [$status, $id]);
    }
    
    public function checkTrialExpiry() {
        $sql = "SELECT id, company_name, email, trial_end_date 
                FROM tenants 
                WHERE status = 'trial' AND trial_end_date <= NOW()";
        
        $stmt = $this->db->query($sql);
        $expiredTenants = $stmt->fetchAll();
        
        foreach ($expiredTenants as $tenant) {
            $this->updateStatus($tenant['id'], 'expired');
            $this->sendTrialExpiredEmail($tenant);
        }
        
        return count($expiredTenants);
    }
    
    private function generateSubdomain($companyName) {
        $subdomain = strtolower(trim($companyName));
        $subdomain = preg_replace('/[^a-z0-9]/', '', $subdomain);
        $subdomain = substr($subdomain, 0, 20);
        
        // Check if subdomain exists
        $counter = 1;
        $originalSubdomain = $subdomain;
        
        while ($this->subdomainExists($subdomain)) {
            $subdomain = $originalSubdomain . $counter;
            $counter++;
        }
        
        return $subdomain;
    }
    
    private function subdomainExists($subdomain) {
        $sql = "SELECT COUNT(*) FROM tenants WHERE subdomain = ?";
        $stmt = $this->db->query($sql, [$subdomain]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function createDefaultAdmin($tenantId, $data) {
        $sql = "INSERT INTO tenant_users (tenant_id, username, email, password_hash, role) 
                VALUES (?, ?, ?, ?, 'admin')";
        
        $username = strtolower(str_replace(' ', '', $data['contact_person']));
        $password = $this->generateRandomPassword();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $params = [$tenantId, $username, $data['email'], $passwordHash];
        $this->db->query($sql, $params);
        
        // Store password for welcome email
        $data['temp_password'] = $password;
        return $password;
    }
    
    private function createDefaultPackages($tenantId) {
        $sql = "INSERT INTO tenant_packages (tenant_id, template_id, name, duration_type, 
                                           duration_value, price, speed_limit, device_limit, device_multiplier)
                SELECT ? as tenant_id, id as template_id, name, duration_type, 
                       duration_value, base_price, speed_limit, device_limit,
                       CASE 
                           WHEN name LIKE '%Weekly%' OR name LIKE '%Daily%' 
                           THEN JSON_OBJECT('2', 1.5, '3', 2.0, '4', 2.5)
                           ELSE JSON_OBJECT('1', 1.0)
                       END as device_multiplier
                FROM package_templates WHERE is_active = 1";
        
        $this->db->query($sql, [$tenantId]);
    }
    
    private function generateRandomPassword($length = 8) {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
    }
    
    private function sendWelcomeEmail($tenantId) {
        // Implementation would send welcome email with login credentials
        // Using PHPMailer or similar email library
        // This is a placeholder for the email functionality
    }
    
    private function sendTrialExpiredEmail($tenant) {
        // Implementation would send trial expiry notification
        // This is a placeholder for the email functionality
    }
}