<?php
// api/mpesa-callback.php - M-Pesa Callback Handler
header('Content-Type: application/json');

require_once '../includes/Database.php';
require_once '../includes/Billing.php';
require_once '../includes/Session.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid callback data');
    }
    
    $resultCode = $data['Body']['stkCallback']['ResultCode'] ?? null;
    $checkoutRequestId = $data['Body']['stkCallback']['CheckoutRequestID'] ?? null;
    
    if ($resultCode === 0) {
        // Payment successful
        $callbackMetadata = $data['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];
        $mpesaReceiptNumber = '';
        $transactionDate = '';
        $phoneNumber = '';
        
        foreach ($callbackMetadata as $item) {
            switch ($item['Name']) {
                case 'MpesaReceiptNumber':
                    $mpesaReceiptNumber = $item['Value'];
                    break;
                case 'TransactionDate':
                    $transactionDate = $item['Value'];
                    break;
                case 'PhoneNumber':
                    $phoneNumber = $item['Value'];
                    break;
            }
        }
        
        // Find transaction and update status
        $db = Database::getInstance();
        $billing = new Billing();
        
        // Update transaction status
        $billing->updateTransaction($checkoutRequestId, 'completed', $mpesaReceiptNumber);
        
        // Get transaction details
        $transaction = $db->query(
            "SELECT t.*, c.username FROM transactions t 
             JOIN customers c ON t.customer_id = c.id 
             WHERE t.external_reference = ?", 
            [$mpesaReceiptNumber]
        )->fetch();
        
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
            
            // Log success
            error_log("M-Pesa payment successful: Transaction {$transaction['payment_reference']}, Session {$sessionId}");
        }
        
    } else {
        // Payment failed
        $errorMessage = $data['Body']['stkCallback']['ResultDesc'] ?? 'Payment failed';
        error_log("M-Pesa payment failed: {$errorMessage}");
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("M-Pesa callback error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}