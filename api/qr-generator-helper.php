<?php
/**
 * QR Code Generation Helper
 * Generates QR codes automatically for parcels
 */

/**
 * Auto-generate QR code for a parcel
 * @param string $trackingNumber - Tracking number of the parcel
 * @param string $matricNumber - Matric number of receiver
 * @param string $deliveryLocation - Delivery location
 * @param object $conn - Database connection
 * @param string $parcelName - Optional parcel name/description
 * @return array - Result with success status and message
 */
function autoGenerateQRCode($trackingNumber, $matricNumber, $deliveryLocation, $conn, $parcelName = '') {
    try {
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

        // Generate QR code URL using external API
        $qrDataUrl = urlencode($secureVerificationData);
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . $qrDataUrl;

        // Fetch QR code image
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $qrCodeUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $qrImageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $qrImageData === false || empty($qrImageData)) {
            return [
                'success' => false,
                'message' => 'Failed to generate QR code image from API.'
            ];
        }

        // Create QR codes directory if it doesn't exist
        $qrDir = __DIR__ . '/../assets/qr-codes/';
        if (!file_exists($qrDir)) {
            mkdir($qrDir, 0755, true);
        }

        // Save QR code as image file
        $qrFileName = 'QR_' . $trackingNumber . '.png';
        $qrFilePath = $qrDir . $qrFileName;

        if (file_put_contents($qrFilePath, $qrImageData) === false) {
            return [
                'success' => false,
                'message' => 'Failed to save QR code file.'
            ];
        }

        // Update parcel with QR code path and secure verification data
        $updateQuery = "UPDATE parcel SET QR = ?, qr_verification_data = ? WHERE TrackingNumber = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $qrRelativePath = 'assets/qr-codes/' . $qrFileName;
        $updateStmt->bind_param("sss", $qrRelativePath, $secureVerificationData, $trackingNumber);

        if (!$updateStmt->execute()) {
            return [
                'success' => false,
                'message' => 'Failed to update parcel with QR code: ' . $updateStmt->error
            ];
        }

        // Send combined notification to receiver about parcel arrival with QR code
        try {
            // Check if notification already sent (de-duplicate)
            $alreadyNotified = false;
            $checkNotifStmt = $conn->prepare("
                SELECT 1
                FROM notification
                WHERE MatricNumber = ?
                  AND TrackingNumber = ?
                  AND notificationType IN ('arrival', 'qr_generated')
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
                // Create smart combined message
                $message = "({$trackingNumber}) Your parcel has arrived with QR code. Ready for pickup!";

                $logStmt = $conn->prepare("
                    INSERT INTO notification (
                        MatricNumber, TrackingNumber, notificationType, messageContent,
                        sentTimestamp, notificationStatus, isRead, deliveryMethod
                    ) VALUES (
                        ?, ?, 'arrival', ?,
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

        return [
            'success' => true,
            'message' => 'QR code generated successfully.',
            'qrPath' => $qrRelativePath,
            'verificationData' => $secureVerificationData
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'QR generation error: ' . $e->getMessage()
        ];
    }
}

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

    // Try to load from .env file
    if (file_exists(__DIR__ . '/../.env')) {
        $envFile = file_get_contents(__DIR__ . '/../.env');
        $lines = explode("\n", $envFile);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'QR_SECRET_KEY=') === 0) {
                return trim(substr($line, strlen('QR_SECRET_KEY=')));
            }
        }
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

