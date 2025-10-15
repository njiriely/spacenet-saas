<?php
// public/forgot-password.php - Password Reset
session_start();
require_once '../includes/Database.php';
require_once '../includes/EmailService.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $message = "Email address is required.";
        $messageType = "danger";
    } else {
        $db = Database::getInstance();
        
        // Check if email exists in tenant_users or admin_users
        $user = $db->query("SELECT * FROM tenant_users WHERE email = ? AND is_active = 1", [$email])->fetch();
        $userType = 'tenant';
        
        if (!$user) {
            $user = $db->query("SELECT * FROM admin_users WHERE email = ? AND is_active = 1", [$email])->fetch();
            $userType = 'admin';
        }
        
        if ($user) {
            // Generate password reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database (you'd need to create a password_resets table)
            $db->query("INSERT INTO password_resets (email, token, expires_at, user_type) VALUES (?, ?, ?, ?)", 
                      [$email, $token, $expiry, $userType]);
            
            // Send reset email
            $emailService = new EmailService();
            $emailService->sendPasswordResetEmail($user, $token);
            
            $message = "Password reset instructions have been sent to your email.";
            $messageType = "success";
        } else {
            $message = "If an account with that email exists, reset instructions have been sent.";
            $messageType = "info";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SPACE NET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00BCD4; --primary-dark: #0097A7; }
        .reset-container { min-height: 100vh; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); display: flex; align-items: center; }
        .reset-card { background: white; border-radius: 15px; padding: 40px; max-width: 400px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="reset-card">
                        <div class="text-center mb-4">
                            <i class="fas fa-key text-primary mb-3" style="font-size: 3rem;"></i>
                            <h2>Reset Password</h2>
                            <p class="text-muted">Enter your email to receive reset instructions</p>
                        </div>

                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                            </button>
                        </form>

                        <div class="text-center">
                            <a href="/login.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>Back to Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>