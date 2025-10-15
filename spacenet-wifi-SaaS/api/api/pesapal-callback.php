// api/pesapal-callback.php - PesaPal Webhook Handler
<?php
header('Content-Type: application/json');

require_once '../includes/Database.php';
require_once '../includes/Billing.php';
require_once '../includes/Session.php';
require_once '../includes/PesapalAPI.php';

try {
    $orderTrackingId = $_GET['OrderTrackingId'] ?? null;
    $merchantReference = $_GET['OrderMerchantReference'] ?? null;
    
    if (!$orderTrackingId || !$merchantReference) {
        throw new Exception('Missing required parameters');
    }
    
    // Get PesaPal configuration
    $pesapalConfig = [
        'consumer_key' => $_ENV['PESAPAL_CONSUMER_KEY'] ?? '',
        'consumer_secret' => $_ENV['PESAPAL_CONSUMER_SECRET'] ?? '',
        'environment' => $_ENV['PESAPAL_ENVIRONMENT'] ?? 'sandbox'
    ];
    
    $pesapal = new PesapalAPI($pesapalConfig);
    $transactionStatus = $pesapal->getTransactionStatus($orderTrackingId);
    
    if ($transactionStatus['payment_status_description'] === 'Completed') {
        // Payment successful
        $db = Database::getInstance();
        $billing = new Billing();
        
        // Update transaction status
        $billing->updateTransaction($merchantReference, 'completed', $orderTrackingId);
        
        // Get transaction details
        $transaction = $billing->getTransactionByReference($merchantReference);
        
        if ($transaction) {
            // Create internet session
            $session = new Session();
            $metadata = json_decode($transaction['metadata'], true);
            
            $sessionId = $session->createSession(
                $transaction['tenant_id'],
                $transaction['customer_id'],
                $metadata['package_id'],
                $transaction['payment_reference']
            );
            
            error_log("PesaPal payment successful: Transaction {$merchantReference}, Session {$sessionId}");
        }
    } else {
        // Payment failed or pending
        error_log("PesaPal payment status: " . $transactionStatus['payment_status_description']);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("PesaPal callback error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}