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
$patient_name = $_SESSION['patient_name'];

// Get patient information
try {
    $patient_sql = "SELECT * FROM Patient WHERE SSN = ?";
    $patient = $db->selectOne($patient_sql, [$patient_ssn]);
    
    if (!$patient) {
        session_destroy();
        header('Location: login.php?error=account_not_found');
        exit();
    }
    
    // Get statistics
    $stats_sql = "
        SELECT 
            (SELECT COUNT(*) FROM Consultation WHERE patient_ssn = ?) as total_consultations,
            (SELECT COUNT(*) FROM Operation WHERE patient_ssn = ?) as total_operations,
            (SELECT COUNT(*) FROM Diagnosis WHERE patient_ssn = ?) as total_diagnoses,
            (SELECT COUNT(*) FROM Payment_records WHERE patient_ssn = ?) as total_payments
    ";
    $stats = $db->selectOne($stats_sql, [$patient_ssn, $patient_ssn, $patient_ssn, $patient_ssn]);
    
    // Get recent medical records
    $recent_records_sql = "
        SELECT 'Consultation' as type, consultation_date as date, 
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               chief_complaint as description, consultation_fee as cost,
               consultation_id as record_id
        FROM Consultation c
        JOIN Doctor d ON c.doctor_ssn = d.SSN
        WHERE c.patient_ssn = ?
        
        UNION ALL
        
        SELECT 'Operation' as type, operation_date as date,
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               procedure_name as description, operation_cost as cost,
               operation_id as record_id
        FROM Operation o
        JOIN Doctor d ON o.doctor_ssn = d.SSN
        WHERE o.patient_ssn = ?
        
        UNION ALL
        
        SELECT 'Diagnosis' as type, diagnosis_date as date,
               CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
               diagnosis_name as description, 0 as cost,
               diagnosis_id as record_id
        FROM Diagnosis diag
        JOIN Doctor d ON diag.doctor_ssn = d.SSN
        WHERE diag.patient_ssn = ?
        
        ORDER BY date DESC
        LIMIT 10
    ";
    $recent_records = $db->select($recent_records_sql, [$patient_ssn, $patient_ssn, $patient_ssn]);
    
    // Get upcoming appointments (scheduled consultations)
    $upcoming_sql = "
        SELECT c.*, CONCAT(d.first_name, ' ', d.last_name) as doctor_name, d.specialization
        FROM Consultation c
        JOIN Doctor d ON c.doctor_ssn = d.SSN
        WHERE c.patient_ssn = ? AND c.consultation_date > NOW() AND c.status = 'Scheduled'
        ORDER BY c.consultation_date ASC
        LIMIT 5
    ";
    $upcoming_appointments = $db->select($upcoming_sql, [$patient_ssn]);
    
    // Get recent payments
    $payments_sql = "
        SELECT * FROM Payment_records 
        WHERE patient_ssn = ? 
        ORDER BY payment_date DESC 
        LIMIT 5
    ";
    $recent_payments = $db->select($payments_sql, [$patient_ssn]);
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error_message = "Unable to load dashboard data. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Goba Hospital</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/portal.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="portal-body">
    <!-- Navigation -->
    <nav class="portal-nav">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="../index.html">
                    <i class="fas fa-hospital"></i>
                    <span>Goba Hospital</span>
                </a>
            </div>
            <div class="nav-links">
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="records.php" class="nav-link">
                    <i class="fas fa-clipboard-list"></i> Records
                </a>
                <a href="files.php" class="nav-link">
                    <i class="fas fa-folder"></i> Files
                </a>
                <a href="search.php" class="nav-link">
                    <i class="fas fa-search"></i> Search
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

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <!-- Welcome Header -->
        <div class="dashboard-header">
            <div class="welcome-message">
                <h1>Welcome back, <?php echo htmlspecialchars($patient['first_name']); ?>!</h1>
                <p>Here's your healthcare overview and recent activity.</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <h3><?php echo $stats['total_consultations']; ?></h3>
                <p>Total Consultations</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-procedures"></i>
                </div>
                <h3><?php echo $stats['total_operations']; ?></h3>
                <p>Operations</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-microscope"></i>
                </div>
                <h3><?php echo $stats['total_diagnoses']; ?></h3>
                <p>Diagnoses</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <h3><?php echo $stats['total_payments']; ?></h3>
                <p>Payment Records</p>
            </div>
        </div>

        <!-- Main Dashboard Content -->
        <div class="dashboard-content">
            <div class="main-content">
                <!-- Recent Medical Records -->
                <div class="content-card">
                    <h2>
                        <i class="fas fa-clipboard-list"></i>
                        Recent Medical Records
                    </h2>
                    
                    <?php if (empty($recent_records)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No medical records found.</p>
                        </div>
                    <?php else: ?>
                        <div class="records-list">
                            <?php foreach ($recent_records as $record): ?>
                                <div class="record-item">
                                    <div class="record-header">
                                        <div class="record-type">
                                            <i class="fas fa-<?php 
                                                echo $record['type'] === 'Consultation' ? 'stethoscope' : 
                                                    ($record['type'] === 'Operation' ? 'procedures' : 'microscope'); 
                                            ?>"></i>
                                            <span><?php echo htmlspecialchars($record['type']); ?></span>
                                        </div>
                                        <div class="record-date">
                                            <?php echo date('M d, Y', strtotime($record['date'])); ?>
                                        </div>
                                    </div>
                                    <div class="record-content">
                                        <p class="record-description">
                                            <?php echo htmlspecialchars($record['description']); ?>
                                        </p>
                                        <p class="record-doctor">
                                            <i class="fas fa-user-md"></i>
                                            Dr. <?php echo htmlspecialchars($record['doctor_name']); ?>
                                        </p>
                                        <?php if ($record['cost'] > 0): ?>
                                            <p class="record-cost">
                                                <i class="fas fa-dollar-sign"></i>
                                                <?php echo number_format($record['cost'], 2); ?> ETB
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="card-footer">
                            <a href="records.php" class="btn btn-primary">
                                <i class="fas fa-eye"></i>
                                View All Records
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Payments -->
                <div class="content-card">
                    <h2>
                        <i class="fas fa-credit-card"></i>
                        Recent Payments
                    </h2>
                    
                    <?php if (empty($recent_payments)): ?>
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <p>No payment records found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Service</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($payment['service_type']); ?></td>
                                            <td><?php echo number_format($payment['total_amount'], 2); ?> ETB</td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo htmlspecialchars($payment['payment_method']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $payment['payment_status'] === 'Completed' ? 'success' : 
                                                        ($payment['payment_status'] === 'Pending' ? 'warning' : 'error'); 
                                                ?>">
                                                    <?php echo htmlspecialchars($payment['payment_status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer">
                            <a href="payments.php" class="btn btn-primary">
                                <i class="fas fa-eye"></i>
                                View All Payments
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sidebar-content">
                <!-- Patient Info Card -->
                <div class="content-card">
                    <h2>
                        <i class="fas fa-user"></i>
                        Patient Information
                    </h2>
                    
                    <div class="patient-info">
                        <div class="info-item">
                            <strong>Name:</strong>
                            <span><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>SSN:</strong>
                            <span><?php echo htmlspecialchars($patient['SSN']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Date of Birth:</strong>
                            <span><?php echo date('M d, Y', strtotime($patient['date_of_birth'])); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Gender:</strong>
                            <span><?php echo htmlspecialchars($patient['gender']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Blood Type:</strong>
                            <span><?php echo htmlspecialchars($patient['blood_type']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Phone:</strong>
                            <span><?php echo htmlspecialchars($patient['phone']); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Email:</strong>
                            <span><?php echo htmlspecialchars($patient['email']); ?></span>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <a href="profile.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-edit"></i>
                            Edit Profile
                        </a>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="content-card">
                    <h2>
                        <i class="fas fa-calendar-alt"></i>
                        Upcoming Appointments
                    </h2>
                    
                    <?php if (empty($upcoming_appointments)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>No upcoming appointments.</p>
                        </div>
                    <?php else: ?>
                        <div class="appointments-list">
                            <?php foreach ($upcoming_appointments as $appointment): ?>
                                <div class="appointment-item">
                                    <div class="appointment-date">
                                        <div class="date-month">
                                            <?php echo date('M', strtotime($appointment['consultation_date'])); ?>
                                        </div>
                                        <div class="date-day">
                                            <?php echo date('d', strtotime($appointment['consultation_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="appointment-details">
                                        <h4><?php echo htmlspecialchars($appointment['doctor_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($appointment['specialization']); ?></p>
                                        <p class="appointment-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('h:i A', strtotime($appointment['consultation_date'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="content-card">
                    <h2>
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </h2>
                    
                    <div class="quick-actions">
                        <a href="search.php" class="action-btn">
                            <i class="fas fa-search"></i>
                            <span>Search Records</span>
                        </a>
                        <a href="payments.php?action=new" class="action-btn">
                            <i class="fas fa-credit-card"></i>
                            <span>Make Payment</span>
                        </a>
                        <a href="profile.php" class="action-btn">
                            <i class="fas fa-user-edit"></i>
                            <span>Update Profile</span>
                        </a>
                        <a href="mailto:support@gobahospital.com" class="action-btn">
                            <i class="fas fa-envelope"></i>
                            <span>Contact Support</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh dashboard every 5 minutes
        setTimeout(() => {
            window.location.reload();
        }, 300000);

        // Add hover effects to record items
        document.querySelectorAll('.record-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });

        // Add click handlers for quick actions
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.href.includes('mailto:')) return;
                
                // Add loading effect
                const icon = this.querySelector('i');
                const originalClass = icon.className;
                icon.className = 'fas fa-spinner fa-spin';
                
                setTimeout(() => {
                    icon.className = originalClass;
                }, 1000);
            });
        });
    </script>

    <style>
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #718096;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #e2e8f0;
        }

        .records-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .record-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .record-item:hover {
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
        }

        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .record-type {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: #2d3748;
        }

        .record-type i {
            color: #667eea;
        }

        .record-date {
            color: #718096;
            font-size: 0.9rem;
        }

        .record-content {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .record-description {
            color: #4a5568;
            font-weight: 500;
        }

        .record-doctor,
        .record-cost {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #718096;
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success {
            background: #c6f6d5;
            color: #2f855a;
        }

        .badge-warning {
            background: #fef5e7;
            color: #c05621;
        }

        .badge-error {
            background: #fed7d7;
            color: #c53030;
        }

        .badge-info {
            background: #bee3f8;
            color: #2c5282;
        }

        .patient-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f7fafc;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item strong {
            color: #4a5568;
            font-size: 0.9rem;
        }

        .info-item span {
            color: #2d3748;
            font-weight: 500;
        }

        .appointments-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .appointment-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .appointment-item:hover {
            border-color: #667eea;
            transform: translateY(-1px);
        }

        .appointment-date {
            text-align: center;
            min-width: 50px;
        }

        .date-month {
            font-size: 0.8rem;
            color: #718096;
            text-transform: uppercase;
        }

        .date-day {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .appointment-details h4 {
            margin: 0 0 0.25rem 0;
            color: #2d3748;
            font-size: 1rem;
        }

        .appointment-details p {
            margin: 0;
            font-size: 0.9rem;
            color: #718096;
        }

        .appointment-time {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.5rem !important;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: #f7fafc;
            border-radius: 8px;
            text-decoration: none;
            color: #4a5568;
            transition: all 0.3s ease;
            text-align: center;
        }

        .action-btn:hover {
            background: #edf2f7;
            color: #667eea;
            transform: translateY(-2px);
        }

        .action-btn i {
            font-size: 1.25rem;
        }

        .action-btn span {
            font-size: 0.8rem;
            font-weight: 500;
        }

        .card-footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        @media (max-width: 768px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }

            .appointment-item {
                flex-direction: column;
                text-align: center;
            }

            .appointment-date {
                align-self: center;
            }
        }
    </style>
</body>
</html>