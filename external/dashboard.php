<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'external_health') {
    header('Location: login.php');
    exit();
}

$doctor_ssn = $_SESSION['user_id'];
$error = '';
$success = '';

// Get doctor's external health office records
try {
    $stmt = $db->prepare("SELECT eho.*, p.first_name, p.last_name FROM external_health_office eho 
                         JOIN patient p ON eho.patient_ssn = p.ssn 
                         WHERE eho.doctor_ssn = ? 
                         ORDER BY eho.created_at DESC");
    $stmt->execute([$doctor_ssn]);
    $external_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all patients for dropdown
    $stmt = $db->prepare("SELECT ssn, first_name, last_name FROM patient ORDER BY first_name, last_name");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_ssn = $_POST['patient_ssn'];
    $description = $_POST['description'];
    $destination_hospital = $_POST['destination_hospital'];
    $destination_link = $_POST['destination_link'];
    
    // Handle file upload
    $file_path = '';
    $file_type = '';
    
    if (isset($_FILES['patient_file']) && $_FILES['patient_file']['error'] == 0) {
        $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
        $file_extension = strtolower(pathinfo($_FILES['patient_file']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_extension, $allowed_types)) {
            $upload_dir = '../uploads/external_health/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . $_FILES['patient_file']['name'];
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['patient_file']['tmp_name'], $file_path)) {
                $file_path = 'uploads/external_health/' . $file_name;
                $file_type = $file_extension;
            } else {
                $error = 'Failed to upload file.';
            }
        } else {
            $error = 'Invalid file type. Only PDF, JPG, JPEG, and PNG files are allowed.';
        }
    }
    
    if (empty($error)) {
        try {
            $stmt = $db->prepare("INSERT INTO external_health_office (doctor_ssn, patient_ssn, file_path, file_type, 
                                 description, destination_hospital, destination_link, status) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            $result = $stmt->execute([
                $doctor_ssn, $patient_ssn, $file_path, $file_type, 
                $description, $destination_hospital, $destination_link
            ]);
            
            if ($result) {
                $success = 'Patient information uploaded successfully!';
                $_POST = array(); // Clear form
            } else {
                $error = 'Failed to upload patient information.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>External Health Office Dashboard - Goba Hospital</title>
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
                        <i class="fas fa-hospital-alt fa-2x text-danger"></i>
                        <h5 class="mt-2">External Health Office</h5>
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
                            <a class="nav-link" href="upload.php">
                                <i class="fas fa-upload"></i> Upload Files
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="records.php">
                                <i class="fas fa-list"></i> All Records
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
                    <h1 class="h2">External Health Office Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-upload me-1"></i>Upload Patient Info
                        </button>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Welcome Message -->
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    Welcome to the External Health Office Portal, Dr. <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>! 
                    Here you can upload and share patient information with other healthcare facilities.
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="dashboard-card danger">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-upload fa-2x text-danger"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-0"><?php echo count($external_records); ?></h5>
                                    <p class="text-muted mb-0">Total Uploads</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="dashboard-card warning">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-clock fa-2x text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-0"><?php echo count(array_filter($external_records, function($r) { return $r['status'] === 'pending'; })); ?></h5>
                                    <p class="text-muted mb-0">Pending</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="dashboard-card success">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-0"><?php echo count(array_filter($external_records, function($r) { return $r['status'] === 'sent'; })); ?></h5>
                                    <p class="text-muted mb-0">Sent</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="dashboard-card info">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-hospital fa-2x text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-0"><?php echo count(array_unique(array_column($external_records, 'destination_hospital'))); ?></h5>
                                    <p class="text-muted mb-0">Hospitals</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Uploads -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Recent Uploads
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($external_records)): ?>
                            <p class="text-muted">No uploads found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Patient</th>
                                            <th>File Type</th>
                                            <th>Destination</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($external_records, 0, 10) as $record): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y H:i', strtotime($record['created_at'])); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php
                                                    $file_icons = [
                                                        'pdf' => 'fas fa-file-pdf text-danger',
                                                        'jpg' => 'fas fa-file-image text-success',
                                                        'jpeg' => 'fas fa-file-image text-success',
                                                        'png' => 'fas fa-file-image text-info'
                                                    ];
                                                    $icon_class = $file_icons[$record['file_type']] ?? 'fas fa-file';
                                                    ?>
                                                    <i class="<?php echo $icon_class; ?> me-2"></i>
                                                    <?php echo strtoupper($record['file_type']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($record['destination_hospital']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_classes = [
                                                        'pending' => 'bg-warning',
                                                        'sent' => 'bg-success',
                                                        'received' => 'bg-info'
                                                    ];
                                                    $status_class = $status_classes[$record['status']] ?? 'bg-secondary';
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($record['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($record['file_path']): ?>
                                                        <a href="../<?php echo $record['file_path']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-outline-info" 
                                                            onclick="viewDetails('<?php echo $record['id']; ?>')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="records.php" class="btn btn-outline-primary">
                                <i class="fas fa-list me-1"></i>View All Records
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-upload me-2"></i>Upload Patient Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="patient_ssn" class="form-label">
                                    <i class="fas fa-user me-2"></i>Patient *
                                </label>
                                <select class="form-select" id="patient_ssn" name="patient_ssn" required>
                                    <option value="">Select Patient</option>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo htmlspecialchars($patient['ssn']); ?>"
                                                <?php echo (isset($_POST['patient_ssn']) && $_POST['patient_ssn'] == $patient['ssn']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a patient.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="destination_hospital" class="form-label">
                                    <i class="fas fa-hospital me-2"></i>Destination Hospital *
                                </label>
                                <input type="text" class="form-control" id="destination_hospital" 
                                       name="destination_hospital" 
                                       value="<?php echo isset($_POST['destination_hospital']) ? htmlspecialchars($_POST['destination_hospital']) : ''; ?>" 
                                       required>
                                <div class="invalid-feedback">
                                    Please enter destination hospital.
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="patient_file" class="form-label">
                                <i class="fas fa-file me-2"></i>Patient File (PDF, JPG, PNG) *
                            </label>
                            <input type="file" class="form-control" id="patient_file" name="patient_file" 
                                   accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="form-text">Maximum file size: 10MB</div>
                            <div class="invalid-feedback">
                                Please select a file.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-comment me-2"></i>Description
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Describe the patient information being shared..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="destination_link" class="form-label">
                                <i class="fas fa-link me-2"></i>Destination Link
                            </label>
                            <input type="url" class="form-control" id="destination_link" 
                                   name="destination_link" 
                                   value="<?php echo isset($_POST['destination_link']) ? htmlspecialchars($_POST['destination_link']) : ''; ?>" 
                                   placeholder="https://example.com">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-upload me-2"></i>Upload & Send
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        function viewDetails(recordId) {
            // Implement record details view
            alert('Record details for ID: ' + recordId);
        }
    </script>
</body>
</html>