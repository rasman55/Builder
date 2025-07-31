<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$action = $_POST['action'] ?? '';
$pdo = getDatabaseConnection();

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

switch ($action) {
    case 'login':
        handleLogin($pdo);
        break;
    case 'register':
        handleRegistration($pdo);
        break;
    case 'admin_register':
        handleAdminRegistration($pdo);
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleLogin($pdo) {
    $userType = $_POST['user_type'] ?? '';
    $idNumber = $_POST['id_number'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($userType) || empty($idNumber) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Check for external health office special handling
    if ($userType === 'external') {
        // External users have a different authentication flow
        $stmt = $pdo->prepare("
            SELECT id, user_type, first_name, last_name, email, password_hash, is_active
            FROM users 
            WHERE user_type = 'external' AND id_number = ? AND is_active = 1
        ");
    } else {
        // Regular user authentication
        $stmt = $pdo->prepare("
            SELECT id, user_type, first_name, last_name, email, password_hash, is_active
            FROM users 
            WHERE user_type = ? AND id_number = ? AND is_active = 1
        ");
    }
    
    try {
        if ($userType === 'external') {
            $stmt->execute([$idNumber]);
        } else {
            $stmt->execute([$userType, $idNumber]);
        }
        
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            return;
        }
        
        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['login_time'] = time();
        
        // Generate session token
        $sessionToken = bin2hex(random_bytes(32));
        $_SESSION['session_token'] = $sessionToken;
        
        // Store session in database
        $sessionStmt = $pdo->prepare("
            INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent)
            VALUES (?, ?, ?, ?)
        ");
        $sessionStmt->execute([
            $user['id'],
            $sessionToken,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        // Log login activity
        logUserActivity($user['id'], 'user_login');
        
        // Determine redirect based on user type
        $redirectUrl = $user['user_type'] . '_dashboard.php';
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => $redirectUrl,
            'user_type' => $user['user_type']
        ]);
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Login failed. Please try again.']);
    }
}

function handleRegistration($pdo) {
    // Get form data
    $userType = $_POST['user_type'] ?? '';
    $idType = $_POST['id_type'] ?? '';
    $idNumber = $_POST['id_number'] ?? '';
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $dateOfBirth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $password = $_POST['password'] ?? '';
    $address = $_POST['address'] ?? '';
    $emergencyContact = $_POST['emergency_contact'] ?? '';
    
    // Additional fields based on user type
    $specialization = $_POST['specialization'] ?? '';
    $department = $_POST['department'] ?? '';
    $licenseNumber = $_POST['license_number'] ?? '';
    
    // Validate required fields
    $requiredFields = ['user_type', 'id_type', 'id_number', 'first_name', 'last_name', 'password'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '{$field}' is required"]);
            return;
        }
    }
    
    // Validate email format if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    // Check if user already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id_number = ? OR (email = ? AND email IS NOT NULL AND email != '')");
    $checkStmt->execute([$idNumber, $email]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'User with this ID number or email already exists']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $userStmt = $pdo->prepare("
            INSERT INTO users (
                user_type, id_type, id_number, first_name, last_name, email, phone,
                date_of_birth, gender, password_hash, address, emergency_contact,
                specialization, department, license_number
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $userStmt->execute([
            $userType, $idType, $idNumber, $firstName, $lastName, $email, $phone,
            $dateOfBirth ?: null, $gender, $passwordHash, $address, $emergencyContact,
            $specialization ?: null, $department ?: null, $licenseNumber ?: null
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Insert type-specific information
        if ($userType === 'patient') {
            $patientStmt = $pdo->prepare("
                INSERT INTO patients (
                    user_id, blood_type, allergies, medical_conditions, medications,
                    insurance_number, insurance_provider, next_of_kin, next_of_kin_phone
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $patientStmt->execute([
                $userId,
                $_POST['blood_type'] ?? null,
                $_POST['allergies'] ?? null,
                $_POST['medical_conditions'] ?? null,
                $_POST['medications'] ?? null,
                $_POST['insurance_number'] ?? null,
                $_POST['insurance_provider'] ?? null,
                $_POST['next_of_kin'] ?? null,
                $_POST['next_of_kin_phone'] ?? null
            ]);
        } elseif ($userType === 'doctor') {
            $doctorStmt = $pdo->prepare("
                INSERT INTO doctors (
                    user_id, medical_license, years_of_experience, consultation_fee, hospital_id
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $doctorStmt->execute([
                $userId,
                $licenseNumber,
                $_POST['years_of_experience'] ?? null,
                $_POST['consultation_fee'] ?? null,
                $_POST['hospital_id'] ?? null
            ]);
        }
        
        // Log registration activity
        logUserActivity($userId, 'user_registration', 'users', $userId);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! You can now login with your credentials.'
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Registration error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }
}

function handleLogout() {
    if (isset($_SESSION['user_id'])) {
        $pdo = getDatabaseConnection();
        if ($pdo && isset($_SESSION['session_token'])) {
            // Deactivate session in database
            $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = 0 WHERE session_token = ?");
            $stmt->execute([$_SESSION['session_token']]);
            
            // Log logout activity
            logUserActivity($_SESSION['user_id'], 'user_logout');
        }
    }
    
    // Destroy session
    session_destroy();
    
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully',
        'redirect' => 'login.php'
    ]);
}

// Function to check if user is authenticated
function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['session_token']);
}

// Function to check user permissions
function hasPermission($requiredUserType) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userType = $_SESSION['user_type'];
    
    // Admin has access to everything
    if ($userType === 'admin') {
        return true;
    }
    
    // Check if user type matches required type
    if (is_array($requiredUserType)) {
        return in_array($userType, $requiredUserType);
    }
    
    return $userType === $requiredUserType;
}

// Function to get current user info
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'type' => $_SESSION['user_type'],
        'first_name' => $_SESSION['first_name'],
        'last_name' => $_SESSION['last_name'],
        'email' => $_SESSION['email']
    ];
}

// Function to refresh session
function refreshSession() {
    if (isAuthenticated()) {
        $pdo = getDatabaseConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("
                UPDATE user_sessions 
                SET last_activity = CURRENT_TIMESTAMP 
                WHERE session_token = ? AND is_active = 1
            ");
            $stmt->execute([$_SESSION['session_token']]);
        }
    }
}

// Admin registration function (only accessible by admin users)
function handleAdminRegistration($pdo) {
    // Check if user is admin
    if (!isAuthenticated() || $_SESSION['user_type'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Admin access required']);
        return;
    }
    
    $userType = $_POST['user_type'] ?? '';
    $idNumber = $_POST['id_number'] ?? '';
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $dateOfBirth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $password = $_POST['password'] ?? '';
    $idType = $_POST['id_type'] ?? '';
    $address = $_POST['address'] ?? null;
    $emergencyContact = $_POST['emergency_contact'] ?? null;
    
    // Validate required fields
    if (empty($userType) || empty($idNumber) || empty($firstName) || empty($lastName) || empty($password) || empty($idType)) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        return;
    }
    
    // Validate user type
    $allowedTypes = ['patient', 'doctor', 'staff', 'external'];
    if (!in_array($userType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid user type']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Check if ID number already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id_number = ?");
        $stmt->execute([$idNumber]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'ID number already exists']);
            return;
        }
        
        // Check if email already exists (if provided)
        if ($email) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                return;
            }
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Prepare type-specific fields
        $specialization = null;
        $department = null;
        $licenseNumber = null;
        
        if ($userType === 'doctor') {
            $specialization = $_POST['specialization'] ?? null;
            $department = $_POST['department'] ?? null;
            $licenseNumber = $_POST['medical_license'] ?? null;
        } elseif ($userType === 'staff') {
            $specialization = $_POST['staff_specialization'] ?? null;
            $department = $_POST['staff_department'] ?? null;
            $licenseNumber = $_POST['license_number'] ?? null;
        }
        
        // Insert into users table
        $stmt = $pdo->prepare("
            INSERT INTO users (
                user_type, id_number, first_name, last_name, email, phone,
                date_of_birth, gender, password_hash, address, emergency_contact,
                id_type, license_number, specialization, department, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $stmt->execute([
            $userType, $idNumber, $firstName, $lastName, $email, $phone,
            $dateOfBirth ?: null, $gender, $passwordHash, $address, $emergencyContact,
            $idType, $licenseNumber, $specialization, $department
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Insert type-specific data
        if ($userType === 'patient') {
            $stmt = $pdo->prepare("
                INSERT INTO patients (
                    user_id, blood_type, allergies, medical_conditions, medications,
                    insurance_number, insurance_provider, next_of_kin, next_of_kin_phone
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $_POST['blood_type'] ?? null,
                $_POST['allergies'] ?? null,
                $_POST['medical_conditions'] ?? null,
                $_POST['medications'] ?? null,
                $_POST['insurance_number'] ?? null,
                $_POST['insurance_provider'] ?? null,
                $_POST['next_of_kin'] ?? null,
                $_POST['next_of_kin_phone'] ?? null
            ]);
        } elseif ($userType === 'doctor') {
            $stmt = $pdo->prepare("
                INSERT INTO doctors (
                    user_id, medical_license, years_of_experience, consultation_fee, hospital_id
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $_POST['medical_license'] ?? null,
                $_POST['years_of_experience'] ?? null,
                $_POST['consultation_fee'] ?? null,
                'GOBA-HOSP-001'
            ]);
        }
        
        $pdo->commit();
        
        // Log activity
        logUserActivity($_SESSION['user_id'], 'ADMIN_REGISTER_USER', 'users', $userId, null, json_encode([
            'registered_user' => $firstName . ' ' . $lastName,
            'user_type' => $userType,
            'id_number' => $idNumber
        ]));
        
        // Send email notification if requested and email provided
        if ($_POST['send_credentials'] ?? false && $email) {
            // TODO: Implement email sending
            // sendCredentialsEmail($email, $firstName, $idNumber, $password);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'User registered successfully',
            'user_id' => $userId
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
}
?>