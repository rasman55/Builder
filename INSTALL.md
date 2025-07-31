# Quick Installation Guide
## Goba Hospital Patient Record Management System

### 🚀 **3-Step Quick Setup**

#### **Step 1: Download & Extract**
- Download the system files
- Extract to your web server directory (e.g., `htdocs`, `www`, or `public_html`)

#### **Step 2: Setup Database (Choose One)**

**Option A: Automatic Setup (Easiest)**
1. Open browser and navigate to: `http://your-server/database/setup.php`
2. Follow the on-screen instructions
3. Database and sample data will be created automatically

**Option B: Manual MySQL Import**
```bash
mysql -u root -p < database/goba_hospital_db.sql
```

#### **Step 3: Start Using**
1. Navigate to: `http://your-server/index.html`
2. Click "Login" and choose your user type
3. Use demo credentials to test the system

---

### 🔑 **Demo Login Credentials**

| User Type | ID | Password | Purpose |
|-----------|----|---------:|---------|
| **Admin** | ADMIN001 | admin123 | System management |
| **Doctor** | DOC001 | doctor123 | Medical records |
| **Patient** | PAT001 | patient123 | View records |
| **Staff** | STAFF001 | staff123 | Assist doctors |

---

### ⚙️ **System Requirements**
- PHP 7.4+ 
- MySQL 5.7+
- Web server (Apache/Nginx)
- Modern browser

### 📂 **File Permissions**
Ensure these directories are writable:
```bash
chmod 755 uploads/
chmod 755 config/
```

### 🛠️ **Configuration**
- Database settings: `config/database.php`
- Upload settings: Check `system_settings` table
- Default: 10MB max file size, PDF/JPG/PNG/DOC allowed

---

### 🆘 **Troubleshooting**

**Database Connection Issues:**
- Check MySQL credentials in `config/database.php`
- Ensure MySQL server is running
- Verify database permissions

**File Upload Issues:**
- Check folder permissions (755 for uploads/)
- Verify PHP upload settings in php.ini
- Check system_settings table for limits

**Login Problems:**
- Verify database is set up correctly
- Check that sample data was inserted
- Use exact demo credentials (case-sensitive)

---

### 📞 **Need Help?**
- Check the full README.md for detailed documentation
- Verify all demo credentials are working
- Ensure database setup completed successfully

**Ready to go!** The system includes complete patient record management, medical history tracking, appointment scheduling, and multi-user role management.