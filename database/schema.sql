-- Goba Hospital Patient Record Management System Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS goba_hospital_db;
USE goba_hospital_db;

-- Hospital table
CREATE TABLE hospital (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Patient table
CREATE TABLE patient (
    ssn VARCHAR(50) PRIMARY KEY, -- National ID, Passport, or Birth Certificate
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    gender ENUM('Male', 'Female', 'Other'),
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
    emergency_contact VARCHAR(20),
    emergency_contact_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Doctor table
CREATE TABLE doctor (
    ssn VARCHAR(50) PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    hospital_id INT,
    license_number VARCHAR(50),
    experience_years INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospital(id)
);

-- Medical staff table
CREATE TABLE medical_staff (
    ssn VARCHAR(50) PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    position VARCHAR(100),
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    hospital_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospital(id)
);

-- Login tables
CREATE TABLE patient_login (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ssn VARCHAR(50) UNIQUE,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ssn) REFERENCES patient(ssn)
);

CREATE TABLE doctor_login (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ssn VARCHAR(50) UNIQUE,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ssn) REFERENCES doctor(ssn)
);

CREATE TABLE staff_login (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ssn VARCHAR(50) UNIQUE,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ssn) REFERENCES medical_staff(ssn)
);

CREATE TABLE admin_login (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Consultation table (with audio recording)
CREATE TABLE consultation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_ssn VARCHAR(50),
    patient_ssn VARCHAR(50),
    consultation_date DATETIME NOT NULL,
    complaints TEXT,
    diagnosis TEXT,
    treatment TEXT,
    prescription TEXT,
    audio_file VARCHAR(255), -- Path to audio recording
    reference_number VARCHAR(50) UNIQUE,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_ssn) REFERENCES doctor(ssn),
    FOREIGN KEY (patient_ssn) REFERENCES patient(ssn)
);

-- Operation table
CREATE TABLE operation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_ssn VARCHAR(50),
    patient_ssn VARCHAR(50),
    operation_date DATETIME NOT NULL,
    operation_name VARCHAR(255),
    description TEXT,
    complications TEXT,
    allergies TEXT,
    reference_number VARCHAR(50) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_ssn) REFERENCES doctor(ssn),
    FOREIGN KEY (patient_ssn) REFERENCES patient(ssn)
);

-- Diagnosis table
CREATE TABLE diagnosis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_ssn VARCHAR(50),
    patient_ssn VARCHAR(50),
    diagnosis_date DATETIME NOT NULL,
    diagnosis_name VARCHAR(255),
    description TEXT,
    test_results TEXT,
    reference_number VARCHAR(50) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_ssn) REFERENCES doctor(ssn),
    FOREIGN KEY (patient_ssn) REFERENCES patient(ssn)
);

-- Medical administration table
CREATE TABLE medical_administration (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_ssn VARCHAR(50),
    patient_ssn VARCHAR(50),
    administration_date DATETIME NOT NULL,
    medicines TEXT,
    dosage TEXT,
    allergies TEXT,
    notes TEXT,
    reference_number VARCHAR(50) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_ssn) REFERENCES medical_staff(ssn),
    FOREIGN KEY (patient_ssn) REFERENCES patient(ssn)
);

-- Payment table
CREATE TABLE payment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_ssn VARCHAR(50),
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Commercial Bank', 'Awash Bank', 'Abyssinia Bank', 'Telebirr'),
    transaction_id VARCHAR(100),
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    reference_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_ssn) REFERENCES patient(ssn)
);

-- External health office table
CREATE TABLE external_health_office (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_ssn VARCHAR(50),
    patient_ssn VARCHAR(50),
    file_path VARCHAR(255), -- Path to uploaded file (PDF, JPG)
    file_type ENUM('pdf', 'jpg', 'jpeg', 'png'),
    description TEXT,
    destination_hospital VARCHAR(255),
    destination_link VARCHAR(500),
    status ENUM('pending', 'sent', 'received') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_ssn) REFERENCES doctor(ssn),
    FOREIGN KEY (patient_ssn) REFERENCES patient(ssn)
);

-- Insert default hospital
INSERT INTO hospital (name, email, address, phone) VALUES 
('Goba Hospital', 'info@gobahospital.com', 'Goba, Bale Zone, Oromia, Ethiopia', '+251-123456789');

-- Insert default admin
INSERT INTO admin_login (username, password, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@gobahospital.com', 'super_admin');

-- Create indexes for better performance
CREATE INDEX idx_patient_ssn ON patient(ssn);
CREATE INDEX idx_doctor_ssn ON doctor(ssn);
CREATE INDEX idx_staff_ssn ON medical_staff(ssn);
CREATE INDEX idx_consultation_date ON consultation(consultation_date);
CREATE INDEX idx_operation_date ON operation(operation_date);
CREATE INDEX idx_diagnosis_date ON diagnosis(diagnosis_date);
CREATE INDEX idx_reference_number ON consultation(reference_number);