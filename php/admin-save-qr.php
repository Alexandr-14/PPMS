<?php
session_start();
require_once 'db_connect.php';

// Check if user is admin
if (!isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trackingNumber = trim($_POST['trackingNumber'] ?? '');
    $qrCode = $_POST['qrCode'] ?? '';
    $verificationData = $_POST['verificationData'] ?? '';

    // Validate required fields
    if (empty($trackingNumber) || empty($qrCode)) {
        echo json_encode(['success' => false, 'message' => 'Tracking number and QR code are required.']);
        exit();
    }

    try {
        // Check if parcel exists
        $checkQuery = "SELECT TrackingNumber FROM Parcel WHERE TrackingNumber = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $trackingNumber);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Parcel not found.']);
            exit();
        }

        // Process QR code data (remove data URL prefix if present)
        $qrData = $qrCode;
        if (strpos($qrCode, 'data:image/png;base64,') === 0) {
            $qrData = substr($qrCode, strlen('data:image/png;base64,'));
        }

        // Create QR codes directory if it doesn't exist
        $qrDir = '../assets/qr-codes/';
        if (!file_exists($qrDir)) {
            mkdir($qrDir, 0755, true);
        }

        // Save QR code as image file
        $qrFileName = 'QR_' . $trackingNumber . '.png';
        $qrFilePath = $qrDir . $qrFileName;
        
        $qrImageData = base64_decode($qrData);
        if ($qrImageData === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid QR code data.']);
            exit();
        }

        if (file_put_contents($qrFilePath, $qrImageData) === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to save QR code file.']);
            exit();
        }

        // Update parcel with QR code path and verification data
        $updateQuery = "UPDATE Parcel SET QR = ?, qr_verification_data = ? WHERE TrackingNumber = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $qrRelativePath = 'assets/qr-codes/' . $qrFileName;
        $updateStmt->bind_param("sss", $qrRelativePath, $verificationData, $trackingNumber);

        if ($updateStmt->execute()) {
            // Log QR generation activity
            $logStmt = $conn->prepare("
                INSERT INTO Notification (
                    ICNumber, TrackingNumber, notificationType, messageContent,
                    sentTimestamp, notificationStatus, isRead, deliveryMethod
                ) VALUES (
                    (SELECT ICNumber FROM Parcel WHERE TrackingNumber = ?),
                    ?, 'qr_generated', 'Verification QR code generated for parcel',
                    NOW(), 'sent', 0, 'system'
                )
            ");
            $logStmt->bind_param("ss", $trackingNumber, $trackingNumber);
            $logStmt->execute();

            echo json_encode([
                'success' => true,
                'message' => 'Secure QR verification code generated and saved successfully.',
                'qrPath' => $qrRelativePath,
                'hasVerificationData' => !empty($verificationData)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update parcel with QR code.']);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
