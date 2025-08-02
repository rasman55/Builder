<?php
require_once '../includes/config.php';
requirePatient();

$pdo = getDBConnection();
$patient_id = $_SESSION['profile_id'];

// Get patient information
try {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get statistics for this patient
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM consultations WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $totalConsultations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM surgeries WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $totalSurgeries = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM diagnoses WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $totalDiagnoses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM payments WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $totalPayments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent consultations
    $stmt = $pdo->prepare("
        SELECT c.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name, d.specialization
        FROM consultations c 
        JOIN doctors d ON c.doctor_id = d.id 
        WHERE c.patient_id = ?
        ORDER BY c.consultation_date DESC LIMIT 5
    ");
    $stmt->execute([$patient_id]);
    $recentConsultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent payments
    $stmt = $pdo->prepare("
        SELECT * FROM payments 
        WHERE patient_id = ? 
        ORDER BY payment_date DESC LIMIT 5
    ");
    $stmt->execute([$patient_id]);
    $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Upcoming appointments
    $stmt = $pdo->prepare("
        SELECT c.*, d.first_name as doctor_first_name, d.last_name as doctor_last_name, d.specialization
        FROM consultations c 
        JOIN doctors d ON c.doctor_id = d.id 
        WHERE c.patient_id = ? AND c.consultation_date >= CURDATE()
        ORDER BY c.consultation_date ASC LIMIT 3
    ");
    $stmt->execute([$patient_id]);
    $upcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setMessage('danger', 'Database error: ' . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-user me-2"></i>
            Patient Dashboard
        </h1>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
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
                <p>Surgeries</p>
            </div>
            <i class="fas fa-procedures fa-3x opacity-50"></i>
        </div>
    </div>
    
    <div class="stats-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?php echo $totalDiagnoses; ?></h3>
                <p>Diagnoses</p>
            </div>
            <i class="fas fa-notes-medical fa-3x opacity-50"></i>
        </div>
    </div>
    
    <div class="stats-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?php echo $totalPayments; ?></h3>
                <p>Payments Made</p>
            </div>
            <i class="fas fa-credit-card fa-3x opacity-50"></i>
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
                        <a href="records.php" class="btn btn-primary w-100">
                            <i class="fas fa-notes-medical me-2"></i>
                            View Records
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="payments.php" class="btn btn-success w-100">
                            <i class="fas fa-credit-card me-2"></i>
                            Make Payment
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="profile.php" class="btn btn-info w-100">
                            <i class="fas fa-user-edit me-2"></i>
                            Update Profile
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <a href="records.php?print=1" class="btn btn-warning w-100">
                            <i class="fas fa-print me-2"></i>
                            Print Records
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Appointments -->
<?php if (!empty($upcomingAppointments)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Upcoming Appointments
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Reference ID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingAppointments as $appointment): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo formatDateTime($appointment['consultation_date']); ?></strong>
                                    </td>
                                    <td>
                                        <strong>Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($appointment['specialization'] ?? 'Not specified'); ?></td>
                                    <td>
                                        <code><?php echo htmlspecialchars($appointment['reference_id']); ?></code>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Scheduled</span>
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
                <a href="records.php" class="btn btn-sm btn-outline-primary">View All</a>
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
                                    <th>Doctor</th>
                                    <th>Specialization</th>
                                    <th>Reference ID</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentConsultations as $consultation): ?>
                                    <tr>
                                        <td><?php echo formatDate($consultation['consultation_date']); ?></td>
                                        <td>
                                            <strong>Dr. <?php echo htmlspecialchars($consultation['doctor_first_name'] . ' ' . $consultation['doctor_last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($consultation['specialization'] ?? 'Not specified'); ?></td>
                                        <td>
                                            <code><?php echo htmlspecialchars($consultation['reference_id']); ?></code>
                                        </td>
                                        <td>
                                            <a href="records.php?view=<?php echo $consultation['id']; ?>" 
                                               class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye me-1"></i>
                                                View Details
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
    
    <!-- Patient Information -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    Patient Information
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="fas fa-user fa-4x text-primary"></i>
                </div>
                <h6 class="text-center mb-3"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h6>
                
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <strong>Patient ID:</strong><br>
                        <?php echo htmlspecialchars($patient['patient_id']); ?>
                    </li>
                    <li class="mb-2">
                        <strong>Date of Birth:</strong><br>
                        <?php echo formatDate($patient['date_of_birth']); ?>
                    </li>
                    <li class="mb-2">
                        <strong>Gender:</strong><br>
                        <?php echo htmlspecialchars($patient['gender'] ?? 'Not specified'); ?>
                    </li>
                    <li class="mb-2">
                        <strong>Blood Type:</strong><br>
                        <?php echo htmlspecialchars($patient['blood_type'] ?? 'Not specified'); ?>
                    </li>
                    <li class="mb-2">
                        <strong>Phone:</strong><br>
                        <?php echo htmlspecialchars($patient['phone'] ?? 'Not specified'); ?>
                    </li>
                    <li class="mb-2">
                        <strong>Email:</strong><br>
                        <?php echo htmlspecialchars($patient['email'] ?? 'Not specified'); ?>
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
        
        <!-- Recent Payments -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-credit-card me-2"></i>
                    Recent Payments
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentPayments)): ?>
                    <p class="text-muted text-center">No payments found.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentPayments as $payment): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($payment['description'] ?? 'Payment'); ?></strong><br>
                                    <small class="text-muted"><?php echo formatDate($payment['payment_date']); ?></small>
                                </div>
                                <div class="text-end">
                                    <strong>$<?php echo number_format($payment['amount'], 2); ?></strong><br>
                                    <span class="badge bg-<?php echo $payment['status'] === 'Completed' ? 'success' : ($payment['status'] === 'Pending' ? 'warning' : 'danger'); ?>">
                                        <?php echo $payment['status']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>