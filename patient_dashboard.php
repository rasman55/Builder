<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user is authenticated and is a patient
if (!isAuthenticated() || $_SESSION['user_type'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$pdo = getDatabaseConnection();
$currentUser = getCurrentUser();

// Get patient profile information
$profileStmt = $pdo->prepare("
    SELECT u.*, p.blood_type, p.allergies, p.medical_conditions, p.medications,
           p.insurance_number, p.insurance_provider, p.next_of_kin, p.next_of_kin_phone
    FROM users u 
    LEFT JOIN patients p ON u.id = p.user_id 
    WHERE u.id = ?
");
$profileStmt->execute([$currentUser['id']]);
$profile = $profileStmt->fetch();

// Get patient medical records summary
$recordsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_records,
        COUNT(CASE WHEN record_type = 'consultation' THEN 1 END) as consultations,
        COUNT(CASE WHEN record_type = 'diagnosis' THEN 1 END) as diagnoses,
        COUNT(CASE WHEN record_type = 'surgery' THEN 1 END) as surgeries,
        COUNT(CASE WHEN record_type = 'treatment' THEN 1 END) as treatments
    FROM medical_records 
    WHERE patient_id = ?
");
$recordsStmt->execute([$currentUser['id']]);
$recordsSummary = $recordsStmt->fetch();

// Get recent medical records
$recentRecordsStmt = $pdo->prepare("
    SELECT mr.*, 
           CONCAT(du.first_name, ' ', du.last_name) as doctor_name,
           du.specialization
    FROM medical_records mr
    JOIN users du ON mr.doctor_id = du.id
    WHERE mr.patient_id = ?
    ORDER BY mr.date_time DESC
    LIMIT 10
");
$recentRecordsStmt->execute([$currentUser['id']]);
$recentRecords = $recentRecordsStmt->fetchAll();

// Get upcoming appointments
$appointmentsStmt = $pdo->prepare("
    SELECT a.*, 
           CONCAT(du.first_name, ' ', du.last_name) as doctor_name,
           du.specialization
    FROM appointments a
    JOIN users du ON a.doctor_id = du.id
    WHERE a.patient_id = ? AND a.appointment_date > NOW() AND a.status IN ('scheduled', 'confirmed')
    ORDER BY a.appointment_date ASC
    LIMIT 5
");
$appointmentsStmt->execute([$currentUser['id']]);
$upcomingAppointments = $appointmentsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Goba Hospital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .user-info h1 {
            margin-bottom: 0.5rem;
        }
        
        .user-info p {
            opacity: 0.9;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #3498db;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .stat-card p {
            color: #7f8c8d;
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .main-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        
        .content-section {
            display: none;
            padding: 2rem;
        }
        
        .content-section.active {
            display: block;
        }
        
        .record-card {
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: box-shadow 0.3s ease;
        }
        
        .record-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .record-type {
            background: #3498db;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        
        .record-type.consultation { background: #3498db; }
        .record-type.diagnosis { background: #e74c3c; }
        .record-type.surgery { background: #9b59b6; }
        .record-type.treatment { background: #2ecc71; }
        
        .appointment-card {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0 8px 8px 0;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 25px;
            font-size: 1rem;
        }
        
        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
        }
        
        @media (max-width: 768px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .welcome-section {
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="welcome-section">
                <div class="user-info">
                    <h1>Welcome, <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h1>
                    <p><i class="fas fa-id-card"></i> ID: <?php echo htmlspecialchars($profile['id_number']); ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($profile['email'] ?: 'No email provided'); ?></p>
                </div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-file-medical"></i>
                <h3><?php echo $recordsSummary['total_records']; ?></h3>
                <p>Total Records</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-stethoscope"></i>
                <h3><?php echo $recordsSummary['consultations']; ?></h3>
                <p>Consultations</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-diagnoses"></i>
                <h3><?php echo $recordsSummary['diagnoses']; ?></h3>
                <p>Diagnoses</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-procedures"></i>
                <h3><?php echo $recordsSummary['surgeries']; ?></h3>
                <p>Surgeries</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="dashboard-content">
            <div class="main-content">
                <!-- Navigation Tabs -->
                <div class="dashboard-nav">
                    <a href="#records" class="nav-tab active" data-section="records">
                        <i class="fas fa-file-medical"></i> Medical Records
                    </a>
                    <a href="#profile" class="nav-tab" data-section="profile">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="#appointments" class="nav-tab" data-section="appointments">
                        <i class="fas fa-calendar"></i> Appointments
                    </a>
                    <a href="#search" class="nav-tab" data-section="search">
                        <i class="fas fa-search"></i> Search Records
                    </a>
                </div>

                <!-- Medical Records Section -->
                <div id="records" class="content-section active">
                    <h2>Recent Medical Records</h2>
                    <div class="search-box">
                        <input type="text" id="recordsSearch" placeholder="Search your medical records...">
                        <i class="fas fa-search"></i>
                    </div>
                    
                    <div id="recordsList">
                        <?php if (empty($recentRecords)): ?>
                            <p>No medical records found.</p>
                        <?php else: ?>
                            <?php foreach ($recentRecords as $record): ?>
                                <div class="record-card">
                                    <div class="record-header">
                                        <div>
                                            <span class="record-type <?php echo $record['record_type']; ?>">
                                                <?php echo ucfirst($record['record_type']); ?>
                                            </span>
                                            <span class="record-ref">#<?php echo $record['reference_number']; ?></span>
                                        </div>
                                        <div class="record-date">
                                            <?php echo date('M d, Y H:i', strtotime($record['date_time'])); ?>
                                        </div>
                                    </div>
                                    <div class="record-content">
                                        <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($record['doctor_name']); ?>
                                        <?php if ($record['specialization']): ?>
                                            (<?php echo htmlspecialchars($record['specialization']); ?>)
                                        <?php endif; ?>
                                        </p>
                                        <?php if ($record['complaints']): ?>
                                            <p><strong>Complaints:</strong> <?php echo htmlspecialchars($record['complaints']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($record['diagnosis']): ?>
                                            <p><strong>Diagnosis:</strong> <?php echo htmlspecialchars($record['diagnosis']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($record['treatment']): ?>
                                            <p><strong>Treatment:</strong> <?php echo htmlspecialchars($record['treatment']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($record['notes']): ?>
                                            <p><strong>Notes:</strong> <?php echo htmlspecialchars($record['notes']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="record-actions">
                                        <button class="btn btn-primary btn-sm view-record" data-id="<?php echo $record['id']; ?>">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Profile Section -->
                <div id="profile" class="content-section">
                    <h2>My Profile</h2>
                    <div class="profile-info">
                        <div class="form-group">
                            <label>Full Name:</label>
                            <p><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></p>
                        </div>
                        <div class="form-group">
                            <label>ID Number:</label>
                            <p><?php echo htmlspecialchars($profile['id_number']); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Email:</label>
                            <p><?php echo htmlspecialchars($profile['email'] ?: 'Not provided'); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Phone:</label>
                            <p><?php echo htmlspecialchars($profile['phone'] ?: 'Not provided'); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth:</label>
                            <p><?php echo $profile['date_of_birth'] ? date('M d, Y', strtotime($profile['date_of_birth'])) : 'Not provided'; ?></p>
                        </div>
                        <div class="form-group">
                            <label>Gender:</label>
                            <p><?php echo $profile['gender'] ? ucfirst($profile['gender']) : 'Not provided'; ?></p>
                        </div>
                        <div class="form-group">
                            <label>Blood Type:</label>
                            <p><?php echo htmlspecialchars($profile['blood_type'] ?: 'Not provided'); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Allergies:</label>
                            <p><?php echo htmlspecialchars($profile['allergies'] ?: 'None reported'); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Medical Conditions:</label>
                            <p><?php echo htmlspecialchars($profile['medical_conditions'] ?: 'None reported'); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Current Medications:</label>
                            <p><?php echo htmlspecialchars($profile['medications'] ?: 'None reported'); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact:</label>
                            <p><?php echo htmlspecialchars($profile['emergency_contact'] ?: 'Not provided'); ?></p>
                        </div>
                        <button class="btn btn-primary" onclick="editProfile()">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                    </div>
                </div>

                <!-- Appointments Section -->
                <div id="appointments" class="content-section">
                    <h2>Upcoming Appointments</h2>
                    <div id="appointmentsList">
                        <?php if (empty($upcomingAppointments)): ?>
                            <p>No upcoming appointments.</p>
                        <?php else: ?>
                            <?php foreach ($upcomingAppointments as $appointment): ?>
                                <div class="appointment-card">
                                    <div class="appointment-header">
                                        <h4>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></h4>
                                        <span class="appointment-status status-<?php echo $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </div>
                                    <p><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></p>
                                    <p><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($appointment['appointment_date'])); ?></p>
                                    <?php if ($appointment['specialization']): ?>
                                        <p><i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($appointment['specialization']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($appointment['purpose']): ?>
                                        <p><i class="fas fa-notes-medical"></i> <?php echo htmlspecialchars($appointment['purpose']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Search Section -->
                <div id="search" class="content-section">
                    <h2>Search Medical Records</h2>
                    <form id="searchForm">
                        <div class="form-group">
                            <label for="searchQuery">Search Query:</label>
                            <input type="text" id="searchQuery" name="query" class="form-control" 
                                   placeholder="Enter keywords, reference number, or doctor name">
                        </div>
                        <div class="form-group">
                            <label for="searchType">Record Type:</label>
                            <select id="searchType" name="record_type" class="form-control">
                                <option value="">All Types</option>
                                <option value="consultation">Consultation</option>
                                <option value="diagnosis">Diagnosis</option>
                                <option value="surgery">Surgery</option>
                                <option value="treatment">Treatment</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dateFrom">Date From:</label>
                            <input type="date" id="dateFrom" name="date_from" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="dateTo">Date To:</label>
                            <input type="date" id="dateTo" name="date_to" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </form>
                    <div id="searchResults" class="mt-4"></div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <h3>Quick Actions</h3>
                <div class="quick-actions">
                    <button class="btn btn-secondary btn-block mb-2" onclick="requestAppointment()">
                        <i class="fas fa-calendar-plus"></i> Request Appointment
                    </button>
                    <button class="btn btn-secondary btn-block mb-2" onclick="downloadRecords()">
                        <i class="fas fa-download"></i> Download Records
                    </button>
                    <button class="btn btn-secondary btn-block mb-2" onclick="contactDoctor()">
                        <i class="fas fa-envelope"></i> Contact Doctor
                    </button>
                </div>
                
                <h3 class="mt-4">Recent Activity</h3>
                <div class="activity-feed">
                    <!-- Activity items would be loaded here -->
                    <p class="text-muted">No recent activity</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Tab navigation
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                const section = $(this).data('section');
                
                $('.nav-tab').removeClass('active');
                $(this).addClass('active');
                
                $('.content-section').removeClass('active');
                $('#' + section).addClass('active');
            });
            
            // Search functionality
            $('#recordsSearch').on('keyup', function() {
                const query = $(this).val().toLowerCase();
                $('.record-card').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(text.indexOf(query) > -1);
                });
            });
        });
        
        function logout() {
            $.post('includes/auth.php', { action: 'logout' }, function(response) {
                if (response.success) {
                    window.location.href = 'login.php';
                }
            }, 'json');
        }
        
        function editProfile() {
            // Implementation for profile editing
            alert('Profile editing feature will be implemented');
        }
        
        function requestAppointment() {
            // Implementation for appointment request
            alert('Appointment request feature will be implemented');
        }
        
        function downloadRecords() {
            // Implementation for downloading records
            alert('Download records feature will be implemented');
        }
        
        function contactDoctor() {
            // Implementation for contacting doctor
            alert('Contact doctor feature will be implemented');
        }
    </script>
</body>
</html>