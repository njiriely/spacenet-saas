<?php
// register.php - Registration Handler
session_start();
require_once 'includes/Database.php';
require_once 'includes/Tenant.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $tenant = new Tenant();
        
        $data = [
            'company_name' => $_POST['company_name'],
            'contact_person' => $_POST['contact_person'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'subscription_plan' => $_POST['subscription_plan'],
            'mikrotik_ip' => $_POST['mikrotik_ip'] ?? null,
            'mikrotik_username' => $_POST['mikrotik_username'] ?? null,
            'mikrotik_password' => $_POST['mikrotik_password'] ?? null
        ];
        
        $tenantId = $tenant->create($data);
        
        // Redirect to success page
        header('Location: registration-success.php?tenant=' . $tenantId);
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        header('Location: index.php?error=' . urlencode($error));
        exit;
    }
}
?>