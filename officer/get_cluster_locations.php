<?php
include '../includes/db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['cluster_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Cluster ID is required']);
    exit;
}

$clusterId = mysqli_real_escape_string($conn, $_GET['cluster_id']);

$query = "SELECT 
            LocationInCluster as LocationName,
            Latitude,
            Longitude
          FROM clusters 
          WHERE ClusterID = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $clusterId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

$locations = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Convert latitude and longitude to float values
    $locations[] = [
        'LocationName' => $row['LocationName'],
        'Latitude' => floatval($row['Latitude']),
        'Longitude' => floatval($row['Longitude'])
    ];
}

if (empty($locations)) {
    http_response_code(404);
    echo json_encode(['error' => 'No locations found for this cluster']);
    exit;
}

echo json_encode($locations);
?>