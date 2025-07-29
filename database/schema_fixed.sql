-- Goba Hospital Patient Record Management System Database Schema (Fixed Version)
-- This version handles existing tables gracefully

-- Create Database
CREATE DATABASE IF NOT EXISTS goba_hospital_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE goba_hospital_db;

-- Hospital Table (Fixed)
CREATE TABLE IF NOT EXISTS Hospital (
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
CREATE TABLE IF NOT EXISTS Patient (
    SSN VARCHAR(50) PRIMARY KEY,
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
CREATE TABLE IF NOT EXISTS Doctor (
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
    availability_schedule JSON,
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
CREATE TABLE IF NOT EXISTS Medical_staff (
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

-- Continue with other tables using IF NOT EXISTS...
-- (Rest of the schema with all tables using CREATE TABLE IF NOT EXISTS)

-- Insert default data safely
INSERT IGNORE INTO Hospital (name, email, phone, address, city, region, license_number, hospital_type, bed_capacity) 
VALUES ('Goba Hospital', 'info@gobahospital.com', '+251-XX-XXX-XXXX', 'Goba Town', 'Goba', 'Oromia', 'GH-2024-001', 'Public', 150);

-- Insert default admin user safely
INSERT IGNORE INTO Admin_login (username, email, password_hash, salt, full_name, role, hospital_id, two_factor_enabled) 
VALUES ('admin', 'admin@gobahospital.com', '$2y$10$example_hash_here', 'random_salt_here', 'System Administrator', 'Super_Admin', 1, TRUE);