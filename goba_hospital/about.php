<?php
require_once 'includes/config.php';
startSecureSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-hospital me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4">About Goba Hospital</h1>
                    <p class="lead mb-4">
                        Goba Hospital is a leading healthcare facility committed to providing exceptional medical care 
                        and innovative patient record management solutions. Our comprehensive system ensures efficient 
                        healthcare delivery while maintaining the highest standards of patient privacy and data security.
                    </p>
                </div>
                <div class="col-lg-4">
                    <img src="assets/images/hospital-building.svg" alt="Goba Hospital" class="img-fluid" 
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDQwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSI0MDAiIGhlaWdodD0iMzAwIiBmaWxsPSIjRjBGOUZGIi8+CjxwYXRoIGQ9Ik0yMDAgNTBMNTAgMTUwVjI1MEgyNTBWMjAwTDIwMCA1MFoiIGZpbGw9IiMwRDZFRkQiLz4KPHJlY3QgeD0iNzUiIHk9IjE1MCIgd2lkdGg9IjI1MCIgaGVpZ2h0PSIxMDAiIGZpbGw9IndoaXRlIi8+CjxjaXJjbGUgY3g9IjIwMCIgY3k9IjIwMCIgcj0iMjAiIGZpbGw9IiMwRDZFRkQiLz4KPC9zdmc+Cg=='">
                </div>
            </div>
        </div>
    </section>

    <!-- About Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="mb-4">Our Mission</h2>
                    <p class="lead">
                        To provide comprehensive, high-quality healthcare services while leveraging advanced technology 
                        to improve patient outcomes and streamline medical operations.
                    </p>
                    
                    <h3 class="mt-5 mb-3">Patient Record Management System</h3>
                    <p>
                        Our state-of-the-art Patient Record Management System is designed to revolutionize how healthcare 
                        professionals manage and access patient information. The system provides secure, efficient, and 
                        user-friendly interfaces for all stakeholders in the healthcare process.
                    </p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5><i class="fas fa-shield-alt text-primary me-2"></i>Security & Privacy</h5>
                            <p>HIPAA compliant with advanced encryption and secure access controls to protect patient data.</p>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-tachometer-alt text-success me-2"></i>Efficiency</h5>
                            <p>Streamlined workflows and automated processes to reduce administrative burden and improve care delivery.</p>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-users text-info me-2"></i>User-Friendly</h5>
                            <p>Intuitive interfaces designed for healthcare professionals with varying technical expertise.</p>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-sync text-warning me-2"></i>Real-time Updates</h5>
                            <p>Instant synchronization of patient records across all departments and user types.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Hospital Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <strong>Name:</strong><br>
                                    Goba Hospital
                                </li>
                                <li class="mb-3">
                                    <strong>Location:</strong><br>
                                    Goba, Ethiopia
                                </li>
                                <li class="mb-3">
                                    <strong>Phone:</strong><br>
                                    +251-123-456-789
                                </li>
                                <li class="mb-3">
                                    <strong>Email:</strong><br>
                                    info@gobahospital.com
                                </li>
                                <li class="mb-3">
                                    <strong>Established:</strong><br>
                                    2024
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="bg-light py-5" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">System Features</h2>
                <p class="lead text-muted">Comprehensive healthcare management solutions</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-user-md fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">Doctor Portal</h5>
                            <p class="card-text">
                                Comprehensive tools for managing patient consultations, surgeries, and diagnoses. 
                                Access complete patient medical histories with advanced search capabilities.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-user fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">Patient Portal</h5>
                            <p class="card-text">
                                Secure access to personal medical records, appointment scheduling, and payment processing. 
                                View complete medical history in an organized format.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-users fa-3x text-info"></i>
                            </div>
                            <h5 class="card-title">Staff Portal</h5>
                            <p class="card-text">
                                Tools for medical staff to record medication dosages, manage patient information, 
                                and coordinate with healthcare teams effectively.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-cogs fa-3x text-warning"></i>
                            </div>
                            <h5 class="card-title">Admin Portal</h5>
                            <p class="card-text">
                                Complete system administration with user management, hospital configuration, 
                                and comprehensive oversight capabilities.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-exchange-alt fa-3x text-danger"></i>
                            </div>
                            <h5 class="card-title">External Integration</h5>
                            <p class="card-text">
                                Seamless data exchange with external health offices and other healthcare facilities 
                                for improved patient care coordination.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-credit-card fa-3x text-secondary"></i>
                            </div>
                            <h5 class="card-title">Payment Processing</h5>
                            <p class="card-text">
                                Integrated payment system supporting multiple payment methods including 
                                Commercial Bank, Awash Bank, Abyssinia Bank, and Telebirr.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5" id="contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <h2 class="display-6 fw-bold mb-4">Contact Us</h2>
                    <p class="lead mb-4">
                        Get in touch with us for any questions about our services or the patient record management system.
                    </p>
                    
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-map-marker-alt fa-2x text-primary me-3"></i>
                        <div>
                            <h6 class="mb-0">Address</h6>
                            <p class="mb-0">Goba, Ethiopia</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-phone fa-2x text-primary me-3"></i>
                        <div>
                            <h6 class="mb-0">Phone</h6>
                            <p class="mb-0">+251-123-456-789</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-envelope fa-2x text-primary me-3"></i>
                        <div>
                            <h6 class="mb-0">Email</h6>
                            <p class="mb-0">info@gobahospital.com</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-clock fa-2x text-primary me-3"></i>
                        <div>
                            <h6 class="mb-0">Operating Hours</h6>
                            <p class="mb-0">24/7 Emergency Services</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Send us a Message</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" required>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" rows="4" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-hospital me-2"></i><?php echo SITE_NAME; ?></h5>
                    <p class="mb-0">Providing comprehensive healthcare record management solutions.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Goba Hospital. All rights reserved.</p>
                    <p class="mb-0">Contact: info@gobahospital.com | Phone: +251-123-456-789</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
</body>
</html>