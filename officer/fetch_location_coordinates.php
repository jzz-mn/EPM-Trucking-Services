<?php
include '../includes/db_connection.php';

$location = $_GET['location'] ?? '';

$query = "SELECT Latitude, Longitude FROM clusters WHERE LocationsInCluster LIKE '%$location%'";
$result = mysqli_query($conn, $query);

if ($row = mysqli_fetch_assoc($result)) {
    if ($row['Latitude'] != 0 && $row['Longitude'] != 0) {
        echo json_encode(['lat' => $row['Latitude'], 'lng' => $row['Longitude']]);
    } else {
        echo json_encode(['error' => "Coordinates missing for location: $location"]);
    }
} else {
    echo json_encode(['error' => "Location not found: $location"]);
}
?>
