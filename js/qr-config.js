/**
 * PPMS QR Code Configuration
 * Shared settings for consistent QR code generation across the system
 * 
 * These settings ensure:
 * - Proper quiet zone (margin) for scanning
 * - Maximum contrast for reliability
 * - Consistent size across all dashboards
 * - High error correction for damaged codes
 */

const PPMS_QR_CONFIG = {
    /**
     * QR Code Size
     * - 250x250 pixels is large enough for reliable scanning
     * - Consistent across all dashboards
     */
    width: 250,
    height: 250,

    /**
     * Quiet Zone (Margin)
     * - Minimum 4 modules per QR code specification
     * - Prevents scanning errors when code is near other graphics
     * - 4 pixels = 1 module at this resolution
     */
    margin: 4,

    /**
     * Color Settings
     * - Black (#000000) on white (#FFFFFF) = maximum contrast
     * - Ensures scanning works with any phone camera
     * - Professional appearance
     */
    color: {
        dark: '#000000',      // Black (maximum contrast)
        light: '#FFFFFF'      // White (standard background)
    },

    /**
     * Error Correction Level
     * - 'H' = High (can recover if 30% of code is damaged)
     * - Ensures scanning works even if code is partially obscured
     * - Best choice for printed or displayed codes
     */
    errorCorrectionLevel: 'H'
};

/**
 * Generate QR Code with PPMS Standard Settings
 * 
 * @param {HTMLElement} container - DOM element to render QR code into
 * @param {string} data - Data to encode in QR code (usually JSON string)
 * @param {Function} callback - Callback function(error) after generation
 * @param {Object} customOptions - Optional custom options to override defaults
 */
function generatePPMSQRCode(container, data, callback, customOptions = {}) {
    try {
        // Check if QRCode library is available
        if (typeof QRCode === 'undefined') {
            console.error('QRCode library not available');
            if (callback) callback(new Error('QRCode library not available'));
            return;
        }

        // Merge custom options with defaults
        const options = {
            ...PPMS_QR_CONFIG,
            ...customOptions
        };

        // Clear container
        container.innerHTML = '';

        // Generate QR code
        QRCode.toCanvas(container, data, options, function(error) {
            if (error) {
                console.error('QR Code generation error:', error);
                if (callback) callback(error);
            } else {
                console.log('✅ QR Code generated successfully with PPMS settings');
                if (callback) callback(null);
            }
        });
    } catch (error) {
        console.error('QR Code generation exception:', error);
        if (callback) callback(error);
    }
}

/**
 * Get QR Code Configuration
 * Useful for debugging or displaying current settings
 * 
 * @returns {Object} Current QR code configuration
 */
function getPPMSQRConfig() {
    return { ...PPMS_QR_CONFIG };
}

/**
 * Validate QR Code Configuration
 * Checks if settings meet QR code specification requirements
 * 
 * @returns {Object} Validation result with status and messages
 */
function validatePPMSQRConfig() {
    const issues = [];

    // Check margin
    if (PPMS_QR_CONFIG.margin < 4) {
        issues.push('⚠️ Margin too small (minimum 4 required)');
    }

    // Check size
    if (PPMS_QR_CONFIG.width < 200 || PPMS_QR_CONFIG.height < 200) {
        issues.push('⚠️ Size too small (minimum 200x200 recommended)');
    }

    // Check colors
    if (PPMS_QR_CONFIG.color.dark === PPMS_QR_CONFIG.color.light) {
        issues.push('❌ Dark and light colors are the same');
    }

    // Check error correction
    if (!['L', 'M', 'Q', 'H'].includes(PPMS_QR_CONFIG.errorCorrectionLevel)) {
        issues.push('❌ Invalid error correction level');
    }

    return {
        valid: issues.length === 0,
        issues: issues,
        config: PPMS_QR_CONFIG
    };
}

// Log configuration on load
console.log('📋 PPMS QR Code Configuration Loaded');
console.log('Settings:', PPMS_QR_CONFIG);
console.log('Validation:', validatePPMSQRConfig());

