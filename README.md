# Goba Hospital Patient Record Management System

A comprehensive, responsive web-based patient record management system designed specifically for Goba Hospital in Ethiopia. This system provides secure access to medical records for patients, doctors, medical staff, administrators, and external health offices.

## 🏥 Features

### Patient Portal
- **Secure Login**: Multi-factor authentication with account lockout protection
- **Medical Records**: View complete medical history including consultations, operations, and diagnoses
- **Advanced Search**: Find specific medical information quickly
- **Payment Management**: Support for Ethiopian banks (Commercial Bank, Awash Bank, Abyssinia Bank) and Telebirr
- **Profile Management**: Update personal information and contact details
- **Appointment Tracking**: View upcoming and past appointments

### Doctor Portal
- **Patient Management**: Access complete patient medical histories
- **Record Creation**: Add consultations, operations, and diagnoses
- **Audio Recording**: Support for consultation audio recordings
- **Search Functionality**: Find patients by various criteria
- **Prescription Management**: Digital prescription creation and management

### Medical Staff Portal
- **Medicine Administration**: Record and track medication dosages
- **Patient Updates**: Update patient information and medical data
- **Shift Management**: Track work schedules and responsibilities
- **Inventory Management**: Monitor medical supplies and equipment

### Admin Portal
- **User Management**: Register and manage doctors, staff, and patients
- **System Oversight**: Monitor system usage and performance
- **Report Generation**: Create detailed reports for hospital management
- **Access Control**: Manage user permissions and security settings

### External Health Office Portal
- **Patient Referrals**: Send patients to other hospitals
- **File Upload**: Support for PDF, JPG, and other medical documents
- **Inter-hospital Communication**: Seamless patient transfer system
- **Document Management**: Secure file sharing between healthcare facilities

## 🛠 Technology Stack

### Frontend
- **HTML5**: Semantic markup for accessibility
- **CSS3**: Modern styling with Flexbox and Grid
- **JavaScript (ES6+)**: Interactive functionality and form validation
- **Font Awesome**: Professional icon library
- **Google Fonts**: Inter font family for optimal readability

### Backend
- **PHP 8.0+**: Server-side logic and business rules
- **MySQL 8.0+**: Relational database with advanced features
- **PDO**: Secure database abstraction layer
- **Session Management**: Secure user authentication

### Security Features
- **Password Hashing**: Argon2ID encryption
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization
- **CSRF Protection**: Token-based form validation
- **Audit Logging**: Comprehensive activity tracking
- **Account Lockout**: Brute force attack prevention

## 📋 Prerequisites

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Composer (for dependency management)
- SSL certificate (recommended for production)

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/goba-hospital.git
cd goba-hospital
```

### 2. Database Setup
```sql
-- Create database
CREATE DATABASE goba_hospital_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema
mysql -u root -p goba_hospital_db < database/schema.sql
```

### 3. Configuration
```bash
# Copy and configure database settings
cp config/database.php.example config/database.php
```

Edit `config/database.php`:
```php
const DB_HOST = 'localhost';
const DB_NAME = 'goba_hospital_db';
const DB_USER = 'your_username';
const DB_PASS = 'your_password';
```

### 4. Set Permissions
```bash
# Make upload directories writable
chmod 755 uploads/
chmod 755 uploads/patient_files/
chmod 755 uploads/audio_records/
chmod 755 uploads/medical_images/
```

### 5. Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/goba-hospital;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 🔧 Configuration

### Environment Variables
Create a `.env` file:
```env
ENVIRONMENT=production
DB_HOST=localhost
DB_NAME=goba_hospital_db
DB_USER=hospital_user
DB_PASS=secure_password_2024
AUTO_CREATE_TABLES=false
```

### Default Admin Account
After installation, use these credentials to access the admin portal:
- **Username**: admin
- **Password**: admin123 (change immediately after first login)

## 📚 Database Schema

### Core Tables
- `Patient`: Patient demographic and medical information
- `Doctor`: Healthcare provider information
- `Medical_staff`: Hospital staff details
- `Hospital`: Facility information
- `Consultation`: Patient consultation records with audio support
- `Operation`: Surgical procedure documentation
- `Diagnosis`: Medical diagnosis records
- `Medical_administration`: Medication dosage tracking
- `Payment_records`: Financial transaction history
- `File_attachments`: Document and image storage
- `Audit_log`: System activity monitoring

### Authentication Tables
- `Patient_login`: Patient portal authentication
- `Doctor_login`: Doctor portal authentication
- `Staff_login`: Staff portal authentication
- `Admin_login`: Administrator authentication
- `External_health_office_login`: External office access

## 🔐 Security Features

### Authentication
- **Multi-factor Authentication**: Optional 2FA for enhanced security
- **Account Lockout**: Automatic lockout after failed attempts
- **Password Complexity**: Enforced strong password requirements
- **Session Management**: Secure session handling with timeouts

### Data Protection
- **Encryption**: All sensitive data encrypted at rest
- **Access Control**: Role-based permissions system
- **Audit Trail**: Comprehensive logging of all system activities
- **Data Validation**: Input sanitization and validation

### Compliance
- **HIPAA Compatible**: Healthcare data privacy compliance
- **Ethiopian Standards**: Meets local healthcare regulations
- **International Standards**: ISO 27001 security practices

## 💳 Payment Integration

### Supported Payment Methods
- **Commercial Bank of Ethiopia**: Direct bank integration
- **Awash Bank**: Secure payment processing
- **Abyssinia Bank**: Real-time transaction handling
- **Telebirr**: Mobile money integration
- **Cash Payments**: Manual payment recording
- **Insurance Claims**: Automated insurance processing

### Payment Features
- Real-time transaction processing
- Receipt generation and management
- Payment history tracking
- Refund and adjustment handling
- Multi-currency support (ETB primary)

## 📱 Mobile Responsiveness

The system is fully responsive and optimized for:
- **Desktop**: Full-featured interface
- **Tablet**: Touch-optimized navigation
- **Mobile**: Condensed layout with essential features
- **Progressive Web App**: Offline capability (planned)

## 🔍 Search Functionality

### Advanced Search Features
- **Patient Search**: By name, SSN, phone, or email
- **Medical Records**: By date range, doctor, or diagnosis
- **Prescription Search**: By medication or dosage
- **Appointment Search**: By doctor, date, or status
- **Full-text Search**: Across all medical records

## 📊 Reporting and Analytics

### Available Reports
- **Patient Statistics**: Demographics and visit patterns
- **Doctor Workload**: Consultation and operation metrics
- **Financial Reports**: Revenue and payment analysis
- **Medical Trends**: Disease patterns and treatment outcomes
- **System Usage**: User activity and performance metrics

## 🌐 Internationalization

### Language Support
- **Amharic**: Primary language for Ethiopian users
- **English**: Secondary language for international staff
- **Oromo**: Regional language support (planned)
- **Arabic**: Additional language option (planned)

## 🚀 Deployment

### Production Deployment
1. **SSL Certificate**: Install valid SSL certificate
2. **Environment**: Set ENVIRONMENT=production
3. **Database**: Configure production database
4. **Backups**: Set up automated backups
5. **Monitoring**: Implement system monitoring
6. **Performance**: Configure caching and optimization

### Docker Deployment (Optional)
```bash
# Build container
docker build -t goba-hospital .

# Run with docker-compose
docker-compose up -d
```

## 🔧 Maintenance

### Regular Tasks
- **Database Backups**: Daily automated backups
- **Log Rotation**: Weekly log file rotation
- **Security Updates**: Monthly security patches
- **Performance Monitoring**: Continuous system monitoring
- **User Training**: Regular staff training sessions

### Troubleshooting
Common issues and solutions are documented in the `docs/troubleshooting.md` file.

## 🤝 Contributing

We welcome contributions to improve the Goba Hospital system:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new features
5. Submit a pull request

### Development Guidelines
- Follow PSR-12 coding standards
- Write comprehensive tests
- Document all new features
- Maintain backwards compatibility

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

### Technical Support
- **Email**: support@gobahospital.com
- **Phone**: +251-XX-XXX-XXXX
- **Documentation**: Available in the `docs/` directory
- **Issue Tracker**: GitHub Issues for bug reports

### Emergency Contact
For critical system issues:
- **24/7 Hotline**: +251-XX-XXX-XXXX
- **Emergency Email**: emergency@gobahospital.com

## 🎯 Roadmap

### Version 2.0 (Q2 2024)
- [ ] Telemedicine integration
- [ ] Mobile application
- [ ] AI-powered diagnostics
- [ ] Blockchain health records
- [ ] IoT device integration

### Version 3.0 (Q4 2024)
- [ ] Multi-hospital network
- [ ] Advanced analytics dashboard
- [ ] Patient self-service portal
- [ ] Integration with national health system
- [ ] Machine learning predictions

## 🏆 Acknowledgments

- **Goba Hospital Staff**: For requirements and testing
- **Ethiopian Ministry of Health**: For regulatory guidance
- **Open Source Community**: For tools and libraries used
- **Beta Testers**: For valuable feedback and bug reports

---

**Made with ❤️ for Goba Hospital and the Ethiopian Healthcare System**

For more detailed documentation, please refer to the `docs/` directory.