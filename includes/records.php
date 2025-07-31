<?php
session_start();
require_once '../config/database.php';
require_once 'auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Check authentication
if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$action = $_POST['action'] ?? '';
$pdo = getDatabaseConnection();

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

switch ($action) {
    case 'add_record':
        handleAddRecord($pdo);
        break;
    case 'get_record':
        handleGetRecord($pdo);
        break;
    case 'update_record':
        handleUpdateRecord($pdo);
        break;
    case 'delete_record':
        handleDeleteRecord($pdo);
        break;
    case 'get_patient_records':
        handleGetPatientRecords($pdo);
        break;
    case 'get_doctor_records':
        handleGetDoctorRecords($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleAddRecord($pdo) {
    $currentUser = getCurrentUser();
    
    // Check if user has permission to add records
    if (!hasPermission(['doctor', 'staff'])) {
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        return;
    }
    
    // Get form data
    $patientId = $_POST['patient_id'] ?? '';
    $recordType = $_POST['record_type'] ?? '';
    $dateTime = $_POST['date_time'] ?? '';
    $referenceNumber = $_POST['reference_number'] ?? '';
    $complaints = $_POST['complaints'] ?? '';
    $symptoms = $_POST['symptoms'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    $treatment = $_POST['treatment'] ?? '';
    $prescriptions = $_POST['prescriptions'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $followUpDate = $_POST['follow_up_date'] ?? null;
    
    // Validate required fields
    if (empty($patientId) || empty($recordType) || empty($dateTime) || empty($referenceNumber)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        return;
    }
    
    // Check if reference number already exists
    $checkStmt = $pdo->prepare("SELECT id FROM medical_records WHERE reference_number = ?");
    $checkStmt->execute([$referenceNumber]);
    if ($checkStmt->fetch()) {
        // Generate new reference number
        $referenceNumber = generateReferenceNumber(strtoupper(substr($recordType, 0, 3)));
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insert medical record
        $recordStmt = $pdo->prepare("
            INSERT INTO medical_records (
                patient_id, doctor_id, staff_id, record_type, reference_number, date_time,
                complaints, symptoms, diagnosis, treatment, prescriptions, notes, follow_up_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $staffId = ($_SESSION['user_type'] === 'staff') ? $currentUser['id'] : null;
        $doctorId = ($_SESSION['user_type'] === 'doctor') ? $currentUser['id'] : ($_POST['doctor_id'] ?? null);
        
        $recordStmt->execute([
            $patientId,
            $doctorId,
            $staffId,
            $recordType,
            $referenceNumber,
            $dateTime,
            $complaints,
            $symptoms,
            $diagnosis,
            $treatment,
            $prescriptions,
            $notes,
            $followUpDate ?: null
        ]);
        
        $recordId = $pdo->lastInsertId();
        
        // Handle file uploads
        if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
            $uploadResults = handleFileUploads($_FILES['files'], $recordId, $currentUser['id'], $pdo);
            if (!$uploadResults['success']) {
                $pdo->rollBack();
                echo json_encode($uploadResults);
                return;
            }
        }
        
        // Log activity
        logUserActivity($currentUser['id'], 'add_medical_record', 'medical_records', $recordId);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Medical record added successfully',
            'record_id' => $recordId,
            'reference_number' => $referenceNumber
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Add record error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to add medical record']);
    }
}

function handleGetRecord($pdo) {
    $recordId = $_POST['record_id'] ?? '';
    $currentUser = getCurrentUser();
    
    if (empty($recordId)) {
        echo json_encode(['success' => false, 'message' => 'Record ID is required']);
        return;
    }
    
    try {
        // Build query based on user type
        $query = "
            SELECT mr.*, 
                   CONCAT(pu.first_name, ' ', pu.last_name) as patient_name,
                   pu.id_number as patient_id_number,
                   CONCAT(du.first_name, ' ', du.last_name) as doctor_name,
                   du.specialization as doctor_specialization,
                   CONCAT(su.first_name, ' ', su.last_name) as staff_name
            FROM medical_records mr
            JOIN users pu ON mr.patient_id = pu.id
            LEFT JOIN users du ON mr.doctor_id = du.id
            LEFT JOIN users su ON mr.staff_id = su.id
            WHERE mr.id = ?
        ";
        
        // Add permission check
        if ($_SESSION['user_type'] === 'patient') {
            $query .= " AND mr.patient_id = ?";
            $params = [$recordId, $currentUser['id']];
        } elseif ($_SESSION['user_type'] === 'doctor') {
            $query .= " AND mr.doctor_id = ?";
            $params = [$recordId, $currentUser['id']];
        } elseif ($_SESSION['user_type'] === 'staff') {
            $query .= " AND (mr.staff_id = ? OR mr.doctor_id IN (SELECT id FROM users WHERE department = (SELECT department FROM users WHERE id = ?)))";
            $params = [$recordId, $currentUser['id'], $currentUser['id']];
        } else {
            // Admin can see all records
            $params = [$recordId];
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $record = $stmt->fetch();
        
        if (!$record) {
            echo json_encode(['success' => false, 'message' => 'Record not found or access denied']);
            return;
        }
        
        // Get associated files
        $filesStmt = $pdo->prepare("
            SELECT id, file_name, file_type, file_size, description, created_at
            FROM medical_files 
            WHERE record_id = ?
        ");
        $filesStmt->execute([$recordId]);
        $files = $filesStmt->fetchAll();
        
        $record['files'] = $files;
        
        echo json_encode([
            'success' => true,
            'data' => $record
        ]);
        
    } catch (PDOException $e) {
        error_log("Get record error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve record']);
    }
}

function handleGetPatientRecords($pdo) {
    $patientId = $_POST['patient_id'] ?? '';
    $currentUser = getCurrentUser();
    
    // Permission check
    if ($_SESSION['user_type'] === 'patient' && $patientId != $currentUser['id']) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    if (empty($patientId)) {
        $patientId = $currentUser['id']; // Default to current user if patient
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT mr.*, 
                   CONCAT(du.first_name, ' ', du.last_name) as doctor_name,
                   du.specialization
            FROM medical_records mr
            LEFT JOIN users du ON mr.doctor_id = du.id
            WHERE mr.patient_id = ?
            ORDER BY mr.date_time DESC
        ");
        $stmt->execute([$patientId]);
        $records = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $records
        ]);
        
    } catch (PDOException $e) {
        error_log("Get patient records error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve patient records']);
    }
}

function handleGetDoctorRecords($pdo) {
    $doctorId = $_POST['doctor_id'] ?? '';
    $currentUser = getCurrentUser();
    
    // Permission check
    if ($_SESSION['user_type'] === 'doctor' && $doctorId != $currentUser['id']) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    if (empty($doctorId)) {
        $doctorId = $currentUser['id']; // Default to current user if doctor
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT mr.*, 
                   CONCAT(pu.first_name, ' ', pu.last_name) as patient_name,
                   pu.id_number as patient_id_number
            FROM medical_records mr
            JOIN users pu ON mr.patient_id = pu.id
            WHERE mr.doctor_id = ?
            ORDER BY mr.date_time DESC
        ");
        $stmt->execute([$doctorId]);
        $records = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $records
        ]);
        
    } catch (PDOException $e) {
        error_log("Get doctor records error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve doctor records']);
    }
}

function handleUpdateRecord($pdo) {
    $currentUser = getCurrentUser();
    
    // Check permissions
    if (!hasPermission(['doctor', 'staff', 'admin'])) {
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        return;
    }
    
    $recordId = $_POST['record_id'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    $treatment = $_POST['treatment'] ?? '';
    $prescriptions = $_POST['prescriptions'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if (empty($recordId)) {
        echo json_encode(['success' => false, 'message' => 'Record ID is required']);
        return;
    }
    
    try {
        // Get current record for audit
        $currentStmt = $pdo->prepare("SELECT * FROM medical_records WHERE id = ?");
        $currentStmt->execute([$recordId]);
        $currentRecord = $currentStmt->fetch();
        
        if (!$currentRecord) {
            echo json_encode(['success' => false, 'message' => 'Record not found']);
            return;
        }
        
        // Update record
        $updateStmt = $pdo->prepare("
            UPDATE medical_records 
            SET diagnosis = ?, treatment = ?, prescriptions = ?, notes = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $updateStmt->execute([
            $diagnosis,
            $treatment,
            $prescriptions,
            $notes,
            $status,
            $recordId
        ]);
        
        // Log activity
        logUserActivity(
            $currentUser['id'], 
            'update_medical_record', 
            'medical_records', 
            $recordId,
            $currentRecord,
            [
                'diagnosis' => $diagnosis,
                'treatment' => $treatment,
                'prescriptions' => $prescriptions,
                'notes' => $notes,
                'status' => $status
            ]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Medical record updated successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Update record error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update medical record']);
    }
}

function handleDeleteRecord($pdo) {
    $currentUser = getCurrentUser();
    
    // Only admin can delete records
    if (!hasPermission(['admin'])) {
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        return;
    }
    
    $recordId = $_POST['record_id'] ?? '';
    
    if (empty($recordId)) {
        echo json_encode(['success' => false, 'message' => 'Record ID is required']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get record info for audit
        $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE id = ?");
        $stmt->execute([$recordId]);
        $record = $stmt->fetch();
        
        if (!$record) {
            echo json_encode(['success' => false, 'message' => 'Record not found']);
            return;
        }
        
        // Delete associated files
        $filesStmt = $pdo->prepare("SELECT file_path FROM medical_files WHERE record_id = ?");
        $filesStmt->execute([$recordId]);
        $files = $filesStmt->fetchAll();
        
        foreach ($files as $file) {
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }
        }
        
        // Delete record (cascades to files due to foreign key)
        $deleteStmt = $pdo->prepare("DELETE FROM medical_records WHERE id = ?");
        $deleteStmt->execute([$recordId]);
        
        // Log activity
        logUserActivity($currentUser['id'], 'delete_medical_record', 'medical_records', $recordId, $record);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Medical record deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Delete record error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete medical record']);
    }
}

function handleFileUploads($files, $recordId, $uploadedBy, $pdo) {
    $uploadDir = '../uploads/documents/';
    $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadedFiles = [];
    
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        $fileName = $files['name'][$i];
        $fileSize = $files['size'][$i];
        $fileTmpName = $files['tmp_name'][$i];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Validate file type
        if (!in_array($fileType, $allowedTypes)) {
            return ['success' => false, 'message' => "File type '$fileType' is not allowed"];
        }
        
        // Validate file size
        if ($fileSize > $maxFileSize) {
            return ['success' => false, 'message' => "File '$fileName' is too large"];
        }
        
        // Generate unique filename
        $uniqueFileName = uniqid() . '_' . time() . '.' . $fileType;
        $filePath = $uploadDir . $uniqueFileName;
        
        // Move uploaded file
        if (move_uploaded_file($fileTmpName, $filePath)) {
            // Save file info to database
            $fileStmt = $pdo->prepare("
                INSERT INTO medical_files (record_id, file_name, file_path, file_type, file_size, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $fileStmt->execute([
                $recordId,
                $fileName,
                $filePath,
                $fileType,
                $fileSize,
                $uploadedBy
            ]);
            
            $uploadedFiles[] = [
                'id' => $pdo->lastInsertId(),
                'name' => $fileName,
                'type' => $fileType,
                'size' => $fileSize
            ];
        } else {
            return ['success' => false, 'message' => "Failed to upload file '$fileName'"];
        }
    }
    
    return [
        'success' => true,
        'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
        'files' => $uploadedFiles
    ];
}

// Function to download file
function downloadFile($fileId, $pdo) {
    $currentUser = getCurrentUser();
    
    try {
        $stmt = $pdo->prepare("
            SELECT mf.*, mr.patient_id, mr.doctor_id, mr.staff_id
            FROM medical_files mf
            JOIN medical_records mr ON mf.record_id = mr.id
            WHERE mf.id = ?
        ");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();
        
        if (!$file) {
            http_response_code(404);
            echo "File not found";
            return;
        }
        
        // Check permissions
        $hasAccess = false;
        if ($_SESSION['user_type'] === 'admin') {
            $hasAccess = true;
        } elseif ($_SESSION['user_type'] === 'patient' && $file['patient_id'] == $currentUser['id']) {
            $hasAccess = true;
        } elseif ($_SESSION['user_type'] === 'doctor' && $file['doctor_id'] == $currentUser['id']) {
            $hasAccess = true;
        } elseif ($_SESSION['user_type'] === 'staff' && $file['staff_id'] == $currentUser['id']) {
            $hasAccess = true;
        }
        
        if (!$hasAccess) {
            http_response_code(403);
            echo "Access denied";
            return;
        }
        
        if (!file_exists($file['file_path'])) {
            http_response_code(404);
            echo "File not found on server";
            return;
        }
        
        // Serve file
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
        header('Content-Length: ' . filesize($file['file_path']));
        readfile($file['file_path']);
        
    } catch (PDOException $e) {
        error_log("Download file error: " . $e->getMessage());
        http_response_code(500);
        echo "Error downloading file";
    }
}
?>