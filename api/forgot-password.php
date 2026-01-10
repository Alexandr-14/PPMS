<?php
session_start();
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json');

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
if (count($_SESSION[$rate_limit_key]) >= 10) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many attempts. Please try again later.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// Normalize phone number for comparison
function normalizePhone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        $phone = '+60' . substr($phone, 1);
    }
    return $phone;
}

switch ($action) {
    case 'verify_identity':
        // Step 1: Verify Matric Number + Phone Number
        $matric = strtoupper(trim($input['matric_number'] ?? ''));
        $phone = normalizePhone(trim($input['phone_number'] ?? ''));

        if (empty($matric) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Please enter both Matric Number and Phone Number.']);
            exit;
        }

        if (!preg_match('/^[A-Z]{2}[0-9]{6}$/', $matric)) {
            echo json_encode(['success' => false, 'message' => 'Invalid Matric Number format.']);
            exit;
        }

        $_SESSION[$rate_limit_key][] = time();

        $stmt = $conn->prepare("SELECT security_question, phoneNumber FROM receiver WHERE MatricNumber = ?");
        $stmt->bind_param("s", $matric);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'No account found with this Matric Number.']);
            exit;
        }

        $row = $result->fetch_assoc();
        $storedPhone = normalizePhone($row['phoneNumber']);

        if ($storedPhone !== $phone) {
            echo json_encode(['success' => false, 'message' => 'Phone number does not match our records.']);
            exit;
        }

        if (empty($row['security_question'])) {
            echo json_encode(['success' => false, 'message' => 'No security question set. Please contact administrator.']);
            exit;
        }

        // Store matric in session for next step
        $_SESSION['reset_matric'] = $matric;

        echo json_encode([
            'success' => true,
            'security_question' => $row['security_question']
        ]);
        break;

    case 'verify_answer':
        // Step 2: Verify Security Answer
        $matric = strtoupper(trim($input['matric_number'] ?? ''));
        $answer = strtolower(trim($input['security_answer'] ?? ''));

        if (empty($matric) || empty($answer)) {
            echo json_encode(['success' => false, 'message' => 'Please provide your answer.']);
            exit;
        }

        // Verify session
        if (!isset($_SESSION['reset_matric']) || $_SESSION['reset_matric'] !== $matric) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
            exit;
        }

        $_SESSION[$rate_limit_key][] = time();

        $stmt = $conn->prepare("SELECT security_answer FROM receiver WHERE MatricNumber = ?");
        $stmt->bind_param("s", $matric);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Account not found.']);
            exit;
        }

        $row = $result->fetch_assoc();

        // Verify hashed answer
        if (!password_verify($answer, $row['security_answer'])) {
            echo json_encode(['success' => false, 'message' => 'Incorrect answer. Please try again.']);
            exit;
        }

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $_SESSION['reset_token'] = $token;
        $_SESSION['reset_token_matric'] = $matric;
        $_SESSION['reset_token_expires'] = time() + 600; // 10 minutes

        echo json_encode(['success' => true, 'token' => $token]);
        break;

    case 'reset_password':
        // Step 3: Reset Password
        $matric = strtoupper(trim($input['matric_number'] ?? ''));
        $token = $input['token'] ?? '';
        $newPassword = $input['new_password'] ?? '';

        if (empty($matric) || empty($token) || empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
            exit;
        }

        // Verify token
        if (!isset($_SESSION['reset_token']) || $_SESSION['reset_token'] !== $token ||
            !isset($_SESSION['reset_token_matric']) || $_SESSION['reset_token_matric'] !== $matric ||
            !isset($_SESSION['reset_token_expires']) || $_SESSION['reset_token_expires'] < time()) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired token. Please start over.']);
            exit;
        }

        if (strlen($newPassword) < 8) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
            exit;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE receiver SET password = ? WHERE MatricNumber = ?");
        $stmt->bind_param("ss", $hashedPassword, $matric);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Clear session data
            unset($_SESSION['reset_matric'], $_SESSION['reset_token'], $_SESSION['reset_token_matric'], $_SESSION['reset_token_expires']);
            echo json_encode(['success' => true, 'message' => 'Password reset successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reset password. Please try again.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

$conn->close();

