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
    case 'search':
        handleSearch($pdo);
        break;
    case 'advanced_search':
        handleAdvancedSearch($pdo);
        break;
    case 'search_patients':
        handleSearchPatients($pdo);
        break;
    case 'search_doctors':
        handleSearchDoctors($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleSearch($pdo) {
    $currentUser = getCurrentUser();
    $query = $_POST['query'] ?? '';
    $recordType = $_POST['record_type'] ?? '';
    $dateFrom = $_POST['date_from'] ?? '';
    $dateTo = $_POST['date_to'] ?? '';
    $patientId = $_POST['patient_id'] ?? '';
    $doctorId = $_POST['doctor_id'] ?? '';
    
    // Build base query
    $sql = "
        SELECT mr.*, 
               CONCAT(pu.first_name, ' ', pu.last_name) as patient_name,
               pu.id_number as patient_id_number,
               CONCAT(du.first_name, ' ', du.last_name) as doctor_name,
               du.specialization as doctor_specialization,
               DATE_FORMAT(mr.date_time, '%Y-%m-%d %H:%i') as formatted_date
        FROM medical_records mr
        JOIN users pu ON mr.patient_id = pu.id
        LEFT JOIN users du ON mr.doctor_id = du.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add permission-based filtering
    if ($_SESSION['user_type'] === 'patient') {
        $sql .= " AND mr.patient_id = ?";
        $params[] = $currentUser['id'];
    } elseif ($_SESSION['user_type'] === 'doctor') {
        $sql .= " AND mr.doctor_id = ?";
        $params[] = $currentUser['id'];
    } elseif ($_SESSION['user_type'] === 'staff') {
        // Staff can see records from their department
        $sql .= " AND (mr.staff_id = ? OR mr.doctor_id IN (SELECT id FROM users WHERE department = (SELECT department FROM users WHERE id = ?)))";
        $params[] = $currentUser['id'];
        $params[] = $currentUser['id'];
    }
    // Admin can see all records - no additional filtering
    
    // Add search filters
    if (!empty($query)) {
        $sql .= " AND (
            mr.reference_number LIKE ? OR
            mr.diagnosis LIKE ? OR
            mr.complaints LIKE ? OR
            mr.treatment LIKE ? OR
            mr.notes LIKE ? OR
            CONCAT(pu.first_name, ' ', pu.last_name) LIKE ? OR
            pu.id_number LIKE ? OR
            CONCAT(du.first_name, ' ', du.last_name) LIKE ?
        )";
        $searchTerm = "%$query%";
        for ($i = 0; $i < 8; $i++) {
            $params[] = $searchTerm;
        }
    }
    
    if (!empty($recordType)) {
        $sql .= " AND mr.record_type = ?";
        $params[] = $recordType;
    }
    
    if (!empty($dateFrom)) {
        $sql .= " AND DATE(mr.date_time) >= ?";
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $sql .= " AND DATE(mr.date_time) <= ?";
        $params[] = $dateTo;
    }
    
    if (!empty($patientId)) {
        $sql .= " AND mr.patient_id = ?";
        $params[] = $patientId;
    }
    
    if (!empty($doctorId)) {
        $sql .= " AND mr.doctor_id = ?";
        $params[] = $doctorId;
    }
    
    $sql .= " ORDER BY mr.date_time DESC LIMIT 50";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        // Format results for display
        $formattedResults = [];
        foreach ($results as $record) {
            $formattedResults[] = [
                'id' => $record['id'],
                'reference_number' => $record['reference_number'],
                'type' => $record['record_type'],
                'date' => $record['formatted_date'],
                'patient_name' => $record['patient_name'],
                'patient_id' => $record['patient_id_number'],
                'doctor_name' => $record['doctor_name'],
                'doctor_specialization' => $record['doctor_specialization'],
                'diagnosis' => $record['diagnosis'],
                'complaints' => $record['complaints'],
                'treatment' => $record['treatment'],
                'status' => $record['status']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $formattedResults,
            'total' => count($formattedResults)
        ]);
        
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Search failed']);
    }
}

function handleAdvancedSearch($pdo) {
    $currentUser = getCurrentUser();
    
    // Get all search parameters
    $filters = [
        'query' => $_POST['query'] ?? '',
        'record_type' => $_POST['record_type'] ?? '',
        'date_from' => $_POST['date_from'] ?? '',
        'date_to' => $_POST['date_to'] ?? '',
        'patient_id' => $_POST['patient_id'] ?? '',
        'doctor_id' => $_POST['doctor_id'] ?? '',
        'status' => $_POST['status'] ?? '',
        'has_diagnosis' => $_POST['has_diagnosis'] ?? '',
        'has_prescription' => $_POST['has_prescription'] ?? '',
        'age_from' => $_POST['age_from'] ?? '',
        'age_to' => $_POST['age_to'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'blood_type' => $_POST['blood_type'] ?? ''
    ];
    
    // Build complex query
    $sql = "
        SELECT mr.*, 
               CONCAT(pu.first_name, ' ', pu.last_name) as patient_name,
               pu.id_number as patient_id_number,
               pu.gender as patient_gender,
               pu.date_of_birth,
               YEAR(CURDATE()) - YEAR(pu.date_of_birth) as patient_age,
               CONCAT(du.first_name, ' ', du.last_name) as doctor_name,
               du.specialization as doctor_specialization,
               p.blood_type,
               p.allergies,
               DATE_FORMAT(mr.date_time, '%Y-%m-%d %H:%i') as formatted_date
        FROM medical_records mr
        JOIN users pu ON mr.patient_id = pu.id
        LEFT JOIN users du ON mr.doctor_id = du.id
        LEFT JOIN patients p ON pu.id = p.user_id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Permission-based filtering
    if ($_SESSION['user_type'] === 'patient') {
        $sql .= " AND mr.patient_id = ?";
        $params[] = $currentUser['id'];
    } elseif ($_SESSION['user_type'] === 'doctor') {
        $sql .= " AND mr.doctor_id = ?";
        $params[] = $currentUser['id'];
    } elseif ($_SESSION['user_type'] === 'staff') {
        $sql .= " AND (mr.staff_id = ? OR mr.doctor_id IN (SELECT id FROM users WHERE department = (SELECT department FROM users WHERE id = ?)))";
        $params[] = $currentUser['id'];
        $params[] = $currentUser['id'];
    }
    
    // Apply filters
    if (!empty($filters['query'])) {
        $sql .= " AND (
            mr.reference_number LIKE ? OR
            mr.diagnosis LIKE ? OR
            mr.complaints LIKE ? OR
            mr.treatment LIKE ? OR
            mr.prescriptions LIKE ? OR
            mr.notes LIKE ? OR
            CONCAT(pu.first_name, ' ', pu.last_name) LIKE ? OR
            pu.id_number LIKE ?
        )";
        $searchTerm = "%{$filters['query']}%";
        for ($i = 0; $i < 8; $i++) {
            $params[] = $searchTerm;
        }
    }
    
    if (!empty($filters['record_type'])) {
        $sql .= " AND mr.record_type = ?";
        $params[] = $filters['record_type'];
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND DATE(mr.date_time) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND DATE(mr.date_time) <= ?";
        $params[] = $filters['date_to'];
    }
    
    if (!empty($filters['patient_id'])) {
        $sql .= " AND mr.patient_id = ?";
        $params[] = $filters['patient_id'];
    }
    
    if (!empty($filters['doctor_id'])) {
        $sql .= " AND mr.doctor_id = ?";
        $params[] = $filters['doctor_id'];
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND mr.status = ?";
        $params[] = $filters['status'];
    }
    
    if ($filters['has_diagnosis'] === 'true') {
        $sql .= " AND mr.diagnosis IS NOT NULL AND mr.diagnosis != ''";
    } elseif ($filters['has_diagnosis'] === 'false') {
        $sql .= " AND (mr.diagnosis IS NULL OR mr.diagnosis = '')";
    }
    
    if ($filters['has_prescription'] === 'true') {
        $sql .= " AND mr.prescriptions IS NOT NULL AND mr.prescriptions != ''";
    } elseif ($filters['has_prescription'] === 'false') {
        $sql .= " AND (mr.prescriptions IS NULL OR mr.prescriptions = '')";
    }
    
    if (!empty($filters['age_from'])) {
        $sql .= " AND YEAR(CURDATE()) - YEAR(pu.date_of_birth) >= ?";
        $params[] = $filters['age_from'];
    }
    
    if (!empty($filters['age_to'])) {
        $sql .= " AND YEAR(CURDATE()) - YEAR(pu.date_of_birth) <= ?";
        $params[] = $filters['age_to'];
    }
    
    if (!empty($filters['gender'])) {
        $sql .= " AND pu.gender = ?";
        $params[] = $filters['gender'];
    }
    
    if (!empty($filters['blood_type'])) {
        $sql .= " AND p.blood_type = ?";
        $params[] = $filters['blood_type'];
    }
    
    $sql .= " ORDER BY mr.date_time DESC LIMIT 100";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $results,
            'total' => count($results),
            'filters_applied' => array_filter($filters)
        ]);
        
    } catch (PDOException $e) {
        error_log("Advanced search error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Advanced search failed']);
    }
}

function handleSearchPatients($pdo) {
    $currentUser = getCurrentUser();
    $query = $_POST['query'] ?? '';
    
    // Check permissions - only doctors, staff, and admin can search patients
    if (!hasPermission(['doctor', 'staff', 'admin'])) {
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        return;
    }
    
    try {
        $sql = "
            SELECT DISTINCT u.id, u.first_name, u.last_name, u.id_number, u.phone, u.email,
                   u.date_of_birth, u.gender,
                   p.blood_type, p.allergies, p.medical_conditions,
                   YEAR(CURDATE()) - YEAR(u.date_of_birth) as age
            FROM users u
            LEFT JOIN patients p ON u.id = p.user_id
            WHERE u.user_type = 'patient' AND u.is_active = 1
        ";
        
        $params = [];
        
        if (!empty($query)) {
            $sql .= " AND (
                CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR
                u.id_number LIKE ? OR
                u.phone LIKE ? OR
                u.email LIKE ?
            )";
            $searchTerm = "%$query%";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }
        
        $sql .= " ORDER BY u.first_name, u.last_name LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $results,
            'total' => count($results)
        ]);
        
    } catch (PDOException $e) {
        error_log("Search patients error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Patient search failed']);
    }
}

function handleSearchDoctors($pdo) {
    $currentUser = getCurrentUser();
    $query = $_POST['query'] ?? '';
    $specialization = $_POST['specialization'] ?? '';
    $department = $_POST['department'] ?? '';
    
    // Check permissions
    if (!hasPermission(['patient', 'staff', 'admin', 'external'])) {
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        return;
    }
    
    try {
        $sql = "
            SELECT DISTINCT u.id, u.first_name, u.last_name, u.id_number, u.phone, u.email,
                   u.specialization, u.department,
                   d.medical_license, d.years_of_experience, d.consultation_fee
            FROM users u
            LEFT JOIN doctors d ON u.id = d.user_id
            WHERE u.user_type = 'doctor' AND u.is_active = 1
        ";
        
        $params = [];
        
        if (!empty($query)) {
            $sql .= " AND (
                CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR
                u.specialization LIKE ? OR
                d.medical_license LIKE ?
            )";
            $searchTerm = "%$query%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }
        
        if (!empty($specialization)) {
            $sql .= " AND u.specialization = ?";
            $params[] = $specialization;
        }
        
        if (!empty($department)) {
            $sql .= " AND u.department = ?";
            $params[] = $department;
        }
        
        $sql .= " ORDER BY u.first_name, u.last_name LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $results,
            'total' => count($results)
        ]);
        
    } catch (PDOException $e) {
        error_log("Search doctors error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Doctor search failed']);
    }
}

// Function to get search suggestions (autocomplete)
function getSearchSuggestions($pdo, $query, $type = 'all') {
    $currentUser = getCurrentUser();
    $suggestions = [];
    
    try {
        switch ($type) {
            case 'patients':
                if (hasPermission(['doctor', 'staff', 'admin'])) {
                    $stmt = $pdo->prepare("
                        SELECT DISTINCT CONCAT(first_name, ' ', last_name) as suggestion, 'patient' as type
                        FROM users 
                        WHERE user_type = 'patient' AND is_active = 1 
                        AND CONCAT(first_name, ' ', last_name) LIKE ?
                        LIMIT 10
                    ");
                    $stmt->execute(["%$query%"]);
                    $suggestions = array_merge($suggestions, $stmt->fetchAll());
                }
                break;
                
            case 'doctors':
                $stmt = $pdo->prepare("
                    SELECT DISTINCT CONCAT(first_name, ' ', last_name) as suggestion, 'doctor' as type
                    FROM users 
                    WHERE user_type = 'doctor' AND is_active = 1 
                    AND CONCAT(first_name, ' ', last_name) LIKE ?
                    LIMIT 10
                ");
                $stmt->execute(["%$query%"]);
                $suggestions = array_merge($suggestions, $stmt->fetchAll());
                break;
                
            case 'diagnoses':
                $sql = "
                    SELECT DISTINCT diagnosis as suggestion, 'diagnosis' as type
                    FROM medical_records 
                    WHERE diagnosis IS NOT NULL AND diagnosis != '' AND diagnosis LIKE ?
                ";
                
                // Add permission filtering
                if ($_SESSION['user_type'] === 'patient') {
                    $sql .= " AND patient_id = ?";
                    $params = ["%$query%", $currentUser['id']];
                } elseif ($_SESSION['user_type'] === 'doctor') {
                    $sql .= " AND doctor_id = ?";
                    $params = ["%$query%", $currentUser['id']];
                } else {
                    $params = ["%$query%"];
                }
                
                $sql .= " LIMIT 10";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $suggestions = array_merge($suggestions, $stmt->fetchAll());
                break;
                
            default:
                // Get mixed suggestions
                $suggestions = array_merge(
                    getSearchSuggestions($pdo, $query, 'patients'),
                    getSearchSuggestions($pdo, $query, 'doctors'),
                    getSearchSuggestions($pdo, $query, 'diagnoses')
                );
                break;
        }
        
        return $suggestions;
        
    } catch (PDOException $e) {
        error_log("Get suggestions error: " . $e->getMessage());
        return [];
    }
}

// Function to export search results
function exportSearchResults($pdo, $searchParams, $format = 'csv') {
    $currentUser = getCurrentUser();
    
    // Check permissions
    if (!hasPermission(['doctor', 'staff', 'admin'])) {
        return false;
    }
    
    // This would implement CSV/PDF export functionality
    // For now, we'll just return the search results
    return handleSearch($pdo);
}
?>