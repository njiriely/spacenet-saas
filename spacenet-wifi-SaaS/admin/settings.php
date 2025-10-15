<?php
// admin/settings.php - System Settings Management
session_start();
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();
$currentUser = $auth->getCurrentUser();

if ($currentUser['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST['settings'] as $key => $value) {
            $db->query(
                "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [$key, $value]
            );
        }
        $message = "Settings updated successfully!";
        $messageType = "success";
    } catch (Exception $e) {
        $message = "Error updating settings: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Get current settings
$settings = [];
$settingsResult = $db->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll();
foreach ($settingsResult as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - SPACE NET Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00BCD4;
            --primary-dark: #0097A7;
        }
        
        .settings-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            padding: 25px;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <nav class="navbar navbar-expand-lg" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark));">
        <div class="container">
            <a class="navbar-brand text-white fw-bold" href="index.php">
                <i class="fas fa-satellite me-2"></i>SPACE NET Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-white" href="index.php">Dashboard</a>
                <a class="nav-link text-white active" href="settings.php">Settings</a>
                <a class="nav-link text-white" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <h2 class="mb-4">System Settings</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <!-- General Settings -->
            <div class="settings-section">
                <h5 class="mb-4"><i class="fas fa-cog me-2 text-primary"></i>General Settings</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="settings[company_name]" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['company_name'] ?? 'SPACE NET SaaS'); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Application URL</label>
                            <input type="url" name="settings[app_url]" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['app_url'] ?? 'https://spacenet.co.ke'); ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Support Email</label>
                            <input type="email" name="settings[support_email]" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['support_email'] ?? 'support@spacenet.co.ke'); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Support Phone</label>
                            <input type="tel" name="settings[support_phone]" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['support_phone'] ?? '+254700123456'); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trial Settings -->
            <div class="settings-section">
                <h5 class="mb-4"><i class="fas fa-clock me-2 text-warning"></i>Trial Settings</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Trial Duration (days)</label>
                            <input type="number" name="settings[trial_duration_days]" class="form-control" min="1" max="90"
                                   value="<?php echo htmlspecialchars($settings['trial_duration_days'] ?? '15'); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Enable Registration</label>
                            <select name="settings[enable_registration]" class="form-select">
                                <option value="1" <?php echo ($settings['enable_registration'] ?? '1') == '1' ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo ($settings['enable_registration'] ?? '1') == '0' ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Maintenance Mode</label>
                            <select name="settings[maintenance_mode]" class="form-select">
                                <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') == '0' ? 'selected' : ''; ?>>Disabled</option>
                                <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'selected' : ''; ?>>Enabled</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Settings -->
            <div class="settings-section">
                <h5 class="mb-4"><i class="fas fa-money-bill-wave me-2 text-success"></i>Subscription Pricing</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Standard Plan (KES)</label>
                            <input type="number" name="settings[standard_plan_price]" class="form-control" step="0.01"
                                   value="<?php echo htmlspecialchars($settings['standard_plan_price'] ?? '1999.00'); ?>">
                            <small class="text-muted">Up to 1,000 users</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Professional Plan (KES)</label>
                            <input type="number" name="settings[professional_plan_price]" class="form-control" step="0.01"
                                   value="<?php echo htmlspecialchars($settings['professional_plan_price'] ?? '4999.00'); ?>">
                            <small class="text-muted">Up to 5,000 users</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Enterprise Plan (KES)</label>
                            <input type="number" name="settings[enterprise_plan_price]" class="form-control" step="0.01"
                                   value="<?php echo htmlspecialchars($settings['enterprise_plan_price'] ?? '9999.00'); ?>">
                            <small class="text-muted">Unlimited users</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Settings -->
            <div class="settings-section">
                <h5 class="mb-4"><i class="fas fa-server me-2 text-info"></i>System Settings</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Session Timeout (seconds)</label>
                            <input type="number" name="settings[session_timeout]" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['session_timeout'] ?? '7200'); ?>">
                            <small class="text-muted">Default: 7200 (2 hours)</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Log Retention (days)</label>
                            <input type="number" name="settings[log_retention_days]" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['log_retention_days'] ?? '90'); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Backup Retention (days)</label>
                            <input type="number" name="settings[backup_retention_days]" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['backup_retention_days'] ?? '30'); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- M-Pesa Settings -->
            <div class="settings-section">
                <h5 class="mb-4"><i class="fas fa-mobile-alt me-2 text-success"></i>M-Pesa Configuration</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Business Shortcode</label>
                            <input type="text" name="settings[mpesa_shortcode]" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['mpesa_shortcode'] ?? '174379'); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Environment</label>
                            <select name="settings[mpesa_environment]" class="form-select">
                                <option value="sandbox" <?php echo ($settings['mpesa_environment'] ?? 'sandbox') == 'sandbox' ? 'selected' : ''; ?>>Sandbox</option>
                                <option value="production" <?php echo ($settings['mpesa_environment'] ?? 'sandbox') == 'production' ? 'selected' : ''; ?>>Production</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
