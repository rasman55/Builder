<?php
require_once '../includes/config.php';
requireExternal();

$pdo = getDBConnection();

// Get external user information
try {
    $stmt = $pdo->prepare("SELECT * FROM external_login WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $external_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM external_patient_info");
    $totalUploads = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent uploads
    $stmt = $pdo->query("
        SELECT * FROM external_patient_info 
        ORDER BY upload_date DESC LIMIT 5
    ");
    $recentUploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    setMessage('danger', 'Database error: ' . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="fas fa-exchange-alt me-2"></i>
            External Health Office Dashboard
        </h1>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($external_user['organization_name'] ?? 'External User'); ?></p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="dashboard-grid">
    <div class="stats-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?php echo $totalUploads; ?></h3>
                <p>Total Uploads</p>
            </div>
            <i class="fas fa-upload fa-3x opacity-50"></i>
        </div>
    </div>
    
    <div class="stats-card">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3><?php echo htmlspecialchars($external_user['organization_name'] ?? 'External'); ?></h3>
                <p>Organization</p>
            </div>
            <i class="fas fa-building fa-3x opacity-50"></i>
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
                        <a href="upload.php" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-2"></i>
                            Upload Records
                        </a>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <a href="records.php" class="btn btn-success w-100">
                            <i class="fas fa-folder-open me-2"></i>
                            View Records
                        </a>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <a href="transfer.php" class="btn btn-info w-100">
                            <i class="fas fa-exchange-alt me-2"></i>
                            Transfer Data
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <!-- Recent Uploads -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-upload me-2"></i>
                    Recent Uploads
                </h5>
                <a href="records.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentUploads)): ?>
                    <p class="text-muted text-center">No uploads found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient Name</th>
                                    <th>Source Hospital</th>
                                    <th>Uploaded By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUploads as $upload): ?>
                                    <tr>
                                        <td><?php echo formatDate($upload['upload_date']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($upload['patient_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($upload['source_hospital']); ?></td>
                                        <td><?php echo htmlspecialchars($upload['uploaded_by']); ?></td>
                                        <td>
                                            <a href="records.php?view=<?php echo $upload['id']; ?>" 
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
    
    <!-- External Office Information -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-building me-2"></i>
                    Office Information
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="fas fa-building fa-4x text-primary"></i>
                </div>
                <h6 class="text-center mb-3"><?php echo htmlspecialchars($external_user['organization_name'] ?? 'External Health Office'); ?></h6>
                
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <strong>Username:</strong><br>
                        <?php echo htmlspecialchars($external_user['username']); ?>
                    </li>
                    <li class="mb-2">
                        <strong>Access Level:</strong><br>
                        External Health Office
                    </li>
                    <li class="mb-2">
                        <strong>Account Created:</strong><br>
                        <?php echo formatDate($external_user['created_at']); ?>
                    </li>
                </ul>
                
                <div class="text-center mt-3">
                    <a href="profile.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>
                        Update Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Upload Guidelines -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Upload Guidelines
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Supported formats: PDF, DOC, DOCX, JPG, PNG
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Maximum file size: 10MB
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Include patient identification
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        Ensure data accuracy
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>