<?php
// config/app.php
class AppConfig {
    const APP_NAME = 'SPACE NET SaaS';
    const APP_URL = 'https://spacenet.co.ke';
    const TRIAL_DAYS = 15;
    const TIMEZONE = 'Africa/Nairobi';
    const SESSION_LIFETIME = 7200; // 2 hours
    
    // Subscription plans
    const PLANS = [
        'standard' => [
            'name' => 'Standard',
            'price' => 1999.00,
            'max_users' => 1000,
            'features' => ['Basic Analytics', 'M-Pesa Integration', 'Email Support']
        ],
        'professional' => [
            'name' => 'Professional', 
            'price' => 4999.00,
            'max_users' => 5000,
            'features' => ['Advanced Analytics', 'All Payment Gateways', 'Priority Support', 'Custom Branding']
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 9999.00, 
            'max_users' => -1, // unlimited
            'features' => ['Real-time Analytics', 'API Access', '24/7 Phone Support', 'Multi-location', 'White-label']
        ]
    ];
}