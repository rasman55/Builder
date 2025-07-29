# Installation Guide - Goba Hospital Patient Record Management System

This guide will help you set up the Patient Record Management System for Goba Hospital on your local machine or server.

## Prerequisites

Before installing the system, ensure you have the following software installed:

- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher
- **Composer**: (optional, for dependency management)

### PHP Extensions Required

Make sure the following PHP extensions are enabled:
- PDO
- PDO_MySQL
- mbstring
- fileinfo
- gd (for image processing)
- curl

## Installation Steps

### 1. Download and Extract

1. Download the project files to your web server directory
2. Extract the files to your web root (e.g., `/var/www/html/` or `htdocs/`)

### 2. Database Setup

1. **Create Database**:
   ```sql
   CREATE DATABASE goba_hospital_db;
   ```

2. **Import Schema**:
   ```bash
   mysql -u your_username -p goba_hospital_db < database/schema.sql
   ```

3. **Configure Database Connection**:
   - Open `config/database.php`
   - Update the database connection settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'goba_hospital_db');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

### 3. File Permissions

Set appropriate file permissions:

```bash
# For Linux/Unix systems
chmod 755 assets/uploads/
chmod 755 assets/audio/
chmod 644 config/database.php
```

### 4. Web Server Configuration

#### Apache Configuration

Create or update your `.htaccess` file in the root directory:

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

Add this to your Nginx server block:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 5. Environment Configuration

Update the site URL in `config/database.php`:

```php
define('SITE_URL', 'http://your-domain.com'); // Replace with your actual domain
```

### 6. Default Login Credentials

After installation, you can login with the default admin account:

- **Username**: `admin`
- **Password**: `password`

**Important**: Change the default password immediately after first login!

## Post-Installation Setup

### 1. Create Additional Users

1. **Admin Portal**: Access `http://your-domain.com/admin/login.php`
2. **Register New Users**: Use the registration page at `http://your-domain.com/register.php`
3. **Create Test Data**: Add sample patients, doctors, and staff for testing

### 2. Configure Email Settings

If you plan to use email notifications, configure your email settings in the admin panel.

### 3. Set Up File Upload Limits

Update your PHP configuration to allow larger file uploads:

```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

## Security Considerations

### 1. Database Security

- Use strong passwords for database users
- Limit database user permissions
- Regularly backup your database

### 2. File Security

- Ensure upload directories are not publicly accessible
- Validate all uploaded files
- Use HTTPS in production

### 3. Session Security

- Configure secure session settings
- Use HTTPS for all communications
- Implement proper session timeout

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Verify database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Check if database exists

2. **File Upload Issues**:
   - Check file permissions on upload directories
   - Verify PHP upload settings
   - Ensure sufficient disk space

3. **Page Not Found Errors**:
   - Verify .htaccess file is present
   - Check Apache/Nginx configuration
   - Ensure mod_rewrite is enabled (Apache)

4. **Session Issues**:
   - Check PHP session configuration
   - Verify session directory permissions
   - Clear browser cookies

### Error Logs

Check these log files for detailed error information:

- **Apache**: `/var/log/apache2/error.log`
- **Nginx**: `/var/log/nginx/error.log`
- **PHP**: `/var/log/php_errors.log`

## Backup and Maintenance

### Regular Backups

1. **Database Backup**:
   ```bash
   mysqldump -u username -p goba_hospital_db > backup_$(date +%Y%m%d).sql
   ```

2. **File Backup**:
   ```bash
   tar -czf files_backup_$(date +%Y%m%d).tar.gz assets/uploads/ assets/audio/
   ```

### Maintenance Tasks

- Regularly update PHP and MySQL
- Monitor disk space usage
- Review and clean old log files
- Update system security patches

## Support

For technical support or questions:

1. Check the troubleshooting section above
2. Review the system documentation
3. Contact the development team

## License

This system is proprietary software developed for Goba Hospital. All rights reserved.

---

**Note**: This installation guide assumes a Linux/Unix environment. For Windows installations, some commands and paths may need to be adjusted accordingly.