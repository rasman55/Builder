<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}

$db = Database::getInstance();

// Get statistics
$stats = [
    'patients' => $db->fetchOne("SELECT COUNT(*) as count FROM patients")['count'],
    'doctors' => $db->fetchOne("SELECT COUNT(*) as count FROM doctors")['count'],
    'staff' => $db->fetchOne("SELECT COUNT(*) as count FROM medical_staff")['count'],
    'consultations' => $db->fetchOne("SELECT COUNT(*) as count FROM consultations")['count'],
    'operations' => $db->fetchOne("SELECT COUNT(*) as count FROM operations")['count'],
    'diagnoses' => $db->fetchOne("SELECT COUNT(*) as count FROM diagnoses")['count'],
    'payments' => $db->fetchOne("SELECT COUNT(*) as count FROM payments")['count'],
    'hospitals' => $db->fetchOne("SELECT COUNT(*) as count FROM hospitals")['count']
];

// Get recent activities
$recent_consultations = $db->fetchAll("
    SELECT c.*, p.first_name, p.last_name, d.first_name as doctor_first, d.last_name as doctor_last
    FROM consultations c
    JOIN patients p ON c.patient_ssn = p.ssn
    JOIN doctors d ON c.doctor_ssn = d.ssn
    ORDER BY c.created_at DESC
    LIMIT 5
");

$recent_payments = $db->fetchAll("
    SELECT p.*, pt.first_name, pt.last_name
    FROM payments p
    JOIN patients pt ON p.patient_ssn = pt.ssn
    ORDER BY p.created_at DESC
    LIMIT 5
");

$message = get_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-hospital fa-2x text-primary"></i>
                        <h5 class="text-white mt-2">Goba Hospital</h5>
                        <small class="text-muted">Admin Panel</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="patients.php">
                                <i class="fas fa-users"></i> Patients
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="doctors.php">
                                <i class="fas fa-user-md"></i> Doctors
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="staff.php">
                                <i class="fas fa-user-nurse"></i> Staff
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="hospitals.php">
                                <i class="fas fa-hospital"></i> Hospitals
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
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportDashboard()">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="printDashboard()">
                                <i class="fas fa-print me-1"></i>Print
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message['message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card">
                            <div class="stat-number text-primary"><?php echo $stats['patients']; ?></div>
                            <div class="stat-label">Total Patients</div>
                            <i class="fas fa-users fa-2x text-primary position-absolute top-0 end-0 m-3 opacity-25"></i>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card">
                            <div class="stat-number text-success"><?php echo $stats['doctors']; ?></div>
                            <div class="stat-label">Total Doctors</div>
                            <i class="fas fa-user-md fa-2x text-success position-absolute top-0 end-0 m-3 opacity-25"></i>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card">
                            <div class="stat-number text-warning"><?php echo $stats['staff']; ?></div>
                            <div class="stat-label">Medical Staff</div>
                            <i class="fas fa-user-nurse fa-2x text-warning position-absolute top-0 end-0 m-3 opacity-25"></i>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card">
                            <div class="stat-number text-info"><?php echo $stats['hospitals']; ?></div>
                            <div class="stat-label">Hospitals</div>
                            <i class="fas fa-hospital fa-2x text-info position-absolute top-0 end-0 m-3 opacity-25"></i>
                        </div>
                    </div>
                </div>

                <!-- Medical Records Statistics -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card">
                            <div class="stat-number text-primary"><?php echo $stats['consultations']; ?></div>
                            <div class="stat-label">Consultations</div>
                            <i class="fas fa-stethoscope fa-2x text-primary position-absolute top-0 end-0 m-3 opacity-25"></i>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card">
                            <div class="stat-number text-danger"><?php echo $stats['operations']; ?></div>
                            <div class="stat-label">Operations</div>
                            <i class="fas fa-procedures fa-2x text-danger position-absolute top-0 end-0 m-3 opacity-25"></i>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card">
                            <div class="stat-number text-warning"><?php echo $stats['diagnoses']; ?></div>
                            <div class="stat-label">Diagnoses</div>
                            <i class="fas fa-microscope fa-2x text-warning position-absolute top-0 end-0 m-3 opacity-25"></i>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card">
                            <div class="stat-number text-success"><?php echo $stats['payments']; ?></div>
                            <div class="stat-label">Payments</div>
                            <i class="fas fa-credit-card fa-2x text-success position-absolute top-0 end-0 m-3 opacity-25"></i>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row">
                    <!-- Recent Consultations -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-stethoscope me-2"></i>Recent Consultations
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_consultations)): ?>
                                    <p class="text-muted">No recent consultations.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Patient</th>
                                                    <th>Doctor</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_consultations as $consultation): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($consultation['doctor_first'] . ' ' . $consultation['doctor_last']); ?></td>
                                                        <td><?php echo format_datetime($consultation['consultation_date']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $consultation['status'] == 'Active' ? 'success' : ($consultation['status'] == 'Completed' ? 'primary' : 'secondary'); ?>">
                                                                <?php echo $consultation['status']; ?>
                                                            </span>
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

                    <!-- Recent Payments -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-credit-card me-2"></i>Recent Payments
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_payments)): ?>
                                    <p class="text-muted">No recent payments.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Patient</th>
                                                    <th>Amount</th>
                                                    <th>Method</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_payments as $payment): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                                        <td><?php echo number_format($payment['amount'], 2); ?> ETB</td>
                                                        <td><?php echo $payment['payment_method']; ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $payment['status'] == 'Completed' ? 'success' : ($payment['status'] == 'Pending' ? 'warning' : 'danger'); ?>">
                                                                <?php echo $payment['status']; ?>
                                                            </span>
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
                                        <a href="patients.php?action=add" class="btn btn-primary w-100">
                                            <i class="fas fa-user-plus me-2"></i>Add Patient
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="doctors.php?action=add" class="btn btn-success w-100">
                                            <i class="fas fa-user-md me-2"></i>Add Doctor
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="staff.php?action=add" class="btn btn-warning w-100">
                                            <i class="fas fa-user-nurse me-2"></i>Add Staff
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="reports.php" class="btn btn-info w-100">
                                            <i class="fas fa-chart-bar me-2"></i>Generate Report
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function exportDashboard() {
            // Implementation for exporting dashboard data
            alert('Export functionality will be implemented.');
        }

        function printDashboard() {
            window.print();
        }
    </script>
</body>
</html>