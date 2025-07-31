<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user is authenticated and is a doctor
if (!isAuthenticated() || $_SESSION['user_type'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

$pdo = getDatabaseConnection();
$currentUser = getCurrentUser();

// Get doctor profile information
$profileStmt = $pdo->prepare("
    SELECT u.*, d.medical_license, d.years_of_experience, d.consultation_fee, d.hospital_id
    FROM users u 
    LEFT JOIN doctors d ON u.id = d.user_id 
    WHERE u.id = ?
");
$profileStmt->execute([$currentUser['id']]);
$profile = $profileStmt->fetch();

// Get doctor's statistics
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT mr.patient_id) as total_patients,
        COUNT(*) as total_records,
        COUNT(CASE WHEN mr.record_type = 'consultation' THEN 1 END) as consultations,
        COUNT(CASE WHEN mr.record_type = 'surgery' THEN 1 END) as surgeries,
        COUNT(CASE WHEN DATE(mr.date_time) = CURDATE() THEN 1 END) as today_records
    FROM medical_records mr
    WHERE mr.doctor_id = ?
");
$statsStmt->execute([$currentUser['id']]);
$stats = $statsStmt->fetch();

// Get recent patients
$recentPatientsStmt = $pdo->prepare("
    SELECT DISTINCT
        u.id, u.first_name, u.last_name, u.id_number,
        p.blood_type, p.allergies,
        MAX(mr.date_time) as last_visit
    FROM medical_records mr
    JOIN users u ON mr.patient_id = u.id
    LEFT JOIN patients p ON u.id = p.user_id
    WHERE mr.doctor_id = ?
    GROUP BY u.id
    ORDER BY last_visit DESC
    LIMIT 10
");
$recentPatientsStmt->execute([$currentUser['id']]);
$recentPatients = $recentPatientsStmt->fetchAll();

// Get today's appointments
$todayAppointmentsStmt = $pdo->prepare("
    SELECT a.*, 
           CONCAT(u.first_name, ' ', u.last_name) as patient_name,
           u.phone, u.id_number
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ? AND DATE(a.appointment_date) = CURDATE()
    ORDER BY a.appointment_date ASC
");
$todayAppointmentsStmt->execute([$currentUser['id']]);
$todayAppointments = $todayAppointmentsStmt->fetchAll();

// Get all patients for quick access
$allPatientsStmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.first_name, u.last_name, u.id_number, u.phone
    FROM users u
    WHERE u.user_type = 'patient' AND u.is_active = 1
    ORDER BY u.first_name, u.last_name
");
$allPatientsStmt->execute();
$allPatients = $allPatientsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Goba Hospital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .patient-card {
            background: white;
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .patient-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #3498db;
        }
        
        .patient-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .patient-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .patient-id {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .patient-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        
        .appointment-card {
            background: #f8f9fa;
            border-left: 4px solid #2ecc71;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0 8px 8px 0;
        }
        
        .appointment-time {
            font-weight: 600;
            color: #2ecc71;
            margin-bottom: 0.5rem;
        }
        
        .medical-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .patient-search {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .patient-search input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .patient-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ecf0f1;
            border-top: none;
            border-radius: 0 0 5px 5px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .patient-suggestion {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .patient-suggestion:hover {
            background: #f8f9fa;
        }
        
        .stat-card.doctor {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            color: white;
        }
        
        .stat-card.doctor i {
            color: white;
        }
        
        .record-type-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 1rem;
        }
        
        .record-type-option {
            padding: 10px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .record-type-option:hover,
        .record-type-option.active {
            border-color: #2ecc71;
            background: #f8f9fa;
            color: #2ecc71;
        }
        
        .file-upload-area {
            border: 2px dashed #ecf0f1;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            margin: 1rem 0;
            transition: border-color 0.3s ease;
        }
        
        .file-upload-area:hover {
            border-color: #2ecc71;
        }
        
        .file-upload-area.dragover {
            border-color: #2ecc71;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="welcome-section">
                <div class="user-info">
                    <h1>Dr. <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h1>
                    <p><i class="fas fa-id-card"></i> License: <?php echo htmlspecialchars($profile['medical_license'] ?: 'Not provided'); ?></p>
                    <p><i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($profile['specialization'] ?: 'General Practice'); ?></p>
                    <p><i class="fas fa-hospital"></i> <?php echo htmlspecialchars($profile['department'] ?: 'Not specified'); ?></p>
                </div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card doctor">
                <i class="fas fa-users"></i>
                <h3><?php echo $stats['total_patients']; ?></h3>
                <p>Total Patients</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-file-medical"></i>
                <h3><?php echo $stats['total_records']; ?></h3>
                <p>Medical Records</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-stethoscope"></i>
                <h3><?php echo $stats['consultations']; ?></h3>
                <p>Consultations</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-day"></i>
                <h3><?php echo $stats['today_records']; ?></h3>
                <p>Today's Records</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="dashboard-content">
            <div class="main-content">
                <!-- Navigation Tabs -->
                <div class="dashboard-nav">
                    <a href="#add-record" class="nav-tab active" data-section="add-record">
                        <i class="fas fa-plus"></i> Add Record
                    </a>
                    <a href="#patients" class="nav-tab" data-section="patients">
                        <i class="fas fa-users"></i> Patients
                    </a>
                    <a href="#search" class="nav-tab" data-section="search">
                        <i class="fas fa-search"></i> Search Records
                    </a>
                    <a href="#appointments" class="nav-tab" data-section="appointments">
                        <i class="fas fa-calendar"></i> Appointments
                    </a>
                    <a href="#profile" class="nav-tab" data-section="profile">
                        <i class="fas fa-user-md"></i> Profile
                    </a>
                </div>

                <!-- Add Medical Record Section -->
                <div id="add-record" class="content-section active">
                    <h2>Add Medical Record</h2>
                    <form id="medicalRecordForm" class="medical-form">
                        <div class="patient-search">
                            <label for="patientSearch">Select Patient:</label>
                            <input type="text" id="patientSearch" placeholder="Search patient by name or ID..." autocomplete="off">
                            <div id="patientSuggestions" class="patient-suggestions"></div>
                            <input type="hidden" id="selectedPatientId" name="patient_id">
                        </div>
                        
                        <div id="selectedPatientInfo" class="patient-card" style="display: none;">
                            <div class="patient-info">
                                <span id="selectedPatientName" class="patient-name"></span>
                                <span id="selectedPatientId_display" class="patient-id"></span>
                            </div>
                            <div class="patient-details">
                                <span id="selectedPatientPhone"></span>
                                <span id="selectedPatientBlood"></span>
                            </div>
                        </div>

                        <div class="record-type-selector">
                            <div class="record-type-option active" data-type="consultation">
                                <i class="fas fa-stethoscope"></i>
                                <br>Consultation
                            </div>
                            <div class="record-type-option" data-type="diagnosis">
                                <i class="fas fa-diagnoses"></i>
                                <br>Diagnosis
                            </div>
                            <div class="record-type-option" data-type="surgery">
                                <i class="fas fa-procedures"></i>
                                <br>Surgery
                            </div>
                            <div class="record-type-option" data-type="treatment">
                                <i class="fas fa-pills"></i>
                                <br>Treatment
                            </div>
                            <div class="record-type-option" data-type="emergency">
                                <i class="fas fa-ambulance"></i>
                                <br>Emergency
                            </div>
                        </div>
                        
                        <input type="hidden" id="recordType" name="record_type" value="consultation">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="recordDate">Date & Time:</label>
                                <input type="datetime-local" id="recordDate" name="date_time" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="referenceNumber">Reference Number:</label>
                                <input type="text" id="referenceNumber" name="reference_number" class="form-control" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="complaints">Patient Complaints:</label>
                            <textarea id="complaints" name="complaints" class="form-control" rows="3" 
                                      placeholder="Describe the patient's complaints and symptoms..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="symptoms">Observed Symptoms:</label>
                            <textarea id="symptoms" name="symptoms" class="form-control" rows="3" 
                                      placeholder="List observed symptoms and clinical findings..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="diagnosis">Diagnosis:</label>
                            <textarea id="diagnosis" name="diagnosis" class="form-control" rows="3" 
                                      placeholder="Enter diagnosis and medical assessment..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="treatment">Treatment Plan:</label>
                            <textarea id="treatment" name="treatment" class="form-control" rows="3" 
                                      placeholder="Describe treatment plan and procedures..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="prescriptions">Prescriptions:</label>
                            <textarea id="prescriptions" name="prescriptions" class="form-control" rows="3" 
                                      placeholder="List medications, dosages, and instructions..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="notes">Additional Notes:</label>
                            <textarea id="notes" name="notes" class="form-control" rows="2" 
                                      placeholder="Any additional notes or observations..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="followUpDate">Follow-up Date (Optional):</label>
                            <input type="date" id="followUpDate" name="follow_up_date" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Attach Files (Optional):</label>
                            <div class="file-upload-area" id="fileUploadArea">
                                <i class="fas fa-cloud-upload-alt fa-2x"></i>
                                <p>Drag and drop files here or <button type="button" onclick="$('#fileInput').click()">browse</button></p>
                                <input type="file" id="fileInput" name="files[]" multiple style="display: none;" 
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            </div>
                            <div id="fileList"></div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Medical Record
                        </button>
                    </form>
                </div>

                <!-- Patients Section -->
                <div id="patients" class="content-section">
                    <h2>My Patients</h2>
                    <div class="search-box">
                        <input type="text" id="patientsSearch" placeholder="Search patients...">
                        <i class="fas fa-search"></i>
                    </div>
                    
                    <div id="patientsList">
                        <?php foreach ($recentPatients as $patient): ?>
                            <div class="patient-card" onclick="viewPatientHistory(<?php echo $patient['id']; ?>)">
                                <div class="patient-info">
                                    <span class="patient-name"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></span>
                                    <span class="patient-id">ID: <?php echo htmlspecialchars($patient['id_number']); ?></span>
                                </div>
                                <div class="patient-details">
                                    <span><i class="fas fa-tint"></i> <?php echo htmlspecialchars($patient['blood_type'] ?: 'Unknown'); ?></span>
                                    <span><i class="fas fa-clock"></i> Last visit: <?php echo date('M d, Y', strtotime($patient['last_visit'])); ?></span>
                                    <?php if ($patient['allergies']): ?>
                                        <span><i class="fas fa-exclamation-triangle"></i> Allergies: <?php echo htmlspecialchars($patient['allergies']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Search Records Section -->
                <div id="search" class="content-section">
                    <h2>Search Medical Records</h2>
                    <form id="searchForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="searchQuery">Search Query:</label>
                                <input type="text" id="searchQuery" name="query" class="form-control" 
                                       placeholder="Patient name, reference number, diagnosis...">
                            </div>
                            <div class="form-group">
                                <label for="searchRecordType">Record Type:</label>
                                <select id="searchRecordType" name="record_type" class="form-control">
                                    <option value="">All Types</option>
                                    <option value="consultation">Consultation</option>
                                    <option value="diagnosis">Diagnosis</option>
                                    <option value="surgery">Surgery</option>
                                    <option value="treatment">Treatment</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="searchDateFrom">Date From:</label>
                                <input type="date" id="searchDateFrom" name="date_from" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="searchDateTo">Date To:</label>
                                <input type="date" id="searchDateTo" name="date_to" class="form-control">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search Records
                        </button>
                    </form>
                    <div id="searchResults" class="mt-4"></div>
                </div>

                <!-- Appointments Section -->
                <div id="appointments" class="content-section">
                    <h2>Today's Appointments</h2>
                    <div id="appointmentsList">
                        <?php if (empty($todayAppointments)): ?>
                            <p>No appointments scheduled for today.</p>
                        <?php else: ?>
                            <?php foreach ($todayAppointments as $appointment): ?>
                                <div class="appointment-card">
                                    <div class="appointment-time">
                                        <?php echo date('H:i', strtotime($appointment['appointment_date'])); ?> - 
                                        <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                    </div>
                                    <p><i class="fas fa-id-card"></i> ID: <?php echo htmlspecialchars($appointment['id_number']); ?></p>
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($appointment['phone'] ?: 'No phone'); ?></p>
                                    <?php if ($appointment['purpose']): ?>
                                        <p><i class="fas fa-notes-medical"></i> <?php echo htmlspecialchars($appointment['purpose']); ?></p>
                                    <?php endif; ?>
                                    <p><span class="appointment-status status-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span></p>
                                    <div class="appointment-actions">
                                        <button class="btn btn-primary btn-sm" onclick="startConsultation(<?php echo $appointment['patient_id']; ?>)">
                                            <i class="fas fa-stethoscope"></i> Start Consultation
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
                            <p>Dr. <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Medical License:</label>
                            <p><?php echo htmlspecialchars($profile['medical_license'] ?: 'Not provided'); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Specialization:</label>
                            <p><?php echo htmlspecialchars($profile['specialization'] ?: 'General Practice'); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Department:</label>
                            <p><?php echo htmlspecialchars($profile['department'] ?: 'Not specified'); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Years of Experience:</label>
                            <p><?php echo htmlspecialchars($profile['years_of_experience'] ?: 'Not specified'); ?> years</p>
                        </div>
                        <div class="form-group">
                            <label>Email:</label>
                            <p><?php echo htmlspecialchars($profile['email'] ?: 'Not provided'); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Phone:</label>
                            <p><?php echo htmlspecialchars($profile['phone'] ?: 'Not provided'); ?></p>
                        </div>
                        <button class="btn btn-primary" onclick="editProfile()">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <h3>Quick Actions</h3>
                <div class="quick-actions">
                    <button class="btn btn-secondary btn-block mb-2" onclick="addEmergencyRecord()">
                        <i class="fas fa-ambulance"></i> Emergency Record
                    </button>
                    <button class="btn btn-secondary btn-block mb-2" onclick="viewSchedule()">
                        <i class="fas fa-calendar-week"></i> View Schedule
                    </button>
                    <button class="btn btn-secondary btn-block mb-2" onclick="generateReport()">
                        <i class="fas fa-chart-bar"></i> Generate Report
                    </button>
                </div>
                
                <h3 class="mt-4">Recent Activity</h3>
                <div class="activity-feed">
                    <p class="text-muted">Loading recent activity...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        const allPatients = <?php echo json_encode($allPatients); ?>;
        
        $(document).ready(function() {
            // Initialize form
            initializeForm();
            
            // Patient search functionality
            $('#patientSearch').on('input', function() {
                const query = $(this).val().toLowerCase();
                if (query.length > 2) {
                    showPatientSuggestions(query);
                } else {
                    hidePatientSuggestions();
                }
            });
            
            // Record type selection
            $('.record-type-option').on('click', function() {
                $('.record-type-option').removeClass('active');
                $(this).addClass('active');
                $('#recordType').val($(this).data('type'));
            });
            
            // File upload
            setupFileUpload();
        });
        
        function initializeForm() {
            // Set current date and time
            const now = new Date();
            const dateTimeString = now.toISOString().slice(0, 16);
            $('#recordDate').val(dateTimeString);
            
            // Generate reference number
            generateReferenceNumber();
        }
        
        function generateReferenceNumber() {
            const now = new Date();
            const dateString = now.toISOString().slice(0, 10).replace(/-/g, '');
            const randomNum = Math.floor(Math.random() * 9999) + 1000;
            $('#referenceNumber').val('CON' + dateString + randomNum);
        }
        
        function showPatientSuggestions(query) {
            const suggestions = allPatients.filter(patient => 
                patient.first_name.toLowerCase().includes(query) ||
                patient.last_name.toLowerCase().includes(query) ||
                patient.id_number.toLowerCase().includes(query)
            );
            
            let html = '';
            suggestions.slice(0, 10).forEach(patient => {
                html += `
                    <div class="patient-suggestion" onclick="selectPatient(${patient.id}, '${patient.first_name} ${patient.last_name}', '${patient.id_number}', '${patient.phone || ''}')">
                        <strong>${patient.first_name} ${patient.last_name}</strong><br>
                        <small>ID: ${patient.id_number} ${patient.phone ? '| Phone: ' + patient.phone : ''}</small>
                    </div>
                `;
            });
            
            $('#patientSuggestions').html(html).show();
        }
        
        function hidePatientSuggestions() {
            $('#patientSuggestions').hide();
        }
        
        function selectPatient(id, name, idNumber, phone) {
            $('#selectedPatientId').val(id);
            $('#patientSearch').val(name);
            $('#selectedPatientName').text(name);
            $('#selectedPatientId_display').text('ID: ' + idNumber);
            $('#selectedPatientPhone').text(phone ? 'Phone: ' + phone : '');
            $('#selectedPatientInfo').show();
            hidePatientSuggestions();
        }
        
        function setupFileUpload() {
            const uploadArea = $('#fileUploadArea');
            
            uploadArea.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });
            
            uploadArea.on('dragleave dragend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });
            
            uploadArea.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                handleFiles(files);
            });
            
            $('#fileInput').on('change', function() {
                handleFiles(this.files);
            });
        }
        
        function handleFiles(files) {
            let html = '';
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                html += `
                    <div class="file-item">
                        <i class="fas fa-file"></i>
                        <span>${file.name}</span>
                        <span class="file-size">(${formatFileSize(file.size)})</span>
                    </div>
                `;
            }
            $('#fileList').html(html);
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function logout() {
            $.post('includes/auth.php', { action: 'logout' }, function(response) {
                if (response.success) {
                    window.location.href = 'login.php';
                }
            }, 'json');
        }
        
        function viewPatientHistory(patientId) {
            // Implementation for viewing patient history
            alert(`View patient history for patient ID: ${patientId}`);
        }
        
        function startConsultation(patientId) {
            // Select the patient and switch to add record tab
            const patient = allPatients.find(p => p.id == patientId);
            if (patient) {
                selectPatient(patient.id, patient.first_name + ' ' + patient.last_name, patient.id_number, patient.phone || '');
                $('.nav-tab').removeClass('active');
                $('.nav-tab[data-section="add-record"]').addClass('active');
                $('.content-section').removeClass('active');
                $('#add-record').addClass('active');
            }
        }
        
        function addEmergencyRecord() {
            $('.record-type-option').removeClass('active');
            $('.record-type-option[data-type="emergency"]').addClass('active');
            $('#recordType').val('emergency');
            $('.nav-tab').removeClass('active');
            $('.nav-tab[data-section="add-record"]').addClass('active');
            $('.content-section').removeClass('active');
            $('#add-record').addClass('active');
        }
        
        function viewSchedule() {
            alert('Schedule view feature will be implemented');
        }
        
        function generateReport() {
            alert('Report generation feature will be implemented');
        }
        
        function editProfile() {
            alert('Profile editing feature will be implemented');
        }
    </script>
</body>
</html>