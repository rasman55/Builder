-- Goba Hospital Patient Record Management System Database Schema
-- Created for comprehensive medical record management

-- Create Database
CREATE DATABASE IF NOT EXISTS goba_hospital_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE goba_hospital_db;

-- Hospital Table
CREATE TABLE Hospital (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    region VARCHAR(100),
    postal_code VARCHAR(20),
    license_number VARCHAR(100) UNIQUE,
    established_date DATE,
    hospital_type ENUM('Public', 'Private', 'Specialized', 'Clinic') DEFAULT 'Public',
    bed_capacity INT DEFAULT 0,
    status ENUM('Active', 'Inactive', 'Under_Maintenance') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Patient Table
CREATE TABLE Patient (
    SSN VARCHAR(50) PRIMARY KEY, -- Social Security Number (NID, Passport, Birth Certificate)
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    emergency_contact_name VARCHAR(200),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relationship VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    region VARCHAR(100),
    postal_code VARCHAR(20),
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown') DEFAULT 'Unknown',
    allergies TEXT,
    chronic_conditions TEXT,
    insurance_provider VARCHAR(200),
    insurance_policy_number VARCHAR(100),
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed', 'Other') DEFAULT 'Single',
    occupation VARCHAR(200),
    nationality VARCHAR(100) DEFAULT 'Ethiopian',
    language_preference VARCHAR(50) DEFAULT 'Amharic',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_visit_date TIMESTAMP NULL,
    status ENUM('Active', 'Inactive', 'Deceased') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Doctor Table
CREATE TABLE Doctor (
    SSN VARCHAR(50) PRIMARY KEY,
    hospital_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    specialization VARCHAR(200) NOT NULL,
    license_number VARCHAR(100) UNIQUE NOT NULL,
    years_of_experience INT DEFAULT 0,
    education TEXT,
    certifications TEXT,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    availability_schedule JSON, -- Store weekly schedule
    department VARCHAR(200),
    hire_date DATE,
    employment_status ENUM('Active', 'On_Leave', 'Terminated', 'Retired') DEFAULT 'Active',
    salary DECIMAL(12,2),
    address TEXT,
    city VARCHAR(100),
    region VARCHAR(100),
    nationality VARCHAR(100) DEFAULT 'Ethiopian',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES Hospital(ID) ON DELETE RESTRICT
);

-- Medical Staff Table
CREATE TABLE Medical_staff (
    SSN VARCHAR(50) PRIMARY KEY,
    hospital_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    email VARCHAR(255),
    phone VARCHAR(20),
    job_title VARCHAR(200) NOT NULL,
    department VARCHAR(200),
    license_number VARCHAR(100),
    hire_date DATE,
    employment_status ENUM('Active', 'On_Leave', 'Terminated', 'Retired') DEFAULT 'Active',
    salary DECIMAL(12,2),
    shift_schedule ENUM('Day', 'Night', 'Rotating', 'Flexible') DEFAULT 'Day',
    supervisor_ssn VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    region VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES Hospital(ID) ON DELETE RESTRICT,
    FOREIGN KEY (supervisor_ssn) REFERENCES Medical_staff(SSN) ON DELETE SET NULL
);

-- Consultation Table (with Audio Record Support)
CREATE TABLE Consultation (
    consultation_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_ssn VARCHAR(50) NOT NULL,
    doctor_ssn VARCHAR(50) NOT NULL,
    consultation_date DATETIME NOT NULL,
    chief_complaint TEXT NOT NULL,
    history_of_present_illness TEXT,
    physical_examination TEXT,
    vital_signs JSON, -- Store temperature, blood pressure, pulse, etc.
    symptoms TEXT,
    diagnosis_provisional TEXT,
    treatment_plan TEXT,
    medications_prescribed TEXT,
    follow_up_instructions TEXT,
    follow_up_date DATE,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('Pending', 'Paid', 'Partial', 'Waived') DEFAULT 'Pending',
    audio_record_path VARCHAR(500), -- Path to audio recording
    consultation_notes TEXT,
    urgency_level ENUM('Low', 'Medium', 'High', 'Emergency') DEFAULT 'Medium',
    consultation_type ENUM('Regular', 'Follow_up', 'Emergency', 'Telemedicine') DEFAULT 'Regular',
    duration_minutes INT DEFAULT 30,
    status ENUM('Scheduled', 'In_Progress', 'Completed', 'Cancelled', 'No_Show') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_ssn) REFERENCES Patient(SSN) ON DELETE CASCADE,
    FOREIGN KEY (doctor_ssn) REFERENCES Doctor(SSN) ON DELETE RESTRICT
);

-- Operation/Surgery Table
CREATE TABLE Operation (
    operation_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_ssn VARCHAR(50) NOT NULL,
    doctor_ssn VARCHAR(50) NOT NULL, -- Primary surgeon
    operation_date DATETIME NOT NULL,
    operation_type VARCHAR(200) NOT NULL,
    procedure_name VARCHAR(300) NOT NULL,
    pre_operative_diagnosis TEXT,
    post_operative_diagnosis TEXT,
    operation_description TEXT NOT NULL,
    complications TEXT,
    allergies_noted TEXT,
    anesthesia_type VARCHAR(200),
    anesthesiologist_ssn VARCHAR(50),
    operation_duration_minutes INT,
    blood_loss_ml INT DEFAULT 0,
    operation_notes TEXT,
    recovery_notes TEXT,
    discharge_instructions TEXT,
    operation_outcome ENUM('Successful', 'Complicated', 'Failed', 'Ongoing') DEFAULT 'Successful',
    operation_cost DECIMAL(12,2) DEFAULT 0.00,
    insurance_covered DECIMAL(12,2) DEFAULT 0.00,
    room_number VARCHAR(20),
    urgency_level ENUM('Elective', 'Urgent', 'Emergency') DEFAULT 'Elective',
    status ENUM('Scheduled', 'In_Progress', 'Completed', 'Cancelled', 'Postponed') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_ssn) REFERENCES Patient(SSN) ON DELETE CASCADE,
    FOREIGN KEY (doctor_ssn) REFERENCES Doctor(SSN) ON DELETE RESTRICT,
    FOREIGN KEY (anesthesiologist_ssn) REFERENCES Doctor(SSN) ON DELETE SET NULL
);

-- Diagnosis Table
CREATE TABLE Diagnosis (
    diagnosis_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_ssn VARCHAR(50) NOT NULL,
    doctor_ssn VARCHAR(50) NOT NULL,
    diagnosis_date DATETIME NOT NULL,
    diagnosis_code VARCHAR(20), -- ICD-10 code
    diagnosis_name VARCHAR(300) NOT NULL,
    diagnosis_description TEXT,
    diagnosis_type ENUM('Primary', 'Secondary', 'Differential', 'Provisional', 'Final') DEFAULT 'Primary',
    severity ENUM('Mild', 'Moderate', 'Severe', 'Critical') DEFAULT 'Moderate',
    confidence_level ENUM('Low', 'Medium', 'High', 'Confirmed') DEFAULT 'Medium',
    symptoms_present TEXT,
    test_results TEXT,
    treatment_recommended TEXT,
    prognosis TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    consultation_id INT,
    notes TEXT,
    status ENUM('Active', 'Resolved', 'Chronic', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_ssn) REFERENCES Patient(SSN) ON DELETE CASCADE,
    FOREIGN KEY (doctor_ssn) REFERENCES Doctor(SSN) ON DELETE RESTRICT,
    FOREIGN KEY (consultation_id) REFERENCES Consultation(consultation_id) ON DELETE SET NULL
);

-- Medical Administration Table (Medicine Dosage)
CREATE TABLE Medical_administration (
    administration_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_ssn VARCHAR(50) NOT NULL,
    doctor_ssn VARCHAR(50) NOT NULL,
    staff_ssn VARCHAR(50) NOT NULL, -- Administering staff
    administration_date DATETIME NOT NULL,
    medication_name VARCHAR(300) NOT NULL,
    dosage VARCHAR(200) NOT NULL,
    dosage_unit VARCHAR(50) NOT NULL, -- mg, ml, tablets, etc.
    frequency VARCHAR(200) NOT NULL, -- twice daily, every 6 hours, etc.
    route_of_administration VARCHAR(100) NOT NULL, -- oral, IV, IM, topical, etc.
    duration_days INT,
    start_date DATE,
    end_date DATE,
    indication TEXT, -- Why the medication is given
    contraindications TEXT,
    side_effects_noted TEXT,
    allergies_checked BOOLEAN DEFAULT TRUE,
    medication_batch_number VARCHAR(100),
    expiry_date DATE,
    cost_per_unit DECIMAL(10,2) DEFAULT 0.00,
    total_cost DECIMAL(10,2) DEFAULT 0.00,
    insurance_covered DECIMAL(10,2) DEFAULT 0.00,
    administration_notes TEXT,
    monitoring_required BOOLEAN DEFAULT FALSE,
    status ENUM('Prescribed', 'Administered', 'Completed', 'Discontinued', 'Held') DEFAULT 'Prescribed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_ssn) REFERENCES Patient(SSN) ON DELETE CASCADE,
    FOREIGN KEY (doctor_ssn) REFERENCES Doctor(SSN) ON DELETE RESTRICT,
    FOREIGN KEY (staff_ssn) REFERENCES Medical_staff(SSN) ON DELETE RESTRICT
);

-- Patient Login Table
CREATE TABLE Patient_login (
    login_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_ssn VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    salt VARCHAR(100) NOT NULL,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    account_locked BOOLEAN DEFAULT FALSE,
    lockout_time TIMESTAMP NULL,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires TIMESTAMP NULL,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255) NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_ssn) REFERENCES Patient(SSN) ON DELETE CASCADE
);

-- Doctor Login Table
CREATE TABLE Doctor_login (
    login_id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_ssn VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    salt VARCHAR(100) NOT NULL,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    account_locked BOOLEAN DEFAULT FALSE,
    lockout_time TIMESTAMP NULL,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires TIMESTAMP NULL,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255) NULL,
    session_timeout_minutes INT DEFAULT 120,
    role ENUM('Doctor', 'Senior_Doctor', 'Consultant', 'Head_of_Department') DEFAULT 'Doctor',
    permissions JSON, -- Store specific permissions
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_ssn) REFERENCES Doctor(SSN) ON DELETE CASCADE
);

-- Staff Login Table
CREATE TABLE Staff_login (
    login_id INT PRIMARY KEY AUTO_INCREMENT,
    staff_ssn VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    salt VARCHAR(100) NOT NULL,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    account_locked BOOLEAN DEFAULT FALSE,
    lockout_time TIMESTAMP NULL,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires TIMESTAMP NULL,
    role ENUM('Nurse', 'Pharmacist', 'Lab_Technician', 'Radiologist', 'Administrative', 'Other') DEFAULT 'Nurse',
    permissions JSON,
    shift_access ENUM('Day', 'Night', 'Both') DEFAULT 'Both',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_ssn) REFERENCES Medical_staff(SSN) ON DELETE CASCADE
);

-- Admin Login Table
CREATE TABLE Admin_login (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    salt VARCHAR(100) NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    role ENUM('Super_Admin', 'Hospital_Admin', 'System_Admin', 'Regional_Admin') DEFAULT 'Hospital_Admin',
    hospital_id INT,
    permissions JSON,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    account_locked BOOLEAN DEFAULT FALSE,
    lockout_time TIMESTAMP NULL,
    two_factor_enabled BOOLEAN DEFAULT TRUE,
    two_factor_secret VARCHAR(255),
    created_by INT,
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES Hospital(ID) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES Admin_login(admin_id) ON DELETE SET NULL
);

-- External Health Office Login Table
CREATE TABLE External_health_office_login (
    office_id INT PRIMARY KEY AUTO_INCREMENT,
    office_name VARCHAR(200) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    salt VARCHAR(100) NOT NULL,
    contact_person VARCHAR(200),
    phone VARCHAR(20),
    address TEXT,
    region VARCHAR(100),
    office_type ENUM('Regional_Health_Office', 'Woreda_Health_Office', 'Zone_Health_Office', 'Federal_Ministry') DEFAULT 'Woreda_Health_Office',
    license_number VARCHAR(100),
    authorized_hospitals JSON, -- List of hospital IDs they can send patients to
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    account_locked BOOLEAN DEFAULT FALSE,
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Patient Transfer/Referral Table
CREATE TABLE Patient_transfer (
    transfer_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_ssn VARCHAR(50) NOT NULL,
    referring_doctor_ssn VARCHAR(50) NOT NULL,
    source_hospital_id INT NOT NULL,
    destination_hospital_id INT NOT NULL,
    external_office_id INT,
    transfer_reason TEXT NOT NULL,
    urgency_level ENUM('Low', 'Medium', 'High', 'Emergency') DEFAULT 'Medium',
    medical_summary TEXT NOT NULL,
    current_medications TEXT,
    special_instructions TEXT,
    transfer_date DATETIME NOT NULL,
    expected_arrival_date DATETIME,
    transportation_method VARCHAR(200),
    accompanying_staff VARCHAR(200),
    transfer_cost DECIMAL(10,2) DEFAULT 0.00,
    insurance_covered DECIMAL(10,2) DEFAULT 0.00,
    patient_consent BOOLEAN DEFAULT FALSE,
    family_consent BOOLEAN DEFAULT FALSE,
    documents_attached JSON, -- List of attached documents
    status ENUM('Pending', 'Approved', 'In_Transit', 'Completed', 'Cancelled', 'Rejected') DEFAULT 'Pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_ssn) REFERENCES Patient(SSN) ON DELETE CASCADE,
    FOREIGN KEY (referring_doctor_ssn) REFERENCES Doctor(SSN) ON DELETE RESTRICT,
    FOREIGN KEY (source_hospital_id) REFERENCES Hospital(ID) ON DELETE RESTRICT,
    FOREIGN KEY (destination_hospital_id) REFERENCES Hospital(ID) ON DELETE RESTRICT,
    FOREIGN KEY (external_office_id) REFERENCES External_health_office_login(office_id) ON DELETE SET NULL
);

-- Payment Records Table
CREATE TABLE Payment_records (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_ssn VARCHAR(50) NOT NULL,
    service_type ENUM('Consultation', 'Operation', 'Medication', 'Lab_Test', 'Imaging', 'Emergency', 'Other') NOT NULL,
    service_reference_id INT, -- Reference to consultation_id, operation_id, etc.
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'ETB',
    payment_method ENUM('Cash', 'Commercial_Bank', 'Awash_Bank', 'Abyssinia_Bank', 'Telebirr', 'Credit_Card', 'Insurance') NOT NULL,
    payment_reference VARCHAR(200), -- Bank transaction reference, insurance claim number, etc.
    payment_date DATETIME NOT NULL,
    payment_status ENUM('Pending', 'Completed', 'Failed', 'Refunded', 'Partial') DEFAULT 'Pending',
    insurance_provider VARCHAR(200),
    insurance_coverage_percentage DECIMAL(5,2) DEFAULT 0.00,
    insurance_claim_number VARCHAR(200),
    discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    discount_reason VARCHAR(300),
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL,
    receipt_number VARCHAR(100) UNIQUE,
    payment_processor VARCHAR(200), -- Bank name, payment gateway, etc.
    transaction_fee DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    processed_by_staff_ssn VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_ssn) REFERENCES Patient(SSN) ON DELETE CASCADE,
    FOREIGN KEY (processed_by_staff_ssn) REFERENCES Medical_staff(SSN) ON DELETE SET NULL
);

-- File Attachments Table (for PDFs, images, etc.)
CREATE TABLE File_attachments (
    file_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_ssn VARCHAR(50) NOT NULL,
    uploaded_by_type ENUM('Doctor', 'Staff', 'External_Office', 'Patient') NOT NULL,
    uploaded_by_id VARCHAR(50) NOT NULL,
    file_name VARCHAR(500) NOT NULL,
    original_file_name VARCHAR(500) NOT NULL,
    file_path VARCHAR(1000) NOT NULL,
    file_size_bytes BIGINT NOT NULL,
    file_type VARCHAR(100) NOT NULL, -- PDF, JPG, PNG, DOCX, etc.
    mime_type VARCHAR(200) NOT NULL,
    description TEXT,
    category ENUM('Medical_Report', 'Lab_Result', 'Imaging', 'Prescription', 'Insurance', 'Identification', 'Other') DEFAULT 'Other',
    is_confidential BOOLEAN DEFAULT FALSE,
    access_level ENUM('Public', 'Hospital_Only', 'Doctor_Only', 'Admin_Only') DEFAULT 'Hospital_Only',
    related_service_type ENUM('Consultation', 'Operation', 'Diagnosis', 'Transfer', 'General') DEFAULT 'General',
    related_service_id INT,
    virus_scan_status ENUM('Pending', 'Clean', 'Infected', 'Error') DEFAULT 'Pending',
    encryption_key VARCHAR(255),
    checksum VARCHAR(255),
    expiry_date DATE,
    download_count INT DEFAULT 0,
    last_accessed TIMESTAMP NULL,
    status ENUM('Active', 'Archived', 'Deleted') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_ssn) REFERENCES Patient(SSN) ON DELETE CASCADE
);

-- Audit Log Table (for tracking all system activities)
CREATE TABLE Audit_log (
    log_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_type ENUM('Patient', 'Doctor', 'Staff', 'Admin', 'External_Office') NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    action VARCHAR(200) NOT NULL,
    table_affected VARCHAR(100),
    record_id VARCHAR(100),
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    session_id VARCHAR(255),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT TRUE,
    error_message TEXT
);

-- Create Indexes for Better Performance
CREATE INDEX idx_patient_name ON Patient(first_name, last_name);
CREATE INDEX idx_patient_dob ON Patient(date_of_birth);
CREATE INDEX idx_patient_phone ON Patient(phone);
CREATE INDEX idx_patient_email ON Patient(email);

CREATE INDEX idx_doctor_name ON Doctor(first_name, last_name);
CREATE INDEX idx_doctor_specialization ON Doctor(specialization);
CREATE INDEX idx_doctor_hospital ON Doctor(hospital_id);

CREATE INDEX idx_consultation_patient ON Consultation(patient_ssn);
CREATE INDEX idx_consultation_doctor ON Consultation(doctor_ssn);
CREATE INDEX idx_consultation_date ON Consultation(consultation_date);

CREATE INDEX idx_operation_patient ON Operation(patient_ssn);
CREATE INDEX idx_operation_doctor ON Operation(doctor_ssn);
CREATE INDEX idx_operation_date ON Operation(operation_date);

CREATE INDEX idx_diagnosis_patient ON Diagnosis(patient_ssn);
CREATE INDEX idx_diagnosis_doctor ON Diagnosis(doctor_ssn);
CREATE INDEX idx_diagnosis_date ON Diagnosis(diagnosis_date);

CREATE INDEX idx_payment_patient ON Payment_records(patient_ssn);
CREATE INDEX idx_payment_date ON Payment_records(payment_date);
CREATE INDEX idx_payment_method ON Payment_records(payment_method);

CREATE INDEX idx_file_patient ON File_attachments(patient_ssn);
CREATE INDEX idx_file_category ON File_attachments(category);
CREATE INDEX idx_file_type ON File_attachments(file_type);

CREATE INDEX idx_audit_user ON Audit_log(user_type, user_id);
CREATE INDEX idx_audit_timestamp ON Audit_log(timestamp);
CREATE INDEX idx_audit_action ON Audit_log(action);

-- Create Views for Common Queries
CREATE VIEW patient_summary AS
SELECT 
    p.SSN,
    CONCAT(p.first_name, ' ', p.last_name) AS full_name,
    p.date_of_birth,
    p.gender,
    p.phone,
    p.email,
    p.blood_type,
    p.last_visit_date,
    COUNT(DISTINCT c.consultation_id) AS total_consultations,
    COUNT(DISTINCT o.operation_id) AS total_operations,
    COUNT(DISTINCT d.diagnosis_id) AS total_diagnoses
FROM Patient p
LEFT JOIN Consultation c ON p.SSN = c.patient_ssn
LEFT JOIN Operation o ON p.SSN = o.patient_ssn
LEFT JOIN Diagnosis d ON p.SSN = d.patient_ssn
GROUP BY p.SSN;

CREATE VIEW doctor_workload AS
SELECT 
    d.SSN,
    CONCAT(d.first_name, ' ', d.last_name) AS full_name,
    d.specialization,
    d.department,
    COUNT(DISTINCT c.consultation_id) AS total_consultations,
    COUNT(DISTINCT o.operation_id) AS total_operations,
    AVG(c.consultation_fee) AS avg_consultation_fee
FROM Doctor d
LEFT JOIN Consultation c ON d.SSN = c.doctor_ssn
LEFT JOIN Operation o ON d.SSN = o.doctor_ssn
GROUP BY d.SSN;

-- Insert Default Data
INSERT INTO Hospital (name, email, phone, address, city, region, license_number, hospital_type, bed_capacity) VALUES
('Goba Hospital', 'info@gobahospital.com', '+251-XX-XXX-XXXX', 'Goba Town', 'Goba', 'Oromia', 'GH-2024-001', 'Public', 150);

-- Insert Default Admin User
INSERT INTO Admin_login (username, email, password_hash, salt, full_name, role, hospital_id, two_factor_enabled) VALUES
('admin', 'admin@gobahospital.com', '$2y$10$example_hash_here', 'random_salt_here', 'System Administrator', 'Super_Admin', 1, TRUE);

-- Create Triggers for Audit Logging
DELIMITER //

CREATE TRIGGER patient_audit_insert
AFTER INSERT ON Patient
FOR EACH ROW
BEGIN
    INSERT INTO Audit_log (user_type, user_id, action, table_affected, record_id, new_values)
    VALUES ('System', 'AUTO', 'INSERT', 'Patient', NEW.SSN, JSON_OBJECT('data', 'Patient record created'));
END//

CREATE TRIGGER patient_audit_update
AFTER UPDATE ON Patient
FOR EACH ROW
BEGIN
    INSERT INTO Audit_log (user_type, user_id, action, table_affected, record_id, old_values, new_values)
    VALUES ('System', 'AUTO', 'UPDATE', 'Patient', NEW.SSN, 
            JSON_OBJECT('old_data', 'Previous values'), 
            JSON_OBJECT('new_data', 'Updated values'));
END//

CREATE TRIGGER consultation_audit_insert
AFTER INSERT ON Consultation
FOR EACH ROW
BEGIN
    INSERT INTO Audit_log (user_type, user_id, action, table_affected, record_id, new_values)
    VALUES ('Doctor', NEW.doctor_ssn, 'INSERT', 'Consultation', NEW.consultation_id, 
            JSON_OBJECT('patient_ssn', NEW.patient_ssn, 'consultation_date', NEW.consultation_date));
END//

DELIMITER ;

-- Create Stored Procedures for Common Operations

DELIMITER //

-- Procedure to get patient medical history
CREATE PROCEDURE GetPatientMedicalHistory(IN patient_id VARCHAR(50))
BEGIN
    SELECT 'Consultations' as record_type, consultation_date as date, 
           CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
           chief_complaint as description, consultation_fee as cost
    FROM Consultation c
    JOIN Doctor d ON c.doctor_ssn = d.SSN
    WHERE c.patient_ssn = patient_id
    
    UNION ALL
    
    SELECT 'Operations' as record_type, operation_date as date,
           CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
           procedure_name as description, operation_cost as cost
    FROM Operation o
    JOIN Doctor d ON o.doctor_ssn = d.SSN
    WHERE o.patient_ssn = patient_id
    
    UNION ALL
    
    SELECT 'Diagnoses' as record_type, diagnosis_date as date,
           CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
           diagnosis_name as description, 0 as cost
    FROM Diagnosis diag
    JOIN Doctor d ON diag.doctor_ssn = d.SSN
    WHERE diag.patient_ssn = patient_id
    
    ORDER BY date DESC;
END//

-- Procedure to search patients
CREATE PROCEDURE SearchPatients(
    IN search_term VARCHAR(200),
    IN search_type ENUM('name', 'ssn', 'phone', 'email')
)
BEGIN
    CASE search_type
        WHEN 'name' THEN
            SELECT * FROM Patient 
            WHERE CONCAT(first_name, ' ', last_name) LIKE CONCAT('%', search_term, '%')
            OR first_name LIKE CONCAT('%', search_term, '%')
            OR last_name LIKE CONCAT('%', search_term, '%');
            
        WHEN 'ssn' THEN
            SELECT * FROM Patient WHERE SSN = search_term;
            
        WHEN 'phone' THEN
            SELECT * FROM Patient WHERE phone LIKE CONCAT('%', search_term, '%');
            
        WHEN 'email' THEN
            SELECT * FROM Patient WHERE email LIKE CONCAT('%', search_term, '%');
    END CASE;
END//

DELIMITER ;

-- Grant appropriate permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE ON goba_hospital_db.* TO 'hospital_user'@'localhost';
-- GRANT SELECT ON goba_hospital_db.patient_summary TO 'doctor_user'@'localhost';
-- GRANT SELECT ON goba_hospital_db.doctor_workload TO 'admin_user'@'localhost';