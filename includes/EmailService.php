// includes/EmailService.php - Email Notification System
<?php
class EmailService {
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromAddress;
    private $fromName;
    private $encryption;
    
    public function __construct($config = null) {
        $this->host = $config['host'] ?? $_ENV['MAIL_HOST'] ?? 'localhost';
        $this->port = $config['port'] ?? $_ENV['MAIL_PORT'] ?? 587;
        $this->username = $config['username'] ?? $_ENV['MAIL_USERNAME'] ?? '';
        $this->password = $config['password'] ?? $_ENV['MAIL_PASSWORD'] ?? '';
        $this->fromAddress = $config['from_address'] ?? $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@spacenet.co.ke';
        $this->fromName = $config['from_name'] ?? $_ENV['MAIL_FROM_NAME'] ?? 'SPACE NET SaaS';
        $this->encryption = $config['encryption'] ?? 'tls';
    }
    
    public function sendWelcomeEmail($tenantData, $loginCredentials) {
        $subject = "Welcome to SPACE NET SaaS - Your Account is Ready!";
        
        $body = $this->getEmailTemplate('welcome', [
            'company_name' => $tenantData['company_name'],
            'contact_person' => $tenantData['contact_person'],
            'subdomain' => $tenantData['subdomain'],
            'username' => $loginCredentials['username'],
            'password' => $loginCredentials['password'],
            'trial_end_date' => date('M j, Y', strtotime($tenantData['trial_end_date'])),
            'login_url' => "https://spacenet.co.ke/tenant/"
        ]);
        
        return $this->sendEmail($tenantData['email'], $subject, $body);
    }
    
    public function sendTrialExpiryWarning($tenantData, $daysLeft) {
        $subject = "Trial Expiring Soon - Upgrade Your SPACE NET Account";
        
        $body = $this->getEmailTemplate('trial_warning', [
            'company_name' => $tenantData['company_name'],
            'contact_person' => $tenantData['contact_person'],
            'days_left' => $daysLeft,
            'upgrade_url' => "https://spacenet.co.ke/tenant/billing.php"
        ]);
        
        return $this->sendEmail($tenantData['email'], $subject, $body);
    }
    
    public function sendTrialExpiredNotification($tenantData) {
        $subject = "Trial Expired - Upgrade to Continue Service";
        
        $body = $this->getEmailTemplate('trial_expired', [
            'company_name' => $tenantData['company_name'],
            'contact_person' => $tenantData['contact_person'],
            'upgrade_url' => "https://spacenet.co.ke/tenant/billing.php"
        ]);
        
        return $this->sendEmail($tenantData['email'], $subject, $body);
    }
    
    public function sendPaymentSuccessNotification($customerData, $transactionData) {
        $subject = "Payment Confirmed - Internet Access Activated";
        
        $body = $this->getEmailTemplate('payment_success', [
            'customer_name' => $customerData['username'],
            'package_name' => $transactionData['package_name'],
            'amount' => $transactionData['amount'],
            'duration' => $transactionData['duration'],
            'speed' => $transactionData['speed'],
            'login_credentials' => $transactionData['login_credentials']
        ]);
        
        return $this->sendEmail($customerData['email'], $subject, $body);
    }
    
    public function sendPasswordResetEmail($userData, $resetToken) {
        $subject = "Password Reset Request - SPACE NET SaaS";
        
        $body = $this->getEmailTemplate('password_reset', [
            'name' => $userData['name'] ?? $userData['username'],
            'reset_url' => "https://spacenet.co.ke/reset-password.php?token=" . $resetToken
        ]);
        
        return $this->sendEmail($userData['email'], $subject, $body);
    }
    
    private function sendEmail($to, $subject, $body) {
        try {
            // Use PHP's built-in mail function or PHPMailer if available
            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                return $this->sendWithPHPMailer($to, $subject, $body);
            } else {
                return $this->sendWithBuiltInMail($to, $subject, $body);
            }
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendWithBuiltInMail($to, $subject, $body) {
        $headers = [
            'From: ' . $this->fromName . ' <' . $this->fromAddress . '>',
            'Reply-To: ' . $this->fromAddress,
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        return mail($to, $subject, $body, implode("\r\n", $headers));
    }
    
    private function getEmailTemplate($template, $variables) {
        $templates = [
            'welcome' => '
                <html>
                <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                        <div style="background: linear-gradient(135deg, #00BCD4, #0097A7); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                            <h1>Welcome to SPACE NET SaaS!</h1>
                            <p>Your WiFi billing platform is ready</p>
                        </div>
                        <div style="background: white; padding: 30px; border: 1px solid #e0e0e0; border-radius: 0 0 10px 10px;">
                            <h2>Hello {contact_person}!</h2>
                            <p>Congratulations! Your SPACE NET SaaS account for <strong>{company_name}</strong> has been successfully created.</p>
                            
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                                <h3>Your Account Details:</h3>
                                <p><strong>Company:</strong> {company_name}</p>
                                <p><strong>Subdomain:</strong> {subdomain}.spacenet.co.ke</p>
                                <p><strong>Login URL:</strong> <a href="{login_url}">{login_url}</a></p>
                                <p><strong>Username:</strong> {username}</p>
                                <p><strong>Password:</strong> {password}</p>
                                <p><strong>Trial Expires:</strong> {trial_end_date}</p>
                            </div>
                            
                            <p>Your 15-day free trial includes:</p>
                            <ul>
                                <li>Complete WiFi billing system</li>
                                <li>MikroTik router integration</li>
                                <li>M-Pesa and PesaPal payments</li>
                                <li>Customer management portal</li>
                                <li>Real-time analytics</li>
                                <li>24/7 email support</li>
                            </ul>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{login_url}" style="background: #00BCD4; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Access Your Dashboard</a>
                            </div>
                            
                            <p>Need help getting started? Check out our <a href="https://docs.spacenet.co.ke">setup guide</a> or contact our support team.</p>
                            
                            <p>Best regards,<br>The SPACE NET Team</p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            
            'trial_warning' => '
                <html>
                <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                        <div style="background: linear-gradient(135deg, #FFC107, #FF8F00); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                            <h1>Trial Expiring Soon</h1>
                            <p>Only {days_left} days left on your free trial</p>
                        </div>
                        <div style="background: white; padding: 30px; border: 1px solid #e0e0e0; border-radius: 0 0 10px 10px;">
                            <h2>Hi {contact_person},</h2>
                            <p>Your free trial for <strong>{company_name}</strong> will expire in <strong>{days_left} days</strong>.</p>
                            
                            <p>Don\'t lose access to your WiFi billing platform! Upgrade now to continue serving your customers without interruption.</p>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{upgrade_url}" style="background: #4CAF50; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Upgrade Now</a>
                            </div>
                            
                            <p>Questions? Our support team is here to help at support@spacenet.co.ke</p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            
            'payment_success' => '
                <html>
                <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                        <div style="background: linear-gradient(135deg, #4CAF50, #388E3C); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                            <h1>Payment Confirmed!</h1>
                            <p>Your internet access is now active</p>
                        </div>
                        <div style="background: white; padding: 30px; border: 1px solid #e0e0e0; border-radius: 0 0 10px 10px;">
                            <h2>Hello {customer_name}!</h2>
                            <p>Your payment has been processed successfully. Your internet access is now active!</p>
                            
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                                <h3>Your Internet Package:</h3>
                                <p><strong>Package:</strong> {package_name}</p>
                                <p><strong>Amount Paid:</strong> Ksh {amount}</p>
                                <p><strong>Duration:</strong> {duration}</p>
                                <p><strong>Speed:</strong> {speed}Mbps</p>
                                <p><strong>Username:</strong> {login_credentials}</p>
                            </div>
                            
                            <p>To connect to the internet:</p>
                            <ol>
                                <li>Connect to the WiFi network</li>
                                <li>Open your browser</li>
                                <li>Enter your login credentials</li>
                                <li>Start browsing!</li>
                            </ol>
                            
                            <p>Enjoy your internet access!</p>
                        </div>
                    </div>
                </body>
                </html>
            '
        ];
        
        $template = $templates[$template] ?? '';
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }
}