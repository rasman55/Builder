<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check admin authentication
if (!isAuthenticated() || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php?type=admin');
    exit();
}

$pdo = getDatabaseConnection();
$currentUser = getCurrentUser();

// Fetch system statistics
$stats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE user_type = 'patient' AND is_active = TRUE) as total_patients,
        (SELECT COUNT(*) FROM users WHERE user_type = 'doctor' AND is_active = TRUE) as total_doctors,
        (SELECT COUNT(*) FROM users WHERE user_type = 'staff' AND is_active = TRUE) as total_staff,
        (SELECT COUNT(*) FROM medical_records) as total_records,
        (SELECT COUNT(*) FROM medical_records WHERE DATE(date_time) = CURDATE()) as today_records,
        (SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE()) as today_appointments,
        (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as new_users_today,
        (SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURDATE()) as today_activities
")->fetch(PDO::FETCH_ASSOC);

// Fetch recent users
$recentUsers = $pdo->query("
    SELECT id, user_type, id_number, first_name, last_name, email, created_at, is_active
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent activities
$recentActivities = $pdo->query("
    SELECT al.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.user_type
    FROM audit_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 15
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch system settings
$systemSettings = $pdo->query("SELECT * FROM system_settings ORDER BY setting_key")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all users for management
$allUsers = $pdo->query("
    SELECT u.*, 
           p.blood_type, p.allergies, p.insurance_provider,
           d.medical_license, d.specialization, d.years_of_experience
    FROM users u
    LEFT JOIN patients p ON u.id = p.user_id
    LEFT JOIN doctors d ON u.id = d.user_id
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Goba Hospital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <!-- Admin Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-hospital"></i> Admin Panel</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="#dashboard" class="nav-item active" data-section="dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="#register-user" class="nav-item" data-section="register-user">
                    <i class="fas fa-user-plus"></i> Register New User
                </a>
                <a href="#manage-users" class="nav-item" data-section="manage-users">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="#system-settings" class="nav-item" data-section="system-settings">
                    <i class="fas fa-cogs"></i> System Settings
                </a>
                <a href="#audit-logs" class="nav-item" data-section="audit-logs">
                    <i class="fas fa-history"></i> Audit Logs
                </a>
                <a href="#reports" class="nav-item" data-section="reports">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a href="#backup" class="nav-item" data-section="backup">
                    <i class="fas fa-database"></i> Backup & Restore
                </a>
                <div class="sidebar-bottom">
                    <a href="#profile" class="nav-item" data-section="profile">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="#" onclick="logout()" class="nav-item logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <button class="sidebar-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>Admin Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="admin-info">
                        <span>Welcome, <?php echo htmlspecialchars($currentUser['first_name']); ?></span>
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Dashboard Section -->
                <div id="dashboard" class="content-section active">
                    <div class="section-header">
                        <h2>System Overview</h2>
                        <p>Hospital management system statistics and recent activity</p>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="stats-grid">
                        <div class="stat-card patients">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_patients']; ?></h3>
                                <p>Total Patients</p>
                            </div>
                        </div>
                        <div class="stat-card doctors">
                            <div class="stat-icon">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_doctors']; ?></h3>
                                <p>Total Doctors</p>
                            </div>
                        </div>
                        <div class="stat-card staff">
                            <div class="stat-icon">
                                <i class="fas fa-user-nurse"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_staff']; ?></h3>
                                <p>Total Staff</p>
                            </div>
                        </div>
                        <div class="stat-card records">
                            <div class="stat-icon">
                                <i class="fas fa-file-medical"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_records']; ?></h3>
                                <p>Medical Records</p>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Activity -->
                    <div class="dashboard-row">
                        <div class="dashboard-card">
                            <h3>Today's Activity</h3>
                            <div class="activity-stats">
                                <div class="activity-item">
                                    <i class="fas fa-file-medical"></i>
                                    <span><?php echo $stats['today_records']; ?> Records Created</span>
                                </div>
                                <div class="activity-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <span><?php echo $stats['today_appointments']; ?> Appointments</span>
                                </div>
                                <div class="activity-item">
                                    <i class="fas fa-user-plus"></i>
                                    <span><?php echo $stats['new_users_today']; ?> New Users</span>
                                </div>
                                <div class="activity-item">
                                    <i class="fas fa-activity"></i>
                                    <span><?php echo $stats['today_activities']; ?> System Activities</span>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Users -->
                        <div class="dashboard-card">
                            <h3>Recent Users</h3>
                            <div class="recent-users">
                                <?php foreach ($recentUsers as $user): ?>
                                <div class="user-item">
                                    <div class="user-info">
                                        <span class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                                        <span class="user-type badge badge-<?php echo $user['user_type']; ?>"><?php echo ucfirst($user['user_type']); ?></span>
                                    </div>
                                    <span class="user-date"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="dashboard-card full-width">
                        <h3>Recent System Activities</h3>
                        <div class="activity-log">
                            <?php foreach ($recentActivities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-<?php echo getActivityIcon($activity['action']); ?>"></i>
                                </div>
                                <div class="activity-details">
                                    <p><strong><?php echo htmlspecialchars($activity['user_name']); ?></strong> 
                                       <?php echo formatActivityAction($activity['action']); ?>
                                       <?php if ($activity['table_name']): ?>in <?php echo ucfirst(str_replace('_', ' ', $activity['table_name'])); ?><?php endif; ?>
                                    </p>
                                    <span class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Register New User Section -->
                <div id="register-user" class="content-section">
                    <div class="section-header">
                        <h2>Register New User</h2>
                        <p>Add new patients, doctors, staff, or external users to the system</p>
                    </div>

                    <form id="adminRegisterForm" class="admin-form">
                        <input type="hidden" name="action" value="admin_register">
                        
                        <!-- User Type Selection -->
                        <div class="form-section">
                            <h3>Account Type</h3>
                            <div class="user-type-grid">
                                <label class="type-card">
                                    <input type="radio" name="user_type" value="patient" required>
                                    <div class="card-content">
                                        <i class="fas fa-user"></i>
                                        <h4>Patient</h4>
                                        <p>Register new patient</p>
                                    </div>
                                </label>
                                <label class="type-card">
                                    <input type="radio" name="user_type" value="doctor" required>
                                    <div class="card-content">
                                        <i class="fas fa-user-md"></i>
                                        <h4>Doctor</h4>
                                        <p>Register medical doctor</p>
                                    </div>
                                </label>
                                <label class="type-card">
                                    <input type="radio" name="user_type" value="staff" required>
                                    <div class="card-content">
                                        <i class="fas fa-user-nurse"></i>
                                        <h4>Staff</h4>
                                        <p>Register medical staff</p>
                                    </div>
                                </label>
                                <label class="type-card">
                                    <input type="radio" name="user_type" value="external" required>
                                    <div class="card-content">
                                        <i class="fas fa-hospital"></i>
                                        <h4>External</h4>
                                        <p>External health office</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div class="form-section">
                            <h3>Personal Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="id_number">ID Number *</label>
                                    <input type="text" id="id_number" name="id_number" required>
                                </div>
                                <div class="form-group">
                                    <label for="id_type">ID Type *</label>
                                    <select id="id_type" name="id_type" required>
                                        <option value="">Select ID Type</option>
                                        <option value="national_id">National ID</option>
                                        <option value="passport">Passport</option>
                                        <option value="birth_certificate">Birth Certificate</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email">
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="tel" id="phone" name="phone">
                                </div>
                                <div class="form-group">
                                    <label for="date_of_birth">Date of Birth</label>
                                    <input type="date" id="date_of_birth" name="date_of_birth">
                                </div>
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Doctor Specific Fields -->
                        <div id="doctor-fields" class="form-section conditional-fields" style="display: none;">
                            <h3>Doctor Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="medical_license">Medical License *</label>
                                    <input type="text" id="medical_license" name="medical_license">
                                </div>
                                <div class="form-group">
                                    <label for="specialization">Specialization</label>
                                    <input type="text" id="specialization" name="specialization">
                                </div>
                                <div class="form-group">
                                    <label for="department">Department</label>
                                    <input type="text" id="department" name="department">
                                </div>
                                <div class="form-group">
                                    <label for="years_of_experience">Years of Experience</label>
                                    <input type="number" id="years_of_experience" name="years_of_experience" min="0">
                                </div>
                                <div class="form-group">
                                    <label for="consultation_fee">Consultation Fee (ETB)</label>
                                    <input type="number" id="consultation_fee" name="consultation_fee" step="0.01" min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Patient Specific Fields -->
                        <div id="patient-fields" class="form-section conditional-fields" style="display: none;">
                            <h3>Patient Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="blood_type">Blood Type</label>
                                    <select id="blood_type" name="blood_type">
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
                                    <label for="emergency_contact">Emergency Contact</label>
                                    <input type="text" id="emergency_contact" name="emergency_contact">
                                </div>
                                <div class="form-group">
                                    <label for="insurance_provider">Insurance Provider</label>
                                    <input type="text" id="insurance_provider" name="insurance_provider">
                                </div>
                                <div class="form-group">
                                    <label for="insurance_number">Insurance Number</label>
                                    <input type="text" id="insurance_number" name="insurance_number">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="allergies">Known Allergies</label>
                                <textarea id="allergies" name="allergies" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="medical_conditions">Medical Conditions</label>
                                <textarea id="medical_conditions" name="medical_conditions" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Staff Specific Fields -->
                        <div id="staff-fields" class="form-section conditional-fields" style="display: none;">
                            <h3>Staff Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="staff_specialization">Role/Specialization</label>
                                    <input type="text" id="staff_specialization" name="staff_specialization" placeholder="e.g., Registered Nurse, Lab Technician">
                                </div>
                                <div class="form-group">
                                    <label for="staff_department">Department</label>
                                    <input type="text" id="staff_department" name="staff_department">
                                </div>
                                <div class="form-group">
                                    <label for="license_number">License Number</label>
                                    <input type="text" id="license_number" name="license_number">
                                </div>
                            </div>
                        </div>

                        <!-- Account Security -->
                        <div class="form-section">
                            <h3>Account Security</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="password">Password *</label>
                                    <input type="password" id="password" name="password" required minlength="8">
                                    <div class="password-strength">
                                        <div class="strength-bar"></div>
                                        <span class="strength-text">Password strength</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm Password *</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="send_credentials" checked>
                                    Send login credentials to user's email
                                </label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Register User
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Manage Users Section -->
                <div id="manage-users" class="content-section">
                    <div class="section-header">
                        <h2>Manage Users</h2>
                        <p>View, edit, and manage all system users</p>
                    </div>

                    <div class="table-controls">
                        <div class="search-filter">
                            <input type="text" id="userSearch" placeholder="Search users...">
                            <select id="userTypeFilter">
                                <option value="">All User Types</option>
                                <option value="patient">Patients</option>
                                <option value="doctor">Doctors</option>
                                <option value="staff">Staff</option>
                                <option value="external">External</option>
                            </select>
                            <select id="statusFilter">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" onclick="exportUsers()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>

                    <div class="table-container">
                        <table class="data-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allUsers as $user): ?>
                                <tr data-user-id="<?php echo $user['id']; ?>">
                                    <td><?php echo htmlspecialchars($user['id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><span class="badge badge-<?php echo $user['user_type']; ?>"><?php echo ucfirst($user['user_type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td class="actions">
                                        <button class="btn-action edit" onclick="editUser(<?php echo $user['id']; ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-action toggle" onclick="toggleUserStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 'false' : 'true'; ?>)" title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo $user['is_active'] ? 'times' : 'check'; ?>"></i>
                                        </button>
                                        <button class="btn-action view" onclick="viewUserDetails(<?php echo $user['id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- System Settings Section -->
                <div id="system-settings" class="content-section">
                    <div class="section-header">
                        <h2>System Settings</h2>
                        <p>Configure hospital and system settings</p>
                    </div>

                    <form id="systemSettingsForm" class="settings-form">
                        <div class="settings-grid">
                            <?php foreach ($systemSettings as $setting): ?>
                            <div class="setting-item">
                                <label for="setting_<?php echo $setting['setting_key']; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                </label>
                                <input type="text" 
                                       id="setting_<?php echo $setting['setting_key']; ?>" 
                                       name="<?php echo $setting['setting_key']; ?>" 
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                <small><?php echo htmlspecialchars($setting['description']); ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </form>
                </div>

                <!-- Other sections placeholders -->
                <div id="audit-logs" class="content-section">
                    <div class="section-header">
                        <h2>Audit Logs</h2>
                        <p>System activity and security logs</p>
                    </div>
                    <div class="coming-soon">
                        <i class="fas fa-history"></i>
                        <p>Detailed audit logs coming soon</p>
                    </div>
                </div>

                <div id="reports" class="content-section">
                    <div class="section-header">
                        <h2>Reports</h2>
                        <p>Generate system reports and analytics</p>
                    </div>
                    <div class="coming-soon">
                        <i class="fas fa-chart-bar"></i>
                        <p>Advanced reporting features coming soon</p>
                    </div>
                </div>

                <div id="backup" class="content-section">
                    <div class="section-header">
                        <h2>Backup & Restore</h2>
                        <p>Database backup and restoration tools</p>
                    </div>
                    <div class="coming-soon">
                        <i class="fas fa-database"></i>
                        <p>Backup tools coming soon</p>
                    </div>
                </div>

                <div id="profile" class="content-section">
                    <div class="section-header">
                        <h2>Admin Profile</h2>
                        <p>Manage your admin account settings</p>
                    </div>
                    <div class="coming-soon">
                        <i class="fas fa-user"></i>
                        <p>Profile management coming soon</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>User Details</h3>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body" id="userModalContent">
                <!-- User details will be loaded here -->
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Sidebar navigation
            $('.nav-item').on('click', function(e) {
                e.preventDefault();
                const section = $(this).data('section');
                
                $('.nav-item').removeClass('active');
                $(this).addClass('active');
                
                $('.content-section').removeClass('active');
                $('#' + section).addClass('active');
            });

            // User type selection for registration
            $('input[name="user_type"]').on('change', function() {
                const userType = $(this).val();
                $('.conditional-fields').hide();
                
                if (userType === 'doctor') {
                    $('#doctor-fields').show();
                } else if (userType === 'patient') {
                    $('#patient-fields').show();
                } else if (userType === 'staff') {
                    $('#staff-fields').show();
                }
            });

            // Admin registration form
            $('#adminRegisterForm').on('submit', function(e) {
                e.preventDefault();
                
                if (!validateAdminForm()) {
                    return;
                }
                
                showSpinner();
                
                $.ajax({
                    url: 'includes/auth.php',
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        hideSpinner();
                        if (response.success) {
                            showAlert('User registered successfully!', 'success');
                            $('#adminRegisterForm')[0].reset();
                            $('.conditional-fields').hide();
                            $('input[name="user_type"]').prop('checked', false);
                            
                            // Refresh user table
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        } else {
                            showAlert(response.message, 'error');
                        }
                    },
                    error: function() {
                        hideSpinner();
                        showAlert('Registration failed. Please try again.', 'error');
                    }
                });
            });

            // User search and filter
            $('#userSearch, #userTypeFilter, #statusFilter').on('input change', function() {
                filterUsers();
            });

            // Password strength indicator
            $('#password').on('keyup', function() {
                const password = $(this).val();
                const strength = checkPasswordStrength(password);
                updatePasswordStrength(strength);
            });
        });

        function validateAdminForm() {
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            
            if (password !== confirmPassword) {
                showAlert('Passwords do not match', 'error');
                return false;
            }
            
            if (password.length < 8) {
                showAlert('Password must be at least 8 characters long', 'error');
                return false;
            }
            
            return true;
        }

        function filterUsers() {
            const searchTerm = $('#userSearch').val().toLowerCase();
            const typeFilter = $('#userTypeFilter').val();
            const statusFilter = $('#statusFilter').val();
            
            $('#usersTable tbody tr').each(function() {
                const row = $(this);
                const name = row.find('td:nth-child(2)').text().toLowerCase();
                const email = row.find('td:nth-child(4)').text().toLowerCase();
                const type = row.find('.badge').text().toLowerCase();
                const status = row.find('.status-badge').hasClass('active') ? '1' : '0';
                
                let show = true;
                
                if (searchTerm && !name.includes(searchTerm) && !email.includes(searchTerm)) {
                    show = false;
                }
                
                if (typeFilter && !type.includes(typeFilter)) {
                    show = false;
                }
                
                if (statusFilter && status !== statusFilter) {
                    show = false;
                }
                
                row.toggle(show);
            });
        }

        function editUser(userId) {
            // TODO: Implement user editing
            showAlert('User editing feature coming soon', 'info');
        }

        function toggleUserStatus(userId, activate) {
            if (confirm(`Are you sure you want to ${activate ? 'activate' : 'deactivate'} this user?`)) {
                $.ajax({
                    url: 'includes/admin.php',
                    method: 'POST',
                    data: {
                        action: 'toggle_user_status',
                        user_id: userId,
                        activate: activate
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAlert('User status updated successfully', 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showAlert(response.message, 'error');
                        }
                    }
                });
            }
        }

        function viewUserDetails(userId) {
            $.ajax({
                url: 'includes/admin.php',
                method: 'POST',
                data: {
                    action: 'get_user_details',
                    user_id: userId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#userModalContent').html(response.html);
                        $('#userModal').show();
                    } else {
                        showAlert(response.message, 'error');
                    }
                }
            });
        }

        function exportUsers() {
            window.open('includes/admin.php?action=export_users', '_blank');
        }

        function toggleSidebar() {
            $('.admin-layout').toggleClass('sidebar-collapsed');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                $.post('includes/auth.php', { action: 'logout' }, function() {
                    window.location.href = 'index.html';
                });
            }
        }

        // Modal close functionality
        $('.modal-close, .modal').on('click', function(e) {
            if (e.target === this) {
                $('.modal').hide();
            }
        });

        function getActivityIcon(action) {
            const icons = {
                'INSERT': 'plus',
                'UPDATE': 'edit',
                'DELETE': 'trash',
                'LOGIN': 'sign-in-alt',
                'LOGOUT': 'sign-out-alt'
            };
            return icons[action] || 'activity';
        }

        function formatActivityAction(action) {
            const actions = {
                'INSERT': 'created a new record',
                'UPDATE': 'updated a record',
                'DELETE': 'deleted a record',
                'LOGIN': 'logged in',
                'LOGOUT': 'logged out'
            };
            return actions[action] || action.toLowerCase();
        }
    </script>
</body>
</html>

<?php
function getActivityIcon($action) {
    $icons = [
        'INSERT' => 'plus',
        'UPDATE' => 'edit',
        'DELETE' => 'trash',
        'LOGIN' => 'sign-in-alt',
        'LOGOUT' => 'sign-out-alt'
    ];
    return $icons[$action] ?? 'activity';
}

function formatActivityAction($action) {
    $actions = [
        'INSERT' => 'created a new record',
        'UPDATE' => 'updated a record',
        'DELETE' => 'deleted a record',
        'LOGIN' => 'logged in',
        'LOGOUT' => 'logged out'
    ];
    return $actions[$action] ?? strtolower($action);
}
?>