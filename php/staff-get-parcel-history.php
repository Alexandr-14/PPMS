<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as staff or admin
if (!isset($_SESSION['staff_role']) || !in_array($_SESSION['staff_role'], ['Staff', 'Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Staff privileges required.']);
    exit();
}

try {
    // Fetch only RETRIEVED parcels (history)
    $query = "
        SELECT
            p.TrackingNumber,
            p.MatricNumber,
            p.name,
            p.deliveryLocation,
            p.weight,
            p.date,
            p.time,
            p.status,
            r.name as receiverName,
            r.phoneNumber as receiverPhone
        FROM parcel p
        LEFT JOIN receiver r ON p.MatricNumber = r.MatricNumber
        WHERE p.status = 'Retrieved'
        ORDER BY p.date DESC, p.time DESC
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $parcels = [];
    while ($row = $result->fetch_assoc()) {
        $parcels[] = [
            'TrackingNumber' => $row['TrackingNumber'],
            'MatricNumber' => $row['MatricNumber'],
            'name' => $row['name'],
            'deliveryLocation' => $row['deliveryLocation'],
            'weight' => $row['weight'],
            'date' => $row['date'],
            'time' => $row['time'],
            'status' => $row['status'],
            'receiverName' => $row['receiverName'],
            'receiverPhone' => $row['receiverPhone']
        ];
    }

    echo json_encode([
        'success' => true,
        'parcels' => $parcels,
        'count' => count($parcels)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

