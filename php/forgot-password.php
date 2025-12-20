<?php
session_start();
require_once 'db_connect.php';

// Rate limiting - prevent abuse
$rate_limit_key = 'forgot_password_' . $_SERVER['REMOTE_ADDR'];
if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = [];
}

// Clean old attempts (older than 1 hour)
$_SESSION[$rate_limit_key] = array_filter($_SESSION[$rate_limit_key], function($time) {
    return $time > (time() - 3600);
});

// Check if too many attempts
if (count($_SESSION[$rate_limit_key]) >= 5) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Too many password reset attempts. Please try again later.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_type = trim($_POST['user_type'] ?? '');
    $matric_number = trim($_POST['matric_number'] ?? '');

    // Security: Only allow receiver type for forgot password
    if ($user_type !== 'receiver') {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request.'
        ]);
        exit;
    }

    // Validate input
    if (empty($matric_number)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter your Matric Number.'
        ]);
        exit;
    }

    // Validate Matric format (2 letters + 6 digits)
    if (!preg_match('/^[A-Z]{2}[0-9]{6}$/', $matric_number)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid Matric Number: 2 letters + 6 digits (e.g., CI230010)'
        ]);
        exit;
    }

    // Add to rate limiting
    $_SESSION[$rate_limit_key][] = time();

    // Check if receiver exists (receiver-only forgot password)
    $user_exists = false;
    $user_name = '';

    $stmt = $conn->prepare("SELECT name FROM receiver WHERE MatricNumber = ?");
    $stmt->bind_param("s", $matric_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_exists = true;
        $user_name = $row['name'];
    }
    $stmt->close();
    
    // Always return success message for security (don't reveal if user exists)
    if ($user_exists) {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

        // Clean up old tokens for this user (receiver only)
        $cleanup_stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_type = ? AND user_id = ?");
        $cleanup_stmt->bind_param("ss", $user_type, $ic_number);
        $cleanup_stmt->execute();
        $cleanup_stmt->close();

        // Insert new token
        $stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_type, user_id, token, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $user_type, $ic_number, $token, $expires_at);

        if ($stmt->execute()) {
            // In a real system, you would send an email here
            // For now, we'll store the token in session for demo purposes
            $_SESSION['reset_token_demo'] = [
                'token' => $token,
                'user_type' => $user_type,
                'user_id' => $ic_number,
                'user_name' => $user_name,
                'expires_at' => $expires_at
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Password reset instructions have been sent to your email address.',
                'demo_token' => $token // Remove this in production
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred. Please try again later.'
            ]);
        }
        $stmt->close();
    } else {
        // Return success even if user doesn't exist (security)
        echo json_encode([
            'success' => true,
            'message' => 'If an account with that ID exists, password reset instructions have been sent.'
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
