<?php
session_start();
include '../includes/db_connection.php';
include '../includes/config.php';

header('Content-Type: application/json');

$category = $_GET['category'] ?? '';

if (empty($category)) {
    http_response_code(400);
    echo json_encode(['error' => 'No category provided']);
    exit;
}

try {
    // First get the route optimization record
    $query = "SELECT ro.*, c.*, GROUP_CONCAT(rw.name) as waypoint_names, 
              GROUP_CONCAT(rw.coordinates) as waypoint_coordinates,
              GROUP_CONCAT(rw.id) as waypoint_orders
              FROM route_optimizations ro 
              JOIN clusters c ON ro.cluster_category = c.ClusterCategory 
              LEFT JOIN route_waypoints rw ON ro.id = rw.route_id
              WHERE ro.cluster_category = ?
              GROUP BY ro.id";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 's', $category);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $route = mysqli_fetch_assoc($result);

    if ($route) {
        // Then get the waypoints
        $waypoints_query = "SELECT * FROM route_waypoints WHERE route_id = ? ORDER BY id";
        $stmt = mysqli_prepare($conn, $waypoints_query);
        mysqli_stmt_bind_param($stmt, 'i', $route['id']);
        mysqli_stmt_execute($stmt);
        $waypoints_result = mysqli_stmt_get_result($stmt);
        $waypoints = mysqli_fetch_all($waypoints_result, MYSQLI_ASSOC);
        
        // Create a structured response
        echo json_encode([
            'cluster_info' => [
                'category' => $route['cluster_category'],
                'tonner' => $route['Tonner'],
                'kmradius' => $route['KMRADIUS'],
                'fuel_price' => $route['FuelPrice'],
                'rate_amount' => $route['RateAmount']
            ],
            'route' => [
                'start_point' => [
                    'name' => $route['start_location'],
                    'coordinates' => $route['start_coordinates']
                ],
                'waypoints' => array_map(function($waypoint) {
                    return [
                        'name' => $waypoint['name'],
                        'coordinates' => $waypoint['coordinates'],
                        'order' => $waypoint['id']
                    ];
                }, $waypoints),
                'metrics' => [
                    'total_distance' => $route['KMRADIUS'] * 2 . ' km',
                    'total_time' => round($route['KMRADIUS'] * 2 / 40, 1) . ' hrs',
                    'fuel_cost' => '₱' . number_format($route['FuelPrice'] * ($route['KMRADIUS'] * 2 / 8), 2)
                ]
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 