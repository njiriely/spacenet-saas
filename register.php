<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/tenant.php';
require_once 'includes/AppConfig.php';
require_once 'includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $tenant = new \SpaceNet\Tenant();
        
        $data = [
            'company_name' => $_POST['company_name'],
            'contact_person' => $_POST['contact_person'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'subscription_plan' => $_POST['subscription_plan'],
            'mikrotik_ip' => $_POST['mikrotik_ip'] ?? null,
            'mikrotik_username' => $_POST['mikrotik_username'] ?? null,
            'mikrotik_password' => $_POST['mikrotik_password'] ?? null
        ];
        
        $tenantId = $tenant->create($data);
        
        // Redirect to success page
        header('Location: registration-success.php?tenant=' . $tenantId);
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        // Redirect back to the registration form with an error message
        header('Location: register.php?error=' . urlencode($error));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SPACE NET SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00BCD4;
            --primary-dark: #0097A7;
        }
        
        .register-container {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .register-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h1 {
            color: var(--primary);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-satellite text-primary mb-3" style="font-size: 3rem;"></i>
                <h1>SPACE NET</h1>
                <p class="text-muted">Start your free trial</p>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Subscription Plan</label>
                    <select name="subscription_plan" class="form-select" required>
                        <option value="standard">Standard (KSh 1999/month)</option>
                        <option value="professional">Professional (KSh 4999/month)</option>
                        <option value="enterprise">Enterprise (KSh 9999/month)</option>
                    </select>
                </div>
                
                <hr>
                
                <h5 class="mb-3">MikroTik Configuration (Optional)</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">MikroTik IP</label>
                        <input type="text" name="mikrotik_ip" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">MikroTik Username</label>
                        <input type="text" name="mikrotik_username" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">MikroTik Password</label>
                        <input type="password" name="mikrotik_password" class="form-control">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-lg mt-3">
                    <i class="fas fa-rocket me-2"></i>Start Free Trial
                </button>
                
                <div class="text-center mt-3">
                    <a href="login.php" class="text-decoration-none">Already have an account? Sign In</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
