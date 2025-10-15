<?php
// customer/login.php - Customer Account System
session_start();
require_once '../includes/Database.php';

$error = '';
$success = '';

// Handle customer login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $db = Database::getInstance();
    $customer = $db->query(
        "SELECT c.*, t.subdomain FROM customers c 
         JOIN tenants t ON c.tenant_id = t.id 
         WHERE c.username = ? AND c.password_hash IS NOT NULL",
        [$username]
    )->fetch();
    
    if ($customer && password_verify($password, $customer['password_hash'])) {
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_logged_in'] = true;
        header('Location: profile.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}

// Handle account creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_account') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $error = 'Passwords do not match';
    } else {
        $db = Database::getInstance();
        
        // Check if customer exists
        $existing = $db->query("SELECT id FROM customers WHERE username = ?", [$username])->fetch();
        
        if ($existing) {
            // Update existing customer with account info
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $db->query(
                "UPDATE customers SET email = ?, phone = ?, password_hash = ? WHERE username = ?",
                [$email, $phone, $passwordHash, $username]
            );
            $success = 'Account created successfully! You can now login.';
        } else {
            $error = 'Username not found. Please purchase internet access first.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - SPACE NET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00BCD4; --primary-dark: #0097A7; }
        body { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); min-height: 100vh; }
        .login-container { padding: 80px 0; }
        .login-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1); }
        .nav-tabs .nav-link { color: var(--primary); }
        .nav-tabs .nav-link.active { background-color: var(--primary); color: white; }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <i class="fas fa-wifi text-primary mb-3" style="font-size: 4rem;"></i>
                        <h2>Customer Portal</h2>
                        <p class="text-muted">Manage your internet account</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#login" type="button">Login</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#create-account" type="button">Create Account</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Login Tab -->
                        <div class="tab-pane fade show active" id="login">
                            <form method="POST">
                                <input type="hidden" name="action" value="login">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="rememberMe">
                                    <label class="form-check-label" for="rememberMe">Remember me</label>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mb-3">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </form>
                        </div>

                        <!-- Create Account Tab -->
                        <div class="tab-pane fade" id="create-account">
                            <div class="alert alert-info">
                                <small><i class="fas fa-info-circle me-2"></i>You must have purchased internet access to create an account</small>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="create_account">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" required>
                                    <small class="text-muted">The username you used when purchasing internet</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" minlength="6" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-cart me-2"></i>Buy Internet Access
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>