<?php
require_once '../includes/config.php';
requireDoctor();

$pdo = getDBConnection();
$doctor_id = $_SESSION['profile_id'];

// Get doctor information
try {
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get statistics for this doctor
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM consultations WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $totalConsultations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM surgeries WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $totalSurgeries = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM diagnoses WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $totalDiagnoses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent consultations
    $stmt = $pdo->prepare("
        SELECT c.*, p.first_name, p.last_name, p.patient_id as patient_code
        FROM consultations c 
        JOIN patients p ON c.patient_id = p.id 
        WHERE c.doctor_id = ?
        ORDER BY c.consultation_date DESC LIMIT 5
    ");
    $stmt->execute([$doctor_id]);
    $recentConsultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Today's appointments (if any)
    $stmt = $pdo->prepare("
        SELECT c.*, p.first_name, p.last_name, p.patient_id as patient_code
        FROM consultations c 
        JOIN patients p ON c.patient_id = p.id 
        WHERE c.doctor_id = ? AND DATE(c.consultation_date) = CURDATE()
        ORDER BY c.consultation_date ASC
    ");
    $stmt->execute([$doctor_id]);
    $todayAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setMessage('danger', 'Database error: ' . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-user-md me-2"></i>
            Doctor Dashboard
        </h1>
        <p class="text-muted">Welcome back, Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="dashboard-grid">
    <div class="stats-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?php echo $totalConsultations; ?></h3>
                <p>Total Consultations</p>
            </div>
            <i class="fas fa-stethoscope fa-3x opacity-50"></i>
        </div>
    </div>
    
    <div class="stats-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?php echo $totalSurgeries; ?></h3>
                <p>Surgeries Performed</p>
            </div>
            <i class="fas fa-procedures fa-3x opacity-50"></i>
        </div>
    </div>
    
    <div class="stats-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?php echo $totalDiagnoses; ?></h3>
                <p>Diagnoses Made</p>
            </div>
            <i class="fas fa-notes-medical fa-3x opacity-50"></i>
        </div>
    </div>
    
    <div class="stats-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?php echo count($todayAppointments); ?></h3>
                <p>Today's Appointments</p>
            </div>
            <i class="fas fa-calendar-day fa-3x opacity-50"></i>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <a href="consultations.php" class="btn btn-primary w-100">
                            <i class="fas fa-stethoscope me-2"></i>
                            New Consultation
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="surgeries.php" class="btn btn-success w-100">
                            <i class="fas fa-procedures me-2"></i>
                            Record Surgery
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="diagnoses.php" class="btn btn-info w-100">
                            <i class="fas fa-notes-medical me-2"></i>
                            Add Diagnosis
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="patients.php" class="btn btn-warning w-100">
                            <i class="fas fa-search me-2"></i>
                            Search Patients
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Today's Appointments -->
<?php if (!empty($todayAppointments)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-day me-2"></i>
                    Today's Appointments
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Patient ID</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayAppointments as $appointment): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('H:i', strtotime($appointment['consultation_date'])); ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($appointment['patient_code']); ?></td>
                                    <td>
                                        <span class="badge bg-success">Scheduled</span>
                                    </td>
                                    <td>
                                        <a href="consultations.php?patient_id=<?php echo $appointment['patient_id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-stethoscope me-1"></i>
                                            Start Consultation
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Activity -->
<div class="row">
    <!-- Recent Consultations -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-stethoscope me-2"></i>
                    Recent Consultations
                </h5>
                <a href="consultations.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentConsultations)): ?>
                    <p class="text-muted text-center">No consultations found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Patient ID</th>
                                    <th>Reference ID</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentConsultations as $consultation): ?>
                                    <tr>
                                        <td><?php echo formatDate($consultation['consultation_date']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($consultation['patient_code']); ?></td>
                                        <td>
                                            <code><?php echo htmlspecialchars($consultation['reference_id']); ?></code>
                                        </td>
                                        <td>
                                            <a href="consultations.php?view=<?php echo $consultation['id']; ?>" 
                                               class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye me-1"></i>
                                                View
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
    
    <!-- Doctor Information -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-md me-2"></i>
                    Doctor Information
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="fas fa-user-md fa-4x text-primary"></i>
                </div>
                <h6 class="text-center mb-3">Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h6>
                
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <strong>Specialization:</strong><br>
                        <?php echo htmlspecialchars($doctor['specialization'] ?? 'Not specified'); ?>
                    </li>
                    <li class="mb-2">
                        <strong>License Number:</strong><br>
                        <?php echo htmlspecialchars($doctor['license_number'] ?? 'Not specified'); ?>
                    </li>
                    <li class="mb-2">
                        <strong>Phone:</strong><br>
                        <?php echo htmlspecialchars($doctor['phone'] ?? 'Not specified'); ?>
                    </li>
                    <li class="mb-2">
                        <strong>Email:</strong><br>
                        <?php echo htmlspecialchars($doctor['email'] ?? 'Not specified'); ?>
                    </li>
                </ul>
                
                <div class="text-center mt-3">
                    <a href="profile.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>
                        Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>