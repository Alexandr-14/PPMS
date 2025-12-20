# 📤 MANUAL UPLOAD CHECKLIST - cPanel File Manager

**For:** Mobile Responsiveness Fix  
**Method:** cPanel File Manager (Manual Upload)  
**Date:** 2025-12-20

---

## 🎯 STEP-BY-STEP UPLOAD GUIDE

### STEP 1: Download Files from GitHub
1. Go to: https://github.com/Alexandr-14/PPMS
2. Click **Code** → **Download ZIP**
3. Extract the ZIP file to your computer

---

### STEP 2: Login to cPanel
1. Go to your cPanel URL (usually `yourdomain.com:2083`)
2. Login with your credentials
3. Find **File Manager** (usually in main menu)
4. Click **File Manager**

---

### STEP 3: Upload CSS Files (MOST IMPORTANT!)

**Navigate to:** `/public_html/css/ppms-styles/shared/`

**Upload this NEW file:**
- ✅ `mobile-responsive.css` ⭐ **CRITICAL FILE**

**Navigate to:** `/public_html/css/ppms-styles/auth/`
- ✅ `login.css`

**Navigate to:** `/public_html/css/ppms-styles/`
- ✅ `landing.css`

**Navigate to:** `/public_html/css/ppms-styles/receiver/`
- ✅ `receiver-dashboard.css`
- ✅ `receiver-navbar-buttons.css`
- ✅ `receiver-notifications.css`

**Navigate to:** `/public_html/css/ppms-styles/staff/`
- ✅ `staff-dashboard-refined.css`

---

### STEP 4: Upload HTML/PHP Files

**Navigate to:** `/public_html/html/`
- ✅ `receiver-dashboard.php`
- ✅ `staff-dashboard.php`
- ✅ `landingpage.html`
- ✅ `receiver-login.html`
- ✅ `receiver-register.html`
- ✅ `staff-login.html`
- ✅ `staff-register.html`

---

### STEP 5: Upload PHP Backend Files

**Navigate to:** `/public_html/php/`
- ✅ `db_connect.php`
- ✅ `admin-save-qr.php`
- ✅ `admin-get-parcels.php`
- ✅ `staff-get-parcels.php`
- ✅ `receiver-get-history.php`

---

### STEP 6: Upload Configuration Files

**Navigate to:** `/public_html/` (root)
- ✅ `.env.example`
- ✅ `.gitignore`
- ✅ `SECURITY_FIX_NOTICE.md`

---

### STEP 7: Create .env File

**In cPanel File Manager:**
1. Navigate to `/public_html/`
2. Right-click → **Create New File**
3. Name it: `.env`
4. Click **Create**
5. Right-click `.env` → **Edit**
6. Copy and paste this content:

```
DB_HOST=localhost
DB_USER=myppmsco_ppms
DB_PASS=your_actual_password_here
DB_NAME=myppmsco_ppms
ADMIN_ID=ADMIN
ADMIN_PASSWORD=admin123
QR_SECRET_KEY=your_secure_key_here
GMAIL_EMAIL=your_email@gmail.com
GMAIL_APP_PASSWORD=your_app_password_here
EMAIL_FROM_NAME=PPMS
```

7. **IMPORTANT:** Replace `your_actual_password_here` with your real database password
8. Click **Save**

---

## ✅ UPLOAD CHECKLIST

### CSS Files (7 files)
- [ ] `css/ppms-styles/shared/mobile-responsive.css` ⭐ **NEW**
- [ ] `css/ppms-styles/auth/login.css`
- [ ] `css/ppms-styles/landing.css`
- [ ] `css/ppms-styles/receiver/receiver-dashboard.css`
- [ ] `css/ppms-styles/receiver/receiver-navbar-buttons.css`
- [ ] `css/ppms-styles/receiver/receiver-notifications.css`
- [ ] `css/ppms-styles/staff/staff-dashboard-refined.css`

### HTML/PHP Files (7 files)
- [ ] `html/receiver-dashboard.php`
- [ ] `html/staff-dashboard.php`
- [ ] `html/landingpage.html`
- [ ] `html/receiver-login.html`
- [ ] `html/receiver-register.html`
- [ ] `html/staff-login.html`
- [ ] `html/staff-register.html`

### PHP Backend (5 files)
- [ ] `php/db_connect.php`
- [ ] `php/admin-save-qr.php`
- [ ] `php/admin-get-parcels.php`
- [ ] `php/staff-get-parcels.php`
- [ ] `php/receiver-get-history.php`

### Configuration (4 files)
- [ ] `.env.example`
- [ ] `.gitignore`
- [ ] `SECURITY_FIX_NOTICE.md`
- [ ] `.env` (created manually)

**TOTAL: 23 files**

---

## 🧪 TESTING AFTER UPLOAD

### Test on Mobile Device (Important!)

1. **Clear Browser Cache:**
   - Open DevTools (F12)
   - Right-click refresh button → **Empty cache and hard refresh**
   - Or: Ctrl+Shift+Delete

2. **Test Mobile Features:**
   - [ ] Navbar is compact (reduced padding)
   - [ ] Logout button is icon-only
   - [ ] Notification dropdown appears above table
   - [ ] Tables have single scrollbar
   - [ ] Carousel works with swipe
   - [ ] Pagination dots visible
   - [ ] Registration pages responsive
   - [ ] Login pages responsive
   - [ ] Database connection works
   - [ ] QR code generation works

3. **Test on Different Devices:**
   - [ ] iPhone (375px width)
   - [ ] Android phone (360px width)
   - [ ] Tablet (768px width)
   - [ ] Desktop (1024px+ width)

---

## 🆘 TROUBLESHOOTING

### Mobile styles not showing?
1. Check `mobile-responsive.css` is in correct folder
2. Clear browser cache (Ctrl+Shift+Delete)
3. Hard refresh (Ctrl+F5)
4. Check file permissions (should be readable)

### Database connection error?
1. Verify `.env` file exists in `/public_html/`
2. Check database password in `.env` is correct
3. Verify `php/db_connect.php` is uploaded
4. Check database credentials are correct

### Carousel not working?
1. Verify `receiver-dashboard.php` is uploaded
2. Verify `staff-dashboard.php` is uploaded
3. Check browser console (F12) for errors
4. Test on different browser

---

## 📝 IMPORTANT NOTES

⚠️ **CRITICAL:**
- The `mobile-responsive.css` file is the MOST IMPORTANT file
- Without it, mobile changes won't appear
- Make sure it's uploaded to the correct path

🔒 **SECURITY:**
- Never share your `.env` file
- Keep `.env` file private (don't upload to GitHub)
- Change database password if exposed

📱 **TESTING:**
- Always test on actual mobile device
- Not just browser resize
- Test on multiple devices

---

## 📞 QUICK REFERENCE

**cPanel File Manager Paths:**
- CSS: `/public_html/css/ppms-styles/`
- HTML: `/public_html/html/`
- PHP: `/public_html/php/`
- Root: `/public_html/`

**File Count:**
- CSS: 7 files
- HTML/PHP: 7 files
- PHP Backend: 5 files
- Config: 4 files
- **Total: 23 files**

---

**Status:** ✅ Ready for manual upload  
**Last Updated:** 2025-12-20  
**Method:** cPanel File Manager

