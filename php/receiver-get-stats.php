<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as receiver
if (!isset($_SESSION['receiver_ic'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Please login first.']);
    exit();
}

$receiverIC = $_SESSION['receiver_ic'];

try {
    // Get total parcels for this receiver
    $totalQuery = "SELECT COUNT(*) as total FROM Parcel WHERE ICNumber = ?";
    $totalStmt = $conn->prepare($totalQuery);
    $totalStmt->bind_param("s", $receiverIC);
    $totalStmt->execute();
    $totalResult = $totalStmt->get_result();
    $totalParcels = $totalResult->fetch_assoc()['total'];

    // Get pending parcels (not yet retrieved)
    $pendingQuery = "SELECT COUNT(*) as pending FROM Parcel p 
                     WHERE p.ICNumber = ? 
                     AND p.TrackingNumber NOT IN (
                         SELECT trackingNumber FROM retrievalrecord 
                         WHERE status = 'Retrieved'
                     )";
    $pendingStmt = $conn->prepare($pendingQuery);
    $pendingStmt->bind_param("s", $receiverIC);
    $pendingStmt->execute();
    $pendingResult = $pendingStmt->get_result();
    $pendingParcels = $pendingResult->fetch_assoc()['pending'];

    // Get retrieved parcels
    $retrievedQuery = "SELECT COUNT(*) as retrieved FROM retrievalrecord r
                       JOIN Parcel p ON r.trackingNumber = p.TrackingNumber
                       WHERE p.ICNumber = ? AND r.status = 'Retrieved'";
    $retrievedStmt = $conn->prepare($retrievedQuery);
    $retrievedStmt->bind_param("s", $receiverIC);
    $retrievedStmt->execute();
    $retrievedResult = $retrievedStmt->get_result();
    $retrievedParcels = $retrievedResult->fetch_assoc()['retrieved'];

    echo json_encode([
        'success' => true,
        'totalParcels' => $totalParcels,
        'pendingParcels' => $pendingParcels,
        'retrievedParcels' => $retrievedParcels
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
