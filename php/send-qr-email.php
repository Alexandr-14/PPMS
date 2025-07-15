<?php
// Clean output buffer and set JSON header
ob_clean();
header('Content-Type: application/json');

session_start();
require_once 'db_connect.php';
require_once 'smtp-config.php';
require_once 'simple-email-sender.php';

// Check if user is staff or admin
if (!isset($_SESSION['staff_role']) || ($_SESSION['staff_role'] !== 'Staff' && $_SESSION['staff_role'] !== 'Admin')) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Staff privileges required.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trackingNumber = trim($_POST['trackingNumber'] ?? '');
    $receiverEmail = trim($_POST['receiverEmail'] ?? '');
    $qrImage = $_POST['qrImage'] ?? '';
    $additionalMessage = trim($_POST['additionalMessage'] ?? '');

    // Validate required fields
    if (empty($trackingNumber) || empty($receiverEmail) || empty($qrImage)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit();
    }

    // Validate email format
    if (!filter_var($receiverEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address format.']);
        exit();
    }

    try {
        // Get parcel information
        $stmt = $conn->prepare("
            SELECT p.*, r.name as receiverName
            FROM Parcel p
            LEFT JOIN Receiver r ON p.ICNumber = r.ICNumber
            WHERE p.TrackingNumber = ?
        ");
        $stmt->bind_param("s", $trackingNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Parcel not found.']);
            exit();
        }

        $parcel = $result->fetch_assoc();
        $receiverName = $parcel['receiverName'] ?: 'Valued Customer';

        // Check if QR email was already sent recently (within last 5 minutes)
        $recentEmailCheck = $conn->prepare("
            SELECT COUNT(*) as recent_count
            FROM Notification
            WHERE TrackingNumber = ?
            AND notificationType = 'qr_email'
            AND sentTimestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $recentEmailCheck->bind_param("s", $trackingNumber);
        $recentEmailCheck->execute();
        $recentResult = $recentEmailCheck->get_result();
        $recentCount = $recentResult->fetch_assoc()['recent_count'];

        if ($recentCount > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'QR email was already sent recently. Please wait 5 minutes before sending again.'
            ]);
            exit();
        }

        // Process QR image data
        $qrData = $qrImage;
        if (strpos($qrImage, 'data:image/png;base64,') === 0) {
            $qrData = substr($qrImage, strlen('data:image/png;base64,'));
        }

        // Create temporary QR image file
        $tempDir = '../temp/';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $qrFileName = 'QR_' . $trackingNumber . '_' . time() . '.png';
        $qrFilePath = $tempDir . $qrFileName;
        
        $qrImageData = base64_decode($qrData);
        if ($qrImageData === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid QR code data.']);
            exit();
        }

        if (file_put_contents($qrFilePath, $qrImageData) === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to create QR image file.']);
            exit();
        }

        // Email configuration (you'll need to configure this based on your email server)
        $to = $receiverEmail;
        $subject = "PPMS - Your Parcel Verification QR Code (Tracking: $trackingNumber)";
        
        // Create email body
        $emailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: linear-gradient(135deg, #6A1B9A 0%, #FF9800 100%); color: white; padding: 20px; text-align: center; border-radius: 8px; }
                .content { padding: 20px; background: #f9f9f9; border-radius: 8px; margin: 20px 0; }
                .qr-section { text-align: center; background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 2px dashed #6A1B9A; }
                .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .info-table td { padding: 10px; border: 1px solid #ddd; }
                .info-table td:first-child { background: #f8f9fa; font-weight: bold; width: 40%; }
                .instructions { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>Perwira Parcel Management System</h2>
                <h3>Your Parcel Verification QR Code</h3>
            </div>
            
            <div class='content'>
                <p>Dear $receiverName,</p>
                
                <p>Your parcel verification QR code is ready! Please find the details below:</p>
                
                <table class='info-table'>
                    <tr><td>Tracking Number:</td><td>$trackingNumber</td></tr>
                    <tr><td>Receiver IC:</td><td>{$parcel['ICNumber']}</td></tr>
                    <tr><td>Pickup Location:</td><td>{$parcel['deliveryLocation']}</td></tr>
                    <tr><td>Status:</td><td>{$parcel['status']}</td></tr>
                    <tr><td>Generated:</td><td>" . date('Y-m-d H:i:s') . "</td></tr>
                </table>";

        if (!empty($additionalMessage)) {
            $emailBody .= "
                <div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                    <strong>Additional Message:</strong><br>
                    " . htmlspecialchars($additionalMessage) . "
                </div>";
        }

        $emailBody .= "
                <div class='qr-section'>
                    <h4>Your Verification QR Code</h4>
                    <p>Present this QR code at the parcel counter for verification</p>
                    <img src='cid:qr_code' alt='QR Code' style='max-width: 250px; border: 2px solid #6A1B9A; border-radius: 8px;'>
                </div>
                
                <div class='instructions'>
                    <h4>📋 Instructions:</h4>
                    <ol>
                        <li><strong>Save this QR code</strong> to your phone or print this email</li>
                        <li><strong>Visit the parcel counter</strong> at Kolej Kediaman Luar Kampus - UTHM</li>
                        <li><strong>Present the QR code</strong> to our staff for scanning</li>
                        <li><strong>Collect your parcel</strong> after successful verification</li>
                    </ol>
                    <p><em>Note: This QR code is unique to your parcel and cannot be used by others.</em></p>
                </div>
                
                <p>If you have any questions or need assistance, please contact our support team.</p>
                
                <p>Thank you for using Perwira Parcel Management System!</p>
            </div>
            
            <div class='footer'>
                <p>This is an automated email from PPMS. Please do not reply to this email.</p>
                <p>© " . date('Y') . " Perwira Parcel Management System. All rights reserved.</p>
            </div>
        </body>
        </html>";

        // Email headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/related; boundary=\"boundary123\"\r\n";
        $headers .= "From: PPMS System <noreply@ppms.com>\r\n";
        $headers .= "Reply-To: support@ppms.com\r\n";

        // Create multipart email with embedded image
        $emailContent = "--boundary123\r\n";
        $emailContent .= "Content-Type: text/html; charset=UTF-8\r\n";
        $emailContent .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $emailContent .= $emailBody . "\r\n\r\n";

        $emailContent .= "--boundary123\r\n";
        $emailContent .= "Content-Type: image/png; name=\"qr_code.png\"\r\n";
        $emailContent .= "Content-Transfer-Encoding: base64\r\n";
        $emailContent .= "Content-ID: <qr_code>\r\n";
        $emailContent .= "Content-Disposition: inline; filename=\"qr_code.png\"\r\n\r\n";
        $emailContent .= chunk_split(base64_encode($qrImageData)) . "\r\n";
        $emailContent .= "--boundary123--\r\n";

        // Send email using Simple Email Sender (XAMPP-friendly)
        try {
            $emailConfig = getEmailConfig();
            $emailSender = new SimpleEmailSender(
                $emailConfig['gmail_email'],
                $emailConfig['app_password'],
                $emailConfig['from_name']
            );

        // Create email with embedded QR image
        $emailBodyWithImage = str_replace(
            "src='cid:qr_code'",
            "src='data:image/png;base64," . base64_encode($qrImageData) . "'",
            $emailBody
        );

        // Send the email
        $emailResult = $emailSender->sendEmail(
            $receiverEmail,
            $receiverName,
            $subject,
            $emailBodyWithImage
        );

        if ($emailResult['success']) {
            // Log successful email
            $logStmt = $conn->prepare("
                INSERT INTO Notification (
                    ICNumber, TrackingNumber, notificationType, messageContent,
                    sentTimestamp, notificationStatus, isRead, deliveryMethod
                ) VALUES (?, ?, 'qr_email', ?, NOW(), 'sent', 0, 'email')
            ");
            $logMessage = "QR verification code emailed to: $receiverEmail";
            $logStmt->bind_param("sss", $parcel['ICNumber'], $trackingNumber, $logMessage);
            $logStmt->execute();

            // Clean up temporary QR file
            unlink($qrFilePath);

            // Clean any output buffer before JSON response
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'QR verification code sent successfully to ' . $receiverEmail
            ]);
            exit;
        } else {
            // Email failed
            unlink($qrFilePath);

            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Email sending failed: ' . $emailResult['error']
            ]);
            exit;
        }

        } catch (Exception $e) {
            // Clean up temporary file if it exists
            if (file_exists($qrFilePath)) {
                unlink($qrFilePath);
            }

            // Log the error for debugging
            error_log("Email sending error: " . $e->getMessage());

            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Email error: ' . $e->getMessage()
            ]);
            exit;
        }

    } catch (Exception $e) {
        // Clean up temporary file if it exists
        if (isset($qrFilePath) && file_exists($qrFilePath)) {
            unlink($qrFilePath);
        }
        
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
        exit;
    }
} else {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$conn->close();
?>
