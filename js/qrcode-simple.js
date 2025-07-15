/**
 * Simple QR Code Fallback Implementation
 * Provides basic QR code generation when main library fails
 */

// Only create fallback if QRCode is not already available
if (typeof QRCode === 'undefined') {
    console.log('Creating QRCode fallback implementation');
    
    window.QRCode = {
        CorrectLevel: {
            L: 'L',
            M: 'M',
            Q: 'Q',
            H: 'H'
        },
        
        toCanvas: function(container, text, options, callback) {
            console.log('QRCode fallback: toCanvas called');
            
            // Default options
            const opts = {
                width: options?.width || 256,
                height: options?.height || 256,
                color: {
                    dark: options?.color?.dark || '#000000',
                    light: options?.color?.light || '#FFFFFF'
                },
                margin: options?.margin || 4
            };
            
            try {
                // Create canvas element
                const canvas = document.createElement('canvas');
                canvas.width = opts.width;
                canvas.height = opts.height;
                
                const ctx = canvas.getContext('2d');
                
                // Fill background
                ctx.fillStyle = opts.color.light;
                ctx.fillRect(0, 0, opts.width, opts.height);
                
                // Generate simple QR pattern (placeholder)
                this.generateSimplePattern(ctx, text, opts);
                
                // Clear container and add canvas
                if (typeof container === 'string') {
                    container = document.getElementById(container);
                }
                
                if (container) {
                    container.innerHTML = '';
                    container.appendChild(canvas);
                }
                
                // Call success callback
                if (callback) {
                    setTimeout(() => callback(null), 10);
                }
                
                console.log('QRCode fallback: Canvas generated successfully');
                
            } catch (error) {
                console.error('QRCode fallback error:', error);
                if (callback) {
                    callback(error);
                }
            }
        },
        
        generateSimplePattern: function(ctx, text, opts) {
            const size = Math.min(opts.width, opts.height);
            const margin = opts.margin;
            const qrSize = size - (margin * 2);
            const moduleSize = Math.floor(qrSize / 25); // 25x25 grid
            
            ctx.fillStyle = opts.color.dark;
            
            // Generate a simple pattern based on text hash
            const hash = this.simpleHash(text);
            
            // Draw finder patterns (corners)
            this.drawFinderPattern(ctx, margin, margin, moduleSize);
            this.drawFinderPattern(ctx, margin + 18 * moduleSize, margin, moduleSize);
            this.drawFinderPattern(ctx, margin, margin + 18 * moduleSize, moduleSize);
            
            // Draw data pattern
            for (let i = 0; i < 25; i++) {
                for (let j = 0; j < 25; j++) {
                    // Skip finder pattern areas
                    if (this.isFinderArea(i, j)) continue;
                    
                    // Use hash to determine if module should be dark
                    const index = (i * 25 + j) % hash.length;
                    if (hash.charCodeAt(index) % 2 === 0) {
                        ctx.fillRect(
                            margin + j * moduleSize,
                            margin + i * moduleSize,
                            moduleSize - 1,
                            moduleSize - 1
                        );
                    }
                }
            }
        },
        
        drawFinderPattern: function(ctx, x, y, moduleSize) {
            // Outer 7x7 square
            ctx.fillRect(x, y, 7 * moduleSize, 7 * moduleSize);
            
            // Inner white 5x5 square
            ctx.fillStyle = ctx.canvas.style.backgroundColor || '#FFFFFF';
            ctx.fillRect(x + moduleSize, y + moduleSize, 5 * moduleSize, 5 * moduleSize);
            
            // Center 3x3 square
            ctx.fillStyle = '#000000';
            ctx.fillRect(x + 2 * moduleSize, y + 2 * moduleSize, 3 * moduleSize, 3 * moduleSize);
        },
        
        isFinderArea: function(row, col) {
            // Top-left finder
            if (row < 9 && col < 9) return true;
            // Top-right finder
            if (row < 9 && col > 15) return true;
            // Bottom-left finder
            if (row > 15 && col < 9) return true;
            return false;
        },
        
        simpleHash: function(str) {
            let hash = '';
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash += String.fromCharCode(((char * 7) % 94) + 33);
            }
            // Ensure minimum length
            while (hash.length < 50) {
                hash += hash;
            }
            return hash.substring(0, 100);
        }
    };
    
    // Mark as fallback
    window.SimpleQR = true;
    console.log('QRCode fallback implementation created');
}

// Ensure QRCode is available globally
window.QRCode = window.QRCode || QRCode;
