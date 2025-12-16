<?php
session_start();
header('Content-Type: application/json');
require_once 'db_connect.php';

// Check if user is logged in as receiver
if (!isset($_SESSION['receiver_matric'])) {
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
    $receiverMatric = $_SESSION['receiver_matric'];

    // Get parcel details with QR code - only for the logged-in receiver
    $query = "
        SELECT
            p.TrackingNumber,
            p.MatricNumber,
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
        LEFT JOIN Receiver r ON p.MatricNumber = r.MatricNumber
        WHERE p.TrackingNumber = ? AND p.MatricNumber = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $trackingNumber, $receiverMatric);
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
            'MatricNumber' => $parcel['MatricNumber'],
            'receiverName' => $parcel['receiverName'],
            'name' => $parcel['name'],
            'deliveryLocation' => $parcel['deliveryLocation'],
            'weight' => $parcel['weight'],
            'status' => $parcel['status'],
            'date' => $parcel['date'],
            'time' => $parcel['time'],
            'qrExists' => $qrExists,
            'qrPath' => $qrPath,
            'qrGenerated' => !empty($parcel['QR']), // Flag: true if staff has generated QR
            'hasVerificationData' => !empty($parcel['qr_verification_data']),
            'verificationData' => $parcel['qr_verification_data'] // Return the actual verification data for QR generation
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
