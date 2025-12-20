<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['receiver_matric']) || empty($_SESSION['receiver_matric'])) {
    header("Location: ../html/receiver-login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trackingNumber = $_POST['trackingNumber'] ?? '';

    if (empty($trackingNumber)) {
        header("Location: ../html/receiver-dashboard.php?tab=tracking&tracking_result=not_found");
        exit();
    }

    try {
        // Get current receiver's Matric number
        $receiverMatric = $_SESSION['receiver_matric'] ?? '';

        // Query the database for the parcel
        $stmt = $conn->prepare("SELECT * FROM parcel WHERE TrackingNumber = ?");
        $stmt->bind_param("s", $trackingNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Parcel found, check if it belongs to current receiver
            $parcel = $result->fetch_assoc();

            // Check if this parcel belongs to the current receiver
            if ($parcel['MatricNumber'] !== $receiverMatric) {
                // Parcel exists but belongs to different receiver
                header("Location: ../html/receiver-dashboard.php?tab=tracking&tracking_result=unauthorized");
                exit();
            }

            // Parcel belongs to current receiver, store in session and redirect
            $_SESSION['tracked_parcel'] = $parcel;
            header("Location: ../html/receiver-dashboard.php?tab=details&tracking_success=1");
            exit();
        } else {
            // No parcel found
            header("Location: ../html/receiver-dashboard.php?tab=tracking&tracking_result=not_found");
            exit();
        }
    } catch (Exception $e) {
        error_log("Error tracking parcel: " . $e->getMessage());
        header("Location: ../html/receiver-dashboard.php?tab=tracking&tracking_result=error");
        exit();
    }
} else {
    // Not a POST request
    header("Location: ../html/receiver-dashboard.php");
    exit();
}
?>