<?php
include '../includes/db_connection.php';

if (isset($_GET['uniqueClusterId'])) {
    $uniqueClusterId = $_GET['uniqueClusterId'];

    $query = "SELECT UniqueClusterID, ClusterID, ClusterCategory, LocationsInCluster, Tonner, KMRADIUS, FuelPrice, RateAmount FROM clusters WHERE UniqueClusterID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $uniqueClusterId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(["error" => "No data found for the selected cluster."]);
    }

    $stmt->close();
}
$conn->close();
?>
