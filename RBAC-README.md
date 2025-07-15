# PPMS Role-Based Access Control (RBAC) System

## Overview
The Perwira Parcel Management System (PPMS) implements a Role-Based Access Control system with two main roles:

### Roles

#### 1. Staff
- **Registration**: Can register through the staff registration page
- **Login**: Uses Staff ID and password
- **Permissions**:
  - View and manage parcels
  - Add new parcels
  - Generate QR codes for receivers
  - Access basic dashboard functionality

#### 2. Admin
- **Registration**: No registration required (single admin account)
- **Login**: Uses the same staff login page with special credentials
- **Permissions**:
  - All staff permissions
  - Generate comprehensive reports
  - View system statistics
  - Access admin-only features

## Authentication System

### Staff Login
- **URL**: `html/staff-login.html`
- **Handler**: `php/staff-login.php`
- **Credentials**: Staff ID + Password (stored in database)

### Admin Login
- **URL**: Same as staff login (`html/staff-login.html`)
- **Handler**: Same as staff login (`php/staff-login.php`)
- **Credentials**: 
  - Staff ID: `ADMIN`
  - Password: `admin123`
- **Note**: Admin credentials are hardcoded for security

## Color Schemes

### Staff Interface
- **Gradient**: Orange to Purple (`#6A1B9A` to `#FF9800`)
- **Used in**: Staff login, registration, and dashboard pages

### Receiver Interface  
- **Gradient**: Blue to Green (`#667eea` to `#764ba2`)
- **Used in**: Receiver login, registration, and dashboard pages

## Database Schema

### Staff Table
```sql
CREATE TABLE Staff (
    staffID VARCHAR(20) PRIMARY KEY,
    role VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    phoneNumber VARCHAR(20),
    password VARCHAR(255) NOT NULL
);
```

### Retrieval Table
```sql
CREATE TABLE Retrieval (
    RetrievalID INT AUTO_INCREMENT PRIMARY KEY,
    trackingNumber VARCHAR(50) NOT NULL,
    ICNumber VARCHAR(20) NOT NULL,
    staffID VARCHAR(20),
    retrieveDate DATE,
    retrieveTime TIME,
    status VARCHAR(50)
);
```

## Admin Features

### Dashboard Statistics
- Total parcels in system
- Parcels retrieved today
- Pending parcels count

### Report Generation
- **URL**: `html/admin-reports.html`
- **Handler**: `php/generate-report.php`
- **Filters**:
  - Date range (start/end date)
  - Status filter (All/Retrieved/Pending)
  - Staff ID filter
- **Output**: Detailed table with print functionality

## File Structure

### HTML Files
- `html/staff-login.html` - Staff/Admin login page
- `html/staff-register.html` - Staff registration page
- `html/staff-dashboard.html` - Main dashboard (role-aware)
- `html/admin-reports.html` - Admin-only reports page

### PHP Files
- `php/staff-login.php` - Handles both staff and admin login
- `php/staff-register.php` - Handles staff registration
- `php/generate-report.php` - Admin report generation
- `php/get-admin-stats.php` - Dashboard statistics for admin

### SQL Files
- `sql/setup-admin.sql` - Sample data and admin setup reference

## Security Features

1. **Password Hashing**: All staff passwords are hashed using PHP's `password_hash()`
2. **Session Management**: User roles stored in PHP sessions
3. **Access Control**: Admin features protected by role checks
4. **Hardcoded Admin**: Single admin account prevents unauthorized admin creation

## Usage Instructions

### For Staff
1. Register at `html/staff-register.html`
2. Login at `html/staff-login.html`
3. Access dashboard with standard features

### For Admin
1. Login at `html/staff-login.html` with:
   - Staff ID: `ADMIN`
   - Password: `admin123`
2. Access dashboard with additional admin tab
3. Generate reports from admin reports page

## Development Notes

- Admin role is determined by hardcoded credentials in `php/staff-login.php`
- Role-based UI elements are controlled via JavaScript in the dashboard
- Color gradients distinguish between staff and receiver interfaces
- All admin features require session role verification
