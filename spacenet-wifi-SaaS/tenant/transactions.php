<?php
// tenant/transactions.php - Transaction History
session_start();
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();
$currentUser = $auth->getCurrentUser();

$db = Database::getInstance();

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 25;
$offset = ($page - 1) * $limit;

// Get transactions
$transactions = $db->query("
    SELECT t.*, c.username, c.email 
    FROM transactions t 
    LEFT JOIN customers c ON t.customer_id = c.id 
    WHERE t.tenant_id = ? 
    ORDER BY t.created_at DESC 
    LIMIT ? OFFSET ?
", [$currentUser['tenant_id'], $limit, $offset])->fetchAll();

// Get total count
$totalCount = $db->query("SELECT COUNT(*) as count FROM transactions WHERE tenant_id = ?", [$currentUser['tenant_id']])->fetch()['count'];
$totalPages = ceil($totalCount / $limit);

// Get summary stats
$stats = $db->query("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count
    FROM transactions 
    WHERE tenant_id = ? AND DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)
", [$currentUser['tenant_id']])->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - <?php echo htmlspecialchars($currentUser['company_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00BCD4; --primary-dark: #0097A7; }
        .stats-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); padding: 20px; text-align: center; }
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
                <a class="nav-link text-white active" href="transactions.php">Transactions</a>
                <a class="nav-link text-white" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Transaction History</h2>
            <button class="btn btn-outline-primary" onclick="exportTransactions()">
                <i class="fas fa-download me-2"></i>Export CSV
            </button>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <i class="fas fa-receipt text-primary mb-2" style="font-size: 2rem;"></i>
                    <div class="h4 text-primary"><?php echo number_format($stats['total_transactions']); ?></div>
                    <div class="text-muted">Total (30 days)</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <i class="fas fa-money-bill-wave text-success mb-2" style="font-size: 2rem;"></i>
                    <div class="h4 text-success">Ksh <?php echo number_format($stats['total_revenue']); ?></div>
                    <div class="text-muted">Revenue (30 days)</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <i class="fas fa-check-circle text-info mb-2" style="font-size: 2rem;"></i>
                    <div class="h4 text-info"><?php echo number_format($stats['completed_count']); ?></div>
                    <div class="text-muted">Successful</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <i class="fas fa-percentage text-warning mb-2" style="font-size: 2rem;"></i>
                    <div class="h4 text-warning">
                        <?php 
                        $successRate = $stats['total_transactions'] > 0 ? ($stats['completed_count'] / $stats['total_transactions']) * 100 : 0;
                        echo number_format($successRate, 1); 
                        ?>%
                    </div>
                    <div class="text-muted">Success Rate</div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Transactions (<?php echo number_format($totalCount); ?>)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($transactions)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-receipt mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
                        <h5 class="text-muted">No transactions yet</h5>
                        <p class="text-muted">Customer transactions will appear here</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background-color: var(--primary); color: white;">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Reference</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo date('M j, Y', strtotime($transaction['created_at'])); ?></strong><br>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($transaction['created_at'])); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($transaction['username']): ?>
                                                <strong><?php echo htmlspecialchars($transaction['username']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($transaction['email'] ?: 'No email'); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Subscription Payment</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong>Ksh <?php echo number_format($transaction['amount'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $transaction['payment_method'] === 'mpesa' ? 'success' : 
                                                    ($transaction['payment_method'] === 'pesapal' ? 'info' : 'secondary'); 
                                            ?>">
                                                <?php echo strtoupper($transaction['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($transaction['payment_reference']); ?></code>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $transaction['status'] === 'completed' ? 'success' : 
                                                    ($transaction['status'] === 'pending' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($transaction['status']); ?>
                                            </span>
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
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === (int)$page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportTransactions() {
            window.open('export-transactions.php?format=csv', '_blank');
        }
    </script>
</body>
</html>
