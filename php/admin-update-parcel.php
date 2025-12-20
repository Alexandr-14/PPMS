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
    $status = trim($_POST['status'] ?? '');

    // Validate required fields (name/description is optional)
    if (empty($trackingNumber) || empty($receiverIC) || empty($weight) || empty($deliveryLocation)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
        exit();
    }

    // Set default description if empty
    if (empty($name)) {
        $name = 'Description only'; // Default description
    }

    // Validate weight is numeric
    if (!is_numeric($weight) || $weight <= 0) {
        echo json_encode(['success' => false, 'message' => 'Weight must be a positive number.']);
        exit();
    }

    try {
        // Check if parcel exists
        $checkQuery = "SELECT TrackingNumber FROM parcel WHERE TrackingNumber = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $trackingNumber);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Parcel not found.']);
            exit();
        }

        // Check if receiver Matric exists in receivers table
        $receiverQuery = "SELECT MatricNumber FROM receiver WHERE MatricNumber = ?";
        $receiverStmt = $conn->prepare($receiverQuery);
        $receiverStmt->bind_param("s", $receiverIC);
        $receiverStmt->execute();
        $receiverResult = $receiverStmt->get_result();

        if ($receiverResult->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Receiver Matric Number not found. Please ensure the receiver is registered.']);
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

            // Handle status update
            if ($status === 'Retrieved') {
                // Check if retrieval record exists
                $retrievalCheckQuery = "SELECT trackingNumber FROM retrievalrecord WHERE trackingNumber = ?";
                $retrievalCheckStmt = $conn->prepare($retrievalCheckQuery);
                $retrievalCheckStmt->bind_param("s", $trackingNumber);
                $retrievalCheckStmt->execute();
                $retrievalCheckResult = $retrievalCheckStmt->get_result();

                if ($retrievalCheckResult->num_rows === 0) {
                    // Insert new retrieval record
                    $insertRetrievalQuery = "INSERT INTO retrievalrecord (trackingNumber, MatricNumber, staffID, retrieveDate, retrieveTime, status) VALUES (?, ?, ?, CURDATE(), CURTIME(), 'Retrieved')";
                    $insertRetrievalStmt = $conn->prepare($insertRetrievalQuery);
                    $staffID = $_SESSION['staff_id'];
                    $insertRetrievalStmt->bind_param("sss", $trackingNumber, $receiverIC, $staffID);
                    $insertRetrievalStmt->execute();
                } else {
                    // Update existing retrieval record
                    $updateRetrievalQuery = "UPDATE retrievalrecord SET MatricNumber = ?, status = 'Retrieved', retrieveDate = CURDATE(), retrieveTime = CURTIME() WHERE trackingNumber = ?";
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
?>
