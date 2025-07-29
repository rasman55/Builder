<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

$doctor_ssn = $_SESSION['user_id'];

// Get doctor information
try {
    $stmt = $db->prepare("SELECT d.*, h.name as hospital_name FROM doctor d 
                         LEFT JOIN hospital h ON d.hospital_id = h.id 
                         WHERE d.ssn = ?");
    $stmt->execute([$doctor_ssn]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get consultation count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM consultation WHERE doctor_ssn = ?");
    $stmt->execute([$doctor_ssn]);
    $consultation_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get operation count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM operation WHERE doctor_ssn = ?");
    $stmt->execute([$doctor_ssn]);
    $operation_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get diagnosis count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM diagnosis WHERE doctor_ssn = ?");
    $stmt->execute([$doctor_ssn]);
    $diagnosis_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get today's consultations
    $stmt = $db->prepare("SELECT c.*, p.first_name, p.last_name FROM consultation c 
                         JOIN patient p ON c.patient_ssn = p.ssn 
                         WHERE c.doctor_ssn = ? AND DATE(c.consultation_date) = CURDATE()
                         ORDER BY c.consultation_date DESC");
    $stmt->execute([$doctor_ssn]);
    $today_consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent patients
    $stmt = $db->prepare("SELECT DISTINCT p.*, MAX(c.consultation_date) as last_visit 
                         FROM patient p 
                         JOIN consultation c ON p.ssn = c.patient_ssn 
                         WHERE c.doctor_ssn = ? 
                         GROUP BY p.ssn 
                         ORDER BY last_visit DESC 
                         LIMIT 5");
    $stmt->execute([$doctor_ssn]);
    $recent_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Goba Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-md fa-2x text-success"></i>
                        <h5 class="mt-2">Doctor Portal</h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                        <small class="text-info"><?php echo htmlspecialchars($_SESSION['specialization']); ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="consultations.php">
                                <i class="fas fa-stethoscope"></i> Consultations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="operations.php">
                                <i class="fas fa-procedures"></i> Operations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="diagnoses.php">
                                <i class="fas fa-microscope"></i> Diagnoses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="patients.php">
                                <i class="fas fa-users"></i> Patients
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="search.php">
                                <i class="fas fa-search"></i> Search Records
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="new-consultation.php" class="btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i>New Consultation
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i>Print
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    Welcome back, Dr. <strong><?php echo htmlspecialchars($doctor['first_name']); ?></strong>! 
                    Here's your medical practice overview.
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="dashboard-card success">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-stethoscope fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-0"><?php echo $consultation_count; ?></h5>
                                    <p class="text-muted mb-0">Total Consultations</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="dashboard-card info">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-procedures fa-2x text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-0"><?php echo $operation_count; ?></h5>
                                    <p class="text-muted mb-0">Operations</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="dashboard-card warning">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-microscope fa-2x text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-0"><?php echo $diagnosis_count; ?></h5>
                                    <p class="text-muted mb-0">Diagnoses</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="dashboard-card primary">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-calendar-day fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-0"><?php echo count($today_consultations); ?></h5>
                                    <p class="text-muted mb-0">Today's Consultations</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Consultations -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-day me-2"></i>Today's Consultations
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($today_consultations)): ?>
                                    <p class="text-muted">No consultations scheduled for today.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Patient</th>
                                                    <th>Complaints</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($today_consultations as $consultation): ?>
                                                    <tr>
                                                        <td><?php echo date('H:i', strtotime($consultation['consultation_date'])); ?></td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']); ?></strong>
                                                        </td>
                                                        <td><?php echo htmlspecialchars(substr($consultation['complaints'], 0, 50)) . '...'; ?></td>
                                                        <td>
                                                            <span class="badge bg-success">Completed</span>
                                                        </td>
                                                        <td>
                                                            <a href="view-consultation.php?id=<?php echo $consultation['id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user me-2"></i>Doctor Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Name:</strong><br>Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></p>
                                <p><strong>Specialization:</strong><br><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                                <p><strong>License:</strong><br><?php echo htmlspecialchars($doctor['license_number']); ?></p>
                                <p><strong>Hospital:</strong><br><?php echo htmlspecialchars($doctor['hospital_name']); ?></p>
                                <p><strong>Experience:</strong><br><?php echo htmlspecialchars($doctor['experience_years']); ?> years</p>
                                <a href="profile.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i>Edit Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Patients -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-users me-2"></i>Recent Patients
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_patients)): ?>
                                    <p class="text-muted">No recent patients found.</p>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($recent_patients as $patient): ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card border">
                                                    <div class="card-body">
                                                        <h6 class="card-title">
                                                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                                        </h6>
                                                        <p class="card-text small text-muted">
                                                            <strong>SSN:</strong> <?php echo htmlspecialchars($patient['ssn']); ?><br>
                                                            <strong>Last Visit:</strong> <?php echo date('M d, Y', strtotime($patient['last_visit'])); ?><br>
                                                            <strong>Age:</strong> <?php echo date_diff(date_create($patient['date_of_birth']), date_create('today'))->y; ?> years
                                                        </p>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="patient-records.php?ssn=<?php echo $patient['ssn']; ?>" 
                                                               class="btn btn-outline-primary">
                                                                <i class="fas fa-file-medical"></i> Records
                                                            </a>
                                                            <a href="new-consultation.php?patient_ssn=<?php echo $patient['ssn']; ?>" 
                                                               class="btn btn-outline-success">
                                                                <i class="fas fa-plus"></i> Consult
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <a href="patients.php" class="btn btn-outline-primary">
                                        <i class="fas fa-list me-1"></i>View All Patients
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="new-consultation.php" class="btn btn-success w-100">
                                            <i class="fas fa-stethoscope me-2"></i>New Consultation
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="search.php" class="btn btn-primary w-100">
                                            <i class="fas fa-search me-2"></i>Search Patient
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="new-operation.php" class="btn btn-info w-100">
                                            <i class="fas fa-procedures me-2"></i>Record Operation
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="new-diagnosis.php" class="btn btn-warning w-100">
                                            <i class="fas fa-microscope me-2"></i>Record Diagnosis
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>