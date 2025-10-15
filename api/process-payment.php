<?php
// api/process-payment.php - Payment Processing API
session_start();
header('Content-Type: application/json');

require_once '../includes/Database.php';
require_once '../includes/User.php';
require_once '../includes/Billing.php';
require_once '../includes/Session.php';
require_once '../includes/MpesaAPI.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $tenantId = $_POST['tenant_id'] ?? null;
    $packageId = $_POST['package_id'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $username = $_POST['username'] ?? null;
    $email = $_POST['email'] ?? null;
    $paymentMethod = $_POST['payment_method'] ?? 'mpesa';
    $deviceCount = intval($_POST['device_count'] ?? 1);
    $totalAmount = floatval($_POST['total_amount'] ?? 0);
    
    if (!$tenantId || !$packageId || !$phone || !$username || !$totalAmount) {
        throw new Exception('Missing required fields');
    }
    
    // Validate phone number format
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 3) === '254') {
        $phone = '+' . $phone;
    } elseif (substr($phone, 0, 1) === '0') {
        $phone = '+254' . substr($phone, 1);
    } elseif (!str_starts_with($phone, '+254')) {
        throw new Exception('Invalid phone number format');
    }
    
    // Create customer
    $user = new User();
    $customerId = $user->createCustomer($tenantId, [
        'username' => $username,
        'email' => $email,
        'phone' => $phone
    ]);
    
    if (!$customerId) {
        throw new Exception('Failed to create customer');
    }
    
    // Create transaction
    $billing = new Billing();
    $transactionId = $billing->createTransaction(
        $tenantId, 
        $customerId, 
        'package_purchase', 
        $totalAmount, 
        $paymentMethod,
        [
            'package_id' => $packageId,
            'device_count' => $deviceCount,
            'username' => $username
        ]
    );
    
    if (!$transactionId) {
        throw new Exception('Failed to create transaction');
    }
    
    $transaction = $billing->getTransactionByReference("TXN_" . $transactionId);
    
    if ($paymentMethod === 'mpesa') {
        // Process M-Pesa payment
        $mpesaConfig = [
            'consumer_key' => 'your_mpesa_consumer_key',
            'consumer_secret' => 'your_mpesa_consumer_secret',
            'environment' => 'sandbox', // or 'production'
            'shortcode' => '174379',
            'passkey' => 'your_passkey',
            'callback_url' => 'https://yourdomain.com/api/mpesa-callback.php'
        ];
        
        $mpesa = new MpesaAPI($mpesaConfig);
        $response = $mpesa->stkPush(
            $totalAmount,
            $phone,
            $transaction['payment_reference'],
            "Internet package - {$username}"
        );
        
        if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
            echo json_encode([
                'success' => true,
                'payment_method' => 'mpesa',
                'transaction_code' => $transaction['payment_reference'],
                'amount' => $totalAmount,
                'checkout_request_id' => $response['CheckoutRequestID']
            ]);
        } else {
            throw new Exception('M-Pesa payment failed: ' . ($response['errorMessage'] ?? 'Unknown error'));
        }
        
    } else {
        // Process PesaPal payment
        // This would integrate with PesaPal API
        echo json_encode([
            'success' => true,
            'payment_method' => 'pesapal',
            'redirect_url' => 'https://pesapal.com/checkout/' . $transaction['payment_reference']
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}