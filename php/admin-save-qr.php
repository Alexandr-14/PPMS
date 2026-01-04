<?php
// Set error handling to return JSON
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'PHP Error: ' . $errstr . ' in ' . $errfile . ':' . $errline]);
    exit();
});

session_start();
require_once __DIR__ . '/db_connect.php';

// Check if user is logged in as staff or admin
if (!isset($_SESSION['staff_role']) || ($_SESSION['staff_role'] !== 'Staff' && $_SESSION['staff_role'] !== 'Admin')) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Staff privileges required.']);
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
        // Check if parcel exists and get its details
        $checkQuery = "
            SELECT
                TrackingNumber,
                MatricNumber,
                deliveryLocation
            FROM parcel
            WHERE TrackingNumber = ?
        ";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $trackingNumber);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Parcel not found.']);
            exit();
        }

        $parcelData = $checkResult->fetch_assoc();
        $matricNumber = $parcelData['MatricNumber'];
        $deliveryLocation = $parcelData['deliveryLocation'];

        // Generate secure verification data with server-side signature
        $timestamp = date('c'); // ISO 8601 format
        $secretKey = getQRSecretKey();

        // Create data object for signing (sorted for consistent hashing)
        $dataArray = [
            'location' => $deliveryLocation,
            'matric' => $matricNumber,
            'timestamp' => $timestamp,
            'tracking' => $trackingNumber
        ];
        ksort($dataArray); // Sort by keys for consistent hashing
        $dataToSign = json_encode($dataArray, JSON_UNESCAPED_SLASHES);

        // Generate HMAC-SHA256 signature
        $signature = hash_hmac('sha256', $dataToSign, $secretKey);

        // Create complete verification data with signature
        $secureVerificationData = json_encode([
            'tracking' => $trackingNumber,
            'matric' => $matricNumber,
            'timestamp' => $timestamp,
            'location' => $deliveryLocation,
            'signature' => $signature,
            'version' => '1.0'
        ]);

        // Note: We're using the frontend-generated QR code image for display
        // The actual scannable QR code should contain the secure verification data
        // This is handled by the receiver dashboard which fetches the verification data

        // Process QR code data (remove data URL prefix if present)
        $qrData = $qrCode;
        if (strpos($qrCode, 'data:image/png;base64,') === 0) {
            $qrData = substr($qrCode, strlen('data:image/png;base64,'));
        }

        // Create QR codes directory if it doesn't exist
        $qrDir = __DIR__ . '/../assets/qr-codes/';
        if (!file_exists($qrDir)) {
            mkdir($qrDir, 0755, true);
        }

        // Save QR code as image file (frontend-generated for display purposes)
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

        // Update parcel with QR code path and secure verification data
        $updateQuery = "UPDATE parcel SET QR = ?, qr_verification_data = ? WHERE TrackingNumber = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $qrRelativePath = 'assets/qr-codes/' . $qrFileName;
        $updateStmt->bind_param("sss", $qrRelativePath, $secureVerificationData, $trackingNumber);

        if ($updateStmt->execute()) {
            // Send notification to receiver about QR code generation (de-duplicate)
            try {
                // Skip if we've already notified for this parcel
                $alreadyNotified = false;
                $checkNotifStmt = $conn->prepare("
                    SELECT 1
                    FROM notification
                    WHERE MatricNumber = ?
                      AND TrackingNumber = ?
                      AND notificationType = 'qr_generated'
                    LIMIT 1
                ");
                if ($checkNotifStmt) {
                    $checkNotifStmt->bind_param("ss", $matricNumber, $trackingNumber);
                    $checkNotifStmt->execute();
                    $checkNotifStmt->store_result();
                    $alreadyNotified = ($checkNotifStmt->num_rows > 0);
                    $checkNotifStmt->close();
                }

                if (!$alreadyNotified) {
                    $message = "({$trackingNumber}) QR code has been generated. Your parcel is ready for pickup verification.";

                    $logStmt = $conn->prepare("
                        INSERT INTO notification (
                            MatricNumber, TrackingNumber, notificationType, messageContent,
                            sentTimestamp, notificationStatus, isRead, deliveryMethod
                        ) VALUES (
                            ?, ?, 'qr_generated', ?,
                            NOW(), 'sent', 0, 'system'
                        )
                    ");
                    if ($logStmt) {
                        $logStmt->bind_param("sss", $matricNumber, $trackingNumber, $message);
                        $logStmt->execute();
                        $logStmt->close();
                    }
                }
            } catch (Exception $logError) {
                // Log notification is optional, don't fail the QR generation
                error_log('QR notification log failed: ' . $logError->getMessage());
            }

            echo json_encode([
                'success' => true,
                'message' => 'Secure QR verification code generated and saved successfully.',
                'qrPath' => $qrRelativePath,
                'verificationData' => $secureVerificationData,
                'hasVerificationData' => !empty($secureVerificationData)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update parcel with QR code: ' . $updateStmt->error]);
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

/**
 * Get QR Secret Key for HMAC signature
 * In production, use environment variables or secure config
 */
function getQRSecretKey() {
    // Try to get from environment variable first
    $secretKey = getenv('QR_SECRET_KEY');

    if ($secretKey) {
        return $secretKey;
    }

    // Try to load from config file
    if (file_exists(__DIR__ . '/../config/qr-config.php')) {
        require_once __DIR__ . '/../config/qr-config.php';
        if (defined('QR_SECRET_KEY')) {
            return constant('QR_SECRET_KEY');
        }
    }

    // Fallback to default (MUST be changed in production!)
    return 'ppms-qr-secret-key-change-in-production-' . date('Y');
}
?>

