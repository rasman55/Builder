<?php
require_once '../includes/config.php';
requireAdmin();

$pdo = getDBConnection();

// Get statistics
try {
    // Total patients
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM patients");
    $totalPatients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total doctors
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM doctors");
    $totalDoctors = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total staff
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM medical_staff");
    $totalStaff = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total consultations
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM consultations");
    $totalConsultations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent patients
    $stmt = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC LIMIT 5");
    $recentPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent consultations
    $stmt = $pdo->query("
        SELECT c.*, p.first_name, p.last_name, d.first_name as doctor_first_name, d.last_name as doctor_last_name 
        FROM consultations c 
        JOIN patients p ON c.patient_id = p.id 
        JOIN doctors d ON c.doctor_id = d.id 
        ORDER BY c.created_at DESC LIMIT 5
    ");
    $recentConsultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setMessage('danger', 'Database error: ' . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-tachometer-alt me-2"></i>
            Admin Dashboard
        </h1>
    </div>
</div>

<!-- Statistics Cards -->
<div class="dashboard-grid">
    <div class="stats-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?php echo $totalPatients; ?></h3>
                <p>Total Patients</p>
            </div>
            <i class="fas fa-users fa-3x opacity-50"></i>
        </div>
    </div>
    
    <div class="stats-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?php echo $totalDoctors; ?></h3>
                <p>Total Doctors</p>
            </div>
            <i class="fas fa-user-md fa-3x opacity-50"></i>
        </div>
    </div>
    
    <div class="stats-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?php echo $totalStaff; ?></h3>
                <p>Medical Staff</p>
            </div>
            <i class="fas fa-user-nurse fa-3x opacity-50"></i>
        </div>
    </div>
    
    <div class="stats-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?php echo $totalConsultations; ?></h3>
                <p>Total Consultations</p>
            </div>
            <i class="fas fa-stethoscope fa-3x opacity-50"></i>
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
                        <a href="patients.php" class="btn btn-primary w-100">
                            <i class="fas fa-user-plus me-2"></i>
                            Add Patient
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="doctors.php" class="btn btn-success w-100">
                            <i class="fas fa-user-md me-2"></i>
                            Add Doctor
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="staff.php" class="btn btn-info w-100">
                            <i class="fas fa-user-nurse me-2"></i>
                            Add Staff
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="hospitals.php" class="btn btn-warning w-100">
                            <i class="fas fa-hospital me-2"></i>
                            Manage Hospitals
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <!-- Recent Patients -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Recent Patients
                </h5>
                <a href="patients.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentPatients)): ?>
                    <p class="text-muted text-center">No patients found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>ID</th>
                                    <th>Date Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPatients as $patient): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($patient['patient_id']); ?></td>
                                        <td><?php echo formatDate($patient['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Consultations -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-stethoscope me-2"></i>
                    Recent Consultations
                </h5>
                <a href="../doctor/consultations.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentConsultations)): ?>
                    <p class="text-muted text-center">No consultations found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentConsultations as $consultation): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']); ?></strong>
                                        </td>
                                        <td>Dr. <?php echo htmlspecialchars($consultation['doctor_first_name'] . ' ' . $consultation['doctor_last_name']); ?></td>
                                        <td><?php echo formatDate($consultation['consultation_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- System Information -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    System Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Server Information</h6>
                        <ul class="list-unstyled">
                            <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                            <li><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></li>
                            <li><strong>Database:</strong> MySQL</li>
                            <li><strong>System Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Application Information</h6>
                        <ul class="list-unstyled">
                            <li><strong>Application Name:</strong> <?php echo SITE_NAME; ?></li>
                            <li><strong>Version:</strong> 1.0.0</li>
                            <li><strong>Last Updated:</strong> <?php echo date('Y-m-d'); ?></li>
                            <li><strong>Support:</strong> info@gobahospital.com</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>