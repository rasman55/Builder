<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goba Hospital - Patient Record Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
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
                        Efficiently manage patient records, consultations, and medical information 
                        with our comprehensive digital healthcare management system.
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
                    <img src="assets/images/hospital-hero.jpg" alt="Hospital" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Portals Section -->
    <section id="portals" class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold text-primary">User Portals</h2>
                    <p class="lead">Choose your portal to access the system</p>
                </div>
            </div>
            <div class="row g-4">
                <!-- Patient Portal -->
                <div class="col-lg-3 col-md-6">
                    <div class="card portal-card h-100">
                        <div class="card-body text-center">
                            <div class="portal-icon mb-3">
                                <i class="fas fa-user-injured fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">Patient Portal</h5>
                            <p class="card-text">View your medical records, search history, and manage your profile.</p>
                            <a href="patient/login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Patient Login
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Doctor Portal -->
                <div class="col-lg-3 col-md-6">
                    <div class="card portal-card h-100">
                        <div class="card-body text-center">
                            <div class="portal-icon mb-3">
                                <i class="fas fa-user-md fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">Doctor Portal</h5>
                            <p class="card-text">Record consultations, surgeries, diagnoses, and search patient history.</p>
                            <a href="doctor/login.php" class="btn btn-success">
                                <i class="fas fa-sign-in-alt me-2"></i>Doctor Login
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Staff Portal -->
                <div class="col-lg-3 col-md-6">
                    <div class="card portal-card h-100">
                        <div class="card-body text-center">
                            <div class="portal-icon mb-3">
                                <i class="fas fa-user-nurse fa-3x text-info"></i>
                            </div>
                            <h5 class="card-title">Staff Portal</h5>
                            <p class="card-text">Record medicine dosages and manage patient information.</p>
                            <a href="staff/login.php" class="btn btn-info">
                                <i class="fas fa-sign-in-alt me-2"></i>Staff Login
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Admin Portal -->
                <div class="col-lg-3 col-md-6">
                    <div class="card portal-card h-100">
                        <div class="card-body text-center">
                            <div class="portal-icon mb-3">
                                <i class="fas fa-user-shield fa-3x text-warning"></i>
                            </div>
                            <h5 class="card-title">Admin Portal</h5>
                            <p class="card-text">Manage users, hospitals, and system administration.</p>
                            <a href="admin/login.php" class="btn btn-warning">
                                <i class="fas fa-sign-in-alt me-2"></i>Admin Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- External Health Office Portal -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card portal-card">
                        <div class="card-body text-center">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="portal-icon mb-3">
                                        <i class="fas fa-hospital-alt fa-3x text-danger"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="card-title">External Health Office Portal</h5>
                                    <p class="card-text">Upload patient information and send to other hospitals.</p>
                                </div>
                                <div class="col-md-3">
                                    <a href="external/login.php" class="btn btn-danger">
                                        <i class="fas fa-sign-in-alt me-2"></i>External Login
                                    </a>
                                </div>
                            </div>
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
                    <h3>Patient Record Management</h3>
                    <p class="lead">Our comprehensive system provides efficient management of patient medical records, consultations, surgeries, and diagnoses.</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Secure patient data storage</li>
                        <li><i class="fas fa-check text-success me-2"></i>Easy access to medical history</li>
                        <li><i class="fas fa-check text-success me-2"></i>Audio recording for consultations</li>
                        <li><i class="fas fa-check text-success me-2"></i>Payment integration with local banks</li>
                        <li><i class="fas fa-check text-success me-2"></i>File upload capabilities</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/about-hospital.jpg" alt="About Hospital" class="img-fluid rounded shadow">
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
                <div class="col-lg-4 col-md-6">
                    <div class="service-card text-center p-4">
                        <i class="fas fa-stethoscope fa-3x text-primary mb-3"></i>
                        <h4>Medical Consultations</h4>
                        <p>Record and manage patient consultations with audio support.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card text-center p-4">
                        <i class="fas fa-procedures fa-3x text-success mb-3"></i>
                        <h4>Surgery Records</h4>
                        <p>Comprehensive surgery information and post-operative care.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card text-center p-4">
                        <i class="fas fa-microscope fa-3x text-info mb-3"></i>
                        <h4>Diagnosis Management</h4>
                        <p>Track patient diagnoses and treatment plans.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card text-center p-4">
                        <i class="fas fa-pills fa-3x text-warning mb-3"></i>
                        <h4>Medicine Administration</h4>
                        <p>Record medicine dosages and patient responses.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card text-center p-4">
                        <i class="fas fa-credit-card fa-3x text-danger mb-3"></i>
                        <h4>Payment Processing</h4>
                        <p>Integrated payment with local banks and TeleBirr.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card text-center p-4">
                        <i class="fas fa-share-alt fa-3x text-secondary mb-3"></i>
                        <h4>External Communication</h4>
                        <p>Share patient information with other healthcare facilities.</p>
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
                    <h4>Goba Hospital</h4>
                    <p><i class="fas fa-map-marker-alt text-primary me-2"></i>Goba, Bale Zone, Oromia, Ethiopia</p>
                    <p><i class="fas fa-phone text-primary me-2"></i>+251-123456789</p>
                    <p><i class="fas fa-envelope text-primary me-2"></i>info@gobahospital.com</p>
                    <div class="mt-4">
                        <h5>Payment Partners</h5>
                        <div class="d-flex gap-3 mt-3">
                            <span class="badge bg-primary">Commercial Bank</span>
                            <span class="badge bg-success">Awash Bank</span>
                            <span class="badge bg-info">Abyssinia Bank</span>
                            <span class="badge bg-warning">Telebirr</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <form>
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Your Name">
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Your Email">
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" rows="4" placeholder="Your Message"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Goba Hospital</h5>
                    <p>Providing quality healthcare services with modern technology.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; 2024 Goba Hospital. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>