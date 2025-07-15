# PPMS Complete Backup Creation Script
# This script creates a complete backup of the PPMS system

Write-Host "🚀 Creating PPMS Complete Backup..." -ForegroundColor Green
Write-Host "📅 Backup Date: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Yellow

# Define paths
$backupName = "PPMS_Complete_Backup_$(Get-Date -Format 'yyyy-MM-dd_HH-mm-ss')"
$backupPath = "C:\xampp\htdocs\$backupName"
$zipPath = "C:\xampp\htdocs\$backupName.zip"

# Create backup directory
Write-Host "📁 Creating backup directory..." -ForegroundColor Cyan
New-Item -ItemType Directory -Path $backupPath -Force | Out-Null

# Copy PPMS system files
Write-Host "📋 Copying PPMS system files..." -ForegroundColor Cyan
Copy-Item -Path "C:\xampp\htdocs\ppms" -Destination "$backupPath\ppms" -Recurse -Force

# Copy database backup
Write-Host "🗄️ Including database backup..." -ForegroundColor Cyan
Copy-Item -Path "C:\xampp\htdocs\ppms_database_backup.sql" -Destination "$backupPath\ppms_database_backup.sql" -Force

# Copy README
Write-Host "📖 Including documentation..." -ForegroundColor Cyan
Copy-Item -Path "C:\xampp\htdocs\PPMS_BACKUP_README.md" -Destination "$backupPath\README.md" -Force

# Create installation script
Write-Host "⚙️ Creating installation script..." -ForegroundColor Cyan
$installScript = @"
# PPMS Installation Script
# Run this script to install PPMS system

Write-Host "🚀 Installing PPMS System..." -ForegroundColor Green

# Check if XAMPP is running
`$xamppPath = "C:\xampp"
if (-not (Test-Path `$xamppPath)) {
    Write-Host "❌ XAMPP not found! Please install XAMPP first." -ForegroundColor Red
    exit 1
}

# Copy files to htdocs
Write-Host "📁 Copying files to htdocs..." -ForegroundColor Cyan
Copy-Item -Path ".\ppms" -Destination "C:\xampp\htdocs\ppms" -Recurse -Force

Write-Host "✅ Files copied successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Next Steps:" -ForegroundColor Yellow
Write-Host "1. Start XAMPP (Apache + MySQL)" -ForegroundColor White
Write-Host "2. Open phpMyAdmin: http://localhost/phpmyadmin" -ForegroundColor White
Write-Host "3. Create database: CREATE DATABASE ppms;" -ForegroundColor White
Write-Host "4. Import: ppms_database_backup.sql" -ForegroundColor White
Write-Host "5. Access system: http://localhost/ppms/" -ForegroundColor White
Write-Host ""
Write-Host "🎉 Installation complete!" -ForegroundColor Green
"@

$installScript | Out-File -FilePath "$backupPath\INSTALL.ps1" -Encoding UTF8

# Create system info file
Write-Host "ℹ️ Creating system information..." -ForegroundColor Cyan
$systemInfo = @"
# PPMS System Information

## System Details
- **Name:** Perwira Parcel Management System (PPMS)
- **Version:** Complete System with All Features
- **Backup Date:** $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
- **Database:** MariaDB/MySQL
- **Framework:** PHP + Bootstrap + JavaScript

## Default Accounts
### Admin Account:
- Username: admin
- Password: admin123
- Role: Administrator

### Test Receiver:
- IC: 010401150099
- Password: MikailTest123!
- Name: Mikail

### Test Staff:
- Staff ID: 0105
- Password: StaffTest123!
- Role: Staff

## Access URLs
- Landing Page: http://localhost/ppms/
- Receiver Login: http://localhost/ppms/html/receiver-login.php
- Staff Login: http://localhost/ppms/html/staff-login.php

## Features Included
✅ Complete CRUD Operations
✅ Role-Based Access Control
✅ Notification System
✅ QR Code Generation
✅ Modern UI/UX Design
✅ Security Features (Password Hashing, SQL Injection Prevention)
✅ Responsive Design
✅ Real-time Statistics

## File Structure
- /ppms/ - Main application
- /ppms/html/ - Frontend pages
- /ppms/php/ - Backend scripts
- /ppms/css/ - Styling files
- /ppms/assets/ - Media files
- ppms_database_backup.sql - Database export

## Support
For any issues, refer to README.md for detailed installation instructions.
"@

$systemInfo | Out-File -FilePath "$backupPath\SYSTEM_INFO.txt" -Encoding UTF8

# Create the zip file
Write-Host "🗜️ Creating zip archive..." -ForegroundColor Cyan
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($backupPath, $zipPath)

# Clean up temporary directory
Write-Host "🧹 Cleaning up..." -ForegroundColor Cyan
Remove-Item -Path $backupPath -Recurse -Force

# Display results
Write-Host ""
Write-Host "🎉 PPMS Backup Created Successfully!" -ForegroundColor Green
Write-Host "📦 Backup Location: $zipPath" -ForegroundColor Yellow
Write-Host "📊 Backup Size: $([math]::Round((Get-Item $zipPath).Length / 1MB, 2)) MB" -ForegroundColor Cyan
Write-Host ""
Write-Host "📋 Backup Contents:" -ForegroundColor White
Write-Host "  ✅ Complete PPMS system files" -ForegroundColor Green
Write-Host "  ✅ Database backup (SQL)" -ForegroundColor Green
Write-Host "  ✅ Installation instructions" -ForegroundColor Green
Write-Host "  ✅ System documentation" -ForegroundColor Green
Write-Host "  ✅ Default accounts info" -ForegroundColor Green
Write-Host ""
Write-Host "🚀 Ready for deployment or archival!" -ForegroundColor Green
