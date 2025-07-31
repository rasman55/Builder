-- Goba Hospital Patient Record Management System Database
-- MySQL Database Creation Script
-- Version: 1.0
-- Created: 2024

-- Create database
CREATE DATABASE IF NOT EXISTS goba_hospital_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE goba_hospital_db;

-- Drop tables if they exist (for clean installation)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS medical_files;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS medical_records;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS system_settings;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 1. Users table (Core user information for all types)
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('patient', 'doctor', 'staff', 'admin', 'external') NOT NULL,
    id_number VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    password_hash VARCHAR(255) NOT NULL,
    address TEXT,
    emergency_contact VARCHAR(255),
    id_type ENUM('national_id', 'passport', 'birth_certificate') NOT NULL,
    license_number VARCHAR(100) NULL,
    specialization VARCHAR(100) NULL,
    department VARCHAR(100) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_type (user_type),
    INDEX idx_id_number (id_number),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active),
    INDEX idx_department (department)
) ENGINE=InnoDB;

-- =====================================================
-- 2. Patients table (Extended patient information)
-- =====================================================
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    blood_type ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
    allergies TEXT,
    medical_conditions TEXT,
    medications TEXT,
    insurance_number VARCHAR(100),
    insurance_provider VARCHAR(100),
    next_of_kin VARCHAR(255),
    next_of_kin_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_blood_type (blood_type)
) ENGINE=InnoDB;

-- =====================================================
-- 3. Doctors table (Extended doctor information)
-- =====================================================
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    medical_license VARCHAR(100) NOT NULL,
    years_of_experience INT,
    consultation_fee DECIMAL(10,2),
    availability_schedule JSON,
    hospital_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_license (medical_license),
    INDEX idx_specialization (user_id, medical_license)
) ENGINE=InnoDB;

-- =====================================================
-- 4. Medical Records table (Core medical records)
-- =====================================================
CREATE TABLE medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    staff_id INT NULL,
    record_type ENUM('consultation', 'diagnosis', 'surgery', 'treatment', 'emergency') NOT NULL,
    reference_number VARCHAR(50) UNIQUE NOT NULL,
    date_time DATETIME NOT NULL,
    complaints TEXT,
    symptoms TEXT,
    diagnosis TEXT,
    treatment TEXT,
    prescriptions TEXT,
    notes TEXT,
    follow_up_date DATE,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_patient_id (patient_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_reference (reference_number),
    INDEX idx_date (date_time),
    INDEX idx_type (record_type),
    INDEX idx_status (status),
    INDEX idx_patient_date (patient_id, date_time),
    INDEX idx_doctor_date (doctor_id, date_time)
) ENGINE=InnoDB;

-- =====================================================
-- 5. Medical Files table (File attachments)
-- =====================================================
CREATE TABLE medical_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    uploaded_by INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (record_id) REFERENCES medical_records(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_record_id (record_id),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_file_type (file_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- =====================================================
-- 6. Appointments table (Appointment scheduling)
-- =====================================================
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATETIME NOT NULL,
    duration_minutes INT DEFAULT 30,
    purpose TEXT,
    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_patient_id (patient_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status),
    INDEX idx_doctor_date (doctor_id, appointment_date)
) ENGINE=InnoDB;

-- =====================================================
-- 7. User Sessions table (Session management)
-- =====================================================
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_token (session_token),
    INDEX idx_is_active (is_active),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB;

-- =====================================================
-- 8. Audit Logs table (Activity tracking)
-- =====================================================
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_date (created_at),
    INDEX idx_user_action (user_id, action)
) ENGINE=InnoDB;

-- =====================================================
-- 9. System Settings table (Configuration)
-- =====================================================
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (setting_key)
) ENGINE=InnoDB;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Default Admin User
INSERT INTO users (
    user_type, id_number, first_name, last_name, email, 
    password_hash, id_type, is_active, created_at
) VALUES (
    'admin', 
    'ADMIN001', 
    'System', 
    'Administrator', 
    'admin@gobahospital.et',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
    'national_id', 
    TRUE, 
    NOW()
);

-- Sample Doctor
INSERT INTO users (
    user_type, id_number, first_name, last_name, email, phone,
    date_of_birth, gender, password_hash, id_type, specialization, 
    department, is_active
) VALUES (
    'doctor', 
    'DOC001', 
    'Dr. Abebe', 
    'Kebede', 
    'dr.abebe@gobahospital.et',
    '+251911234567',
    '1980-05-15',
    'male',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: doctor123
    'national_id',
    'Cardiology',
    'Internal Medicine',
    TRUE
);

-- Sample Patient
INSERT INTO users (
    user_type, id_number, first_name, last_name, email, phone,
    date_of_birth, gender, password_hash, id_type, 
    emergency_contact, is_active
) VALUES (
    'patient', 
    'PAT001', 
    'Almaz', 
    'Tadesse', 
    'almaz.tadesse@email.com',
    '+251922345678',
    '1990-08-20',
    'female',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: patient123
    'national_id',
    'Dawit Tadesse - +251933456789',
    TRUE
);

-- Sample Staff
INSERT INTO users (
    user_type, id_number, first_name, last_name, email, phone,
    date_of_birth, gender, password_hash, id_type, 
    specialization, department, is_active
) VALUES (
    'staff', 
    'STAFF001', 
    'Sister Meron', 
    'Haile', 
    'meron.haile@gobahospital.et',
    '+251944567890',
    '1985-03-10',
    'female',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: staff123
    'national_id',
    'Registered Nurse',
    'Internal Medicine',
    TRUE
);

-- Doctor Profile
INSERT INTO doctors (user_id, medical_license, years_of_experience, consultation_fee, hospital_id)
SELECT id, 'ETH-MED-001234', 15, 500.00, 'GOBA-HOSP-001'
FROM users WHERE id_number = 'DOC001';

-- Patient Profile
INSERT INTO patients (user_id, blood_type, allergies, insurance_provider, next_of_kin, next_of_kin_phone)
SELECT id, 'O+', 'Penicillin allergy', 'Ethiopian Insurance Corporation', 'Dawit Tadesse', '+251933456789'
FROM users WHERE id_number = 'PAT001';

-- Sample Medical Record
INSERT INTO medical_records (
    patient_id, doctor_id, record_type, reference_number, date_time,
    complaints, symptoms, diagnosis, treatment, prescriptions, notes, status
)
SELECT 
    p.id, d.id, 'consultation', 'CON20241201001', '2024-12-01 10:30:00',
    'Chest pain and shortness of breath',
    'Mild chest discomfort, elevated heart rate',
    'Mild cardiac stress, recommend further monitoring',
    'Rest, lifestyle modification, follow-up in 2 weeks',
    'Aspirin 81mg daily, Lisinopril 10mg daily',
    'Patient advised to avoid strenuous activities',
    'completed'
FROM users p, users d 
WHERE p.id_number = 'PAT001' AND d.id_number = 'DOC001';

-- Sample Appointment
INSERT INTO appointments (patient_id, doctor_id, appointment_date, duration_minutes, purpose, status)
SELECT 
    p.id, d.id, '2024-12-15 14:00:00', 30, 'Follow-up consultation for cardiac monitoring', 'scheduled'
FROM users p, users d 
WHERE p.id_number = 'PAT001' AND d.id_number = 'DOC001';

-- System Settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('hospital_name', 'Goba Hospital', 'Hospital name'),
('hospital_address', 'Goba, Bale Zone, Oromia Region, Ethiopia', 'Hospital address'),
('hospital_phone', '+251-22-XXX-XXXX', 'Hospital phone number'),
('hospital_email', 'info@gobahospital.et', 'Hospital email'),
('system_version', '1.0.0', 'System version'),
('max_file_size', '10485760', 'Maximum file upload size in bytes (10MB)'),
('allowed_file_types', 'pdf,jpg,jpeg,png,doc,docx', 'Allowed file types for upload'),
('session_timeout', '3600', 'Session timeout in seconds (1 hour)'),
('password_min_length', '8', 'Minimum password length'),
('audit_retention_days', '365', 'Number of days to retain audit logs'),
('backup_frequency', 'daily', 'Database backup frequency'),
('maintenance_mode', 'false', 'System maintenance mode flag');

-- =====================================================
-- CREATE VIEWS FOR COMMON QUERIES
-- =====================================================

-- Patient Records View
CREATE VIEW patient_records_view AS
SELECT 
    mr.id,
    mr.reference_number,
    mr.record_type,
    mr.date_time,
    mr.diagnosis,
    mr.treatment,
    mr.status,
    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
    p.id_number as patient_id_number,
    CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
    d.specialization as doctor_specialization
FROM medical_records mr
JOIN users p ON mr.patient_id = p.id
LEFT JOIN users d ON mr.doctor_id = d.id
WHERE p.user_type = 'patient' AND p.is_active = TRUE;

-- Doctor Workload View
CREATE VIEW doctor_workload_view AS
SELECT 
    d.id as doctor_id,
    CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
    d.specialization,
    d.department,
    COUNT(DISTINCT mr.patient_id) as total_patients,
    COUNT(mr.id) as total_records,
    COUNT(CASE WHEN DATE(mr.date_time) = CURDATE() THEN 1 END) as today_records,
    COUNT(CASE WHEN mr.record_type = 'emergency' THEN 1 END) as emergency_cases
FROM users d
LEFT JOIN medical_records mr ON d.id = mr.doctor_id
WHERE d.user_type = 'doctor' AND d.is_active = TRUE
GROUP BY d.id, d.first_name, d.last_name, d.specialization, d.department;

-- System Statistics View
CREATE VIEW system_stats_view AS
SELECT 
    (SELECT COUNT(*) FROM users WHERE user_type = 'patient' AND is_active = TRUE) as total_patients,
    (SELECT COUNT(*) FROM users WHERE user_type = 'doctor' AND is_active = TRUE) as total_doctors,
    (SELECT COUNT(*) FROM users WHERE user_type = 'staff' AND is_active = TRUE) as total_staff,
    (SELECT COUNT(*) FROM medical_records) as total_records,
    (SELECT COUNT(*) FROM medical_records WHERE DATE(date_time) = CURDATE()) as today_records,
    (SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE()) as today_appointments,
    (SELECT COUNT(*) FROM medical_files) as total_files;

-- =====================================================
-- CREATE STORED PROCEDURES
-- =====================================================

DELIMITER $$

-- Procedure to get patient medical history
CREATE PROCEDURE GetPatientHistory(IN patient_user_id INT)
BEGIN
    SELECT 
        mr.*,
        CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
        d.specialization,
        (SELECT COUNT(*) FROM medical_files WHERE record_id = mr.id) as file_count
    FROM medical_records mr
    LEFT JOIN users d ON mr.doctor_id = d.id
    WHERE mr.patient_id = patient_user_id
    ORDER BY mr.date_time DESC;
END$$

-- Procedure to get doctor's patients
CREATE PROCEDURE GetDoctorPatients(IN doctor_user_id INT)
BEGIN
    SELECT DISTINCT
        p.id,
        p.first_name,
        p.last_name,
        p.id_number,
        p.phone,
        pat.blood_type,
        pat.allergies,
        MAX(mr.date_time) as last_visit,
        COUNT(mr.id) as total_visits
    FROM users p
    LEFT JOIN patients pat ON p.id = pat.user_id
    JOIN medical_records mr ON p.id = mr.patient_id
    WHERE mr.doctor_id = doctor_user_id 
    AND p.user_type = 'patient' 
    AND p.is_active = TRUE
    GROUP BY p.id, p.first_name, p.last_name, p.id_number, p.phone, pat.blood_type, pat.allergies
    ORDER BY last_visit DESC;
END$$

-- Procedure to cleanup old sessions
CREATE PROCEDURE CleanupOldSessions()
BEGIN
    DELETE FROM user_sessions 
    WHERE last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR) 
    OR is_active = FALSE;
END$$

DELIMITER ;

-- =====================================================
-- CREATE TRIGGERS FOR AUDIT LOGGING
-- =====================================================

DELIMITER $$

-- Trigger for medical_records INSERT
CREATE TRIGGER medical_records_insert_audit 
AFTER INSERT ON medical_records
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values, ip_address)
    VALUES (NEW.doctor_id, 'INSERT', 'medical_records', NEW.id, 
            JSON_OBJECT('reference_number', NEW.reference_number, 'record_type', NEW.record_type, 'patient_id', NEW.patient_id),
            '127.0.0.1');
END$$

-- Trigger for medical_records UPDATE
CREATE TRIGGER medical_records_update_audit 
AFTER UPDATE ON medical_records
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address)
    VALUES (NEW.doctor_id, 'UPDATE', 'medical_records', NEW.id,
            JSON_OBJECT('diagnosis', OLD.diagnosis, 'treatment', OLD.treatment, 'status', OLD.status),
            JSON_OBJECT('diagnosis', NEW.diagnosis, 'treatment', NEW.treatment, 'status', NEW.status),
            '127.0.0.1');
END$$

DELIMITER ;

-- =====================================================
-- CREATE INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional composite indexes for common queries
CREATE INDEX idx_records_patient_type_date ON medical_records(patient_id, record_type, date_time);
CREATE INDEX idx_records_doctor_status_date ON medical_records(doctor_id, status, date_time);
CREATE INDEX idx_users_type_active ON users(user_type, is_active);
CREATE INDEX idx_appointments_doctor_date_status ON appointments(doctor_id, appointment_date, status);

-- =====================================================
-- GRANT PERMISSIONS (Optional - adjust as needed)
-- =====================================================

-- Create application user (uncomment and modify as needed)
-- CREATE USER 'goba_app'@'localhost' IDENTIFIED BY 'secure_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON goba_hospital_db.* TO 'goba_app'@'localhost';
-- GRANT EXECUTE ON goba_hospital_db.* TO 'goba_app'@'localhost';
-- FLUSH PRIVILEGES;

-- =====================================================
-- FINAL VERIFICATION
-- =====================================================

-- Show table status
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    DATA_LENGTH,
    INDEX_LENGTH,
    CREATE_TIME
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'goba_hospital_db'
ORDER BY TABLE_NAME;

-- Show sample data counts
SELECT 'Users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'Medical Records', COUNT(*) FROM medical_records
UNION ALL
SELECT 'Appointments', COUNT(*) FROM appointments
UNION ALL
SELECT 'System Settings', COUNT(*) FROM system_settings;

-- =====================================================
-- SETUP COMPLETE
-- =====================================================

SELECT 'Database setup completed successfully!' as status,
       'Default admin credentials: ID=ADMIN001, Password=admin123' as admin_info,
       'Sample doctor: ID=DOC001, Password=doctor123' as doctor_info,
       'Sample patient: ID=PAT001, Password=patient123' as patient_info;