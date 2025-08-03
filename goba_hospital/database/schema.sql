-- Goba Hospital Patient Record Management System Database Schema
-- Created for comprehensive patient, doctor, and staff management

-- Create database
CREATE DATABASE IF NOT EXISTS goba_hospital;
USE goba_hospital;

-- Admin table
CREATE TABLE admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hospital information table
CREATE TABLE hospitals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Patients table
CREATE TABLE patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    national_id VARCHAR(20),
    passport_number VARCHAR(20),
    birth_certificate VARCHAR(20),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    emergency_contact VARCHAR(20),
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
    allergies TEXT,
    medical_history TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Doctors table
CREATE TABLE doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    national_id VARCHAR(20),
    passport_number VARCHAR(20),
    birth_certificate VARCHAR(20),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    license_number VARCHAR(50),
    hospital_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id)
);

-- Medical staff table
CREATE TABLE medical_staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    position VARCHAR(100),
    national_id VARCHAR(20),
    passport_number VARCHAR(20),
    birth_certificate VARCHAR(20),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    hospital_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id)
);

-- User authentication tables
CREATE TABLE patient_login (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

CREATE TABLE doctor_login (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

CREATE TABLE staff_login (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES medical_staff(id)
);

CREATE TABLE external_login (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    organization_name VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Consultations table
CREATE TABLE consultations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reference_id VARCHAR(50) UNIQUE NOT NULL,
    patient_id INT,
    doctor_id INT,
    consultation_date DATETIME,
    symptoms TEXT,
    diagnosis TEXT,
    treatment_plan TEXT,
    prescription TEXT,
    notes TEXT,
    audio_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

-- Surgeries table
CREATE TABLE surgeries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reference_id VARCHAR(50) UNIQUE NOT NULL,
    patient_id INT,
    doctor_id INT,
    surgery_date DATETIME,
    surgery_type VARCHAR(200),
    procedure_description TEXT,
    pre_operative_notes TEXT,
    post_operative_notes TEXT,
    complications TEXT,
    outcome TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

-- Diagnoses table
CREATE TABLE diagnoses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reference_id VARCHAR(50) UNIQUE NOT NULL,
    patient_id INT,
    doctor_id INT,
    diagnosis_date DATETIME,
    condition_name VARCHAR(200),
    diagnosis_details TEXT,
    test_results TEXT,
    treatment_recommendations TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

-- Medication dosages table (for staff)
CREATE TABLE medication_dosages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT,
    staff_id INT,
    medication_name VARCHAR(200),
    dosage_amount VARCHAR(100),
    frequency VARCHAR(100),
    duration VARCHAR(100),
    instructions TEXT,
    prescribed_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (staff_id) REFERENCES medical_staff(id)
);

-- Patient referrals table
CREATE TABLE patient_referrals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT,
    referring_doctor_id INT,
    referred_hospital_id INT,
    referral_date DATETIME,
    reason_for_referral TEXT,
    urgency_level ENUM('Low', 'Medium', 'High', 'Emergency'),
    status ENUM('Pending', 'Accepted', 'Rejected', 'Completed'),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (referring_doctor_id) REFERENCES doctors(id),
    FOREIGN KEY (referred_hospital_id) REFERENCES hospitals(id)
);

-- Payments table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT,
    amount DECIMAL(10,2),
    payment_method ENUM('Commercial Bank', 'Awash Bank', 'Abyssinia Bank', 'Telebirr'),
    payment_date DATETIME,
    reference_number VARCHAR(100),
    description TEXT,
    status ENUM('Pending', 'Completed', 'Failed'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- External patient information table
CREATE TABLE external_patient_info (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_name VARCHAR(200),
    national_id VARCHAR(20),
    passport_number VARCHAR(20),
    birth_certificate VARCHAR(20),
    medical_records TEXT,
    source_hospital VARCHAR(200),
    uploaded_by VARCHAR(100),
    upload_date DATETIME,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin account
INSERT INTO admin (username, password, email) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@gobahospital.com');

-- Insert default hospital
INSERT INTO hospitals (name, address, phone, email) VALUES ('Goba Hospital', 'Goba, Ethiopia', '+251-123-456-789', 'info@gobahospital.com');

-- Insert sample data for testing
INSERT INTO patients (patient_id, first_name, last_name, date_of_birth, gender, national_id, phone, email, address) VALUES
('P001', 'Abebe', 'Kebede', '1990-05-15', 'Male', '1234567890', '+251-911-123-456', 'abebe@email.com', 'Addis Ababa, Ethiopia'),
('P002', 'Fatima', 'Ahmed', '1985-08-22', 'Female', '0987654321', '+251-922-234-567', 'fatima@email.com', 'Dire Dawa, Ethiopia');

INSERT INTO doctors (doctor_id, first_name, last_name, specialization, national_id, phone, email, hospital_id) VALUES
('D001', 'Dr. Yohannes', 'Tesfaye', 'Cardiology', '1122334455', '+251-933-345-678', 'yohannes@email.com', 1),
('D002', 'Dr. Aisha', 'Mohammed', 'Pediatrics', '5544332211', '+251-944-456-789', 'aisha@email.com', 1);

INSERT INTO medical_staff (staff_id, first_name, last_name, position, national_id, phone, email, hospital_id) VALUES
('S001', 'Nurse', 'Mariam', 'Registered Nurse', '6677889900', '+251-955-567-890', 'mariam@email.com', 1),
('S002', 'Lab', 'Technician', 'Laboratory Technician', '9988776655', '+251-966-678-901', 'labtech@email.com', 1);

-- Insert login credentials for sample users
INSERT INTO patient_login (patient_id, username, password) VALUES
(1, 'patient1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, 'patient2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO doctor_login (doctor_id, username, password) VALUES
(1, 'doctor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, 'doctor2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO staff_login (staff_id, username, password) VALUES
(1, 'staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, 'staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO external_login (username, password, organization_name) VALUES
('external1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Addis Ababa Health Office');