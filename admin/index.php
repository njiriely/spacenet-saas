<?php
// admin/index.php - Admin Dashboard
require_once '../includes/auth.php';
$auth = new SpaceNet\Auth();
$auth->requireLogin();

if ($auth->getCurrentUser()['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

echo "Welcome to the Super Admin Dashboard!";
?>
