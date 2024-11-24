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
    // Fetch route optimization data
    $query = "SELECT ro.*, c.LocationsInCluster, c.Tonner, c.KMRADIUS, c.FuelPrice 
              FROM route_optimizations ro
              JOIN clusters c ON ro.cluster_category = c.ClusterCategory 
              WHERE ro.cluster_category = ?
              ORDER BY ro.created_at DESC LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 's', $category);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $routeData = mysqli_fetch_assoc($result);

    if ($routeData) {
        // Decode JSON waypoints
        $waypoints = json_decode($routeData['waypoints'], true);
        
        echo json_encode([
            'cluster_info' => [
                'category' => $routeData['cluster_category'],
                'locations' => $routeData['LocationsInCluster'],
                'tonner' => $routeData['Tonner'],
                'kmradius' => $routeData['KMRADIUS'],
                'fuel_price' => $routeData['FuelPrice']
            ],
            'route' => $waypoints,
            'start_location' => [
                'name' => $routeData['start_location'],
                'coordinates' => $routeData['start_coordinates']
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