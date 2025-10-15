<?php
// includes/Auth.php
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login($email, $password, $userType = 'tenant') {
        if ($userType === 'tenant') {
            $sql = "SELECT tu.*, t.company_name, t.subdomain, t.status as tenant_status 
                    FROM tenant_users tu 
                    JOIN tenants t ON tu.tenant_id = t.id 
                    WHERE tu.email = ? AND tu.is_active = 1";
        } else {
            // Super admin login
            $sql = "SELECT * FROM admin_users WHERE email = ? AND is_active = 1";
        }
        
        $stmt = $this->db->query($sql, [$email]);
        if ($user = $stmt->fetch()) {
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $userType;
                $_SESSION['tenant_id'] = $user['tenant_id'] ?? null;
                $_SESSION['logged_in'] = true;
                
                // Update last login
                $this->updateLastLogin($user['id'], $userType);
                
                return true;
            }
        }
        return false;
    }
    
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $userType = $_SESSION['user_type'];
        $userId = $_SESSION['user_id'];
        
        if ($userType === 'tenant') {
            $sql = "SELECT tu.*, t.company_name, t.subdomain, t.status as tenant_status, t.subscription_plan 
                    FROM tenant_users tu 
                    JOIN tenants t ON tu.tenant_id = t.id 
                    WHERE tu.id = ?";
        } else {
            $sql = "SELECT * FROM admin_users WHERE id = ?";
        }
        
        $stmt = $this->db->query($sql, [$userId]);
        return $stmt->fetch();
    }
    
    private function updateLastLogin($userId, $userType) {
        $table = $userType === 'tenant' ? 'tenant_users' : 'admin_users';
        $this->db->query("UPDATE {$table} SET last_login = NOW() WHERE id = ?", [$userId]);
    }
}