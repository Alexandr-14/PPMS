# 🔄 PPMS MODULE FLOW ANALYSIS

---

## 1️⃣ AUTHENTICATION MODULE

### Receiver Registration Flow
```
receiver-register.html
    ↓
receiver-register.php
    ├─ Validate input (matric, name, phone, password)
    ├─ Check password strength (password-validator.php)
    ├─ Check duplicate matric
    ├─ Hash password (bcrypt)
    ├─ Insert into receiver table
    └─ Redirect to login
```
**Status:** ✅ WORKING

### Receiver Login Flow
```
receiver-login.html
    ↓
receiver-login.php
    ├─ Validate matric format (2 letters + 6 digits)
    ├─ Query receiver table
    ├─ Verify password (bcrypt)
    ├─ Create session (receiver_id, receiver_name, receiver_matric)
    └─ Redirect to receiver-dashboard.php
```
**Status:** ✅ WORKING

### Staff Login Flow
```
staff-login.html
    ↓
staff-login.php
    ├─ Check default admin (ADMIN/admin123)
    ├─ Query staff table
    ├─ Verify password (bcrypt)
    ├─ Create session (staff_id, staff_name, staff_role, staff_phone)
    └─ Redirect to staff-dashboard.php?role=[admin|staff]
```
**Status:** ✅ WORKING

### Logout Flow
```
Any Dashboard
    ↓
logout.php
    ├─ Destroy session
    ├─ Delete session cookie
    └─ Redirect to landingpage.html
```
**Status:** ✅ WORKING

---

## 2️⃣ PARCEL MANAGEMENT MODULE

### Add Parcel Flow
```
staff-dashboard.php (Add Parcel Modal)
    ↓
staff-add-parcel.php
    ├─ Validate tracking number (unique)
    ├─ Validate receiver exists (matric)
    ├─ Validate delivery location
    ├─ Insert into parcel table
    ├─ Send arrival notification (notification-helper.php)
    └─ Return success JSON
```
**Status:** ✅ WORKING

### View Parcel Details Flow
```
receiver-dashboard.php (Click parcel)
    ↓
get-parcel-with-qr.php
    ├─ Verify receiver owns parcel
    ├─ Fetch parcel data
    ├─ Fetch QR code path
    ├─ Fetch verification data
    └─ Return JSON with all data
```
**Status:** ✅ WORKING

### Update Parcel Flow
```
staff-dashboard.php (Edit button)
    ↓
staff-update-parcel.php OR admin-update-parcel.php
    ├─ Validate tracking number exists
    ├─ Update parcel fields
    └─ Return success JSON
```
**Status:** ✅ WORKING

### Delete Parcel Flow
```
staff-dashboard.php (Delete button - Admin only)
    ↓
admin-delete-parcel.php
    ├─ Verify admin role
    ├─ Delete from parcel table
    ├─ Delete QR file
    └─ Return success JSON
```
**Status:** ✅ WORKING

---

## 3️⃣ QR CODE MODULE

### Generate QR Flow
```
staff-dashboard.php (Generate QR button)
    ↓
Frontend: qr-config.js
    ├─ Generate QR code (250x250px, black/white)
    ├─ Convert to base64 PNG
    └─ Send to backend
    ↓
admin-save-qr.php
    ├─ Verify staff role
    ├─ Generate HMAC-SHA256 signature
    ├─ Create verification data (JSON)
    ├─ Save PNG to assets/qr-codes/
    ├─ Update parcel table (QR path + verification data)
    └─ Return success JSON
```
**Status:** ✅ WORKING

### Retrieve QR Flow
```
receiver-dashboard.php (View Details)
    ↓
get-parcel-with-qr.php
    ├─ Fetch QR path
    ├─ Fetch verification data
    └─ Return JSON
    ↓
Frontend: qr-config.js
    ├─ Generate QR from verification data
    ├─ Display in modal
    └─ Allow download/print
```
**Status:** ✅ WORKING

### Verify QR Flow
```
staff-dashboard.php (Scan QR)
    ↓
verify-qr-scan.php
    ├─ Parse QR data
    ├─ Verify HMAC-SHA256 signature
    ├─ Check timestamp (30-day expiry)
    ├─ Verify matric number
    ├─ Fetch parcel details
    ├─ Generate verification token
    └─ Return success JSON
```
**Status:** ✅ WORKING (FIXED db connection)

---

## 4️⃣ PARCEL RETRIEVAL MODULE

### Retrieval Flow
```
staff-dashboard.php (After QR verification)
    ↓
process-parcel-retrieval.php
    ├─ Verify staff role
    ├─ Verify parcel exists
    ├─ Update parcel status to "Retrieved"
    ├─ Create retrieval record
    ├─ Send retrieval notification
    └─ Return success JSON
```
**Status:** ✅ WORKING (FIXED db connection)

---

## 5️⃣ NOTIFICATION MODULE

### Arrival Notification
```
staff-add-parcel.php
    ↓
notification-helper.php (sendParcelArrivalNotification)
    ├─ Create message
    ├─ Insert into notification table
    └─ Return success
```
**Status:** ✅ WORKING

### Ready Notification
```
admin-save-qr.php
    ↓
notification-helper.php (sendParcelReadyNotification)
    ├─ Create message
    ├─ Insert into notification table
    └─ Return success
```
**Status:** ✅ WORKING

### Email Notification
```
staff-dashboard.php (Send QR Email)
    ↓
send-qr-email.php
    ├─ Validate email format
    ├─ Fetch parcel details
    ├─ Create HTML email with QR
    ├─ Send via simple-email-sender.php
    │   ├─ Try SMTP
    │   ├─ Try PHP mail()
    │   └─ Fallback to web service
    └─ Return success JSON
```
**Status:** ✅ WORKING

---

## 6️⃣ FORGOT PASSWORD MODULE

### Reset Flow
```
forgot-password.html
    ↓
forgot-password.php
    ├─ Rate limit (5 attempts/hour)
    ├─ Validate matric format
    ├─ Check receiver exists
    ├─ Generate reset token (32 bytes)
    ├─ Set 1-hour expiry
    ├─ Insert into password_reset_tokens
    └─ Return success (doesn't reveal if user exists)
```
**Status:** ✅ WORKING

---

## 7️⃣ DASHBOARD MODULES

### Receiver Dashboard
```
receiver-dashboard.php
    ├─ Load parcels (receiver-get-history.php)
    ├─ Load stats (receiver-get-stats.php)
    ├─ Load notifications
    ├─ Display parcel list
    ├─ Show QR codes
    └─ Track parcels
```
**Status:** ✅ WORKING

### Staff Dashboard
```
staff-dashboard.php
    ├─ Load parcels (staff-get-parcels.php OR admin-get-parcels.php)
    ├─ Load stats (admin-get-stats.php)
    ├─ Add parcel
    ├─ Edit parcel
    ├─ Delete parcel (admin only)
    ├─ Generate QR
    ├─ Verify QR
    ├─ Mark retrieved
    └─ Send email
```
**Status:** ✅ WORKING

---

## 📊 OVERALL SYSTEM STATUS

| Component | Status | Issues |
|-----------|--------|--------|
| Authentication | ✅ | None |
| Parcel Management | ✅ | None |
| QR Generation | ✅ | FIXED |
| QR Verification | ✅ | FIXED |
| Retrieval | ✅ | FIXED |
| Notifications | ✅ | None |
| Email | ✅ | None |
| Forgot Password | ✅ | None |
| Dashboards | ✅ | None |

**SYSTEM STATUS: ✅ FULLY OPERATIONAL**

