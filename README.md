# Goba Hospital - Patient Record Management System

A comprehensive Patient Record Management System designed for Goba Hospital, featuring secure authentication, role-based access control, and complete medical record management capabilities.

## 🏥 System Overview

This system serves as a centralized database for storing and managing patient medical records, enabling rapid retrieval and secure access for healthcare professionals and patients.

### Key Features

- **Multi-User Authentication**: Separate login portals for Admin, Patient, Doctor, Staff, and External Health Office
- **Comprehensive Record Management**: Input, update, and view medical records including consultations, diagnoses, surgeries, and treatments
- **Advanced Search**: Find records by doctor/staff ID, date & time, reference number, and various other criteria
- **File Management**: Upload and manage medical documents, images, and reports
- **Role-Based Permissions**: Secure access control ensuring users only see authorized data
- **Audit Logging**: Complete activity tracking for compliance and security
- **Responsive Design**: Modern, mobile-friendly interface

## 🚀 Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript, jQuery
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **File Storage**: Local filesystem with organized directory structure
- **Security**: Password hashing, session management, SQL injection prevention

## 📋 User Types & Capabilities

### 1. **Patient Portal**
- View personal medical records categorized by type (consultation, surgery, diagnosis)
- Access medical history with details (date & time, doctor name, complaints, treatments, reference numbers)
- Search personal records
- View upcoming appointments
- Update personal profile information

### 2. **Doctor Portal**
- Add and update patient medical records
- View complete medical histories of patients
- Search across all accessible patient records
- Manage patient consultations and treatments
- Upload medical files and documents
- View scheduled appointments

### 3. **Medical Staff Portal**
- Input and update patient medical information (with appropriate permissions)
- Assist doctors with record management
- Search and retrieve patient records
- Upload supporting documents

### 4. **Admin Portal**
- Complete user management (register, delete, activate/deactivate users)
- Full system access and control
- Manage user permissions and roles
- View system-wide statistics and reports
- Access audit logs and activity tracking

### 5. **External Health Office Portal**
- Authorized access for external health office staff
- Upload patient information with PDF/JPG files
- Share patient data with other hospitals via secure links
- Limited access based on authorization level

## 🛠️ Installation & Setup

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (optional, for dependency management)

### Step 1: Clone the Repository

```bash
git clone <repository-url>
cd goba-hospital-management
```

### Step 2: Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE goba_hospital_db;
```

2. Update database configuration in `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'goba_hospital_db');
```

3. The database tables will be automatically created when you first access the system.

### Step 3: File Permissions

Ensure the following directories are writable:
```bash
chmod 755 uploads/
chmod 755 uploads/documents/
chmod 755 uploads/images/
chmod 755 config/
```

### Step 4: Web Server Configuration

Point your web server document root to the project directory, or place the project in your web server's document root.

### Step 5: Access the System

Navigate to your web server URL to access the system.

## 🔐 Demo Credentials

The system comes with a default admin account:

**Admin Login:**
- **User Type**: Admin
- **ID Number**: ADMIN001
- **Password**: admin123

**Note**: Change the default admin password immediately after first login for security.

## 📁 Directory Structure

```
goba-hospital-management/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── config/
│   └── database.php
├── includes/
│   ├── auth.php
│   ├── records.php
│   └── search.php
├── uploads/
│   ├── documents/
│   └── images/
├── index.html
├── login.php
├── register.php
├── patient_dashboard.php
├── doctor_dashboard.php
├── staff_dashboard.php (pending)
├── admin_dashboard.php (pending)
├── external_dashboard.php (pending)
└── README.md
```

## 🔍 API Endpoints

### Authentication
- `POST includes/auth.php` - Handle login, registration, logout

### Medical Records
- `POST includes/records.php` - Add, update, view, delete medical records
- `GET includes/records.php` - Retrieve patient/doctor records

### Search
- `POST includes/search.php` - Search records, patients, doctors

## 🔒 Security Features

1. **Password Security**: Bcrypt hashing for all passwords
2. **Session Management**: Secure session handling with database storage
3. **SQL Injection Prevention**: Prepared statements for all database queries
4. **Role-Based Access**: Strict permission checking for all operations
5. **File Upload Security**: File type validation and secure storage
6. **Audit Logging**: Complete activity tracking for compliance

## 📊 Database Schema

### Key Tables

- **users**: Core user information for all user types
- **patients**: Extended patient-specific information
- **doctors**: Extended doctor-specific information
- **medical_records**: Complete medical record storage
- **medical_files**: File attachments for medical records
- **appointments**: Appointment scheduling and management
- **user_sessions**: Active session tracking
- **audit_logs**: System activity logging

## 🔧 Configuration

### File Upload Settings

Maximum file size: 10MB  
Allowed file types: PDF, JPG, JPEG, PNG, DOC, DOCX

Update in `config/database.php`:
```php
INSERT INTO system_settings (setting_key, setting_value) VALUES
('max_file_size', '10485760'),
('allowed_file_types', 'pdf,jpg,jpeg,png,doc,docx');
```

## 🚀 Features in Development

- Staff dashboard completion
- Admin dashboard with advanced user management
- External health office portal with inter-hospital sharing
- Advanced reporting and analytics
- Email notification system
- Mobile application API

## 📝 Usage Guidelines

### For Patients
1. Register using National ID, Passport, or Birth Certificate
2. Login to access your medical records
3. View categorized records (consultation, surgery, diagnosis)
4. Search your medical history
5. Update personal profile information

### For Doctors
1. Register with medical license information
2. Add medical records for patients
3. Search and view patient histories
4. Upload medical documents
5. Manage consultations and treatments

### For Staff
1. Register with department and role information
2. Assist with patient record management
3. Update patient information as authorized
4. Search and retrieve records

### For Administrators
1. Use default admin credentials initially
2. Register new users (patients, doctors, staff)
3. Manage user permissions
4. Monitor system activity
5. Configure system settings

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For technical support or questions about the Patient Record Management System, please contact the development team.

## 📄 License

This project is developed for Goba Hospital. All rights reserved.

---

**Goba Hospital Patient Record Management System** - Secure, Efficient, Comprehensive Medical Record Management