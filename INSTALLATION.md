# Goba Hospital Patient Record Management System - Installation Guide

## Prerequisites

Before installing the system, ensure you have the following software installed:

- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 7.4+ (with PDO, MySQL, and file upload extensions)
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Web Browser**: Modern browser with JavaScript enabled

## Installation Steps

### 1. Download and Extract

1. Download the project files to your web server directory
2. Extract the files to your web root (e.g., `/var/www/html/` or `htdocs/`)

### 2. Database Setup

1. **Create Database**:
   ```sql
   CREATE DATABASE goba_hospital_db;
   USE goba_hospital_db;
   ```

2. **Import Schema**:
   ```bash
   mysql -u root -p goba_hospital_db < database/schema.sql
   ```

3. **Verify Tables**:
   ```sql
   SHOW TABLES;
   ```

### 3. Configuration

1. **Database Configuration**:
   Edit `config/database.php` and update the database credentials:
   ```php
   private $host = 'localhost';
   private $db_name = 'goba_hospital_db';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

2. **File Permissions**:
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/external_health/
   chmod 644 config/database.php
   ```

### 4. Web Server Configuration

#### Apache Configuration
Add to your `.htaccess` file or Apache config:
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

#### Nginx Configuration
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 5. Initial Setup

1. **Access the System**:
   Open your web browser and navigate to:
   ```
   http://your-domain.com/
   ```

2. **Default Admin Credentials**:
   - Username: `admin`
   - Password: `password`
   
   **Important**: Change the default password immediately after first login!

3. **Create Initial Users**:
   - Register patients, doctors, and staff through the admin portal
   - Set up hospital information
   - Configure payment methods

## System Features

### User Portals

#### 1. Patient Portal
- **Access**: `http://your-domain.com/patient/login.php`
- **Features**:
  - View medical records
  - Search consultation history
  - Make payments
  - Update profile information

#### 2. Doctor Portal
- **Access**: `http://your-domain.com/doctor/login.php`
- **Features**:
  - Record consultations with audio
  - Manage patient records
  - Search patient history
  - Record operations and diagnoses

#### 3. Staff Portal
- **Access**: `http://your-domain.com/staff/login.php`
- **Features**:
  - Record medicine administration
  - View patient information
  - Manage medical records

#### 4. Admin Portal
- **Access**: `http://your-domain.com/admin/login.php`
- **Features**:
  - Manage users and hospitals
  - System administration
  - View all records

#### 5. External Health Office
- **Access**: `http://your-domain.com/external/login.php`
- **Features**:
  - Upload patient files (PDF, JPG, PNG)
  - Send information to other hospitals
  - Track file transfers

### Payment Integration

The system supports multiple payment methods:
- **Commercial Bank of Ethiopia**
- **Awash Bank**
- **Abyssinia Bank**
- **Telebirr**

## Security Considerations

### 1. Password Security
- Use strong passwords for all accounts
- Enable password hashing (already implemented)
- Regular password updates

### 2. File Upload Security
- Only authorized file types (PDF, JPG, PNG)
- File size limits (10MB max)
- Secure file storage outside web root

### 3. Database Security
- Use dedicated database user
- Limit database permissions
- Regular backups

### 4. SSL/TLS
- Enable HTTPS for production
- Secure cookie settings
- HSTS headers

## Backup and Maintenance

### 1. Database Backup
```bash
# Daily backup script
mysqldump -u username -p goba_hospital_db > backup_$(date +%Y%m%d).sql
```

### 2. File Backup
```bash
# Backup uploads directory
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

### 3. Log Monitoring
- Monitor error logs
- Check access logs
- Review security events

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Check database credentials in `config/database.php`
   - Verify MySQL service is running
   - Check database permissions

2. **File Upload Issues**:
   - Check directory permissions
   - Verify PHP upload settings
   - Check file size limits

3. **Session Issues**:
   - Check PHP session configuration
   - Verify session directory permissions
   - Clear browser cookies

4. **Audio Recording Not Working**:
   - Ensure HTTPS is enabled
   - Check browser microphone permissions
   - Verify MediaRecorder API support

### Error Logs

Check these locations for error logs:
- **Apache**: `/var/log/apache2/error.log`
- **Nginx**: `/var/log/nginx/error.log`
- **PHP**: `/var/log/php/error.log`

## Support

For technical support or questions:
- Email: support@gobahospital.com
- Phone: +251-123456789
- Documentation: Check the README.md file

## Updates

To update the system:
1. Backup current installation
2. Download new version
3. Replace files (except config and uploads)
4. Run database migrations if needed
5. Test functionality

## License

This system is developed for Goba Hospital's internal use.
All rights reserved.

---

**Note**: This is a comprehensive healthcare management system. Ensure compliance with local healthcare regulations and data protection laws before deployment.