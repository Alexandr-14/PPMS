<?php
session_start();
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staffId = trim($_POST['staffId'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($staffId) || empty($password)) {
        $_SESSION['error'] = "Please enter both Staff ID and password!";
        header("Location: ../html/staff-login.html?login=fail");
        exit;
    }

    // Check staff table for regular staff
    $stmt = $conn->prepare("SELECT * FROM staff WHERE staffID = ?");
    $stmt->bind_param("s", $staffId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Login successful
            $_SESSION['staff_id'] = $row['staffID'];
            $_SESSION['staff_name'] = $row['name'];
            $_SESSION['staff_role'] = $row['role'];
            $_SESSION['staff_phone'] = $row['phoneNumber'];
            
            $role = strtolower($row['role']);
            $_SESSION['login_success'] = "Welcome back, " . $row['name'] . "!";
            header("Location: ../html/staff-dashboard.php?login=success&role=" . $role);
            exit();
        } else {
            // Invalid password
            $_SESSION['error'] = "Invalid password!";
            header("Location: ../html/staff-login.html?login=fail");
            exit;
        }
    } else {
        // User not found
        $_SESSION['error'] = "Staff ID not found!";
        header("Location: ../html/staff-login.html?login=fail");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: ../html/staff-login.html");
    exit();
}
?>

