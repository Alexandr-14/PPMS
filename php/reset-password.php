<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validate input
    if (empty($token) || empty($new_password) || empty($confirm_password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all fields.'
        ]);
        exit;
    }
    
    if ($new_password !== $confirm_password) {
        echo json_encode([
            'success' => false,
            'message' => 'Passwords do not match.'
        ]);
        exit;
    }
    
    // Validate password strength
    if (strlen($new_password) < 12) {
        echo json_encode([
            'success' => false,
            'message' => 'Password must be at least 12 characters long.'
        ]);
        exit;
    }
    
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $new_password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
        ]);
        exit;
    }
    
    // Verify token
    $stmt = $conn->prepare("SELECT user_type, user_id, expires_at, used FROM password_reset_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired reset token.'
        ]);
        exit;
    }
    
    $token_data = $result->fetch_assoc();
    $stmt->close();
    
    // Check if token is expired or already used
    if ($token_data['used'] == 1 || strtotime($token_data['expires_at']) < time()) {
        echo json_encode([
            'success' => false,
            'message' => 'Reset token has expired or already been used.'
        ]);
        exit;
    }
    
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password based on user type
    $update_success = false;
    
    if ($token_data['user_type'] === 'staff') {
        if ($token_data['user_id'] === 'ADMIN') {
            // For admin, we need to handle this specially since it's hardcoded
            // In a real system, you might want to store admin in database too
            echo json_encode([
                'success' => false,
                'message' => 'Admin password reset must be handled by system administrator.'
            ]);
            exit;
        } else {
            // Update staff password
            $stmt = $conn->prepare("UPDATE Staff SET password = ? WHERE staffID = ?");
            $stmt->bind_param("ss", $hashed_password, $token_data['user_id']);
            $update_success = $stmt->execute();
            $stmt->close();
        }
    } else {
        // Update receiver password
        $stmt = $conn->prepare("UPDATE Receiver SET password = ? WHERE ICNumber = ?");
        $stmt->bind_param("ss", $hashed_password, $token_data['user_id']);
        $update_success = $stmt->execute();
        $stmt->close();
    }
    
    if ($update_success) {
        // Mark token as used
        $stmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();
        
        // Clear demo token from session
        unset($_SESSION['reset_token_demo']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Password has been successfully reset. You can now login with your new password.',
            'redirect' => $token_data['user_type'] === 'staff' ? 'staff-login.html' : 'receiver-login.html'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while updating your password. Please try again.'
        ]);
    }
    
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}
?>
