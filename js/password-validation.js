/* ===================================
   PPMS Password Validation JavaScript
   Real-time password strength checking
   ================================== */

class PasswordValidator {
    constructor(passwordFieldId, confirmPasswordFieldId = null) {
        this.passwordField = document.getElementById(passwordFieldId);
        this.confirmPasswordField = confirmPasswordFieldId ? document.getElementById(confirmPasswordFieldId) : null;
        this.requirements = {
            length: false,
            uppercase: false,
            lowercase: false,
            number: false,
            special: false
        };
        
        this.init();
    }
    
    init() {
        if (!this.passwordField) return;
        
        // Add event listeners
        this.passwordField.addEventListener('input', () => this.validatePassword());
        this.passwordField.addEventListener('focus', () => this.showRequirements());
        this.passwordField.addEventListener('blur', () => this.hideRequirements());

        if (this.confirmPasswordField) {
            this.confirmPasswordField.addEventListener('input', () => this.validatePasswordMatch());
        }

        // Hide requirements when clicking outside
        document.addEventListener('click', (e) => {
            const passwordContainer = this.passwordField.closest('.password-field-container');
            if (passwordContainer && !passwordContainer.contains(e.target)) {
                this.hideRequirements();
            }
        });
        
        // Add password toggle functionality
        this.addPasswordToggle();
    }
    
    validatePassword() {
        const password = this.passwordField.value;

        // Check each requirement
        this.requirements.length = password.length >= 12;
        this.requirements.uppercase = /[A-Z]/.test(password);
        this.requirements.lowercase = /[a-z]/.test(password);
        this.requirements.number = /[0-9]/.test(password);
        this.requirements.special = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);

        console.log('Password requirements:', this.requirements); // Debug

        // Update UI
        this.updateRequirementsList();
        this.updateStrengthMeter(password);
        this.updateFieldValidation();

        // Validate confirm password if it exists
        if (this.confirmPasswordField && this.confirmPasswordField.value) {
            this.validatePasswordMatch();
        }
    }
    
    updateRequirementsList() {
        const passwordContainer = this.passwordField.closest('.password-field-container');
        if (!passwordContainer) {
            console.log('No password container found for requirements update'); // Debug
            return;
        }

        const requirements = passwordContainer.querySelectorAll('.requirement');
        console.log('Found requirements:', requirements.length); // Debug

        requirements.forEach(req => {
            const type = req.getAttribute('data-requirement');
            const icon = req.querySelector('.requirement-icon');

            console.log(`Updating ${type}:`, this.requirements[type]); // Debug

            if (this.requirements[type]) {
                req.classList.add('valid');
                req.classList.remove('invalid');
                if (icon) {
                    icon.classList.add('valid');
                    icon.classList.remove('invalid');
                }
            } else {
                req.classList.add('invalid');
                req.classList.remove('valid');
                if (icon) {
                    icon.classList.add('invalid');
                    icon.classList.remove('valid');
                }
            }
        });
    }
    
    updateStrengthMeter(password) {
        const passwordContainer = this.passwordField.closest('.password-field-container');
        if (!passwordContainer) {
            console.log('No password container found for strength meter'); // Debug
            return;
        }

        const strengthFillId = this.strengthFillId || 'strengthFill';
        const strengthTextId = this.strengthTextId || 'strengthText';

        let strengthFill = document.getElementById(strengthFillId);
        let strengthText = document.getElementById(strengthTextId);

        // Fallback to container search if IDs not found
        if (!strengthFill) {
            strengthFill = passwordContainer.querySelector('.strength-fill');
        }
        if (!strengthText) {
            strengthText = passwordContainer.querySelector('.strength-text');
        }

        console.log('Strength elements:', strengthFill, strengthText); // Debug

        if (!strengthFill || !strengthText) {
            console.log('Strength meter elements not found'); // Debug
            return;
        }

        const strength = this.calculateStrength(password);
        console.log('Password strength:', strength); // Debug

        // Remove all strength classes safely
        strengthFill.className = 'strength-fill';
        strengthText.className = 'strength-text';

        // Add current strength class
        if (strength.level) {
            strengthFill.classList.add(strength.level);
            strengthText.classList.add(strength.level);
        }
        strengthText.textContent = strength.text;
    }
    
    calculateStrength(password) {
        if (!password) {
            return { level: '', text: 'Enter password to see strength' };
        }
        
        let score = 0;
        
        // Length scoring
        if (password.length >= 12) score += 2;
        else if (password.length >= 8) score += 1;
        
        // Character type scoring
        if (/[A-Z]/.test(password)) score += 1;
        if (/[a-z]/.test(password)) score += 1;
        if (/[0-9]/.test(password)) score += 1;
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) score += 1;
        
        // Additional complexity
        if (/[A-Z].*[A-Z]/.test(password)) score += 1;
        if (/[0-9].*[0-9]/.test(password)) score += 1;
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?].*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) score += 1;
        
        if (score >= 8) return { level: 'very-strong', text: 'Very Strong Password' };
        if (score >= 6) return { level: 'strong', text: 'Strong Password' };
        if (score >= 4) return { level: 'medium', text: 'Medium Strength' };
        if (score >= 2) return { level: 'weak', text: 'Weak Password' };
        return { level: 'very-weak', text: 'Very Weak Password' };
    }
    
    validatePasswordMatch() {
        if (!this.confirmPasswordField) return;

        const password = this.passwordField.value;
        const confirmPassword = this.confirmPasswordField.value;
        const confirmContainer = this.confirmPasswordField.closest('.password-field-container') || this.confirmPasswordField.parentElement;
        const indicatorId = this.matchIndicatorId || null;
        let indicator = indicatorId ? document.getElementById(indicatorId) : confirmContainer.querySelector('.password-match-indicator');
        
        if (!confirmPassword) {
            if (indicator) {
                indicator.style.display = 'none';
            }
            this.confirmPasswordField.classList.remove('is-valid', 'is-invalid');
            return;
        }
        
        if (password === confirmPassword) {
            if (indicator) {
                indicator.className = 'password-match-indicator match';
                indicator.innerHTML = '<i class="fas fa-check me-1"></i>Passwords match';
            }
            this.confirmPasswordField.classList.add('is-valid');
            this.confirmPasswordField.classList.remove('is-invalid');
        } else {
            if (indicator) {
                indicator.className = 'password-match-indicator no-match';
                indicator.innerHTML = '<i class="fas fa-times me-1"></i>Passwords do not match';
            }
            this.confirmPasswordField.classList.add('is-invalid');
            this.confirmPasswordField.classList.remove('is-valid');
        }
    }
    
    updateFieldValidation() {
        const allValid = Object.values(this.requirements).every(req => req);
        
        if (this.passwordField.value.length > 0) {
            if (allValid) {
                this.passwordField.classList.add('is-valid');
                this.passwordField.classList.remove('is-invalid');
            } else {
                this.passwordField.classList.add('is-invalid');
                this.passwordField.classList.remove('is-valid');
            }
        } else {
            this.passwordField.classList.remove('is-valid', 'is-invalid');
        }
    }
    
    showRequirements() {
        const passwordContainer = this.passwordField.closest('.password-field-container');
        if (!passwordContainer) {
            console.log('No password container found'); // Debug
            return;
        }

        const requirements = passwordContainer.querySelector('.password-requirements');
        console.log('Show requirements:', passwordContainer, requirements); // Debug
        if (requirements) {
            requirements.classList.add('show');
            console.log('Added show class'); // Debug
        } else {
            console.log('No requirements element found'); // Debug
        }
    }

    hideRequirements() {
        const passwordContainer = this.passwordField.closest('.password-field-container');
        if (!passwordContainer) return;

        const requirements = passwordContainer.querySelector('.password-requirements');
        if (requirements) {
            setTimeout(() => {
                if (document.activeElement !== this.passwordField) {
                    requirements.classList.remove('show');
                }
            }, 150);
        }
    }
    
    addPasswordToggle() {
        const container = this.passwordField.parentElement;
        if (!container) {
            console.log('No parent container found for password field'); // Debug
            return;
        }

        if (!container.classList.contains('password-field-container')) {
            container.classList.add('password-field-container');
        }

        // Check if toggle already exists
        if (container.querySelector('.password-toggle')) {
            console.log('Password toggle already exists'); // Debug
            return;
        }

        const toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.className = 'password-toggle';
        toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
        toggleButton.setAttribute('aria-label', 'Toggle password visibility');

        toggleButton.addEventListener('click', () => {
            const type = this.passwordField.getAttribute('type');
            const icon = toggleButton.querySelector('i');

            if (type === 'password') {
                this.passwordField.setAttribute('type', 'text');
                icon.className = 'fas fa-eye-slash';
            } else {
                this.passwordField.setAttribute('type', 'password');
                icon.className = 'fas fa-eye';
            }
        });

        container.appendChild(toggleButton);
        console.log('Password toggle added'); // Debug

        // Add toggle for confirm password if it exists
        if (this.confirmPasswordField) {
            this.addConfirmPasswordToggle();
        }
    }
    
    addConfirmPasswordToggle() {
        const container = this.confirmPasswordField.parentElement;
        if (!container) {
            console.log('No parent container found for confirm password field'); // Debug
            return;
        }

        if (!container.classList.contains('password-field-container')) {
            container.classList.add('password-field-container');
        }

        // Check if toggle already exists
        if (container.querySelector('.password-toggle')) {
            console.log('Confirm password toggle already exists'); // Debug
            return;
        }

        const toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.className = 'password-toggle';
        toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
        toggleButton.setAttribute('aria-label', 'Toggle confirm password visibility');

        toggleButton.addEventListener('click', () => {
            const type = this.confirmPasswordField.getAttribute('type');
            const icon = toggleButton.querySelector('i');

            if (type === 'password') {
                this.confirmPasswordField.setAttribute('type', 'text');
                icon.className = 'fas fa-eye-slash';
            } else {
                this.confirmPasswordField.setAttribute('type', 'password');
                icon.className = 'fas fa-eye';
            }
        });

        container.appendChild(toggleButton);
        console.log('Confirm password toggle added'); // Debug
    }
    
    isValid() {
        const passwordValid = Object.values(this.requirements).every(req => req);
        const matchValid = this.confirmPasswordField ? 
            this.passwordField.value === this.confirmPasswordField.value : true;
        
        return passwordValid && matchValid;
    }
}

// Utility function to initialize password validation
function initPasswordValidation(passwordFieldId, confirmPasswordFieldId = null) {
    return new PasswordValidator(passwordFieldId, confirmPasswordFieldId);
}
