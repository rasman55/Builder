<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: login.php');
    exit();
}

$doctor_ssn = $_SESSION['user_id'];
$error = '';
$success = '';

// Get all patients for dropdown
try {
    $stmt = $db->prepare("SELECT ssn, first_name, last_name, date_of_birth FROM patient ORDER BY first_name, last_name");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_ssn = $_POST['patient_ssn'];
    $consultation_date = $_POST['consultation_date'];
    $complaints = $_POST['complaints'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $prescription = $_POST['prescription'];
    $follow_up_date = $_POST['follow_up_date'];
    
    // Generate reference number
    $reference_number = 'REF' . time() . rand(100, 999);
    
    try {
        $stmt = $db->prepare("INSERT INTO consultation (doctor_ssn, patient_ssn, consultation_date, complaints, 
                             diagnosis, treatment, prescription, reference_number, follow_up_date) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $result = $stmt->execute([
            $doctor_ssn, $patient_ssn, $consultation_date, $complaints, 
            $diagnosis, $treatment, $prescription, $reference_number, $follow_up_date
        ]);
        
        if ($result) {
            $success = 'Consultation recorded successfully! Reference Number: ' . $reference_number;
            
            // Clear form data
            $_POST = array();
        } else {
            $error = 'Failed to record consultation.';
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
    <title>New Consultation - Goba Hospital</title>
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
                        <i class="fas fa-user-md fa-2x text-success"></i>
                        <h5 class="mt-2">Doctor Portal</h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="new-consultation.php">
                                <i class="fas fa-stethoscope"></i> New Consultation
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="consultations.php">
                                <i class="fas fa-list"></i> All Consultations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="patients.php">
                                <i class="fas fa-users"></i> Patients
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
                    <h1 class="h2">New Consultation</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                        </a>
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

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-stethoscope me-2"></i>Record New Consultation
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <!-- Patient Selection -->
                                <div class="col-md-6 mb-3">
                                    <label for="patient_ssn" class="form-label">
                                        <i class="fas fa-user me-2"></i>Patient *
                                    </label>
                                    <select class="form-select" id="patient_ssn" name="patient_ssn" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo htmlspecialchars($patient['ssn']); ?>"
                                                    <?php echo (isset($_POST['patient_ssn']) && $_POST['patient_ssn'] == $patient['ssn']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . ' (' . $patient['ssn'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a patient.
                                    </div>
                                </div>

                                <!-- Consultation Date -->
                                <div class="col-md-6 mb-3">
                                    <label for="consultation_date" class="form-label">
                                        <i class="fas fa-calendar me-2"></i>Consultation Date & Time *
                                    </label>
                                    <input type="datetime-local" class="form-control" id="consultation_date" 
                                           name="consultation_date" 
                                           value="<?php echo isset($_POST['consultation_date']) ? $_POST['consultation_date'] : date('Y-m-d\TH:i'); ?>" 
                                           required>
                                    <div class="invalid-feedback">
                                        Please select consultation date and time.
                                    </div>
                                </div>
                            </div>

                            <!-- Audio Recording Section -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label">
                                        <i class="fas fa-microphone me-2"></i>Audio Recording
                                    </label>
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <button type="button" class="btn btn-primary record-audio" id="recordButton">
                                                <i class="fas fa-microphone me-2"></i>Start Recording
                                            </button>
                                            <p class="text-muted mt-2 mb-0">
                                                Click to start/stop audio recording of the consultation
                                            </p>
                                            <div id="audioPlayer" class="mt-3" style="display: none;">
                                                <audio controls class="audio-player">
                                                    Your browser does not support the audio element.
                                                </audio>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Complaints -->
                            <div class="mb-3">
                                <label for="complaints" class="form-label">
                                    <i class="fas fa-comment-medical me-2"></i>Patient Complaints *
                                </label>
                                <textarea class="form-control" id="complaints" name="complaints" rows="4" 
                                          placeholder="Describe patient's complaints and symptoms..." required><?php echo isset($_POST['complaints']) ? htmlspecialchars($_POST['complaints']) : ''; ?></textarea>
                                <div class="invalid-feedback">
                                    Please describe patient complaints.
                                </div>
                            </div>

                            <!-- Diagnosis -->
                            <div class="mb-3">
                                <label for="diagnosis" class="form-label">
                                    <i class="fas fa-microscope me-2"></i>Diagnosis *
                                </label>
                                <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" 
                                          placeholder="Enter medical diagnosis..." required><?php echo isset($_POST['diagnosis']) ? htmlspecialchars($_POST['diagnosis']) : ''; ?></textarea>
                                <div class="invalid-feedback">
                                    Please enter diagnosis.
                                </div>
                            </div>

                            <!-- Treatment -->
                            <div class="mb-3">
                                <label for="treatment" class="form-label">
                                    <i class="fas fa-pills me-2"></i>Treatment Plan *
                                </label>
                                <textarea class="form-control" id="treatment" name="treatment" rows="3" 
                                          placeholder="Describe treatment plan..." required><?php echo isset($_POST['treatment']) ? htmlspecialchars($_POST['treatment']) : ''; ?></textarea>
                                <div class="invalid-feedback">
                                    Please describe treatment plan.
                                </div>
                            </div>

                            <!-- Prescription -->
                            <div class="mb-3">
                                <label for="prescription" class="form-label">
                                    <i class="fas fa-prescription me-2"></i>Prescription
                                </label>
                                <textarea class="form-control" id="prescription" name="prescription" rows="4" 
                                          placeholder="Enter prescription details..."><?php echo isset($_POST['prescription']) ? htmlspecialchars($_POST['prescription']) : ''; ?></textarea>
                            </div>

                            <!-- Follow-up Date -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="follow_up_date" class="form-label">
                                        <i class="fas fa-calendar-check me-2"></i>Follow-up Date
                                    </label>
                                    <input type="date" class="form-control" id="follow_up_date" 
                                           name="follow_up_date" 
                                           value="<?php echo isset($_POST['follow_up_date']) ? $_POST['follow_up_date'] : ''; ?>">
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Save Consultation
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-undo me-2"></i>Reset Form
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Audio recording functionality
        let mediaRecorder;
        let audioChunks = [];
        let isRecording = false;

        document.getElementById('recordButton').addEventListener('click', function() {
            if (!isRecording) {
                startRecording();
            } else {
                stopRecording();
            }
        });

        function startRecording() {
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(stream => {
                    mediaRecorder = new MediaRecorder(stream);
                    audioChunks = [];
                    
                    mediaRecorder.ondataavailable = function(event) {
                        audioChunks.push(event.data);
                    };
                    
                    mediaRecorder.onstop = function() {
                        const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                        const audioUrl = URL.createObjectURL(audioBlob);
                        
                        const audioPlayer = document.getElementById('audioPlayer');
                        const audio = audioPlayer.querySelector('audio');
                        audio.src = audioUrl;
                        audioPlayer.style.display = 'block';
                        
                        // Add hidden input for form submission
                        let audioInput = document.querySelector('input[name="audio_data"]');
                        if (!audioInput) {
                            audioInput = document.createElement('input');
                            audioInput.type = 'hidden';
                            audioInput.name = 'audio_data';
                            document.querySelector('form').appendChild(audioInput);
                        }
                        audioInput.value = audioUrl;
                    };
                    
                    mediaRecorder.start();
                    isRecording = true;
                    
                    const button = document.getElementById('recordButton');
                    button.innerHTML = '<i class="fas fa-stop me-2"></i>Stop Recording';
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-danger');
                })
                .catch(error => {
                    console.error('Error accessing microphone:', error);
                    alert('Error accessing microphone. Please check permissions.');
                });
        }

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                isRecording = false;
                
                const button = document.getElementById('recordButton');
                button.innerHTML = '<i class="fas fa-microphone me-2"></i>Start Recording';
                button.classList.remove('btn-danger');
                button.classList.add('btn-primary');
            }
        }

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
    </script>
</body>
</html>