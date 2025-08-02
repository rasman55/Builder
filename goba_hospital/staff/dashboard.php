<?php
require_once '../includes/config.php';
requireStaff();

$pdo = getDBConnection();
$staff_id = $_SESSION['profile_id'];

// Get staff information
try {
    $stmt = $pdo->prepare("SELECT * FROM medical_staff WHERE id = ?");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get statistics for this staff member
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM medication_dosages WHERE staff_id = ?");
    $stmt->execute([$staff_id]);
    $totalMedications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent medication dosages
    $stmt = $pdo->prepare("
        SELECT md.*, p.first_name, p.last_name, p.patient_id as patient_code
        FROM medication_dosages md 
        JOIN patients p ON md.patient_id = p.id 
        WHERE md.staff_id = ?
        ORDER BY md.prescribed_date DESC LIMIT 5
    ");
    $stmt->execute([$staff_id]);
    $recentMedications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setMessage('danger', 'Database error: ' . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-user-nurse me-2"></i>
            Staff Dashboard
        </h1>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="dashboard-grid">
    <div class="stats-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?php echo $totalMedications; ?></h3>
                <p>Medications Prescribed</p>
            </div>
            <i class="fas fa-pills fa-3x opacity-50"></i>
        </div>
    </div>
    
    <div class="stats-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?php echo $staff['position'] ?? 'Staff'; ?></h3>
                <p>Position</p>
            </div>
            <i class="fas fa-user-nurse fa-3x opacity-50"></i>
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
                    <div class="col-md-4 col-sm-6">
                        <a href="medications.php" class="btn btn-primary w-100">
                            <i class="fas fa-pills me-2"></i>
                            Record Medication
                        </a>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <a href="patients.php" class="btn btn-success w-100">
                            <i class="fas fa-search me-2"></i>
                            Search Patients
                        </a>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <a href="profile.php" class="btn btn-info w-100">
                            <i class="fas fa-user-edit me-2"></i>
                            Update Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <!-- Recent Medications -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-pills me-2"></i>
                    Recent Medications
                </h5>
                <a href="medications.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentMedications)): ?>
                    <p class="text-muted text-center">No medications recorded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Patient ID</th>
                                    <th>Medication</th>
                                    <th>Dosage</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentMedications as $medication): ?>
                                    <tr>
                                        <td><?php echo formatDate($medication['prescribed_date']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($medication['first_name'] . ' ' . $medication['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($medication['patient_code']); ?></td>
                                        <td><?php echo htmlspecialchars($medication['medication_name']); ?></td>
                                        <td><?php echo htmlspecialchars($medication['dosage_amount']); ?></td>
                                        <td>
                                            <a href="medications.php?view=<?php echo $medication['id']; ?>" 
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
    
    <!-- Staff Information -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-nurse me-2"></i>
                    Staff Information
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="fas fa-user-nurse fa-4x text-primary"></i>
                </div>
                <h6 class="text-center mb-3"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></h6>
                
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <strong>Position:</strong><br>
                        <?php echo htmlspecialchars($staff['position'] ?? 'Not specified'); ?>
                    </li>
                    <li class="mb-2">
                        <strong>Staff ID:</strong><br>
                        <?php echo htmlspecialchars($staff['staff_id']); ?>
                    </li>
                    <li class="mb-2">
                        <strong>Phone:</strong><br>
                        <?php echo htmlspecialchars($staff['phone'] ?? 'Not specified'); ?>
                    </li>
                    <li class="mb-2">
                        <strong>Email:</strong><br>
                        <?php echo htmlspecialchars($staff['email'] ?? 'Not specified'); ?>
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