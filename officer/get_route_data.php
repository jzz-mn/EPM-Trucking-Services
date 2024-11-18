<?php
include '../includes/db_connection.php';

header('Content-Type: application/json');

// Ensure a route_id parameter is passed
if (!isset($_GET['route_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Route ID is required']);
    exit;
}

$routeId = mysqli_real_escape_string($conn, $_GET['route_id']);

// Fetch route details for the selected route
$routeQuery = "SELECT StartLocation, EndLocation, TotalDistance, EstimatedFuel, EstimatedTime, FuelPrice, TotalCost 
               FROM route_optimization 
               WHERE RouteID = ?";

$stmt = mysqli_prepare($conn, $routeQuery);
mysqli_stmt_bind_param($stmt, 'i', $routeId);
mysqli_stmt_execute($stmt);
$routeResult = mysqli_stmt_get_result($stmt);

$route = mysqli_fetch_assoc($routeResult);

if (!$route) {
    http_response_code(404);
    echo json_encode(['error' => 'No route found for the specified Route ID']);
    exit;
}

// Fetch waypoints for the selected route
$waypointsQuery = "SELECT LocationName, Latitude, Longitude, SequenceNumber 
                   FROM route_waypoints 
                   WHERE RouteID = ? 
                   ORDER BY SequenceNumber ASC";

$stmt = mysqli_prepare($conn, $waypointsQuery);
mysqli_stmt_bind_param($stmt, 'i', $routeId);
mysqli_stmt_execute($stmt);
$waypointsResult = mysqli_stmt_get_result($stmt);

$waypoints = [];
while ($row = mysqli_fetch_assoc($waypointsResult)) {
    $waypoints[] = [
        'LocationName' => $row['LocationName'],
        'Latitude' => floatval($row['Latitude']),
        'Longitude' => floatval($row['Longitude']),
        'SequenceNumber' => $row['SequenceNumber'],
    ];
}

// Function to get directions steps from OSRM API
function get_directions_steps($waypoints) {
    // Build the coordinates string for OSRM API
    $coords = [];
    foreach ($waypoints as $wp) {
        $coords[] = $wp['Longitude'] . ',' . $wp['Latitude'];
    }
    $coords_str = implode(';', $coords);

    // OSRM API endpoint
    $osrm_url = "http://router.project-osrm.org/route/v1/driving/" . $coords_str . "?overview=false&geometries=geojson&steps=true";

    // Make the request to OSRM
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $osrm_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout after 10 seconds
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code != 200) {
        return null;
    }

    $data = json_decode($response, true);
    if ($data['code'] != 'Ok') {
        return null;
    }

    $directions = [];

    // Iterate through legs and steps to build instructions
    foreach ($data['routes'][0]['legs'] as $leg) {
        foreach ($leg['steps'] as $step) {
            // Build instruction
            $maneuver = $step['maneuver'];
            $instruction = '';

            // Determine the type of maneuver
            $type = isset($maneuver['type']) ? $maneuver['type'] : '';
            $modifier = isset($maneuver['modifier']) ? $maneuver['modifier'] : '';
            $stepName = isset($step['name']) ? $step['name'] : '';

            // Map OSRM maneuver types and modifiers to human-readable instructions
            if ($type === 'turn') {
                switch ($modifier) {
                    case 'sharp left':
                        $instruction = "Make a sharp left onto {$stepName}";
                        break;
                    case 'left':
                        $instruction = "Turn left onto {$stepName}";
                        break;
                    case 'slight left':
                        $instruction = "Make a slight left onto {$stepName}";
                        break;
                    case 'straight':
                        $instruction = "Continue straight onto {$stepName}";
                        break;
                    case 'slight right':
                        $instruction = "Make a slight right onto {$stepName}";
                        break;
                    case 'right':
                        $instruction = "Turn right onto {$stepName}";
                        break;
                    case 'sharp right':
                        $instruction = "Make a sharp right onto {$stepName}";
                        break;
                    default:
                        $instruction = "Turn onto {$stepName}";
                        break;
                }
            } elseif ($type === 'merge') {
                $instruction = "Merge onto {$stepName}";
            } elseif ($type === 'fork') {
                $instruction = "Take the fork onto {$stepName}";
            } elseif ($type === 'end of road') {
                $instruction = "Turn around or continue on {$stepName}";
            } elseif ($type === 'on ramp') {
                $instruction = "Take the ramp onto {$stepName}";
            } elseif ($type === 'off ramp') {
                $instruction = "Take the off ramp onto {$stepName}";
            } elseif ($type === 'roundabout') {
                $exit = isset($maneuver['exit']) ? $maneuver['exit'] : '';
                $instruction = "Enter the roundabout and take the {$exit} exit onto {$stepName}";
            } else {
                $instruction = "Proceed onto {$stepName}";
            }

            // Distance
            $distance = isset($step['distance']) ? round($step['distance'], 1) . ' m' : '';

            // Combine instruction and distance
            if ($distance) {
                $instruction .= ' (' . $distance . ')';
            }

            $directions[] = $instruction;
        }
    }

    return $directions;
}

// Get directions steps
$directions = get_directions_steps($waypoints);

// Combine route, waypoints, and directions into the response
$response = [
    'route' => $route,
    'waypoints' => $waypoints,
    'directions' => $directions ? $directions : []
];

echo json_encode($response);
?>
