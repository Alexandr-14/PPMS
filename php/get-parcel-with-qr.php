<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

// Check if user is logged in as receiver or staff
$isReceiver = isset($_SESSION['receiver_matric']);
$isStaff = isset($_SESSION['staff_id']);

if (!$isReceiver && !$isStaff) {
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

    // Build query based on user type
    if ($isReceiver) {
        // Receiver can only view their own parcels
        $receiverMatric = $_SESSION['receiver_matric'];
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
            FROM parcel p
            LEFT JOIN receiver r ON p.MatricNumber = r.MatricNumber
            WHERE p.TrackingNumber = ? AND p.MatricNumber = ?
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $trackingNumber, $receiverMatric);
    } else {
        // Staff can view all parcels
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
            FROM parcel p
            LEFT JOIN receiver r ON p.MatricNumber = r.MatricNumber
            WHERE p.TrackingNumber = ?
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $trackingNumber);
    }

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
        $fullQrPath = __DIR__ . '/../' . ltrim($parcel['QR'], '/\\');
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
