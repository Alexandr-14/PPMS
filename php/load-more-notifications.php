<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in (support both session variable names)
if (!isset($_SESSION['receiver_matric']) && !isset($_SESSION['matric_number'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

require_once __DIR__ . '/db_connect.php';


$receiverIC = $_SESSION['receiver_matric'] ?? $_SESSION['matric_number'];
$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
$limit = 10;

try {
    // Fetch notifications with pagination
    $stmt = $conn->prepare("
        SELECT * FROM notification 
        WHERE MatricNumber = ? 
        ORDER BY sentTimestamp DESC 
        LIMIT ? OFFSET ?
    ");
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("sii", $receiverIC, $limit, $offset);
    
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $notifications = [];
    
    while ($notification = $result->fetch_assoc()) {
        $notifications[] = $notification;
    }
    
    // Get total count
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM notification WHERE MatricNumber = ?");
    $countStmt->bind_param("s", $receiverIC);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalCount = $countResult->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'hasMore' => ($offset + $limit) < $totalCount,
        'nextOffset' => $offset + $limit,
        'total' => $totalCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

