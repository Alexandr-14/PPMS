<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['receiver_matric']) || empty($_SESSION['receiver_matric'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check database connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$receiverMatric = $_SESSION['receiver_matric'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$action = $input['action'];

try {
    if ($action === 'mark_all_read') {
        // Mark all notifications as read for this receiver
        $stmt = $conn->prepare("UPDATE notification SET isRead = 1 WHERE MatricNumber = ? AND isRead = 0");
        $stmt->bind_param("s", $receiverMatric);

        if ($stmt->execute()) {
            $affectedRows = $stmt->affected_rows;
            echo json_encode([
                'success' => true,
                'message' => "Marked $affectedRows notifications as read",
                'affected_rows' => $affectedRows
            ]);
        } else {
            throw new Exception('Failed to update notifications');
        }

        $stmt->close();

    } elseif ($action === 'mark_single_read') {
        if (!isset($input['notificationId'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Notification ID required']);
            exit();
        }

        $notificationId = $input['notificationId'];

        // Mark single notification as read (ensure it belongs to this receiver)
        $stmt = $conn->prepare("UPDATE notification SET isRead = 1 WHERE notificationID = ? AND MatricNumber = ?");
        $stmt->bind_param("is", $notificationId, $receiverMatric);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Notification marked as read'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Notification not found or already read'
                ]);
            }
        } else {
            throw new Exception('Failed to update notification');
        }
        
        $stmt->close();
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
