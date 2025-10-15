<?php
// tenant/sessions.php - Active Sessions Management
session_start();
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Session.php';

$auth = new Auth();
$auth->requireLogin();
$currentUser = $auth->getCurrentUser();

$session = new Session();
$activeSessions = $session->getActiveSessions($currentUser['tenant_id']);
$db = Database::getInstance();

// Get session statistics
$stats = $db->query("
    SELECT 
        COUNT(CASE WHEN status = 'active' AND end_time > NOW() THEN 1 END) as active_count,
        COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_count,
        COUNT(CASE WHEN status = 'terminated' THEN 1 END) as terminated_count,
        AVG(duration_minutes) as avg_duration
    FROM customer_sessions 
    WHERE tenant_id = ? AND DATE(created_at) = CURDATE()
", [$currentUser['tenant_id']])->fetch();

// Handle session actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $sessionId = $_POST['session_id'];
    
    try {
        if ($action === 'terminate') {
            $db->query("UPDATE customer_sessions SET status = 'terminated' WHERE id = ? AND tenant_id = ?", 
                      [$sessionId, $currentUser['tenant_id']]);
            $message = "Session terminated successfully.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
    
    // Refresh sessions
    $activeSessions = $session->getActiveSessions($currentUser['tenant_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Sessions - <?php echo htmlspecialchars($currentUser['company_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00BCD4; --primary-dark: #0097A7; }
        .session-active { border-left: 4px solid #28a745; }
        .session-expiring { border-left: 4px solid #ffc107; }
        .metric-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); padding: 20px; text-align: center; }
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
                <a class="nav-link text-white active" href="sessions.php">Sessions</a>
                <a class="nav-link text-white" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Active Sessions</h2>
            <div class="text-muted">
                <i class="fas fa-sync-alt me-1"></i>Auto-refreshes every 30 seconds
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Session Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card">
                    <i class="fas fa-globe text-success mb-2" style="font-size: 2rem;"></i>
                    <div class="h3 text-success"><?php echo $stats['active_count']; ?></div>
                    <div class="text-muted">Active Now</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card">
                    <i class="fas fa-clock text-muted mb-2" style="font-size: 2rem;"></i>
                    <div class="h3 text-muted"><?php echo $stats['expired_count']; ?></div>
                    <div class="text-muted">Expired Today</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card">
                    <i class="fas fa-stop-circle text-danger mb-2" style="font-size: 2rem;"></i>
                    <div class="h3 text-danger"><?php echo $stats['terminated_count']; ?></div>
                    <div class="text-muted">Terminated Today</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card">
                    <i class="fas fa-hourglass-half text-info mb-2" style="font-size: 2rem;"></i>
                    <div class="h3 text-info"><?php echo number_format($stats['avg_duration'] ?? 0, 0); ?>m</div>
                    <div class="text-muted">Avg Duration</div>
                </div>
            </div>
        </div>

        <!-- Active Sessions List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Current Active Sessions</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($activeSessions)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-wifi-slash mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
                        <h5 class="text-muted">No active sessions</h5>
                        <p class="text-muted">Customer sessions will appear here when they connect</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background-color: var(--primary); color: white;">
                                <tr>
                                    <th>Customer</th>
                                    <th>Package</th>
                                    <th>Started</th>
                                    <th>Remaining</th>
                                    <th>Speed</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeSessions as $sessionData): ?>
                                    <?php
                                    $remaining = strtotime($sessionData['end_time']) - time();
                                    $isExpiring = $remaining < 1800; // Less than 30 minutes
                                    ?>
                                    <tr class="<?php echo $isExpiring ? 'session-expiring' : 'session-active'; ?>">
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($sessionData['username']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($sessionData['email'] ?: 'No email'); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($sessionData['package_name']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo date('g:i A', strtotime($sessionData['start_time'])); ?><br>
                                            <small class="text-muted"><?php echo date('M j', strtotime($sessionData['start_time'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($remaining > 0): ?>
                                                <?php 
                                                $hours = floor($remaining / 3600);
                                                $minutes = floor(($remaining % 3600) / 60);
                                                ?>
                                                <strong class="<?php echo $isExpiring ? 'text-warning' : 'text-success'; ?>">
                                                    <?php echo $hours . 'h ' . $minutes . 'm'; ?>
                                                </strong>
                                            <?php else: ?>
                                                <span class="text-danger">Expired</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $sessionData['speed_limit']; ?>Mbps</span>
                                        </td>
                                        <td>
                                            <?php if ($remaining > 0): ?>
                                                <span class="text-success">
                                                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Active
                                                </span>
                                            <?php else: ?>
                                                <span class="text-danger">
                                                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Expired
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($remaining > 0): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="terminate">
                                                    <input type="hidden" name="session_id" value="<?php echo $sessionData['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('Terminate this session?')">
                                                        <i class="fas fa-stop me-1"></i>Terminate
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh page every 30 seconds
        setTimeout(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>