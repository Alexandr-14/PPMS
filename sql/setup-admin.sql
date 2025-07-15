-- Setup script for PPMS Admin user
-- This script creates the default admin user for the system

-- Note: The admin login is handled separately in the PHP code
-- Admin credentials: 
-- Staff ID: ADMIN
-- Password: admin123

-- This is just for reference. The actual admin authentication is hardcoded in staff-login.php
-- for security reasons, as there should only be one admin account.

-- If you want to create additional staff members with admin role in the database:
-- INSERT INTO Staff (staffID, role, name, phoneNumber, password) 
-- VALUES ('ADMIN001', 'Admin', 'System Administrator', '+60111234567', '$2y$10$example_hashed_password');

-- Sample staff data for testing:
INSERT INTO Staff (staffID, role, name, phoneNumber, password) VALUES 
('STF001', 'Staff', 'John Doe', '+60123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('STF002', 'Staff', 'Jane Smith', '+60987654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Note: The password hash above is for 'password' - change this in production!

-- Sample retrieval data for testing admin reports:
INSERT INTO Retrieval (trackingNumber, ICNumber, staffID, retrieveDate, retrieveTime, status) VALUES 
('TRK001', '123456789012', 'STF001', '2024-01-15', '10:30:00', 'Retrieved'),
('TRK002', '123456789013', 'STF002', '2024-01-15', '14:20:00', 'Retrieved'),
('TRK003', '123456789014', NULL, NULL, NULL, 'Pending'),
('TRK004', '123456789015', 'STF001', '2024-01-16', '09:15:00', 'Retrieved'),
('TRK005', '123456789016', NULL, NULL, NULL, 'Pending');
