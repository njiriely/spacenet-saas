<?php
// tenant/settings.php - Tenant Settings
session_start();
require_once '../includes/database.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->requireLogin();
$currentUser = $auth->getCurrentUser();

$db = Database::getInstance();
$message = '';
$messageType = '';

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'];
        
        if ($action === 'update_company') {
            $db->query("
                UPDATE tenants SET 
                company_name = ?, contact_person = ?, phone = ?, updated_at = NOW() 
                WHERE id = ?
            ", [$_POST['company_name'], $_POST['contact_person'], $_POST['phone'], $currentUser['tenant_id']]);
            
            $message = "Company information updated successfully!";
            $messageType = "success";
        }
        
        if ($action === 'update_mikrotik') {
            $mikrotikPassword = $_POST['mikrotik_password'] ? password_hash($_POST['mikrotik_password'], PASSWORD_DEFAULT) : null;
            $db->query("
                UPDATE tenants SET 
                mikrotik_ip = ?, mikrotik_username = ?, mikrotik_password = ?, updated_at = NOW() 
                WHERE id = ?
            ", [$_POST['mikrotik_ip'], $_POST['mikrotik_username'], $mikrotikPassword, $currentUser['tenant_id']]);
            
            $message = "MikroTik settings updated successfully!";
            $messageType = "success";
        }
        
        // Refresh user data
        $currentUser = $auth->getCurrentUser();
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo htmlspecialchars($currentUser['company_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00BCD4; --primary-dark: #0097A7; }
        .settings-section { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); margin-bottom: 20px; padding: 25px; }
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
                <a class="nav-link text-white active" href="settings.php">Settings</a>
                <a class="nav-link text-white" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <h2 class="mb-4">Settings</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <!-- Company Information -->
                <div class="settings-section">
                    <h5 class="mb-4"><i class="fas fa-building me-2 text-primary"></i>Company Information</h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_company">
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
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Company Info
                        </button>
                    </form>
                </div>

                <!-- MikroTik Configuration -->
                <div class="settings-section">
                    <h5 class="mb-4"><i class="fas fa-router me-2 text-success"></i>MikroTik Router Configuration</h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_mikrotik">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Router IP Address</label>
                                    <input type="text" name="mikrotik_ip" class="form-control" 
                                           value="<?php echo htmlspecialchars($currentUser['mikrotik_ip']); ?>" 
                                           placeholder="192.168.1.1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="mikrotik_username" class="form-control" 
                                           value="<?php echo htmlspecialchars($currentUser['mikrotik_username']); ?>" 
                                           placeholder="admin">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="mikrotik_password" class="form-control" 
                                           placeholder="Leave blank to keep current">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Update MikroTik Settings
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="testConnection()">
                                <i class="fas fa-plug me-2"></i>Test Connection
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Account Status -->
                <div class="settings-section">
                    <h5 class="mb-4"><i class="fas fa-info-circle me-2 text-info"></i>Account Status</h5>
                    <div class="mb-3">
                        <label class="form-label">Subscription Plan</label>
                        <div class="p-2 bg-light rounded">
                            <span class="badge bg-<?php echo $currentUser['subscription_plan'] === 'enterprise' ? 'success' : ($currentUser['subscription_plan'] === 'professional' ? 'warning' : 'primary'); ?>