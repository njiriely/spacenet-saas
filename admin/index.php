<?php
// admin/index.php - Admin Dashboard
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/DatabaseConfig.php';

$auth = new SpaceNet\Auth();
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - SPACE NET SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .dashboard-container {
            padding: 30px 0;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card {
            padding: 25px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-card p {
            color: #6c757d;
            margin: 0;
        }
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-rocket-takeoff"></i> SPACE NET SaaS
            </a>
            <div class="ms-auto">
                <span class="me-3">Welcome, <?php echo htmlspecialchars($currentUser['name']); ?></span>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container dashboard-container">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2><i class="bi bi-speedometer2"></i> Super Admin Dashboard</h2>
                        <p class="text-muted">Welcome to the SpaceNet SaaS Super Admin Control Panel</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="card stat-card">
                    <i class="bi bi-building text-primary" style="font-size: 3rem;"></i>
                    <h3 id="total-tenants">0</h3>
                    <p>Total Tenants</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                    <h3 id="active-tenants">0</h3>
                    <p>Active Tenants</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <i class="bi bi-people text-info" style="font-size: 3rem;"></i>
                    <h3 id="total-customers">0</h3>
                    <p>Total Customers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <i class="bi bi-currency-dollar text-warning" style="font-size: 3rem;"></i>
                    <h3 id="total-revenue">$0</h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-list-ul"></i> Recent Tenants</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-tenants">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No tenants yet</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-graph-up"></i> Quick Actions</h5>
                        <div class="d-grid gap-2">
                            <a href="tenants.php" class="btn btn-primary">
                                <i class="bi bi-building"></i> Manage Tenants
                            </a>
                            <a href="settings.php" class="btn btn-secondary">
                                <i class="bi bi-gear"></i> System Settings
                            </a>
                            <a href="reports.php" class="btn btn-info">
                                <i class="bi bi-file-earmark-text"></i> View Reports
                            </a>
                            <a href="../register.php" class="btn btn-success">
                                <i class="bi bi-plus-circle"></i> Add New Tenant
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load dashboard statistics
        async function loadStats() {
            try {
                const response = await fetch('api/dashboard-stats.php');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('total-tenants').textContent = data.stats.total_tenants || 0;
                    document.getElementById('active-tenants').textContent = data.stats.active_tenants || 0;
                    document.getElementById('total-customers').textContent = data.stats.total_customers || 0;
                    document.getElementById('total-revenue').textContent = '$' + (data.stats.total_revenue || 0);
                    
                    // Load recent tenants
                    if (data.recent_tenants && data.recent_tenants.length > 0) {
                        const tbody = document.getElementById('recent-tenants');
                        tbody.innerHTML = data.recent_tenants.map(tenant => `
                            <tr>
                                <td>${tenant.company_name}</td>
                                <td><span class="badge bg-${tenant.status === 'active' ? 'success' : 'warning'}">${tenant.status}</span></td>
                                <td>${new Date(tenant.created_at).toLocaleDateString()}</td>
                            </tr>
                        `).join('');
                    }
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }
        
        // Load stats on page load
        loadStats();
    </script>
</body>
</html>

