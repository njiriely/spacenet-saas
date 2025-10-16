<?php
// customer/profile.php - Customer Profile Management
session_start();
require_once '../includes/database.php';

// Check if customer is logged in
if (!isset($_SESSION['customer_logged_in']) || !isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$db = \SpaceNet\Database::getInstance();

// Get customer data
$customer = $db->query(
    "SELECT c.*, t.company_name, t.subdomain 
     FROM customers c 
     JOIN tenants t ON c.tenant_id = t.id 
     WHERE c.id = ?",
    [$_SESSION['customer_id']]
)->fetch();

if (!$customer) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get purchase history
$purchases = $db->query(
    "SELECT cs.*, tp.name as package_name, tp.price, tr.amount, tr.status as payment_status, tr.created_at as purchase_date
     FROM customer_sessions cs
     JOIN tenant_packages tp ON cs.package_id = tp.id
     LEFT JOIN transactions tr ON cs.payment_reference = tr.payment_reference
     WHERE cs.customer_id = ?
     ORDER BY cs.created_at DESC
     LIMIT 20",
    [$customer['id']]
)->fetchAll();

// Get active sessions
$activeSessions = $db->query(
    "SELECT cs.*, tp.name as package_name, tp.speed_limit
     FROM customer_sessions cs
     JOIN tenant_packages tp ON cs.package_id = tp.id
     WHERE cs.customer_id = ? AND cs.status = 'active' AND cs.end_time > NOW()
     ORDER BY cs.start_time DESC",
    [$customer['id']]
)->fetchAll();

// Handle profile updates
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->query(
            "UPDATE customers SET email = ?, phone = ? WHERE id = ?",
            [$_POST['email'], $_POST['phone'], $customer['id']]
        );
        $message = "Profile updated successfully!";
        
        // Refresh customer data
        $customer = $db->query(
            "SELECT c.*, t.company_name, t.subdomain 
             FROM customers c 
             JOIN tenants t ON c.tenant_id = t.id 
             WHERE c.id = ?",
            [$_SESSION['customer_id']]
        )->fetch();
    } catch (Exception $e) {
        $message = "Error updating profile: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo htmlspecialchars($customer['company_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00BCD4; --primary-dark: #0097A7; }
        .profile-section { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); margin-bottom: 20px; padding: 20px; }
        .session-active { border-left: 4px solid #28a745; }
        .session-expired { border-left: 4px solid #dc3545; }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <nav class="navbar navbar-expand-lg" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark));">
        <div class="container">
            <a class="navbar-brand text-white fw-bold" href="index.php">
                <i class="fas fa-wifi me-2"></i><?php echo htmlspecialchars($customer['company_name']); ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-white" href="index.php">Buy Internet</a>
                <a class="nav-link text-white active" href="profile.php">My Account</a>
                <a class="nav-link text-white" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Account</h2>
            <div class="text-muted">
                Welcome, <strong><?php echo htmlspecialchars($customer['username']); ?></strong>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <!-- Active Sessions -->
                <div class="profile-section">
                    <h5 class="mb-4"><i class="fas fa-globe me-2 text-success"></i>Active Internet Sessions</h5>
                    <?php if (empty($activeSessions)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-wifi-slash mb-2" style="font-size: 3rem; opacity: 0.5;"></i>
                            <p class="mb-0">No active internet sessions</p>
                            <a href="index.php" class="btn btn-primary mt-3">
                                <i class="fas fa-shopping-cart me-2"></i>Buy Internet Access
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activeSessions as $session): ?>
                            <div class="card session-active mb-3">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($session['package_name']); ?></h6>
                                            <small class="text-muted">Speed: <?php echo $session['speed_limit']; ?>Mbps</small>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <div class="h6 mb-1">
                                                    <?php 
                                                    $remaining = strtotime($session['end_time']) - time();
                                                    if ($remaining > 0) {
                                                        $hours = floor($remaining / 3600);
                                                        $minutes = floor(($remaining % 3600) / 60);
                                                        echo $hours . 'h ' . $minutes . 'm';
                                                    } else {
                                                        echo 'Expired';
                                                    }
                                                    ?>
                                                </div>
                                                <small class="text-muted">Remaining</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <span class="badge bg-success">Active</span><br>
                                                <small class="text-muted">Since <?php echo date('g:i A', strtotime($session['start_time'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Purchase History -->
                <div class="profile-section">
                    <h5 class="mb-4"><i class="fas fa-history me-2 text-primary"></i>Purchase History</h5>
                    <?php if (empty($purchases)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-receipt mb-2" style="font-size: 3rem; opacity: 0.5;"></i>
                            <p class="mb-0">No purchase history yet</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Package</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($purchases as $purchase): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y g:i A', strtotime($purchase['purchase_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($purchase['package_name']); ?></td>
                                            <td>Ksh <?php echo number_format($purchase['amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $purchase['payment_status'] === 'completed' ? 'success' : 
                                                        ($purchase['payment_status'] === 'pending' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($purchase['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($purchase['status'] === 'active') {
                                                    echo '<span class="text-success">Active</span>';
                                                } elseif ($purchase['status'] === 'expired') {
                                                    echo '<span class="text-muted">Expired</span>';
                                                } else {
                                                    echo '<span class="text-danger">Terminated</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Profile Information -->
                <div class="profile-section">
                    <h5 class="mb-4"><i class="fas fa-user me-2 text-info"></i>Profile Information</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($customer['username']); ?>" readonly>
                            <small class="text-muted">Cannot be changed</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($customer['email']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($customer['phone']); ?>">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Account Statistics -->
                <div class="profile-section">
                    <h5 class="mb-4"><i class="fas fa-chart-bar me-2 text-warning"></i>Account Statistics</h5>
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="h4 text-primary"><?php echo count($purchases); ?></div>
                            <small class="text-muted">Total Purchases</small>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="h4 text-success"><?php echo count($activeSessions); ?></div>
                            <small class="text-muted">Active Sessions</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 text-warning">
                                Ksh <?php echo number_format(array_sum(array_column($purchases, 'amount')), 2); ?>
                            </div>
                            <small class="text-muted">Total Spent</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 text-info">
                                <?php echo date('M j', strtotime($customer['created_at'])); ?>
                            </div>
                            <small class="text-muted">Member Since</small>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="profile-section">
                    <h5 class="mb-4"><i class="fas fa-bolt me-2 text-success"></i>Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-shopping-cart me-2"></i>Buy More Internet
                        </a>
                        <a href="history.php" class="btn btn-outline-info">
                            <i class="fas fa-history me-2"></i>Full History
                        </a>
                        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#supportModal">
                            <i class="fas fa-headset me-2"></i>Contact Support
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Support Modal -->
    <div class="modal fade" id="supportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contact Support</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Get Help</h6>
                    <p>Need assistance with your internet service? Here are your options:</p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-phone text-primary me-3"></i>
                                <div>
                                    <strong>Phone Support</strong><br>
                                    <small class="text-muted">+254 700 123 456</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-envelope text-success me-3"></i>
                                <div>
                                    <strong>Email Support</strong><br>
                                    <small class="text-muted">support@<?php echo $customer['subdomain']; ?>.spacenet.co.ke</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6>Common Issues</h6>
                    <div class="accordion accordion-flush" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Can't connect to internet
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Check if you have an active session</li>
                                        <li>Try disconnecting and reconnecting to WiFi</li>
                                        <li>Clear your browser cache</li>
                                        <li>Contact support if issue persists</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Slow internet speed
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <ul>
                                        <li>Check your package speed limit</li>
                                        <li>Too many devices may slow connection</li>
                                        <li>Move closer to WiFi router</li>
                                        <li>Restart your device</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>