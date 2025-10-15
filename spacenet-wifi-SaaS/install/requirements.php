<?php
// install/requirements.php - System Requirements Checker
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Requirements - SPACE NET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .requirement-ok { color: #28a745; }
        .requirement-warning { color: #ffc107; }
        .requirement-error { color: #dc3545; }
    </style>
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">System Requirements Check</h4>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Requirement</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>PHP Version</td>
                            <td>
                                <?php 
                                $phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
                                echo $phpOk ? '<span class="requirement-ok">✓ Pass</span>' : '<span class="requirement-error">✗ Fail</span>';
                                ?>
                            </td>
                            <td><?php echo PHP_VERSION; ?> (Required: 8.0+)</td>
                        </tr>
                        <tr>
                            <td>PDO Extension</td>
                            <td>
                                <?php 
                                $pdoOk = extension_loaded('pdo');
                                echo $pdoOk ? '<span class="requirement-ok">✓ Available</span>' : '<span class="requirement-error">✗ Missing</span>';
                                ?>
                            </td>
                            <td>Database connectivity</td>
                        </tr>
                        <tr>
                            <td>PDO MySQL</td>
                            <td>
                                <?php 
                                $mysqlOk = extension_loaded('pdo_mysql');
                                echo $mysqlOk ? '<span class="requirement-ok">✓ Available</span>' : '<span class="requirement-error">✗ Missing</span>';
                                ?>
                            </td>
                            <td>MySQL database support</td>
                        </tr>
                        <tr>
                            <td>cURL Extension</td>
                            <td>
                                <?php 
                                $curlOk = extension_loaded('curl');
                                echo $curlOk ? '<span class="requirement-ok">✓ Available</span>' : '<span class="requirement-error">✗ Missing</span>';
                                ?>
                            </td>
                            <td>Payment gateway integration</td>
                        </tr>
                        <tr>
                            <td>JSON Extension</td>
                            <td>
                                <?php 
                                $jsonOk = extension_loaded('json');
                                echo $jsonOk ? '<span class="requirement-ok">✓ Available</span>' : '<span class="requirement-error">✗ Missing</span>';
                                ?>
                            </td>
                            <td>Data processing</td>
                        </tr>
                        <tr>
                            <td>mbstring Extension</td>
                            <td>
                                <?php 
                                $mbstringOk = extension_loaded('mbstring');
                                echo $mbstringOk ? '<span class="requirement-ok">✓ Available</span>' : '<span class="requirement-warning">⚠ Recommended</span>';
                                ?>
                            </td>
                            <td>Multibyte string support</td>
                        </tr>
                        <tr>
                            <td>Write Permissions</td>
                            <td>
                                <?php 
                                $dirs = ['../logs', '../uploads', '../backups', '../config'];
                                $writeOk = true;
                                foreach ($dirs as $dir) {
                                    if (!is_writable($dir)) {
                                        $writeOk = false;
                                        break;
                                    }
                                }
                                echo $writeOk ? '<span class="requirement-ok">✓ OK</span>' : '<span class="requirement-warning">⚠ Limited</span>';
                                ?>
                            </td>
                            <td>File system permissions</td>
                        </tr>
                        <tr>
                            <td>Memory Limit</td>
                            <td>
                                <?php 
                                $memoryLimit = ini_get('memory_limit');
                                $memoryOk = (int)$memoryLimit >= 128;
                                echo $memoryOk ? '<span class="requirement-ok">✓ OK</span>' : '<span class="requirement-warning">⚠ Low</span>';
                                ?>
                            </td>
                            <td><?php echo $memoryLimit; ?> (Recommended: 256M+)</td>
                        </tr>
                    </tbody>
                </table>

                <?php 
                $allOk = $phpOk && $pdoOk && $mysqlOk && $curlOk && $jsonOk;
                if ($allOk): 
                ?>
                    <div class="alert alert-success">
                        <h5>✓ System Ready</h5>
                        <p class="mb-0">All critical requirements are met. You can proceed with installation.</p>
                    </div>
                    <a href="setup.php" class="btn btn-primary">Continue to Installation</a>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <h5>✗ Requirements Not Met</h5>
                        <p class="mb-0">Please fix the issues above before proceeding with installation.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
