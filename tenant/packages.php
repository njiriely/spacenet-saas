<?php
// tenant/packages.php - Package Management
session_start();
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Package.php';

$auth = new Auth();
$auth->requireLogin();
$currentUser = $auth->getCurrentUser();

$package = new Package();
$packages = $package->getTenantPackages($currentUser['tenant_id']);
$message = '';
$messageType = '';

// Handle package updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    try {
        if ($action === 'update') {
            $packageId = $_POST['package_id'];
            $data = [
                'name' => $_POST['name'],
                'price' => $_POST['price'],
                'duration_type' => $_POST['duration_type'],
                'duration_value' => $_POST['duration_value'],
                'speed_limit' => $_POST['speed_limit'],
                'device_limit' => $_POST['device_limit']
            ];
            
            $package->updatePackage($packageId, $data, $currentUser['tenant_id']);
            $message = "Package updated successfully!";
            $messageType = "success";
            
            // Refresh packages
            $packages = $package->getTenantPackages($currentUser['tenant_id']);
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packages - <?php echo htmlspecialchars($currentUser['company_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #00BCD4; --primary-dark: #0097A7; }
        .package-card { border-top: 4px solid var(--primary); margin-bottom: 20px; }
        .package-card.popular { border-top-color: #ffc107; position: relative; }
        .package-card.popular::before { content: "POPULAR"; position: absolute; top: -12px; right: 20px; background: #ffc107; color: white; padding: 3px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
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
                <a class="nav-link text-white active" href="packages.php">Packages</a>
                <a class="nav-link text-white" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Package Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPackageModal">
                <i class="fas fa-plus me-2"></i>Add Package
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($packages as $pkg): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card package-card <?php echo $pkg['name'] === 'Weekly' ? 'popular' : ''; ?>">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?php echo htmlspecialchars($pkg['name']); ?></h5>
                            <div class="h3 text-primary mb-3">Ksh <?php echo number_format($pkg['price']); ?></div>
                            
                            <div class="mb-2">
                                <i class="fas fa-clock text-muted me-2"></i>
                                <?php echo $pkg['duration_value']; ?> <?php echo ucfirst($pkg['duration_type']); ?>
                            </div>
                            
                            <div class="mb-2">
                                <i class="fas fa-tachometer-alt text-muted me-2"></i>
                                <?php echo $pkg['speed_limit']; ?>Mbps
                            </div>
                            
                            <div class="mb-3">
                                <i class="fas fa-devices text-muted me-2"></i>
                                <?php echo $pkg['device_limit']; ?> Device(s)
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="editPackage(<?php echo htmlspecialchars(json_encode($pkg)); ?>)">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="togglePackage(<?php echo $pkg['id']; ?>, <?php echo $pkg['is_active'] ? 'false' : 'true'; ?>)">
                                    <i class="fas fa-<?php echo $pkg['is_active'] ? 'pause' : 'play'; ?> me-1"></i>
                                    <?php echo $pkg['is_active'] ? 'Disable' : 'Enable'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Edit Package Modal -->
    <div class="modal fade" id="editPackageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editPackageForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Package</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="package_id" id="editPackageId">
                        
                        <div class="mb-3">
                            <label class="form-label">Package Name</label>
                            <input type="text" name="name" class="form-control" id="editPackageName" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Price (KES)</label>
                                    <input type="number" name="price" class="form-control" id="editPackagePrice" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Speed Limit (Mbps)</label>
                                    <select name="speed_limit" class="form-select" id="editPackageSpeed" required>
                                        <option value="3">3 Mbps</option>
                                        <option value="5">5 Mbps</option>
                                        <option value="8">8 Mbps</option>
                                        <option value="10">10 Mbps</option>
                                        <option value="15">15 Mbps</option>
                                        <option value="20">20 Mbps</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Duration</label>
                                    <input type="number" name="duration_value" class="form-control" id="editPackageDuration" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Duration Type</label>
                                    <select name="duration_type" class="form-select" id="editPackageDurationType" required>
                                        <option value="hours">Hours</option>
                                        <option value="days">Days</option>
                                        <option value="months">Months</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Device Limit</label>
                                    <input type="number" name="device_limit" class="form-control" id="editPackageDevices" min="1" max="10" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Package</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editPackage(pkg) {
            document.getElementById('editPackageId').value = pkg.id;
            document.getElementById('editPackageName').value = pkg.name;
            document.getElementById('editPackagePrice').value = pkg.price;
            document.getElementById('editPackageSpeed').value = pkg.speed_limit;
            document.getElementById('editPackageDuration').value = pkg.duration_value;
            document.getElementById('editPackageDurationType').value = pkg.duration_type;
            document.getElementById('editPackageDevices').value = pkg.device_limit;
            
            new bootstrap.Modal(document.getElementById('editPackageModal')).show();
        }
        
        function togglePackage(packageId, enable) {
            if (confirm(`Are you sure you want to ${enable ? 'enable' : 'disable'} this package?`)) {
                // Implementation would send AJAX request to toggle package status
                location.reload();
            }
        }
    </script>
</body>
</html>