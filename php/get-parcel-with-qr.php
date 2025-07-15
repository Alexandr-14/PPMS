<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

// Check if user is logged in as receiver
if (!isset($_SESSION['receiver_ic'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Please log in.'
    ]);
    exit();
}

if (!isset($_GET['trackingNumber'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Tracking number is required'
    ]);
    exit();
}

try {
    $trackingNumber = $_GET['trackingNumber'];
    $receiverIC = $_SESSION['receiver_ic'];
    
    // Get parcel details with QR code - only for the logged-in receiver
    $query = "
        SELECT 
            p.TrackingNumber,
            p.ICNumber,
            p.date,
            p.time,
            p.name,
            p.deliveryLocation,
            p.weight,
            p.status,
            p.QR,
            p.qr_verification_data,
            r.name as receiverName
        FROM Parcel p
        LEFT JOIN Receiver r ON p.ICNumber = r.ICNumber
        WHERE p.TrackingNumber = ? AND p.ICNumber = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $trackingNumber, $receiverIC);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Parcel not found or access denied'
        ]);
        exit();
    }
    
    $parcel = $result->fetch_assoc();
    
    // Check if QR code file exists
    $qrExists = false;
    $qrPath = null;
    
    if (!empty($parcel['QR'])) {
        $fullQrPath = '../' . $parcel['QR'];
        if (file_exists($fullQrPath)) {
            $qrExists = true;
            $qrPath = $parcel['QR'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'parcel' => [
            'TrackingNumber' => $parcel['TrackingNumber'],
            'ICNumber' => $parcel['ICNumber'],
            'receiverName' => $parcel['receiverName'],
            'name' => $parcel['name'],
            'deliveryLocation' => $parcel['deliveryLocation'],
            'weight' => $parcel['weight'],
            'status' => $parcel['status'],
            'date' => $parcel['date'],
            'time' => $parcel['time'],
            'qrExists' => $qrExists,
            'qrPath' => $qrPath,
            'hasVerificationData' => !empty($parcel['qr_verification_data'])
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
