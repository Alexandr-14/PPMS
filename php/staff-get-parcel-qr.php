<?php
/**
 * Staff Get Parcel QR Data
 * Fetches the latest QR verification data for a parcel
 * Used by staff dashboard to display the correct QR code
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'PHP Error: ' . $errstr]);
    exit();
});

session_start();
require_once 'db_connect.php';

// Check if user is logged in as staff or admin
if (!isset($_SESSION['staff_role']) || ($_SESSION['staff_role'] !== 'Staff' && $_SESSION['staff_role'] !== 'Admin')) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Staff privileges required.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

try {
    $trackingNumber = trim($_GET['trackingNumber'] ?? '');

    if (empty($trackingNumber)) {
        echo json_encode(['success' => false, 'message' => 'Tracking number is required.']);
        exit();
    }

    // Get parcel with QR verification data
    $query = "
        SELECT
            TrackingNumber,
            MatricNumber,
            date,
            time,
            name,
            deliveryLocation,
            weight,
            status,
            QR,
            qr_verification_data
        FROM Parcel
        WHERE TrackingNumber = ?
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("s", $trackingNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Parcel not found.']);
        exit();
    }

    $parcel = $result->fetch_assoc();

    // Check if QR verification data exists
    $hasVerificationData = !empty($parcel['qr_verification_data']);
    $verificationData = null;

    if ($hasVerificationData) {
        // Verify it's valid JSON
        $decoded = json_decode($parcel['qr_verification_data'], true);
        if ($decoded !== null) {
            $verificationData = $parcel['qr_verification_data'];
        }
    }

    echo json_encode([
        'success' => true,
        'parcel' => [
            'TrackingNumber' => $parcel['TrackingNumber'],
            'MatricNumber' => $parcel['MatricNumber'],
            'date' => $parcel['date'],
            'time' => $parcel['time'],
            'name' => $parcel['name'],
            'deliveryLocation' => $parcel['deliveryLocation'],
            'weight' => $parcel['weight'],
            'status' => $parcel['status'],
            'qrPath' => $parcel['QR'],
            'hasVerificationData' => $hasVerificationData,
            'verificationData' => $verificationData
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

