<?php
// tenant/profile.php - User Profile Management
session_start();
require_once '../includes/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();
$currentUser = $auth->getCurrentUser();

$db = Database::getInstance();
$message = '';
$messageType = '';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_profile'])) {
            // Update user profile
            $db->query("
                UPDATE tenant_users SET 
                username = ?, email = ?, updated_at = NOW() 
                WHERE id = ?
            ", [$_POST['username'], $_POST['email'], $currentUser['id']]);
            
            $message = "Profile updated successfully!";
            $messageType = "success";
        }
        
        if (isset($_POST['change_password'])) {
            // Verify current password
            if (password_verify($_POST['current_password'], $currentUser['password_hash'])) {
                if ($_POST['new_password'] === $_POST['confirm_password']) {
                    $newPasswordHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $db->query("UPDATE tenant_users SET password_hash = ? WHERE id = ?", [$newPasswordHash, $currentUser['id']]);
                    
                    $message = "Password changed successfully!";
                    $messageType = "success";
                } else {
                    $message = "New passwords do not match!";
                    $messageType = "danger";
                }
            } else {
                $message = "Current password is incorrect!";
                $messageType = "danger";
            }
        }
        
        if (isset($_POST['update_company'])) {
            // Update company information
            $db->query("
                UPDATE tenants SET 
                company_name = ?, contact_person = ?, phone = ?, updated_at = NOW() 
                WHERE id = ?
            ", [$_POST['company_name'], $_POST['contact_person'], $_POST['phone'], $currentUser['tenant_id']]);
            
            $message = "Company information updated successfully!";
            $messageType = "success";
        }
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Get updated user data
$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($currentUser['company_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00BCD4; --primary-dark: #0097A7; }
        .profile-section { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); margin-bottom: 20px; padding: 25px; }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <nav class="navbar navbar-expand-lg" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark));">
        <div class="container">
            <a class="navbar-brand text-white fw-bold" href="index.php">
                <i class="fas fa-wifi me-2"></i><?php echo htmlspecialchars($currentUser['company_name']); ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-white" href="index.php">Dashboard</a>
                <a class="nav-link text-white active" href="profile.php">Profile</a>
                <a class="nav-link text-white" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <h2 class="mb-4">Profile Management</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <!-- Personal Information -->
                <div class="profile-section">
                    <h5 class="mb-4"><i class="fas fa-user me-2 text-primary"></i>Personal Information</h5>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" 
                                           value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="profile-section">
                    <h5 class="mb-4"><i class="fas fa-lock me-2 text-warning"></i>Change Password</h5>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" minlength="8" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Company Information -->
                <div class="profile-section">
                    <h5 class="mb-4"><i class="fas fa-building me-2 text-success"></i>Company Information</h5>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" name="company_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($currentUser['company_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Contact Person</label>
                                    <input type="text" name="contact_person" class="form-control" 
                                           value="<?php echo htmlspecialchars($currentUser['contact_person']); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentUser['phone']); ?>">
                        </div>
                        <div class="text-end">
                            <button type="submit" name="update_company" class="btn btn-success">
                                <i class="fas fa-building me-2"></i>Update Company Info
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Account Status -->
                <div class="profile-section">
                    <h5 class="mb-4"><i class="fas fa-info-circle me-2 text-info"></i>Account Status</h5>
                    <div class="mb-3">
                        <label class="form-label">Subscription Plan</label>
                        <div class="p-2 bg-light rounded">
                            <span class="badge bg-<?php echo $currentUser['subscription_plan'] === 'enterprise' ? 'success' : ($currentUser['subscription_plan'] === 'professional' ? 'warning' : 'primary'); ?>">
                                <?php echo ucfirst($currentUser['subscription_plan']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Status</label>
                        <div class="p-2 bg-light rounded">
                            <span class="badge bg-<?php echo $currentUser['tenant_status'] === 'active' ? 'success' : ($currentUser['tenant_status'] === 'trial' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($currentUser['tenant_status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subdomain</label>
                        <div class="p-2 bg-light rounded">
                            <code><?php echo htmlspecialchars($currentUser['subdomain']); ?>.spacenet.co.ke</code>
                        </div>
                    </div>
                    <?php if ($currentUser['tenant_status'] === 'trial'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock me-2"></i>
                            <strong>Trial Account</strong><br>
                            Upgrade to continue service after trial expires.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="profile-section">
                    <h5 class="mb-4"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="settings.php" class="btn btn-outline-primary">
                            <i class="fas fa-cog me-2"></i>System Settings
                        </a>
                        <a href="packages.php" class="btn btn-outline-success">
                            <i class="fas fa-box me-2"></i>Manage Packages
                        </a>
                        <a href="reports.php" class="btn btn-outline-info">
                            <i class="fas fa-chart-bar me-2"></i>View Reports
                        </a>
                        <a href="billing.php" class="btn btn-outline-warning">
                            <i class="fas fa-credit-card me-2"></i>Billing & Subscription
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
