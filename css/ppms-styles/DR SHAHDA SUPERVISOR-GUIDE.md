# 👨‍💼 SUPERVISOR GUIDE - PPMS Custom Styles

## 🎯 Quick Overview

All **custom CSS styles** for the PPMS system are organized in this `ppms-styles` folder. Bootstrap CSS remains untouched in the main `css` folder.

## 📁 What's Where?

### 🎨 **Design System** (Most Important)
- **`shared/variables.css`** - All colors, spacing, fonts (EDIT THIS for global changes)
- **`shared/components.css`** - Buttons, cards, forms, tables

### 📄 **Page-Specific Styles**
- **`receiver/receiver-dashboard.css`** - Receiver dashboard only
- **`staff/staff-dashboard.css`** - Staff dashboard only  
- **`auth/login.css`** - Login pages
- **`auth/register.css`** - Registration pages
- **`landing.css`** - Landing page

## 🔧 How to Make Changes

### ✅ **Global Changes** (Colors, Fonts, Spacing)
**Edit:** `shared/variables.css`

**Example:** Change primary color
```css
--primary-gradient: linear-gradient(135deg, #YOUR_COLOR 0%, #YOUR_COLOR2 100%);
```

### ✅ **Component Changes** (Buttons, Cards, Forms)
**Edit:** `shared/components.css`

### ✅ **Page-Specific Changes**
**Edit:** The specific page CSS file
- Receiver dashboard issues → `receiver/receiver-dashboard.css`
- Staff dashboard issues → `staff/staff-dashboard.css`
- Login issues → `auth/login.css`

## 🎨 Current Design System

### **Colors**
- **Primary:** Purple gradient (#667eea to #764ba2)
- **Secondary:** Pink gradient (#f093fb to #f5576c)  
- **Success:** Blue gradient (#4facfe to #00f2fe)
- **Warning:** Green gradient (#43e97b to #38f9d7)

### **Typography**
- **Primary Font:** Inter
- **Secondary Font:** Poppins

### **Spacing Scale**
- xs: 0.25rem, sm: 0.5rem, md: 1rem, lg: 1.5rem, xl: 2rem, 2xl: 2.5rem, 3xl: 3rem, 4xl: 4rem

## 🚨 Important Notes

1. **Don't edit Bootstrap files** - Only edit files in `ppms-styles` folder
2. **Test changes** - Always test on both desktop and mobile
3. **Use variables** - Use CSS variables instead of hardcoded values
4. **Ask for help** - Contact development team if unsure

---

**This guide was created to help Dr Shahda make quick style adjustments without breaking the system.**
