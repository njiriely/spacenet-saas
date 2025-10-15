<?php
// admin/support.php - Support Center Management
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

// Handle ticket status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $ticketId = $_POST['ticket_id'];
    $action = $_POST['action'];
    
    if ($action === 'close') {
        $db->query("UPDATE support_tickets SET status = 'closed', updated_at = NOW() WHERE id = ?", [$ticketId]);
    } elseif ($action === 'respond') {
        $response = $_POST['response'];
        $db->query("UPDATE support_tickets SET status = 'responded', admin_response = ?, updated_at = NOW() WHERE id = ?", [$response, $ticketId]);
    }
}

// Get support tickets
$tickets = $db->query("
    SELECT st.*, t.company_name, tu.email as user_email
    FROM support_tickets st
    LEFT JOIN tenants t ON st.tenant_id = t.id
    LEFT JOIN tenant_users tu ON st.user_id = tu.id
    ORDER BY st.created_at DESC
    LIMIT 50
")->fetchAll();

// Get ticket stats
$stats = $db->query("
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
        SUM(CASE WHEN status = 'responded' THEN 1 ELSE 0 END) as responded_tickets,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets
    FROM support_tickets
")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Center - SPACE NET Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00BCD4; --primary-dark: #0097A7; }
        .ticket-card { border-left: 4px solid #00BCD4; margin-bottom: 15px; }
        .ticket-open { border-left-color: #dc3545; }
        .ticket-responded { border-left-color: #ffc107; }
        .ticket-closed { border-left-color: #28a745; }
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
                <a class="nav-link text-white active" href="support.php">Support</a>
                <a class="nav-link text-white" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Support Center</h2>
            <div class="d-flex gap-3">
                <div class="text-center">
                    <div class="h4 text-success"><?php echo $stats['closed_tickets']; ?></div>
                    <small class="text-muted">Closed</small>
                </div>
            </div>
        </div>

        <!-- Support Tickets -->
        <div class="row">
            <?php foreach ($tickets as $ticket): ?>
                <div class="col-lg-6 mb-3">
                    <div class="card ticket-card ticket-<?php echo $ticket['status']; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($ticket['subject']); ?></h6>
                                <span class="badge bg-<?php 
                                    echo $ticket['status'] === 'open' ? 'danger' : 
                                        ($ticket['status'] === 'responded' ? 'warning' : 'success'); 
                                ?>">
                                    <?php echo ucfirst($ticket['status']); ?>
                                </span>
                            </div>
                            <p class="card-text small text-muted mb-2">
                                <strong><?php echo htmlspecialchars($ticket['company_name']); ?></strong> • 
                                <?php echo htmlspecialchars($ticket['user_email']); ?>
                            </p>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($ticket['message'], 0, 150))); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?>
                                </small>
                                <div>
                                    <?php if ($ticket['status'] === 'open'): ?>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                                data-bs-target="#respondModal" data-ticket-id="<?php echo $ticket['id']; ?>"
                                                data-ticket-subject="<?php echo htmlspecialchars($ticket['subject']); ?>">
                                            <i class="fas fa-reply me-1"></i>Respond
                                        </button>
                                    <?php endif; ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                        <input type="hidden" name="action" value="close">
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-check me-1"></i>Close
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Response Modal -->
    <div class="modal fade" id="respondModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Respond to Ticket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="ticket_id" id="responseTicketId">
                        <input type="hidden" name="action" value="respond">
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" id="responseSubject" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Response</label>
                            <textarea name="response" class="form-control" rows="5" required 
                                      placeholder="Enter your response to the customer..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Response</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('respondModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const ticketId = button.getAttribute('data-ticket-id');
            const subject = button.getAttribute('data-ticket-subject');
            
            document.getElementById('responseTicketId').value = ticketId;
            document.getElementById('responseSubject').value = subject;
        });
    </script>
</body>
</html>