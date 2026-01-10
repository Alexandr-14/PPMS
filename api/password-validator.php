<?php
/* ===================================
   PPMS Password Validation Utility
   Strong password requirements implementation
   ================================== */

/**
 * Validate password strength
 * Requirements:
 * - At least 12 characters
 * - At least 1 uppercase letter
 * - At least 1 lowercase letter
 * - At least 1 number
 * - At least 1 special character
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    // Check minimum length
    if (strlen($password) < 12) {
        $errors[] = "Password must be at least 12 characters long";
    }
    
    // Check for uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    // Check for lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    // Check for number
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    // Check for special character
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        $errors[] = "Password must contain at least one special character (!@#$%^&*()_+-=[]{}|;':\",./<>?)";
    }
    
    return $errors;
}

/**
 * Check if passwords match
 */
function validatePasswordMatch($password, $confirmPassword) {
    if ($password !== $confirmPassword) {
        return ["Passwords do not match"];
    }
    return [];
}

/**
 * Get password strength level
 */
function getPasswordStrength($password) {
    $score = 0;
    
    // Length scoring
    if (strlen($password) >= 12) $score += 2;
    else if (strlen($password) >= 8) $score += 1;
    
    // Character type scoring
    if (preg_match('/[A-Z]/', $password)) $score += 1;
    if (preg_match('/[a-z]/', $password)) $score += 1;
    if (preg_match('/[0-9]/', $password)) $score += 1;
    if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) $score += 1;
    
    // Additional complexity
    if (preg_match('/[A-Z].*[A-Z]/', $password)) $score += 1; // Multiple uppercase
    if (preg_match('/[0-9].*[0-9]/', $password)) $score += 1; // Multiple numbers
    if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?].*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) $score += 1; // Multiple special chars
    
    if ($score >= 8) return 'very-strong';
    if ($score >= 6) return 'strong';
    if ($score >= 4) return 'medium';
    if ($score >= 2) return 'weak';
    return 'very-weak';
}

/**
 * Generate password requirements HTML
 */
function getPasswordRequirementsHTML() {
    return '
    <div class="password-requirements mt-2">
        <small class="text-muted">Password must contain:</small>
        <ul class="password-checklist mt-1">
            <li class="requirement" data-requirement="length">
                <i class="fas fa-times text-danger me-1"></i>
                At least 12 characters
            </li>
            <li class="requirement" data-requirement="uppercase">
                <i class="fas fa-times text-danger me-1"></i>
                One uppercase letter (A-Z)
            </li>
            <li class="requirement" data-requirement="lowercase">
                <i class="fas fa-times text-danger me-1"></i>
                One lowercase letter (a-z)
            </li>
            <li class="requirement" data-requirement="number">
                <i class="fas fa-times text-danger me-1"></i>
                One number (0-9)
            </li>
            <li class="requirement" data-requirement="special">
                <i class="fas fa-times text-danger me-1"></i>
                One special character (!@#$%^&*)
            </li>
        </ul>
    </div>';
}

/**
 * Generate password strength meter HTML
 */
function getPasswordStrengthMeterHTML() {
    return '
    <div class="password-strength-meter mt-2">
        <div class="strength-bar">
            <div class="strength-fill" id="strengthFill"></div>
        </div>
        <small class="strength-text" id="strengthText">Enter password to see strength</small>
    </div>';
}
?>

