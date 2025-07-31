<?php
session_start();
require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $userType = $_SESSION['user_type'];
    header("Location: {$userType}_dashboard.php");
    exit();
}

$userType = $_GET['type'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Goba Hospital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
            margin: 0 20px;
        }
        
        .login-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #e74c3c;
        }
        
        .login-form {
            padding: 2rem;
        }
        
        .user-type-selector {
            margin-bottom: 2rem;
        }
        
        .user-type-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .type-option {
            padding: 10px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .type-option:hover,
        .type-option.active {
            border-color: #3498db;
            background: #f8f9fa;
            color: #3498db;
        }
        
        .type-option i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .form-control.error {
            border-color: #e74c3c;
        }
        
        .login-footer {
            padding: 1rem 2rem;
            background: #f8f9fa;
            text-align: center;
            border-top: 1px solid #ecf0f1;
        }
        
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-hospital-alt"></i>
                <h2>Goba Hospital</h2>
                <p>Patient Record Management System</p>
            </div>
            
            <form id="loginForm" class="login-form">
                <div class="user-type-selector">
                    <label>Select User Type:</label>
                    <div class="user-type-grid">
                        <div class="type-option <?php echo $userType === 'patient' ? 'active' : ''; ?>" data-type="patient">
                            <i class="fas fa-user"></i>
                            <span>Patient</span>
                        </div>
                        <div class="type-option <?php echo $userType === 'doctor' ? 'active' : ''; ?>" data-type="doctor">
                            <i class="fas fa-user-md"></i>
                            <span>Doctor</span>
                        </div>
                        <div class="type-option <?php echo $userType === 'staff' ? 'active' : ''; ?>" data-type="staff">
                            <i class="fas fa-users"></i>
                            <span>Staff</span>
                        </div>
                        <div class="type-option <?php echo $userType === 'admin' ? 'active' : ''; ?>" data-type="admin">
                            <i class="fas fa-cog"></i>
                            <span>Admin</span>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" id="user_type" name="user_type" value="<?php echo htmlspecialchars($userType); ?>">
                
                <div class="form-group">
                    <label for="id_number">ID Number:</label>
                    <input type="text" id="id_number" name="id_number" class="form-control" required 
                           placeholder="National ID, Passport, or Birth Certificate">
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </button>
                </div>
            </form>
            
            <div class="login-footer">
                <p>Need an account? <a href="register.php">Register here</a></p>
                <p><a href="index.html">← Back to Home</a></p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // User type selection
            $('.type-option').on('click', function() {
                $('.type-option').removeClass('active');
                $(this).addClass('active');
                $('#user_type').val($(this).data('type'));
            });
            
            // Set initial user type if provided
            if (!$('#user_type').val() && $('.type-option').length > 0) {
                $('.type-option').first().click();
            }
            
            // Login form submission
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                // Validate form
                let isValid = true;
                $('.form-control[required]').each(function() {
                    if ($(this).val().trim() === '') {
                        $(this).addClass('error');
                        isValid = false;
                    } else {
                        $(this).removeClass('error');
                    }
                });
                
                if (!$('#user_type').val()) {
                    showAlert('Please select a user type.', 'error');
                    return;
                }
                
                if (!isValid) {
                    showAlert('Please fill in all required fields.', 'error');
                    return;
                }
                
                // Submit form via AJAX
                const formData = {
                    user_type: $('#user_type').val(),
                    id_number: $('#id_number').val(),
                    password: $('#password').val(),
                    action: 'login'
                };
                
                $.ajax({
                    url: 'includes/auth.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    beforeSend: function() {
                        showSpinner();
                    },
                    success: function(response) {
                        hideSpinner();
                        if (response.success) {
                            showAlert(response.message, 'success');
                            setTimeout(() => {
                                window.location.href = response.redirect;
                            }, 1500);
                        } else {
                            showAlert(response.message, 'error');
                        }
                    },
                    error: function() {
                        hideSpinner();
                        showAlert('An error occurred. Please try again.', 'error');
                    }
                });
            });
            
            // Utility functions
            function showAlert(message, type) {
                const alertHtml = `<div class="alert alert-${type}">${message}</div>`;
                $('.alert').remove();
                $('.login-form').prepend(alertHtml);
                
                setTimeout(() => {
                    $('.alert').fadeOut();
                }, 5000);
            }
            
            function showSpinner() {
                if ($('.spinner-overlay').length === 0) {
                    $('body').append('<div class="spinner-overlay"><div class="spinner"></div></div>');
                }
            }
            
            function hideSpinner() {
                $('.spinner-overlay').remove();
            }
            
            // Remove error styling on input
            $('.form-control').on('input', function() {
                $(this).removeClass('error');
            });
        });
    </script>
</body>
</html>