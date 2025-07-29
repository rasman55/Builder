<?php
/**
 * Universal File Upload System for Goba Hospital
 * Supports images, PDFs, and documents for all portals
 */

require_once '../config/database.php';

class FileUploader {
    private $db;
    private $upload_dir;
    private $allowed_types;
    private $max_file_size;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->upload_dir = '../uploads/';
        $this->max_file_size = 10 * 1024 * 1024; // 10MB
        $this->allowed_types = [
            // Images
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            // Documents
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            // Audio (for consultations)
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            'audio/ogg' => 'ogg'
        ];
    }
    
    /**
     * Upload file with validation and database storage
     */
    public function uploadFile($file, $patient_ssn, $uploaded_by_type, $uploaded_by_id, $category = 'Other', $description = '') {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }
            
            // Create directory structure
            $category_dir = $this->getCategoryDirectory($category);
            $full_upload_dir = $this->upload_dir . $category_dir;
            
            if (!is_dir($full_upload_dir)) {
                mkdir($full_upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $extension = $this->allowed_types[$file['type']];
            $filename = $this->generateUniqueFilename($extension);
            $file_path = $full_upload_dir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                return ['success' => false, 'message' => 'Failed to save file'];
            }
            
            // Save to database
            $file_id = $this->saveToDatabase([
                'patient_ssn' => $patient_ssn,
                'uploaded_by_type' => $uploaded_by_type,
                'uploaded_by_id' => $uploaded_by_id,
                'file_name' => $filename,
                'original_file_name' => $file['name'],
                'file_path' => $category_dir . $filename,
                'file_size_bytes' => $file['size'],
                'file_type' => $extension,
                'mime_type' => $file['type'],
                'description' => $description,
                'category' => $category,
                'checksum' => hash_file('md5', $file_path)
            ]);
            
            if ($file_id) {
                return [
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'file_id' => $file_id,
                    'filename' => $filename,
                    'file_path' => $category_dir . $filename
                ];
            } else {
                // Clean up file if database save failed
                unlink($file_path);
                return ['success' => false, 'message' => 'Failed to save file information'];
            }
            
        } catch (Exception $e) {
            error_log("File upload error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => $this->getUploadErrorMessage($file['error'])];
        }
        
        // Check file size
        if ($file['size'] > $this->max_file_size) {
            return ['valid' => false, 'message' => 'File too large. Maximum size is ' . ($this->max_file_size / 1024 / 1024) . 'MB'];
        }
        
        // Check file type
        if (!isset($this->allowed_types[$file['type']])) {
            return ['valid' => false, 'message' => 'File type not allowed: ' . $file['type']];
        }
        
        // Additional security checks
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if ($mime_type !== $file['type']) {
            return ['valid' => false, 'message' => 'File type mismatch. Security check failed.'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Get category-specific directory
     */
    private function getCategoryDirectory($category) {
        $directories = [
            'Medical_Report' => 'medical_reports/',
            'Lab_Result' => 'lab_results/',
            'Imaging' => 'medical_images/',
            'Prescription' => 'prescriptions/',
            'Insurance' => 'insurance/',
            'Identification' => 'identification/',
            'Audio_Record' => 'audio_records/',
            'Other' => 'patient_files/'
        ];
        
        return $directories[$category] ?? 'patient_files/';
    }
    
    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($extension) {
        return date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $extension;
    }
    
    /**
     * Save file information to database
     */
    private function saveToDatabase($data) {
        $sql = "INSERT INTO File_attachments (
            patient_ssn, uploaded_by_type, uploaded_by_id, file_name, 
            original_file_name, file_path, file_size_bytes, file_type, 
            mime_type, description, category, checksum
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        return $this->db->insert($sql, [
            $data['patient_ssn'],
            $data['uploaded_by_type'],
            $data['uploaded_by_id'],
            $data['file_name'],
            $data['original_file_name'],
            $data['file_path'],
            $data['file_size_bytes'],
            $data['file_type'],
            $data['mime_type'],
            $data['description'],
            $data['category'],
            $data['checksum']
        ]);
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds maximum upload size';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds form maximum size';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary upload directory';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Get patient files
     */
    public function getPatientFiles($patient_ssn, $category = null, $limit = 50) {
        $sql = "SELECT fa.*, 
                CASE 
                    WHEN fa.uploaded_by_type = 'Doctor' THEN CONCAT('Dr. ', d.first_name, ' ', d.last_name)
                    WHEN fa.uploaded_by_type = 'Staff' THEN CONCAT(ms.first_name, ' ', ms.last_name, ' (', ms.job_title, ')')
                    WHEN fa.uploaded_by_type = 'External_Office' THEN eho.office_name
                    ELSE 'Patient'
                END as uploaded_by_name
                FROM File_attachments fa
                LEFT JOIN Doctor d ON fa.uploaded_by_type = 'Doctor' AND fa.uploaded_by_id = d.SSN
                LEFT JOIN Medical_staff ms ON fa.uploaded_by_type = 'Staff' AND fa.uploaded_by_id = ms.SSN
                LEFT JOIN External_health_office_login eho ON fa.uploaded_by_type = 'External_Office' AND fa.uploaded_by_id = eho.office_id
                WHERE fa.patient_ssn = ? AND fa.status = 'Active'";
        
        $params = [$patient_ssn];
        
        if ($category) {
            $sql .= " AND fa.category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY fa.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Delete file
     */
    public function deleteFile($file_id, $user_type, $user_id) {
        try {
            // Get file info
            $file = $this->db->selectOne("SELECT * FROM File_attachments WHERE file_id = ?", [$file_id]);
            
            if (!$file) {
                return ['success' => false, 'message' => 'File not found'];
            }
            
            // Check permissions (only uploader or admin can delete)
            if ($file['uploaded_by_type'] !== $user_type || $file['uploaded_by_id'] !== $user_id) {
                if ($user_type !== 'Admin') {
                    return ['success' => false, 'message' => 'Permission denied'];
                }
            }
            
            // Mark as deleted in database
            $sql = "UPDATE File_attachments SET status = 'Deleted' WHERE file_id = ?";
            $result = $this->db->update($sql, [$file_id]);
            
            if ($result) {
                // Optionally delete physical file
                $file_path = $this->upload_dir . $file['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                return ['success' => true, 'message' => 'File deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete file'];
            }
            
        } catch (Exception $e) {
            error_log("File deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Deletion failed'];
        }
    }
    
    /**
     * Download file
     */
    public function downloadFile($file_id, $user_type, $user_id) {
        try {
            // Get file info and check permissions
            $file = $this->db->selectOne("SELECT * FROM File_attachments WHERE file_id = ? AND status = 'Active'", [$file_id]);
            
            if (!$file) {
                return ['success' => false, 'message' => 'File not found'];
            }
            
            // Update download count
            $this->db->update("UPDATE File_attachments SET download_count = download_count + 1, last_accessed = NOW() WHERE file_id = ?", [$file_id]);
            
            $file_path = $this->upload_dir . $file['file_path'];
            
            if (!file_exists($file_path)) {
                return ['success' => false, 'message' => 'Physical file not found'];
            }
            
            return [
                'success' => true,
                'file_path' => $file_path,
                'original_name' => $file['original_file_name'],
                'mime_type' => $file['mime_type']
            ];
            
        } catch (Exception $e) {
            error_log("File download error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Download failed'];
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    session_start();
    
    $uploader = new FileUploader();
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($_POST['action']) {
        case 'upload':
            if (isset($_FILES['file']) && isset($_POST['patient_ssn'])) {
                $patient_ssn = DatabaseHelper::sanitizeInput($_POST['patient_ssn']);
                $category = DatabaseHelper::sanitizeInput($_POST['category'] ?? 'Other');
                $description = DatabaseHelper::sanitizeInput($_POST['description'] ?? '');
                
                // Determine user type and ID from session
                if (isset($_SESSION['patient_logged_in'])) {
                    $user_type = 'Patient';
                    $user_id = $_SESSION['patient_ssn'];
                } elseif (isset($_SESSION['doctor_logged_in'])) {
                    $user_type = 'Doctor';
                    $user_id = $_SESSION['doctor_ssn'];
                } elseif (isset($_SESSION['staff_logged_in'])) {
                    $user_type = 'Staff';
                    $user_id = $_SESSION['staff_ssn'];
                } elseif (isset($_SESSION['external_logged_in'])) {
                    $user_type = 'External_Office';
                    $user_id = $_SESSION['external_office_id'];
                } else {
                    $response = ['success' => false, 'message' => 'Not authenticated'];
                    break;
                }
                
                $response = $uploader->uploadFile($_FILES['file'], $patient_ssn, $user_type, $user_id, $category, $description);
            }
            break;
            
        case 'delete':
            if (isset($_POST['file_id'])) {
                $file_id = (int)$_POST['file_id'];
                
                // Determine user type and ID from session
                if (isset($_SESSION['patient_logged_in'])) {
                    $user_type = 'Patient';
                    $user_id = $_SESSION['patient_ssn'];
                } elseif (isset($_SESSION['doctor_logged_in'])) {
                    $user_type = 'Doctor';
                    $user_id = $_SESSION['doctor_ssn'];
                } elseif (isset($_SESSION['staff_logged_in'])) {
                    $user_type = 'Staff';
                    $user_id = $_SESSION['staff_ssn'];
                } elseif (isset($_SESSION['admin_logged_in'])) {
                    $user_type = 'Admin';
                    $user_id = $_SESSION['admin_id'];
                } else {
                    $response = ['success' => false, 'message' => 'Not authenticated'];
                    break;
                }
                
                $response = $uploader->deleteFile($file_id, $user_type, $user_id);
            }
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle file downloads
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['download'])) {
    session_start();
    
    $file_id = (int)$_GET['download'];
    $uploader = new FileUploader();
    
    // Determine user type and ID from session
    if (isset($_SESSION['patient_logged_in'])) {
        $user_type = 'Patient';
        $user_id = $_SESSION['patient_ssn'];
    } elseif (isset($_SESSION['doctor_logged_in'])) {
        $user_type = 'Doctor';
        $user_id = $_SESSION['doctor_ssn'];
    } elseif (isset($_SESSION['staff_logged_in'])) {
        $user_type = 'Staff';
        $user_id = $_SESSION['staff_ssn'];
    } elseif (isset($_SESSION['admin_logged_in'])) {
        $user_type = 'Admin';
        $user_id = $_SESSION['admin_id'];
    } else {
        http_response_code(401);
        die('Not authenticated');
    }
    
    $result = $uploader->downloadFile($file_id, $user_type, $user_id);
    
    if ($result['success']) {
        header('Content-Type: ' . $result['mime_type']);
        header('Content-Disposition: attachment; filename="' . $result['original_name'] . '"');
        header('Content-Length: ' . filesize($result['file_path']));
        readfile($result['file_path']);
    } else {
        http_response_code(404);
        echo $result['message'];
    }
    exit();
}
?>