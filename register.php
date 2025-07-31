<?php
session_start();
require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $userType = $_SESSION['user_type'];
    header("Location: {$userType}_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Goba Hospital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .register-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
        }
        
        .register-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .register-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #e74c3c;
        }
        
        .register-form {
            padding: 2rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .user-type-selector {
            margin-bottom: 2rem;
        }
        
        .user-type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .type-option {
            padding: 15px;
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
        
        .conditional-fields {
            display: none;
        }
        
        .conditional-fields.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .register-footer {
            padding: 1rem 2rem;
            background: #f8f9fa;
            text-align: center;
            border-top: 1px solid #ecf0f1;
        }
        
        .required {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="register-page">
        <div class="register-container">
            <div class="register-header">
                <i class="fas fa-hospital-alt"></i>
                <h2>Goba Hospital</h2>
                <p>Register for Patient Record Management System</p>
            </div>
            
            <form id="registerForm" class="register-form">
                <!-- User Type Selection -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user-tag"></i>
                        Select Account Type <span class="required">*</span>
                    </div>
                    <div class="user-type-grid">
                        <div class="type-option" data-type="patient">
                            <i class="fas fa-user"></i>
                            <span>Patient</span>
                        </div>
                        <div class="type-option" data-type="doctor">
                            <i class="fas fa-user-md"></i>
                            <span>Doctor</span>
                        </div>
                        <div class="type-option" data-type="staff">
                            <i class="fas fa-users"></i>
                            <span>Medical Staff</span>
                        </div>
                        <div class="type-option" data-type="external">
                            <i class="fas fa-hospital"></i>
                            <span>External Office</span>
                        </div>
                    </div>
                    <input type="hidden" id="user_type" name="user_type" required>
                </div>

                <!-- Personal Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user"></i>
                        Personal Information
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name <span class="required">*</span>:</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name <span class="required">*</span>:</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_type">ID Type <span class="required">*</span>:</label>
                            <select id="id_type" name="id_type" class="form-control" required>
                                <option value="">Select ID Type</option>
                                <option value="national_id">National ID</option>
                                <option value="passport">Passport</option>
                                <option value="birth_certificate">Birth Certificate</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="id_number">ID Number <span class="required">*</span>:</label>
                            <input type="text" id="id_number" name="id_number" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number:</label>
                            <input type="tel" id="phone" name="phone" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth:</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender:</label>
                            <select id="gender" name="gender" class="form-control">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address:</label>
                        <textarea id="address" name="address" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="emergency_contact">Emergency Contact:</label>
                        <input type="text" id="emergency_contact" name="emergency_contact" class="form-control" 
                               placeholder="Name and phone number">
                    </div>
                </div>

                <!-- Patient-Specific Fields -->
                <div id="patient-fields" class="form-section conditional-fields">
                    <div class="section-title">
                        <i class="fas fa-heartbeat"></i>
                        Medical Information
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="blood_type">Blood Type:</label>
                            <select id="blood_type" name="blood_type" class="form-control">
                                <option value="">Select Blood Type</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="insurance_provider">Insurance Provider:</label>
                            <input type="text" id="insurance_provider" name="insurance_provider" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="insurance_number">Insurance Number:</label>
                        <input type="text" id="insurance_number" name="insurance_number" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="allergies">Known Allergies:</label>
                        <textarea id="allergies" name="allergies" class="form-control" rows="2" 
                                  placeholder="List any known allergies..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="medical_conditions">Existing Medical Conditions:</label>
                        <textarea id="medical_conditions" name="medical_conditions" class="form-control" rows="2" 
                                  placeholder="List any existing medical conditions..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="medications">Current Medications:</label>
                        <textarea id="medications" name="medications" class="form-control" rows="2" 
                                  placeholder="List current medications..."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="next_of_kin">Next of Kin:</label>
                            <input type="text" id="next_of_kin" name="next_of_kin" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="next_of_kin_phone">Next of Kin Phone:</label>
                            <input type="tel" id="next_of_kin_phone" name="next_of_kin_phone" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Doctor-Specific Fields -->
                <div id="doctor-fields" class="form-section conditional-fields">
                    <div class="section-title">
                        <i class="fas fa-stethoscope"></i>
                        Professional Information
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="license_number">Medical License Number <span class="required">*</span>:</label>
                            <input type="text" id="license_number" name="license_number" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="specialization">Specialization:</label>
                            <input type="text" id="specialization" name="specialization" class="form-control" 
                                   placeholder="e.g., Cardiology, Pediatrics">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="department">Department:</label>
                            <input type="text" id="department" name="department" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="years_of_experience">Years of Experience:</label>
                            <input type="number" id="years_of_experience" name="years_of_experience" 
                                   class="form-control" min="0" max="50">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="consultation_fee">Consultation Fee (ETB):</label>
                            <input type="number" id="consultation_fee" name="consultation_fee" 
                                   class="form-control" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="hospital_id">Hospital ID:</label>
                            <input type="text" id="hospital_id" name="hospital_id" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Staff-Specific Fields -->
                <div id="staff-fields" class="form-section conditional-fields">
                    <div class="section-title">
                        <i class="fas fa-id-badge"></i>
                        Staff Information
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="staff_department">Department:</label>
                            <input type="text" id="staff_department" name="department" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="staff_position">Position/Role:</label>
                            <input type="text" id="staff_position" name="specialization" class="form-control" 
                                   placeholder="e.g., Nurse, Lab Technician">
                        </div>
                    </div>
                </div>

                <!-- Password Section -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-lock"></i>
                        Account Security
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password <span class="required">*</span>:</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                            <div class="password-strength" id="passwordStrength"></div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password <span class="required">*</span>:</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-user-plus"></i>
                    Register Account
                </button>
            </form>
            
            <div class="register-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
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
                
                const userType = $(this).data('type');
                $('#user_type').val(userType);
                
                // Show/hide conditional fields
                $('.conditional-fields').removeClass('show');
                
                if (userType === 'patient') {
                    $('#patient-fields').addClass('show');
                } else if (userType === 'doctor') {
                    $('#doctor-fields').addClass('show');
                    $('#license_number').attr('required', true);
                } else if (userType === 'staff') {
                    $('#staff-fields').addClass('show');
                }
                
                // Reset required attributes
                $('.conditional-fields input, .conditional-fields select').removeAttr('required');
                if (userType === 'doctor') {
                    $('#license_number').attr('required', true);
                }
            });
            
            // Password strength checker
            $('#password').on('keyup', function() {
                const password = $(this).val();
                const strength = checkPasswordStrength(password);
                updatePasswordStrength(strength);
            });
            
            // Registration form submission
            $('#registerForm').on('submit', function(e) {
                e.preventDefault();
                
                // Validate form
                if (!validateForm()) {
                    return;
                }
                
                // Password confirmation check
                if ($('#password').val() !== $('#confirm_password').val()) {
                    showAlert('Passwords do not match.', 'error');
                    return;
                }
                
                // Submit form via AJAX
                const formData = new FormData(this);
                formData.append('action', 'register');
                
                $.ajax({
                    url: 'includes/auth.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    beforeSend: function() {
                        showSpinner();
                    },
                    success: function(response) {
                        hideSpinner();
                        if (response.success) {
                            showAlert(response.message, 'success');
                            $('#registerForm')[0].reset();
                            $('.type-option').removeClass('active');
                            $('.conditional-fields').removeClass('show');
                            
                            // Redirect to login after success
                            setTimeout(() => {
                                window.location.href = 'login.php';
                            }, 2000);
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
            
            function validateForm() {
                let isValid = true;
                
                // Check required fields
                $('#registerForm .form-control[required]').each(function() {
                    if ($(this).val().trim() === '') {
                        $(this).addClass('error');
                        isValid = false;
                    } else {
                        $(this).removeClass('error');
                    }
                });
                
                // Check user type selection
                if (!$('#user_type').val()) {
                    showAlert('Please select an account type.', 'error');
                    return false;
                }
                
                if (!isValid) {
                    showAlert('Please fill in all required fields.', 'error');
                }
                
                return isValid;
            }
            
            function checkPasswordStrength(password) {
                let score = 0;
                if (password.length >= 8) score++;
                if (/[a-z]/.test(password)) score++;
                if (/[A-Z]/.test(password)) score++;
                if (/[0-9]/.test(password)) score++;
                if (/[^a-zA-Z0-9]/.test(password)) score++;
                return score;
            }
            
            function updatePasswordStrength(score) {
                const indicator = $('#passwordStrength');
                const levels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
                const colors = ['#e74c3c', '#e67e22', '#f39c12', '#2ecc71', '#27ae60'];
                
                indicator.text(levels[score - 1] || 'Very Weak');
                indicator.css('color', colors[score - 1] || colors[0]);
            }
            
            function showAlert(message, type) {
                const alertHtml = `<div class="alert alert-${type}">${message}</div>`;
                $('.alert').remove();
                $('.register-form').prepend(alertHtml);
                
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