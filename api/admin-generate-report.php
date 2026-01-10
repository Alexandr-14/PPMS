<?php
session_start();
require_once __DIR__ . '/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = trim($_POST['startDate'] ?? '');
    $endDate = trim($_POST['endDate'] ?? '');
    $status = trim($_POST['status'] ?? '');

    try {
        // Build the query based on filters - Focus on Retrieval table with retrievalID
        $query = "
            SELECT
                ret.retrievalID,
                ret.trackingNumber as TrackingNumber,
                ret.MatricNumber,
                ret.staffID,
                ret.retrieveDate,
                ret.retrieveTime,
                ret.status,
                r.name as receiverName,
                p.name as parcelName,
                p.weight,
                p.deliveryLocation,
                p.date as parcelDate,
                p.addedBy
            FROM retrievalrecord ret
            LEFT JOIN receiver r ON ret.MatricNumber = r.MatricNumber
            LEFT JOIN parcel p ON ret.trackingNumber = p.TrackingNumber
            WHERE 1=1
        ";

        $params = [];
        $types = "";

        // Add date filters (based on retrieve date)
        if (!empty($startDate)) {
            $query .= " AND ret.retrieveDate >= ?";
            $params[] = $startDate;
            $types .= "s";
        }

        if (!empty($endDate)) {
            $query .= " AND ret.retrieveDate <= ?";
            $params[] = $endDate;
            $types .= "s";
        }

        // Add status filter
        if (!empty($status)) {
            $query .= " AND ret.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        // Add staff ID filter
        $staffID = trim($_POST['staffID'] ?? '');
        if (!empty($staffID)) {
            $query .= " AND ret.staffID = ?";
            $params[] = $staffID;
            $types .= "s";
        }

        // Add receiver Matric filter
        $receiverIC = trim($_POST['receiverIC'] ?? '');
        if (!empty($receiverIC)) {
            $query .= " AND ret.MatricNumber = ?";
            $params[] = $receiverIC;
            $types .= "s";
        }

        // Add addedBy filter (who registered the parcel)
        $addedBy = trim($_POST['addedBy'] ?? '');
        if (!empty($addedBy)) {
            $query .= " AND p.addedBy = ?";
            $params[] = $addedBy;
            $types .= "s";
        }

        $query .= " ORDER BY ret.retrieveDate DESC, ret.retrieveTime DESC";

        // Prepare and execute query
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $report = [];
        while ($row = $result->fetch_assoc()) {
            $report[] = [
                'retrievalID' => $row['retrievalID'],
                'TrackingNumber' => $row['TrackingNumber'],
                'MatricNumber' => $row['MatricNumber'],
                'receiverName' => $row['receiverName'],
                'parcelName' => $row['parcelName'],
                'weight' => $row['weight'],
                'deliveryLocation' => $row['deliveryLocation'],
                'parcelDate' => $row['parcelDate'],
                'addedBy' => $row['addedBy'],
                'staffID' => $row['staffID'],
                'retrieveDate' => $row['retrieveDate'],
                'retrieveTime' => $row['retrieveTime'],
                'status' => $row['status']
            ];
        }

        echo json_encode([
            'success' => true,
            'report' => $report,
            'filters' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'status' => $status,
                'staffID' => $staffID,
                'receiverIC' => $receiverIC,
                'addedBy' => $addedBy
            ],
            'totalRecords' => count($report)
        ]);

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

