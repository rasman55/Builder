<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['doctor_logged_in']) || $_SESSION['doctor_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

try {
    // Log the logout action in audit log
    $db = Database::getInstance();
    $audit_sql = "INSERT INTO Audit_log (user_type, user_id, action, details, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    
    $db->insert($audit_sql, [
        'Doctor',
        $_SESSION['doctor_ssn'],
        'logout',
        'Doctor logged out successfully',
        $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
} catch (Exception $e) {
    error_log("Logout audit error: " . $e->getMessage());
}

// Destroy session
session_unset();
session_destroy();

// Start new session for success message
session_start();
$_SESSION['logout_success'] = true;

// Redirect to login page
header('Location: login.php');
exit();
?>