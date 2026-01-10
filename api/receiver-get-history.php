<?php
session_start();
require_once __DIR__ . '/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['receiver_matric']) || empty($_SESSION['receiver_matric'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$receiverIC = $_SESSION['receiver_matric'];

try {
    // Fetch receiver's parcel history
    $stmt = $conn->prepare("
        SELECT
            p.*,
            r.name as receiverName
        FROM parcel p
        LEFT JOIN receiver r ON p.MatricNumber = r.MatricNumber
        WHERE p.MatricNumber = ?
        ORDER BY p.date DESC, p.time DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $receiverIC);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $parcels = [];
    
    while ($row = $result->fetch_assoc()) {
        $parcels[] = $row;
    }
    
    // Count statistics
    $totalCount = count($parcels);
    $pendingCount = 0;
    $retrievedCount = 0;
    
    foreach ($parcels as $parcel) {
        if ($parcel['status'] === 'Pending') {
            $pendingCount++;
        } elseif ($parcel['status'] === 'Retrieved') {
            $retrievedCount++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'parcels' => $parcels,
        'totalCount' => $totalCount,
        'pendingCount' => $pendingCount,
        'retrievedCount' => $retrievedCount
    ]);
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching history: ' . $e->getMessage()
    ]);
}

$conn->close();
?>


