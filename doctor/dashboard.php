<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['doctor_logged_in']) || $_SESSION['doctor_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$doctor_ssn = $_SESSION['doctor_ssn'];

try {
    // Get doctor information
    $doctor_sql = "SELECT * FROM Doctor WHERE SSN = ?";
    $doctor = $db->selectOne($doctor_sql, [$doctor_ssn]);
    
    // Get doctor statistics
    $stats_sql = "
        SELECT 
            (SELECT COUNT(*) FROM Consultation WHERE doctor_ssn = ?) as total_consultations,
            (SELECT COUNT(*) FROM Operation WHERE doctor_ssn = ?) as total_operations,
            (SELECT COUNT(*) FROM Diagnosis WHERE doctor_ssn = ?) as total_diagnoses,
            (SELECT COUNT(DISTINCT patient_ssn) FROM Consultation WHERE doctor_ssn = ?) as total_patients
    ";
    $stats = $db->selectOne($stats_sql, [$doctor_ssn, $doctor_ssn, $doctor_ssn, $doctor_ssn]);
    
    // Get recent consultations
    $recent_consultations_sql = "
        SELECT c.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name, p.phone as patient_phone
        FROM Consultation c
        JOIN Patient p ON c.patient_ssn = p.SSN
        WHERE c.doctor_ssn = ?
        ORDER BY c.consultation_date DESC
        LIMIT 10
    ";
    $recent_consultations = $db->select($recent_consultations_sql, [$doctor_ssn]);
    
    // Get today's schedule/appointments (simulated)
    $today = date('Y-m-d');
    $appointments_sql = "
        SELECT c.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name, p.phone as patient_phone
        FROM Consultation c
        JOIN Patient p ON c.patient_ssn = p.SSN
        WHERE c.doctor_ssn = ? AND DATE(c.consultation_date) = ?
        ORDER BY c.consultation_date ASC
    ";
    $todays_appointments = $db->select($appointments_sql, [$doctor_ssn, $today]);
    
} catch (Exception $e) {
    error_log("Doctor Dashboard error: " . $e->getMessage());
    $error_message = "Unable to load dashboard data. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Goba Hospital</title>
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
                    <span>Goba Hospital - Doctor Portal</span>
                </a>
            </div>
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-dashboard"></i> Dashboard
                </a>
                <a href="patients.php" class="nav-link">
                    <i class="fas fa-users"></i> Patients
                </a>
                <a href="consultations.php" class="nav-link">
                    <i class="fas fa-stethoscope"></i> Consultations
                </a>
                <a href="operations.php" class="nav-link">
                    <i class="fas fa-procedures"></i> Operations
                </a>
                <a href="diagnoses.php" class="nav-link">
                    <i class="fas fa-microscope"></i> Diagnoses
                </a>
                <a href="file_manager.php" class="nav-link">
                    <i class="fas fa-folder-medical"></i> Files
                </a>
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user-md"></i> Profile
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <h1>
                <i class="fas fa-user-md"></i> 
                Welcome, Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
            </h1>
            <p class="subtitle">
                <i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($doctor['specialization']); ?> • 
                <i class="fas fa-building"></i> Department: <?php echo htmlspecialchars($doctor['department']); ?>
            </p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card consultations">
                <div class="stat-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_consultations'] ?? 0); ?></h3>
                    <p>Total Consultations</p>
                </div>
            </div>
            
            <div class="stat-card operations">
                <div class="stat-icon">
                    <i class="fas fa-procedures"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_operations'] ?? 0); ?></h3>
                    <p>Total Operations</p>
                </div>
            </div>
            
            <div class="stat-card diagnoses">
                <div class="stat-icon">
                    <i class="fas fa-microscope"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_diagnoses'] ?? 0); ?></h3>
                    <p>Total Diagnoses</p>
                </div>
            </div>
            
            <div class="stat-card patients">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_patients'] ?? 0); ?></h3>
                    <p>Unique Patients</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="content-card">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="quick-actions">
                <a href="add_consultation.php" class="action-btn consultation">
                    <i class="fas fa-plus"></i>
                    <span>New Consultation</span>
                </a>
                <a href="add_operation.php" class="action-btn operation">
                    <i class="fas fa-procedures"></i>
                    <span>Record Operation</span>
                </a>
                <a href="add_diagnosis.php" class="action-btn diagnosis">
                    <i class="fas fa-microscope"></i>
                    <span>Add Diagnosis</span>
                </a>
                <a href="patient_search.php" class="action-btn search">
                    <i class="fas fa-search"></i>
                    <span>Find Patient</span>
                </a>
                <a href="file_upload.php" class="action-btn files">
                    <i class="fas fa-upload"></i>
                    <span>Upload Files</span>
                </a>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="dashboard-grid">
            <!-- Today's Schedule -->
            <div class="content-card">
                <h2><i class="fas fa-calendar-day"></i> Today's Schedule (<?php echo date('M d, Y'); ?>)</h2>
                
                <?php if (empty($todays_appointments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-check"></i>
                        <h3>No appointments today</h3>
                        <p>You have a clear schedule today.</p>
                    </div>
                <?php else: ?>
                    <div class="appointments-list">
                        <?php foreach ($todays_appointments as $appointment): ?>
                            <div class="appointment-item">
                                <div class="appointment-time">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('g:i A', strtotime($appointment['consultation_date'])); ?>
                                </div>
                                <div class="appointment-details">
                                    <h4><?php echo htmlspecialchars($appointment['patient_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($appointment['chief_complaint']); ?></p>
                                    <span class="phone">
                                        <i class="fas fa-phone"></i>
                                        <?php echo htmlspecialchars($appointment['patient_phone']); ?>
                                    </span>
                                </div>
                                <div class="appointment-actions">
                                    <a href="view_patient.php?ssn=<?php echo urlencode($appointment['patient_ssn']); ?>" 
                                       class="btn-sm btn-primary">View</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <div class="content-card">
                <h2><i class="fas fa-history"></i> Recent Consultations</h2>
                
                <?php if (empty($recent_consultations)): ?>
                    <div class="empty-state">
                        <i class="fas fa-stethoscope"></i>
                        <h3>No recent consultations</h3>
                        <p>Start adding patient consultations to see activity here.</p>
                    </div>
                <?php else: ?>
                    <div class="consultations-list">
                        <?php foreach ($recent_consultations as $consultation): ?>
                            <div class="consultation-item">
                                <div class="consultation-info">
                                    <h4><?php echo htmlspecialchars($consultation['patient_name']); ?></h4>
                                    <p class="complaint"><?php echo htmlspecialchars($consultation['chief_complaint']); ?></p>
                                    <div class="consultation-meta">
                                        <span class="date">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('M d, Y g:i A', strtotime($consultation['consultation_date'])); ?>
                                        </span>
                                        <span class="fee">
                                            <i class="fas fa-money-bill"></i>
                                            <?php echo number_format($consultation['consultation_fee'], 2); ?> ETB
                                        </span>
                                    </div>
                                </div>
                                <div class="consultation-actions">
                                    <a href="view_consultation.php?id=<?php echo $consultation['consultation_id']; ?>" 
                                       class="btn-sm btn-outline">View</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="card-footer">
                        <a href="consultations.php" class="btn btn-outline">
                            <i class="fas fa-list"></i> View All Consultations
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- File Upload Section -->
        <div class="content-card">
            <h2><i class="fas fa-cloud-upload-alt"></i> Quick File Upload</h2>
            <p>Upload medical documents, images, or audio recordings for your patients.</p>
            
            <form id="quickUploadForm" class="quick-upload-form" enctype="multipart/form-data">
                <div class="upload-row">
                    <div class="form-group">
                        <label for="patient_ssn">Patient ID/SSN *</label>
                        <input type="text" id="patient_ssn" name="patient_ssn" required 
                               placeholder="Enter patient SSN...">
                    </div>
                    
                    <div class="form-group">
                        <label for="quick_file">Choose File *</label>
                        <input type="file" id="quick_file" name="file" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="quick_category">Category *</label>
                        <select id="quick_category" name="category" required>
                            <option value="">Select...</option>
                            <option value="Medical_Report">Medical Report</option>
                            <option value="Lab_Result">Lab Result</option>
                            <option value="Imaging">Medical Image</option>
                            <option value="Prescription">Prescription</option>
                            <option value="Audio_Record">Audio Record</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                    </div>
                </div>
                
                <input type="hidden" name="action" value="upload">
            </form>
        </div>
    </div>

    <script>
        // Quick upload form handler
        document.getElementById('quickUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            submitBtn.disabled = true;
            
            fetch('../common/file_upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('File uploaded successfully!', 'success');
                    this.reset();
                } else {
                    showNotification('Upload failed: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Upload failed: Network error', 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // Notification system
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()">×</button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }
    </script>

    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            padding: 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            text-decoration: none;
            color: #4a5568;
            transition: all 0.3s ease;
            background: white;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .action-btn.consultation:hover {
            border-color: #3182ce;
            background: #ebf8ff;
            color: #3182ce;
        }
        
        .action-btn.operation:hover {
            border-color: #e53e3e;
            background: #fed7d7;
            color: #e53e3e;
        }
        
        .action-btn.diagnosis:hover {
            border-color: #38a169;
            background: #c6f6d5;
            color: #38a169;
        }
        
        .action-btn.search:hover {
            border-color: #805ad5;
            background: #e9d8fd;
            color: #805ad5;
        }
        
        .action-btn.files:hover {
            border-color: #d69e2e;
            background: #faf089;
            color: #d69e2e;
        }
        
        .action-btn i {
            font-size: 2rem;
        }
        
        .appointments-list, .consultations-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .appointment-item, .consultation-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
        }
        
        .appointment-time {
            min-width: 80px;
            text-align: center;
            color: #3182ce;
            font-weight: 600;
        }
        
        .appointment-details, .consultation-info {
            flex: 1;
        }
        
        .appointment-details h4, .consultation-info h4 {
            margin: 0 0 0.25rem 0;
            color: #2d3748;
        }
        
        .appointment-details p, .consultation-info .complaint {
            margin: 0 0 0.5rem 0;
            color: #718096;
            font-size: 0.9rem;
        }
        
        .consultation-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: #718096;
        }
        
        .consultation-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .quick-upload-form {
            margin-top: 1rem;
        }
        
        .upload-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1.5fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 1001;
            animation: slideIn 0.3s ease;
        }
        
        .notification.success {
            border-left: 4px solid #38a169;
            color: #38a169;
        }
        
        .notification.error {
            border-left: 4px solid #e53e3e;
            color: #e53e3e;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .upload-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>