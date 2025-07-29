<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_type = sanitize_input($_POST['user_type']);
    $ssn = sanitize_input($_POST['ssn']);
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate required fields
    if (empty($ssn) || empty($first_name) || empty($last_name) || empty($email) || 
        empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $db = Database::getInstance();
        
        // Check if username already exists
        $existing_user = $db->fetchOne("SELECT id FROM {$user_type}_login WHERE username = ?", [$username]);
        if ($existing_user) {
            $error = 'Username already exists. Please choose a different username.';
        } else {
            // Check if SSN already exists
            $existing_ssn = $db->fetchOne("SELECT ssn FROM {$user_type}s WHERE ssn = ?", [$ssn]);
            if ($existing_ssn) {
                $error = 'SSN/NID already registered. Please use a different SSN/NID.';
            } else {
                try {
                    $db->getConnection()->beginTransaction();
                    
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert into main table based on user type
                    switch ($user_type) {
                        case 'patient':
                            $date_of_birth = sanitize_input($_POST['date_of_birth']);
                            $gender = sanitize_input($_POST['gender']);
                            $address = sanitize_input($_POST['address']);
                            
                            $db->query("
                                INSERT INTO patients (ssn, first_name, last_name, date_of_birth, gender, email, phone, address) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ", [$ssn, $first_name, $last_name, $date_of_birth, $gender, $email, $phone, $address]);
                            
                            // Insert into login table
                            $db->query("
                                INSERT INTO patient_login (ssn, username, password, email) 
                                VALUES (?, ?, ?, ?)
                            ", [$ssn, $username, $hashed_password, $email]);
                            break;
                            
                        case 'doctor':
                            $specialization = sanitize_input($_POST['specialization']);
                            $license_number = sanitize_input($_POST['license_number']);
                            $hospital_id = sanitize_input($_POST['hospital_id']);
                            $experience_years = sanitize_input($_POST['experience_years']);
                            
                            $db->query("
                                INSERT INTO doctors (ssn, first_name, last_name, specialization, license_number, email, phone, hospital_id, experience_years) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ", [$ssn, $first_name, $last_name, $specialization, $license_number, $email, $phone, $hospital_id, $experience_years]);
                            
                            // Insert into login table
                            $db->query("
                                INSERT INTO doctor_login (ssn, username, password, email) 
                                VALUES (?, ?, ?, ?)
                            ", [$ssn, $username, $hashed_password, $email]);
                            break;
                            
                        case 'staff':
                            $position = sanitize_input($_POST['position']);
                            $department = sanitize_input($_POST['department']);
                            $hospital_id = sanitize_input($_POST['hospital_id']);
                            
                            $db->query("
                                INSERT INTO medical_staff (ssn, first_name, last_name, position, email, phone, hospital_id, department) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ", [$ssn, $first_name, $last_name, $position, $email, $phone, $hospital_id, $department]);
                            
                            // Insert into login table
                            $db->query("
                                INSERT INTO staff_login (ssn, username, password, email) 
                                VALUES (?, ?, ?, ?)
                            ", [$ssn, $username, $hashed_password, $email]);
                            break;
                    }
                    
                    $db->getConnection()->commit();
                    $success = 'Registration successful! You can now login with your credentials.';
                    
                } catch (Exception $e) {
                    $db->getConnection()->rollBack();
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

// Get hospitals for dropdown
$db = Database::getInstance();
$hospitals = $db->fetchAll("SELECT id, name FROM hospitals ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-container">
    <div class="login-card" style="max-width: 600px;">
        <div class="login-header">
            <i class="fas fa-user-plus fa-3x mb-3"></i>
            <h3>User Registration</h3>
            <p class="mb-0">Goba Hospital Management System</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" data-validate>
                <!-- User Type Selection -->
                <div class="mb-3">
                    <label for="user_type" class="form-label">
                        <i class="fas fa-users me-2"></i>Register as
                    </label>
                    <select class="form-control" id="user_type" name="user_type" required>
                        <option value="">Select user type</option>
                        <option value="patient" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'patient') ? 'selected' : ''; ?>>Patient</option>
                        <option value="doctor" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'doctor') ? 'selected' : ''; ?>>Doctor</option>
                        <option value="staff" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'staff') ? 'selected' : ''; ?>>Medical Staff</option>
                    </select>
                </div>
                
                <!-- Basic Information -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="ssn" class="form-label">
                                <i class="fas fa-id-card me-2"></i>SSN/NID/Passport
                            </label>
                            <input type="text" class="form-control" id="ssn" name="ssn" 
                                   value="<?php echo isset($_POST['ssn']) ? htmlspecialchars($_POST['ssn']) : ''; ?>"
                                   required minlength="5" maxlength="50">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-2"></i>Username
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   required minlength="3" maxlength="50">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">
                                <i class="fas fa-user me-2"></i>First Name
                            </label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="last_name" class="form-label">
                                <i class="fas fa-user me-2"></i>Last Name
                            </label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                   required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Email
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone me-2"></i>Phone
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                   required>
                        </div>
                    </div>
                </div>
                
                <!-- Patient-specific fields -->
                <div id="patient-fields" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label">
                                    <i class="fas fa-calendar me-2"></i>Date of Birth
                                </label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gender" class="form-label">
                                    <i class="fas fa-venus-mars me-2"></i>Gender
                                </label>
                                <select class="form-control" id="gender" name="gender">
                                    <option value="">Select gender</option>
                                    <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">
                            <i class="fas fa-map-marker-alt me-2"></i>Address
                        </label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>
                </div>
                
                <!-- Doctor-specific fields -->
                <div id="doctor-fields" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="specialization" class="form-label">
                                    <i class="fas fa-stethoscope me-2"></i>Specialization
                                </label>
                                <input type="text" class="form-control" id="specialization" name="specialization" 
                                       value="<?php echo isset($_POST['specialization']) ? htmlspecialchars($_POST['specialization']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="license_number" class="form-label">
                                    <i class="fas fa-certificate me-2"></i>License Number
                                </label>
                                <input type="text" class="form-control" id="license_number" name="license_number" 
                                       value="<?php echo isset($_POST['license_number']) ? htmlspecialchars($_POST['license_number']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="hospital_id" class="form-label">
                                    <i class="fas fa-hospital me-2"></i>Hospital
                                </label>
                                <select class="form-control" id="hospital_id" name="hospital_id">
                                    <option value="">Select hospital</option>
                                    <?php foreach ($hospitals as $hospital): ?>
                                        <option value="<?php echo $hospital['id']; ?>" 
                                                <?php echo (isset($_POST['hospital_id']) && $_POST['hospital_id'] == $hospital['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($hospital['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="experience_years" class="form-label">
                                    <i class="fas fa-clock me-2"></i>Experience (Years)
                                </label>
                                <input type="number" class="form-control" id="experience_years" name="experience_years" 
                                       value="<?php echo isset($_POST['experience_years']) ? htmlspecialchars($_POST['experience_years']) : ''; ?>"
                                       min="0" max="50">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Staff-specific fields -->
                <div id="staff-fields" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="position" class="form-label">
                                    <i class="fas fa-user-tie me-2"></i>Position
                                </label>
                                <input type="text" class="form-control" id="position" name="position" 
                                       value="<?php echo isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department" class="form-label">
                                    <i class="fas fa-building me-2"></i>Department
                                </label>
                                <input type="text" class="form-control" id="department" name="department" 
                                       value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="staff_hospital_id" class="form-label">
                            <i class="fas fa-hospital me-2"></i>Hospital
                        </label>
                        <select class="form-control" id="staff_hospital_id" name="hospital_id">
                            <option value="">Select hospital</option>
                            <?php foreach ($hospitals as $hospital): ?>
                                <option value="<?php echo $hospital['id']; ?>" 
                                        <?php echo (isset($_POST['hospital_id']) && $_POST['hospital_id'] == $hospital['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($hospital['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Password fields -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" 
                                       required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Confirm Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="fas fa-user-plus me-2"></i>Register
                </button>
            </form>
            
            <div class="text-center">
                <a href="index.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
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

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const password = document.getElementById('confirm_password');
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

        // Show/hide fields based on user type
        document.getElementById('user_type').addEventListener('change', function() {
            const userType = this.value;
            
            // Hide all specific fields
            document.getElementById('patient-fields').style.display = 'none';
            document.getElementById('doctor-fields').style.display = 'none';
            document.getElementById('staff-fields').style.display = 'none';
            
            // Show relevant fields
            if (userType === 'patient') {
                document.getElementById('patient-fields').style.display = 'block';
            } else if (userType === 'doctor') {
                document.getElementById('doctor-fields').style.display = 'block';
            } else if (userType === 'staff') {
                document.getElementById('staff-fields').style.display = 'block';
            }
        });

        // Trigger change event on page load if user type is selected
        if (document.getElementById('user_type').value) {
            document.getElementById('user_type').dispatchEvent(new Event('change'));
        }
    </script>
</body>
</html>