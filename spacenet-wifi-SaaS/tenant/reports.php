<?php
// tenant/reports.php - Detailed Reporting for ISPs
session_start();
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || $currentUser['tenant_status'] === 'suspended') {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$tenantId = $currentUser['tenant_id'];

// Get report parameters
$reportType = $_GET['type'] ?? 'revenue';
$dateRange = $_GET['range'] ?? '30';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Generate reports based on type
switch ($reportType) {
    case 'revenue':
        $reportData = $db->query("
            SELECT 
                DATE(t.created_at) as date,
                COUNT(*) as transactions,
                SUM(CASE WHEN t.status = 'completed' THEN t.amount ELSE 0 END) as revenue,
                AVG(CASE WHEN t.status = 'completed' THEN t.amount ELSE NULL END) as avg_transaction
            FROM transactions t
            WHERE t.tenant_id = ? AND DATE(t.created_at) BETWEEN ? AND ?
            GROUP BY DATE(t.created_at)
            ORDER BY date ASC
        ", [$tenantId, $startDate, $endDate])->fetchAll();
        break;
        
    case 'customers':
        $reportData = $db->query("
            SELECT 
                DATE(c.created_at) as date,
                COUNT(*) as new_customers,
                SUM(COUNT(*)) OVER (ORDER BY DATE(c.created_at)) as cumulative_customers
            FROM customers c
            WHERE c.tenant_id = ? AND DATE(c.created_at) BETWEEN ? AND ?
            GROUP BY DATE(c.created_at)
            ORDER BY date ASC
        ", [$tenantId, $startDate, $endDate])->fetchAll();
        break;
        
    case 'packages':
        $reportData = $db->query("
            SELECT 
                tp.name as package_name,
                COUNT(cs.id) as sessions_count,
                SUM(CASE WHEN t.status = 'completed' THEN t.amount ELSE 0 END) as revenue,
                AVG(CASE WHEN t.status = 'completed' THEN t.amount ELSE NULL END) as avg_revenue
            FROM tenant_packages tp
            LEFT JOIN customer_sessions cs ON tp.id = cs.package_id
            LEFT JOIN transactions t ON cs.payment_reference = t.payment_reference
            WHERE tp.tenant_id = ? AND cs.created_at BETWEEN ? AND ?
            GROUP BY tp.id, tp.name
            ORDER BY revenue DESC
        ", [$tenantId, $startDate . ' 00:00:00', $endDate . ' 23:59:59'])->fetchAll();
        break;
        
    case 'usage':
        $reportData = $db->query("
            SELECT 
                DATE(cs.start_time) as date,
                COUNT(*) as total_sessions,
                COUNT(CASE WHEN cs.status = 'active' THEN 1 END) as active_sessions,
                COUNT(CASE WHEN cs.status = 'expired' THEN 1 END) as expired_sessions,
                AVG(cs.duration_minutes) as avg_duration
            FROM customer_sessions cs
            WHERE cs.tenant_id = ? AND DATE(cs.start_time) BETWEEN ? AND ?
            GROUP BY DATE(cs.start_time)
            ORDER BY date ASC
        ", [$tenantId, $startDate, $endDate])->fetchAll();
        break;
}

// Get summary statistics
$summaryStats = $db->query("
    SELECT 
        COUNT(DISTINCT c.id) as total_customers,
        COUNT(DISTINCT cs.id) as total_sessions,
        COALESCE(SUM(CASE WHEN t.status = 'completed' THEN t.amount ELSE 0 END), 0) as total_revenue,
        COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as successful_transactions,
        COUNT(CASE WHEN t.status = 'failed' THEN 1 END) as failed_transactions
    FROM customers c
    LEFT JOIN customer_sessions cs ON c.id = cs.customer_id
    LEFT JOIN transactions t ON cs.payment_reference = t.payment_reference
    WHERE c.tenant_id = ? AND c.created_at BETWEEN ? AND ?
", [$tenantId, $startDate . ' 00:00:00', $endDate . ' 23:59:59'])->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo htmlspecialchars($currentUser['company_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary: #00BCD4; --primary-dark: #0097A7; }
        .report-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); margin-bottom: 20px; }
        .chart-container { position: relative; height: 400px; }
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
                <a class="nav-link text-white active" href="reports.php">Reports</a>
                <a class="nav-link text-white" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Detailed Reports</h2>
            <button class="btn btn-outline-primary" onclick="exportReport()">
                <i class="fas fa-download me-2"></i>Export CSV
            </button>
        </div>

        <!-- Report Filters -->
        <div class="report-card p-4 mb-4">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Report Type</label>
                    <select name="type" class="form-select">
                        <option value="revenue" <?php echo $reportType === 'revenue' ? 'selected' : ''; ?>>Revenue</option>
                        <option value="customers" <?php echo $reportType === 'customers' ? 'selected' : ''; ?>>Customers</option>
                        <option value="packages" <?php echo $reportType === 'packages' ? 'selected' : ''; ?>>Packages</option>
                        <option value="usage" <?php echo $reportType === 'usage' ? 'selected' : ''; ?>>Usage</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Generate Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="report-card p-4 text-center">
                    <i class="fas fa-users text-primary mb-2" style="font-size: 2.5rem;"></i>
                    <div class="h3 text-primary"><?php echo number_format($summaryStats['total_customers']); ?></div>
                    <div class="text-muted">Total Customers</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="report-card p-4 text-center">
                    <i class="fas fa-wifi text-success mb-2" style="font-size: 2.5rem;"></i>
                    <div class="h3 text-success"><?php echo number_format($summaryStats['total_sessions']); ?></div>
                    <div class="text-muted">Total Sessions</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="report-card p-4 text-center">
                    <i class="fas fa-money-bill-wave text-warning mb-2" style="font-size: 2.5rem;"></i>
                    <div class="h3 text-warning">Ksh <?php echo number_format($summaryStats['total_revenue']); ?></div>
                    <div class="text-muted">Total Revenue</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="report-card p-4 text-center">
                    <i class="fas fa-percentage text-info mb-2" style="font-size: 2.5rem;"></i>
                    <div class="h3 text-info">
                        <?php 
                        $totalTrans = $summaryStats['successful_transactions'] + $summaryStats['failed_transactions'];
                        $successRate = $totalTrans > 0 ? ($summaryStats['successful_transactions'] / $totalTrans) * 100 : 0;
                        echo number_format($successRate, 1); 
                        ?>%
                    </div>
                    <div class="text-muted">Success Rate</div>
                </div>
            </div>
        </div>

        <!-- Report Chart and Data -->
        <div class="row">
            <div class="col-lg-8">
                <div class="report-card p-4">
                    <h5><?php echo ucfirst($reportType); ?> Report</h5>
                    <div class="chart-container">
                        <canvas id="reportChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="report-card p-4">
                    <h5>Report Data</h5>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <?php if ($reportType === 'revenue'): ?>
                                    <tr><th>Date</th><th>Transactions</th><th>Revenue</th></tr>
                                <?php elseif ($reportType === 'customers'): ?>
                                    <tr><th>Date</th><th>New</th><th>Total</th></tr>
                                <?php elseif ($reportType === 'packages'): ?>
                                    <tr><th>Package</th><th>Sessions</th><th>Revenue</th></tr>
                                <?php elseif ($reportType === 'usage'): ?>
                                    <tr><th>Date</th><th>Sessions</th><th>Avg Duration</th></tr>
                                <?php endif; ?>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $row): ?>
                                    <tr>
                                        <?php if ($reportType === 'revenue'): ?>
                                            <td><?php echo date('M j', strtotime($row['date'])); ?></td>
                                            <td><?php echo $row['transactions']; ?></td>
                                            <td>Ksh <?php echo number_format($row['revenue']); ?></td>
                                        <?php elseif ($reportType === 'customers'): ?>
                                            <td><?php echo date('M j', strtotime($row['date'])); ?></td>
                                            <td><?php echo $row['new_customers']; ?></td>
                                            <td><?php echo $row['cumulative_customers']; ?></td>
                                        <?php elseif ($reportType === 'packages'): ?>
                                            <td><?php echo htmlspecialchars($row['package_name']); ?></td>
                                            <td><?php echo $row['sessions_count']; ?></td>
                                            <td>Ksh <?php echo number_format($row['revenue']); ?></td>
                                        <?php elseif ($reportType === 'usage'): ?>
                                            <td><?php echo date('M j', strtotime($row['date'])); ?></td>
                                            <td><?php echo $row['total_sessions']; ?></td>
                                            <td><?php echo number_format($row['avg_duration'], 1); ?>min</td>
                                        <?php endif; ?>
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
        // Dynamic chart based on report type
        const ctx = document.getElementById('reportChart').getContext('2d');
        const reportData = <?php echo json_encode($reportData); ?>;
        const reportType = '<?php echo $reportType; ?>';
        
        let chartConfig = {
            type: 'line',
            data: { datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        };
        
        switch (reportType) {
            case 'revenue':
                chartConfig.data = {
                    labels: reportData.map(row => new Date(row.date).toLocaleDateString()),
                    datasets: [{
                        label: 'Revenue (KES)',
                        data: reportData.map(row => row.revenue),
                        borderColor: '#00BCD4',
                        backgroundColor: 'rgba(0, 188, 212, 0.1)',
                        tension: 0.4
                    }]
                };
                break;
                
            case 'customers':
                chartConfig.data = {
                    labels: reportData.map(row => new Date(row.date).toLocaleDateString()),
                    datasets: [{
                        label: 'New Customers',
                        data: reportData.map(row => row.new_customers),
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)'
                    }, {
                        label: 'Cumulative',
                        data: reportData.map(row => row.cumulative_customers),
                        borderColor: '#2196F3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)'
                    }]
                };
                break;
                
            case 'packages':
                chartConfig.type = 'bar';
                chartConfig.data = {
                    labels: reportData.map(row => row.package_name),
                    datasets: [{
                        label: 'Revenue (KES)',
                        data: reportData.map(row => row.revenue),
                        backgroundColor: '#00BCD4'
                    }]
                };
                break;
                
            case 'usage':
                chartConfig.data = {
                    labels: reportData.map(row => new Date(row.date).toLocaleDateString()),
                    datasets: [{
                        label: 'Total Sessions',
                        data: reportData.map(row => row.total_sessions),
                        borderColor: '#FF9800',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)'
                    }]
                };
                break;
        }
        
        new Chart(ctx, chartConfig);
        
        function exportReport() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.open('export-report.php?' + params.toString());
        }
    </script>
</body>
</html>text-danger"><?php echo $stats['open_tickets']; ?></div>
                    <small class="text-muted">Open</small>
                </div>
                <div class="text-center">
                    <div class="h4 text-warning"><?php echo $stats['responded_tickets']; ?></div>
                    <small class="text-muted">Responded</small>
                </div>
                <div class="text-center">
                    <div class="h4 <?php
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
$monthlyRevenue = $db->query("SELECT COALESCE(SUM(amount), 0) as revenue FROM transactions WHERE status = 'completed'")->fetch()['revenue'];