<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['receiver_matric']) || empty($_SESSION['receiver_matric'])) {
    header("Location: ../html/receiver-login.html");
    exit();
}

// Check if tracking number is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../html/receiver-dashboard.php?tab=availability");
    exit();
}

$trackingNumber = $_GET['id'];
$receiverMatric = $_SESSION['receiver_matric'];

try {
    // Query the database for the parcel, ensuring it belongs to the logged-in user
    $stmt = $conn->prepare("SELECT * FROM parcel WHERE TrackingNumber = ? AND MatricNumber = ?");
    $stmt->bind_param("ss", $trackingNumber, $receiverMatric);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Parcel found, store in session and redirect
        $parcel = $result->fetch_assoc();
        $_SESSION['tracked_parcel'] = $parcel;
        header("Location: ../html/receiver-dashboard.php?tab=details&tracking_success=1");
        exit();
    } else {
        // No parcel found or not authorized
        header("Location: ../html/receiver-dashboard.php?tab=availability&error=not_found");
        exit();
    }
} catch (Exception $e) {
    error_log("Error viewing parcel details: " . $e->getMessage());
    header("Location: ../html/receiver-dashboard.php?tab=availability&error=db_error");
    exit();
}
?>