<?php
// customer/history.php - Full Purchase History
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
    "SELECT c.*, t.company_name FROM customers c 
     JOIN tenants t ON c.tenant_id = t.id 
     WHERE c.id = ?",
    [$_SESSION['customer_id']]
)->fetch();

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count
$totalCount = $db->query(
    "SELECT COUNT(*) as count FROM customer_sessions WHERE customer_id = ?",
    [$customer['id']]
)->fetch()['count'];

$totalPages = ceil($totalCount / $limit);

// Get purchase history with pagination
$purchases = $db->query(
    "SELECT cs.*, tp.name as package_name, tp.price, tp.speed_limit,
            tr.amount, tr.status as payment_status, tr.created_at as purchase_date,
            tr.payment_method
     FROM customer_sessions cs
     JOIN tenant_packages tp ON cs.package_id = tp.id
     LEFT JOIN transactions tr ON cs.payment_reference = tr.payment_reference
     WHERE cs.customer_id = ?
     ORDER BY cs.created_at DESC
     LIMIT ? OFFSET ?",
    [$customer['id'], $limit, $offset]
)->fetchAll();

// Get summary statistics
$stats = $db->query(
    "SELECT 
        COUNT(*) as total_purchases,
        COALESCE(SUM(tr.amount), 0) as total_spent,
        COUNT(CASE WHEN cs.status = 'active' THEN 1 END) as active_sessions,
        AVG(cs.duration_minutes) as avg_duration
     FROM customer_sessions cs
     LEFT JOIN transactions tr ON cs.payment_reference = tr.payment_reference
     WHERE cs.customer_id = ?",
    [$customer['id']]
)->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase History - <?php echo htmlspecialchars($customer['company_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00BCD4; --primary-dark: #0097A7; }
        .stats-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); margin-bottom: 20px; padding: 20px; text-align: center; }
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
                <a class="nav-link text-white" href="profile.php">My Account</a>
                <a class="nav-link text-white active" href="history.php">History</a>
                <a class="nav-link text-white" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Purchase History</h2>
            <a href="profile.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Profile
            </a>
        </div>

        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <i class="fas fa-shopping-cart text-primary mb-2" style="font-size: 2rem;"></i>
                    <div class="h4 text-primary"><?php echo number_format($stats['total_purchases']); ?></div>
                    <div class="text-muted">Total Purchases</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <i class="fas fa-money-bill-wave text-success mb-2" style="font-size: 2rem;"></i>
                    <div class="h4 text-success">Ksh <?php echo number_format($stats['total_spent'], 2); ?></div>
                    <div class="text-muted">Total Spent</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <i class="fas fa-globe text-info mb-2" style="font-size: 2rem;"></i>
                    <div class="h4 text-info"><?php echo number_format($stats['active_sessions']); ?></div>
                    <div class="text-muted">Active Sessions</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <i class="fas fa-clock text-warning mb-2" style="font-size: 2rem;"></i>
                    <div class="h4 text-warning"><?php echo number_format($stats['avg_duration'] ?? 0, 0); ?>m</div>
                    <div class="text-muted">Avg Duration</div>
                </div>
            </div>
        </div>

        <!-- Purchase History Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Purchases (<?php echo number_format($totalCount); ?> total)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($purchases)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-receipt mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
                        <h5 class="text-muted">No purchase history</h5>
                        <p class="text-muted">Your internet purchases will appear here</p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-shopping-cart me-2"></i>Buy Internet Access
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background-color: var(--primary); color: white;">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Package</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Duration</th>
                                    <th>Speed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($purchases as $purchase): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo date('M j, Y', strtotime($purchase['purchase_date'])); ?></strong><br>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($purchase['purchase_date'])); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($purchase['package_name']); ?></strong><br>
                                            <small class="text-muted">
                                                <?php 
                                                if ($purchase['duration_minutes']) {
                                                    $hours = floor($purchase['duration_minutes'] / 60);
                                                    $minutes = $purchase['duration_minutes'] % 60;
                                                    if ($hours > 0) {
                                                        echo $hours . 'h';
                                                        if ($minutes > 0) echo ' ' . $minutes . 'm';
                                                    } else {
                                                        echo $minutes . 'm';
                                                    }
                                                }
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <strong>Ksh <?php echo number_format($purchase['amount'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $purchase['payment_method'] === 'mpesa' ? 'success' : 
                                                    ($purchase['payment_method'] === 'pesapal' ? 'info' : 'secondary'); 
                                            ?>">
                                                <?php echo strtoupper($purchase['payment_method']); ?>
                                            </span>
                                        </td>
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
                                            if ($purchase['status'] === 'active' && strtotime($purchase['end_time']) > time()) {
                                                $remaining = strtotime($purchase['end_time']) - time();
                                                $hours = floor($remaining / 3600);
                                                $minutes = floor(($remaining % 3600) / 60);
                                                echo '<span class="text-success">Active</span><br>';
                                                echo '<small class="text-muted">' . $hours . 'h ' . $minutes . 'm left</small>';
                                            } elseif ($purchase['status'] === 'expired') {
                                                echo '<span class="text-muted">Expired</span>';
                                            } else {
                                                echo '<span class="text-danger">Terminated</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <strong><?php echo $purchase['speed_limit']; ?>Mbps</strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="card-footer">
                            <nav>
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === (int)$page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>