<?php
// api/radius/accounting.php - RADIUS Accounting Integration
header('Content-Type: application/json');

require_once '../../includes/Database.php';

try {
    // Handle RADIUS accounting requests
    $requestType = $_POST['Acct-Status-Type'] ?? $_GET['type'] ?? null;
    
    if (!$requestType) {
        throw new Exception('Accounting type is required');
    }
    
    $db = Database::getInstance();
    
    switch ($requestType) {
        case 'Start':
        case 'start':
            // Session start
            $username = $_POST['User-Name'] ?? '';
            $sessionId = $_POST['Acct-Session-Id'] ?? '';
            $nasIP = $_POST['NAS-IP-Address'] ?? '';
            $framedIP = $_POST['Framed-IP-Address'] ?? '';
            
            $db->query(
                "INSERT INTO radacct (acctsessionid, username, nasipaddress, framedipaddress, acctstarttime) 
                 VALUES (?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE acctstarttime = NOW()",
                [$sessionId, $username, $nasIP, $framedIP]
            );
            break;
            
        case 'Stop':
        case 'stop':
            // Session stop
            $username = $_POST['User-Name'] ?? '';
            $sessionId = $_POST['Acct-Session-Id'] ?? '';
            $sessionTime = $_POST['Acct-Session-Time'] ?? 0;
            $inputOctets = $_POST['Acct-Input-Octets'] ?? 0;
            $outputOctets = $_POST['Acct-Output-Octets'] ?? 0;
            
            $db->query(
                "UPDATE radacct SET 
                 acctstoptime = NOW(), 
                 acctsessiontime = ?, 
                 acctinputoctets = ?, 
                 acctoutputoctets = ?
                 WHERE acctsessionid = ? AND username = ?",
                [$sessionTime, $inputOctets, $outputOctets, $sessionId, $username]
            );
            break;
            
        case 'Interim-Update':
        case 'update':
            // Interim update
            $username = $_POST['User-Name'] ?? '';
            $sessionId = $_POST['Acct-Session-Id'] ?? '';
            $inputOctets = $_POST['Acct-Input-Octets'] ?? 0;
            $outputOctets = $_POST['Acct-Output-Octets'] ?? 0;
            
            $db->query(
                "UPDATE radacct SET 
                 acctupdatetime = NOW(),
                 acctinputoctets = ?, 
                 acctoutputoctets = ?
                 WHERE acctsessionid = ? AND username = ?",
                [$inputOctets, $outputOctets, $sessionId, $username]
            );
            break;
    }
    
    echo json_encode(['success' => true, 'message' => 'Accounting record processed']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
