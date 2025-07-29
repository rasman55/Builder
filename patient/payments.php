<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$patient_ssn = $_SESSION['user_id'];
$error = '';
$success = '';

// Get patient's payment history
try {
    $stmt = $db->prepare("SELECT * FROM payment WHERE patient_ssn = ? ORDER BY payment_date DESC");
    $stmt->execute([$patient_ssn]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $reference_number = $_POST['reference_number'];
    
    // Generate transaction ID
    $transaction_id = 'TXN' . time() . rand(100, 999);
    
    try {
        $stmt = $db->prepare("INSERT INTO payment (patient_ssn, amount, payment_method, transaction_id, reference_number, status) 
                             VALUES (?, ?, ?, ?, ?, 'pending')");
        
        $result = $stmt->execute([$patient_ssn, $amount, $payment_method, $transaction_id, $reference_number]);
        
        if ($result) {
            $success = 'Payment initiated successfully! Transaction ID: ' . $transaction_id;
            
            // Clear form data
            $_POST = array();
        } else {
            $error = 'Failed to process payment.';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Goba Hospital</title>
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
                        <i class="fas fa-user-injured fa-2x text-primary"></i>
                        <h5 class="mt-2">Patient Portal</h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="consultations.php">
                                <i class="fas fa-stethoscope"></i> Consultations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="payments.php">
                                <i class="fas fa-credit-card"></i> Payments
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
                    <h1 class="h2">Payment Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#newPaymentModal">
                            <i class="fas fa-plus me-1"></i>New Payment
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

                <!-- Payment Methods Overview -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-university fa-3x text-primary mb-3"></i>
                                <h5>Commercial Bank</h5>
                                <p class="text-muted">Traditional banking</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-university fa-3x text-success mb-3"></i>
                                <h5>Awash Bank</h5>
                                <p class="text-muted">Modern banking solutions</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-university fa-3x text-info mb-3"></i>
                                <h5>Abyssinia Bank</h5>
                                <p class="text-muted">Reliable banking services</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-mobile-alt fa-3x text-warning mb-3"></i>
                                <h5>Telebirr</h5>
                                <p class="text-muted">Mobile money transfer</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Payment History
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($payments)): ?>
                            <p class="text-muted">No payment history found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Payment Method</th>
                                            <th>Transaction ID</th>
                                            <th>Reference</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y H:i', strtotime($payment['payment_date'])); ?></td>
                                                <td>
                                                    <strong>ETB <?php echo number_format($payment['amount'], 2); ?></strong>
                                                </td>
                                                <td>
                                                    <?php
                                                    $method_icons = [
                                                        'Commercial Bank' => 'fas fa-university text-primary',
                                                        'Awash Bank' => 'fas fa-university text-success',
                                                        'Abyssinia Bank' => 'fas fa-university text-info',
                                                        'Telebirr' => 'fas fa-mobile-alt text-warning'
                                                    ];
                                                    $icon_class = $method_icons[$payment['payment_method']] ?? 'fas fa-credit-card';
                                                    ?>
                                                    <i class="<?php echo $icon_class; ?> me-2"></i>
                                                    <?php echo htmlspecialchars($payment['payment_method']); ?>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($payment['transaction_id']); ?></code>
                                                </td>
                                                <td><?php echo htmlspecialchars($payment['reference_number']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_classes = [
                                                        'pending' => 'bg-warning',
                                                        'completed' => 'bg-success',
                                                        'failed' => 'bg-danger'
                                                    ];
                                                    $status_class = $status_classes[$payment['status']] ?? 'bg-secondary';
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="viewPaymentDetails('<?php echo $payment['id']; ?>')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($payment['status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-outline-success" 
                                                                onclick="completePayment('<?php echo $payment['id']; ?>')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- New Payment Modal -->
    <div class="modal fade" id="newPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-credit-card me-2"></i>New Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">
                                    <i class="fas fa-money-bill me-2"></i>Amount (ETB) *
                                </label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0" required>
                                <div class="invalid-feedback">
                                    Please enter a valid amount.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="reference_number" class="form-label">
                                    <i class="fas fa-hashtag me-2"></i>Reference Number
                                </label>
                                <input type="text" class="form-control" id="reference_number" 
                                       name="reference_number" placeholder="Optional reference">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-credit-card me-2"></i>Payment Method *
                            </label>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <div class="payment-method" data-method="Commercial Bank">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-university fa-2x text-primary me-3"></i>
                                            <div>
                                                <h6 class="mb-0">Commercial Bank</h6>
                                                <small class="text-muted">Traditional banking</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="payment-method" data-method="Awash Bank">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-university fa-2x text-success me-3"></i>
                                            <div>
                                                <h6 class="mb-0">Awash Bank</h6>
                                                <small class="text-muted">Modern banking solutions</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="payment-method" data-method="Abyssinia Bank">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-university fa-2x text-info me-3"></i>
                                            <div>
                                                <h6 class="mb-0">Abyssinia Bank</h6>
                                                <small class="text-muted">Reliable banking services</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="payment-method" data-method="Telebirr">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-mobile-alt fa-2x text-warning me-3"></i>
                                            <div>
                                                <h6 class="mb-0">Telebirr</h6>
                                                <small class="text-muted">Mobile money transfer</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="payment_method" id="selected_payment_method" required>
                            <div class="invalid-feedback">
                                Please select a payment method.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-credit-card me-2"></i>Process Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // Remove selected class from all methods
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                
                // Add selected class to clicked method
                this.classList.add('selected');
                
                // Update hidden input
                document.getElementById('selected_payment_method').value = this.dataset.method;
            });
        });

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

        function viewPaymentDetails(paymentId) {
            // Implement payment details view
            alert('Payment details for ID: ' + paymentId);
        }

        function completePayment(paymentId) {
            if (confirm('Mark this payment as completed?')) {
                // Implement payment completion
                alert('Payment marked as completed!');
                location.reload();
            }
        }
    </script>
</body>
</html>