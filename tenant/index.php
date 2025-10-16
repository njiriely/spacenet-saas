<?php
// tenant/index.php - Tenant Dashboard
session_start();
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/DatabaseConfig.php';

$auth = new SpaceNet\Auth();
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();

// Check if user is tenant admin
if (!isset($_SESSION['tenant_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = SpaceNet\Database::getInstance();

// Get tenant information
$tenant = $db->query("SELECT * FROM tenants WHERE id = ?", [$_SESSION['tenant_id']])->fetch();

// Get statistics
$totalCustomers = $db->query("SELECT COUNT(*) as count FROM customers WHERE tenant_id = ?", [$_SESSION['tenant_id']])->fetch()['count'];
$activeCustomers = $db->query("SELECT COUNT(*) as count FROM customers WHERE tenant_id = ? AND status = 'active'", [$_SESSION['tenant_id']])->fetch()['count'];
$totalPackages = $db->query("SELECT COUNT(*) as count FROM tenant_packages WHERE tenant_id = ?", [$_SESSION['tenant_id']])->fetch()['count'];
$monthlyRevenue = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE tenant_id = ? AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", [$_SESSION['tenant_id']])->fetch()['total'];

// Get recent customers
$recentCustomers = $db->query("SELECT * FROM customers WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 5", [$_SESSION['tenant_id']])->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Dashboard - <?php echo htmlspecialchars($tenant['company_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #00BCD4;
            --primary-dark: #0097A7;
        }
        
        body {
            background: #f5f5f5;
        }
        
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }
        
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: white !important;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .stat-card {
            padding: 20px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-card .icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .sidebar {
            background: white;
            min-height: calc(100vh - 56px);
            padding: 20px 0;
        }
        
        .sidebar .nav-link {
            color: #333;
            padding: 12px 20px;
            border-left: 3px solid transparent;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #f0f0f0;
            border-left-color: var(--primary);
            color: var(--primary);
        }
        
        .trial-badge {
            background: #ff9800;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-rocket-takeoff"></i> <?php echo htmlspecialchars($tenant['company_name']); ?>
            </a>
            <?php if ($tenant['status'] === 'trial'): ?>
                <span class="trial-badge">
                    <i class="bi bi-clock"></i> Trial: <?php echo date('d M Y', strtotime($tenant['trial_end_date'])); ?>
                </span>
            <?php endif; ?>
            <div class="ms-auto d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($currentUser['username']); ?>
                </span>
                <a href="../logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link active" href="index.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="customers.php">
                        <i class="bi bi-people"></i> Customers
                    </a>
                    <a class="nav-link" href="packages.php">
                        <i class="bi bi-box"></i> Packages
                    </a>
                    <a class="nav-link" href="transactions.php">
                        <i class="bi bi-credit-card"></i> Transactions
                    </a>
                    <a class="nav-link" href="sessions.php">
                        <i class="bi bi-wifi"></i> Active Sessions
                    </a>
                    <a class="nav-link" href="reports.php">
                        <i class="bi bi-graph-up"></i> Reports
                    </a>
                    <a class="nav-link" href="settings.php">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                </nav>
            </div>

            <div class="col-md-10 p-4">
                <h2 class="mb-4">Dashboard Overview</h2>

                <div class="row">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="icon text-primary">
                                <i class="bi bi-people"></i>
                            </div>
                            <h3><?php echo $totalCustomers; ?></h3>
                            <p class="text-muted mb-0">Total Customers</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="icon text-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <h3><?php echo $activeCustomers; ?></h3>
                            <p class="text-muted mb-0">Active Customers</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="icon text-info">
                                <i class="bi bi-box"></i>
                            </div>
                            <h3><?php echo $totalPackages; ?></h3>
                            <p class="text-muted mb-0">Packages</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="icon text-warning">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <h3>KSh <?php echo number_format($monthlyRevenue, 2); ?></h3>
                            <p class="text-muted mb-0">Monthly Revenue</p>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-people"></i> Recent Customers</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Status</th>
                                                <th>Registered</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($recentCustomers) > 0): ?>
                                                <?php foreach ($recentCustomers as $customer): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                                        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $customer['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                                <?php echo ucfirst($customer['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('d M Y', strtotime($customer['created_at'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">No customers yet</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-lightning"></i> Quick Actions</h5>
                                <div class="d-grid gap-2">
                                    <a href="add-customer.php" class="btn btn-primary">
                                        <i class="bi bi-person-plus"></i> Add New Customer
                                    </a>
                                    <a href="packages.php" class="btn btn-info">
                                        <i class="bi bi-box"></i> Manage Packages
                                    </a>
                                    <a href="transactions.php" class="btn btn-success">
                                        <i class="bi bi-credit-card"></i> View Transactions
                                    </a>
                                    <a href="reports.php" class="btn btn-secondary">
                                        <i class="bi bi-file-earmark-text"></i> Generate Report
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-info-circle"></i> Account Info</h5>
                                <p class="mb-2"><strong>Plan:</strong> <?php echo ucfirst($tenant['subscription_plan']); ?></p>
                                <p class="mb-2"><strong>Status:</strong> 
                                    <span class="badge bg-<?php echo $tenant['status'] === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($tenant['status']); ?>
                                    </span>
                                </p>
                                <?php if ($tenant['status'] === 'trial'): ?>
                                    <p class="mb-0"><strong>Trial Ends:</strong> <?php echo date('d M Y', strtotime($tenant['trial_end_date'])); ?></p>
                                    <a href="settings.php" class="btn btn-sm btn-warning mt-2">
                                        <i class="bi bi-arrow-up-circle"></i> Upgrade Now
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

