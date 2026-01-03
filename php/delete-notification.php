<?php
session_start();
require_once __DIR__ . '/db_connect.php';

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

if (!$input || !isset($input['notificationId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit();
}

$notificationId = $input['notificationId'];

try {
    // Delete notification (ensure it belongs to this receiver)
    $stmt = $conn->prepare("DELETE FROM notification WHERE notificationID = ? AND MatricNumber = ?");
    $stmt->bind_param("is", $notificationId, $receiverMatric);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Notification deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Notification not found or already deleted'
            ]);
        }
    } else {
        throw new Exception('Failed to delete notification');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
