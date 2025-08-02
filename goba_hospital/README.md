# Goba Hospital Patient Record Management System

A comprehensive web-based patient record management system designed for healthcare facilities. This system provides secure, efficient, and user-friendly interfaces for managing patient records, doctor information, and medical staff details.

## 🏥 Features

### Core Functionality
- **Multi-User Portal System**: Separate interfaces for patients, doctors, medical staff, administrators, and external health offices
- **Patient Record Management**: Complete medical history tracking including consultations, surgeries, and diagnoses
- **Doctor Portal**: Tools for managing patient consultations, surgeries, and diagnoses
- **Staff Portal**: Medication dosage recording and patient information management
- **Admin Portal**: Complete system administration and user management
- **External Health Office**: Upload and transfer patient information between hospitals

### Security & Compliance
- **HIPAA Compliant**: Secure data storage with encryption
- **Role-Based Access Control**: Different access levels for different user types
- **Secure Authentication**: Password-protected login system
- **Data Privacy**: Patient information protection with industry standards

### Payment Integration
- **Multiple Payment Methods**: Support for Commercial Bank, Awash Bank, Abyssinia Bank, and Telebirr
- **Payment Tracking**: Complete payment history and status tracking
- **Secure Transactions**: Encrypted payment processing

### Advanced Features
- **Audio Recording**: Support for consultation audio recordings
- **File Upload**: Document and image upload capabilities
- **Search Functionality**: Advanced search for patient records
- **Reference ID System**: Unique identifiers for all medical records
- **Real-time Updates**: Instant synchronization across all portals

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, etc.)
- Composer (optional, for dependency management)

### Step 1: Clone or Download
```bash
# Clone the repository
git clone <repository-url>
cd goba_hospital

# Or download and extract the ZIP file
```

### Step 2: Database Setup
1. Create a MySQL database named `goba_hospital`
2. Import the database schema:
```bash
mysql -u root -p goba_hospital < database/schema.sql
```

### Step 3: Configuration
1. Update database connection settings in `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'goba_hospital');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

2. Update the site URL in `includes/config.php`:
```php
define('SITE_URL', 'http://your-domain.com/goba_hospital');
```

### Step 4: File Permissions
Set appropriate permissions for upload directories:
```bash
chmod 755 uploads/
chmod 755 uploads/audio/
chmod 755 uploads/files/
chmod 755 uploads/images/
```

### Step 5: Web Server Configuration
Ensure your web server is configured to serve PHP files and has access to the project directory.

## 👥 User Types & Access

### Default Login Credentials

#### Admin Portal
- **Username**: `admin`
- **Password**: `admin123`
- **Access**: Complete system administration

#### Doctor Portal
- **Username**: `doctor1`
- **Password**: `doctor`
- **Access**: Patient consultations, surgeries, diagnoses

#### Patient Portal
- **Username**: `patient1`
- **Password**: `patient`
- **Access**: Personal medical records, payments

#### Staff Portal
- **Username**: `staff1`
- **Password**: `staff`
- **Access**: Medication dosages, patient information

#### External Health Office
- **Username**: `external1`
- **Password**: `external`
- **Access**: Upload and transfer patient information

## 📁 Directory Structure

```
goba_hospital/
├── admin/                    # Admin portal files
├── doctor/                   # Doctor portal files
├── external/                 # External health office portal files
├── patient/                  # Patient portal files
├── staff/                    # Staff portal files
├── uploads/                  # File upload directory
│   ├── audio/               # Audio recordings
│   ├── files/               # Document uploads
│   └── images/              # Image uploads
├── database/                 # Database files
│   └── schema.sql           # Database schema
├── assets/                   # Static assets
│   ├── css/                 # Stylesheets
│   ├── js/                  # JavaScript files
│   └── images/              # Images
├── includes/                 # Configuration and common files
│   ├── config.php           # Database and app configuration
│   ├── header.php           # Common header
│   └── footer.php           # Common footer
├── index.php                # Main landing page
├── login.php                # User authentication
├── logout.php               # Logout functionality
├── about.php                # About page
└── README.md                # This file
```

## 🔧 Configuration

### Database Configuration
Edit `includes/config.php` to configure your database connection:

```php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'goba_hospital');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// Application configuration
define('SITE_NAME', 'Goba Hospital Patient Record Management System');
define('SITE_URL', 'http://your-domain.com/goba_hospital');
```

### File Upload Settings
Configure upload directories and file size limits in `includes/config.php`:

```php
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
```

## 🛠️ Usage

### Admin Portal
1. **User Management**: Add, edit, and manage patients, doctors, and staff
2. **Hospital Management**: Configure hospital information and settings
3. **System Overview**: View system statistics and recent activities
4. **Data Management**: Oversee all patient records and medical data

### Doctor Portal
1. **Patient Consultations**: Record and manage patient consultations
2. **Surgery Records**: Document surgical procedures and outcomes
3. **Diagnoses**: Create and manage patient diagnoses
4. **Patient Search**: Search for specific patient records
5. **Audio Recording**: Record consultation audio for future reference

### Patient Portal
1. **Medical Records**: View complete medical history
2. **Payment Processing**: Make payments using various methods
3. **Profile Management**: Update personal information
4. **Appointment History**: View past and upcoming appointments

### Staff Portal
1. **Medication Management**: Record medication dosages and instructions
2. **Patient Information**: Access and update patient details
3. **Medical Records**: View patient medical histories
4. **Profile Management**: Update staff information

### External Health Office
1. **File Upload**: Upload patient information from external sources
2. **Data Transfer**: Send patient records to other facilities
3. **Record Management**: View and manage uploaded records

## 🔒 Security Features

- **Password Hashing**: All passwords are securely hashed using PHP's password_hash()
- **Session Management**: Secure session handling with automatic timeout
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: Form token validation
- **File Upload Security**: File type and size validation

## 📊 Database Schema

The system uses a comprehensive database schema with the following main tables:

- **patients**: Patient information and demographics
- **doctors**: Doctor information and specializations
- **medical_staff**: Staff information and positions
- **consultations**: Patient consultation records
- **surgeries**: Surgical procedure records
- **diagnoses**: Patient diagnosis records
- **payments**: Payment transaction records
- **patient_referrals**: Patient referral information
- **external_patient_info**: External patient data

## 🎨 Customization

### Styling
- Modify `assets/css/style.css` to customize the appearance
- Update color schemes and layouts as needed
- Add custom CSS for specific components

### Functionality
- Extend the system by adding new features in respective portal directories
- Modify database schema for additional fields
- Add new user types by creating corresponding portal directories

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify database credentials in `includes/config.php`
   - Ensure MySQL service is running
   - Check database name and user permissions

2. **File Upload Issues**
   - Verify upload directory permissions
   - Check PHP upload settings in php.ini
   - Ensure sufficient disk space

3. **Session Issues**
   - Check PHP session configuration
   - Verify session directory permissions
   - Clear browser cookies if needed

4. **404 Errors**
   - Ensure proper web server configuration
   - Check file permissions
   - Verify URL rewriting rules if using .htaccess

### Error Logging
Enable error logging in PHP to debug issues:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📞 Support

For technical support or questions:
- **Email**: info@gobahospital.com
- **Phone**: +251-123-456-789
- **Documentation**: Check this README and inline code comments

## 📄 License

This project is developed for Goba Hospital. All rights reserved.

## 🔄 Updates

### Version 1.0.0
- Initial release with core functionality
- Multi-user portal system
- Patient record management
- Payment processing
- File upload capabilities
- Security features

## 🤝 Contributing

To contribute to this project:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📝 Changelog

### Version 1.0.0 (2024)
- Initial release
- Complete patient record management system
- Multi-user portal implementation
- Security and compliance features
- Payment integration
- File upload system

---

**Note**: This system is designed for healthcare facilities and should be used in compliance with local healthcare regulations and data protection laws.