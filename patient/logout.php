<?php
session_start();
require_once '../config/database.php';

// Log the logout action if user was logged in
if (isset($_SESSION['patient_logged_in']) && $_SESSION['patient_logged_in'] === true) {
    try {
        $db = Database::getInstance();
        
        // Log logout action
        $audit_sql = "INSERT INTO Audit_log (user_type, user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
        $db->insert($audit_sql, [
            'Patient',
            $_SESSION['patient_ssn'] ?? 'Unknown',
            'LOGOUT',
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch (Exception $e) {
        error_log("Logout audit log error: " . $e->getMessage());
    }
}

// Clear all session data
session_unset();
session_destroy();

// Start a new session for the success message
session_start();
$_SESSION['logout_success'] = true;

// Redirect to login page
header('Location: login.php');
exit();
?>