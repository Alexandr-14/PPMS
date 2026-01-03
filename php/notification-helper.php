<?php
/**
 * Notification Helper Functions for PPMS
 * Handles creating and sending notifications to receivers
 */

require_once __DIR__ . '/db_connect.php';

/**
 * Send notification to receiver when parcel arrives/is added
 * @param string $icNumber 
 * @param string $trackingNumber 
 * @param string $parcelName 
 * @param string $deliveryLocation 
 * @return bool - Success status
 */
function sendParcelArrivalNotification($icNumber, $trackingNumber, $parcelName, $deliveryLocation) {
    global $conn;

    try {
        // Create smart notification message
        $packageText = ($parcelName && $parcelName !== 'Package') ? $parcelName : 'your package';
        $message = "({$trackingNumber}) Parcel has arrived and is ready for pickup.";

        // Insert notification into database
        $stmt = $conn->prepare("
            INSERT INTO notification (
                MatricNumber,
                TrackingNumber,
                notificationType,
                messageContent,
                sentTimestamp,
                notificationStatus,
                isRead,
                deliveryMethod
            ) VALUES (?, ?, 'arrival', ?, NOW(), 'sent', 0, 'system')
        ");

        $stmt->bind_param("sss", $icNumber, $trackingNumber, $message);

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            error_log("Failed to send arrival notification: " . $stmt->error);
            $stmt->close();
            return false;
        }

    } catch (Exception $e) {
        error_log("Error sending arrival notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send notification when parcel status changes to Retrieved
 * @param string $icNumber - Receiver's IC number
 * @param string $trackingNumber - Parcel tracking number
 * @param string $parcelName - Name/description of the parcel
 * @return bool - Success status
 */
function sendParcelRetrievedNotification($icNumber, $trackingNumber, $parcelName) {
    global $conn;

    try {
        // Create smart notification message
        $message = "({$trackingNumber}) Parcel has been retrieved. Thank you!";

        // Insert notification into database
        $stmt = $conn->prepare("
            INSERT INTO notification (
                MatricNumber,
                TrackingNumber,
                notificationType,
                messageContent,
                sentTimestamp,
                notificationStatus,
                isRead,
                deliveryMethod
            ) VALUES (?, ?, 'pickup', ?, NOW(), 'sent', 0, 'system')
        ");

        $stmt->bind_param("sss", $icNumber, $trackingNumber, $message);

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            error_log("Failed to send retrieved notification: " . $stmt->error);
            $stmt->close();
            return false;
        }

    } catch (Exception $e) {
        error_log("Error sending retrieved notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send notification when parcel is ready for pickup (status change to Pending)
 * @param string $icNumber - Receiver's IC number
 * @param string $trackingNumber - Parcel tracking number
 * @param string $parcelName - Name/description of the parcel
 * @param string $deliveryLocation - Where parcel can be collected
 * @return bool - Success status
 */
function sendParcelReadyNotification($icNumber, $trackingNumber, $parcelName, $deliveryLocation) {
    global $conn;

    try {
        // Create smart notification message
        $message = "({$trackingNumber}) Parcel is ready for pickup.";

        // Insert notification into database
        $stmt = $conn->prepare("
            INSERT INTO notification (
                MatricNumber,
                TrackingNumber,
                notificationType,
                messageContent,
                sentTimestamp,
                notificationStatus,
                isRead,
                deliveryMethod
            ) VALUES (?, ?, 'delivery', ?, NOW(), 'sent', 0, 'system')
        ");

        $stmt->bind_param("sss", $icNumber, $trackingNumber, $message);

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            error_log("Failed to send ready notification: " . $stmt->error);
            $stmt->close();
            return false;
        }

    } catch (Exception $e) {
        error_log("Error sending ready notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get receiver name by Matric number
 * @param string $matricNumber - Receiver's Matric number
 * @return string - Receiver's name or 'Unknown'
 */
function getReceiverName($matricNumber) {
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT name FROM receiver WHERE MatricNumber = ?");
        $stmt->bind_param("s", $matricNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row['name'];
        } else {
            $stmt->close();
            return 'Unknown';
        }

    } catch (Exception $e) {
        error_log("Error getting receiver name: " . $e->getMessage());
        return 'Unknown';
    }
}

/**
 * Check if receiver exists
 * @param string $matricNumber - Receiver's Matric number
 * @return bool - True if receiver exists
 */
function receiverExists($matricNumber) {
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT MatricNumber FROM receiver WHERE MatricNumber = ?");
        $stmt->bind_param("s", $matricNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;

    } catch (Exception $e) {
        error_log("Error checking receiver existence: " . $e->getMessage());
        return false;
    }
}
?>
