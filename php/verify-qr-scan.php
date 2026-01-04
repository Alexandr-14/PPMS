<?php
/**
 * QR Code Verification Endpoint
 * Verifies QR code data and validates parcel information
 * 
 * POST Parameters:
 * - qrData: JSON string containing QR code data with signature
 * 
 * Returns: JSON response with verification result
 */

header('Content-Type: application/json');
session_start();

// Include database connection
require_once __DIR__ . '/db_connect.php';

// Security: Verify staff is logged in
if (!isset($_SESSION['staff_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'message' => 'Staff authentication required'
    ]);
    exit();
}

try {
    // Get QR data from request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['qrData'])) {
        throw new Exception('QR data not provided');
    }

    // Parse QR data
    $qrData = json_decode($input['qrData'], true);
    
    if (!$qrData) {
        throw new Exception('Invalid QR data format');
    }

    // Extract verification components
    $trackingNumber = $qrData['tracking'] ?? null;
    $matricNumber = $qrData['matric'] ?? null;
    $timestamp = $qrData['timestamp'] ?? null;
    $signature = $qrData['signature'] ?? null;
    $location = $qrData['location'] ?? null;

    // Validate required fields
    if (!$trackingNumber || !$matricNumber || !$timestamp || !$signature) {
        throw new Exception('Missing required QR data fields');
    }

    // Get secret key from config
    $secretKey = getQRSecretKey();
    
    // Reconstruct data for signature verification (sort keys for consistent hashing)
    $dataArray = [
        'tracking' => $trackingNumber,
        'matric' => $matricNumber,
        'timestamp' => $timestamp,
        'location' => $location
    ];
    ksort($dataArray);
    $dataToVerify = json_encode($dataArray, JSON_UNESCAPED_SLASHES);

    // Verify signature
    $expectedSignature = hash_hmac('sha256', $dataToVerify, $secretKey);
    
    if (!hash_equals($signature, $expectedSignature)) {
        throw new Exception('Invalid QR code signature - possible tampering detected');
    }

    // Verify timestamp is within 30 days
    $qrTimestamp = strtotime($timestamp);
    $currentTime = time();
    $maxAge = 30 * 24 * 60 * 60; // 30 days in seconds
    
    if ($currentTime - $qrTimestamp > $maxAge) {
        throw new Exception('QR code has expired');
    }

    // Fetch parcel from database
    $query = "
        SELECT 
            p.TrackingNumber,
            p.MatricNumber,
            p.name,
            p.weight,
            p.deliveryLocation,
            p.status,
            p.date,
            p.time,
            r.name as receiverName,
            r.phoneNumber
        FROM parcel p
        LEFT JOIN receiver r ON p.MatricNumber = r.MatricNumber
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
        throw new Exception('Parcel not found');
    }

    $parcel = $result->fetch_assoc();

    // Verify matric number matches
    if ($parcel['MatricNumber'] !== $matricNumber) {
        throw new Exception('QR code matric number does not match parcel receiver');
    }

    // Verify parcel status is Pending
    if ($parcel['status'] !== 'Pending') {
        throw new Exception('Parcel is not available for retrieval (status: ' . $parcel['status'] . ')');
    }

    // Verify location matches (if provided in QR)
    if ($location && $parcel['deliveryLocation'] !== $location) {
        throw new Exception('QR code location does not match parcel location');
    }

    // All verifications passed
    echo json_encode([
        'success' => true,
        'parcel' => [
            'trackingNumber' => $parcel['TrackingNumber'],
            'matric' => $parcel['MatricNumber'],
            'receiverName' => $parcel['receiverName'],
            'name' => $parcel['name'],
            'weight' => $parcel['weight'],
            'location' => $parcel['deliveryLocation'],
            'status' => $parcel['status'],
            'date' => $parcel['date'],
            'time' => $parcel['time'],
            'phone' => $parcel['phoneNumber']
        ],
        'message' => 'QR code verified successfully',
        'verificationToken' => bin2hex(random_bytes(32)) // Token for retrieval confirmation
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'QR code verification failed'
    ]);
}

/**
 * Get QR Secret Key
 * In production, use environment variables
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

