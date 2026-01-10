<?php
session_start();
require_once __DIR__ . '/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matric_number = trim($_POST['matricnumber'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($matric_number) || empty($password)) {
        $_SESSION['error'] = "Please enter both Matric Number and password!";
        header("Location: ../html/receiver-login.html?login=fail");
        exit;
    }

    // Convert to uppercase and validate format
    $matric_number = strtoupper($matric_number);
    if (!preg_match('/^[A-Z]{2}[0-9]{6}$/', $matric_number)) {
        $_SESSION['error'] = "Invalid Matric Number format!";
        header("Location: ../html/receiver-login.html?login=fail");
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM receiver WHERE MatricNumber = ?");
    $stmt->bind_param("s", $matric_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Login successful
            $_SESSION['receiver_id'] = $row['MatricNumber'];
            $_SESSION['receiver_name'] = $row['name'];
            $_SESSION['receiver_matric'] = $row['MatricNumber'];
            header("Location: ../html/receiver-dashboard.php?login=success");
            exit();
        } else {
            // Invalid password
            $_SESSION['error'] = "Invalid password!";
            header("Location: ../html/receiver-login.html?login=fail");
            exit;
        }
    } else {
        // User not found
        $_SESSION['error'] = "User not found!";
        header("Location: ../html/receiver-login.html?login=fail");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>
