<?php
session_start();
require_once __DIR__ . '/db_connect.php';

// Check if user is logged in as staff or admin
if (!isset($_SESSION['staff_role']) || !in_array($_SESSION['staff_role'], ['Staff', 'Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Staff privileges required.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trackingNumber = trim($_POST['trackingNumber'] ?? '');

    // Validate required fields
    if (empty($trackingNumber)) {
        echo json_encode(['success' => false, 'message' => 'Tracking number is required.']);
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

        // Start transaction
        $conn->begin_transaction();

        try {
            // Delete from notification table first (foreign key constraint)
            $deleteNotificationQuery = "DELETE FROM notification WHERE TrackingNumber = ?";
            $deleteNotificationStmt = $conn->prepare($deleteNotificationQuery);
            $deleteNotificationStmt->bind_param("s", $trackingNumber);
            $deleteNotificationStmt->execute();

            // Delete from retrievalrecord table second (foreign key constraint)
            $deleteRetrievalQuery = "DELETE FROM retrievalrecord WHERE trackingNumber = ?";
            $deleteRetrievalStmt = $conn->prepare($deleteRetrievalQuery);
            $deleteRetrievalStmt->bind_param("s", $trackingNumber);
            $deleteRetrievalStmt->execute();

            // Delete from parcel table last
            $deleteParcelQuery = "DELETE FROM parcel WHERE TrackingNumber = ?";
            $deleteParcelStmt = $conn->prepare($deleteParcelQuery);
            $deleteParcelStmt->bind_param("s", $trackingNumber);
            $deleteParcelStmt->execute();

            // Commit transaction
            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Parcel deleted successfully.'
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
