<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'goba_hospital');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('SITE_NAME', 'Goba Hospital Patient Record Management System');
define('SITE_URL', 'http://localhost/goba_hospital');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Database connection function
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Session management
function startSecureSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Authentication functions
function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_type'] !== 'admin') {
        header('Location: ../index.php');
        exit();
    }
}

function requireDoctor() {
    requireLogin();
    if ($_SESSION['user_type'] !== 'doctor') {
        header('Location: ../index.php');
        exit();
    }
}

function requirePatient() {
    requireLogin();
    if ($_SESSION['user_type'] !== 'patient') {
        header('Location: ../index.php');
        exit();
    }
}

function requireStaff() {
    requireLogin();
    if ($_SESSION['user_type'] !== 'staff') {
        header('Location: ../index.php');
        exit();
    }
}

function requireExternal() {
    requireLogin();
    if ($_SESSION['user_type'] !== 'external') {
        header('Location: ../index.php');
        exit();
    }
}

// Utility functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateReferenceId($prefix) {
    return $prefix . date('Ymd') . rand(1000, 9999);
}

function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('F j, Y g:i A', strtotime($datetime));
}

// File upload functions
function uploadFile($file, $directory, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']) {
    $uploadDir = UPLOAD_PATH . $directory . '/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = basename($file['name']);
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedTypes)) {
        return false;
    }
    
    $newFileName = uniqid() . '.' . $fileExtension;
    $targetPath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $directory . '/' . $newFileName;
    }
    
    return false;
}

// Error and success message functions
function setMessage($type, $message) {
    startSecureSession();
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

function getMessage() {
    startSecureSession();
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

// Pagination function
function paginate($totalRecords, $recordsPerPage, $currentPage, $url) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $pagination = '';
    
    if ($totalPages > 1) {
        $pagination .= '<nav><ul class="pagination">';
        
        // Previous button
        if ($currentPage > 1) {
            $pagination .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . ($currentPage - 1) . '">Previous</a></li>';
        }
        
        // Page numbers
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $currentPage) ? 'active' : '';
            $pagination .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $url . '?page=' . $i . '">' . $i . '</a></li>';
        }
        
        // Next button
        if ($currentPage < $totalPages) {
            $pagination .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . ($currentPage + 1) . '">Next</a></li>';
        }
        
        $pagination .= '</ul></nav>';
    }
    
    return $pagination;
}
?>