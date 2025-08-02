<?php
require_once 'config.php';
startSecureSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-hospital me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../about.php">About</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <?php if ($_SESSION['user_type'] === 'admin'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    Admin Panel
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="../admin/dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="../admin/patients.php">Manage Patients</a></li>
                                    <li><a class="dropdown-item" href="../admin/doctors.php">Manage Doctors</a></li>
                                    <li><a class="dropdown-item" href="../admin/staff.php">Manage Staff</a></li>
                                    <li><a class="dropdown-item" href="../admin/hospitals.php">Manage Hospitals</a></li>
                                </ul>
                            </li>
                        <?php elseif ($_SESSION['user_type'] === 'doctor'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    Doctor Panel
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="../doctor/dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="../doctor/consultations.php">Consultations</a></li>
                                    <li><a class="dropdown-item" href="../doctor/surgeries.php">Surgeries</a></li>
                                    <li><a class="dropdown-item" href="../doctor/diagnoses.php">Diagnoses</a></li>
                                    <li><a class="dropdown-item" href="../doctor/patients.php">Patient Search</a></li>
                                </ul>
                            </li>
                        <?php elseif ($_SESSION['user_type'] === 'patient'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    Patient Panel
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="../patient/dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="../patient/records.php">Medical Records</a></li>
                                    <li><a class="dropdown-item" href="../patient/payments.php">Payments</a></li>
                                    <li><a class="dropdown-item" href="../patient/profile.php">Profile</a></li>
                                </ul>
                            </li>
                        <?php elseif ($_SESSION['user_type'] === 'staff'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    Staff Panel
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="../staff/dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="../staff/medications.php">Medications</a></li>
                                    <li><a class="dropdown-item" href="../staff/patients.php">Patient Search</a></li>
                                    <li><a class="dropdown-item" href="../staff/profile.php">Profile</a></li>
                                </ul>
                            </li>
                        <?php elseif ($_SESSION['user_type'] === 'external'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    External Panel
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="../external/dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="../external/upload.php">Upload Records</a></li>
                                    <li><a class="dropdown-item" href="../external/records.php">View Records</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="../profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-4">
        <?php
        $message = getMessage();
        if ($message): ?>
            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $message['text']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>