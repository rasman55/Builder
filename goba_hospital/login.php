<?php
require_once 'includes/config.php';
startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . $_SESSION['user_type'] . '/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $user_type = sanitizeInput($_POST['user_type']);
    
    if (empty($username) || empty($password) || empty($user_type)) {
        $error = 'All fields are required.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Determine which table to query based on user type
            $table_map = [
                'admin' => 'admin',
                'patient' => 'patient_login',
                'doctor' => 'doctor_login',
                'staff' => 'staff_login',
                'external' => 'external_login'
            ];
            
            if (!isset($table_map[$user_type])) {
                $error = 'Invalid user type.';
            } else {
                $table = $table_map[$user_type];
                
                // Build query based on user type
                if ($user_type === 'admin') {
                    $stmt = $pdo->prepare("SELECT id, username, password FROM admin WHERE username = ?");
                } elseif ($user_type === 'external') {
                    $stmt = $pdo->prepare("SELECT id, username, password FROM external_login WHERE username = ?");
                } else {
                    $stmt = $pdo->prepare("SELECT l.id, l.username, l.password, l.{$user_type}_id as user_id FROM {$table} l WHERE l.username = ?");
                }
                
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = $user_type;
                    
                    if ($user_type !== 'admin' && $user_type !== 'external') {
                        $_SESSION['profile_id'] = $user['user_id'];
                    }
                    
                    // Redirect to appropriate dashboard or specific portal
                    if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
                        header('Location: ' . $_POST['redirect'] . '/dashboard.php');
                    } else {
                        header('Location: ' . $user_type . '/dashboard.php');
                    }
                    exit();
                } else {
                    $error = 'Invalid username or password.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-header text-center">
                        <h4 class="mb-0">
                            <i class="fas fa-hospital me-2"></i>
                            <?php echo SITE_NAME; ?>
                        </h4>
                        <p class="text-muted mb-0">Sign in to your account</p>
                        <?php if (isset($_GET['redirect'])): ?>
                            <div class="alert alert-info mt-2 mb-0 py-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Accessing <?php echo ucfirst($_GET['redirect']); ?> Portal
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <?php if (isset($_GET['redirect'])): ?>
                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="user_type" class="form-label">User Type</label>
                                <select class="form-select" id="user_type" name="user_type" required>
                                    <option value="">Select user type</option>
                                    <option value="admin" <?php echo (isset($_GET['redirect']) && $_GET['redirect'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                    <option value="doctor" <?php echo (isset($_GET['redirect']) && $_GET['redirect'] === 'doctor') ? 'selected' : ''; ?>>Doctor</option>
                                    <option value="patient" <?php echo (isset($_GET['redirect']) && $_GET['redirect'] === 'patient') ? 'selected' : ''; ?>>Patient</option>
                                    <option value="staff" <?php echo (isset($_GET['redirect']) && $_GET['redirect'] === 'staff') ? 'selected' : ''; ?>>Medical Staff</option>
                                    <option value="external" <?php echo (isset($_GET['redirect']) && $_GET['redirect'] === 'external') ? 'selected' : ''; ?>>External Health Office</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a user type.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           placeholder="Enter username" required>
                                </div>
                                <div class="invalid-feedback">
                                    Please enter your username.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Enter password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Please enter your password.
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Sign In
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <h6 class="text-muted mb-3">Demo Accounts</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <button class="btn btn-outline-primary btn-sm w-100" onclick="fillDemo('admin', 'admin', 'admin')">
                                        Admin
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-success btn-sm w-100" onclick="fillDemo('doctor', 'doctor1', 'doctor')">
                                        Doctor
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-info btn-sm w-100" onclick="fillDemo('patient', 'patient1', 'patient')">
                                        Patient
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-warning btn-sm w-100" onclick="fillDemo('staff', 'staff1', 'staff')">
                                        Staff
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-2"></i>
                                Back to Home
                            </a>
                        </div>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Fill demo account details
        function fillDemo(userType, username, password) {
            document.getElementById('user_type').value = userType;
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            
            // Update the redirect hidden field if it exists
            const redirectField = document.querySelector('input[name="redirect"]');
            if (redirectField) {
                redirectField.value = userType;
            }
        }
        
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
                
                // Auto-focus on username field
                document.getElementById('username').focus();
                
                // Add visual feedback for portal-specific access
                const urlParams = new URLSearchParams(window.location.search);
                const redirect = urlParams.get('redirect');
                if (redirect) {
                    const userTypeSelect = document.getElementById('user_type');
                    userTypeSelect.addEventListener('change', function() {
                        if (this.value === redirect) {
                            this.classList.add('border-success');
                            this.classList.remove('border-warning');
                        } else {
                            this.classList.add('border-warning');
                            this.classList.remove('border-success');
                        }
                    });
                }
            }, false);
        })();
    </script>
</body>
</html>