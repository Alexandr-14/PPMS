<?php
session_start();
require_once 'db_connect.php';
require_once 'notification-helper.php';

// Check if user is admin
if (!isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trackingNumber = trim($_POST['trackingNumber'] ?? '');
    $receiverIC = trim($_POST['receiverIC'] ?? '');
    $weight = trim($_POST['weight'] ?? '');
    $deliveryLocation = trim($_POST['deliveryLocation'] ?? '');
    $name = trim($_POST['name'] ?? '');

    // Validate required fields (name/description is optional)
    if (empty($trackingNumber) || empty($receiverIC) || empty($weight) || empty($deliveryLocation)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
        exit();
    }

    // Set default description if empty
    if (empty($name)) {
        $name = 'Package'; // Default description
    }

    // Validate weight is numeric
    if (!is_numeric($weight) || $weight <= 0) {
        echo json_encode(['success' => false, 'message' => 'Weight must be a positive number.']);
        exit();
    }

    try {
        // Check if tracking number already exists
        $checkQuery = "SELECT TrackingNumber FROM Parcel WHERE TrackingNumber = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $trackingNumber);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Tracking number already exists.']);
            exit();
        }

        // Check if receiver IC exists in receivers table
        $receiverQuery = "SELECT ICNumber FROM Receiver WHERE ICNumber = ?";
        $receiverStmt = $conn->prepare($receiverQuery);
        $receiverStmt->bind_param("s", $receiverIC);
        $receiverStmt->execute();
        $receiverResult = $receiverStmt->get_result();

        if ($receiverResult->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Receiver IC number not found. Please ensure the receiver is registered.']);
            exit();
        }

        // Insert new parcel with default status 'Pending'
        $insertQuery = "INSERT INTO Parcel (TrackingNumber, ICNumber, date, time, name, deliveryLocation, weight, status) VALUES (?, ?, CURDATE(), CURTIME(), ?, ?, ?, 'Pending')";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("ssssd", $trackingNumber, $receiverIC, $name, $deliveryLocation, $weight);

        if ($insertStmt->execute()) {
            // Send notification to receiver about new parcel arrival
            $notificationSent = sendParcelArrivalNotification($receiverIC, $trackingNumber, $name, $deliveryLocation);

            $response = [
                'success' => true,
                'message' => 'Parcel added successfully.',
                'trackingNumber' => $trackingNumber
            ];

            if ($notificationSent) {
                $response['notification'] = 'Receiver notified successfully.';
            } else {
                $response['notification'] = 'Parcel added but notification failed.';
            }

            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add parcel to database.']);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
