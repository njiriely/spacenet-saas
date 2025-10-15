
<?php
// includes/PesapalAPI.php - PesaPal Payment Gateway Integration
class PesapalAPI {
    private $consumerKey;
    private $consumerSecret;
    private $environment;
    private $baseUrl;
    
    public function __construct($config) {
        $this->consumerKey = $config['consumer_key'];
        $this->consumerSecret = $config['consumer_secret'];
        $this->environment = $config['environment'] ?? 'sandbox';
        $this->baseUrl = $this->environment === 'production' 
            ? 'https://pay.pesapal.com/v3/' 
            : 'https://cybqa.pesapal.com/pesapalv3/';
    }
    
    public function authenticate() {
        $url = $this->baseUrl . 'api/Auth/RequestToken';
        
        $data = [
            'consumer_key' => $this->consumerKey,
            'consumer_secret' => $this->consumerSecret
        ];
        
        $response = $this->makeRequest($url, 'POST', $data);
        
        if (isset($response['token'])) {
            return $response['token'];
        }
        
        throw new Exception('PesaPal authentication failed: ' . json_encode($response));
    }
    
    public function registerIPN($callbackUrl) {
        $token = $this->authenticate();
        $url = $this->baseUrl . 'api/URLSetup/RegisterIPN';
        
        $data = [
            'url' => $callbackUrl,
            'ipn_notification_type' => 'GET'
        ];
        
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];
        
        return $this->makeRequest($url, 'POST', $data, $headers);
    }
    
    public function submitOrderRequest($orderData) {
        $token = $this->authenticate();
        $url = $this->baseUrl . 'api/Transactions/SubmitOrderRequest';
        
        $data = [
            'id' => $orderData['reference'],
            'currency' => 'KES',
            'amount' => $orderData['amount'],
            'description' => $orderData['description'],
            'callback_url' => $orderData['callback_url'],
            'notification_id' => $orderData['notification_id'] ?? '',
            'billing_address' => [
                'phone_number' => $orderData['phone'],
                'email_address' => $orderData['email'] ?? '',
                'country_code' => 'KE',
                'first_name' => $orderData['first_name'] ?? '',
                'last_name' => $orderData['last_name'] ?? ''
            ]
        ];
        
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];
        
        $response = $this->makeRequest($url, 'POST', $data, $headers);
        
        if (isset($response['order_tracking_id'])) {
            return [
                'success' => true,
                'order_tracking_id' => $response['order_tracking_id'],
                'merchant_reference' => $response['merchant_reference'],
                'redirect_url' => $response['redirect_url']
            ];
        }
        
        return [
            'success' => false,
            'error' => $response['error']['message'] ?? 'Unknown error'
        ];
    }
    
    public function getTransactionStatus($orderTrackingId) {
        $token = $this->authenticate();
        $url = $this->baseUrl . 'api/Transactions/GetTransactionStatus?orderTrackingId=' . $orderTrackingId;
        
        $headers = [
            'Authorization: Bearer ' . $token
        ];
        
        return $this->makeRequest($url, 'GET', null, $headers);
    }
    
    private function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array_merge([
                'Accept: application/json',
                'Content-Type: application/json'
            ], $headers),
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        if ($data && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($response === false) {
            throw new Exception('cURL Error: ' . curl_error($curl));
        }
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception('HTTP Error ' . $httpCode . ': ' . $response);
        }
        
        return $decodedResponse;
    }
}