<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


include '../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['clusterId']) || !isset($data['waypoints'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or incomplete data']);
    exit;
}

try {
    mysqli_begin_transaction($conn);

    // Insert into route_optimization
    $query = "INSERT INTO route_optimization (
        ClusterID, TotalDistance, EstimatedFuel, EstimatedTime, TotalCost, TruckID, FuelPrice
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'sdddsdd', 
        $data['clusterId'],
        $data['distance'],
        $data['fuel'],
        $data['time'],
        $data['cost'],
        $data['truckId'],
        $data['fuelPrice']
    );
    mysqli_stmt_execute($stmt);
    $routeId = mysqli_insert_id($conn);

    // Insert waypoints
    $waypointQuery = "INSERT INTO route_waypoints (RouteID, LocationName, Latitude, Longitude, SequenceNumber) 
                      VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $waypointQuery);

    foreach ($data['waypoints'] as $index => $waypoint) {
        mysqli_stmt_bind_param($stmt, 'isddi',
            $routeId,
            $waypoint['LocationName'],
            $waypoint['Latitude'],
            $waypoint['Longitude'],
            $index + 1
        );
        mysqli_stmt_execute($stmt);
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'routeId' => $routeId]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log('Route Saving Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save route']);
}
?>
