-- Goba Hospital Patient Record Management System Database Schema
-- Created for comprehensive patient, doctor, staff, and admin management

-- Create database
CREATE DATABASE IF NOT EXISTS goba_hospital_db;
USE goba_hospital_db;

-- Hospitals table
CREATE TABLE hospitals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Ethiopia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Patients table
CREATE TABLE patients (
    ssn VARCHAR(50) PRIMARY KEY, -- National ID, Passport, or Birth Certificate
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    emergency_contact_name VARCHAR(200),
    emergency_contact_phone VARCHAR(20),
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
    allergies TEXT,
    medical_history TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Doctors table
CREATE TABLE doctors (
    ssn VARCHAR(50) PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(200),
    license_number VARCHAR(100) UNIQUE,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    hospital_id INT,
    experience_years INT,
    education TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL
);

-- Medical staff table
CREATE TABLE medical_staff (
    ssn VARCHAR(50) PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    hospital_id INT,
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL
);

-- Consultations table (with audio support)
CREATE TABLE consultations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_ssn VARCHAR(50) NOT NULL,
    patient_ssn VARCHAR(50) NOT NULL,
    consultation_date DATETIME NOT NULL,
    complaints TEXT,
    symptoms TEXT,
    diagnosis TEXT,
    treatment TEXT,
    prescription TEXT,
    notes TEXT,
    audio_file VARCHAR(255), -- Path to audio recording
    reference_number VARCHAR(50) UNIQUE,
    follow_up_date DATE,
    status ENUM('Active', 'Completed', 'Cancelled') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_ssn) REFERENCES doctors(ssn) ON DELETE CASCADE,
    FOREIGN KEY (patient_ssn) REFERENCES patients(ssn) ON DELETE CASCADE,
    UNIQUE KEY unique_consultation (doctor_ssn, patient_ssn, consultation_date)
);

-- Operations table
CREATE TABLE operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_ssn VARCHAR(50) NOT NULL,
    patient_ssn VARCHAR(50) NOT NULL,
    operation_date DATETIME NOT NULL,
    operation_name VARCHAR(255) NOT NULL,
    description TEXT,
    complications TEXT,
    allergies TEXT,
    anesthesia_type VARCHAR(100),
    duration_minutes INT,
    reference_number VARCHAR(50) UNIQUE,
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_ssn) REFERENCES doctors(ssn) ON DELETE CASCADE,
    FOREIGN KEY (patient_ssn) REFERENCES patients(ssn) ON DELETE CASCADE,
    UNIQUE KEY unique_operation (doctor_ssn, patient_ssn, operation_date)
);

-- Diagnoses table
CREATE TABLE diagnoses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_ssn VARCHAR(50) NOT NULL,
    patient_ssn VARCHAR(50) NOT NULL,
    diagnosis_date DATETIME NOT NULL,
    diagnosis_name VARCHAR(255) NOT NULL,
    description TEXT,
    symptoms TEXT,
    test_results TEXT,
    treatment_plan TEXT,
    reference_number VARCHAR(50) UNIQUE,
    severity ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_ssn) REFERENCES doctors(ssn) ON DELETE CASCADE,
    FOREIGN KEY (patient_ssn) REFERENCES patients(ssn) ON DELETE CASCADE,
    UNIQUE KEY unique_diagnosis (doctor_ssn, patient_ssn, diagnosis_date)
);

-- Medical administrations table
CREATE TABLE medical_administrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_ssn VARCHAR(50) NOT NULL,
    patient_ssn VARCHAR(50) NOT NULL,
    administration_date DATETIME NOT NULL,
    medicines TEXT,
    dosage TEXT,
    allergies TEXT,
    notes TEXT,
    reference_number VARCHAR(50) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_ssn) REFERENCES medical_staff(ssn) ON DELETE CASCADE,
    FOREIGN KEY (patient_ssn) REFERENCES patients(ssn) ON DELETE CASCADE,
    UNIQUE KEY unique_administration (staff_ssn, patient_ssn, administration_date)
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_ssn VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Commercial Bank', 'Awash Bank', 'Abyssinia Bank', 'Telebir') NOT NULL,
    transaction_id VARCHAR(100) UNIQUE,
    payment_date DATETIME NOT NULL,
    description TEXT,
    status ENUM('Pending', 'Completed', 'Failed', 'Refunded') DEFAULT 'Pending',
    reference_number VARCHAR(50) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_ssn) REFERENCES patients(ssn) ON DELETE CASCADE
);

-- External health office uploads table
CREATE TABLE external_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_ssn VARCHAR(50) NOT NULL,
    patient_ssn VARCHAR(50) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('PDF', 'JPG', 'PNG', 'DOC', 'DOCX') NOT NULL,
    file_size INT,
    description TEXT,
    upload_date DATETIME NOT NULL,
    status ENUM('Pending', 'Processed', 'Sent') DEFAULT 'Pending',
    destination_hospital VARCHAR(255),
    destination_link VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_ssn) REFERENCES doctors(ssn) ON DELETE CASCADE,
    FOREIGN KEY (patient_ssn) REFERENCES patients(ssn) ON DELETE CASCADE
);

-- User authentication tables

-- Admin login table
CREATE TABLE admin_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    role ENUM('Super Admin', 'Admin') DEFAULT 'Admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Patient login table
CREATE TABLE patient_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ssn VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ssn) REFERENCES patients(ssn) ON DELETE CASCADE
);

-- Doctor login table
CREATE TABLE doctor_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ssn VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ssn) REFERENCES doctors(ssn) ON DELETE CASCADE
);

-- Staff login table
CREATE TABLE staff_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ssn VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ssn) REFERENCES medical_staff(ssn) ON DELETE CASCADE
);

-- External health office login table
CREATE TABLE external_health_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    office_name VARCHAR(255) NOT NULL,
    office_address TEXT,
    contact_person VARCHAR(200),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default data

-- Insert default hospital
INSERT INTO hospitals (name, email, phone, address, city, state) VALUES 
('Goba Hospital', 'info@gobahospital.com', '+251-911-123456', 'Goba, Bale Zone, Oromia', 'Goba', 'Oromia');

-- Insert default admin
INSERT INTO admin_login (username, password, email, full_name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@gobahospital.com', 'System Administrator', 'Super Admin');

-- Create indexes for better performance
CREATE INDEX idx_patient_ssn ON patients(ssn);
CREATE INDEX idx_doctor_ssn ON doctors(ssn);
CREATE INDEX idx_staff_ssn ON medical_staff(ssn);
CREATE INDEX idx_consultation_date ON consultations(consultation_date);
CREATE INDEX idx_operation_date ON operations(operation_date);
CREATE INDEX idx_diagnosis_date ON diagnoses(diagnosis_date);
CREATE INDEX idx_payment_date ON payments(payment_date);
CREATE INDEX idx_reference_number ON consultations(reference_number);
CREATE INDEX idx_reference_number_ops ON operations(reference_number);
CREATE INDEX idx_reference_number_diag ON diagnoses(reference_number);