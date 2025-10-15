<?php
// includes/Billing.php
class Billing {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function processSubscriptionBilling() {
        $sql = "SELECT s.*, t.company_name, t.email 
                FROM subscriptions s
                JOIN tenants t ON s.tenant_id = t.id
                WHERE s.status = 'active' AND s.next_billing_date <= NOW()";
        
        $stmt = $this->db->query($sql);
        $dueSubscriptions = $stmt->fetchAll();
        
        foreach ($dueSubscriptions as $subscription) {
            $this->chargeTenant($subscription);
        }
        
        return count($dueSubscriptions);
    }
    
    public function createTransaction($tenantId, $customerId, $type, $amount, $paymentMethod, $metadata = []) {
        $reference = $this->generateTransactionReference();
        
        $sql = "INSERT INTO transactions (tenant_id, customer_id, transaction_type, 
                                        amount, payment_method, payment_reference, 
                                        metadata, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        $params = [
            $tenantId,
            $customerId,
            $type,
            $amount,
            $paymentMethod,
            $reference,
            json_encode($metadata)
        ];
        
        $stmt = $this->db->query($sql, $params);
        return $stmt ? $this->db->lastInsertId() : false;
    }
    
    public function updateTransaction($id, $status, $externalReference = null) {
        $sql = "UPDATE transactions SET status = ?, updated_at = NOW()";
        $params = [$status];
        
        if ($externalReference) {
            $sql .= ", external_reference = ?";
            $params[] = $externalReference;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        return $this->db->query($sql, $params);
    }
    
    public function getTransactionByReference($reference) {
        $sql = "SELECT * FROM transactions WHERE payment_reference = ?";
        $stmt = $this->db->query($sql, [$reference]);
        return $stmt->fetch();
    }
    
    private function chargeTenant($subscription) {
        // Implementation would charge tenant's payment method
        // Update subscription dates
        // Handle failed payments
    }
    
    private function generateTransactionReference() {
        return 'TXN_' . time() . '_' . rand(1000, 9999);
    }
}