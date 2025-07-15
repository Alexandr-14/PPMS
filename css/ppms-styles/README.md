# PPMS Custom Styles

This folder contains all custom CSS styles for the Perwira Parcel Management System (PPMS).

## 📁 Folder Structure

```
ppms-styles/
├── shared/
│   ├── variables.css      # Design system variables (colors, spacing, typography)
│   └── components.css     # Reusable UI components (buttons, cards, forms, tables)
├── receiver/
│   └── receiver-dashboard.css  # Receiver dashboard specific styles
├── staff/
│   └── staff-dashboard.css     # Staff dashboard specific styles
├── auth/
│   ├── login.css          # Login page styles
│   └── register.css       # Register page styles
├── landing.css            # Landing page styles
└── README.md             # This file
```

## 🎨 Design System

### Variables (variables.css)
Contains all design tokens:
- **Colors**: Primary, secondary, success, warning, danger gradients
- **Typography**: Font families, sizes, weights, line heights
- **Spacing**: Consistent spacing scale (xs, sm, md, lg, xl, 2xl, 3xl, 4xl)
- **Shadows**: Card shadows, hover effects
- **Border Radius**: Consistent border radius scale
- **Transitions**: Smooth animations and transitions

### Components (components.css)
Reusable UI components:
- **Buttons**: Primary, secondary, outline variants
- **Cards**: Modern cards with hover effects
- **Forms**: Input fields, labels, focus states
- **Tables**: Enhanced tables with hover effects
- **Badges**: Status badges with gradients
- **Alerts**: Warning, danger, info, success alerts
- **Dropdowns**: Enhanced dropdown menus

## 🔧 How to Use

### For New Pages
1. Always include the shared files first:
   ```html
   <link rel="stylesheet" href="../css/ppms-styles/shared/variables.css">
   <link rel="stylesheet" href="../css/ppms-styles/shared/components.css">
   ```

2. Then include page-specific CSS:
   ```html
   <link rel="stylesheet" href="../css/ppms-styles/receiver/receiver-dashboard.css">
   ```

### For Customization
- **Global changes**: Edit `shared/variables.css`
- **Component changes**: Edit `shared/components.css`
- **Page-specific changes**: Edit the respective page CSS file

## 🎯 Benefits

1. **Easy Maintenance**: Clear separation of concerns
2. **Consistent Design**: Shared variables ensure consistency
3. **Scalable**: Easy to add new pages and components
4. **Professional**: Industry-standard CSS architecture
5. **Supervisor Friendly**: Well-organized and documented

## 📝 Notes for Supervisor

- All custom styles are in this `ppms-styles` folder
- Bootstrap CSS remains untouched in the main `css` folder
- Each page has its own CSS file for easy debugging
- Design system variables make global changes easy
- All files are well-commented and organized

## 🚀 Future Development

When adding new pages:
1. Create a new CSS file in the appropriate folder
2. Include shared variables and components
3. Add page-specific styles
4. Update this README if needed

---

**Created by**: PPMS Development Team  
**Last Updated**: 2025-01-19  
**Version**: 1.0
