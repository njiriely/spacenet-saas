// config/payment_gateways.php - Payment Gateway Configuration
<?php
class PaymentGatewayConfig {
    // M-Pesa Configuration
    const MPESA = [
        'enabled' => true,
        'environment' => 'sandbox', // or 'production'
        'consumer_key' => '',
        'consumer_secret' => '',
        'shortcode' => '174379',
        'passkey' => '',
        'callback_url' => 'https://spacenet.co.ke/api/mpesa-callback.php',
        'timeout_url' => 'https://spacenet.co.ke/api/mpesa-timeout.php',
        'result_url' => 'https://spacenet.co.ke/api/mpesa-result.php'
    ];
    
    // PesaPal Configuration  
    const PESAPAL = [
        'enabled' => true,
        'environment' => 'sandbox', // or 'production'
        'consumer_key' => '',
        'consumer_secret' => '',
        'callback_url' => 'https://spacenet.co.ke/api/pesapal-callback.php',
        'ipn_url' => 'https://spacenet.co.ke/api/pesapal-ipn.php'
    ];
    
    // Stripe Configuration (Future)
    const STRIPE = [
        'enabled' => false,
        'publishable_key' => '',
        'secret_key' => '',
        'webhook_secret' => ''
    ];
    
    // PayPal Configuration (Future)
    const PAYPAL = [
        'enabled' => false,
        'client_id' => '',
        'client_secret' => '',
        'environment' => 'sandbox'
    ];
    
    // Payment Settings
    const DEFAULT_CURRENCY = 'KES';
    const SUPPORTED_CURRENCIES = ['KES', 'USD', 'EUR'];
    const PAYMENT_TIMEOUT = 300; // 5 minutes
    const MAX_RETRY_ATTEMPTS = 3;
    
    public static function getEnabledGateways() {
        $gateways = [];
        
        if (self::MPESA['enabled']) {
            $gateways['mpesa'] = 'M-Pesa';
        }
        
        if (self::PESAPAL['enabled']) {
            $gateways['pesapal'] = 'PesaPal';
        }
        
        if (self::STRIPE['enabled']) {
            $gateways['stripe'] = 'Stripe';
        }
        
        if (self::PAYPAL['enabled']) {
            $gateways['paypal'] = 'PayPal';
        }
        
        return $gateways;
    }
}