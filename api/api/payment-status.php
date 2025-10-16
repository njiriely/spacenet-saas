<?php
// api/payment-status.php - Payment Status Checker
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/billing.php';

try {
    $transactionCode = $_GET['code'] ?? null;
    
    if (!$transactionCode) {
        throw new Exception('Transaction code is required');
    }
    
    $billing = new Billing();
    $transaction = $billing->getTransactionByReference($transactionCode);
    
    if (!$transaction) {
        echo json_encode(['success' => false, 'error' => 'Transaction not found']);
        exit;
    }
    
    // Return transaction status
    $response = [
        'success' => true,
        'status' => $transaction['status'],
        'amount' => $transaction['amount'],
        'payment_method' => $transaction['payment_method'],
        'created_at' => $transaction['created_at']
    ];
    
    // If completed, include session details
    if ($transaction['status'] === 'completed') {
        $db = Database::getInstance();
        $sessionData = $db->query(
            "SELECT cs.*, tp.name as package_name, tp.speed_limit, tp.duration_value, tp.duration_type, c.username
             FROM customer_sessions cs
             JOIN tenant_packages tp ON cs.package_id = tp.id
             JOIN customers c ON cs.customer_id = c.id
             WHERE cs.payment_reference = ?",
            [$transactionCode]
        )->fetch();
        
        if ($sessionData) {
            $response['username'] = $sessionData['username'];
            $response['password'] = 'auto_' . substr(bin2hex(random_bytes(8)), 0, 8); // Generate a secure random password
            $response['package_name'] = $sessionData['package_name'];
            $response['speed'] = $sessionData['speed_limit'];
            $response['duration'] = $sessionData['duration_value'] . ' ' . $sessionData['duration_type'];
            $response['wifi_name'] = 'WiFi Network'; // This should come from tenant settings
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}