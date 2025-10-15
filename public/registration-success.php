<?php
// registration-success.php
session_start();
require_once 'includes/Database.php';
require_once 'includes/Tenant.php';

$tenantId = $_GET['tenant'] ?? null;
if (!$tenantId) {
    header('Location: index.php');
    exit;
}

$tenant = new Tenant();
$tenantData = $tenant->getTenant($tenantId);
if (!$tenantData) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - SPACE NET SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00BCD4;
            --primary-dark: #0097A7;
            --success: #4CAF50;
        }
        
        .success-container {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .success-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 600px;
        }
        
        .success-icon {
            font-size: 5rem;
            color: var(--success);
            margin-bottom: 20px;
        }
        
        .countdown {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <i class="fas fa-check-circle success-icon"></i>
            <h1 class="mb-4">Welcome to SPACE NET SaaS!</h1>
            <p class="lead mb-4">Your free trial has been activated successfully.</p>
            
            <div class="row text-start mb-4">
                <div class="col-md-6">
                    <h6>Account Details:</h6>
                    <ul class="list-unstyled">
                        <li><strong>Company:</strong> <?php echo htmlspecialchars($tenantData['company_name']); ?></li>
                        <li><strong>Subdomain:</strong> <?php echo htmlspecialchars($tenantData['subdomain']); ?>.spacenet.co.ke</li>
                        <li><strong>Plan:</strong> <?php echo ucfirst($tenantData['subscription_plan']); ?></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Trial Information:</h6>
                    <ul class="list-unstyled">
                        <li><strong>Trial Period:</strong> 15 days</li>
                        <li><strong>Expires:</strong> <?php echo date('M j, Y', strtotime($tenantData['trial_end_date'])); ?></li>
                        <li><strong>Status:</strong> <span class="badge bg-success">Active</span></li>
                    </ul>
                </div>
            </div>
            
            <div class="alert alert-info">
                <h6><i class="fas fa-envelope me-2"></i>Check Your Email</h6>
                <p class="mb-0">We've sent login credentials and setup instructions to <strong><?php echo htmlspecialchars($tenantData['email']); ?></strong></p>
            </div>
            
            <div class="d-flex gap-3 justify-content-center">
                <a href="https://<?php echo htmlspecialchars($tenantData['subdomain']); ?>.spacenet.co.ke" class="btn btn-primary btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Access Your Portal
                </a>
                <a href="setup-guide.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-book me-2"></i>Setup Guide
                </a>
            </div>
            
            <div class="mt-4">
                <small class="text-muted">
                    Need help? Contact us at <a href="mailto:support@spacenet.co.ke">support@spacenet.co.ke</a> or +254 700 123 456
                </small>
            </div>
        </div>
    </div>
</body>
</html>