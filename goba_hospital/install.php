<?php
// Goba Hospital Patient Record Management System - Installation Script

// Check if already installed
if (file_exists('includes/config.php')) {
    die('System is already installed. Remove install.php for security.');
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        // Database configuration
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_name = $_POST['db_name'] ?? 'goba_hospital';
        $db_user = $_POST['db_user'] ?? 'root';
        $db_pass = $_POST['db_pass'] ?? '';
        
        // Test database connection
        try {
            $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
            $pdo->exec("USE `$db_name`");
            
            // Import schema
            $schema = file_get_contents('database/schema.sql');
            $pdo->exec($schema);
            
            // Create config file
            $config_content = "<?php
// Database configuration
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');

// Application configuration
define('SITE_NAME', 'Goba Hospital Patient Record Management System');
define('SITE_URL', '" . ($_POST['site_url'] ?? 'http://localhost/goba_hospital') . "');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Database connection function
function getDBConnection() {
    try {
        \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME, DB_USER, DB_PASS);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return \$pdo;
    } catch(PDOException \$e) {
        die(\"Connection failed: \" . \$e->getMessage());
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
    return isset(\$_SESSION['user_id']) && isset(\$_SESSION['user_type']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (\$_SESSION['user_type'] !== 'admin') {
        header('Location: ../index.php');
        exit();
    }
}

function requireDoctor() {
    requireLogin();
    if (\$_SESSION['user_type'] !== 'doctor') {
        header('Location: ../index.php');
        exit();
    }
}

function requirePatient() {
    requireLogin();
    if (\$_SESSION['user_type'] !== 'patient') {
        header('Location: ../index.php');
        exit();
    }
}

function requireStaff() {
    requireLogin();
    if (\$_SESSION['user_type'] !== 'staff') {
        header('Location: ../index.php');
        exit();
    }
}

function requireExternal() {
    requireLogin();
    if (\$_SESSION['user_type'] !== 'external') {
        header('Location: ../index.php');
        exit();
    }
}

// Utility functions
function sanitizeInput(\$data) {
    \$data = trim(\$data);
    \$data = stripslashes(\$data);
    \$data = htmlspecialchars(\$data);
    return \$data;
}

function generateReferenceId(\$prefix) {
    return \$prefix . date('Ymd') . rand(1000, 9999);
}

function formatDate(\$date) {
    return date('F j, Y', strtotime(\$date));
}

function formatDateTime(\$datetime) {
    return date('F j, Y g:i A', strtotime(\$datetime));
}

// File upload functions
function uploadFile(\$file, \$directory, \$allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']) {
    \$uploadDir = UPLOAD_PATH . \$directory . '/';
    
    if (!is_dir(\$uploadDir)) {
        mkdir(\$uploadDir, 0755, true);
    }
    
    \$fileName = basename(\$file['name']);
    \$fileExtension = strtolower(pathinfo(\$fileName, PATHINFO_EXTENSION));
    
    if (!in_array(\$fileExtension, \$allowedTypes)) {
        return false;
    }
    
    \$newFileName = uniqid() . '.' . \$fileExtension;
    \$targetPath = \$uploadDir . \$newFileName;
    
    if (move_uploaded_file(\$file['tmp_name'], \$targetPath)) {
        return \$directory . '/' . \$newFileName;
    }
    
    return false;
}

// Error and success message functions
function setMessage(\$type, \$message) {
    startSecureSession();
    \$_SESSION['message'] = [
        'type' => \$type,
        'text' => \$message
    ];
}

function getMessage() {
    startSecureSession();
    if (isset(\$_SESSION['message'])) {
        \$message = \$_SESSION['message'];
        unset(\$_SESSION['message']);
        return \$message;
    }
    return null;
}

// Pagination function
function paginate(\$totalRecords, \$recordsPerPage, \$currentPage, \$url) {
    \$totalPages = ceil(\$totalRecords / \$recordsPerPage);
    \$pagination = '';
    
    if (\$totalPages > 1) {
        \$pagination .= '<nav><ul class=\"pagination\">';
        
        // Previous button
        if (\$currentPage > 1) {
            \$pagination .= '<li class=\"page-item\"><a class=\"page-link\" href=\"' . \$url . '?page=' . (\$currentPage - 1) . '\">Previous</a></li>';
        }
        
        // Page numbers
        for (\$i = 1; \$i <= \$totalPages; \$i++) {
            \$active = (\$i == \$currentPage) ? 'active' : '';
            \$pagination .= '<li class=\"page-item ' . \$active . '\"><a class=\"page-link\" href=\"' . \$url . '?page=' . \$i . '\">' . \$i . '</a></li>';
        }
        
        // Next button
        if (\$currentPage < \$totalPages) {
            \$pagination .= '<li class=\"page-item\"><a class=\"page-link\" href=\"' . \$url . '?page=' . (\$currentPage + 1) . '\">Next</a></li>';
        }
        
        \$pagination .= '</ul></nav>';
    }
    
    return \$pagination;
}
?>";
            
            file_put_contents('includes/config.php', $config_content);
            
            // Create upload directories
            $directories = ['uploads', 'uploads/audio', 'uploads/files', 'uploads/images'];
            foreach ($directories as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }
            
            $success = 'Database setup completed successfully!';
            $step = 2;
            
        } catch (PDOException $e) {
            $error = 'Database connection failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Goba Hospital Patient Record Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg">
                    <div class="card-header text-center">
                        <h4 class="mb-0">
                            <i class="fas fa-hospital me-2"></i>
                            Goba Hospital Patient Record Management System
                        </h4>
                        <p class="text-muted mb-0">Installation Wizard</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($step === 1): ?>
                            <h5 class="mb-3">Step 1: Database Configuration</h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="db_host" class="form-label">Database Host</label>
                                    <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="db_name" class="form-label">Database Name</label>
                                    <input type="text" class="form-control" id="db_name" name="db_name" value="goba_hospital" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="db_user" class="form-label">Database Username</label>
                                    <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="db_pass" class="form-label">Database Password</label>
                                    <input type="password" class="form-control" id="db_pass" name="db_pass">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="site_url" class="form-label">Site URL</label>
                                    <input type="url" class="form-control" id="site_url" name="site_url" 
                                           value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']); ?>" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-database me-2"></i>
                                    Install Database
                                </button>
                            </form>
                        <?php elseif ($step === 2): ?>
                            <h5 class="mb-3">Step 2: Installation Complete</h5>
                            <div class="text-center">
                                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                <h6>Installation completed successfully!</h6>
                                <p class="text-muted">The Goba Hospital Patient Record Management System has been installed and configured.</p>
                                
                                <div class="alert alert-info">
                                    <h6>Default Login Credentials:</h6>
                                    <ul class="list-unstyled mb-0">
                                        <li><strong>Admin:</strong> admin / admin123</li>
                                        <li><strong>Doctor:</strong> doctor1 / doctor</li>
                                        <li><strong>Patient:</strong> patient1 / patient</li>
                                        <li><strong>Staff:</strong> staff1 / staff</li>
                                        <li><strong>External:</strong> external1 / external</li>
                                    </ul>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="index.php" class="btn btn-primary">
                                        <i class="fas fa-home me-2"></i>
                                        Go to Homepage
                                    </a>
                                    <a href="login.php" class="btn btn-outline-primary">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Go to Login
                                    </a>
                                </div>
                                
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <strong>Important:</strong> Delete install.php for security reasons.
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        &copy; <?php echo date('Y'); ?> Goba Hospital. All rights reserved.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>