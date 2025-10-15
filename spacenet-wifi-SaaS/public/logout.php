<?php
// public/logout.php - Simple Logout Handler
session_start();

// Determine user type and redirect appropriately
if (isset($_SESSION['customer_logged_in'])) {
    // Customer logout
    session_unset();
    session_destroy();
    header('Location: /customer/index.php');
} elseif (isset($_SESSION['logged_in'])) {
    // Admin/Tenant logout
    session_unset();
    session_destroy();
    header('Location: /login.php');
} else {
    // No active session
    header('Location: /index.php');
}
exit;

?>