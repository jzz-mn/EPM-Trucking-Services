<?php
include '../includes/db_connection.php';

header('Content-Type: application/json');

// Ensure a cluster_id parameter is passed
if (!isset($_GET['cluster_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Cluster ID is required']);
    exit;
}

$clusterId = mysqli_real_escape_string($conn, $_GET['cluster_id']);

// Fetch locations for the given cluster
$locationsQuery = "SELECT LocationInCluster AS LocationName, Latitude, Longitude 
                   FROM clusters 
                   WHERE ClusterID = ?";

$stmt = mysqli_prepare($conn, $locationsQuery);
mysqli_stmt_bind_param($stmt, 's', $clusterId);
mysqli_stmt_execute($stmt);
$locationsResult = mysqli_stmt_get_result($stmt);

$locations = [];
while ($row = mysqli_fetch_assoc($locationsResult)) {
    $locations[] = [
        'LocationName' => $row['LocationName'],
        'Latitude' => floatval($row['Latitude']),
        'Longitude' => floatval($row['Longitude']),
    ];
}

if (empty($locations)) {
    http_response_code(404);
    echo json_encode(['error' => 'No locations found for the specified Cluster ID']);
    exit;
}

echo json_encode($locations);
?>
