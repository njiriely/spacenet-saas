<?php
// login.php - Login Page
session_start();
require_once 'includes/database.php';
require_once 'includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $userType = $_POST['user_type'] ?? 'tenant';
    
    $auth = new SpaceNet\Auth();
    if ($auth->login($email, $password, $userType)) {
        if ($userType === 'admin') {
            header('Location: admin/index.php');
        } else {
            header('Location: tenant/index.php');
        }
        exit;
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SPACE NET SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00BCD4;
            --primary-dark: #0097A7;
        }
        
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: var(--primary);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-satellite text-primary mb-3" style="font-size: 3rem;"></i>
                <h1>SPACE NET</h1>
                <p class="text-muted">Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Login As</label>
                    <select name="user_type" class="form-select">
                        <option value="tenant">ISP Admin</option>
                        <option value="admin">Super Admin</option>
                    </select>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">Remember me</label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>
                
                <div class="text-center">
                    <a href="forgot-password.php" class="text-decoration-none">Forgot your password?</a>
                </div>
            </form>
            
            <hr>
            
            <div class="text-center">
                <p class="text-muted mb-2">Don't have an account?</p>
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-rocket me-2"></i>Start Free Trial
                </a>
            </div>
        </div>
    </div>
</body>
</html>