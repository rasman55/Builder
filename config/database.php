<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'goba_hospital_db');

// Create database connection
function getDatabaseConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }
}

// Initialize database and create tables
function initializeDatabase() {
    try {
        // Create database if it doesn't exist
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        $pdo->exec("USE " . DB_NAME);
        
        // Create users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
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
                INDEX idx_email (email)
            ) ENGINE=InnoDB
        ");
        
        // Create patients table (extended patient information)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS patients (
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
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB
        ");
        
        // Create doctors table (extended doctor information)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS doctors (
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
                INDEX idx_license (medical_license)
            ) ENGINE=InnoDB
        ");
        
        // Create medical_records table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS medical_records (
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
                INDEX idx_reference (reference_number),
                INDEX idx_date (date_time),
                INDEX idx_type (record_type)
            ) ENGINE=InnoDB
        ");
        
        // Create medical_files table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS medical_files (
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
                INDEX idx_record_id (record_id)
            ) ENGINE=InnoDB
        ");
        
        // Create appointments table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS appointments (
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
                INDEX idx_date (appointment_date)
            ) ENGINE=InnoDB
        ");
        
        // Create user_sessions table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_token VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_active BOOLEAN DEFAULT TRUE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_token (session_token)
            ) ENGINE=InnoDB
        ");
        
        // Create audit_logs table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS audit_logs (
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
                INDEX idx_date (created_at)
            ) ENGINE=InnoDB
        ");
        
        // Create system_settings table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_key (setting_key)
            ) ENGINE=InnoDB
        ");
        
        // Insert default admin user
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT IGNORE INTO users (
                user_type, id_number, first_name, last_name, email, 
                password_hash, id_type, created_at
            ) VALUES (
                'admin', 'ADMIN001', 'System', 'Administrator', 'admin@gobahospital.et',
                '$adminPassword', 'national_id', NOW()
            )
        ");
        
        // Insert default system settings
        $pdo->exec("
            INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
            ('hospital_name', 'Goba Hospital', 'Hospital name'),
            ('hospital_address', 'Goba, Ethiopia', 'Hospital address'),
            ('hospital_phone', '+251-XX-XXX-XXXX', 'Hospital phone number'),
            ('hospital_email', 'info@gobahospital.et', 'Hospital email'),
            ('system_version', '1.0.0', 'System version'),
            ('max_file_size', '10485760', 'Maximum file upload size in bytes (10MB)'),
            ('allowed_file_types', 'pdf,jpg,jpeg,png,doc,docx', 'Allowed file types for upload')
        ");
        
        return true;
        
    } catch(PDOException $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}

// Function to generate unique reference number
function generateReferenceNumber($type = 'REC') {
    return $type . date('Ymd') . rand(1000, 9999);
}

// Function to log user activity
function logUserActivity($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    try {
        $pdo = getDatabaseConnection();
        if (!$pdo) return false;
        
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $userId,
            $action,
            $tableName,
            $recordId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch(PDOException $e) {
        error_log("Failed to log user activity: " . $e->getMessage());
        return false;
    }
}

// Initialize database on first run
if (!file_exists(__DIR__ . '/.db_initialized')) {
    if (initializeDatabase()) {
        file_put_contents(__DIR__ . '/.db_initialized', date('Y-m-d H:i:s'));
    }
}
?>