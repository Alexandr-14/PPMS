<?php
require 'db_connect.php';
require 'password-validator.php';

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Data sanitization functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function sanitizeName($name) {
    $name = sanitizeInput($name);
    // Remove any characters that aren't letters, spaces, or common name symbols
    $name = preg_replace('/[^a-zA-Z\s@\/\.\',\-]/', '', $name);
    return $name;
}

function sanitizePhone($phone) {
    $phone = sanitizeInput($phone);
    // Remove any non-digit characters except + at the beginning
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return $phone;
}

function sanitizeMatric($matric) {
    $matric = sanitizeInput($matric);
    // Convert to uppercase and keep only letters and digits
    $matric = strtoupper($matric);
    $matric = preg_replace('/[^A-Z0-9]/', '', $matric);
    return $matric;
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
            window.location.href = '../html/receiver-register.html';
        });
        </script>
    </body>
    </html>";
    exit();
}

// Validate required fields
if (empty($_POST['name']) || empty($_POST['matricnumber']) || empty($_POST['phoneNumber']) || empty($_POST['password']) || empty($_POST['confirm_password']) || empty($_POST['security_question']) || empty($_POST['security_answer'])) {
    showError('Missing Information!', 'Please fill in all required fields.');
}

// Sanitize and validate input data
$name = sanitizeName($_POST['name']);
$matricnumber = sanitizeMatric($_POST['matricnumber']);
$phone = sanitizePhone($_POST['phoneNumber']);
$password = $_POST['password']; // Don't sanitize password as it may contain special chars
$confirmPassword = $_POST['confirm_password'];
$securityQuestion = sanitizeInput($_POST['security_question']);
$securityAnswer = strtolower(trim($_POST['security_answer'])); // Store lowercase for case-insensitive comparison

// Additional validation after sanitization
if (empty($name) || strlen($name) < 2 || strlen($name) > 100) {
    showError('Invalid Name!', 'Name must be between 2-100 characters and contain only letters and common symbols.');
}

// Validate Matric number format (2 letters + 6 digits)
if (!preg_match('/^[A-Z]{2}[0-9]{6}$/', $matricnumber)) {
    showError('Invalid Matric Number!', 'Matric Number must be 2 letters followed by 6 digits (e.g., CI230010).');
}

// Validate Malaysian phone number format
if (!preg_match('/^(\+60|0)[1-9][0-9]{7,9}$/', $phone)) {
    showError('Invalid Phone Number!', 'Please enter a valid Malaysian phone number (e.g., +60123456789 or 0123456789).');
}

// Normalize phone number to +60 format for storage
if (substr($phone, 0, 1) === '0') {
    $phone = '+60' . substr($phone, 1);
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

// Check if Matric number already exists
$stmt = $conn->prepare("SELECT MatricNumber FROM receiver WHERE MatricNumber = ?");
$stmt->bind_param("s", $matricnumber);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Matric already registered
    $stmt->close();
    $conn->close();
    showError('Matric Number already registered!', 'Please use a different Matric Number.');
}
$stmt->close();

// Hash the password and security answer
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$hashed_security_answer = password_hash($securityAnswer, PASSWORD_DEFAULT);

// Insert new receiver with security question
$stmt = $conn->prepare("INSERT INTO receiver (MatricNumber, name, phoneNumber, password, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $matricnumber, $name, $phone, $hashed_password, $securityQuestion, $hashed_security_answer);

if ($stmt->execute()) {
    // Registration successful, redirect with flag
    $stmt->close();
    $conn->close();
    header("Location: ../html/receiver-register.html?registered=1");
    exit();
} else {
    // Registration failed
    $stmt->close();
    $conn->close();
    showError('Registration Failed', 'Please try again. Error: ' . $conn->error);
}
?>