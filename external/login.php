<?php
session_start();
require_once '../config/database.php';

// Check if user is already logged in
if (isset($_SESSION['external_logged_in']) && $_SESSION['external_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $username = DatabaseHelper::sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $error_message = 'Please enter both username and password.';
        } else {
            $db = Database::getInstance();
            
            try {
                // Get external office login details
                $sql = "SELECT * FROM External_health_office_login WHERE username = ? AND status = 'Active'";
                $user = $db->selectOne($sql, [$username]);
                
                if ($user) {
                    // Check if account is locked
                    if ($user['account_locked']) {
                        $lockout_time = new DateTime($user['lockout_time']);
                        $current_time = new DateTime();
                        $lockout_duration = 30; // 30 minutes
                        
                        if ($current_time->getTimestamp() - $lockout_time->getTimestamp() > ($lockout_duration * 60)) {
                            // Unlock account
                            $unlock_sql = "UPDATE External_health_office_login SET account_locked = FALSE, login_attempts = 0, lockout_time = NULL WHERE office_id = ?";
                            $db->update($unlock_sql, [$user['office_id']]);
                        } else {
                            $remaining_time = $lockout_duration - floor(($current_time->getTimestamp() - $lockout_time->getTimestamp()) / 60);
                            $error_message = "Account is locked. Please try again in {$remaining_time} minutes.";
                        }
                    }
                    
                    if (!$user['account_locked']) {
                        // Verify password
                        if (DatabaseHelper::verifyPassword($password, $user['password_hash'])) {
                            // Successful login
                            $_SESSION['external_logged_in'] = true;
                            $_SESSION['external_office_id'] = $user['office_id'];
                            $_SESSION['external_username'] = $user['username'];
                            $_SESSION['external_office_name'] = $user['office_name'];
                            $_SESSION['external_office_type'] = $user['office_type'];
                            $_SESSION['external_region'] = $user['region'];
                            $_SESSION['login_time'] = time();
                            
                            // Reset login attempts and update last login
                            $update_sql = "UPDATE External_health_office_login SET login_attempts = 0, last_login = NOW() WHERE office_id = ?";
                            $db->update($update_sql, [$user['office_id']]);
                            
                            // Log successful login
                            $audit_sql = "INSERT INTO Audit_log (user_type, user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
                            $db->insert($audit_sql, [
                                'External_Office',
                                $user['office_id'],
                                'LOGIN_SUCCESS',
                                $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                            ]);
                            
                            header('Location: dashboard.php');
                            exit();
                        } else {
                            // Invalid password - increment login attempts
                            $attempts = $user['login_attempts'] + 1;
                            $lock_account = $attempts >= 5;
                            
                            $update_sql = "UPDATE External_health_office_login SET login_attempts = ?, account_locked = ?, lockout_time = ? WHERE office_id = ?";
                            $lockout_time = $lock_account ? date('Y-m-d H:i:s') : null;
                            $db->update($update_sql, [$attempts, $lock_account, $lockout_time, $user['office_id']]);
                            
                            if ($lock_account) {
                                $error_message = 'Too many failed attempts. Account has been locked for 30 minutes.';
                            } else {
                                $remaining_attempts = 5 - $attempts;
                                $error_message = "Invalid password. {$remaining_attempts} attempts remaining.";
                            }
                            
                            // Log failed login
                            $audit_sql = "INSERT INTO Audit_log (user_type, user_id, action, ip_address, user_agent, success) VALUES (?, ?, ?, ?, ?, ?)";
                            $db->insert($audit_sql, [
                                'External_Office',
                                $username,
                                'LOGIN_FAILED',
                                $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                                false
                            ]);
                        }
                    }
                } else {
                    $error_message = 'Invalid username or account not found.';
                }
            } catch (Exception $e) {
                $error_message = 'A system error occurred. Please try again later.';
                error_log("External office login error: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>External Health Office Login - Goba Hospital</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/portal.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="portal-body">
    <!-- Navigation -->
    <nav class="portal-nav">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="../index.html">
                    <i class="fas fa-hospital"></i>
                    <span>Goba Hospital</span>
                </a>
            </div>
            <div class="nav-links">
                <a href="../index.html" class="nav-link">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        </div>
    </nav>

    <!-- Login Container -->
    <div class="portal-container">
        <div class="login-wrapper">
            <div class="login-card">
                <div class="login-header">
                    <div class="portal-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h1>External Health Office Portal</h1>
                    <p>Patient referral and inter-hospital communication</p>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form" id="loginForm">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-building"></i>
                            Office Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required 
                            placeholder="Enter your office username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <div class="password-input">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required 
                                placeholder="Enter your password"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember_me">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>

                <div class="login-footer">
                    <div class="register-link">
                        <p>Need access? <a href="../admin/login.php">Contact hospital administration</a></p>
                    </div>
                    
                    <div class="portal-links">
                        <h4>Other Portals</h4>
                        <div class="portal-grid">
                            <a href="../patient/login.php" class="portal-link">
                                <i class="fas fa-user-injured"></i>
                                Patient Portal
                            </a>
                            <a href="../doctor/login.php" class="portal-link">
                                <i class="fas fa-user-md"></i>
                                Doctor Portal
                            </a>
                            <a href="../admin/login.php" class="portal-link">
                                <i class="fas fa-user-cog"></i>
                                Admin Portal
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login Information Panel -->
            <div class="login-info">
                <h3>Welcome to External Health Office Portal</h3>
                <div class="info-features">
                    <div class="feature-item">
                        <i class="fas fa-file-upload"></i>
                        <div>
                            <h4>Patient Information Upload</h4>
                            <p>Upload patient files including PDFs, images, and medical documents.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-paper-plane"></i>
                        <div>
                            <h4>Hospital Referrals</h4>
                            <p>Send patient referrals to authorized hospitals in the network.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-network-wired"></i>
                        <div>
                            <h4>Inter-hospital Communication</h4>
                            <p>Facilitate seamless communication between healthcare facilities.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-folder-open"></i>
                        <div>
                            <h4>Document Management</h4>
                            <p>Secure storage and sharing of medical documents and records.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-route"></i>
                        <div>
                            <h4>Transfer Tracking</h4>
                            <p>Monitor patient transfer status and receive confirmations.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-shield-virus"></i>
                        <div>
                            <h4>Regional Health Coordination</h4>
                            <p>Coordinate regional health initiatives and emergency responses.</p>
                        </div>
                    </div>
                </div>

                <div class="contact-support">
                    <h4>Technical Support</h4>
                    <p>Contact support for assistance with the external health office portal.</p>
                    <div class="support-contacts">
                        <a href="tel:+251-XX-XXX-XXXX" class="support-link">
                            <i class="fas fa-phone"></i>
                            +251-XX-XXX-XXXX
                        </a>
                        <a href="mailto:external@gobahospital.com" class="support-link">
                            <i class="fas fa-envelope"></i>
                            external@gobahospital.com
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Notice -->
    <div class="security-notice">
        <div class="container">
            <div class="notice-content">
                <i class="fas fa-shield-alt"></i>
                <div>
                    <h4>Secure Inter-Hospital Network</h4>
                    <p>All patient transfers and communications are encrypted and logged for compliance.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;

            if (!username || !password) {
                e.preventDefault();
                showAlert('Please fill in all required fields.', 'error');
                return;
            }
        });

        // Show alert function
        function showAlert(message, type) {
            const existingAlert = document.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }

            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
                ${message}
            `;

            const form = document.querySelector('.login-form');
            form.insertBefore(alert, form.firstChild);
        }

        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>