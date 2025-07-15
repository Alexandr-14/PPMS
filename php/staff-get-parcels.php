<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in as staff or admin
if (!isset($_SESSION['staff_role']) || !in_array($_SESSION['staff_role'], ['Staff', 'Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Staff privileges required.']);
    exit();
}

try {
    // Get all parcels with receiver information and status
    $query = "
        SELECT
            p.TrackingNumber,
            p.ICNumber,
            p.date,
            p.time,
            p.name,
            p.deliveryLocation,
            p.QR,
            p.weight,
            p.status,
            r.name as receiverName,
            ret.retrieveDate,
            ret.retrieveTime
        FROM Parcel p
        LEFT JOIN Receiver r ON p.ICNumber = r.ICNumber
        LEFT JOIN retrievalrecord ret ON p.TrackingNumber = ret.trackingNumber
        ORDER BY p.date DESC, p.time DESC
    ";

    $result = $conn->query($query);
    $parcels = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $parcels[] = [
                'TrackingNumber' => $row['TrackingNumber'],
                'ICNumber' => $row['ICNumber'],
                'receiverName' => $row['receiverName'],
                'name' => $row['name'],
                'deliveryLocation' => $row['deliveryLocation'],
                'weight' => $row['weight'],
                'QR' => $row['QR'],
                'date' => $row['date'],
                'time' => $row['time'],
                'status' => $row['status'],
                'retrieveDate' => $row['retrieveDate'],
                'retrieveTime' => $row['retrieveTime']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'parcels' => $parcels
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
