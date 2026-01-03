<?php
session_start();
require_once __DIR__ . '/db_connect.php';

// Auth: receiver or staff/admin only
$isReceiver = isset($_SESSION['receiver_matric']) && !empty($_SESSION['receiver_matric']);
$isStaff = isset($_SESSION['staff_id']) && !empty($_SESSION['staff_id']);

if (!$isReceiver && !$isStaff) {
    http_response_code(401);
    echo 'Access denied. Please log in.';
    exit();
}

$trackingNumber = trim($_GET['trackingNumber'] ?? '');
if ($trackingNumber === '') {
    http_response_code(400);
    echo 'Tracking number is required.';
    exit();
}

$inline = isset($_GET['inline']) && $_GET['inline'] === '1';

try {
    if ($isReceiver) {
        // Receivers can only download their own parcel QR
        $receiverMatric = $_SESSION['receiver_matric'];
        $stmt = $conn->prepare("SELECT QR FROM parcel WHERE TrackingNumber = ? AND MatricNumber = ? LIMIT 1");
        $stmt->bind_param("ss", $trackingNumber, $receiverMatric);
    } else {
        // Staff/Admin can download any parcel QR
        $stmt = $conn->prepare("SELECT QR FROM parcel WHERE TrackingNumber = ? LIMIT 1");
        $stmt->bind_param("s", $trackingNumber);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        http_response_code(404);
        echo 'Parcel not found or access denied.';
        exit();
    }

    $qrRelativePath = $row['QR'] ?? '';
    if (empty($qrRelativePath)) {
        http_response_code(404);
        echo 'QR code not generated for this parcel.';
        exit();
    }

    // Build full path safely (QR paths are stored like: assets/qr-codes/QR_<tracking>.png)
    $fullPath = __DIR__ . '/../' . ltrim($qrRelativePath, '/\\');
    if (!file_exists($fullPath)) {
        http_response_code(404);
        echo 'QR file not found on server.';
        exit();
    }

    $downloadName = 'PPMS_Verification_' . $trackingNumber . '.png';

    // Headers for download / inline view
    header('Content-Type: image/png');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($fullPath));

    readfile($fullPath);
    exit();
} catch (Exception $e) {
    http_response_code(500);
    echo 'Server error.';
    exit();
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}


