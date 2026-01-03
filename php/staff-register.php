<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/password-validator.php';

// =====================================================
// STAFF REGISTRATION CODE - Change this as needed
// =====================================================
define('STAFF_REGISTRATION_CODE', getenv('STAFF_REGISTRATION_CODE') ?: 'CHANGE_ME');
// =====================================================

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to show error and redirect
function showError($title, $message) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Registration Error</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
        Swal.fire({
            icon: 'error',
            title: '$title',
            text: '$message',
            confirmButtonText: 'Back'
        }).then(() => {
            window.location.href = '../html/staff-register.html';
        });
        </script>
    </body>
    </html>";
    exit();
}

// Validate required fields
if (empty($_POST['registration_code']) || empty($_POST['name']) || empty($_POST['staffId']) || empty($_POST['password']) || empty($_POST['confirm_password'])) {
    showError('Missing Information!', 'Please fill in all required fields.');
}

$registrationCode = trim($_POST['registration_code']);
$name = trim($_POST['name']);
$staffId = trim($_POST['staffId']);
$password = $_POST['password'];
$confirmPassword = $_POST['confirm_password'];

// Validate Registration Code FIRST (security check)
if (STAFF_REGISTRATION_CODE === 'CHANGE_ME') {
    showError('Registration Disabled', 'Staff registration code is not configured. Please contact the administrator.');
}
if ($registrationCode !== STAFF_REGISTRATION_CODE) {
    // Log failed attempt (optional security measure)
    error_log("Failed staff registration attempt with invalid code from IP: " . $_SERVER['REMOTE_ADDR']);
    showError('Invalid Registration Code!', 'The registration code you entered is incorrect. Please contact the administrator.');
}

// Validate Staff ID format (4 digits, 8000-8999)
if (!preg_match('/^8[0-9]{3}$/', $staffId)) {
    showError('Invalid Staff ID!', 'Staff ID must be 4 digits between 8000-8999.');
}

// Validate Staff ID range
$staffIdNum = intval($staffId);
if ($staffIdNum < 8000 || $staffIdNum > 8999) {
    showError('Invalid Staff ID Range!', 'Staff ID must be between 8000 and 8999.');
}

// Validate password strength
$passwordErrors = validatePasswordStrength($password);
if (!empty($passwordErrors)) {
    showError('Weak Password!', implode(' ', $passwordErrors));
}

// Validate password match
$matchErrors = validatePasswordMatch($password, $confirmPassword);
if (!empty($matchErrors)) {
    showError('Password Mismatch!', implode(' ', $matchErrors));
}

// Check if Staff ID already exists
$stmt = $conn->prepare("SELECT staffID FROM staff WHERE staffID = ?");
$stmt->bind_param("s", $staffId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Staff ID already registered
    $stmt->close();
    $conn->close();
    showError('Staff ID already registered!', 'Please use a different Staff ID.');
}
$stmt->close();

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert new staff with default role 'Staff'
$stmt = $conn->prepare("INSERT INTO staff (staffID, role, name, phoneNumber, password) VALUES (?, 'Staff', ?, '', ?)");
$stmt->bind_param("sss", $staffId, $name, $hashed_password);

if ($stmt->execute()) {
    // Registration successful, redirect with flag
    $stmt->close();
    $conn->close();
    header("Location: ../html/staff-register.html?registered=1");
    exit();
} else {
    // Registration failed
    $stmt->close();
    $conn->close();
    showError('Registration Failed', 'Please try again. Error: ' . $conn->error);
}
