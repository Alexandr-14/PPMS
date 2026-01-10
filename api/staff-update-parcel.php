<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/notification-helper.php';

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
    $status = trim($_POST['status'] ?? 'Pending');

    // Validate required fields (name/description is optional)
    if (empty($trackingNumber) || empty($receiverIC) || empty($deliveryLocation) || $weight <= 0) {
        echo json_encode(['success' => false, 'message' => 'Tracking number, receiver Matric, delivery location are required and weight must be greater than 0.']);
        exit();
    }

    // Set default description if empty
    if (empty($name)) {
        $name = ''; // Default: empty description
    }

    // Validate Matric Number format (2 letters + 6 digits)
    if (!preg_match('/^[A-Z]{2}\d{6}$/', $receiverIC)) {
        echo json_encode(['success' => false, 'message' => 'Matric Number must be 2 letters followed by 6 digits (e.g., CI230010).']);
        exit();
    }

    try {
        // Check if parcel exists (and whether QR has been generated)
        $checkQuery = "SELECT TrackingNumber, QR, qr_verification_data FROM parcel WHERE TrackingNumber = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $trackingNumber);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Parcel not found.']);
            exit();
        }

        $parcelRow = $checkResult->fetch_assoc();
        if ($status === 'Retrieved') {
            $hasQr = !empty($parcelRow['QR']);
            $hasVerificationData = !empty($parcelRow['qr_verification_data']);
            if (!$hasQr || !$hasVerificationData) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cannot mark parcel as Retrieved until a QR code has been generated.'
                ]);
                exit();
            }
        }

        // Check if receiver exists
        $receiverCheckQuery = "SELECT MatricNumber FROM receiver WHERE MatricNumber = ?";
        $receiverCheckStmt = $conn->prepare($receiverCheckQuery);
        $receiverCheckStmt->bind_param("s", $receiverIC);
        $receiverCheckStmt->execute();
        $receiverCheckResult = $receiverCheckStmt->get_result();

        if ($receiverCheckResult->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Receiver with this Matric Number does not exist.']);
            exit();
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // Update parcel information INCLUDING STATUS
            $updateQuery = "UPDATE parcel SET MatricNumber = ?, name = ?, deliveryLocation = ?, weight = ?, status = ? WHERE TrackingNumber = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("sssdss", $receiverIC, $name, $deliveryLocation, $weight, $status, $trackingNumber);
            $updateStmt->execute();

            // Handle status update and send notifications
            if ($status === 'Retrieved') {
                // Check if retrieval record exists
                $retrievalCheckQuery = "SELECT trackingNumber FROM retrievalrecord WHERE trackingNumber = ?";
                $retrievalCheckStmt = $conn->prepare($retrievalCheckQuery);
                $retrievalCheckStmt->bind_param("s", $trackingNumber);
                $retrievalCheckStmt->execute();
                $retrievalCheckResult = $retrievalCheckStmt->get_result();

                if ($retrievalCheckResult->num_rows === 0) {
                    // Insert new retrieval record
                    $insertRetrievalQuery = "INSERT INTO retrievalrecord (trackingNumber, MatricNumber, retrieveDate, retrieveTime, status) VALUES (?, ?, CURDATE(), CURTIME(), 'Retrieved')";
                    $insertRetrievalStmt = $conn->prepare($insertRetrievalQuery);
                    $insertRetrievalStmt->bind_param("ss", $trackingNumber, $receiverIC);
                    $insertRetrievalStmt->execute();
                } else {
                    // Update existing retrieval record
                    $updateRetrievalQuery = "UPDATE retrievalrecord SET MatricNumber = ?, retrieveDate = CURDATE(), retrieveTime = CURTIME(), status = 'Retrieved' WHERE trackingNumber = ?";
                    $updateRetrievalStmt = $conn->prepare($updateRetrievalQuery);
                    $updateRetrievalStmt->bind_param("ss", $receiverIC, $trackingNumber);
                    $updateRetrievalStmt->execute();
                }

                // Send notification about parcel being retrieved
                sendParcelRetrievedNotification($receiverIC, $trackingNumber, $name);

            } else if ($status === 'Pending') {
                // Remove or update retrieval record to pending
                $updateRetrievalQuery = "UPDATE retrievalrecord SET status = 'Pending' WHERE trackingNumber = ?";
                $updateRetrievalStmt = $conn->prepare($updateRetrievalQuery);
                $updateRetrievalStmt->bind_param("s", $trackingNumber);
                $updateRetrievalStmt->execute();

                // Send notification about parcel being ready for pickup
                sendParcelReadyNotification($receiverIC, $trackingNumber, $name, $deliveryLocation);
            }

            // Commit transaction
            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Parcel updated successfully.'
            ]);

        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            throw $e;
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

