<?php
// install/setup.php - Installation Script
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPACE NET SaaS - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00BCD4;
            --primary-dark: #0097A7;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            min-height: 100vh;
        }
        
        .install-container {
            padding: 40px 0;
        }
        
        .install-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .step {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .step.active {
            border-color: var(--primary);
            background: rgba(0, 188, 212, 0.05);
        }
        
        .step.completed {
            border-color: #28a745;
            background: rgba(40, 167, 69, 0.05);
        }
        
        .step-number {
            background: #e9ecef;
            color: #6c757d;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .step.active .step-number {
            background: var(--primary);
            color: white;
        }
        
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        
        .requirements-table td:first-child {
            font-weight: 500;
        }
        
        .requirement-ok { color: #28a745; }
        .requirement-error { color: #dc3545; }
        .requirement-warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container install-container">
        <div class="install-card">
            <div class="text-center mb-5">
                <i class="fas fa-satellite text-primary mb-3" style="font-size: 4rem;"></i>
                <h1 class="mb-3">SPACE NET SaaS Installation</h1>
                <p class="lead text-muted">Welcome to the installation wizard for your WiFi billing platform</p>
            </div>
            
            <!-- Installation Steps -->
            <div id="installationSteps">
                
                <!-- Step 1: System Requirements -->
                <div class="step active" id="step1">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number">1</div>
                        <h4 class="mb-0">System Requirements Check</h4>
                    </div>
                    
                    <table class="table requirements-table">
                        <tbody>
                            <tr>
                                <td>PHP Version</td>
                                <td>
                                    <?php
                                    $phpVersion = phpversion();
                                    $phpOk = version_compare($phpVersion, '8.0', '>=');
                                    ?>
                                    <span class="<?php echo $phpOk ? 'requirement-ok' : 'requirement-error'; ?>">
                                        <i class="fas <?php echo $phpOk ? 'fa-check' : 'fa-times'; ?> me-2"></i>
                                        <?php echo $phpVersion; ?> <?php echo $phpOk ? '(OK)' : '(Requires PHP 8.0+)'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>PDO Extension</td>
                                <td>
                                    <?php $pdoOk = extension_loaded('pdo'); ?>
                                    <span class="<?php echo $pdoOk ? 'requirement-ok' : 'requirement-error'; ?>">
                                        <i class="fas <?php echo $pdoOk ? 'fa-check' : 'fa-times'; ?> me-2"></i>
                                        <?php echo $pdoOk ? 'Available' : 'Not Available'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>MySQL PDO</td>
                                <td>
                                    <?php $mysqlOk = extension_loaded('pdo_mysql'); ?>
                                    <span class="<?php echo $mysqlOk ? 'requirement-ok' : 'requirement-error'; ?>">
                                        <i class="fas <?php echo $mysqlOk ? 'fa-check' : 'fa-times'; ?> me-2"></i>
                                        <?php echo $mysqlOk ? 'Available' : 'Not Available'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>cURL Extension</td>
                                <td>
                                    <?php $curlOk = extension_loaded('curl'); ?>
                                    <span class="<?php echo $curlOk ? 'requirement-ok' : 'requirement-error'; ?>">
                                        <i class="fas <?php echo $curlOk ? 'fa-check' : 'fa-times'; ?> me-2"></i>
                                        <?php echo $curlOk ? 'Available' : 'Not Available'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>JSON Extension</td>
                                <td>
                                    <?php $jsonOk = extension_loaded('json'); ?>
                                    <span class="<?php echo $jsonOk ? 'requirement-ok' : 'requirement-error'; ?>">
                                        <i class="fas <?php echo $jsonOk ? 'fa-check' : 'fa-times'; ?> me-2"></i>
                                        <?php echo $jsonOk ? 'Available' : 'Not Available'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Write Permissions</td>
                                <td>
                                    <?php 
                                    $writeOk = is_writable('../config/') && is_writable('../'); 
                                    ?>
                                    <span class="<?php echo $writeOk ? 'requirement-ok' : 'requirement-warning'; ?>">
                                        <i class="fas <?php echo $writeOk ? 'fa-check' : 'fa-exclamation-triangle'; ?> me-2"></i>
                                        <?php echo $writeOk ? 'OK' : 'Limited (some features may not work)'; ?>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <?php if ($phpOk && $pdoOk && $mysqlOk && $curlOk && $jsonOk): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>All requirements met! You can proceed with the installation.
                        </div>
                        <button class="btn btn-primary" onclick="nextStep(2)">Continue to Database Setup</button>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>Please fix the requirements above before continuing.
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Step 2: Database Configuration -->
                <div class="step" id="step2" style="display: none;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number">2</div>
                        <h4 class="mb-0">Database Configuration</h4>
                    </div>
                    
                    <form id="databaseForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Database Host</label>
                                    <input type="text" name="db_host" class="form-control" value="localhost" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Database Port</label>
                                    <input type="number" name="db_port" class="form-control" value="3306">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Database Name</label>
                                    <input type="text" name="db_name" class="form-control" value="spacenet_saas" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="db_username" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="db_password" class="form-control">
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" onclick="testDatabaseConnection()">
                                <i class="fas fa-plug me-2"></i>Test Connection
                            </button>
                            <button type="button" class="btn btn-success" onclick="nextStep(3)" id="dbNextBtn" style="display: none;">
                                Continue to Admin Setup
                            </button>
                        </div>
                        
                        <div id="dbTestResult" class="mt-3"></div>
                    </form>
                </div>
                
                <!-- Step 3: Admin Account -->
                <div class="step" id="step3" style="display: none;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number">3</div>
                        <h4 class="mb-0">Create Super Admin Account</h4>
                    </div>
                    
                    <form id="adminForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="admin_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="admin_email" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="admin_password" class="form-control" minlength="8" required>
                                    <small class="text-muted">Minimum 8 characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="admin_password_confirm" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-primary" onclick="nextStep(4)">
                            Continue to Application Settings
                        </button>
                    </form>
                </div>
                
                <!-- Step 4: Application Settings -->
                <div class="step" id="step4" style="display: none;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number">4</div>
                        <h4 class="mb-0">Application Settings</h4>
                    </div>
                    
                    <form id="appSettingsForm">
                        <div class="mb-3">
                            <label class="form-label">Application URL</label>
                            <input type="url" name="app_url" class="form-control" value="<?php echo 'https://' . $_SERVER['HTTP_HOST']; ?>" required>
                            <small class="text-muted">The main domain for your SaaS platform</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" name="company_name" class="form-control" value="SPACE NET SaaS" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Support Email</label>
                                    <input type="email" name="support_email" class="form-control" value="support@spacenet.co.ke" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Trial Duration (days)</label>
                                    <input type="number" name="trial_days" class="form-control" value="15" min="1" max="90" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Standard Plan Price (KSh)</label>
                                    <input type="number" name="standard_price" class="form-control" value="1999" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Professional Plan Price (KSh)</label>
                                    <input type="number" name="professional_price" class="form-control" value="4999" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Enterprise Plan Price (KSh)</label>
                            <input type="number" name="enterprise_price" class="form-control" value="9999" step="0.01" required>
                        </div>
                        
                        <button type="button" class="btn btn-primary" onclick="nextStep(5)">
                            Continue to Payment Setup
                        </button>
                    </form>
                </div>
                
                <!-- Step 5: Payment Gateway Configuration -->
                <div class="step" id="step5" style="display: none;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number">5</div>
                        <h4 class="mb-0">Payment Gateway Setup</h4>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        You can configure these settings later in the admin panel. Leave blank to skip for now.
                    </div>
                    
                    <form id="paymentForm">
                        <h6 class="mb-3">M-Pesa Configuration</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Consumer Key</label>
                                    <input type="text" name="mpesa_consumer_key" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Consumer Secret</label>
                                    <input type="password" name="mpesa_consumer_secret" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Shortcode</label>
                                    <input type="text" name="mpesa_shortcode" class="form-control" value="174379">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Passkey</label>
                                    <input type="password" name="mpesa_passkey" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Environment</label>
                            <select name="mpesa_environment" class="form-select">
                                <option value="sandbox">Sandbox (Testing)</option>
                                <option value="production">Production (Live)</option>
                            </select>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">PesaPal Configuration</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Consumer Key</label>
                                    <input type="text" name="pesapal_consumer_key" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Consumer Secret</label>
                                    <input type="password" name="pesapal_consumer_secret" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-success" onclick="startInstallation()">
                            <i class="fas fa-rocket me-2"></i>Start Installation
                        </button>
                    </form>
                </div>
                
                <!-- Step 6: Installation Progress -->
                <div class="step" id="step6" style="display: none;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number">6</div>
                        <h4 class="mb-0">Installation Progress</h4>
                    </div>
                    
                    <div id="installationProgress">
                        <div class="progress mb-3">
                            <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                        </div>
                        
                        <div id="installationLog" class="border rounded p-3" style="height: 300px; overflow-y: auto; background: #f8f9fa;">
                            <div class="text-muted">Starting installation...</div>
                        </div>
                    </div>
                    
                    <div id="installationComplete" style="display: none;">
                        <div class="alert alert-success text-center">
                            <i class="fas fa-check-circle mb-3" style="font-size: 3rem;"></i>
                            <h4>Installation Complete!</h4>
                            <p>Your SPACE NET SaaS platform is now ready to use.</p>
                            <div class="d-flex gap-3 justify-content-center mt-4">
                                <a href="../admin/" class="btn btn-primary btn-lg">
                                    <i class="fas fa-tachometer-alt me-2"></i>Access Admin Panel
                                </a>
                                <a href="../" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-home me-2"></i>Visit Homepage
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 1;
        let installationData = {};
        
        function nextStep(step) {
            // Hide current step
            document.getElementById(`step${currentStep}`).style.display = 'none';
            document.getElementById(`step${currentStep}`).classList.remove('active');
            document.getElementById(`step${currentStep}`).classList.add('completed');
            
            // Show next step
            document.getElementById(`step${step}`).style.display = 'block';
            document.getElementById(`step${step}`).classList.add('active');
            
            currentStep = step;
        }
        
        function testDatabaseConnection() {
            const form = document.getElementById('databaseForm');
            const formData = new FormData(form);
            const resultDiv = document.getElementById('dbTestResult');
            const nextBtn = document.getElementById('dbNextBtn');
            
            resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Testing connection...</div>';
            
            fetch('test-db-connection.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>Database connection successful!
                            <br><small>Database version: ${data.version}</small>
                        </div>`;
                    nextBtn.style.display = 'inline-block';
                    
                    // Store database config
                    installationData.database = Object.fromEntries(formData);
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>Connection failed: ${data.error}
                        </div>`;
                    nextBtn.style.display = 'none';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Error testing connection: ${error.message}
                    </div>`;
            });
        }
        
        function startInstallation() {
            // Collect all form data
            const adminForm = new FormData(document.getElementById('adminForm'));
            const appForm = new FormData(document.getElementById('appSettingsForm'));
            const paymentForm = new FormData(document.getElementById('paymentForm'));
            
            // Validate admin passwords match
            const password = adminForm.get('admin_password');
            const confirmPassword = adminForm.get('admin_password_confirm');
            
            if (password !== confirmPassword) {
                alert('Admin passwords do not match!');
                return;
            }
            
            // Store form data
            installationData.admin = Object.fromEntries(adminForm);
            installationData.app = Object.fromEntries(appForm);
            installationData.payment = Object.fromEntries(paymentForm);
            
            // Move to progress step
            nextStep(6);
            
            // Start installation process
            performInstallation();
        }
        
        function performInstallation() {
            const progressBar = document.getElementById('progressBar');
            const log = document.getElementById('installationLog');
            
            const steps = [
                'Creating database tables...',
                'Inserting default data...',
                'Creating configuration files...',
                'Setting up admin account...',
                'Configuring payment gateways...',
                'Finalizing installation...'
            ];
            
            let currentStepIndex = 0;
            
            function runStep() {
                if (currentStepIndex >= steps.length) {
                    // Installation complete
                    progressBar.style.width = '100%';
                    log.innerHTML += '<div class="text-success"><i class="fas fa-check me-2"></i>Installation completed successfully!</div>';
                    
                    setTimeout(() => {
                        document.getElementById('installationProgress').style.display = 'none';
                        document.getElementById('installationComplete').style.display = 'block';
                    }, 1000);
                    
                    return;
                }
                
                const stepName = steps[currentStepIndex];
                const progress = ((currentStepIndex + 1) / steps.length) * 100;
                
                log.innerHTML += `<div class="text-info"><i class="fas fa-spinner fa-spin me-2"></i>${stepName}</div>`;
                progressBar.style.width = progress + '%';
                
                // Simulate installation step
                setTimeout(() => {
                    log.innerHTML += `<div class="text-success"><i class="fas fa-check me-2"></i>${stepName} Complete</div>`;
                    log.scrollTop = log.scrollHeight;
                    
                    currentStepIndex++;
                    setTimeout(runStep, 1000);
                }, Math.random() * 2000 + 1000); // Random delay between 1-3 seconds
            }
            
            runStep();
        }
    </script>
</body>
</html>