<?php
/**
 * Generate QR Code for a Parcel
 * Creates a unique QR code payload for each parcel based on its data
 * Format: PPMS|TrackingNumber|MatricNumber|ReceiverName|DeliveryLocation|Status
 */

require_once 'db_connect.php';

// Check if tracking number is provided
if (!isset($_GET['trackingNumber']) || empty($_GET['trackingNumber'])) {
    echo json_encode(['success' => false, 'message' => 'Tracking number is required.']);
    exit();
}

$trackingNumber = trim($_GET['trackingNumber']);

try {
    // Fetch parcel details from database
    $query = "
        SELECT
            p.TrackingNumber,
            p.MatricNumber,
            p.name,
            p.deliveryLocation,
            p.weight,
            p.status,
            p.date,
            p.time,
            r.name as receiverName
        FROM Parcel p
        LEFT JOIN Receiver r ON p.MatricNumber = r.MatricNumber
        WHERE p.TrackingNumber = ?
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param("s", $trackingNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Parcel not found.']);
        exit();
    }

    $parcel = $result->fetch_assoc();
    $stmt->close();

    // Build unique QR payload
    // Format: PPMS|TrackingNumber|MatricNumber|ReceiverName|DeliveryLocation|Status
    $qrPayload = "PPMS|" .
                 $parcel['TrackingNumber'] . "|" .
                 $parcel['MatricNumber'] . "|" .
                 $parcel['receiverName'] . "|" .
                 $parcel['deliveryLocation'] . "|" .
                 $parcel['status'];

    // Generate QR code URL using qr-server.com API
    $encodedData = urlencode($qrPayload);
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=" . $encodedData;

    // Return success with QR data
    echo json_encode([
        'success' => true,
        'parcel' => [
            'trackingNumber' => $parcel['TrackingNumber'],
            'matricNumber' => $parcel['MatricNumber'],
            'receiverName' => $parcel['receiverName'],
            'name' => $parcel['name'],
            'deliveryLocation' => $parcel['deliveryLocation'],
            'weight' => $parcel['weight'],
            'status' => $parcel['status'],
            'date' => $parcel['date'],
            'time' => $parcel['time']
        ],
        'qrPayload' => $qrPayload,
        'qrCodeUrl' => $qrCodeUrl
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

