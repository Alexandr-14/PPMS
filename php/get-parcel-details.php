<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parcel ID is required'
    ]);
    exit;
}

try {
    $parcelId = $_GET['id'];
    
    // Get parcel details
    $stmt = $conn->prepare("SELECT * FROM parcels WHERE id = :id");
    $stmt->execute(['id' => $parcelId]);
    $parcel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parcel) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Parcel not found'
        ]);
        exit;
    }

    // Return JSON response
    echo json_encode([
        'status' => 'success',
        'data' => $parcel
    ]);
} catch (PDOException $e) {
    // Handle database errors
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
