<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-hospital me-2"></i>
                Goba Hospital
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-primary mb-4">
                        Patient Record Management System
                    </h1>
                    <p class="lead mb-4">
                        Comprehensive digital healthcare management solution for Goba Hospital. 
                        Streamline patient care, medical records, and administrative processes.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#portals" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Access Portals
                        </a>
                        <a href="#about" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-info-circle me-2"></i>Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image">
                        <i class="fas fa-hospital-user fa-10x text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Portals Section -->
    <section id="portals" class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold text-primary">Access Your Portal</h2>
                    <p class="lead">Choose your role to access the appropriate portal</p>
                </div>
            </div>
            <div class="row g-4">
                <!-- Admin Portal -->
                <div class="col-lg-4 col-md-6">
                    <div class="card portal-card h-100">
                        <div class="card-body text-center">
                            <div class="portal-icon mb-3">
                                <i class="fas fa-user-shield fa-3x text-danger"></i>
                            </div>
                            <h5 class="card-title">Admin Portal</h5>
                            <p class="card-text">System administration, user management, and hospital records.</p>
                            <a href="admin/login.php" class="btn btn-danger">
                                <i class="fas fa-sign-in-alt me-2"></i>Admin Login
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Doctor Portal -->
                <div class="col-lg-4 col-md-6">
                    <div class="card portal-card h-100">
                        <div class="card-body text-center">
                            <div class="portal-icon mb-3">
                                <i class="fas fa-user-md fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">Doctor Portal</h5>
                            <p class="card-text">Patient consultations, surgeries, diagnoses, and medical records.</p>
                            <a href="doctor/login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Doctor Login
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Patient Portal -->
                <div class="col-lg-4 col-md-6">
                    <div class="card portal-card h-100">
                        <div class="card-body text-center">
                            <div class="portal-icon mb-3">
                                <i class="fas fa-user fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">Patient Portal</h5>
                            <p class="card-text">View medical history, search records, and manage appointments.</p>
                            <a href="patient/login.php" class="btn btn-success">
                                <i class="fas fa-sign-in-alt me-2"></i>Patient Login
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Staff Portal -->
                <div class="col-lg-4 col-md-6">
                    <div class="card portal-card h-100">
                        <div class="card-body text-center">
                            <div class="portal-icon mb-3">
                                <i class="fas fa-user-nurse fa-3x text-warning"></i>
                            </div>
                            <h5 class="card-title">Staff Portal</h5>
                            <p class="card-text">Medical administration, dosage records, and patient care.</p>
                            <a href="staff/login.php" class="btn btn-warning">
                                <i class="fas fa-sign-in-alt me-2"></i>Staff Login
                            </a>
                        </div>
                    </div>
                </div>

                <!-- External Health Office Portal -->
                <div class="col-lg-4 col-md-6">
                    <div class="card portal-card h-100">
                        <div class="card-body text-center">
                            <div class="portal-icon mb-3">
                                <i class="fas fa-building fa-3x text-info"></i>
                            </div>
                            <h5 class="card-title">External Health Office</h5>
                            <p class="card-text">Upload patient information and transfer medical records.</p>
                            <a href="external_health/login.php" class="btn btn-info">
                                <i class="fas fa-sign-in-alt me-2"></i>External Login
                            </a>
                        </div>
                    </div>
                </div>

                <!-- New Registration -->
                <div class="col-lg-4 col-md-6">
                    <div class="card portal-card h-100">
                        <div class="card-body text-center">
                            <div class="portal-icon mb-3">
                                <i class="fas fa-user-plus fa-3x text-secondary"></i>
                            </div>
                            <h5 class="card-title">New Registration</h5>
                            <p class="card-text">Register as a new patient, doctor, or staff member.</p>
                            <a href="register.php" class="btn btn-secondary">
                                <i class="fas fa-user-plus me-2"></i>Register
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold text-primary">About Our System</h2>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-6">
                    <h3>Comprehensive Patient Care</h3>
                    <p class="lead">Our Patient Record Management System provides a complete digital solution for healthcare management at Goba Hospital.</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Secure patient data management</li>
                        <li><i class="fas fa-check text-success me-2"></i>Real-time medical record access</li>
                        <li><i class="fas fa-check text-success me-2"></i>Multi-user portal system</li>
                        <li><i class="fas fa-check text-success me-2"></i>Advanced search capabilities</li>
                        <li><i class="fas fa-check text-success me-2"></i>Payment processing integration</li>
                        <li><i class="fas fa-check text-success me-2"></i>Audio consultation recording</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="feature-box text-center p-3">
                                <i class="fas fa-shield-alt fa-2x text-primary mb-2"></i>
                                <h5>Secure</h5>
                                <p>HIPAA compliant data protection</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="feature-box text-center p-3">
                                <i class="fas fa-mobile-alt fa-2x text-success mb-2"></i>
                                <h5>Responsive</h5>
                                <p>Works on all devices</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="feature-box text-center p-3">
                                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                <h5>24/7 Access</h5>
                                <p>Available anytime, anywhere</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="feature-box text-center p-3">
                                <i class="fas fa-users fa-2x text-info mb-2"></i>
                                <h5>Multi-User</h5>
                                <p>Role-based access control</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold text-primary">Our Services</h2>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                        <h4>Medical Consultations</h4>
                        <p>Digital consultation records with audio support for comprehensive patient care documentation.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-procedures"></i>
                        </div>
                        <h4>Surgery Management</h4>
                        <p>Complete surgical procedure tracking including pre-operative and post-operative care.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-microscope"></i>
                        </div>
                        <h4>Diagnosis Records</h4>
                        <p>Comprehensive diagnosis tracking with test results and treatment plans.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-pills"></i>
                        </div>
                        <h4>Medical Administration</h4>
                        <p>Staff-administered treatments and medication dosage tracking.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h4>Payment Processing</h4>
                        <p>Integrated payment solutions with multiple banking options and Telebir support.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h4>External Transfers</h4>
                        <p>Seamless patient information transfer between healthcare facilities.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold text-primary">Contact Us</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <div class="contact-info">
                        <h4>Goba Hospital</h4>
                        <p><i class="fas fa-map-marker-alt me-2"></i>Goba, Bale Zone, Oromia, Ethiopia</p>
                        <p><i class="fas fa-phone me-2"></i>+251-911-123456</p>
                        <p><i class="fas fa-envelope me-2"></i>info@gobahospital.com</p>
                        <p><i class="fas fa-clock me-2"></i>24/7 Emergency Services Available</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <form class="contact-form">
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Your Name" required>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Your Email" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Subject" required>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" rows="5" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Goba Hospital</h5>
                    <p>Providing quality healthcare services with advanced technology.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; 2024 Goba Hospital. All rights reserved.</p>
                    <p>Patient Record Management System</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>