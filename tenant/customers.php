<?php
// tenant/customers.php - Customer Management
session_start();
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/User.php';

$auth = new Auth();
$auth->requireLogin();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || $currentUser['tenant_status'] === 'suspended') {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$user = new User();
$message = '';
$messageType = '';

// Handle customer actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $customerId = $_POST['customer_id'] ?? null;
    
    try {
        switch ($action) {
            case 'suspend':
                $user->updateCustomerStatus($customerId, 'suspended', $currentUser['tenant_id']);
                $message = "Customer suspended successfully.";
                $messageType = "success";
                break;
            case 'activate':
                $user->updateCustomerStatus($customerId, 'active', $currentUser['tenant_id']);
                $message = "Customer activated successfully.";
                $messageType = "success";
                break;
            case 'delete':
                // This would require more complex logic to handle sessions and transactions
                $message = "Customer deletion requires manual database cleanup.";
                $messageType = "warning";
                break;
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Get customers with pagination
$page = $_GET['page'] ?? 1;
$limit = 25;
$offset = ($page - 1) * $limit;

$customers = $user->getTenantCustomers($currentUser['tenant_id'], $limit, $offset);
$totalCustomers = $db->query("SELECT COUNT(*) as count FROM customers WHERE tenant_id = ?", [$currentUser['tenant_id']])->fetch()['count'];
$totalPages = ceil($totalCustomers / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - <?php echo htmlspecialchars($currentUser['company_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00BCD4; --primary-dark: #0097A7; }
        .customer-card { border-left: 4px solid var(--primary); margin-bottom: 15px; }
        .status-active { color: #28a745; }
        .status-suspended { color: #dc3545; }
        .status-expired { color: #6c757d; }
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
                <a class="nav-link text-white active" href="customers.php">Customers</a>
                <a class="nav-link text-white" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Customer Management</h2>
            <div class="d-flex gap-2">
                <a href="add-customer.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Customer
                </a>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="fas fa-upload me-2"></i>Import CSV
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Customers (<?php echo number_format($totalCustomers); ?>)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($customers)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
                        <h5 class="text-muted">No customers yet</h5>
                        <p class="text-muted">Add your first customer to get started</p>
                        <a href="add-customer.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Customer
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background-color: var(--primary); color: white;">
                                <tr>
                                    <th>Customer</th>
                                    <th>Contact</th>
                                    <th>Sessions</th>
                                    <th>Last Active</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($customer['username']); ?></strong><br>
                                                <small class="text-muted">ID: <?php echo $customer['id']; ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <?php if ($customer['email']): ?>
                                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($customer['email']); ?><br>
                                                <?php endif; ?>
                                                <?php if ($customer['phone']): ?>
                                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($customer['phone']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo number_format($customer['total_sessions']); ?></strong><br>
                                            <small class="text-muted">total sessions</small>
                                        </td>
                                        <td>
                                            <?php if ($customer['last_session']): ?>
                                                <?php echo date('M j, Y g:i A', strtotime($customer['last_session'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Never</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-<?php echo $customer['status']; ?>">
                                                <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                                <?php echo ucfirst($customer['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="customer-details.php?id=<?php echo $customer['id']; ?>">
                                                        <i class="fas fa-eye me-2"></i>View Details
                                                    </a></li>
                                                    <?php if ($customer['status'] === 'active'): ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="suspend">
                                                                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                                <button type="submit" class="dropdown-item text-warning" 
                                                                        onclick="return confirm('Suspend this customer?')">
                                                                    <i class="fas fa-pause me-2"></i>Suspend
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php else: ?>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="activate">
                                                                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                                <button type="submit" class="dropdown-item text-success">
                                                                    <i class="fas fa-play me-2"></i>Activate
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
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
                                    <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
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
</body>
</html>
