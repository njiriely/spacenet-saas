// config/email.php - Email Configuration
<?php
class EmailConfig {
    // SMTP Configuration
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_ENCRYPTION = 'tls';
    
    // Email Templates
    const TEMPLATES = [
        'welcome' => [
            'subject' => 'Welcome to SPACE NET SaaS!',
            'template' => 'welcome.html'
        ],
        'trial_warning' => [
            'subject' => 'Trial Expiring Soon',
            'template' => 'trial_warning.html'
        ],
        'trial_expired' => [
            'subject' => 'Trial Expired - Upgrade Now',
            'template' => 'trial_expired.html'
        ],
        'payment_success' => [
            'subject' => 'Payment Confirmed - Internet Activated',
            'template' => 'payment_success.html'
        ],
        'password_reset' => [
            'subject' => 'Password Reset Request',
            'template' => 'password_reset.html'
        ]
    ];
    
    // Email Settings
    const FROM_ADDRESS = 'noreply@spacenet.co.ke';
    const FROM_NAME = 'SPACE NET SaaS';
    const REPLY_TO = 'support@spacenet.co.ke';
    
    // Notification Settings
    const SEND_WELCOME_EMAIL = true;
    const SEND_TRIAL_WARNINGS = true;
    const SEND_PAYMENT_CONFIRMATIONS = true;
    const TRIAL_WARNING_DAYS = [7, 3, 1]; // Days before expiry to send warnings
}