# ⚠️ ISSUES & RECOMMENDATIONS

---

## 🔴 CRITICAL ISSUES (FIXED ✅)

### 1. Database Connection File Naming Mismatch
**Severity:** CRITICAL  
**Files Affected:** 2
- ❌ `verify-qr-scan.php` (Line 16)
- ❌ `process-parcel-retrieval.php` (Line 17)

**Problem:** Both files tried to include `db-connection.php` (hyphen) but the actual file is `db_connect.php` (underscore).

**Impact:** QR verification and parcel retrieval would completely fail with "file not found" error.

**Status:** ✅ FIXED - Both files now correctly reference `db_connect.php`

---

## 🟡 MEDIUM PRIORITY ISSUES

### 1. Default Admin Credentials Hardcoded
**File:** `staff-login.php` (Line 16)
```php
if ($staffId === 'ADMIN' && $password === 'admin123') {
```

**Risk:** Hardcoded credentials are a security vulnerability.

**Recommendation:**
```php
// Use environment variable instead
$default_admin_id = getenv('ADMIN_ID') ?: 'ADMIN';
$default_admin_pass = getenv('ADMIN_PASSWORD') ?: 'admin123';
if ($staffId === $default_admin_id && password_verify($password, $default_admin_pass)) {
```

---

### 2. Database Credentials Hardcoded
**File:** `php/db_connect.php`
```php
$user = "myppmsco_ppms";
$pass = "cM@IjCvdBAe%EcGi";
```

**Risk:** Credentials visible in source code.

**Recommendation:** Use `.env` file with `php-dotenv` library:
```php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$db = $_ENV['DB_NAME'];
```

---

### 3. Email Credentials Hardcoded
**File:** `php/smtp-config.php` (Line 124)
```php
'gmail_email' => 'iskandardzulqarnain0104@gmail.com',
'app_password' => 'khfroloeubtuyvgb',
```

**Risk:** Gmail credentials exposed in source code.

**Recommendation:** Move to `.env` file:
```php
function getEmailConfig() {
    return [
        'gmail_email' => $_ENV['GMAIL_EMAIL'],
        'app_password' => $_ENV['GMAIL_APP_PASSWORD'],
        'from_name' => $_ENV['EMAIL_FROM_NAME'] ?? 'PPMS'
    ];
}
```

---

### 4. QR Secret Key Uses Date-Based Fallback
**File:** `php/admin-save-qr.php` (Line 184)
```php
return 'ppms-qr-secret-key-change-in-production-' . date('Y');
```

**Risk:** Secret key changes yearly, old QR codes become invalid.

**Recommendation:** Use environment variable:
```php
function getQRSecretKey() {
    $secretKey = $_ENV['QR_SECRET_KEY'] ?? null;
    if (!$secretKey) {
        throw new Exception('QR_SECRET_KEY not configured in environment');
    }
    return $secretKey;
}
```

---

## 🟠 LOW PRIORITY ISSUES

### 1. Error Reporting Enabled in Production
**Files:** Multiple PHP files
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

**Recommendation:** Disable in production:
```php
if ($_ENV['APP_ENV'] === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
```

---

### 2. No CSRF Protection
**Issue:** Forms don't use CSRF tokens.

**Recommendation:** Add CSRF token validation:
```php
// Generate token
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate on POST
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```

---

### 3. No Rate Limiting on Login
**Issue:** Brute force attacks possible on login endpoints.

**Recommendation:** Implement rate limiting:
```php
$rate_limit_key = 'login_' . $_SERVER['REMOTE_ADDR'];
if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = [];
}
$_SESSION[$rate_limit_key] = array_filter($_SESSION[$rate_limit_key], 
    fn($t) => $t > time() - 3600);
if (count($_SESSION[$rate_limit_key]) >= 5) {
    die('Too many login attempts');
}
```

---

### 4. No Input Sanitization on Some Fields
**Issue:** Some files don't sanitize all inputs.

**Recommendation:** Use consistent sanitization:
```php
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
```

---

### 5. No Logging System
**Issue:** No audit trail for critical operations.

**Recommendation:** Implement logging:
```php
function logActivity($action, $user_id, $details) {
    $log_file = __DIR__ . '/../logs/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $action | User: $user_id | Details: $details\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
```

---

## ✅ WHAT'S WORKING WELL

1. ✅ **Prepared Statements** - All SQL queries use prepared statements (SQL injection safe)
2. ✅ **Password Hashing** - Uses bcrypt (password_hash/password_verify)
3. ✅ **Session Management** - Proper session handling
4. ✅ **Role-Based Access Control** - Staff/Admin/Receiver roles enforced
5. ✅ **QR Signature Verification** - HMAC-SHA256 prevents tampering
6. ✅ **Email Validation** - Uses filter_var with FILTER_VALIDATE_EMAIL
7. ✅ **Matric Format Validation** - Regex validation on all matric inputs
8. ✅ **Rate Limiting** - Forgot password has rate limiting
9. ✅ **Error Handling** - Try-catch blocks on database operations
10. ✅ **Notification System** - Comprehensive notification tracking

---

## 📋 IMPLEMENTATION PRIORITY

### Phase 1 (Immediate - Security)
1. Move credentials to `.env` file
2. Add CSRF protection
3. Disable error reporting in production

### Phase 2 (Short-term - Hardening)
1. Add rate limiting to login
2. Implement audit logging
3. Add input sanitization consistency

### Phase 3 (Medium-term - Enhancement)
1. Add 2FA for staff
2. Implement API key authentication
3. Add request signing

---

## 🎯 CONCLUSION

**System is FULLY FUNCTIONAL after fixes.** The 2 critical database connection issues have been resolved. Remaining issues are security hardening recommendations for production deployment.

**Recommended:** Implement Phase 1 security measures before production deployment.

