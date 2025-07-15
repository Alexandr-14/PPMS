<?php
require 'db_connect.php';
require 'password-validator.php';

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
if (empty($_POST['name']) || empty($_POST['staffId']) || empty($_POST['password']) || empty($_POST['confirm_password'])) {
    showError('Missing Information!', 'Please fill in all required fields.');
}

$name = trim($_POST['name']);
$staffId = trim($_POST['staffId']);
$password = $_POST['password'];
$confirmPassword = $_POST['confirm_password'];

// Validate Staff ID format (4 characters)
if (!preg_match('/^[A-Za-z0-9]{4}$/', $staffId)) {
    showError('Invalid Staff ID!', 'Staff ID must be exactly 4 characters (letters and numbers only).');
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
$stmt = $conn->prepare("SELECT staffID FROM Staff WHERE staffID = ?");
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
$stmt = $conn->prepare("INSERT INTO Staff (staffID, role, name, phoneNumber, password) VALUES (?, 'Staff', ?, '', ?)");
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
