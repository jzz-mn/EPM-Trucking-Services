<?php
header('Content-Type: application/json');
include '../includes/db_connection.php';

// Validate route_id
if (!isset($_GET['route_id']) || empty($_GET['route_id'])) {
    echo json_encode(['error' => 'Invalid route ID.']);
    exit;
}

$route_id = intval($_GET['route_id']);

// Fetch route data
$route_query = "SELECT * FROM route_optimization WHERE RouteID = $route_id";
$route_result = mysqli_query($conn, $route_query);

if (!$route_result || mysqli_num_rows($route_result) == 0) {
    echo json_encode(['error' => 'Route not found or query failed.']);
    exit;
}

$route_data = mysqli_fetch_assoc($route_result);

// Fetch waypoints for the route
$waypoints_query = "
    SELECT LocationName, Latitude, Longitude, SequenceNumber 
    FROM route_waypoints 
    WHERE WaypointGroupID = {$route_data['WaypointGroupID']} 
    ORDER BY SequenceNumber";
$waypoints_result = mysqli_query($conn, $waypoints_query);

if (!$waypoints_result || mysqli_num_rows($waypoints_result) == 0) {
    echo json_encode(['error' => 'No waypoints found for the route.']);
    exit;
}

$waypoints = [];
$total_distance = 0.0;
$prev_lat = null;
$prev_lng = null;

while ($row = mysqli_fetch_assoc($waypoints_result)) {
    $lat = floatval($row['Latitude']);
    $lng = floatval($row['Longitude']);
    $sequence = intval($row['SequenceNumber']);

    if ($prev_lat !== null && $prev_lng !== null) {
        // Haversine formula to calculate the distance between waypoints
        $earth_radius = 6371; // km
        $dlat = deg2rad($lat - $prev_lat);
        $dlng = deg2rad($lng - $prev_lng);
        $a = sin($dlat / 2) ** 2 + cos(deg2rad($prev_lat)) * cos(deg2rad($lat)) * sin($dlng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earth_radius * $c;
        $total_distance += $distance;
    }

    $waypoints[] = [
        'LocationName' => $row['LocationName'],
        'Latitude' => $lat,
        'Longitude' => $lng,
        'SequenceNumber' => $sequence
    ];

    $prev_lat = $lat;
    $prev_lng = $lng;
}

// Estimated values
$estimated_time = $total_distance * 2;   // 2 minutes per km
$estimated_fuel = $total_distance * 0.2; // 0.2 liters per km
$total_cost = $estimated_fuel * $route_data['FuelPrice'];

// Return JSON response
echo json_encode([
    'route' => [
        'TotalDistance' => round($total_distance, 2),
        'EstimatedTime' => round($estimated_time, 2),
        'EstimatedFuel' => round($estimated_fuel, 2),
        'TotalCost' => round($total_cost, 2)
    ],
    'waypoints' => $waypoints
]);
