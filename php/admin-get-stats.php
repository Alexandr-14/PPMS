<?php
session_start();
require_once 'db_connect.php';

// Check if user is admin
if (!isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
    exit();
}

try {
    // Get total parcels from Parcel table
    $totalQuery = "SELECT COUNT(*) as total FROM Parcel";
    $totalResult = $conn->query($totalQuery);
    $totalParcels = $totalResult->fetch_assoc()['total'];

    // Get pending parcels (parcels not yet retrieved)
    $pendingQuery = "SELECT COUNT(*) as pending FROM Parcel WHERE TrackingNumber NOT IN (SELECT trackingNumber FROM retrievalrecord WHERE status = 'Retrieved')";
    $pendingResult = $conn->query($pendingQuery);
    $pendingParcels = $pendingResult->fetch_assoc()['pending'];

    // Get retrieved parcels
    $retrievedQuery = "SELECT COUNT(*) as retrieved FROM retrievalrecord WHERE status = 'Retrieved'";
    $retrievedResult = $conn->query($retrievedQuery);
    $retrievedParcels = $retrievedResult->fetch_assoc()['retrieved'];

    // Get total receivers
    $receiversQuery = "SELECT COUNT(*) as total FROM Receiver";
    $receiversResult = $conn->query($receiversQuery);
    $totalReceivers = $receiversResult->fetch_assoc()['total'];

    echo json_encode([
        'success' => true,
        'totalParcels' => $totalParcels,
        'pendingParcels' => $pendingParcels,
        'retrievedParcels' => $retrievedParcels,
        'totalReceivers' => $totalReceivers
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
