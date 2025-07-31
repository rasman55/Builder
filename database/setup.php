<?php
/**
 * Goba Hospital Database Setup Script
 * This script will create the database and all required tables
 * Run this script once to initialize the system
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'goba_hospital_db';

echo "<h2>Goba Hospital Database Setup</h2>\n";
echo "<p>Setting up database and tables...</p>\n";

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✓ Connected to MySQL server</p>\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✓ Database '$database' created</p>\n";
    
    // Use the database
    $pdo->exec("USE $database");
    
    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/goba_hospital_db.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        $successCount = 0;
        foreach ($statements as $statement) {
            if (trim($statement)) {
                try {
                    $pdo->exec($statement);
                    $successCount++;
                } catch (PDOException $e) {
                    // Some statements might fail (like DROP TABLE IF EXISTS), that's okay
                    if (strpos($e->getMessage(), "doesn't exist") === false) {
                        echo "<p style='color: orange;'>Warning: " . htmlspecialchars($e->getMessage()) . "</p>\n";
                    }
                }
            }
        }
        
        echo "<p>✓ Executed $successCount SQL statements</p>\n";
    } else {
        // Create tables manually if SQL file doesn't exist
        createTablesManually($pdo);
    }
    
    // Verify setup
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>✓ Created " . count($tables) . " tables:</p>\n";
    echo "<ul>\n";
    foreach ($tables as $table) {
        echo "<li>$table</li>\n";
    }
    echo "</ul>\n";
    
    // Check sample data
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<p>✓ Inserted $userCount sample users</p>\n";
    
    echo "<h3>Setup Complete!</h3>\n";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
    echo "<h4>Demo Login Credentials:</h4>\n";
    echo "<strong>Admin:</strong> ID = ADMIN001, Password = admin123<br>\n";
    echo "<strong>Doctor:</strong> ID = DOC001, Password = doctor123<br>\n";
    echo "<strong>Patient:</strong> ID = PAT001, Password = patient123<br>\n";
    echo "<strong>Staff:</strong> ID = STAFF001, Password = staff123<br>\n";
    echo "</div>\n";
    
    echo "<p><a href='../index.html'>Go to Hospital Management System</a></p>\n";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Please check your database configuration in this file.</p>\n";
}

function createTablesManually($pdo) {
    echo "<p>Creating tables manually...</p>\n";
    
    // Users table
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
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
    
    // Patients table
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
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    
    // Doctors table
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
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    
    // Medical Records table
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
            FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ");
    
    // Medical Files table
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
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    
    // Appointments table
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
            FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    
    // User Sessions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_token VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    
    // Audit Logs table
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
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    
    // System Settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
    
    // Insert sample data
    insertSampleData($pdo);
}

function insertSampleData($pdo) {
    echo "<p>Inserting sample data...</p>\n";
    
    // Default admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("
        INSERT IGNORE INTO users (
            user_type, id_number, first_name, last_name, email, 
            password_hash, id_type, is_active
        ) VALUES (
            'admin', 'ADMIN001', 'System', 'Administrator', 'admin@gobahospital.et',
            '$adminPassword', 'national_id', TRUE
        )
    ");
    
    // Sample doctor
    $doctorPassword = password_hash('doctor123', PASSWORD_DEFAULT);
    $pdo->exec("
        INSERT IGNORE INTO users (
            user_type, id_number, first_name, last_name, email, phone,
            date_of_birth, gender, password_hash, id_type, specialization, 
            department, is_active
        ) VALUES (
            'doctor', 'DOC001', 'Dr. Abebe', 'Kebede', 'dr.abebe@gobahospital.et',
            '+251911234567', '1980-05-15', 'male', '$doctorPassword', 'national_id',
            'Cardiology', 'Internal Medicine', TRUE
        )
    ");
    
    // Sample patient
    $patientPassword = password_hash('patient123', PASSWORD_DEFAULT);
    $pdo->exec("
        INSERT IGNORE INTO users (
            user_type, id_number, first_name, last_name, email, phone,
            date_of_birth, gender, password_hash, id_type, 
            emergency_contact, is_active
        ) VALUES (
            'patient', 'PAT001', 'Almaz', 'Tadesse', 'almaz.tadesse@email.com',
            '+251922345678', '1990-08-20', 'female', '$patientPassword', 'national_id',
            'Dawit Tadesse - +251933456789', TRUE
        )
    ");
    
    // Sample staff
    $staffPassword = password_hash('staff123', PASSWORD_DEFAULT);
    $pdo->exec("
        INSERT IGNORE INTO users (
            user_type, id_number, first_name, last_name, email, phone,
            date_of_birth, gender, password_hash, id_type, 
            specialization, department, is_active
        ) VALUES (
            'staff', 'STAFF001', 'Sister Meron', 'Haile', 'meron.haile@gobahospital.et',
            '+251944567890', '1985-03-10', 'female', '$staffPassword', 'national_id',
            'Registered Nurse', 'Internal Medicine', TRUE
        )
    ");
    
    // System settings
    $pdo->exec("
        INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
        ('hospital_name', 'Goba Hospital', 'Hospital name'),
        ('hospital_address', 'Goba, Bale Zone, Oromia Region, Ethiopia', 'Hospital address'),
        ('hospital_phone', '+251-22-XXX-XXXX', 'Hospital phone number'),
        ('hospital_email', 'info@gobahospital.et', 'Hospital email'),
        ('system_version', '1.0.0', 'System version'),
        ('max_file_size', '10485760', 'Maximum file upload size in bytes (10MB)'),
        ('allowed_file_types', 'pdf,jpg,jpeg,png,doc,docx', 'Allowed file types for upload')
    ");
    
    echo "<p>✓ Sample data inserted</p>\n";
}
?>