-- Fix for Hospital table already exists error
-- Choose one of the following approaches:

-- OPTION 1: Drop existing table and recreate (WARNING: This will delete all data)
-- Uncomment the lines below if you want to start fresh
/*
DROP TABLE IF EXISTS Hospital;
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
*/

-- OPTION 2: Create table only if it doesn't exist
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

-- OPTION 3: Check existing table structure and modify if needed
-- First, let's see what the existing table looks like
-- Run this query to check: DESCRIBE Hospital;

-- If the existing table has different structure, you can use ALTER TABLE:
-- Example modifications (uncomment and modify as needed):

/*
-- Add missing columns if they don't exist
ALTER TABLE Hospital 
ADD COLUMN IF NOT EXISTS license_number VARCHAR(100) UNIQUE,
ADD COLUMN IF NOT EXISTS hospital_type ENUM('Public', 'Private', 'Specialized', 'Clinic') DEFAULT 'Public',
ADD COLUMN IF NOT EXISTS bed_capacity INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS status ENUM('Active', 'Inactive', 'Under_Maintenance') DEFAULT 'Active',
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
*/

-- OPTION 4: Backup existing data before recreating
-- Step 1: Create backup table
/*
CREATE TABLE Hospital_backup AS SELECT * FROM Hospital;

-- Step 2: Drop and recreate Hospital table
DROP TABLE Hospital;
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

-- Step 3: Restore data from backup (modify column mapping as needed)
INSERT INTO Hospital (ID, name, email, phone, address, city, region)
SELECT ID, name, email, phone, address, city, region FROM Hospital_backup;

-- Step 4: Drop backup table when satisfied
-- DROP TABLE Hospital_backup;
*/

-- Insert default hospital data
INSERT IGNORE INTO Hospital (name, email, phone, address, city, region, license_number, hospital_type, bed_capacity) 
VALUES ('Goba Hospital', 'info@gobahospital.com', '+251-XX-XXX-XXXX', 'Goba Town', 'Goba', 'Oromia', 'GH-2024-001', 'Public', 150);