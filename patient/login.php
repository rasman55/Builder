<?php
session_start();
require_once '../config/database.php';

// Check if user is already logged in
if (isset($_SESSION['patient_logged_in']) && $_SESSION['patient_logged_in'] === true) {
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
                // Get patient login details with patient information
                $sql = "SELECT pl.*, p.first_name, p.last_name, p.email, p.status 
                        FROM Patient_login pl 
                        JOIN Patient p ON pl.patient_ssn = p.SSN 
                        WHERE pl.username = ? AND p.status = 'Active'";
                
                $user = $db->selectOne($sql, [$username]);
                
                if ($user) {
                    // Check if account is locked
                    if ($user['account_locked']) {
                        $lockout_time = new DateTime($user['lockout_time']);
                        $current_time = new DateTime();
                        $lockout_duration = 30; // 30 minutes
                        
                        if ($current_time->getTimestamp() - $lockout_time->getTimestamp() > ($lockout_duration * 60)) {
                            // Unlock account
                            $unlock_sql = "UPDATE Patient_login SET account_locked = FALSE, login_attempts = 0, lockout_time = NULL WHERE login_id = ?";
                            $db->update($unlock_sql, [$user['login_id']]);
                        } else {
                            $remaining_time = $lockout_duration - floor(($current_time->getTimestamp() - $lockout_time->getTimestamp()) / 60);
                            $error_message = "Account is locked. Please try again in {$remaining_time} minutes.";
                        }
                    }
                    
                    if (!$user['account_locked']) {
                        // Verify password
                        if (DatabaseHelper::verifyPassword($password, $user['password_hash'])) {
                            // Successful login
                            $_SESSION['patient_logged_in'] = true;
                            $_SESSION['patient_ssn'] = $user['patient_ssn'];
                            $_SESSION['patient_username'] = $user['username'];
                            $_SESSION['patient_name'] = $user['first_name'] . ' ' . $user['last_name'];
                            $_SESSION['login_time'] = time();
                            
                            // Reset login attempts and update last login
                            $update_sql = "UPDATE Patient_login SET login_attempts = 0, last_login = NOW() WHERE login_id = ?";
                            $db->update($update_sql, [$user['login_id']]);
                            
                            // Log successful login
                            $audit_sql = "INSERT INTO Audit_log (user_type, user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
                            $db->insert($audit_sql, [
                                'Patient',
                                $user['patient_ssn'],
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
                            
                            $update_sql = "UPDATE Patient_login SET login_attempts = ?, account_locked = ?, lockout_time = ? WHERE login_id = ?";
                            $lockout_time = $lock_account ? date('Y-m-d H:i:s') : null;
                            $db->update($update_sql, [$attempts, $lock_account, $lockout_time, $user['login_id']]);
                            
                            if ($lock_account) {
                                $error_message = 'Too many failed attempts. Account has been locked for 30 minutes.';
                            } else {
                                $remaining_attempts = 5 - $attempts;
                                $error_message = "Invalid password. {$remaining_attempts} attempts remaining.";
                            }
                            
                            // Log failed login
                            $audit_sql = "INSERT INTO Audit_log (user_type, user_id, action, ip_address, user_agent, success) VALUES (?, ?, ?, ?, ?, ?)";
                            $db->insert($audit_sql, [
                                'Patient',
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
                error_log("Patient login error: " . $e->getMessage());
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
    <title>Patient Login - Goba Hospital</title>
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
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <h1>Patient Portal</h1>
                    <p>Access your medical records and manage your healthcare</p>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form" id="loginForm">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i>
                            Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required 
                            placeholder="Enter your username"
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
                        <p>Don't have an account? <a href="register.php">Contact hospital administration</a></p>
                    </div>
                    
                    <div class="portal-links">
                        <h4>Other Portals</h4>
                        <div class="portal-grid">
                            <a href="../doctor/login.php" class="portal-link">
                                <i class="fas fa-user-md"></i>
                                Doctor Portal
                            </a>
                            <a href="../staff/login.php" class="portal-link">
                                <i class="fas fa-user-nurse"></i>
                                Staff Portal
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
                <h3>Welcome to Patient Portal</h3>
                <div class="info-features">
                    <div class="feature-item">
                        <i class="fas fa-clipboard-list"></i>
                        <div>
                            <h4>Medical Records</h4>
                            <p>Access your complete medical history, including consultations, diagnoses, and treatments.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-search"></i>
                        <div>
                            <h4>Advanced Search</h4>
                            <p>Quickly find specific medical information using our powerful search functionality.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-calendar-alt"></i>
                        <div>
                            <h4>Appointment History</h4>
                            <p>View past and upcoming appointments with detailed information.</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-credit-card"></i>
                        <div>
                            <h4>Payment Management</h4>
                            <p>View billing history and make payments through various Ethiopian banking options.</p>
                        </div>
                    </div>
                </div>

                <div class="contact-support">
                    <h4>Need Help?</h4>
                    <p>Contact our support team for assistance with your account.</p>
                    <div class="support-contacts">
                        <a href="tel:+251-XX-XXX-XXXX" class="support-link">
                            <i class="fas fa-phone"></i>
                            +251-XX-XXX-XXXX
                        </a>
                        <a href="mailto:support@gobahospital.com" class="support-link">
                            <i class="fas fa-envelope"></i>
                            support@gobahospital.com
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
                    <h4>Your Privacy is Protected</h4>
                    <p>All medical information is encrypted and protected according to international healthcare privacy standards.</p>
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

            if (username.length < 3) {
                e.preventDefault();
                showAlert('Username must be at least 3 characters long.', 'error');
                return;
            }

            if (password.length < 6) {
                e.preventDefault();
                showAlert('Password must be at least 6 characters long.', 'error');
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

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }

        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Handle Enter key navigation
        document.getElementById('username').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('password').focus();
            }
        });
    </script>
</body>
</html>