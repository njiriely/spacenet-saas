<?php
// registration-success.php - Registration Success Page
echo "Registration successful! Tenant ID: " . htmlspecialchars($_GET['tenant'] ?? 'N/A');
?>
