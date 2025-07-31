<?php
session_start();
require_once '../config/database.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isAuthenticated() || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$pdo = getDatabaseConnection();

switch ($action) {
    case 'toggle_user_status':
        handleToggleUserStatus($pdo);
        break;
    case 'get_user_details':
        handleGetUserDetails($pdo);
        break;
    case 'export_users':
        handleExportUsers($pdo);
        break;
    case 'update_system_settings':
        handleUpdateSystemSettings($pdo);
        break;
    case 'get_system_stats':
        handleGetSystemStats($pdo);
        break;
    case 'delete_user':
        handleDeleteUser($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleToggleUserStatus($pdo) {
    $userId = $_POST['user_id'] ?? '';
    $activate = $_POST['activate'] === 'true';
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    try {
        // Check if user exists and is not an admin (prevent disabling admin accounts)
        $stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        if ($user['user_type'] === 'admin' && !$activate) {
            echo json_encode(['success' => false, 'message' => 'Cannot deactivate admin accounts']);
            return;
        }
        
        // Update user status
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$activate ? 1 : 0, $userId]);
        
        // Log activity
        logUserActivity($_SESSION['user_id'], $activate ? 'ACTIVATE_USER' : 'DEACTIVATE_USER', 'users', $userId);
        
        echo json_encode([
            'success' => true, 
            'message' => 'User status updated successfully'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleGetUserDetails($pdo) {
    $userId = $_POST['user_id'] ?? '';
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    try {
        // Get user details with type-specific information
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   p.blood_type, p.allergies, p.medical_conditions, p.insurance_provider, p.insurance_number,
                   p.next_of_kin, p.next_of_kin_phone,
                   d.medical_license, d.years_of_experience, d.consultation_fee
            FROM users u
            LEFT JOIN patients p ON u.id = p.user_id
            LEFT JOIN doctors d ON u.id = d.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        // Get user's medical records count if patient
        $recordsCount = 0;
        if ($user['user_type'] === 'patient') {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medical_records WHERE patient_id = ?");
            $stmt->execute([$userId]);
            $recordsCount = $stmt->fetch()['count'];
        }
        
        // Get user's created records count if doctor or staff
        $createdRecordsCount = 0;
        if (in_array($user['user_type'], ['doctor', 'staff'])) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medical_records WHERE doctor_id = ?");
            $stmt->execute([$userId]);
            $createdRecordsCount = $stmt->fetch()['count'];
        }
        
        // Generate HTML for user details
        $html = generateUserDetailsHTML($user, $recordsCount, $createdRecordsCount);
        
        echo json_encode([
            'success' => true,
            'html' => $html,
            'user' => $user
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleExportUsers($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.id_number, u.first_name, u.last_name, u.user_type, u.email, u.phone,
                   u.date_of_birth, u.gender, u.is_active, u.created_at,
                   p.blood_type, p.insurance_provider,
                   d.medical_license, d.specialization
            FROM users u
            LEFT JOIN patients p ON u.id = p.user_id
            LEFT JOIN doctors d ON u.id = d.user_id
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        // Output CSV
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, [
            'ID Number', 'First Name', 'Last Name', 'User Type', 'Email', 'Phone',
            'Date of Birth', 'Gender', 'Status', 'Created At',
            'Blood Type', 'Insurance Provider', 'Medical License', 'Specialization'
        ]);
        
        // Data rows
        foreach ($users as $user) {
            fputcsv($output, [
                $user['id_number'],
                $user['first_name'],
                $user['last_name'],
                $user['user_type'],
                $user['email'] ?? '',
                $user['phone'] ?? '',
                $user['date_of_birth'] ?? '',
                $user['gender'] ?? '',
                $user['is_active'] ? 'Active' : 'Inactive',
                $user['created_at'],
                $user['blood_type'] ?? '',
                $user['insurance_provider'] ?? '',
                $user['medical_license'] ?? '',
                $user['specialization'] ?? ''
            ]);
        }
        
        fclose($output);
        exit();
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
    }
}

function handleUpdateSystemSettings($pdo) {
    $settings = $_POST['settings'] ?? [];
    
    if (empty($settings)) {
        echo json_encode(['success' => false, 'message' => 'No settings provided']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("
                UPDATE system_settings 
                SET setting_value = ?, updated_at = NOW() 
                WHERE setting_key = ?
            ");
            $stmt->execute([$value, $key]);
        }
        
        $pdo->commit();
        
        // Log activity
        logUserActivity($_SESSION['user_id'], 'UPDATE_SETTINGS', 'system_settings', null, null, json_encode($settings));
        
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to update settings: ' . $e->getMessage()]);
    }
}

function handleGetSystemStats($pdo) {
    try {
        $stats = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM users WHERE user_type = 'patient' AND is_active = TRUE) as total_patients,
                (SELECT COUNT(*) FROM users WHERE user_type = 'doctor' AND is_active = TRUE) as total_doctors,
                (SELECT COUNT(*) FROM users WHERE user_type = 'staff' AND is_active = TRUE) as total_staff,
                (SELECT COUNT(*) FROM medical_records) as total_records,
                (SELECT COUNT(*) FROM medical_records WHERE DATE(date_time) = CURDATE()) as today_records,
                (SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE()) as today_appointments,
                (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as new_users_today
        ")->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get stats: ' . $e->getMessage()]);
    }
}

function handleDeleteUser($pdo) {
    $userId = $_POST['user_id'] ?? '';
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    try {
        // Check if user exists and is not an admin
        $stmt = $pdo->prepare("SELECT user_type, first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        if ($user['user_type'] === 'admin') {
            echo json_encode(['success' => false, 'message' => 'Cannot delete admin accounts']);
            return;
        }
        
        // Check if user has associated medical records
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medical_records WHERE patient_id = ? OR doctor_id = ?");
        $stmt->execute([$userId, $userId]);
        $recordsCount = $stmt->fetch()['count'];
        
        if ($recordsCount > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete user with existing medical records. Deactivate instead.']);
            return;
        }
        
        // Delete user (cascading will handle related records)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Log activity
        logUserActivity($_SESSION['user_id'], 'DELETE_USER', 'users', $userId, null, json_encode([
            'deleted_user' => $user['first_name'] . ' ' . $user['last_name'],
            'user_type' => $user['user_type']
        ]));
        
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()]);
    }
}

function generateUserDetailsHTML($user, $recordsCount = 0, $createdRecordsCount = 0) {
    $html = '<div class="user-details">';
    
    // Basic Information
    $html .= '<div class="detail-section">';
    $html .= '<h4><i class="fas fa-user"></i> Basic Information</h4>';
    $html .= '<div class="detail-grid">';
    $html .= '<div class="detail-item"><strong>ID Number:</strong> ' . htmlspecialchars($user['id_number']) . '</div>';
    $html .= '<div class="detail-item"><strong>Name:</strong> ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</div>';
    $html .= '<div class="detail-item"><strong>User Type:</strong> <span class="badge badge-' . $user['user_type'] . '">' . ucfirst($user['user_type']) . '</span></div>';
    $html .= '<div class="detail-item"><strong>Status:</strong> <span class="status-badge ' . ($user['is_active'] ? 'active' : 'inactive') . '">' . ($user['is_active'] ? 'Active' : 'Inactive') . '</span></div>';
    $html .= '<div class="detail-item"><strong>Email:</strong> ' . htmlspecialchars($user['email'] ?? 'N/A') . '</div>';
    $html .= '<div class="detail-item"><strong>Phone:</strong> ' . htmlspecialchars($user['phone'] ?? 'N/A') . '</div>';
    $html .= '<div class="detail-item"><strong>Date of Birth:</strong> ' . ($user['date_of_birth'] ? date('M j, Y', strtotime($user['date_of_birth'])) : 'N/A') . '</div>';
    $html .= '<div class="detail-item"><strong>Gender:</strong> ' . ucfirst($user['gender'] ?? 'N/A') . '</div>';
    $html .= '<div class="detail-item"><strong>ID Type:</strong> ' . ucwords(str_replace('_', ' ', $user['id_type'])) . '</div>';
    $html .= '<div class="detail-item"><strong>Joined:</strong> ' . date('M j, Y g:i A', strtotime($user['created_at'])) . '</div>';
    $html .= '</div></div>';
    
    // Type-specific Information
    if ($user['user_type'] === 'patient') {
        $html .= '<div class="detail-section">';
        $html .= '<h4><i class="fas fa-heartbeat"></i> Patient Information</h4>';
        $html .= '<div class="detail-grid">';
        $html .= '<div class="detail-item"><strong>Blood Type:</strong> ' . ($user['blood_type'] ?? 'N/A') . '</div>';
        $html .= '<div class="detail-item"><strong>Insurance Provider:</strong> ' . htmlspecialchars($user['insurance_provider'] ?? 'N/A') . '</div>';
        $html .= '<div class="detail-item"><strong>Insurance Number:</strong> ' . htmlspecialchars($user['insurance_number'] ?? 'N/A') . '</div>';
        $html .= '<div class="detail-item"><strong>Next of Kin:</strong> ' . htmlspecialchars($user['next_of_kin'] ?? 'N/A') . '</div>';
        $html .= '<div class="detail-item"><strong>Emergency Contact:</strong> ' . htmlspecialchars($user['next_of_kin_phone'] ?? 'N/A') . '</div>';
        $html .= '<div class="detail-item"><strong>Medical Records:</strong> ' . $recordsCount . '</div>';
        $html .= '</div>';
        
        if ($user['allergies']) {
            $html .= '<div class="detail-item full-width"><strong>Allergies:</strong><br>' . nl2br(htmlspecialchars($user['allergies'])) . '</div>';
        }
        
        if ($user['medical_conditions']) {
            $html .= '<div class="detail-item full-width"><strong>Medical Conditions:</strong><br>' . nl2br(htmlspecialchars($user['medical_conditions'])) . '</div>';
        }
        
        $html .= '</div>';
    }
    
    if ($user['user_type'] === 'doctor') {
        $html .= '<div class="detail-section">';
        $html .= '<h4><i class="fas fa-user-md"></i> Doctor Information</h4>';
        $html .= '<div class="detail-grid">';
        $html .= '<div class="detail-item"><strong>Medical License:</strong> ' . htmlspecialchars($user['medical_license'] ?? 'N/A') . '</div>';
        $html .= '<div class="detail-item"><strong>Specialization:</strong> ' . htmlspecialchars($user['specialization'] ?? 'N/A') . '</div>';
        $html .= '<div class="detail-item"><strong>Department:</strong> ' . htmlspecialchars($user['department'] ?? 'N/A') . '</div>';
        $html .= '<div class="detail-item"><strong>Experience:</strong> ' . ($user['years_of_experience'] ?? 'N/A') . ' years</div>';
        $html .= '<div class="detail-item"><strong>Consultation Fee:</strong> ' . ($user['consultation_fee'] ? 'ETB ' . number_format($user['consultation_fee'], 2) : 'N/A') . '</div>';
        $html .= '<div class="detail-item"><strong>Records Created:</strong> ' . $createdRecordsCount . '</div>';
        $html .= '</div></div>';
    }
    
    if ($user['user_type'] === 'staff') {
        $html .= '<div class="detail-section">';
        $html .= '<h4><i class="fas fa-user-nurse"></i> Staff Information</h4>';
        $html .= '<div class="detail-grid">';
        $html .= '<div class="detail-item"><strong>Role:</strong> ' . htmlspecialchars($user['specialization'] ?? 'N/A') . '</div>';
        $html .= '<div class="detail-item"><strong>Department:</strong> ' . htmlspecialchars($user['department'] ?? 'N/A') . '</div>';
        $html .= '<div class="detail-item"><strong>License Number:</strong> ' . htmlspecialchars($user['license_number'] ?? 'N/A') . '</div>';
        $html .= '<div class="detail-item"><strong>Records Assisted:</strong> ' . $createdRecordsCount . '</div>';
        $html .= '</div></div>';
    }
    
    // Additional Information
    if ($user['address'] || $user['emergency_contact']) {
        $html .= '<div class="detail-section">';
        $html .= '<h4><i class="fas fa-map-marker-alt"></i> Additional Information</h4>';
        
        if ($user['address']) {
            $html .= '<div class="detail-item full-width"><strong>Address:</strong><br>' . nl2br(htmlspecialchars($user['address'])) . '</div>';
        }
        
        if ($user['emergency_contact']) {
            $html .= '<div class="detail-item full-width"><strong>Emergency Contact:</strong><br>' . htmlspecialchars($user['emergency_contact']) . '</div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}
?>