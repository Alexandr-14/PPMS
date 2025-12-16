# PPMS - Perwira Parcel Management System

## 🚀 Deployment Guide

### Prerequisites
- Web hosting with **PHP 8.x** and **MySQL 5.7+**
- cPanel access (or equivalent)
- FTP client (optional)

---

## 📦 Quick Setup

### Step 1: Upload Files
1. Login to your **cPanel**
2. Go to **File Manager** → `public_html`
3. Upload all files from this repository
4. Or use FTP to upload files

### Step 2: Create Database
1. In cPanel, go to **MySQL Databases**
2. Create a new database (e.g., `ppms`)
3. Create a database user with password
4. Add user to database with **ALL PRIVILEGES**

### Step 3: Import Database
1. Go to **phpMyAdmin**
2. Select your database
3. Click **Import** tab
4. Upload `database/ppms_database.sql`
5. Click **Go**

### Step 4: Configure Connection
1. Edit `php/db_connect.php`
2. Update with your hosting credentials:

```php
$servername = "localhost";
$username = "your_cpanel_user_database";
$password = "your_database_password";
$dbname = "your_database_name";
```

### Step 5: Test
1. Visit your domain: `https://yourdomain.com/html/landingpage.html`
2. Test login functionality
3. Done! 🎉

---

## 📁 Folder Structure

```
PPMS/
├── assets/          # Images, icons, QR codes
├── css/             # Stylesheets
├── database/        # SQL backup file
├── html/            # Frontend pages (PHP/HTML)
├── js/              # JavaScript files
└── php/             # Backend API scripts
```

---

## 🔐 Default Accounts

### Admin
- Login: `/html/staff-login.html`
- Register as Admin to access admin features

### Staff
- Login: `/html/staff-login.html`

### Receiver (Student)
- Login: `/html/receiver-login.html`
- Register: `/html/receiver-register.html`

---

## ⚙️ Requirements

| Component | Version |
|-----------|---------|
| PHP | 8.0+ |
| MySQL | 5.7+ |
| Web Server | Apache/Nginx |

---

## 📧 Support

For issues, contact the system administrator.

---

**Perwira Parcel Management System (PPMS)**  
Kolej Kediaman Perwira, UTHM

