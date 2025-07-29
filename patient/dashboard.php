<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$patient_ssn = $_SESSION['user_id'];

// Get patient information
try {
    $stmt = $db->prepare("SELECT * FROM patient WHERE ssn = ?");
    $stmt->execute([$patient_ssn]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get consultation count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM consultation WHERE patient_ssn = ?");
    $stmt->execute([$patient_ssn]);
    $consultation_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get operation count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM operation WHERE patient_ssn = ?");
    $stmt->execute([$patient_ssn]);
    $operation_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get diagnosis count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM diagnosis WHERE patient_ssn = ?");
    $stmt->execute([$patient_ssn]);
    $diagnosis_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get recent consultations
    $stmt = $db->prepare("SELECT c.*, d.first_name, d.last_name FROM consultation c 
                         JOIN doctor d ON c.doctor_ssn = d.ssn 
                         WHERE c.patient_ssn = ? 
                         ORDER BY c.consultation_date DESC LIMIT 5");
    $stmt->execute([$patient_ssn]);
    $recent_consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Goba Hospital</title>
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
                        <i class="fas fa-user-injured fa-2x text-primary"></i>
                        <h5 class="mt-2">Patient Portal</h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
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
                            <a class="nav-link" href="payments.php">
                                <i class="fas fa-credit-card"></i> Payments
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
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i>Print
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    Welcome back, <strong><?php echo htmlspecialchars($patient['first_name']); ?></strong>! 
                    Here's your medical information overview.
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
                                    <p class="text-muted mb-0">Consultations</p>
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
                                    <i class="fas fa-calendar-check fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-0"><?php echo date('Y'); ?></h5>
                                    <p class="text-muted mb-0">Current Year</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Patient Information -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user me-2"></i>Personal Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p><strong>Name:</strong><br><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                                        <p><strong>SSN:</strong><br><?php echo htmlspecialchars($patient['ssn']); ?></p>
                                        <p><strong>Date of Birth:</strong><br><?php echo htmlspecialchars($patient['date_of_birth']); ?></p>
                                        <p><strong>Gender:</strong><br><?php echo htmlspecialchars($patient['gender']); ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p><strong>Blood Type:</strong><br><?php echo htmlspecialchars($patient['blood_type']); ?></p>
                                        <p><strong>Phone:</strong><br><?php echo htmlspecialchars($patient['phone']); ?></p>
                                        <p><strong>Email:</strong><br><?php echo htmlspecialchars($patient['email']); ?></p>
                                        <p><strong>Emergency Contact:</strong><br><?php echo htmlspecialchars($patient['emergency_contact_name']); ?></p>
                                    </div>
                                </div>
                                <a href="profile.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i>Edit Profile
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>Recent Consultations
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_consultations)): ?>
                                    <p class="text-muted">No recent consultations found.</p>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recent_consultations as $consultation): ?>
                                            <div class="list-group-item border-0 px-0">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">Dr. <?php echo htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']); ?></h6>
                                                        <p class="mb-1 text-muted small">
                                                            <?php echo htmlspecialchars($consultation['complaints']); ?>
                                                        </p>
                                                        <small class="text-muted">
                                                            <?php echo date('M d, Y H:i', strtotime($consultation['consultation_date'])); ?>
                                                        </small>
                                                    </div>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($consultation['reference_number']); ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <a href="consultations.php" class="btn btn-outline-primary btn-sm mt-3">
                                        <i class="fas fa-list me-1"></i>View All
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
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
                                        <a href="search.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-search me-2"></i>Search Records
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="payments.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-credit-card me-2"></i>Make Payment
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="profile.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-user-edit me-2"></i>Update Profile
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="consultations.php" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-file-medical me-2"></i>View Records
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