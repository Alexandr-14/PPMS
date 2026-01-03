<?php
/**
 * Process Parcel Retrieval Endpoint
 * Marks parcel as retrieved and logs the retrieval record
 * 
 * POST Parameters:
 * - trackingNumber: Parcel tracking number
 * - verificationToken: Token from QR verification
 * 
 * Returns: JSON response with retrieval result
 */

header('Content-Type: application/json');
session_start();

// Include database connection
require_once __DIR__ . '/db_connect.php';

// Security: Verify staff is logged in
if (!isset($_SESSION['staff_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'message' => 'Staff authentication required'
    ]);
    exit();
}

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['trackingNumber'])) {
        throw new Exception('Tracking number not provided');
    }

    $trackingNumber = $input['trackingNumber'];
    $staffID = $_SESSION['staff_id'];

    // Fetch parcel to verify it exists and is pending
    $query = "
        SELECT 
            p.TrackingNumber,
            p.MatricNumber,
            p.status,
            p.name,
            r.name as receiverName
        FROM parcel p
        LEFT JOIN receiver r ON p.MatricNumber = r.MatricNumber
        WHERE p.TrackingNumber = ?
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param("s", $trackingNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Parcel not found');
    }

    $parcel = $result->fetch_assoc();

    // Verify parcel status is Pending
    if ($parcel['status'] !== 'Pending') {
        throw new Exception('Parcel is not available for retrieval (current status: ' . $parcel['status'] . ')');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update parcel status to Retrieved
        $updateQuery = "UPDATE parcel SET status = 'Retrieved' WHERE TrackingNumber = ?";
        $updateStmt = $conn->prepare($updateQuery);
        
        if (!$updateStmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $updateStmt->bind_param("s", $trackingNumber);
        
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to update parcel status: ' . $updateStmt->error);
        }

        // Create retrieval record
        $retrieveDate = date('Y-m-d');
        $retrieveTime = date('H:i:s');
        $retrievalStatus = 'completed';

        $retrievalQuery = "
            INSERT INTO retrievalrecord (
                trackingNumber,
                MatricNumber,
                staffID,
                retrieveDate,
                retrieveTime,
                status
            ) VALUES (?, ?, ?, ?, ?, ?)
        ";

        $retrievalStmt = $conn->prepare($retrievalQuery);
        
        if (!$retrievalStmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $retrievalStmt->bind_param(
            "ssssss",
            $trackingNumber,
            $parcel['MatricNumber'],
            $staffID,
            $retrieveDate,
            $retrieveTime,
            $retrievalStatus
        );

        if (!$retrievalStmt->execute()) {
            throw new Exception('Failed to create retrieval record: ' . $retrievalStmt->error);
        }

        $retrievalID = $retrievalStmt->insert_id;

        // Create notification for receiver
        $notificationQuery = "
            INSERT INTO notification (
                MatricNumber,
                TrackingNumber,
                notificationType,
                messageContent,
                sentTimestamp,
                notificationStatus,
                isRead,
                deliveryMethod
            ) VALUES (?, ?, 'parcel_retrieved', ?, NOW(), 'sent', 0, 'system')
        ";

        $notificationStmt = $conn->prepare($notificationQuery);
        
        if (!$notificationStmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $messageContent = "({$trackingNumber}) Parcel has been retrieved. Thank you!";
        
        $notificationStmt->bind_param(
            "sss",
            $parcel['MatricNumber'],
            $trackingNumber,
            $messageContent
        );

        if (!$notificationStmt->execute()) {
            throw new Exception('Failed to create notification: ' . $notificationStmt->error);
        }

        // Commit transaction
        $conn->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Parcel marked as retrieved successfully',
            'retrievalID' => $retrievalID,
            'parcel' => [
                'trackingNumber' => $parcel['TrackingNumber'],
                'receiverName' => $parcel['receiverName'],
                'parcelName' => $parcel['name'],
                'retrievedAt' => $retrieveDate . ' ' . $retrieveTime,
                'staffID' => $staffID
            ]
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Failed to process parcel retrieval'
    ]);
}
?>

