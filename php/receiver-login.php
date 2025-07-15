<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ic_number = trim($_POST['icnumber'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($ic_number) || empty($password)) {
        $_SESSION['error'] = "Please enter both IC No and password!";
        header("Location: ../html/receiver-login.html?login=fail");
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM Receiver WHERE ICNumber = ?");
    $stmt->bind_param("s", $ic_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Login successful
            $_SESSION['receiver_id'] = $row['id'];
            $_SESSION['receiver_name'] = $row['name'];
            $_SESSION['receiver_ic'] = $row['ICNumber']; // Add this line to set the IC number
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