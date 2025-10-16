<?php
// customer/index.php - Customer Facing Portal
session_start();
require_once '../includes/database.php';
require_once '../includes/Package.php';

// Get tenant info from subdomain or parameter
$subdomain = $_GET['tenant'] ?? 'demo'; // For demo purposes

$db = \SpaceNet\Database::getInstance();
$tenant = $db->query("SELECT * FROM tenants WHERE subdomain = ? AND status IN ('trial', 'active')", [$subdomain])->fetch();

if (!$tenant) {
    die('ISP not found or inactive');
}

$package = new Package();
$packages = $package->getTenantPackages($tenant['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tenant['company_name']); ?> - Internet Packages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00BCD4;
            --primary-dark: #0097A7;
            --success: #4CAF50;
            --warning: #FFC107;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .portal-container {
            padding: 40px 0;
        }
        
        .portal-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .package-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
            border-top: 4px solid var(--primary);
        }
        
        .package-card:hover {
            transform: translateY(-5px);
        }
        
        .package-card.popular {
            border-top: 4px solid var(--warning);
            position: relative;
            transform: scale(1.02);
        }
        
        .package-card.popular::before {
            content: "MOST POPULAR";
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--warning);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 20px 0;
        }
        
        .device-info {
            background: rgba(0, 188, 212, 0.1);
            border-radius: 10px;
            padding: 10px;
            margin: 15px 0;
            color: var(--primary);
        }
        
        .purchase-form {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }
        
        .btn-purchase {
            background: var(--primary);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-purchase:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            color: white;
        }
        
        .payment-methods {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        
        .payment-method {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover,
        .payment-method.selected {
            border-color: var(--primary);
            background: rgba(0, 188, 212, 0.05);
        }
    </style>
</head>
<body>
    <div class="container portal-container">
        <!-- Header -->
        <div class="portal-header">
            <h1 class="mb-3">
                <i class="fas fa-wifi text-primary me-3"></i>
                <?php echo htmlspecialchars($tenant['company_name']); ?>
            </h1>
            <p class="lead text-muted mb-0">Choose your internet package and get connected instantly!</p>
        </div>

        <!-- Packages Grid -->
        <div class="row">
            <?php foreach ($packages as $index => $pkg): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="package-card <?php echo $pkg['name'] === 'Weekly' ? 'popular' : ''; ?>">
                        <h4><?php echo htmlspecialchars($pkg['name']); ?></h4>
                        <div class="price">Ksh <?php echo number_format($pkg['price']); ?></div>
                        
                        <div class="mb-3">
                            <i class="fas fa-clock text-primary me-2"></i>
                            <?php echo $pkg['duration_value']; ?> <?php echo ucfirst($pkg['duration_type']); ?>
                        </div>
                        
                        <div class="mb-3">
                            <i class="fas fa-tachometer-alt text-success me-2"></i>
                            <?php echo $pkg['speed_limit']; ?>Mbps Speed
                        </div>
                        
                        <div class="device-info">
                            <i class="fas fa-devices me-2"></i>
                            <?php if ($pkg['device_limit'] > 1): ?>
                                Up to <?php echo $pkg['device_limit']; ?> devices
                            <?php else: ?>
                                Single device
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($pkg['device_limit'] > 1): ?>
                            <div class="small text-muted mb-3">
                                <?php
                                $multipliers = json_decode($pkg['device_multiplier'], true);
                                if ($multipliers) {
                                    echo "Multiple devices: ";
                                    foreach ($multipliers as $devices => $multiplier) {
                                        if ($devices > 1) {
                                            echo $devices . " devices: Ksh " . number_format($pkg['price'] * $multiplier) . " • ";
                                        }
                                    }
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <button class="btn btn-purchase w-100" onclick="selectPackage(<?php echo $pkg['id']; ?>, '<?php echo htmlspecialchars($pkg['name']); ?>', <?php echo $pkg['price']; ?>, <?php echo $pkg['device_limit']; ?>)">
                            <i class="fas fa-shopping-cart me-2"></i>Buy Now
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Purchase Form Modal -->
        <div class="modal fade" id="purchaseModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white;">
                        <h5 class="modal-title">Complete Your Purchase</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="purchaseForm">
                            <input type="hidden" name="tenant_id" value="<?php echo $tenant['id']; ?>">
                            <input type="hidden" name="package_id" id="selectedPackageId">
                            
                            <!-- Package Summary -->
                            <div class="alert alert-info" id="packageSummary">
                                <h6 class="mb-1">Selected Package</h6>
                                <div id="summaryText"></div>
                            </div>
                            
                            <!-- Customer Information -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number *</label>
                                        <input type="tel" name="phone" class="form-control" placeholder="+254..." required>
                                        <small class="text-muted">For M-Pesa payments and notifications</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email (Optional)</label>
                                        <input type="email" name="email" class="form-control" placeholder="your@email.com">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Username *</label>
                                        <input type="text" name="username" class="form-control" required>
                                        <small class="text-muted">Choose a username for your internet session</small>
                                    </div>
                                </div>
                                <div class="col-md-6" id="deviceCountSection" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">Number of Devices</label>
                                        <select name="device_count" class="form-select" id="deviceCountSelect" onchange="updatePrice()">
                                            <option value="1">1 Device</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Methods -->
                            <div class="mb-4">
                                <label class="form-label">Payment Method</label>
                                <div class="payment-methods">
                                    <div class="payment-method selected" onclick="selectPayment('mpesa')">
                                        <i class="fas fa-mobile-alt text-success me-2"></i>M-Pesa
                                    </div>
                                    <div class="payment-method" onclick="selectPayment('pesapal')">
                                        <i class="fas fa-credit-card text-primary me-2"></i>Card/Bank
                                    </div>
                                </div>
                                <input type="hidden" name="payment_method" id="selectedPayment" value="mpesa">
                            </div>
                            
                            <!-- Total Amount -->
                            <div class="alert alert-success text-center">
                                <h4 class="mb-1">Total Amount: <span id="totalAmount">Ksh 0</span></h4>
                                <small class="text-muted">Includes all taxes and fees</small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="processPayment()">
                            <i class="fas fa-credit-card me-2"></i>Pay Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center text-white mt-5">
            <p class="mb-1">Powered by <strong>SPACE NET SaaS</strong></p>
            <p class="small">Secure payments • Instant activation • 24/7 support</p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedPackage = null;
        let packageMultipliers = {};
        
        function selectPackage(packageId, name, price, deviceLimit) {
            selectedPackage = {
                id: packageId,
                name: name,
                price: price,
                deviceLimit: deviceLimit
            };
            
            // Update form
            document.getElementById('selectedPackageId').value = packageId;
            document.getElementById('summaryText').innerHTML = 
                `<strong>${name}</strong> - Base price: Ksh ${price.toLocaleString()}`;
            
            // Setup device count options
            const deviceSection = document.getElementById('deviceCountSection');
            const deviceSelect = document.getElementById('deviceCountSelect');
            
            if (deviceLimit > 1) {
                deviceSection.style.display = 'block';
                deviceSelect.innerHTML = '';
                
                // Get multipliers from the package data
                // This would normally come from the PHP data, simplified for demo
                const multipliers = {1: 1.0, 2: 1.5, 3: 2.0, 4: 2.5};
                packageMultipliers = multipliers;
                
                for (let i = 1; i <= Math.min(deviceLimit, 4); i++) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = `${i} Device${i > 1 ? 's' : ''} - Ksh ${(price * multipliers[i]).toLocaleString()}`;
                    deviceSelect.appendChild(option);
                }
            } else {
                deviceSection.style.display = 'none';
                packageMultipliers = {1: 1.0};
            }
            
            updatePrice();
            
            // Show modal
            new bootstrap.Modal(document.getElementById('purchaseModal')).show();
        }
        
        function updatePrice() {
            if (!selectedPackage) return;
            
            const deviceCount = parseInt(document.getElementById('deviceCountSelect').value) || 1;
            const multiplier = packageMultipliers[deviceCount] || 1.0;
            const totalPrice = selectedPackage.price * multiplier;
            
            document.getElementById('totalAmount').textContent = `Ksh ${totalPrice.toLocaleString()}`;
        }
        
        function selectPayment(method) {
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            event.target.closest('.payment-method').classList.add('selected');
            document.getElementById('selectedPayment').value = method;
        }
        
        function processPayment() {
            const form = document.getElementById('purchaseForm');
            const formData = new FormData(form);
            
            // Validate form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Add device count and total amount
            const deviceCount = parseInt(document.getElementById('deviceCountSelect').value) || 1;
            const multiplier = packageMultipliers[deviceCount] || 1.0;
            const totalAmount = selectedPackage.price * multiplier;
            
            formData.append('device_count', deviceCount);
            formData.append('total_amount', totalAmount);
            
            // Show loading
            const submitBtn = event.target;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            submitBtn.disabled = true;
            
            // Process payment
            fetch('api/process-payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.payment_method === 'mpesa') {
                        // Show M-Pesa instructions
                        showMpesaInstructions(data);
                    } else {
                        // Redirect to PesaPal
                        window.location.href = data.redirect_url;
                    }
                } else {
                    alert('Payment failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Payment processing error. Please try again.');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }
        
        function showMpesaInstructions(data) {
            const modal = document.getElementById('purchaseModal');
            modal.querySelector('.modal-body').innerHTML = `
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-mobile-alt text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="mb-3">M-Pesa Payment Request Sent</h4>
                    <div class="alert alert-info">
                        <h6>Follow these steps:</h6>
                        <ol class="text-start">
                            <li>Check your phone for M-Pesa STK Push</li>
                            <li>Enter your M-Pesa PIN</li>
                            <li>Wait for confirmation</li>
                            <li>Your internet will activate automatically</li>
                        </ol>
                    </div>
                    <div class="alert alert-warning">
                        <strong>Transaction Code:</strong> ${data.transaction_code}<br>
                        <strong>Amount:</strong> Ksh ${data.amount}
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <button class="btn btn-success" onclick="checkPaymentStatus('${data.transaction_code}')">
                            <i class="fas fa-check me-2"></i>Check Status
                        </button>
                        <button class="btn btn-outline-secondary" onclick="location.reload()">
                            <i class="fas fa-redo me-2"></i>Try Again
                        </button>
                    </div>
                </div>
            `;
        }
        
        function checkPaymentStatus(transactionCode) {
            fetch(`api/payment-status.php?code=${transactionCode}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'completed') {
                    showSuccessMessage(data);
                } else if (data.status === 'failed') {
                    alert('Payment failed. Please try again.');
                } else {
                    alert('Payment is still processing. Please wait...');
                }
            });
        }
        
        function showSuccessMessage(data) {
            const modal = document.getElementById('purchaseModal');
            modal.querySelector('.modal-body').innerHTML = `
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="mb-3 text-success">Payment Successful!</h4>
                    <div class="alert alert-success">
                        <h6>Your Internet Access:</h6>
                        <p><strong>Username:</strong> ${data.username}</p>
                        <p><strong>Password:</strong> ${data.password}</p>
                        <p><strong>Duration:</strong> ${data.duration}</p>
                        <p><strong>Speed:</strong> ${data.speed}Mbps</p>
                    </div>
                    <div class="alert alert-info">
                        <p class="mb-0"><strong>Next Steps:</strong></p>
                        <ol class="text-start mb-0">
                            <li>Connect to WiFi: "${data.wifi_name}"</li>
                            <li>Open browser and enter credentials above</li>
                            <li>Start browsing immediately!</li>
                        </ol>
                    </div>
                    <button class="btn btn-primary" onclick="location.reload()">
                        <i class="fas fa-wifi me-2"></i>Buy Another Package
                    </button>
                </div>
            `;
        }
    </script>
</body>
</html>