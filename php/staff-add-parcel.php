<?php
session_start();
require_once 'db_connect.php';
require_once 'notification-helper.php';

// Check if user is logged in as staff or admin
if (!isset($_SESSION['staff_role']) || !in_array($_SESSION['staff_role'], ['Staff', 'Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Staff privileges required.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trackingNumber = trim($_POST['trackingNumber'] ?? '');
    $receiverIC = trim($_POST['receiverIC'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $deliveryLocation = trim($_POST['deliveryLocation'] ?? '');
    $weight = floatval($_POST['weight'] ?? 0);

    // Validate required fields (name/description is optional)
    if (empty($trackingNumber) || empty($receiverIC) || empty($deliveryLocation) || $weight <= 0) {
        echo json_encode(['success' => false, 'message' => 'Tracking number, receiver IC, delivery location are required and weight must be greater than 0.']);
        exit();
    }

    // Set default description if empty
    if (empty($name)) {
        $name = 'Package'; // Default description
    }

    // Validate IC Number format (12 digits)
    if (!preg_match('/^\d{12}$/', $receiverIC)) {
        echo json_encode(['success' => false, 'message' => 'IC Number must be exactly 12 digits.']);
        exit();
    }

    try {
        // Check if receiver exists
        $receiverCheckQuery = "SELECT ICNumber FROM Receiver WHERE ICNumber = ?";
        $receiverCheckStmt = $conn->prepare($receiverCheckQuery);
        $receiverCheckStmt->bind_param("s", $receiverIC);
        $receiverCheckStmt->execute();
        $receiverCheckResult = $receiverCheckStmt->get_result();

        if ($receiverCheckResult->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Receiver with this IC Number does not exist. Please register the receiver first.']);
            exit();
        }

        // Check if tracking number already exists
        $checkQuery = "SELECT TrackingNumber FROM Parcel WHERE TrackingNumber = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $trackingNumber);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Tracking number already exists. Please use a different tracking number.']);
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
