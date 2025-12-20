# 🚀 Deployment Guide

**Last Updated:** 2025-12-20  
**Status:** ✅ Ready for Production  
**Commit:** `385c48c`

---

## 📋 Pre-Deployment Checklist

- [x] All changes committed to GitHub
- [x] Code tested and verified
- [x] Documentation organized
- [x] Security vulnerabilities fixed
- [x] Mobile responsiveness verified

---

## 📁 Step 1: Backup Current Files

```bash
# Backup entire webserver directory
cp -r /public_html /public_html.backup.2025-12-20

# Backup database
mysqldump -u username -p database_name > database_backup.sql
```

---

## 📤 Step 2: Upload New Files

Upload to `/public_html/`:

### CSS Files
```
css/ppms-styles/auth/login.css
css/ppms-styles/landing.css
css/ppms-styles/receiver/receiver-dashboard.css
css/ppms-styles/receiver/receiver-navbar-buttons.css
css/ppms-styles/receiver/receiver-notifications.css
css/ppms-styles/shared/mobile-responsive.css (NEW)
css/ppms-styles/staff/staff-dashboard-refined.css
```

### HTML Files
```
html/landingpage.html
html/receiver-dashboard.php
html/receiver-login.html
html/receiver-register.html
html/staff-dashboard.php
html/staff-login.html
html/staff-register.html
```

### PHP Files
```
php/ (all modified PHP files)
```

---

## 🗑️ Step 3: Delete Old Files

Delete from `/public_html/php/`:
```
send-qr-email.php
simple-email-sender.php
smtp-config.php
generate-qr-code.php
get-parcel-details.php
reset-password.php
```

---

## 🧹 Step 4: Clear Cache

```bash
# Clear browser cache (user side)
# Clear server cache
rm -rf /var/www/cache/*

# Clear CDN cache (if applicable)
# Contact CDN provider or use their dashboard
```

---

## 🧪 Step 5: Testing

### Functionality Tests
- [ ] Login pages work (staff & receiver)
- [ ] Registration pages work
- [ ] Parcel management (add, view, update, delete)
- [ ] QR code generation
- [ ] QR code verification
- [ ] Parcel tracking
- [ ] Notifications
- [ ] Reports generation
- [ ] Dashboard stats

### Mobile Tests
- [ ] Test on iPhone SE (375px)
- [ ] Test on iPhone 12 (390px)
- [ ] Test on Samsung Galaxy S21 (360px)
- [ ] Carousel swipe works
- [ ] Carousel arrows work
- [ ] Pagination dots work
- [ ] Forms responsive
- [ ] No horizontal scroll

### Browser Tests
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile browsers

### Console Tests
- [ ] No JavaScript errors
- [ ] No CSS errors
- [ ] No PHP errors
- [ ] No database errors

---

## ✅ Step 6: Verification

```bash
# Check file permissions
ls -la /public_html/

# Check database connection
# Test login functionality
# Verify QR generation
# Check notifications
```

---

## 🔍 Step 7: Monitor

- Monitor error logs
- Check user feedback
- Monitor performance
- Check for issues
- Gather analytics

---

## 🆘 Troubleshooting

### Issue: Carousel not working
- Clear browser cache
- Check CSS file uploaded
- Verify JavaScript loaded
- Check browser console

### Issue: Mobile layout broken
- Verify mobile-responsive.css uploaded
- Check CSS file size
- Clear cache
- Test on different devices

### Issue: Database errors
- Verify db_connect.php
- Check database credentials
- Verify database connection
- Check error logs

### Issue: QR code not generating
- Verify admin-save-qr.php
- Check file permissions
- Verify QR library
- Check error logs

---

## 📞 Rollback Plan

If issues occur:
```bash
# Restore from backup
cp -r /public_html.backup.2025-12-20/* /public_html/

# Restore database
mysql -u username -p database_name < database_backup.sql
```

---

## ✨ Post-Deployment

- [ ] Monitor for 24 hours
- [ ] Check error logs
- [ ] Verify all features
- [ ] Gather user feedback
- [ ] Plan next improvements

---

## 📊 Deployment Checklist

| Task | Status |
|------|--------|
| Backup files | ⬜ |
| Upload CSS | ⬜ |
| Upload HTML | ⬜ |
| Upload PHP | ⬜ |
| Delete old files | ⬜ |
| Clear cache | ⬜ |
| Test functionality | ⬜ |
| Test mobile | ⬜ |
| Test browsers | ⬜ |
| Monitor logs | ⬜ |

---

## 🎯 Success Criteria

✅ All files uploaded  
✅ Old files deleted  
✅ Cache cleared  
✅ All tests passing  
✅ No errors in logs  
✅ Mobile responsive  
✅ All features working  
✅ Users can access system  

