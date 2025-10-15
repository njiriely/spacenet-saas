<?php
// admin/analytics.php - Advanced Analytics Dashboard
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

// Get analytics data
$totalTenants = $db->query("SELECT COUNT(*) as count FROM tenants")->fetch()['count'];
$activeTenants = $db->query("SELECT COUNT(*) as count FROM tenants WHERE status = 'active'")->fetch()['count'];
$trialTenants = $db->query("SELECT COUNT(*) as count FROM tenants WHERE status = 'trial'")->fetch()['count'];
$totalCustomers = $db->query("SELECT COUNT(*) as count FROM customers")->fetch()['count'];
$totalRevenue = $db->query("SELECT COALESCE(SUM(amount), 0) as revenue FROM transactions WHERE status = 'completed'")->fetch()['revenue'];
$monthlyRevenue = $db->query("SELECT COALESCE(SUM(amount), 0) as revenue FROM transactions WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch()['revenue'];

// Get growth metrics
$growthData = $db->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as new_tenants,
        SUM(COUNT(*)) OVER (ORDER BY DATE(created_at)) as cumulative_tenants
    FROM tenants 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll();

// Get revenue by plan
$planRevenue = $db->query("
    SELECT 
        t.subscription_plan,
        COUNT(*) as tenant_count,
        COALESCE(SUM(tr.amount), 0) as total_revenue
    FROM tenants t
    LEFT JOIN transactions tr ON t.id = tr.tenant_id AND tr.status = 'completed'
    GROUP BY t.subscription_plan
")->fetchAll();

// Get top performing ISPs
$topISPs = $db->query("
    SELECT 
        t.company_name,
        t.subscription_plan,
        COUNT(DISTINCT c.id) as customer_count,
        COALESCE(SUM(tr.amount), 0) as total_revenue,
        COUNT(DISTINCT cs.id) as total_sessions
    FROM tenants t
    LEFT JOIN customers c ON t.id = c.tenant_id
    LEFT JOIN transactions tr ON t.id = tr.tenant_id AND tr.status = 'completed'
    LEFT JOIN customer_sessions cs ON t.id = cs.tenant_id
    WHERE t.status IN ('active', 'trial')
    GROUP BY t.id
    ORDER BY total_revenue DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - SPACE NET Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #00BCD4;
            --primary-dark: #0097A7;
        }
        
        .analytics-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        
        .analytics-card:hover {
            transform: translateY(-2px);
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark));">
        <div class="container">
            <a class="navbar-brand text-white fw-bold" href="index.php">
                <i class="fas fa-satellite me-2"></i>SPACE NET Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-white" href="index.php">Dashboard</a>
                <a class="nav-link text-white active" href="analytics.php">Analytics</a>
                <a class="nav-link text-white" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <h2 class="mb-4">Platform Analytics</h2>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-6">
                <div class="analytics-card p-3 text-center">
                    <i class="fas fa-building text-primary mb-2" style="font-size: 2rem;"></i>
                    <div class="metric-value"><?php echo number_format($totalTenants); ?></div>
                    <div class="text-muted small">Total ISPs</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="analytics-card p-3 text-center">
                    <i class="fas fa-check-circle text-success mb-2" style="font-size: 2rem;"></i>
                    <div class="metric-value"><?php echo number_format($activeTenants); ?></div>
                    <div class="text-muted small">Active ISPs</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="analytics-card p-3 text-center">
                    <i class="fas fa-clock text-warning mb-2" style="font-size: 2rem;"></i>
                    <div class="metric-value"><?php echo number_format($trialTenants); ?></div>
                    <div class="text-muted small">Trial ISPs</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="analytics-card p-3 text-center">
                    <i class="fas fa-users text-info mb-2" style="font-size: 2rem;"></i>
                    <div class="metric-value"><?php echo number_format($totalCustomers); ?></div>
                    <div class="text-muted small">End Users</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="analytics-card p-3 text-center">
                    <i class="fas fa-money-bill-wave text-success mb-2" style="font-size: 2rem;"></i>
                    <div class="metric-value">Ksh <?php echo number_format($totalRevenue); ?></div>
                    <div class="text-muted small">Total Revenue</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="analytics-card p-3 text-center">
                    <i class="fas fa-chart-line text-primary mb-2" style="font-size: 2rem;"></i>
                    <div class="metric-value">Ksh <?php echo number_format($monthlyRevenue); ?></div>
                    <div class="text-muted small">30-Day Revenue</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Growth Chart -->
            <div class="col-lg-8">
                <div class="analytics-card p-4">
                    <h5>ISP Growth (Last 30 Days)</h5>
                    <div class="chart-container">
                        <canvas id="growthChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Plan Revenue -->
            <div class="col-lg-4">
                <div class="analytics-card p-4">
                    <h5>Revenue by Plan</h5>
                    <div class="chart-container">
                        <canvas id="planChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performing ISPs -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="analytics-card p-4">
                    <h5>Top Performing ISPs</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ISP Name</th>
                                    <th>Plan</th>
                                    <th>Customers</th>
                                    <th>Sessions</th>
                                    <th>Revenue</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topISPs as $isp): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($isp['company_name']); ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $isp['subscription_plan'] === 'enterprise' ? 'success' : 
                                                    ($isp['subscription_plan'] === 'professional' ? 'warning' : 'primary'); 
                                            ?>">
                                                <?php echo ucfirst($isp['subscription_plan']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($isp['customer_count']); ?></td>
                                        <td><?php echo number_format($isp['total_sessions']); ?></td>
                                        <td>Ksh <?php echo number_format($isp['total_revenue']); ?></td>
                                        <td>
                                            <?php 
                                            $performance = $isp['customer_count'] > 0 ? ($isp['total_sessions'] / $isp['customer_count']) : 0;
                                            $performanceClass = $performance > 5 ? 'success' : ($performance > 2 ? 'warning' : 'danger');
                                            ?>
                                            <span class="badge bg-<?php echo $performanceClass; ?>">
                                                <?php echo number_format($performance, 1); ?> sessions/customer
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Growth Chart
        const growthCtx = document.getElementById('growthChart').getContext('2d');
        const growthChart = new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($growthData, 'date')); ?>,
                datasets: [{
                    label: 'New ISPs',
                    data: <?php echo json_encode(array_column($growthData, 'new_tenants')); ?>,
                    borderColor: '#00BCD4',
                    backgroundColor: 'rgba(0, 188, 212, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Cumulative ISPs',
                    data: <?php echo json_encode(array_column($growthData, 'cumulative_tenants')); ?>,
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Plan Revenue Chart
        const planCtx = document.getElementById('planChart').getContext('2d');
        const planChart = new Chart(planCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map('ucfirst', array_column($planRevenue, 'subscription_plan'))); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($planRevenue, 'total_revenue')); ?>,
                    backgroundColor: ['#00BCD4', '#4CAF50', '#FFC107']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>