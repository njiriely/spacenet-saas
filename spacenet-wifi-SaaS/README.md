<?php
// README.md - Installation and Setup Documentation
?>
# SPACE NET SaaS - WiFi Billing System

## Overview
Complete SaaS platform for Internet Service Providers in Kenya, featuring:
- Multi-tenant architecture
- 15-day free trials
- MikroTik RouterOS integration
- M-Pesa and PesaPal payment gateways
- Real-time session management
- Comprehensive analytics

## System Requirements

### Server Requirements
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher (8.0 recommended)
- **Web Server**: Apache/Nginx with mod_rewrite
- **Memory**: Minimum 512MB RAM (2GB recommended)
- **Storage**: Minimum 1GB free space

### PHP Extensions
- PDO and PDO_MySQL
- cURL
- JSON
- OpenSSL
- mbstring
- fileinfo

### Recommended Server Configuration
```apache
# Apache Virtual Host Example
<VirtualHost *:80>
    ServerName spacenet.co.ke
    ServerAlias *.spacenet.co.ke
    DocumentRoot /var/www/spacenet-saas/public
    
    <Directory /var/www/spacenet-saas/public>
        Options -Indexes
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/spacenet_error.log
    CustomLog ${APACHE_LOG_DIR}/spacenet_access.log combined
</VirtualHost>
```

## Installation

### 1. Download and Extract
```bash
# Download the latest release
wget https://github.com/spacenet/saas/releases/latest/spacenet-saas.zip
unzip spacenet-saas.zip
cd spacenet-saas
```

### 2. Set Permissions
```bash
# Make directories writable
chmod 755 config/
chmod 755 uploads/
chmod 755 logs/
chown -R www-data:www-data .
```

### 3. Run Installation Wizard
Navigate to `http://yourdomain.com/install/setup.php` and follow the installation wizard.

### 4. Configure Cron Jobs
Add these cron jobs for automated tasks:

```bash
# Check trial expirations (daily at 1 AM)
0 1 * * * /usr/bin/php /path/to/spacenet-saas/cron/trial_expiry.php

# Process subscription billing (daily at 2 AM)
0 2 * * * /usr/bin/php /path/to/spacenet-saas/cron/billing.php

# Clean up expired sessions (every 5 minutes)
*/5 * * * * /usr/bin/php /path/to/spacenet-saas/cron/cleanup.php
```

### 5. Configure Subdomain Wildcards

#### Apache Configuration
```apache
# Enable wildcard subdomains
ServerAlias *.spacenet.co.ke

# Rewrite rules for tenant routing
RewriteEngine On
RewriteCond %{HTTP_HOST} ^([^.]+)\.spacenet\.co\.ke$ [NC]
RewriteRule ^(.*)$ /customer/index.php?tenant=%1 [QSA,L]
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name spacenet.co.ke *.spacenet.co.ke;
    root /var/www/spacenet-saas/public;
    
    location / {
        if ($host ~* ^([^.]+)\.spacenet\.co\.ke$) {
            set $tenant $1;
            rewrite ^(.*)$ /customer/index.php?tenant=$tenant last;
        }
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

## Configuration

### Payment Gateway Setup

#### M-Pesa Configuration
1. Register with Safaricom for M-Pesa API
2. Get Consumer Key, Consumer Secret, and Passkey
3. Configure in Admin Panel > Settings > Payment Gateways

#### PesaPal Configuration
1. Sign up at https://pesapal.com
2. Get API credentials
3. Configure callback URLs

### MikroTik Integration
1. Enable API on your MikroTik router:
   ```
   /ip service enable api
   /ip service set api port=8728
   ```
2. Create API user with hotspot permissions
3. Configure router details in tenant settings

## Usage

### Super Admin Access
- URL: `https://yourdomain.com/admin/`
- Manage ISP clients, billing, platform settings

### ISP Client Access
- URL: `https://yourdomain.com/tenant/`
- Each ISP gets their own management dashboard

### Customer Portal
- URL: `https://ispname.yourdomain.com/`
- Customers purchase packages and access internet

## API Documentation

### Authentication
All API requests require authentication header:
```
Authorization: Bearer <api_key>
```

### Endpoints

#### Create Customer
```http
POST /api/customers/create
Content-Type: application/json

{
    "username": "customer1",
    "email": "customer@email.com",
    "phone": "+254700123456"
}
```

#### Process Payment
```http
POST /api/payments/process
Content-Type: application/json

{
    "customer_id": 123,
    "package_id": 456,
    "payment_method": "mpesa",
    "phone": "+254700123456",
    "amount": 100.00
}
```

## Troubleshooting

### Common Issues

#### Database Connection Errors
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify user has proper permissions

#### M-Pesa Integration Issues
- Confirm credentials are for correct environment (sandbox/production)
- Check callback URL is accessible
- Verify SSL certificate if using production

#### Session Management Problems
- Check MikroTik router connectivity
- Verify API credentials and permissions
- Ensure proper firewall rules

### Log Files
- Application logs: `logs/app.log`
- Error logs: `logs/error.log`
- Payment logs: `logs/payments.log`

## Security

### Best Practices
- Use HTTPS in production
- Keep PHP and dependencies updated
- Regular database backups
- Monitor access logs
- Use strong passwords and 2FA

### File Permissions
```bash
# Secure file permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 600 config/*.php
```

## Support

### Documentation
- Setup Guide: `docs/setup.md`
- API Reference: `docs/api.md`
- FAQ: `docs/faq.md`

### Community
- GitHub Issues: https://github.com/spacenet/saas/issues
- Community Forum: https://community.spacenet.co.ke
- Documentation: https://docs.spacenet.co.ke

### Commercial Support
- Email: support@spacenet.co.ke
- Phone: +254 746 971 061
- Business Hours: Mon-Fri 8AM-6PM EAT

## License
This software is licensed under the MIT License. See LICENSE file for details.

## Changelog

### Version 1.0.0 (2025-01-01)
- Initial release
- Multi-tenant SaaS architecture
- 15-day free trials
- MikroTik integration
- M-Pesa and PesaPal support
- Real-time analytics
- Mobile-responsive interface