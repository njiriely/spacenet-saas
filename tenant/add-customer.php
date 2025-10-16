<?php
// tenant/add-customer.php - Quick Customer Creation
session_start();
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/User.php';

$auth = new Auth();
$auth->requireLogin();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || $currentUser['tenant_status'] === 'suspended') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$messageType = '';

// Handle customer creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user = new User();
        
        $customerData = [
            'username' => $_POST['username'],
            'email' => $_POST['email'] ?: null,
            'phone' => $_POST['phone'] ?: null,
            'mac_address' => $_POST['mac_address'] ?: null,
            'device_info' => [
                'device_type' => $_POST['device_type'] ?: null,
                'device_name' => $_POST['device_name'] ?: null
            ]
        ];
        
        $customerId = $user->createCustomer($currentUser['tenant_id'], $customerData);
        
        if ($customerId) {
            $message = "Customer created successfully! Customer ID: {$customerId}";
            $messageType = "success";
            
            // Clear form
            $_POST = [];
        } else {
            $message = "Failed to create customer. Please try again.";
            $messageType = "danger";
        }
        
    } catch (Exception $e) {
        $message = "Error creating customer: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer - <?php echo htmlspecialchars($currentUser['company_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00BCD4; --primary-dark: #0097A7; }
        .form-section { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); padding: 25px; margin-bottom: 20px; }
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
                <a class="nav-link text-white" href="customers.php">Customers</a>
                <a class="nav-link text-white active" href="add-customer.php">Add Customer</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Add New Customer</h2>
            <a href="customers.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Customers
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="form-section">
                    <h5 class="mb-4"><i class="fas fa-user-plus me-2 text-primary"></i>Customer Information</h5>
                    
                    <form method="POST" id="customerForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                           placeholder="Enter unique username" required>
                                    <small class="text-muted">This will be used for login</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                           placeholder="customer@email.com">
                                    <small class="text-muted">Optional - for notifications</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                           placeholder="+254700123456">
                                    <small class="text-muted">For M-Pesa payments and SMS</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">MAC Address</label>
                                    <input type="text" name="mac_address" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['mac_address'] ?? ''); ?>"
                                           placeholder="AA:BB:CC:DD:EE:FF" pattern="^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$">
                                    <small class="text-muted">Optional - for device binding</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Device Type</label>
                                    <select name="device_type" class="form-select">
                                        <option value="">Select Device Type</option>
                                        <option value="smartphone">Smartphone</option>
                                        <option value="laptop">Laptop</option>
                                        <option value="tablet">Tablet</option>
                                        <option value="desktop">Desktop Computer</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Device Name</label>
                                    <input type="text" name="device_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['device_name'] ?? ''); ?>"
                                           placeholder="e.g., John's iPhone, Office Laptop">
                                    <small class="text-muted">Optional - for identification</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" onclick="clearForm()">
                                <i class="fas fa-eraser me-2"></i>Clear Form
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Create Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Quick Tips -->
                <div class="form-section">
                    <h5 class="mb-4"><i class="fas fa-lightbulb me-2 text-warning"></i>Quick Tips</h5>
                    <div class="small">
                        <div class="mb-3">
                            <strong>Username Guidelines:</strong>
                            <ul class="mb-0 mt-1">
                                <li>Must be unique for your ISP</li>
                                <li>Use letters, numbers, underscore</li>
                                <li>No spaces or special characters</li>
                                <li>Keep it simple and memorable</li>
                            </ul>
                        </div>
                        <div class="mb-3">
                            <strong>MAC Address:</strong>
                            <ul class="mb-0 mt-1">
                                <li>Format: AA:BB:CC:DD:EE:FF</li>
                                <li>Used for device binding</li>
                                <li>Prevents account sharing</li>
                                <li>Can be added later</li>
                            </ul>
                        </div>
                        <div class="mb-3">
                            <strong>Contact Information:</strong>
                            <ul class="mb-0 mt-1">
                                <li>Email for receipts and notifications</li>
                                <li>Phone for M-Pesa payments</li>
                                <li>Both are optional but recommended</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Bulk Import -->
                <div class="form-section">
                    <h5 class="mb-4"><i class="fas fa-upload me-2 text-info"></i>Bulk Import</h5>
                    <p class="small text-muted">Need to add many customers at once?</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
                            <i class="fas fa-file-csv me-2"></i>Import CSV File
                        </button>
                        <a href="download-template.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-download me-2"></i>Download Template
                        </a>
                    </div>
                </div>

                <!-- Recent Customers -->
                <div class="form-section">
                    <h5 class="mb-4"><i class="fas fa-history me-2 text-success"></i>Recent Customers</h5>
                    <?php
                    $db = Database::getInstance();
                    $recentCustomers = $db->query("
                        SELECT username, email, created_at 
                        FROM customers 
                        WHERE tenant_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 5
                    ", [$currentUser['tenant_id']])->fetchAll();
                    ?>
                    <?php if (empty($recentCustomers)): ?>
                        <p class="text-muted small">No customers yet. This will be your first!</p>
                    <?php else: ?>
                        <?php foreach ($recentCustomers as $customer): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <div>
                                    <strong><?php echo htmlspecialchars($customer['username']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($customer['email'] ?: 'No email'); ?></small>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M j', strtotime($customer['created_at'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Import Modal -->
    <div class="modal fade" id="bulkImportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Import Customers</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form enctype="multipart/form-data" id="bulkImportForm">
                        <div class="mb-3">
                            <label class="form-label">CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                            <small class="text-muted">Upload a CSV file with customer data</small>
                        </div>
                        <div class="alert alert-info">
                            <strong>CSV Format:</strong><br>
                            <code>username,email,phone,mac_address,device_type,device_name</code><br>
                            <small>First row should be headers as shown above</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="bulkImportForm" class="btn btn-primary">Import Customers</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function clearForm() {
            if (confirm('Are you sure you want to clear all form data?')) {
                document.getElementById('customerForm').reset();
            }
        }

        // Auto-format MAC address
        document.querySelector('input[name="mac_address"]').addEventListener('input', function() {
            let value = this.value.replace(/[^A-Fa-f0-9]/g, '');
            let formatted = value.replace(/(.{2})(?=.)/g, '$1:');
            if (formatted.length <= 17) {
                this.value = formatted.toUpperCase();
            }
        });

        // Username validation
        document.querySelector('input[name="username"]').addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '').toLowerCase();
        });

        // Phone number formatting
        document.querySelector('input[name="phone"]').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.startsWith('0')) {
                value = '254' + value.substring(1);
            }
            if (!value.startsWith('254') && !value.startsWith('+254')) {
                value = '254' + value;
            }
            this.value = '+' + value;
        });
    </script>
</body>
</html>