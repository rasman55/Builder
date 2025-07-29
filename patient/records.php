<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['patient_logged_in']) || $_SESSION['patient_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$patient_ssn = $_SESSION['patient_ssn'];

// Handle search
$search_term = isset($_GET['search']) ? DatabaseHelper::sanitizeInput($_GET['search']) : '';
$record_type = isset($_GET['type']) ? DatabaseHelper::sanitizeInput($_GET['type']) : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

try {
    // Build search query
    $where_conditions = ["patient_ssn = ?"];
    $params = [$patient_ssn];
    
    if (!empty($search_term)) {
        $where_conditions[] = "(description LIKE ? OR doctor_name LIKE ?)";
        $params[] = "%{$search_term}%";
        $params[] = "%{$search_term}%";
    }
    
    if (!empty($date_from)) {
        $where_conditions[] = "date >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "date <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get medical records with filters
    $records_sql = "
        SELECT 'Consultation' as type, consultation_date as date, 
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               chief_complaint as description, consultation_fee as cost,
               consultation_id as record_id, d.specialization,
               treatment_plan, medications_prescribed, follow_up_date
        FROM Consultation c
        JOIN Doctor d ON c.doctor_ssn = d.SSN
        WHERE {$where_clause}
        " . ($record_type === 'consultation' || $record_type === 'all' ? '' : ' AND 1=0') . "
        
        UNION ALL
        
        SELECT 'Operation' as type, operation_date as date,
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               procedure_name as description, operation_cost as cost,
               operation_id as record_id, d.specialization,
               operation_description as treatment_plan, 
               complications as medications_prescribed, 
               NULL as follow_up_date
        FROM Operation o
        JOIN Doctor d ON o.doctor_ssn = d.SSN
        WHERE {$where_clause}
        " . ($record_type === 'operation' || $record_type === 'all' ? '' : ' AND 1=0') . "
        
        UNION ALL
        
        SELECT 'Diagnosis' as type, diagnosis_date as date,
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               diagnosis_name as description, 0 as cost,
               diagnosis_id as record_id, d.specialization,
               diagnosis_description as treatment_plan,
               treatment_recommended as medications_prescribed,
               NULL as follow_up_date
        FROM Diagnosis diag
        JOIN Doctor d ON diag.doctor_ssn = d.SSN
        WHERE {$where_clause}
        " . ($record_type === 'diagnosis' || $record_type === 'all' ? '' : ' AND 1=0') . "
        
        ORDER BY date DESC
        LIMIT 50
    ";
    
    $records = $db->select($records_sql, array_merge($params, $params, $params));
    
} catch (Exception $e) {
    error_log("Records error: " . $e->getMessage());
    $records = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - Patient Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/portal.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="portal-body">
    <!-- Navigation -->
    <nav class="portal-nav">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="dashboard.php">
                    <i class="fas fa-hospital"></i>
                    <span>Goba Hospital - Patient Portal</span>
                </a>
            </div>
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-dashboard"></i> Dashboard
                </a>
                <a href="records.php" class="nav-link active">
                    <i class="fas fa-clipboard-list"></i> Records
                </a>
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="payments.php" class="nav-link">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Page Header -->
        <div class="dashboard-header">
            <h1><i class="fas fa-clipboard-list"></i> Medical Records</h1>
            <p>View and search your complete medical history</p>
        </div>

        <!-- Search and Filters -->
        <div class="content-card">
            <h2><i class="fas fa-search"></i> Search Records</h2>
            <form method="GET" class="search-form">
                <div class="search-filters">
                    <div class="form-group">
                        <label for="search">Search Term</label>
                        <input type="text" id="search" name="search" 
                               placeholder="Search by description, doctor name..." 
                               value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Record Type</label>
                        <select id="type" name="type">
                            <option value="all" <?php echo $record_type === 'all' ? 'selected' : ''; ?>>All Records</option>
                            <option value="consultation" <?php echo $record_type === 'consultation' ? 'selected' : ''; ?>>Consultations</option>
                            <option value="operation" <?php echo $record_type === 'operation' ? 'selected' : ''; ?>>Operations</option>
                            <option value="diagnosis" <?php echo $record_type === 'diagnosis' ? 'selected' : ''; ?>>Diagnoses</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_from">From Date</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_to">To Date</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    
                    <a href="records.php" class="btn btn-secondary">
                        <i class="fas fa-refresh"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Medical Records -->
        <div class="content-card">
            <h2><i class="fas fa-file-medical"></i> Medical History (<?php echo count($records); ?> records)</h2>
            
            <?php if (empty($records)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-medical"></i>
                    <h3>No medical records found</h3>
                    <p>No records match your search criteria.</p>
                </div>
            <?php else: ?>
                <div class="records-timeline">
                    <?php foreach ($records as $record): ?>
                        <div class="record-item <?php echo strtolower($record['type']); ?>">
                            <div class="record-icon">
                                <i class="fas fa-<?php 
                                    echo $record['type'] === 'Consultation' ? 'stethoscope' : 
                                        ($record['type'] === 'Operation' ? 'procedures' : 'microscope'); 
                                ?>"></i>
                            </div>
                            
                            <div class="record-content">
                                <div class="record-header">
                                    <h3><?php echo htmlspecialchars($record['type']); ?></h3>
                                    <span class="record-date">
                                        <?php echo date('M d, Y', strtotime($record['date'])); ?>
                                    </span>
                                </div>
                                
                                <div class="record-details">
                                    <p class="description">
                                        <strong><?php echo htmlspecialchars($record['description']); ?></strong>
                                    </p>
                                    
                                    <div class="doctor-info">
                                        <i class="fas fa-user-md"></i>
                                        <span>Dr. <?php echo htmlspecialchars($record['doctor_name']); ?></span>
                                        <?php if ($record['specialization']): ?>
                                            <span class="specialization">(<?php echo htmlspecialchars($record['specialization']); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($record['treatment_plan']): ?>
                                        <div class="treatment-plan">
                                            <i class="fas fa-prescription"></i>
                                            <strong>Treatment:</strong>
                                            <p><?php echo htmlspecialchars($record['treatment_plan']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($record['medications_prescribed']): ?>
                                        <div class="medications">
                                            <i class="fas fa-pills"></i>
                                            <strong>Medications/Notes:</strong>
                                            <p><?php echo htmlspecialchars($record['medications_prescribed']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($record['follow_up_date']): ?>
                                        <div class="follow-up">
                                            <i class="fas fa-calendar"></i>
                                            <strong>Follow-up:</strong>
                                            <span><?php echo date('M d, Y', strtotime($record['follow_up_date'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($record['cost'] > 0): ?>
                                        <div class="cost">
                                            <i class="fas fa-money-bill"></i>
                                            <strong>Cost:</strong>
                                            <span><?php echo number_format($record['cost'], 2); ?> ETB</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="record-actions">
                                    <button class="btn-link" onclick="printRecord(<?php echo $record['record_id']; ?>)">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                    <button class="btn-link" onclick="exportRecord(<?php echo $record['record_id']; ?>)">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function printRecord(recordId) {
            // Create print-friendly version
            window.print();
        }
        
        function exportRecord(recordId) {
            // Export record as PDF
            alert('Export functionality will be implemented with PDF generation library');
        }
        
        // Auto-submit form on filter change
        document.getElementById('type').addEventListener('change', function() {
            document.querySelector('.search-form').submit();
        });
    </script>

    <style>
        .search-form {
            margin-bottom: 2rem;
        }
        
        .records-timeline {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .record-item {
            display: flex;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            background: white;
            transition: all 0.3s ease;
        }
        
        .record-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .record-item.consultation {
            border-left: 4px solid #3182ce;
        }
        
        .record-item.operation {
            border-left: 4px solid #e53e3e;
        }
        
        .record-item.diagnosis {
            border-left: 4px solid #38a169;
        }
        
        .record-icon {
            margin-right: 1rem;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .consultation .record-icon {
            background: #ebf8ff;
            color: #3182ce;
        }
        
        .operation .record-icon {
            background: #fed7d7;
            color: #e53e3e;
        }
        
        .diagnosis .record-icon {
            background: #c6f6d5;
            color: #38a169;
        }
        
        .record-content {
            flex: 1;
        }
        
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .record-header h3 {
            margin: 0;
            color: #2d3748;
        }
        
        .record-date {
            color: #718096;
            font-size: 0.9rem;
        }
        
        .record-details > div {
            margin-bottom: 0.75rem;
        }
        
        .description {
            font-size: 1.1rem;
            color: #2d3748;
            margin-bottom: 1rem;
        }
        
        .doctor-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #4a5568;
        }
        
        .specialization {
            color: #718096;
            font-size: 0.9rem;
        }
        
        .treatment-plan, .medications, .follow-up, .cost {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            color: #4a5568;
        }
        
        .treatment-plan p, .medications p {
            margin: 0;
            margin-left: 0.5rem;
        }
        
        .record-actions {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f7fafc;
            display: flex;
            gap: 1rem;
        }
        
        .btn-link {
            background: none;
            border: none;
            color: #3182ce;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.9rem;
        }
        
        .btn-link:hover {
            color: #2c5282;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #e2e8f0;
        }
        
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 6px;
        }
    </style>
</body>
</html>