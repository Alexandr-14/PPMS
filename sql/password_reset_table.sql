-- Password Reset Table for PPMS
-- This table stores secure tokens for password reset functionality

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('staff', 'receiver') NOT NULL,
    user_id VARCHAR(20) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_user_type_id (user_type, user_id),
    INDEX idx_expires_at (expires_at)
);

-- Clean up expired tokens (run this periodically)
-- DELETE FROM password_reset_tokens WHERE expires_at < NOW() OR used = 1;
